<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanctumPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Get the authenticated user from Sanctum
        $user = $request->user('sanctum');
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error' => 'Authentication required'
            ], 401);
        }
        
        // Check if user has the required permission using direct database query
        $hasPermission = $user->permissions()->where('name', $permission)->exists() || 
                        $user->roles()->whereHas('permissions', function($query) use ($permission) {
                            $query->where('name', $permission);
                        })->exists();
        
        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'error' => 'You do not have permission to perform this action'
            ], 403);
        }
        
        return $next($request);
    }
}
