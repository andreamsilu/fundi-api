<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Get all reviews for a fundi.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if ($user->isFundi()) {
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
     * @param Review $review
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Review $review)
    {
        $review->load(['customer', 'booking']);

        return response()->json($review);
    }

    /**
     * Update a review.
     *
     * @param Review $review
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
     * @param Review $review
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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