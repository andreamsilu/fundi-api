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

            // Compute role-aware stats to display on profile
            $stats = [];

            if ($user->isCustomer()) {
                $stats['jobs'] = [
                    'total' => \App\Models\Job::where('customer_id', $user->id)->count(),
                    'open' => \App\Models\Job::where('customer_id', $user->id)->where('status', 'open')->count(),
                    'in_progress' => \App\Models\Job::where('customer_id', $user->id)->where('status', 'in_progress')->count(),
                    'completed' => \App\Models\Job::where('customer_id', $user->id)->where('status', 'completed')->count(),
                ];
            }

            if ($user->isFundi()) {
                $averageRating = $user->ratingsReceived()->avg('rating') ?? 0;
                $totalRatings = $user->ratingsReceived()->count();

                $stats['applications'] = [
                    'total' => \App\Models\JobApplication::where('fundi_id', $user->id)->count(),
                    'pending' => \App\Models\JobApplication::where('fundi_id', $user->id)->where('status', 'pending')->count(),
                    'accepted' => \App\Models\JobApplication::where('fundi_id', $user->id)->where('status', 'accepted')->count(),
                    'rejected' => \App\Models\JobApplication::where('fundi_id', $user->id)->where('status', 'rejected')->count(),
                ];

                $stats['portfolio'] = [
                    'total' => $user->getPortfolioCount(),
                    'visible' => $user->getVisiblePortfolioCount(),
                ];

                // Attach handy fields for mobile rendering
                $user->setAttribute('average_rating', round((float) $averageRating, 1));
                $user->setAttribute('total_ratings', $totalRatings);
            }

            return response()->json([
                'success' => true,
                'message' => 'User profile retrieved successfully',
                'data' => [
                    'user' => $user,
                    'stats' => $stats,
                ]
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
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'first_name' => 'sometimes|string|max:50',
                'last_name' => 'sometimes|string|max:50',
                'email' => 'sometimes|email|max:100|unique:users,email,' . $user->id,
                'phone_number' => 'sometimes|string|max:15|unique:users,phone,' . $user->id,
                'profile_image_url' => 'sometimes|string|max:500',
                'bio' => 'sometimes|string|max:500',
                'location' => 'sometimes|string|max:100',
                'nida_number' => 'sometimes|string|max:20',
                'veta_certificate' => 'sometimes|string|max:255',
                'skills' => 'sometimes|array',
                'skills.*' => 'string|max:50',
                'languages' => 'sometimes|array',
                'languages.*' => 'string|max:50',
                'preferences' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update user profile
            $user->update($request->only([
                'first_name',
                'last_name', 
                'email',
                'phone_number',
                'profile_image_url',
                'bio',
                'location',
                'nida_number',
                'veta_certificate',
                'skills',
                'languages',
                'preferences'
            ]));

            $user->load('fundiProfile');

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating profile'
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

            // Compute stats for fundi profile display
            $averageRating = $fundi->ratingsReceived()->avg('rating') ?? 0;
            $totalRatings = $fundi->ratingsReceived()->count();
            $stats = [
                'applications' => [
                    'total' => \App\Models\JobApplication::where('fundi_id', $fundi->id)->count(),
                    'pending' => \App\Models\JobApplication::where('fundi_id', $fundi->id)->where('status', 'pending')->count(),
                    'accepted' => \App\Models\JobApplication::where('fundi_id', $fundi->id)->where('status', 'accepted')->count(),
                    'rejected' => \App\Models\JobApplication::where('fundi_id', $fundi->id)->where('status', 'rejected')->count(),
                ],
                'portfolio' => [
                    'total' => $fundi->getPortfolioCount(),
                    'visible' => $fundi->getVisiblePortfolioCount(),
                ],
                'ratings' => [
                    'average' => round((float) $averageRating, 1),
                    'count' => $totalRatings,
                ],
            ];

            $fundi->setAttribute('average_rating', $stats['ratings']['average']);
            $fundi->setAttribute('total_ratings', $stats['ratings']['count']);

            return response()->json([
                'success' => true,
                'message' => 'Fundi profile retrieved successfully',
                'data' => [
                    'user' => $fundi,
                    'stats' => $stats,
                ]
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
