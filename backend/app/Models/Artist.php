<?php

namespace App\Models;

use Database\Factories\ArtistFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable([
    'name', 'slug', 'bio', 'instagram_url', 'website', 'languages', 'accepts_walk_in', 'price_range',
])]
#[Hidden(['text_embedding'])]
class Artist extends Model
{
    /** @use HasFactory<ArtistFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'accepts_walk_in' => 'boolean',
            'price_range' => 'integer',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    /** @return BelongsToMany<Studio, $this, ArtistStudio> */
    public function studios(): BelongsToMany
    {
        return $this->belongsToMany(Studio::class)
            ->using(ArtistStudio::class)
            ->withPivot(['id', 'type', 'starts_at', 'ends_at'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Style, $this> */
    public function styles(): BelongsToMany
    {
        return $this->belongsToMany(Style::class)->withPivot(['weight', 'source'])->withTimestamps();
    }

    /** @return HasMany<Work, $this> */
    public function works(): HasMany
    {
        return $this->hasMany(Work::class)->orderBy('position');
    }

    /** @return MorphToMany<Tag, $this> */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /** @param Builder<Artist> $query */
    public function scopeVisible(Builder $query): void
    {
        $query->whereNull($query->qualifyColumn('hidden_at'));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
