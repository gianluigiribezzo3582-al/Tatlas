<?php

return [
    // Public web base URL (artist pages, share links, Android App Links).
    'public_url' => env('TATLAS_PUBLIC_URL', 'https://tatlas.app'),

    // Android App Links: package and comma-separated SHA-256 signing certificate fingerprints
    // (from `eas credentials` or the Play Console). Empty → assetlinks.json serves [].
    'android_package' => env('TATLAS_ANDROID_PACKAGE', 'app.tatlas'),
    'android_cert_sha256' => env('TATLAS_ANDROID_CERT_SHA256', ''),

    // Shown in the privacy notice. Replace before going public.
    'privacy' => [
        'controller' => env('TATLAS_PRIVACY_CONTROLLER', '[Ragione sociale del titolare da definire]'),
        'email' => env('TATLAS_PRIVACY_EMAIL', 'privacy@tatlas.app'),
    ],

    // New artist profiles stay out of public search until an admin verifies them.
    'require_artist_verification' => (bool) env('TATLAS_REQUIRE_ARTIST_VERIFICATION', true),

    // MVP limits.
    'max_works_per_artist' => (int) env('TATLAS_MAX_WORKS', 12),
    'max_styles_per_artist' => 5,
];
