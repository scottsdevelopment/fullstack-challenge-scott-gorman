<?php

return [
    'provider' => env('WEATHER_API_PROVIDER', 'nws'),

    'nws' => [
        'base_url'    => env('WEATHER_BASE_URL', 'https://api.weather.gov'),

        // Per NWS policy, this must be descriptive and include contact info (email/URL).
        'user_agent'  => env('WEATHER_USER_AGENT', 'FullstackChallenge/1.0 (you@example.com)'),

        // Timeouts (milliseconds)
        'timeout'     => (int) env('WEATHER_TIMEOUT_MS', 800),
        'connect'     => (int) env('WEATHER_CONNECT_MS', 300),

        // Retries for failed upstream requests
        'retries'     => (int) env('WEATHER_RETRIES', 2),

        // Cache duration for current conditions (~55 minutes)
        'cache_ttl'   => (int) env('WEATHER_CACHE_TTL', 3300),

        // Cache duration for station/grid metadata (~24 hours)
        'meta_ttl'    => (int) env('WEATHER_META_TTL', 86400),
    ],
];
