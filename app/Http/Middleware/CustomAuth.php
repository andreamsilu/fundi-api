<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/**
 * Custom Authentication Middleware
 * 
 * Simple token-based authentication to avoid Sanctum recursion issues
 * This middleware checks for Bearer token in Authorization header
 */
class CustomAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $this->getTokenFromRequest($request);
        
        if (!$token) {
            return $this->unauthorizedResponse($request);
        }
        
        $user = $this->getUserFromToken($token);
        
        if (!$user) {
            return $this->unauthorizedResponse($request);
        }
        
        // Set the authenticated user
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        // Set user in Auth facade
        Auth::setUser($user);
        
        // Add user to request for easy access
        $request->merge(['user' => $user]);
        
        return $next($request);
    }
    
    /**
     * Extract token from request
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        $authorization = $request->header('Authorization');
        
        if (!$authorization) {
            return null;
        }
        
        if (strpos($authorization, 'Bearer ') === 0) {
            return substr($authorization, 7);
        }
        
        return null;
    }
    
    /**
     * Get user from token
     */
    private function getUserFromToken(string $token): ?User
    {
        // For testing purposes, we'll create a simple token system
        // In production, you'd want to use proper token validation
        
        // Check if it's a test token
        if (strpos($token, 'test-token') === 0) {
            // Extract user ID from token (simple format: test-token-{user_id})
            $parts = explode('-', $token);
            if (count($parts) >= 3) {
                $userId = end($parts);
                return User::find($userId);
            }
        }
        
        // Check Sanctum tokens as fallback
        $sanctumToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if ($sanctumToken) {
            return $sanctumToken->tokenable;
        }
        
        return null;
    }
    
    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(Request $request)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error' => 'Authentication required'
            ], 401);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated',
            'error' => 'Authentication required'
        ], 401);
    }
}
