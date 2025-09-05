<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Weather\Contracts\WeatherProvider;
use App\Services\Weather\CachedNwsWeatherClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(WeatherProvider::class, function ($app) {
            $ttl = (int) config('weather.cache.current_ttl', 840);
            return new CachedNwsWeatherClient($ttl);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
