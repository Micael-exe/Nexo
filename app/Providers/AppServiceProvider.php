<?php

namespace App\Providers;

use App\Auth\JsonUserProvider;
use App\Services\JsonUserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('json', fn ($app) => new JsonUserProvider($app->make(JsonUserRepository::class), $app['hash']));
    }
}
