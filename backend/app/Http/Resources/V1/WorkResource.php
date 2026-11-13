<?php

namespace App\Http\Resources\V1;

use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Work */
class WorkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk(config('filesystems.works_disk', 'public'));

        return [
            'id' => $this->id,
            'image_url' => $disk->url($this->image_path),
            'thumbnail_url' => $this->thumbnail_path ? $disk->url($this->thumbnail_path) : $disk->url($this->image_path),
            'width' => $this->width,
            'height' => $this->height,
            'description' => $this->description,
            'body_part' => $this->body_part,
            'is_color' => $this->is_color,
            'styles' => $this->whenLoaded('styles', fn () => $this->styles->pluck('slug')),
        ];
    }
}
