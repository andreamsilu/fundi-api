<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JWTPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        try {
            // Get the authenticated user from JWT
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Authentication required'
                ], 401);
            }
            
            // Check if user has the required permission using safe methods
            $hasPermission = $user->hasPermissionTo($permission);
            
            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'error' => 'You do not have permission to perform this action'
                ], 403);
            }
            
            return $next($request);
            
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalid or expired',
                'error' => 'Authentication required'
            ], 401);
        }
    }
}

