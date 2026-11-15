<?php

return [
    // Public web base URL (artist pages, share links, Android App Links).
    'public_url' => env('TATLAS_PUBLIC_URL', 'https://tatlas.app'),

    // New artist profiles stay out of public search until an admin verifies them.
    'require_artist_verification' => (bool) env('TATLAS_REQUIRE_ARTIST_VERIFICATION', true),

    // MVP limits.
    'max_works_per_artist' => (int) env('TATLAS_MAX_WORKS', 12),
    'max_styles_per_artist' => 5,
];
