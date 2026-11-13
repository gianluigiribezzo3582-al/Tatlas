<?php

namespace App\Http\Resources\V1;

use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Work */
class WorkResource extends JsonResource
{
    /**
     * Local "public" disk: URL built from the current request host, so a phone on the LAN
     * gets a reachable address. Other disks (R2/S3 in production) use their own URL.
     */
    public static function url(string $path): string
    {
        $disk = config('filesystems.works_disk', 'public');

        return $disk === 'public'
            ? url('storage/'.ltrim($path, '/'))
            : Storage::disk($disk)->url($path);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image_url' => self::url($this->image_path),
            'thumbnail_url' => self::url($this->thumbnail_path ?? $this->image_path),
            'width' => $this->width,
            'height' => $this->height,
            'description' => $this->description,
            'body_part' => $this->body_part,
            'is_color' => $this->is_color,
            'styles' => $this->whenLoaded('styles', fn () => $this->styles->pluck('slug')),
        ];
    }
}
