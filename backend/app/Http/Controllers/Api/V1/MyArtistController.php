<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ArtistProfileRequest;
use App\Http\Resources\V1\ArtistResource;
use App\Models\Artist;
use App\Models\Style;
use App\Support\Slugger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The authenticated user's own artist profile. A user owns at most one profile;
 * new profiles are hidden from public search until an admin verifies them.
 */
class MyArtistController extends Controller
{
    public function show(Request $request): ArtistResource|JsonResponse
    {
        $artist = $request->user()->artistProfile();
        if ($artist === null) {
            return response()->json(['data' => null]);
        }

        return $this->resource($artist);
    }

    public function store(ArtistProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user->artistProfile() !== null, 409, 'You already have an artist profile.');

        $artist = DB::transaction(function () use ($request, $user) {
            $data = $request->validated();
            $artist = new Artist(collect($data)->except('styles')->all());
            $artist->slug = Slugger::unique(Artist::class, $data['name']);
            $artist->claimed_by_user_id = $user->id;
            $artist->save();
            $this->syncStyles($artist, $data['styles'] ?? []);

            if ($user->role === UserRole::User) {
                $user->forceFill(['role' => UserRole::Artist])->save();
            }

            return $artist;
        });

        return $this->resource($artist)->response()->setStatusCode(201);
    }

    public function update(ArtistProfileRequest $request): ArtistResource
    {
        $artist = $request->user()->artistProfile();
        abort_if($artist === null, 404);

        $data = $request->validated();
        $artist->fill(collect($data)->except('styles')->all())->save();
        if (array_key_exists('styles', $data)) {
            $this->syncStyles($artist, $data['styles']);
        }

        return $this->resource($artist);
    }

    /** @param list<string> $slugs */
    private function syncStyles(Artist $artist, array $slugs): void
    {
        $ids = Style::whereIn('slug', $slugs)->pluck('id');
        $artist->styles()->sync($ids->mapWithKeys(fn ($id) => [$id => ['weight' => 1, 'source' => 'artist']])->all());
    }

    private function resource(Artist $artist): ArtistResource
    {
        $artist->load(['styles', 'works.styles', 'studios']);

        return (new ArtistResource($artist))->additional(['meta' => [
            'is_public' => $artist->isPubliclyVisible(),
            'pending_verification' => ! $artist->is_verified,
            'works_limit' => config('tatlas.max_works_per_artist'),
        ]]);
    }
}
