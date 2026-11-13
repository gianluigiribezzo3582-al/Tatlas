<?php

return [
    'overpass_url' => env('OSM_OVERPASS_URL', 'https://overpass-api.de/api/interpreter'),

    // south,west,north,east — default: Città Metropolitana di Bari.
    'default_bbox' => env('OSM_DEFAULT_BBOX', '40.72,16.25,41.32,17.53'),

    // Seconds; also passed to Overpass as [timeout:N].
    'timeout' => (int) env('OSM_OVERPASS_TIMEOUT', 90),

    // Overpass asks clients to identify themselves. Keep it generic (no personal data).
    'user_agent' => env('OSM_USER_AGENT', 'Tatlas/0.1 (OSM tattoo studio importer)'),

    'attribution' => '© OpenStreetMap contributors (ODbL)',
    'attribution_url' => 'https://www.openstreetmap.org/copyright',
];
