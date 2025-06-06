<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /**
     * Get all bookings for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Booking::query();

        if ($user->isFundi()) {
            $query->where('fundi_id', $user->id);
        } else {
            $query->where('customer_id', $user->id);
        }

        $bookings = $query->with(['customer', 'fundi', 'serviceJob'])
            ->latest()
            ->paginate(10);

        return response()->json($bookings);
    }

    /**
     * Create a new booking.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if ($user->isFundi()) {
            return response()->json(['message' => 'Fundis cannot create bookings'], 403);
        }

        $request->validate([
            'fundi_id' => ['required', 'exists:users,id'],
            'service_job_id' => ['required', 'exists:service_jobs,id'],
            'description' => ['required', 'string', 'max:1000'],
            'scheduled_date' => ['required', 'date', 'after:today'],
            'location' => ['required', 'string', 'max:255'],
        ]);

        // Verify fundi exists and is available
        $fundi = User::where('id', $request->fundi_id)
            ->where('role', 'fundi')
            ->whereHas('fundiProfile', function ($query) {
                $query->where('is_available', true);
            })
            ->first();

        if (!$fundi) {
            return response()->json(['message' => 'Fundi not found or not available'], 404);
        }

        // Verify fundi offers the requested service
        $serviceJob = $fundi->fundiProfile->serviceCategories()
            ->where('service_categories.id', $request->service_job_id)
            ->exists();

        if (!$serviceJob) {
            return response()->json(['message' => 'Fundi does not offer this service'], 400);
        }

        $booking = Booking::create([
            'customer_id' => $user->id,
            'fundi_id' => $request->fundi_id,
            'service_job_id' => $request->service_job_id,
            'description' => $request->description,
            'scheduled_date' => $request->scheduled_date,
            'location' => $request->location,
            'status' => 'pending',
        ]);

        $booking->load(['customer', 'fundi', 'serviceJob']);

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking,
        ], 201);
    }

    /**
     * Get a specific booking.
     *
     * @param Booking $booking
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Booking $booking, Request $request)
    {
        $user = $request->user();

        if ($user->id !== $booking->customer_id && $user->id !== $booking->fundi_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->load(['customer', 'fundi', 'serviceJob']);

        return response()->json($booking);
    }

    /**
     * Update booking status.
     *
     * @param Booking $booking
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Booking $booking, Request $request)
    {
        $user = $request->user();

        if ($user->id !== $booking->fundi_id) {
            return response()->json(['message' => 'Only the assigned fundi can update the status'], 403);
        }

        $request->validate([
            'status' => ['required', 'string', Rule::in(['accepted', 'rejected', 'completed', 'cancelled'])],
        ]);

        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'message' => 'Booking status updated successfully',
            'booking' => $booking->load(['customer', 'fundi', 'serviceJob']),
        ]);
    }

    /**
     * Cancel a booking.
     *
     * @param Booking $booking
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Booking $booking, Request $request)
    {
        $user = $request->user();

        if ($user->id !== $booking->customer_id && $user->id !== $booking->fundi_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status === 'completed') {
            return response()->json(['message' => 'Cannot cancel a completed booking'], 400);
        }

        $booking->status = 'cancelled';
        $booking->save();

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking' => $booking->load(['customer', 'fundi', 'serviceJob']),
        ]);
    }
} 