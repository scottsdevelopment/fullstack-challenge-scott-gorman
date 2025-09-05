<?php

namespace App\Services\Weather;

use Illuminate\Support\Facades\Cache;

/**
 * Inheritance-based cache wrapper for NWS client.
 * Caches current() results for a given TTL (default 14 minutes).
 */
class CachedNwsWeatherClient extends NwsWeatherClient
{
    public function __construct(
        private readonly int $currentTtlSeconds = 840
    ) {
        parent::__construct();
    }

    /**
     * Returns cached WeatherResponse if present; else calls parent and caches it.
     */
    public function current(float $latitude, float $longitude): WeatherResponse
    {
        $key = WeatherCacheKey::current($latitude, $longitude);

        $cached = Cache::get($key);
        if ($cached instanceof WeatherResponse) {
            return $cached;
        }

        $wx = parent::current($latitude, $longitude);

        Cache::put($key, $wx, $this->currentTtlSeconds);

        return $wx;
    }
}
