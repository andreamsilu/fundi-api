<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    /**
     * Create a new rating
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'fundi_id' => 'required|exists:users,id',
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'nullable|string|max:1000',
                'job_id' => 'nullable|exists:job_postings,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            
            // Check if user has already rated this fundi
            $existingRating = \DB::table('ratings_reviews')
                ->where('customer_id', $user->id)
                ->where('fundi_id', $request->fundi_id)
                ->first();

            if ($existingRating) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already rated this fundi'
                ], 400);
            }

            $ratingId = \DB::table('ratings_reviews')->insertGetId([
                'customer_id' => $user->id,
                'fundi_id' => $request->fundi_id,
                'rating' => $request->rating,
                'review' => $request->review,
                'job_id' => $request->job_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rating created successfully',
                'data' => ['id' => $ratingId]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create rating: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ratings for a specific fundi
     */
    public function getFundiRatings(Request $request, $fundiId): JsonResponse
    {
        try {
            $ratings = \DB::table('ratings_reviews')
                ->join('users', 'ratings_reviews.customer_id', '=', 'users.id')
                ->where('ratings_reviews.fundi_id', $fundiId)
                ->select(
                    'ratings_reviews.*',
                    'users.name as customer_name',
                    'users.email as customer_email'
                )
                ->orderBy('ratings_reviews.created_at', 'desc')
                ->get();

            $averageRating = \DB::table('ratings_reviews')
                ->where('fundi_id', $fundiId)
                ->avg('rating');

            return response()->json([
                'success' => true,
                'data' => [
                    'ratings' => $ratings,
                    'average_rating' => round($averageRating, 1),
                    'total_ratings' => $ratings->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get fundi ratings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user's ratings
     */
    public function getMyRatings(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $ratings = \DB::table('ratings_reviews')
                ->join('users', 'ratings_reviews.fundi_id', '=', 'users.id')
                ->where('ratings_reviews.customer_id', $user->id)
                ->select(
                    'ratings_reviews.*',
                    'users.name as fundi_name',
                    'users.email as fundi_email'
                )
                ->orderBy('ratings_reviews.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $ratings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get my ratings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a rating
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            
            $rating = \DB::table('ratings_reviews')
                ->where('id', $id)
                ->where('customer_id', $user->id)
                ->first();

            if (!$rating) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rating not found or unauthorized'
                ], 404);
            }

            \DB::table('ratings_reviews')
                ->where('id', $id)
                ->update([
                    'rating' => $request->rating,
                    'review' => $request->review,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Rating updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update rating: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a rating
     */
    public function delete(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $rating = \DB::table('ratings_reviews')
                ->where('id', $id)
                ->where('customer_id', $user->id)
                ->first();

            if (!$rating) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rating not found or unauthorized'
                ], 404);
            }

            \DB::table('ratings_reviews')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rating deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete rating: ' . $e->getMessage()
            ], 500);
        }
    }
}