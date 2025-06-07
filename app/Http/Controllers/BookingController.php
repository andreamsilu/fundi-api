<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="Booking",
 *     required={"customer_id", "fundi_id", "service_job_id", "description", "scheduled_date", "location", "status"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="customer_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="fundi_id", type="integer", format="int64", example=2),
 *     @OA\Property(property="service_job_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="description", type="string", example="Need urgent plumbing repair"),
 *     @OA\Property(property="scheduled_date", type="string", format="date-time", example="2024-03-20T10:00:00Z"),
 *     @OA\Property(property="location", type="string", example="Dar es Salaam, Tanzania"),
 *     @OA\Property(property="status", type="string", enum={"pending", "accepted", "rejected", "completed", "cancelled"}, example="pending"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="customer", ref="#/components/schemas/User"),
 *     @OA\Property(property="fundi", ref="#/components/schemas/User"),
 *     @OA\Property(property="service_job", ref="#/components/schemas/ServiceJob")
 * )
 * 
 * @OA\Schema(
 *     schema="BookingRequest",
 *     required={"fundi_id", "service_job_id", "description", "scheduled_date", "location"},
 *     @OA\Property(property="fundi_id", type="integer", format="int64", example=2),
 *     @OA\Property(property="service_job_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="description", type="string", example="Need urgent plumbing repair"),
 *     @OA\Property(property="scheduled_date", type="string", format="date-time", example="2024-03-20T10:00:00Z"),
 *     @OA\Property(property="location", type="string", example="Dar es Salaam, Tanzania")
 * )
 */
class BookingController extends Controller
{
    /**
     * Get all bookings for the authenticated user.
     *
     * @OA\Get(
     *     path="/bookings",
     *     tags={"Bookings"},
     *     summary="List user's bookings",
     *     description="Get a paginated list of bookings for the authenticated user (customer or fundi)",
     *     operationId="index",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of bookings",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Booking")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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
     * @OA\Post(
     *     path="/bookings",
     *     tags={"Bookings"},
     *     summary="Create a booking",
     *     description="Create a new booking (customers only)",
     *     operationId="store",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BookingRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking created successfully"),
     *             @OA\Property(property="booking", ref="#/components/schemas/Booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fundi does not offer this service")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Fundis cannot create bookings",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fundis cannot create bookings")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fundi not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fundi not found or not available")
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
     * @OA\Get(
     *     path="/bookings/{id}",
     *     tags={"Bookings"},
     *     summary="Get booking details",
     *     description="Get detailed information about a specific booking",
     *     operationId="show",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Booking ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking details",
     *         @OA\JsonContent(ref="#/components/schemas/Booking")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to view this booking",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found"
     *     )
     * )
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
     * @OA\Post(
     *     path="/bookings/{id}/status",
     *     tags={"Bookings"},
     *     summary="Update booking status",
     *     description="Update the status of a booking (fundi only)",
     *     operationId="updateStatus",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Booking ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"accepted", "rejected", "completed", "cancelled"},
     *                 example="accepted"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking status updated successfully"),
     *             @OA\Property(property="booking", ref="#/components/schemas/Booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Only the assigned fundi can update the status",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Only the assigned fundi can update the status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
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
     * @OA\Post(
     *     path="/bookings/{id}/cancel",
     *     tags={"Bookings"},
     *     summary="Cancel booking",
     *     description="Cancel a booking (customer or fundi)",
     *     operationId="cancel",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Booking ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking cancelled successfully"),
     *             @OA\Property(property="booking", ref="#/components/schemas/Booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot cancel completed booking",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot cancel a completed booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to cancel this booking",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found"
     *     )
     * )
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