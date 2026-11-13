<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AreaRequest;
use App\Http\Resources\V1\StudioDetailResource;
use App\Http\Resources\V1\StudioResource;
use App\Models\Studio;
use App\Services\Search\StudioSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudioController extends Controller
{
    public function index(AreaRequest $request, StudioSearch $search): AnonymousResourceCollection
    {
        $studios = $search->search($request->filter());

        return StudioResource::collection($studios)->additional([
            'meta' => [
                'count' => $studios->count(),
                'attribution' => config('osm.attribution'),
            ],
        ]);
    }

    public function show(Studio $studio): StudioDetailResource
    {
        abort_if($studio->hidden_at !== null, 404);

        $studio->load(['artists' => fn ($q) => $q->visible()
            ->where(fn ($q) => $q->where('artist_studio.ends_at', '>=', now()->toDateString())
                ->orWhereNull('artist_studio.ends_at'))
            ->orderBy('artist_studio.type', 'desc') // resident first
            ->orderBy('artist_studio.starts_at')
            ->with('styles'),
        ]);

        return new StudioDetailResource($studio);
    }
}
