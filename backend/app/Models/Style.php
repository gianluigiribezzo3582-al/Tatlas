<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['parent_id', 'slug', 'name_it', 'name_en', 'synonyms', 'sort_order'])]
class Style extends Model
{
    protected function casts(): array
    {
        return ['synonyms' => 'array'];
    }

    /** @return BelongsTo<Style, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Style::class, 'parent_id');
    }

    /** @return HasMany<Style, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Style::class, 'parent_id')->orderBy('sort_order');
    }

    public function name(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'en' ? $this->name_en : $this->name_it;
    }
}
