<?php

namespace App\Services\Weather;

use App\Services\Weather\WeatherProvider;
use App\Services\Weather\WeatherCacheKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RefreshWeatherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public float $latitude,
        public float $longitude
    ) {}

    public function handle(WeatherProvider $weather): void
    {
        $latitude = $this->latitude;
        $longitude = $this->longitude;

        $failKey = WeatherCacheKey::fail($latitude, $longitude, 'meta');

        // Skip if metadata is in a known fail state
        if (Cache::has($failKey)) {
            Log::info('RefreshWeatherJob: skipping due to cached meta failure', [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
            return;
        }

        try {
            // Trigger the provider call (this will populate or refresh the caches internally)
            $currentWeather = $weather->current($latitude, $longitude);

            Log::info('RefreshWeatherJob: refreshed weather data', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'observedAt' => $currentWeather->observedAtIso8601,
            ]);
        } catch (\Throwable $e) {
            // Provider handles setting fail keys, so we just log
            Log::warning('RefreshWeatherJob: weather refresh failed', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
