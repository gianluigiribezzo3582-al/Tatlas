<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A model loaded through artist_studio (an Artist from a studio, or a Studio from an artist).
 */
class PresenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->pivot->type->value,
            'starts_at' => $this->pivot->starts_at?->toDateString(),
            'ends_at' => $this->pivot->ends_at?->toDateString(),
        ];
    }
}
