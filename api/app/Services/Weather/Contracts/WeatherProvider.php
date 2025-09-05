<?php

namespace App\Services\Weather\Contracts;

use App\Services\Weather\WeatherResponse;

interface WeatherProvider
{
    public function current(float $latitude, float $longitude): WeatherResponse;
}
