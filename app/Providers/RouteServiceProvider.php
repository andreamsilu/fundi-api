<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Define the "api" rate limiter (using Laravel's default throttling) so that the test (it_can_send_otp_with_valid_phone_number) does not fail with "Rate limiter [api] is not defined."
        RateLimiter::for("api", function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // In test (or local) environment, register a test rate limiter (with a high limit) to avoid "Rate limiter [api] is not defined" errors.
        if (app()->environment('testing') || app()->environment('local')) {
            $this->app['rateLimiter']->for('api', fn () => Limit::perMinute(1000));
        }

        // Routes are now configured in bootstrap/app.php
        // $this->routes(function () {
        //     Route::middleware('api')
        //          ->prefix('api/v1')
        //          ->group(base_path('routes/api.php'));

        //      Route::middleware('web')
        //           ->group(base_path('routes/web.php'));
        // });
    }
} 