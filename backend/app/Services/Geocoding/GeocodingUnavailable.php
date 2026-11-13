<?php

namespace App\Services\Geocoding;

use RuntimeException;

/** Rate limit reached or upstream error: callers should let the user place the pin manually. */
class GeocodingUnavailable extends RuntimeException {}
