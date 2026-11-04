<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('public-api', function (Request $request): Limit {
            // Caddy overwrites this with the real client address, so visitors can't fake it.
            $visitor = $request->header('X-Forwarded-For');

            // The frontend server calls in directly for every visitor, so limiting it would throttle the whole site.
            return $visitor ? Limit::perMinute(120)->by($visitor) : Limit::none();
        });
    }
}
