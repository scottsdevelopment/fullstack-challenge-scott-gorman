<?php

namespace App\Services\Weather;

/**
 * Utility for building consistent weather cache keys.
 *
 * Example current key: weather:current:42.12:-83.45
 * Example meta key:    weather:meta:42.12:-83.45
 */
final class WeatherCacheKey
{
    private const PREFIX = 'weather';

    public const TYPE_CURRENT = 'current';
    public const TYPE_META    = 'meta';

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
     * Cache key for current observation.
     */
    public static function current(float $lat, float $lon): string
    {
        return self::build(self::TYPE_CURRENT, $lat, $lon);
    }

    /**
     * Cache key for point metadata (city/state/stations/etc).
     */
    public static function meta(float $lat, float $lon): string
    {
        return self::build(self::TYPE_META, $lat, $lon);
    }

    /**
     * General builder for consistency.
     */
    private static function build(string $type, float $lat, float $lon): string
    {
        [$latNorm, $lonNorm] = self::normalize($lat, $lon);
        return sprintf('%s:%s:%s:%s', self::PREFIX, $type, $latNorm, $lonNorm);
    }
}
