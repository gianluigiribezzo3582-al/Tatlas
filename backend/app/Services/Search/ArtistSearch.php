<?php

namespace App\Services\Search;

use App\Enums\PresenceType;
use App\Models\Artist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;

/**
 * Artists located through their studios: residents, plus guests that are
 * ongoing or upcoming. distance_m is the nearest of their studios.
 */
class ArtistSearch
{
    /** @return Collection<int, Artist> */
    public function search(AreaFilter $filter): Collection
    {
        $today = now()->toDateString();
        $horizon = now()->addDays(AreaFilter::GUEST_HORIZON_DAYS)->toDateString();

        $query = Artist::query()->visible()
            ->select('artists.*')
            ->join('artist_studio', 'artist_studio.artist_id', '=', 'artists.id')
            ->join('studios', fn (JoinClause $j) => $j->on('studios.id', '=', 'artist_studio.studio_id')
                ->whereNull('studios.hidden_at'))
            ->where(fn ($q) => $q
                ->where('artist_studio.type', PresenceType::Resident->value)
                ->orWhere(fn ($q) => $q->where('artist_studio.type', PresenceType::Guest->value)
                    ->where('artist_studio.starts_at', '<=', $horizon)
                    ->where(fn ($q) => $q->where('artist_studio.ends_at', '>=', $today)
                        ->orWhere(fn ($q) => $q->whereNull('artist_studio.ends_at')
                            ->where('artist_studio.starts_at', '>=', $today)))))
            ->groupBy('artists.id');

        if ($filter->guestUpcoming) {
            $query->where('artist_studio.type', PresenceType::Guest->value);
        }

        if ($filter->bbox !== null) {
            [$w, $s, $e, $n] = $filter->bbox;
            $query->whereRaw(
                'ST_Intersects(studios.location, ST_MakeEnvelope(?, ?, ?, ?, 4326)::geography)',
                [$w, $s, $e, $n],
            );
        }

        if ($filter->hasOrigin()) {
            $query->selectRaw(
                'MIN(ST_Distance(studios.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography)) AS distance_m',
                [$filter->lng, $filter->lat],
            );
            if ($filter->radius !== null) {
                $query->whereRaw(
                    'ST_DWithin(studios.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                    [$filter->lng, $filter->lat, $filter->radius],
                );
            }
            $query->orderBy('distance_m');
        } else {
            $query->orderBy('artists.name');
        }

        if ($filter->style !== null) {
            $styleIds = StyleTree::idsWithDescendants($filter->style);
            $query->whereHas('styles', fn (Builder $s) => $s->whereIn('styles.id', $styleIds));
        }

        return $query->with(['styles', 'works' => fn ($w) => $w->whereNull('hidden_at')->limit(3)])
            ->limit($filter->limit)
            ->get();
    }
}
