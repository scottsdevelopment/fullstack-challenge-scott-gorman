<?php

namespace Tests\Feature;

use App\Services\Weather\NwsWeatherClient;
use App\Services\Weather\WeatherCacheKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NwsWeatherClientTest extends TestCase
{
    /** @test */
    public function it_normalizes_a_latest_observation()
    {
        Cache::flush();

        // Fake NWS responses using real API shapes
        Http::fake([
            // 1) /points -> top-level observationStations (NOT nested in "properties")
            'api.weather.gov/points/*' => Http::response([
                '@context' => [],
                '@id'      => 'https://api.weather.gov/points/42.3314,-83.0458',
                'observationStations' => 'https://api.weather.gov/gridpoints/DTX/85,46/stations',
            ], 200),

            // 2) /gridpoints/.../stations -> "@graph" array with stationIdentifier on each item
            'api.weather.gov/gridpoints/*/stations' => Http::response([
                '@graph' => [
                    [
                        '@id'               => 'https://api.weather.gov/stations/KDTW',
                        'stationIdentifier' => 'KDTW',
                        'name'              => 'Detroit Metropolitan Wayne County Airport',
                        'distance'          => ['unitCode' => 'wmoUnit:m', 'value' => 1000],
                    ],
                ],
            ], 200),

            // 3) /stations/{id}/observations/latest -> top-level fields (NOT "properties")
            'api.weather.gov/stations/KDTW/observations/latest' => Http::response([
                '@context'         => [],
                '@id'              => 'https://api.weather.gov/stations/KDTW/observations/2025-09-03T12:00:00+00:00',
                'station'          => 'https://api.weather.gov/stations/KDTW',
                'stationId'        => 'KDTW',
                'timestamp'        => '2025-09-03T12:00:00+00:00',
                'textDescription'  => 'Clear',
                'icon'             => 'https://example.com/icon.png',
                'temperature'      => ['unitCode' => 'wmoUnit:degC',   'value' => 23.4],
                // Use m/s to match expected 10.8 km/h and 6.7 mph
                'windSpeed'        => ['unitCode' => 'wmoUnit:m_s-1',  'value' => 3.0],
                'relativeHumidity' => ['unitCode' => 'wmoUnit:percent','value' => 48.0],
                'barometricPressure' => ['unitCode' => 'wmoUnit:Pa',   'value' => 101560],
            ], 200),
        ]);

        $client = new NwsWeatherClient();
        $response = $client->current(42.3314, -83.0458); // Detroit, MI

        $this->assertSame('Clear', $response->conditionSummary);
        $this->assertSame(23.4, $response->temperatureCelsius);
        $this->assertSame(74.1, $response->temperatureFahrenheit);
        $this->assertSame(10.8, $response->windSpeedKilometersPerHour);
        $this->assertSame(6.7, $response->windSpeedMilesPerHour);
        $this->assertSame(48, $response->relativeHumidityPercent);
        $this->assertSame(101.56 * 10, $response->pressureMillibars); // i.e., 1015.6
        $this->assertSame('https://example.com/icon.png', $response->iconUrl);
        $this->assertSame('2025-09-03T12:00:00+00:00', $response->observedAtIso8601);
    }

    /** @test */
    public function it_uses_cached_data_on_subsequent_calls()
    {
        Cache::flush();

        // Force errors so client must use cache
        Http::fake([
            'api.weather.gov/*' => Http::response([], 500),
        ]);

        $cached = [
            'conditionSummary'           => 'Sunny',
            'temperatureCelsius'         => 20.0,
            'temperatureFahrenheit'      => 68.0,
            'windSpeedKilometersPerHour' => 15.0,
            'windSpeedMilesPerHour'      => 9.3,
            'relativeHumidityPercent'    => 40,
            'pressureMillibars'          => 1012.0,
            'iconUrl'                    => null,
            'observedAtIso8601'          => '2025-09-03T10:00:00+00:00',
        ];

        // Match your client's cache key format (rounded coords)
        $cacheKey = WeatherCacheKey::success(42.3314, -83.0458);
        Cache::put($cacheKey, $cached, 3300);

        $client = new NwsWeatherClient();
        $response = $client->current(42.3314, -83.0458);

        $this->assertSame('Sunny', $response->conditionSummary);
        $this->assertSame(20.0, $response->temperatureCelsius);
    }
}
