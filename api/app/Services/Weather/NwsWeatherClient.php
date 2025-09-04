<?php

namespace App\Services\Weather;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Services\Weather\WeatherCacheKey;

/**
 * Weather provider implementation using the
 * U.S. National Weather Service API (https://api.weather.gov).
 */
class NwsWeatherClient implements WeatherProvider
{
    private const CACHE_TYPE_CURRENT = 'current';
    private const CACHE_TYPE_META    = 'meta';

    private string $baseUrl;
    private string $userAgent;
    private int $timeoutMilliseconds;
    private int $connectMilliseconds;
    private int $retryAttempts;
    private int $cacheTtlSeconds;
    private int $metaTtlSeconds;

    public function __construct()
    {
        $config = config('weather.nws');

        $this->baseUrl             = rtrim($config['base_url'], '/');
        $this->userAgent           = $config['user_agent'];
        $this->timeoutMilliseconds = $config['timeout'];
        $this->connectMilliseconds = $config['connect'];
        $this->retryAttempts       = $config['retries'];
        $this->cacheTtlSeconds     = $config['cache_ttl'];
        $this->metaTtlSeconds      = $config['meta_ttl'];
    }

    /**
     * Retrieve the current weather conditions for a given latitude/longitude.
     *
     * @param  float $latitude   Latitude in decimal degrees
     * @param  float $longitude  Longitude in decimal degrees
     * @return WeatherResponse
     *
     * @throws WeatherException if no data can be retrieved or normalized
     */
    public function current(float $latitude, float $longitude): WeatherResponse
    {
        $cacheKey = WeatherCacheKey::success($latitude, $longitude, self::CACHE_TYPE_CURRENT);

        $normalized = Cache::remember($cacheKey, $this->cacheTtlSeconds, function () use ($latitude, $longitude) {
            [$stationsUrl, $city, $state] = $this->resolvePointMeta($latitude, $longitude);

            $stationId = $this->nearestStationId($stationsUrl);
            if (! $stationId) {
                throw new WeatherException("No station found for coordinates {$latitude},{$longitude}");
            }

            $observation = $this->latestObservation($stationId);
            $normalized  = $this->normalize($observation, $city, $state);
            \Log::debug('Normalized observation', ['data' => $normalized]);
            \Log::debug('City/State', ['city' => $city, 'state' => $state]);
            if (! $normalized) {
                throw new WeatherException("Failed to normalize observation for station {$stationId}");
            }

            return $normalized;
        });

        return $this->mapCached($normalized);
    }

    /**
     * Retrieve the current weather conditions from cache only.
     * Returns null if no cached data is available.
     *
     * @param  float $latitude
     * @param  float $longitude
     * @return WeatherResponse|null
     */
    public function currentCachedOnly(float $latitude, float $longitude): ?WeatherResponse
    {
        $key = WeatherCacheKey::success($latitude, $longitude, self::CACHE_TYPE_CURRENT);
        $cached = Cache::get($key);
        return $cached ? $this->mapCached($cached) : null;
    }

    /**
     * Build the HTTP client with retries, timeouts, and headers.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private function http()
    {
        return Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Accept'     => 'application/ld+json',
            ])
            ->connectTimeout($this->connectMilliseconds / 1000)
            ->timeout($this->timeoutMilliseconds / 1000)
            ->retry($this->retryAttempts, 200, throw: false);
    }

    /**
     * Resolve metadata for a point, including observation stations URL.
     *
     * @param  float $latitude
     * @param  float $longitude
     * @return array{0:string,1:?string,2:?string} [$stationsUrl, $city, $state]
     *
     * @throws WeatherException
     */
    private function resolvePointMeta(float $latitude, float $longitude): array
    {
        // Success + failure keys (failure uses same TTL so we won't recheck until it expires)
        $cacheKey = WeatherCacheKey::success($latitude, $longitude, self::CACHE_TYPE_META);
        $failKey  = WeatherCacheKey::fail($latitude, $longitude, self::CACHE_TYPE_META);

        if (Cache::has($failKey)) {
            throw new WeatherException('Point metadata temporarily unavailable (cached failure).');
        }

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $url = "{$this->baseUrl}/points/{$latitude},{$longitude}";
        $response = $this->http()->get($url);

        if (! $response->successful()) {
            Cache::put($failKey, ['status' => $response->status(), 'at' => now()->toIso8601String()], $this->metaTtlSeconds);
            throw new WeatherException("Failed to resolve point metadata: {$response->status()}");
        }

        $stationsUrl = $response->json('observationStations');
        $relativeLocation = $response->json('relativeLocation');
        \Log::debug('relativeLocation', ['data' => $relativeLocation]);
        $city = data_get($relativeLocation, 'city');
        $state = data_get($relativeLocation, 'state');
        \Log::debug('city/state', ['city' => $city, 'state' => $state]);
        if (! $stationsUrl) {
            Cache::put($failKey, ['reason' => 'missing_observationStations', 'at' => now()->toIso8601String()], $this->metaTtlSeconds);
            throw new WeatherException('NWS point metadata missing observationStations URL.');
        }

        $payload = [$stationsUrl, $city, $state];

        Cache::put($cacheKey, $payload, $this->metaTtlSeconds);
        Cache::forget($failKey);

        return $payload;
    }


    /**
     * Get the nearest station identifier from the stations URL.
     *
     * @param  string $stationsUrl
     * @return string|null
     */
    private function nearestStationId(string $stationsUrl): ?string
    {
        $response = $this->http()->get($stationsUrl);

        if (! $response->successful()) {
            return null;
        }

        $stations = $response->json('@graph') ?? [];
        if (count($stations) === 0) {
            return null;
        }

        return $stations[0]['stationIdentifier'] ?? null;
    }


    /**
     * Fetch the latest observation from a given station.
     *
     * @param  string $stationId
     * @return array
     *
     * @throws WeatherException
     */
    private function latestObservation(string $stationId): array
    {
        $response = $this->http()->get("{$this->baseUrl}/stations/{$stationId}/observations/latest");

        if (!$response->successful()) {
            throw new WeatherException("Latest observation request failed: {$response->status()}");
        }

        return $response->json();
    }


    /**
     * Normalize raw observation into a cacheable array.
     *
     * @param  array       $observation  Full latest observation JSON
     * @param  string|null $city
     * @param  string|null $state
     * @return array<string,mixed>
     */
    private function normalize(array $observation, ?string $city = null, ?string $state = null): array
    {
        $tempC          = data_get($observation, 'temperature.value');
        $windVal        = data_get($observation, 'windSpeed.value');
        $windUnit       = data_get($observation, 'windSpeed.unitCode'); // e.g. 'wmoUnit:km_h-1' or 'wmoUnit:m_s-1'
        $humidity       = data_get($observation, 'relativeHumidity.value');
        $pressurePa     = data_get($observation, 'barometricPressure.value');
        $summary        = data_get($observation, 'textDescription');
        $iconUrl        = data_get($observation, 'icon');
        $observedAtIso  = data_get($observation, 'timestamp');

        $windKph = null;
        if (is_numeric($windVal)) {
            switch ($windUnit) {
                case 'wmoUnit:km_h-1':
                    $windKph = (float) $windVal;
                    break;
                case 'wmoUnit:m_s-1':
                    $windKph = $this->metersPerSecondToKph((float) $windVal);
                    break;
                default:
                    $windKph = (float) $windVal;
                    break;
            }
        }

        return [
            'conditionSummary'           => $summary,
            'temperatureCelsius'         => is_numeric($tempC) ? round((float) $tempC, 1) : null,
            'temperatureFahrenheit'      => $this->celsiusToFahrenheit($tempC),
            'windSpeedKilometersPerHour' => $windKph,
            'windSpeedMilesPerHour'      => $this->kphToMph($windKph),
            'relativeHumidityPercent'    => is_numeric($humidity) ? (int) round((float) $humidity) : null,
            'pressureMillibars'          => $this->pascalsToMillibars($pressurePa), // Pa → hPa(mbar)
            'iconUrl'                    => $iconUrl,
            'observedAtIso8601'          => $observedAtIso,
            'city'                       => $city,
            'state'                      => $state,
        ];
    }


    /**
     * Convert Celsius to Fahrenheit.
     */
    private function celsiusToFahrenheit(?float $celsius): ?float
    {
        return is_numeric($celsius) ? round(($celsius * 9 / 5) + 32, 1) : null;
    }

    /**
     * Convert meters per second to kilometers per hour.
     */
    private function metersPerSecondToKph(?float $mps): ?float
    {
        return is_numeric($mps) ? round($mps * 3.6, 1) : null;
    }

    /**
     * Convert kilometers per hour to miles per hour.
     */
    private function kphToMph(?float $kph): ?float
    {
        return is_numeric($kph) ? round($kph * 0.621371, 1) : null;
    }

    /**
     * Convert Pascals to millibars.
     */
    private function pascalsToMillibars(?float $pa): ?float
    {
        return is_numeric($pa) ? round($pa / 100.0, 1) : null;
    }

    /**
     * Rehydrate cached array into a WeatherResponse DTO.
     */
    private function mapCached(array $cached): WeatherResponse
    {
        return new WeatherResponse(
            $cached['conditionSummary']        ?? null,
            $cached['temperatureCelsius']      ?? null,
            $cached['temperatureFahrenheit']   ?? null,
            $cached['windSpeedKilometersPerHour'] ?? null,
            $cached['windSpeedMilesPerHour']   ?? null,
            $cached['relativeHumidityPercent'] ?? null,
            $cached['pressureMillibars']       ?? null,
            $cached['iconUrl']                 ?? null,
            $cached['observedAtIso8601']       ?? null,
            $cached['city']                    ?? null,
            $cached['state']                   ?? null,
        );
    }
}
