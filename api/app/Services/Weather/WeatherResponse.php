<?php

namespace App\Services\Weather;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A normalized response object for weather data.
 */
class WeatherResponse implements Arrayable
{
    public function __construct(
        public readonly ?string $conditionSummary,
        public readonly ?float $temperatureCelsius,
        public readonly ?float $temperatureFahrenheit,
        public readonly ?float $windSpeedKilometersPerHour,
        public readonly ?float $windSpeedMilesPerHour,
        public readonly ?int $relativeHumidityPercent,
        public readonly ?float $pressureMillibars,
        public readonly ?string $iconUrl,
        public readonly ?string $observedAtIso8601,
        public readonly ?string $city,
        public readonly ?string $state,
    ) {}

    public function toArray(): array
    {
        return [
            'conditionSummary'           => $this->conditionSummary,
            'temperatureCelsius'         => $this->temperatureCelsius,
            'temperatureFahrenheit'      => $this->temperatureFahrenheit,
            'windSpeedKilometersPerHour' => $this->windSpeedKilometersPerHour,
            'windSpeedMilesPerHour'      => $this->windSpeedMilesPerHour,
            'relativeHumidityPercent'    => $this->relativeHumidityPercent,
            'pressureMillibars'          => $this->pressureMillibars,
            'iconUrl'                    => $this->iconUrl,
            'observedAtIso8601'          => $this->observedAtIso8601,
            'city'                       => $this->city,
            'state'                      => $this->state,
        ];
    }
}
