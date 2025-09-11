<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FundiProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get authenticated user profile
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->load('fundiProfile');

            return response()->json([
                'success' => true,
                'message' => 'User profile retrieved successfully',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user profile',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving profile'
            ], 500);
        }
    }

    /**
     * Update fundi profile
     */
    public function updateFundiProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isFundi() && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only fundis can update fundi profile'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:100',
                'location_lat' => 'nullable|numeric|between:-90,90',
                'location_lng' => 'nullable|numeric|between:-180,180',
                'veta_certificate' => 'nullable|string|max:255',
                'skills' => 'nullable|string',
                'experience_years' => 'nullable|integer|min:0|max:50',
                'bio' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fundiProfile = $user->fundiProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $request->only([
                    'full_name',
                    'location_lat',
                    'location_lng',
                    'veta_certificate',
                    'skills',
                    'experience_years',
                    'bio'
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'Fundi profile updated successfully',
                'data' => $fundiProfile
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fundi profile',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating profile'
            ], 500);
        }
    }

    /**
     * Get fundi profile
     */
    public function getFundiProfile(Request $request, $fundiId): JsonResponse
    {
        try {
            $fundi = User::with('fundiProfile')->find($fundiId);

            if (!$fundi || !$fundi->isFundi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fundi not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fundi profile retrieved successfully',
                'data' => $fundi
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fundi profile',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving fundi profile'
            ], 500);
        }
    }
}
