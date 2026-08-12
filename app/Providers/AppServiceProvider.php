<?php

namespace App\Providers;

use App\Services\Auth\MicrosoftProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

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
        // Register custom Microsoft Azure AD Socialite driver
        Socialite::extend('microsoft', function ($app) {
            $config = $app['config']['services.microsoft'];

            return Socialite::buildProvider(MicrosoftProvider::class, $config);
        });
    }
}
