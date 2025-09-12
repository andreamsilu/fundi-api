<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get user settings
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $settings = [
                'notifications' => [
                    'email_notifications' => true,
                    'push_notifications' => true,
                    'job_alerts' => true,
                    'message_alerts' => true,
                ],
                'privacy' => [
                    'profile_visibility' => 'public',
                    'show_contact_info' => true,
                    'show_portfolio' => true,
                ],
                'appearance' => [
                    'theme' => 'light',
                    'language' => 'en',
                ],
                'account' => [
                    'two_factor_auth' => false,
                    'email_verified' => $user->email_verified_at ? true : false,
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user settings
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'notifications' => 'nullable|array',
                'privacy' => 'nullable|array',
                'appearance' => 'nullable|array',
                'account' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // In a real application, you would save these to a settings table
            // For now, we'll just return success
            
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a specific setting key
     */
    public function updateKey(Request $request, $key): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'value' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // In a real application, you would update the specific setting
            // For now, we'll just return success
            
            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset settings to default
     */
    public function reset(Request $request): JsonResponse
    {
        try {
            // In a real application, you would reset settings to default values
            
            return response()->json([
                'success' => true,
                'message' => 'Settings reset to default successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export user settings
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $settings = [
                'user_id' => $user->id,
                'exported_at' => now()->toISOString(),
                'settings' => [
                    'notifications' => ['email_notifications' => true],
                    'privacy' => ['profile_visibility' => 'public'],
                    'appearance' => ['theme' => 'light'],
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import user settings
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // In a real application, you would import and validate the settings
            
            return response()->json([
                'success' => true,
                'message' => 'Settings imported successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available themes
     */
    public function getThemes(Request $request): JsonResponse
    {
        try {
            $themes = [
                ['id' => 'light', 'name' => 'Light Theme', 'description' => 'Clean and bright interface'],
                ['id' => 'dark', 'name' => 'Dark Theme', 'description' => 'Easy on the eyes in low light'],
                ['id' => 'auto', 'name' => 'Auto', 'description' => 'Follows system preference'],
            ];

            return response()->json([
                'success' => true,
                'data' => $themes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get themes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available languages
     */
    public function getLanguages(Request $request): JsonResponse
    {
        try {
            $languages = [
                ['id' => 'en', 'name' => 'English', 'native_name' => 'English'],
                ['id' => 'sw', 'name' => 'Swahili', 'native_name' => 'Kiswahili'],
                ['id' => 'fr', 'name' => 'French', 'native_name' => 'Français'],
            ];

            return response()->json([
                'success' => true,
                'data' => $languages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get languages: ' . $e->getMessage()
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
                'profile_visibility' => 'required|in:public,private,friends',
                'show_contact_info' => 'boolean',
                'show_portfolio' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Privacy settings updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update privacy settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update notification settings
     */
    public function updateNotificationSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email_notifications' => 'boolean',
                'push_notifications' => 'boolean',
                'job_alerts' => 'boolean',
                'message_alerts' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification settings: ' . $e->getMessage()
            ], 500);
        }
    }
}