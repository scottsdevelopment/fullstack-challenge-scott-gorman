<?php

namespace App\Services\Weather;

/**
 * DTO for normalized weather + location metadata.
 */
final class WeatherResponse
{
    private ?float $latitude                = null;
    private ?float $longitude               = null;
    private ?string $city                   = null;
    private ?string $state                  = null;
    private ?string $stationsUrl            = null;
    private ?string $stationId              = null;

    private ?string $conditionSummary       = null;
    private ?float  $temperatureCelsius     = null;
    private ?float  $temperatureFahrenheit  = null;
    private ?float  $windSpeedKilometersPerHour = null;
    private ?float  $windSpeedMilesPerHour  = null;
    private ?int    $relativeHumidityPercent = null;
    private ?float  $pressureMillibars      = null;
    private ?string $iconUrl                = null;
    private ?string $observedAtIso8601      = null;

    public function setLatitude(?float $v): self { $this->latitude = $v; return $this; }
    public function setLongitude(?float $v): self { $this->longitude = $v; return $this; }
    public function setCity(?string $v): self { $this->city = $v; return $this; }
    public function setState(?string $v): self { $this->state = $v; return $this; }
    public function setStationsUrl(?string $v): self { $this->stationsUrl = $v; return $this; }
    public function setStationId(?string $v): self { $this->stationId = $v; return $this; }

    public function setConditionSummary(?string $v): self { $this->conditionSummary = $v; return $this; }
    public function setTemperatureCelsius(?float $v): self { $this->temperatureCelsius = $v; return $this; }
    public function setTemperatureFahrenheit(?float $v): self { $this->temperatureFahrenheit = $v; return $this; }
    public function setWindSpeedKilometersPerHour(?float $v): self { $this->windSpeedKilometersPerHour = $v; return $this; }
    public function setWindSpeedMilesPerHour(?float $v): self { $this->windSpeedMilesPerHour = $v; return $this; }
    public function setRelativeHumidityPercent(?int $v): self { $this->relativeHumidityPercent = $v; return $this; }
    public function setPressureMillibars(?float $v): self { $this->pressureMillibars = $v; return $this; }
    public function setIconUrl(?string $v): self { $this->iconUrl = $v; return $this; }
    public function setObservedAtIso8601(?string $v): self { $this->observedAtIso8601 = $v; return $this; }

    public function getLatitude(): ?float { return $this->latitude; }
    public function getLongitude(): ?float { return $this->longitude; }
    public function getCity(): ?string { return $this->city; }
    public function getState(): ?string { return $this->state; }
    public function getStationsUrl(): ?string { return $this->stationsUrl; }
    public function getStationId(): ?string { return $this->stationId; }

    public function getConditionSummary(): ?string { return $this->conditionSummary; }
    public function getTemperatureCelsius(): ?float { return $this->temperatureCelsius; }
    public function getTemperatureFahrenheit(): ?float { return $this->temperatureFahrenheit; }
    public function getWindSpeedKilometersPerHour(): ?float { return $this->windSpeedKilometersPerHour; }
    public function getWindSpeedMilesPerHour(): ?float { return $this->windSpeedMilesPerHour; }
    public function getRelativeHumidityPercent(): ?int { return $this->relativeHumidityPercent; }
    public function getPressureMillibars(): ?float { return $this->pressureMillibars; }
    public function getIconUrl(): ?string { return $this->iconUrl; }
    public function getObservedAtIso8601(): ?string { return $this->observedAtIso8601; }

    public static function fromArray(array $a): self
    {
        $x = new self();
        return $x
            ->setLatitude(self::toFloatOrNull($a['latitude'] ?? null))
            ->setLongitude(self::toFloatOrNull($a['longitude'] ?? null))
            ->setCity(self::toStringOrNull($a['city'] ?? null))
            ->setState(self::toStringOrNull($a['state'] ?? null))
            ->setStationsUrl(self::toStringOrNull($a['stationsUrl'] ?? null))
            ->setStationId(self::toStringOrNull($a['stationId'] ?? null))
            ->setConditionSummary(self::toStringOrNull($a['conditionSummary'] ?? null))
            ->setTemperatureCelsius(self::toFloatOrNull($a['temperatureCelsius'] ?? null))
            ->setTemperatureFahrenheit(self::toFloatOrNull($a['temperatureFahrenheit'] ?? null))
            ->setWindSpeedKilometersPerHour(self::toFloatOrNull($a['windSpeedKilometersPerHour'] ?? null))
            ->setWindSpeedMilesPerHour(self::toFloatOrNull($a['windSpeedMilesPerHour'] ?? null))
            ->setRelativeHumidityPercent(self::toIntOrNull($a['relativeHumidityPercent'] ?? null))
            ->setPressureMillibars(self::toFloatOrNull($a['pressureMillibars'] ?? null))
            ->setIconUrl(self::toStringOrNull($a['iconUrl'] ?? null))
            ->setObservedAtIso8601(self::toStringOrNull($a['observedAtIso8601'] ?? null));
    }

    public function toArray(): array
    {
        return [
            'latitude'                     => $this->latitude,
            'longitude'                    => $this->longitude,
            'city'                         => $this->city,
            'state'                        => $this->state,
            'stationsUrl'                  => $this->stationsUrl,
            'stationId'                    => $this->stationId,
            'conditionSummary'             => $this->conditionSummary,
            'temperatureCelsius'           => $this->temperatureCelsius,
            'temperatureFahrenheit'        => $this->temperatureFahrenheit,
            'windSpeedKilometersPerHour'   => $this->windSpeedKilometersPerHour,
            'windSpeedMilesPerHour'        => $this->windSpeedMilesPerHour,
            'relativeHumidityPercent'      => $this->relativeHumidityPercent,
            'pressureMillibars'            => $this->pressureMillibars,
            'iconUrl'                      => $this->iconUrl,
            'observedAtIso8601'            => $this->observedAtIso8601,
        ];
    }

    private static function toStringOrNull(mixed $v): ?string
    {
        return is_string($v) ? $v : null;
    }
    private static function toFloatOrNull(mixed $v): ?float
    {
        return is_numeric($v) ? (float) $v : null;
    }
    private static function toIntOrNull(mixed $v): ?int
    {
        return is_numeric($v) ? (int) $v : null;
    }
}
