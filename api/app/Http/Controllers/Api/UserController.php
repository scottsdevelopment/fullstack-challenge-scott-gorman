<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Weather\WeatherProvider;
use App\Services\Weather\WeatherResponse;
use App\Services\Weather\RefreshWeatherJob;
use App\Services\Weather\WeatherException;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private WeatherProvider $weather) {}

    /**
     * GET /api/users
     * Returns 20 seeded users with current weather, if observed ≤ 60 minutes ago.
     */
    public function index(): JsonResponse
    {
        $users = User::query()->latest()->take(20)->get();

        $data = $users->map(function (User $u) {
            return $this->serializeUserWithWeather($u);
        })->all();

        return response()->json([
            'users'   => $data,
        ]);
    }

    /**
     * GET /api/users/{user}
     * Returns a single user + detailed weather, if observed ≤ 60 minutes ago.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'user'    => $this->serializeUserWithWeather($user, detailed: true),
        ]);
    }

    /**
     * Serialize a User model to an array, including current weather if available.
     *
     * @param  User  $u
     * @param  bool  $detailed  Whether to include detailed weather data
     * @return array
     */
    private function serializeUserWithWeather(User $u, bool $detailed = false): array
    {
        $base = [
            'id'                => $u->id,
            'name'              => $u->name,
            'email'             => $u->email,
            'latitude'          => (float) $u->latitude,
            'longitude'         => (float) $u->longitude,
            'email_verified_at' => optional($u->email_verified_at)?->toIso8601String(),
            'created_at'        => optional($u->created_at)?->toIso8601String(),
            'updated_at'        => optional($u->updated_at)?->toIso8601String(),
        ];

        $weather = $this->currentWeatherOrNull((float)$u->latitude, (float)$u->longitude, $detailed);

        return $base + ['weather' => $weather];
    }

    /**
     * Returns WeatherResponse::toArray() if observation is ≤ 60 minutes old; else null.
     * Never throws—gracefully degrades to null on provider errors.
     */
    private function currentWeatherOrNull(float $lat, float $lon, bool $detailed = false): ?array
    {
        try {
            /** @var WeatherResponse|null $currentWeather */
            $currentWeather = $this->weather->currentCachedOnly($lat, $lon);

            \Log::debug('UserController currentWeatherOrNull', [
                'lat' => $lat,
                'lon' => $lon,
                'detailed' => $detailed,
                'wx' => $currentWeather,
            ]);

            // No cached data — enqueue refresh and return null
            if (!$currentWeather || empty($currentWeather->observedAtIso8601)) {
                RefreshWeatherJob::dispatch($lat, $lon)->onQueue('weather');
                return null;
            }

            $observed = new CarbonImmutable($currentWeather->observedAtIso8601);

            // Stale (> 60 minutes) — enqueue refresh and return null
            if ($observed->lt(now()->subMinutes(60))) {
                RefreshWeatherJob::dispatch($lat, $lon)->onQueue('weather');
                return null;
            }

            $payload = $currentWeather->toArray();

            return $payload;
        } catch (WeatherException|\Throwable $e) {
            \Log::debug('UserController currentWeatherOrNull caught exception', ['exception' => $e]);
            // On error, attempt to refresh in the background
            RefreshWeatherJob::dispatch($lat, $lon)->onQueue('weather');
            return null;
        }
    }

}
