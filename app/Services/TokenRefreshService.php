<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TokenRefreshService
{
    /**
     * Refresh an expired token
     */
    public static function refreshToken(string $oldToken): array
    {
        try {
            DB::beginTransaction();

            // Find the old token
            $oldAccessToken = PersonalAccessToken::findToken($oldToken);
            
            if (!$oldAccessToken) {
                return [
                    'success' => false,
                    'message' => 'Invalid token',
                    'error' => 'INVALID_TOKEN'
                ];
            }

            // Check if token can be refreshed
            if (!self::canRefreshToken($oldAccessToken)) {
                return [
                    'success' => false,
                    'message' => 'Token cannot be refreshed',
                    'error' => 'REFRESH_NOT_AVAILABLE'
                ];
            }

            $user = $oldAccessToken->tokenable;
            
            // Revoke the old token
            $oldAccessToken->delete();

            // Create new token with same abilities
            $newToken = $user->createToken(
                'auth_token',
                $oldAccessToken->abilities,
                self::getTokenExpiration()
            );

            // User session is now handled by Laravel Sanctum
            // No need to update custom UserSession model

            DB::commit();

            return [
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $newToken->plainTextToken,
                    'expires_at' => self::getTokenExpiration()->toISOString(),
                    'user' => $user->only(['id', 'phone', 'role', 'status'])
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to refresh token',
                'error' => config('app.debug') ? $e->getMessage() : 'Token refresh failed'
            ];
        }
    }

    /**
     * Check if a token can be refreshed
     */
    public static function canRefreshToken(PersonalAccessToken $token): bool
    {
        $refreshExpiration = config('sanctum.refresh_expiration', 60 * 24 * 7); // 7 days
        $refreshDeadline = $token->created_at->addMinutes($refreshExpiration);
        
        return $refreshDeadline->isFuture();
    }

    /**
     * Get token expiration time
     */
    public static function getTokenExpiration(): Carbon
    {
        $expirationMinutes = config('sanctum.expiration', 60 * 24); // 24 hours
        return now()->addMinutes($expirationMinutes);
    }

    /**
     * Revoke all tokens for a user
     */
    public static function revokeAllUserTokens(User $user): bool
    {
        try {
            // Revoke all Sanctum tokens
            $user->tokens()->delete();

            // User session is now handled by Laravel Sanctum
            // No need to update custom UserSession model

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Revoke specific token
     */
    public static function revokeToken(string $token): bool
    {
        try {
            $accessToken = PersonalAccessToken::findToken($token);
            
            if ($accessToken) {
                $accessToken->delete();
                
                // User session is now handled by Laravel Sanctum
                // No need to update custom UserSession model
                
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get token information
     */
    public static function getTokenInfo(string $token): ?array
    {
        $accessToken = PersonalAccessToken::findToken($token);
        
        if (!$accessToken) {
            return null;
        }

        return [
            'id' => $accessToken->id,
            'name' => $accessToken->name,
            'abilities' => $accessToken->abilities,
            'created_at' => $accessToken->created_at->toISOString(),
            'expires_at' => $accessToken->expires_at?->toISOString(),
            'last_used_at' => $accessToken->last_used_at?->toISOString(),
            'can_refresh' => self::canRefreshToken($accessToken),
            'is_expired' => $accessToken->expires_at?->isPast() ?? false,
        ];
    }
}
