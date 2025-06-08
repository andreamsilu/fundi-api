<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use App\Http\Kernel;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware
        $middleware->use([
            \Illuminate\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        // Register all middleware aliases
        $middleware->alias([
            // Core Laravel middleware
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            
            // Custom middleware
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'sanitize.input' => \App\Http\Middleware\SanitizeInput::class,
            'force.https' => \App\Http\Middleware\ForceHttps::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Web middleware group
        $middleware->group('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\ForceHttps::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // API middleware group
        $middleware->group('api', [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\ForceHttps::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
