<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $user = $request->user();

        // Admin users have access to all endpoints
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Check if user has any of the required permissions
        $hasRequiredPermission = false;
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                $hasRequiredPermission = true;
                break;
            }
        }

        if (!$hasRequiredPermission) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions. Required permissions: ' . implode(', ', $permissions)
            ], 403);
        }

        return $next($request);
    }
}