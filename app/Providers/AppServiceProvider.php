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
        // Dynamically apply database-driven SMTP email settings
        try {
            \App\Services\MailSettingService::applyConfig();
        } catch (\Throwable $e) {
            // Safe fallback if database/table is not ready
        }

        // Register custom Microsoft Azure AD Socialite driver
        Socialite::extend('microsoft', function ($app) {
            $config = $app['config']['services.microsoft'];

            return Socialite::buildProvider(MicrosoftProvider::class, $config);
        });
    }
}
