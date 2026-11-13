<?php

namespace Database\Factories;

use App\Enums\StudioSource;
use App\Models\Studio;
use App\Support\Geo\Point;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Studio>
 */
class StudioFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company().' Tattoo';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            // Around Bari city centre.
            'location' => new Point(
                lat: fake()->randomFloat(5, 41.09, 41.14),
                lng: fake()->randomFloat(5, 16.84, 16.90),
            ),
            'street' => fake()->streetName(),
            'housenumber' => (string) fake()->buildingNumber(),
            'postcode' => '70121',
            'city' => 'Bari',
            'country_code' => 'IT',
            'source' => StudioSource::User,
        ];
    }

    public function at(float $lat, float $lng): static
    {
        return $this->state(fn () => ['location' => new Point(lat: $lat, lng: $lng)]);
    }

    public function fromOsm(int $osmId, string $osmType = 'node'): static
    {
        return $this->state(fn () => [
            'source' => StudioSource::Osm,
            'osm_type' => $osmType,
            'osm_id' => $osmId,
            'osm_synced_at' => now(),
        ]);
    }
}
