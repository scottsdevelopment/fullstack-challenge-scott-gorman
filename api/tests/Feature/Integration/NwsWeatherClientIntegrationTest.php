<?php

namespace Tests\Feature\Integration;

use App\Services\Weather\NwsWeatherClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class NwsWeatherClientIntegrationTest extends TestCase
{
    /** @test @group integration */
    public function it_fetches_a_real_current_observation()
    {
        if (! (bool) env('RUN_INTEGRATION_TESTS', false)) {
            $this->markTestSkipped('RUN_INTEGRATION_TESTS is disabled.');
        }

        $ua = env('WEATHER_USER_AGENT', '');
        if ($ua === '' || Str::contains(strtolower($ua), 'example')) {
            $this->markTestSkipped('WEATHER_USER_AGENT must be descriptive with contact info.');
        }

        Config::set('weather.nws.timeout', (int) env('WEATHER_TIMEOUT_MS', 1500));
        Config::set('weather.nws.connect', (int) env('WEATHER_CONNECT_MS', 600));
        Config::set('weather.nws.retries', (int) env('WEATHER_RETRIES', 2));

        $client = new NwsWeatherClient();

        $lat = 42.3314;
        $lon = -83.0458;

        $res = $client->current($lat, $lon);
        $payload = $res->toArray();

        $this->assertArrayHasKey('conditionSummary', $payload);
        $this->assertArrayHasKey('temperatureCelsius', $payload);
        $this->assertArrayHasKey('temperatureFahrenheit', $payload);
        $this->assertArrayHasKey('windSpeedKilometersPerHour', $payload);
        $this->assertArrayHasKey('windSpeedMilesPerHour', $payload);
        $this->assertArrayHasKey('relativeHumidityPercent', $payload);
        $this->assertArrayHasKey('pressureMillibars', $payload);
        $this->assertArrayHasKey('iconUrl', $payload);
        $this->assertArrayHasKey('observedAtIso8601', $payload);
        $this->assertArrayHasKey('city', $payload);
        $this->assertArrayHasKey('state', $payload);

        $this->assertNotNull($res->getObservedAtIso8601());
        $observed = Carbon::parse($res->getObservedAtIso8601());
        $this->assertTrue(
            $observed->greaterThan(Carbon::now()->subHours(3)),
            'Observation should be within the last 3 hours'
        );

        if ($res->getTemperatureCelsius() !== null) {
            $this->assertGreaterThan(-80, $res->getTemperatureCelsius());
            $this->assertLessThan(60, $res->getTemperatureCelsius());
        }
        if ($res->getWindSpeedKilometersPerHour() !== null) {
            $this->assertGreaterThanOrEqual(0, $res->getWindSpeedKilometersPerHour());
            $this->assertLessThanOrEqual(200, $res->getWindSpeedKilometersPerHour());
        }
    }
}
