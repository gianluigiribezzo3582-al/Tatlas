<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable([
    'artist_id', 'image_path', 'thumbnail_path', 'width', 'height', 'description', 'body_part', 'is_color', 'position',
])]
#[Hidden(['image_embedding', 'caption_embedding'])]
class Work extends Model
{
    protected function casts(): array
    {
        return [
            'is_color' => 'boolean',
            'hidden_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Artist, $this> */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    /** @return BelongsToMany<Style, $this> */
    public function styles(): BelongsToMany
    {
        return $this->belongsToMany(Style::class, 'work_style')->withPivot(['weight', 'source'])->withTimestamps();
    }

    /** @return MorphToMany<Tag, $this> */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
