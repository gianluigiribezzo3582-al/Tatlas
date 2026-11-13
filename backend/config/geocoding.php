<?php

return [
    'photon' => [
        'url' => env('PHOTON_URL', 'https://photon.komoot.io'),
        'per_minute' => (int) env('PHOTON_RATE_LIMIT_PER_MINUTE', 30),
        'user_agent' => env('OSM_USER_AGENT', 'Tatlas/0.1'),
    ],
];
