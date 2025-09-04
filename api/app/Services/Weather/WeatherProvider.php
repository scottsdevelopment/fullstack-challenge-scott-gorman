<?php

namespace App\Services\Weather;

interface WeatherProvider
{
    /**
     * Get current weather conditions for a given latitude/longitude.
     *
     * @param  float  $latitude  Latitude in decimal degrees
     * @param  float  $longitude  Longitude in decimal degrees
     * @return \App\Services\Weather\WeatherResponse
     *
     * @throws \App\Services\Weather\WeatherException
     */
    public function current(float $latitude, float $longitude): WeatherResponse;

    /**
     * Get current weather conditions for a given latitude/longitude,
     * but only if cached data is available. Returns null if no cached data.
     *
     * @param  float  $latitude  Latitude in decimal degrees
     * @param  float  $longitude  Longitude in decimal degrees
     *
     * @return \App\Services\Weather\WeatherResponse|null
     */
    public function currentCachedOnly(float $latitude, float $longitude): ?WeatherResponse;
}
