<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AreaRequest;
use App\Http\Resources\V1\ArtistResource;
use App\Models\Artist;
use App\Services\Search\ArtistSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArtistController extends Controller
{
    public function index(AreaRequest $request, ArtistSearch $search): AnonymousResourceCollection
    {
        $artists = $search->search($request->filter());

        return ArtistResource::collection($artists)->additional([
            'meta' => ['count' => $artists->count()],
        ]);
    }

    public function show(Artist $artist): ArtistResource
    {
        abort_if($artist->hidden_at !== null, 404);

        $artist->load([
            'styles',
            'works' => fn ($q) => $q->whereNull('hidden_at')->with('styles'),
            'studios' => fn ($q) => $q->visible()
                ->where(fn ($q) => $q->where('artist_studio.ends_at', '>=', now()->toDateString())
                    ->orWhereNull('artist_studio.ends_at'))
                ->orderBy('artist_studio.type', 'desc')
                ->orderBy('artist_studio.starts_at'),
        ]);

        return new ArtistResource($artist);
    }
}
