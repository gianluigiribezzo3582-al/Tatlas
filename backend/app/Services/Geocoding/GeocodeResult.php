<?php

namespace App\Services\Geocoding;

use App\Support\Geo\Point;

final readonly class GeocodeResult
{
    public function __construct(
        public Point $point,
        public string $label,
        public ?string $street = null,
        public ?string $housenumber = null,
        public ?string $postcode = null,
        public ?string $city = null,
        public ?string $countryCode = null,
    ) {}
}
