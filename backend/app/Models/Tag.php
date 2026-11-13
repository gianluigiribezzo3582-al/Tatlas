<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug'])]
class Tag extends Model
{
    /** Finds or creates the tag for a free-text label, normalized by slug. */
    public static function findOrCreateFromName(string $name): self
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');

        return static::firstOrCreate(['slug' => Str::slug($name)], ['name' => mb_strtolower($name)]);
    }
}
