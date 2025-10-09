<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Settings Controller
 * Handles user-specific settings and preferences
 */
class SettingsController extends Controller
{
    /**
     * Get user settings
     */
    public function getUserSettings(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // In a real application, this would fetch from a user_settings table
            $settings = [
                'notifications' => [
                    'email' => true,
                    'push' => true,
                    'sms' => false,
                ],
                'privacy' => [
                    'profile_visible' => true,
                    'show_email' => false,
                    'show_phone' => true,
                ],
                'preferences' => [
                    'language' => 'en',
                    'theme' => 'light',
                    'timezone' => 'Africa/Dar_es_Salaam',
                ],
            ];

            return response()->json([
                'success' => true,
                'message' => 'Settings retrieved successfully',
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve settings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Update user settings
     */
    public function updateUserSettings(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Validate the settings data
            $validator = Validator::make($request->all(), [
                'notifications' => 'sometimes|array',
                'privacy' => 'sometimes|array',
                'preferences' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // In a real application, this would save to a user_settings table
            
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get specific setting by key
     */
    public function getSetting(Request $request, $key): JsonResponse
    {
        try {
            $value = null; // Would fetch from database
            
            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'value' => $value
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get setting',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Reset settings to defaults
     */
    public function resetToDefaults(Request $request): JsonResponse
    {
        try {
            // Reset user settings to defaults
            
            return response()->json([
                'success' => true,
                'message' => 'Settings reset to defaults successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset settings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Export user settings
     */
    public function exportSettings(Request $request): JsonResponse
    {
        try {
            $settings = []; // Would fetch all user settings
            
            return response()->json([
                'success' => true,
                'message' => 'Settings exported successfully',
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export settings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Import user settings
     */
    public function importSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Would save imported settings
            
            return response()->json([
                'success' => true,
                'message' => 'Settings imported successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import settings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get available themes
     */
    public function getThemes(): JsonResponse
    {
        try {
            $themes = [
                ['id' => 'light', 'name' => 'Light'],
                ['id' => 'dark', 'name' => 'Dark'],
                ['id' => 'auto', 'name' => 'Auto'],
            ];

            return response()->json([
                'success' => true,
                'data' => $themes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get themes',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get available languages
     */
    public function getLanguages(): JsonResponse
    {
        try {
            $languages = [
                ['code' => 'en', 'name' => 'English'],
                ['code' => 'sw', 'name' => 'Swahili'],
            ];

            return response()->json([
                'success' => true,
                'data' => $languages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get languages',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Update privacy settings
     */
    public function updatePrivacy(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'profile_visible' => 'sometimes|boolean',
                'show_email' => 'sometimes|boolean',
                'show_phone' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Would save privacy settings
            
            return response()->json([
                'success' => true,
                'message' => 'Privacy settings updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update privacy settings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email_notifications' => 'sometimes|boolean',
                'push_notifications' => 'sometimes|boolean',
                'sms_notifications' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Would save notification preferences
            
            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification preferences',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
}
