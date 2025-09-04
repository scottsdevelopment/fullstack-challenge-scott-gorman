<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Weather\WeatherProvider;
use App\Services\Weather\NwsWeatherClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(WeatherProvider::class, NwsWeatherClient::class);
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
