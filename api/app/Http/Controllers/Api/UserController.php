<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Weather\Contracts\WeatherProvider;
use App\Services\Weather\WeatherResponse;
use App\Services\Weather\WeatherException;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(private WeatherProvider $weather) {}

    /**
     * GET /api/users
     * Returns all users with their current weather (if available).
     */
    public function index(): JsonResponse
    {
        $users = User::query()->latest()->take(20)->get();

        $data = $users->map(fn (User $u) => $this->serializeUser($u))->all();

        return response()->json(['users' => $data]);
    }

    /**
     * GET /api/users/{user}
     * Returns a single user with their current weather (if available).
     */
    public function show(User $user): JsonResponse
    {
        return response()->json(['user' => $this->serializeUser($user)]);
    }

    /**
     * Serialize a User to array and attach current weather (nullable).
     */
    private function serializeUser(User $u): array
    {
        return [
            'id'                => $u->id,
            'name'              => $u->name,
            'email'             => $u->email,
            'latitude'          => (float) $u->latitude,
            'longitude'         => (float) $u->longitude,
            'email_verified_at' => optional($u->email_verified_at)?->toIso8601String(),
            'created_at'        => optional($u->created_at)?->toIso8601String(),
            'updated_at'        => optional($u->updated_at)?->toIso8601String(),
            'weather'           => $this->weatherArrayOrNull((float) $u->latitude, (float) $u->longitude),
        ];
    }

    /**
     * Ask provider for current weather and return array|null.
     * Never throws—logs and returns null on provider errors.
     */
    private function weatherArrayOrNull(float $latitude, float $longitude): ?array
    {
        try {
            /** @var WeatherResponse|null $weatherResponse */
            $weatherResponse = $this->weather->current($latitude, $longitude);
            return $weatherResponse?->toArray();
        } catch (WeatherException|\Throwable $exception) {
            \Log::warning('Weather provider error', [
                'latitude'  => $latitude,
                'longitude' => $longitude,
                'error'     => $exception->getMessage(),
            ]);
            return null;
        }
    }
}
