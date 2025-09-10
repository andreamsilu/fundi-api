<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided',
                'error' => 'UNAUTHENTICATED'
            ], 401);
        }

        // Find the token in the database
        $accessToken = PersonalAccessToken::findToken($token);
        
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
                'error' => 'INVALID_TOKEN'
            ], 401);
        }

        // Check if token is expired
        if ($this->isTokenExpired($accessToken)) {
            // Check if we can refresh the token
            if ($this->canRefreshToken($accessToken)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expired but can be refreshed',
                    'error' => 'TOKEN_EXPIRED_REFRESH_AVAILABLE',
                    'refresh_available' => true,
                    'expires_at' => $accessToken->expires_at?->toISOString(),
                ], 401);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expired and cannot be refreshed',
                    'error' => 'TOKEN_EXPIRED_NO_REFRESH'
                ], 401);
            }
        }

        // Set the authenticated user
        $request->setUserResolver(function () use ($accessToken) {
            return $accessToken->tokenable;
        });

        return $next($request);
    }

    /**
     * Check if the token is expired
     */
    private function isTokenExpired(PersonalAccessToken $token): bool
    {
        if (!$token->expires_at) {
            return false; // Token doesn't expire
        }

        return $token->expires_at->isPast();
    }

    /**
     * Check if the token can be refreshed
     */
    private function canRefreshToken(PersonalAccessToken $token): bool
    {
        // Check if token has refresh capability (within refresh window)
        $refreshExpiration = config('sanctum.refresh_expiration', 60 * 24 * 7); // 7 days
        $refreshDeadline = $token->created_at->addMinutes($refreshExpiration);
        
        return $refreshDeadline->isFuture();
    }
}
