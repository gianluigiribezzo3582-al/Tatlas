<?php

namespace App\Http\Resources\V1;

use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Artist */
class ArtistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'bio' => $this->bio,
            'instagram_url' => $this->instagram_url,
            'website' => $this->website,
            'languages' => $this->languages,
            'accepts_walk_in' => $this->accepts_walk_in,
            'price_range' => $this->price_range,
            'is_verified' => $this->is_verified,
            'is_claimed' => $this->claimed_by_user_id !== null,
            'distance_m' => $this->when(isset($this->distance_m), fn () => (int) round((float) $this->distance_m)),
            'styles' => StyleResource::collection($this->whenLoaded('styles')),
            'works' => WorkResource::collection($this->whenLoaded('works')),
            'presence' => $this->whenPivotLoaded('artist_studio', fn () => new PresenceResource($this->resource)),
            'studios' => $this->whenLoaded('studios', fn () => $this->studios->map(fn ($studio) => [
                ...(new StudioResource($studio))->toArray($request),
                'presence' => (new PresenceResource($studio))->toArray($request),
            ])),
            'public_url' => rtrim(config('tatlas.public_url'), '/').'/artisti/'.$this->slug,
        ];
    }
}
