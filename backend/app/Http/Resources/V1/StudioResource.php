<?php

namespace App\Http\Resources\V1;

use App\Models\Studio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Studio */
class StudioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'location' => $this->location,
            'address' => [
                'street' => $this->street,
                'housenumber' => $this->housenumber,
                'postcode' => $this->postcode,
                'city' => $this->city,
                'country_code' => $this->country_code,
            ],
            'distance_m' => $this->when(isset($this->distance_m), fn () => (int) round((float) $this->distance_m)),
            'artists_count' => $this->when(isset($this->artists_count), fn () => (int) $this->artists_count),
            'has_upcoming_guest' => $this->when(isset($this->has_upcoming_guest), fn () => (bool) $this->has_upcoming_guest),
            'source' => $this->source->value,
        ];
    }
}
