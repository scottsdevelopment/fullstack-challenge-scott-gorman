<?php

namespace App\Services\Weather;

/**
 * Utility for building consistent weather cache keys.
 *
 * Example success key: weather:meta:42.12:-83.45
 * Example fail key:    weather:fail:meta:42.12:-83.45
 */
final class WeatherCacheKey
{
    private const PREFIX = 'weather';

    /**
     * Normalize coordinates to fixed precision (2 decimals).
     */
    private static function normalize(float $lat, float $lon): array
    {
        return [
            number_format($lat, 2, '.', ''), // e.g. 42.12
            number_format($lon, 2, '.', ''), // e.g. -83.45
        ];
    }

    /**
     * Success-path cache key.
     *
     * @param float $lat
     * @param float $lon
     * @param string $type e.g. "current", "meta", "stations"
     */
    public static function success(float $lat, float $lon, string $type = 'current'): string
    {
        [$latNorm, $lonNorm] = self::normalize($lat, $lon);

        return sprintf('%s:%s:%s:%s', self::PREFIX, $type, $latNorm, $lonNorm);
    }

    /**
     * Failure-path cache key.
     *
     * @param float $lat
     * @param float $lon
     * @param string $type e.g. "current", "meta", "stations"
     */
    public static function fail(float $lat, float $lon, string $type = 'current'): string
    {
        [$latNorm, $lonNorm] = self::normalize($lat, $lon);

        return sprintf('%s:fail:%s:%s:%s', self::PREFIX, $type, $latNorm, $lonNorm);
    }
}
