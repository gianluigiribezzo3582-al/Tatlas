<?php

namespace App\Http\Resources\V1;

use App\Enums\StudioSource;
use App\Models\Studio;
use Illuminate\Http\Request;

/** @mixin Studio */
class StudioDetailResource extends StudioResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'opening_hours' => $this->opening_hours,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'instagram_url' => $this->instagram_url,
            'artists' => ArtistResource::collection($this->whenLoaded('artists')),
            'attribution' => $this->source === StudioSource::Osm ? [
                'text' => config('osm.attribution'),
                'url' => config('osm.attribution_url'),
                'osm_url' => "https://www.openstreetmap.org/{$this->osm_type}/{$this->osm_id}",
            ] : null,
        ];
    }
}
