<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Review",
 *     required={"booking_id", "customer_id", "fundi_id", "rating", "comment"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="booking_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="customer_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="fundi_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
 *     @OA\Property(property="comment", type="string", example="Excellent service, very professional"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="customer", ref="#/components/schemas/User"),
 *     @OA\Property(property="booking", ref="#/components/schemas/Booking")
 * )
 * 
 * @OA\Schema(
 *     schema="ReviewRequest",
 *     required={"booking_id", "rating", "comment"},
 *     @OA\Property(property="booking_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
 *     @OA\Property(property="comment", type="string", example="Excellent service, very professional")
 * )
 */
class ReviewController extends Controller
{
    /**
     * Get all reviews for a fundi.
     *
     * @OA\Get(
     *     path="/fundis/{fundi}/reviews",
     *     tags={"Reviews"},
     *     summary="Get fundi reviews",
     *     description="Get all reviews for a specific fundi",
     *     operationId="fundiReviews",
     *     @OA\Parameter(
     *         name="fundi",
     *         in="path",
     *         description="Fundi ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of fundi reviews",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Review")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User is not a fundi",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User is not a fundi")
     *         )
     *     )
     * )
     */
    public function fundiReviews(User $fundi)
    {
        if (!$fundi->canActAsFundi()) {
            return response()->json(['message' => 'User is not a fundi'], 404);
        }

        $reviews = Review::where('fundi_id', $fundi->id)
            ->with(['customer', 'booking'])
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * Get all reviews for a fundi.
     *
     * @OA\Get(
     *     path="/reviews",
     *     tags={"Reviews"},
     *     summary="List reviews",
     *     description="Get a paginated list of reviews for a specific fundi",
     *     operationId="index",
     *     @OA\Parameter(
     *         name="fundi_id",
     *         in="query",
     *         description="Fundi ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of reviews",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Review")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The fundi id field is required."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $request->validate([
            'fundi_id' => ['required', 'exists:users,id'],
        ]);

        $reviews = Review::where('fundi_id', $request->fundi_id)
            ->with(['customer', 'booking'])
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * Create a new review.
     *
     * @OA\Post(
     *     path="/reviews",
     *     tags={"Reviews"},
     *     summary="Create a review",
     *     description="Create a new review for a completed booking",
     *     operationId="store",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ReviewRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Review created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review created successfully"),
     *             @OA\Property(property="review", ref="#/components/schemas/Review")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Can only review completed bookings")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fundis cannot create reviews")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if ($user->canActAsFundi()) {
            return response()->json(['message' => 'Fundis cannot create reviews'], 403);
        }

        $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        // Verify booking belongs to the customer
        if ($booking->customer_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify booking is completed
        if ($booking->status !== 'completed') {
            return response()->json(['message' => 'Can only review completed bookings'], 400);
        }

        // Check if review already exists
        if ($booking->review) {
            return response()->json(['message' => 'Booking already has a review'], 400);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'customer_id' => $user->id,
            'fundi_id' => $booking->fundi_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Update fundi's average rating
        $fundi = $booking->fundi;
        $averageRating = $fundi->reviews()->avg('rating');
        $fundi->fundiProfile()->update(['rating' => $averageRating]);

        return response()->json([
            'message' => 'Review created successfully',
            'review' => $review->load(['customer', 'booking']),
        ], 201);
    }

    /**
     * Get a specific review.
     *
     * @OA\Get(
     *     path="/reviews/{id}",
     *     tags={"Reviews"},
     *     summary="Get review details",
     *     description="Get detailed information about a specific review",
     *     operationId="show",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review details",
     *         @OA\JsonContent(ref="#/components/schemas/Review")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Review not found"
     *     )
     * )
     */
    public function show(Review $review)
    {
        $review->load(['customer', 'booking']);

        return response()->json($review);
    }

    /**
     * Update a review.
     *
     * @OA\Post(
     *     path="/reviews/{id}",
     *     tags={"Reviews"},
     *     summary="Update review",
     *     description="Update an existing review",
     *     operationId="update",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rating", "comment"},
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
     *             @OA\Property(property="comment", type="string", example="Updated review comment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review updated successfully"),
     *             @OA\Property(property="review", ref="#/components/schemas/Review")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Only the reviewer can update the review")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Review not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Review $review, Request $request)
    {
        $user = $request->user();

        if ($user->id !== $review->customer_id) {
            return response()->json(['message' => 'Only the reviewer can update the review'], 403);
        }

        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Update fundi's average rating
        $fundi = $review->fundi;
        $averageRating = $fundi->reviews()->avg('rating');
        $fundi->fundiProfile()->update(['rating' => $averageRating]);

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review->load(['customer', 'booking']),
        ]);
    }

    /**
     * Delete a review.
     *
     * @OA\Delete(
     *     path="/reviews/{id}",
     *     tags={"Reviews"},
     *     summary="Delete review",
     *     description="Delete a review",
     *     operationId="destroy",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Only the reviewer can delete the review")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Review not found"
     *     )
     * )
     */
    public function destroy(Review $review, Request $request)
    {
        $user = $request->user();

        if ($user->id !== $review->customer_id) {
            return response()->json(['message' => 'Only the reviewer can delete the review'], 403);
        }

        $fundi = $review->fundi;
        $review->delete();

        // Update fundi's average rating
        $averageRating = $fundi->reviews()->avg('rating');
        $fundi->fundiProfile()->update(['rating' => $averageRating]);

        return response()->json([
            'message' => 'Review deleted successfully',
        ]);
    }
} 