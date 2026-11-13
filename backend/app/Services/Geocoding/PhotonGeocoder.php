<?php

namespace App\Services\Geocoding;

use App\Support\Geo\Point;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Photon (komoot) geocoder, OSM-based. The public instance is a shared resource:
 * results are cached and outgoing calls are globally rate limited. Point PHOTON_URL
 * to a self-hosted instance in production.
 */
class PhotonGeocoder implements Geocoder
{
    private const CACHE_DAYS = 30;

    public function __construct(
        private readonly string $url,
        private readonly int $perMinute,
        private readonly string $userAgent,
        private readonly string $lang = 'it',
    ) {}

    public function search(string $query, ?Point $near = null, int $limit = 5): array
    {
        $query = trim(preg_replace('/\s+/', ' ', $query) ?? '');
        if ($query === '') {
            return [];
        }

        $params = ['q' => $query, 'limit' => $limit, 'lang' => $this->lang];
        if ($near !== null) {
            $params += ['lat' => round($near->lat, 3), 'lon' => round($near->lng, 3)];
        }
        $key = 'geocode:photon:'.sha1(json_encode($params));

        if (($cached = Cache::get($key)) !== null) {
            return $cached;
        }

        if (RateLimiter::tooManyAttempts('photon', $this->perMinute)) {
            throw new GeocodingUnavailable('Geocoding rate limit reached.');
        }
        RateLimiter::hit('photon', 60);

        try {
            $response = Http::withUserAgent($this->userAgent)->timeout(10)
                ->get(rtrim($this->url, '/').'/api', $params);
        } catch (ConnectionException $e) {
            throw new GeocodingUnavailable('Geocoding service unreachable.', previous: $e);
        }

        if ($response->failed()) {
            throw new GeocodingUnavailable('Geocoding failed: HTTP '.$response->status());
        }

        $results = array_values(array_filter(array_map(
            fn (array $feature) => $this->toResult($feature),
            $response->json('features') ?? [],
        )));

        Cache::put($key, $results, now()->addDays(self::CACHE_DAYS));

        return $results;
    }

    /** @param array<string, mixed> $feature */
    private function toResult(array $feature): ?GeocodeResult
    {
        [$lng, $lat] = $feature['geometry']['coordinates'] ?? [null, null];
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }
        $p = $feature['properties'] ?? [];
        $label = implode(', ', array_filter([
            trim(($p['street'] ?? $p['name'] ?? '').' '.($p['housenumber'] ?? '')),
            trim(($p['postcode'] ?? '').' '.($p['city'] ?? '')),
        ]));

        return new GeocodeResult(
            point: new Point(lat: (float) $lat, lng: (float) $lng),
            label: $label !== '' ? $label : ($p['name'] ?? ''),
            street: $p['street'] ?? null,
            housenumber: $p['housenumber'] ?? null,
            postcode: $p['postcode'] ?? null,
            city: $p['city'] ?? null,
            countryCode: isset($p['countrycode']) ? strtoupper($p['countrycode']) : null,
        );
    }
}
