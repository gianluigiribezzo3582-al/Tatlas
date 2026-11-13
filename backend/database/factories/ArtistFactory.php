<?php

namespace Database\Factories;

use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Artist>
 */
class ArtistFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->firstName().' Ink';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'bio' => fake()->sentence(12),
            'languages' => ['it'],
            'accepts_walk_in' => fake()->boolean(),
        ];
    }
}
