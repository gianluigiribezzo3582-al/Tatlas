<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StudioSource;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\StudioDetailResource;
use App\Http\Resources\V1\StudioResource;
use App\Models\Studio;
use App\Services\Geocoding\Geocoder;
use App\Services\Geocoding\GeocodingUnavailable;
use App\Services\Osm\OsmStudioMapper;
use App\Support\Geo\Point;
use App\Support\Slugger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Studios created or managed by users (as opposed to OSM imports).
 */
class ManageStudioController extends Controller
{
    /** Name/city search, e.g. to pick the studio you work in. */
    public function search(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
        ]);
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $data['q']).'%';

        $query = Studio::visible()->select('studios.*')
            ->where(fn ($q) => $q->where('name', 'ilike', $like)->orWhere('city', 'ilike', $like));

        if (isset($data['lat'])) {
            $query->selectRaw(
                'ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distance_m',
                [$data['lng'], $data['lat']],
            )->orderBy('distance_m');
        } else {
            $query->orderBy('name');
        }

        return StudioResource::collection($query->limit(20)->get());
    }

    public function store(Request $request, Geocoder $geocoder): JsonResponse
    {
        $data = $request->validate($this->rules(creating: true));

        if (isset($data['lat'], $data['lng'])) {
            $point = new Point((float) $data['lat'], (float) $data['lng']);
        } else {
            $address = trim(($data['street'] ?? '').' '.($data['housenumber'] ?? '').', '.($data['postcode'] ?? '').' '.$data['city']);
            try {
                $result = $geocoder->search($address, limit: 1)[0] ?? null;
            } catch (GeocodingUnavailable) {
                return response()->json([
                    'message' => 'Address lookup is unavailable right now: place the pin on the map.',
                    'code' => 'geocoding_unavailable',
                ], 503);
            }
            if ($result === null) {
                return response()->json([
                    'message' => 'Address not found: check it or place the pin on the map.',
                    'errors' => ['street' => ['Address not found.']],
                    'code' => 'address_not_found',
                ], 422);
            }
            $point = $result->point;
        }

        $studio = Studio::create([
            ...collect($data)->except(['lat', 'lng'])->all(),
            'location' => $point,
            'slug' => Slugger::unique(Studio::class, $data['name'], $data['city']),
            'source' => StudioSource::User,
            'country_code' => $data['country_code'] ?? 'IT',
            'created_by_user_id' => $request->user()->id,
        ]);
        // The creator manages the studio they added.
        $studio->forceFill(['claimed_by_user_id' => $request->user()->id])->save();

        return (new StudioDetailResource($studio->load('artists')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Studio $studio): StudioDetailResource
    {
        Gate::authorize('update', $studio);
        $data = $request->validate($this->rules(creating: false));

        if (isset($data['lat'], $data['lng'])) {
            $data['location'] = new Point((float) $data['lat'], (float) $data['lng']);
        }
        unset($data['lat'], $data['lng']);

        $studio->fill($data);
        // Owner edits win over future OSM resyncs.
        $locked = array_values(array_unique([
            ...$studio->locked_fields ?? [],
            ...array_intersect(array_keys($studio->getDirty()), OsmStudioMapper::SYNCED_FIELDS),
        ]));
        $studio->locked_fields = $locked;
        $studio->save();

        return new StudioDetailResource($studio->load('artists'));
    }

    /** @return array<string, mixed> */
    private function rules(bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return [
            'name' => [$req, 'string', 'min:2', 'max:120'],
            'street' => ['nullable', 'string', 'max:120'],
            'housenumber' => ['nullable', 'string', 'max:16'],
            'postcode' => ['nullable', 'string', 'max:16'],
            'city' => [$req, 'string', 'max:80'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'instagram_url' => ['nullable', 'url:https', 'max:255'],
        ];
    }
}
