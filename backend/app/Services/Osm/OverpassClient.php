<?php

namespace App\Services\Osm;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Overpass API client. Public instances are shared and rate limited:
 * we send one query per import, identify ourselves and back off on 429/504.
 */
class OverpassClient
{
    public function __construct(
        private readonly string $url,
        private readonly int $timeout,
        private readonly string $userAgent,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            url: config('osm.overpass_url'),
            timeout: config('osm.timeout'),
            userAgent: config('osm.user_agent'),
        );
    }

    public static function tattooShopsQuery(BoundingBox $bbox, int $timeout): string
    {
        return <<<OVERPASS
            [out:json][timeout:{$timeout}];
            nwr["shop"="tattoo"]({$bbox->toOverpass()});
            out center tags;
            OVERPASS;
    }

    /**
     * @return list<array<string, mixed>> raw Overpass elements
     */
    public function tattooShops(BoundingBox $bbox): array
    {
        try {
            $response = $this->request()
                ->asForm()
                ->post($this->url, ['data' => self::tattooShopsQuery($bbox, $this->timeout)])
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Overpass request failed: HTTP '.$e->response->status(), previous: $e);
        }

        $elements = $response->json('elements');
        if (! is_array($elements)) {
            throw new RuntimeException('Overpass returned an unexpected payload.');
        }

        // Overpass reports server-side timeouts/memory errors in "remark" with a 200 status.
        $remark = $response->json('remark');
        if (is_string($remark) && str_contains(strtolower($remark), 'error')) {
            throw new RuntimeException("Overpass error: {$remark}");
        }

        return array_values($elements);
    }

    private function request(): PendingRequest
    {
        return Http::withUserAgent($this->userAgent)
            ->acceptJson()
            ->timeout($this->timeout + 10)
            ->retry(
                times: 3,
                sleepMilliseconds: fn (int $attempt) => $attempt * 5000,
                when: fn ($e) => $e instanceof RequestException
                    && in_array($e->response->status(), [429, 502, 503, 504], true),
                throw: true,
            );
    }
}
