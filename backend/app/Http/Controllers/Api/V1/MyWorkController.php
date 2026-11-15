<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\WorkResource;
use App\Models\Artist;
use App\Models\Style;
use App\Models\Work;
use App\Services\Images\ImageProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Portfolio of the authenticated artist (max N works in the MVP).
 */
class MyWorkController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return WorkResource::collection($this->artist($request)->works()->with('styles')->get());
    }

    public function store(Request $request, ImageProcessor $images): JsonResponse
    {
        $artist = $this->artist($request);
        $limit = config('tatlas.max_works_per_artist');
        abort_if($artist->works()->count() >= $limit, 422, "Portfolio limit reached ({$limit} works).");

        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:12288', 'dimensions:min_width=300,min_height=300'],
            ...$this->metaRules(),
        ]);

        try {
            $processed = $images->process($request->file('image')->getRealPath());
        } catch (InvalidArgumentException) {
            return response()->json(['message' => 'Unsupported or corrupted image.', 'errors' => ['image' => ['Unsupported image.']]], 422);
        }

        $disk = Storage::disk(config('filesystems.works_disk', 'public'));
        $base = "works/{$artist->id}/".Str::uuid();
        $disk->put("{$base}.jpg", $processed['image']);
        $disk->put("{$base}_thumb.jpg", $processed['thumbnail']);

        $work = DB::transaction(function () use ($artist, $data, $base, $processed) {
            $work = Work::create([
                'artist_id' => $artist->id,
                'image_path' => "{$base}.jpg",
                'thumbnail_path' => "{$base}_thumb.jpg",
                'width' => $processed['width'],
                'height' => $processed['height'],
                'description' => $data['description'] ?? null,
                'body_part' => $data['body_part'] ?? null,
                'is_color' => $data['is_color'] ?? null,
                'position' => (int) $artist->works()->max('position') + 1,
            ]);
            $this->syncStyles($work, $data['styles'] ?? []);

            return $work;
        });

        return (new WorkResource($work->load('styles')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Work $work): WorkResource
    {
        $this->own($request, $work);
        $data = $request->validate([...$this->metaRules(), 'position' => ['sometimes', 'integer', 'min:0', 'max:999']]);

        $work->fill(collect($data)->except('styles')->all())->save();
        if (array_key_exists('styles', $data)) {
            $this->syncStyles($work, $data['styles']);
        }

        return new WorkResource($work->load('styles'));
    }

    public function destroy(Request $request, Work $work): JsonResponse
    {
        $this->own($request, $work);

        $disk = Storage::disk(config('filesystems.works_disk', 'public'));
        $disk->delete(array_filter([$work->image_path, $work->thumbnail_path]));
        $work->delete();

        return response()->json(status: 204);
    }

    /** @return array<string, mixed> */
    private function metaRules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:500'],
            'body_part' => ['nullable', Rule::in(Work::BODY_PARTS)],
            'is_color' => ['nullable', 'boolean'],
            'styles' => ['sometimes', 'array', 'max:3'],
            'styles.*' => ['string', 'distinct', Rule::exists('styles', 'slug')],
        ];
    }

    /** @param list<string> $slugs */
    private function syncStyles(Work $work, array $slugs): void
    {
        $ids = Style::whereIn('slug', $slugs)->pluck('id');
        $work->styles()->sync($ids->mapWithKeys(fn ($id) => [$id => ['weight' => 1, 'source' => 'artist']])->all());
    }

    private function artist(Request $request): Artist
    {
        $artist = $request->user()->artistProfile();
        abort_if($artist === null, 409, 'Create your artist profile first.');

        return $artist;
    }

    private function own(Request $request, Work $work): void
    {
        abort_unless($work->artist_id === $request->user()->artistProfile()?->id, 404);
    }
}
