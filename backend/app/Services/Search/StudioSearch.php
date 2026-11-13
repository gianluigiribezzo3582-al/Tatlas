<?php

namespace App\Services\Search;

use App\Enums\PresenceType;
use App\Models\Artist;
use App\Models\Studio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Geographic studio search backed by PostGIS (geography, metres).
 */
class StudioSearch
{
    /** @return Collection<int, Studio> */
    public function search(AreaFilter $filter): Collection
    {
        $query = Studio::query()->visible()->select('studios.*');

        if ($filter->bbox !== null) {
            [$w, $s, $e, $n] = $filter->bbox;
            $query->whereRaw(
                'ST_Intersects(studios.location, ST_MakeEnvelope(?, ?, ?, ?, 4326)::geography)',
                [$w, $s, $e, $n],
            );
        }

        if ($filter->hasOrigin()) {
            $query->selectRaw(
                'ST_Distance(studios.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distance_m',
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
            $query->orderBy('studios.name');
        }

        if ($filter->style !== null) {
            $styleIds = StyleTree::idsWithDescendants($filter->style);
            $query->whereHas('artists', fn (Builder $a) => $a->visible()
                ->whereHas('styles', fn (Builder $s) => $s->whereIn('styles.id', $styleIds)));
        }

        if ($filter->guestUpcoming) {
            $query->whereHas('artists', fn (Builder $a) => self::upcomingGuest($a->visible()));
        }

        return $query
            ->withCount(['artists as artists_count' => fn (Builder $a) => $a->visible()])
            ->withExists(['artists as has_upcoming_guest' => fn (Builder $a) => self::upcomingGuest($a)])
            ->limit($filter->limit)
            ->get();
    }

    /**
     * Guest presences that are ongoing or start within the horizon.
     *
     * @param  Builder<Artist>  $artists  constrained through the artist_studio pivot
     */
    public static function upcomingGuest(Builder $artists): Builder
    {
        $today = now()->toDateString();
        $horizon = now()->addDays(AreaFilter::GUEST_HORIZON_DAYS)->toDateString();

        return $artists
            ->where('artist_studio.type', PresenceType::Guest->value)
            ->where('artist_studio.starts_at', '<=', $horizon)
            ->where(fn (Builder $q) => $q
                ->where('artist_studio.ends_at', '>=', $today)
                ->orWhere(fn (Builder $q) => $q->whereNull('artist_studio.ends_at')
                    ->where('artist_studio.starts_at', '>=', $today)));
    }
}
