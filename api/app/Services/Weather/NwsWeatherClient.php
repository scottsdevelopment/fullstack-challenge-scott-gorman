<?php

namespace App\Services\Weather;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use App\Services\Weather\Contracts\WeatherProvider;
use App\Services\Weather\WeatherResponse;

/**
 * Weather provider implementation using the U.S. National Weather Service API.
 * Builds a WeatherResponse via fluent setters.
 */
class NwsWeatherClient implements WeatherProvider
{
    private string $baseUrl;
    private string $userAgent;
    private int $timeoutMs;
    private int $connectMs;
    private int $retries;

    public function __construct()
    {
        $cfg = config('weather.nws');
        $this->baseUrl   = rtrim((string) $cfg['base_url'], '/');
        $this->userAgent = (string) $cfg['user_agent'];
        $this->timeoutMs = (int) $cfg['timeout_ms'];
        $this->connectMs = (int) $cfg['connect_ms'];
        $this->retries   = (int) $cfg['retries'];
    }

    /**
     * Fetch current conditions for coordinates from NWS (no caching).
     *
     * @throws WeatherException
     */
    public function current(float $latitude, float $longitude): WeatherResponse
    {
        $wx = new WeatherResponse();

        $wx->setLatitude($latitude)
           ->setLongitude($longitude);

        $this->resolvePointMeta($wx);

        $this->nearestStationId($wx);

        $this->latestObservation($wx);

        return $wx;
    }

    /**
     * Resolve station list URL and relative location; pushes city/state/stationsUrl into $wx.
     *
     * @throws WeatherException
     */
    protected function resolvePointMeta(WeatherResponse $wx): void
    {
        $lat = $wx->getLatitude();
        $lon = $wx->getLongitude();

        if ($lat === null || $lon === null) {
            throw new WeatherException('Latitude/longitude not set on WeatherResponse.');
        }

        $url = "{$this->baseUrl}/points/{$lat},{$lon}";
        $res = $this->get($url);

        $stationsUrl      = $res->json('observationStations');
        $relativeLocation = $res->json('relativeLocation');

        $wx->setCity(data_get($relativeLocation, 'city'))
           ->setState(data_get($relativeLocation, 'state'))
           ->setStationsUrl(is_string($stationsUrl) ? $stationsUrl : null);

        if (!$wx->getStationsUrl()) {
            throw new WeatherException('NWS point metadata missing observationStations URL.');
        }
    }

    /**
     * Get nearest station identifier using $wx->getStationsUrl(); stores stationId on the DTO.
     *
     * @throws WeatherException
     */
    private function nearestStationId(WeatherResponse $wx): void
    {
        $stationsUrl = $wx->getStationsUrl();
        if (!$stationsUrl) {
            throw new WeatherException('Stations URL is not set on WeatherResponse.');
        }

        $res = $this->get($stationsUrl);

        $stations = $res->json('@graph') ?? [];
        if (!is_array($stations) || !count($stations)) {
            throw new WeatherException('No stations returned for provided coordinates.');
        }

        $stationId = $stations[0]['stationIdentifier'] ?? null;
        if (!is_string($stationId) || $stationId === '') {
            throw new WeatherException('Nearest station does not include stationIdentifier.');
        }

        $wx->setStationId($stationId);
    }

    /**
     * Fetch latest observation for the station in $wx; pushes normalized fields onto the DTO.
     *
     * @throws WeatherException
     */
    private function latestObservation(WeatherResponse $wx): void
    {
        $stationId = $wx->getStationId();
        if (!$stationId) {
            throw new WeatherException('Station ID is not set on WeatherResponse.');
        }

        $res = $this->get("{$this->baseUrl}/stations/{$stationId}/observations/latest");
        $observation = $res->json();

        $tempC         = data_get($observation, 'temperature.value');
        $windVal       = data_get($observation, 'windSpeed.value');
        $windUnit      = data_get($observation, 'windSpeed.unitCode');
        $humidity      = data_get($observation, 'relativeHumidity.value');
        $pressurePa    = data_get($observation, 'barometricPressure.value');
        $summary       = data_get($observation, 'textDescription');
        $iconUrl       = data_get($observation, 'icon');
        $observedAtIso = data_get($observation, 'timestamp');

        $windKph = null;
        if (is_numeric($windVal)) {
            $windKph = match ($windUnit) {
                'wmoUnit:km_h-1' => (float) $windVal,
                'wmoUnit:m_s-1'  => $this->metersPerSecondToKph((float) $windVal),
                default          => (float) $windVal,
            };
        }

        $wx->setConditionSummary(is_string($summary) ? $summary : null)
           ->setTemperatureCelsius(is_numeric($tempC) ? round((float) $tempC, 1) : null)
           ->setTemperatureFahrenheit($this->celsiusToFahrenheit(is_numeric($tempC) ? (float) $tempC : null))
           ->setWindSpeedKilometersPerHour($windKph)
           ->setWindSpeedMilesPerHour($this->kphToMph($windKph))
           ->setRelativeHumidityPercent(is_numeric($humidity) ? (int) round((float) $humidity) : null)
           ->setPressureMillibars($this->pascalsToMillibars(is_numeric($pressurePa) ? (float) $pressurePa : null))
           ->setIconUrl(is_string($iconUrl) ? $iconUrl : null)
           ->setObservedAtIso8601(is_string($observedAtIso) ? $observedAtIso : null);
    }

    /**
     * Perform a GET with the configured client; throws WeatherException on any failure.
     *
     * @throws WeatherException
     */
    private function get(string $url): Response
    {
        try {
            $res = $this->http()->get($url);
        } catch (\Throwable $e) {
            throw new WeatherException("HTTP error for {$url}: {$e->getMessage()}", previous: $e);
        }

        if (!$res->successful()) {
            throw new WeatherException("HTTP {$res->status()} for {$url}");
        }

        return $res;
    }

    /**
     * Configured HTTP client.
     */
    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Accept'     => 'application/ld+json',
            ])
            ->connectTimeout($this->connectMs / 1000)
            ->timeout($this->timeoutMs / 1000)
            ->retry($this->retries, 200, throw: true);
    }

    /** Unit helpers */

    private function celsiusToFahrenheit(?float $celsius): ?float
    {
        return is_numeric($celsius) ? round(($celsius * 9 / 5) + 32, 1) : null;
    }

    private function metersPerSecondToKph(?float $mps): ?float
    {
        return is_numeric($mps) ? round($mps * 3.6, 1) : null;
    }

    private function kphToMph(?float $kph): ?float
    {
        return is_numeric($kph) ? round($kph * 0.621371, 1) : null;
    }

    private function pascalsToMillibars(?float $pa): ?float
    {
        return is_numeric($pa) ? round($pa / 100.0, 1) : null;
    }
}
