<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Slugger
{
    /**
     * Builds a slug unique within the model's table: "name", then "name-city", then "name-city-2"...
     *
     * @param  class-string<Model>  $model
     */
    public static function unique(string $model, string $name, ?string $qualifier = null): string
    {
        $base = Str::slug($name) ?: 'studio';
        $candidates = [$base];
        if ($qualifier !== null && Str::slug($qualifier) !== '') {
            $candidates[] = $base.'-'.Str::slug($qualifier);
        }

        foreach ($candidates as $candidate) {
            if (! $model::where('slug', $candidate)->exists()) {
                return $candidate;
            }
        }

        $stem = end($candidates);
        for ($i = 2; ; $i++) {
            if (! $model::where('slug', "{$stem}-{$i}")->exists()) {
                return "{$stem}-{$i}";
            }
        }
    }
}
