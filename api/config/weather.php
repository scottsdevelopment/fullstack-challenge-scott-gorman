<?php

return [
    'provider' => env('WEATHER_API_PROVIDER', 'nws'),

    'nws' => [
        'base_url'   => env('WEATHER_BASE_URL', 'https://api.weather.gov'),
        'user_agent' => env('WEATHER_USER_AGENT', 'FullstackChallenge/1.0 (you@example.com)'),

        'timeout_ms' => (int) env('WEATHER_TIMEOUT_MS', 500),
        'connect_ms' => (int) env('WEATHER_CONNECT_MS', 100),
        'retries'    => (int) env('WEATHER_RETRIES', 2),

        'cache_ttl'  => (int) env('WEATHER_CACHE_TTL', 840),
    ],
];
