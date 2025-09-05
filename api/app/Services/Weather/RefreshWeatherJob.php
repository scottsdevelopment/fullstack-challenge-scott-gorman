<?php

namespace App\Services\Weather;

use App\Services\Weather\Contracts\WeatherProvider;
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

        try {
            $currentWeather = $weather->current($latitude, $longitude);
        } catch (\Throwable $e) {
            Log::warning('RefreshWeatherJob: weather refresh failed', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
