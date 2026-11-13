<?php

namespace App\Models;

use App\Casts\AsPoint;
use App\Enums\PresenceType;
use App\Enums\StudioSource;
use App\Support\Geo\Point;
use Database\Factories\StudioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property Point $location
 */
#[Fillable([
    'name', 'slug', 'location', 'street', 'housenumber', 'postcode', 'city', 'country_code',
    'opening_hours', 'phone', 'email', 'website', 'instagram_url',
    'source', 'osm_type', 'osm_id', 'osm_tags', 'osm_synced_at', 'osm_missing_since', 'locked_fields',
])]
class Studio extends Model
{
    /** @use HasFactory<StudioFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'location' => AsPoint::class,
            'source' => StudioSource::class,
            'osm_id' => 'integer',
            'osm_tags' => 'array',
            'locked_fields' => 'array',
            'osm_synced_at' => 'datetime',
            'osm_missing_since' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<Artist, $this, ArtistStudio> */
    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class)
            ->using(ArtistStudio::class)
            ->withPivot(['id', 'type', 'starts_at', 'ends_at'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Artist, $this, ArtistStudio> */
    public function residents(): BelongsToMany
    {
        return $this->artists()->wherePivot('type', PresenceType::Resident->value);
    }

    /** @param Builder<Studio> $query */
    public function scopeVisible(Builder $query): void
    {
        $query->whereNull($query->qualifyColumn('hidden_at'));
    }

    public function isLocked(string $field): bool
    {
        return in_array($field, $this->locked_fields ?? [], true);
    }
}
