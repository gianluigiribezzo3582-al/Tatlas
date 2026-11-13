<?php

namespace App\Http\Resources\V1;

use App\Models\Style;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Style */
class StyleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name($request->getPreferredLanguage(['it', 'en'])),
            'name_it' => $this->name_it,
            'name_en' => $this->name_en,
            'parent' => $this->whenLoaded('parent', fn () => $this->parent?->slug),
            'weight' => $this->whenPivotLoaded('artist_style', fn () => (float) $this->pivot->weight),
            'children' => StyleResource::collection($this->whenLoaded('children')),
        ];
    }
}
