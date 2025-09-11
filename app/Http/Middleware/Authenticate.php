<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        if ($this->authenticate($request, $guards)) {
            return $next($request);
        }

        // For API requests, return JSON response instead of redirecting
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error' => 'Authentication required'
            ], 401);
        }

        // For web requests, redirect to login (if login route exists)
        // Since this is an API-only application, we'll return a JSON response
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated',
            'error' => 'Authentication required'
        ], 401);
    }

}
