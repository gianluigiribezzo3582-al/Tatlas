<?php

namespace App\Services\Geocoding;

use App\Support\Geo\Point;

interface Geocoder
{
    /**
     * Forward geocoding of a free-text address.
     *
     * @return list<GeocodeResult>
     *
     * @throws GeocodingUnavailable
     */
    public function search(string $query, ?Point $near = null, int $limit = 5): array;
}
