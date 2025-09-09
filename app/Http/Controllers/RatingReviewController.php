<?php

namespace App\Http\Controllers;

use App\Models\RatingReview;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RatingReviewController extends Controller
{
    /**
     * Create a rating and review
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can rate fundis'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'fundi_id' => 'required|exists:users,id',
                'job_id' => 'required|exists:jobs,id',
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

            // Check if job belongs to customer and is completed
            $job = Job::where('id', $request->job_id)
                ->where('customer_id', $user->id)
                ->where('status', 'completed')
                ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found or not completed'
                ], 404);
            }

            // Check if fundi was actually assigned to this job
            $application = $job->applications()
                ->where('fundi_id', $request->fundi_id)
                ->where('status', 'accepted')
                ->first();

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fundi was not assigned to this job'
                ], 400);
            }

            // Check if customer already rated this fundi for this job
            $existingRating = RatingReview::where('fundi_id', $request->fundi_id)
                ->where('customer_id', $user->id)
                ->where('job_id', $request->job_id)
                ->first();

            if ($existingRating) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already rated this fundi for this job'
                ], 400);
            }

            $rating = RatingReview::create([
                'fundi_id' => $request->fundi_id,
                'customer_id' => $user->id,
                'job_id' => $request->job_id,
                'rating' => $request->rating,
                'review' => $request->review,
            ]);

            $rating->load(['fundi', 'customer', 'job']);

            return response()->json([
                'success' => true,
                'message' => 'Rating submitted successfully',
                'data' => $rating
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit rating',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while submitting rating'
            ], 500);
        }
    }

    /**
     * Get fundi's ratings and reviews
     */
    public function getFundiRatings(Request $request, $fundiId): JsonResponse
    {
        try {
            $fundi = User::find($fundiId);

            if (!$fundi || !$fundi->isFundi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fundi not found'
                ], 404);
            }

            $ratings = RatingReview::with(['customer', 'job'])
                ->where('fundi_id', $fundiId)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            // Calculate average rating
            $averageRating = RatingReview::where('fundi_id', $fundiId)->avg('rating');
            $totalRatings = RatingReview::where('fundi_id', $fundiId)->count();

            return response()->json([
                'success' => true,
                'message' => 'Fundi ratings retrieved successfully',
                'data' => [
                    'fundi_id' => $fundiId,
                    'average_rating' => round($averageRating, 2),
                    'total_ratings' => $totalRatings,
                    'ratings' => $ratings
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fundi ratings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving ratings'
            ], 500);
        }
    }

    /**
     * Get customer's ratings given
     */
    public function getMyRatings(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can view their ratings'
                ], 403);
            }

            $ratings = RatingReview::with(['fundi.fundiProfile', 'job'])
                ->where('customer_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Your ratings retrieved successfully',
                'data' => $ratings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve your ratings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving ratings'
            ], 500);
        }
    }

    /**
     * Update rating and review
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $rating = RatingReview::find($id);

            if (!$rating) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rating not found'
                ], 404);
            }

            if ($rating->customer_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update this rating'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'rating' => 'sometimes|integer|min:1|max:5',
                'review' => 'sometimes|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rating->update($request->only(['rating', 'review']));
            $rating->load(['fundi', 'customer', 'job']);

            return response()->json([
                'success' => true,
                'message' => 'Rating updated successfully',
                'data' => $rating
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update rating',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating rating'
            ], 500);
        }
    }

    /**
     * Delete rating and review
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $rating = RatingReview::find($id);

            if (!$rating) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rating not found'
                ], 404);
            }

            if ($rating->customer_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this rating'
                ], 403);
            }

            $rating->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rating deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete rating',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting rating'
            ], 500);
        }
    }

    /**
     * Get all ratings (Admin only)
     */
    public function getAllRatings(Request $request): JsonResponse
    {
        try {
            $ratings = RatingReview::with(['fundi.fundiProfile', 'customer', 'job'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'All ratings retrieved successfully',
                'data' => $ratings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ratings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving ratings'
            ], 500);
        }
    }
}