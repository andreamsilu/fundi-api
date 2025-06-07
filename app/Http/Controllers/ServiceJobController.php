<?php

namespace App\Http\Controllers;

use App\Models\ServiceJob;
use App\Models\FundiProfile;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="ServiceJob",
 *     required={"title", "description", "customer_id", "service_category_id", "status", "budget"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="title", type="string", example="Fix leaking kitchen sink"),
 *     @OA\Property(property="description", type="string", example="Kitchen sink has been leaking for a week, needs urgent repair"),
 *     @OA\Property(property="customer_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="service_category_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="fundi_id", type="integer", format="int64", nullable=true, example=2),
 *     @OA\Property(property="status", type="string", enum={"open", "assigned", "in_progress", "completed", "cancelled"}, example="open"),
 *     @OA\Property(property="budget", type="number", format="float", minimum=0, example=50000.00),
 *     @OA\Property(property="location", type="string", example="Dar es Salaam, Tanzania"),
 *     @OA\Property(property="scheduled_date", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="customer", ref="#/components/schemas/User"),
 *     @OA\Property(property="fundi", ref="#/components/schemas/FundiProfile"),
 *     @OA\Property(property="service_category", ref="#/components/schemas/ServiceCategory")
 * )
 * 
 * @OA\Schema(
 *     schema="ServiceJobRequest",
 *     required={"title", "description", "service_category_id", "budget"},
 *     @OA\Property(property="title", type="string", example="Fix leaking kitchen sink"),
 *     @OA\Property(property="description", type="string", example="Kitchen sink has been leaking for a week, needs urgent repair"),
 *     @OA\Property(property="service_category_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="budget", type="number", format="float", minimum=0, example=50000.00),
 *     @OA\Property(property="location", type="string", example="Dar es Salaam, Tanzania"),
 *     @OA\Property(property="scheduled_date", type="string", format="date-time", example="2024-03-20T10:00:00Z")
 * )
 */
class ServiceJobController extends Controller
{
    /**
     * Display a listing of service jobs.
     *
     * @OA\Get(
     *     path="/service-jobs",
     *     tags={"Service Jobs"},
     *     summary="List service jobs",
     *     description="Get a paginated list of service jobs with optional filters",
     *     operationId="index",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by job status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"open", "assigned", "in_progress", "completed", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by service category ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by title or description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of service jobs",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ServiceJob")),
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
        // ... existing code ...
    }

    /**
     * Store a newly created service job.
     *
     * @OA\Post(
     *     path="/service-jobs",
     *     tags={"Service Jobs"},
     *     summary="Create a service job",
     *     description="Create a new service job request",
     *     operationId="store",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ServiceJobRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service job created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service job created successfully"),
     *             @OA\Property(property="job", ref="#/components/schemas/ServiceJob")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function store(Request $request)
    {
        // ... existing code ...
    }

    /**
     * Display the specified service job.
     *
     * @OA\Get(
     *     path="/service-jobs/{id}",
     *     tags={"Service Jobs"},
     *     summary="Get service job details",
     *     description="Get detailed information about a specific service job",
     *     operationId="show",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Service job ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service job details",
     *         @OA\JsonContent(ref="#/components/schemas/ServiceJob")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to view this job"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service job not found"
     *     )
     * )
     */
    public function show(ServiceJob $serviceJob)
    {
        // ... existing code ...
    }

    /**
     * Update the specified service job.
     *
     * @OA\Post(
     *     path="/service-jobs/{id}",
     *     tags={"Service Jobs"},
     *     summary="Update service job",
     *     description="Update an existing service job",
     *     operationId="update",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Service job ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ServiceJobRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service job updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service job updated successfully"),
     *             @OA\Property(property="job", ref="#/components/schemas/ServiceJob")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to update this job"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service job not found"
     *     )
     * )
     */
    public function update(Request $request, ServiceJob $serviceJob)
    {
        // ... existing code ...
    }

    /**
     * Remove the specified service job.
     *
     * @OA\Delete(
     *     path="/service-jobs/{id}",
     *     tags={"Service Jobs"},
     *     summary="Delete service job",
     *     description="Delete a service job",
     *     operationId="destroy",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Service job ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service job deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service job deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to delete this job"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service job not found"
     *     )
     * )
     */
    public function destroy(ServiceJob $serviceJob)
    {
        // ... existing code ...
    }

    /**
     * Assign a fundi to a service job.
     *
     * @OA\Post(
     *     path="/service-jobs/{id}/assign",
     *     tags={"Service Jobs"},
     *     summary="Assign fundi to job",
     *     description="Assign a fundi to a service job",
     *     operationId="assignFundi",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Service job ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"fundi_id"},
     *             @OA\Property(property="fundi_id", type="integer", format="int64", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fundi assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fundi assigned successfully"),
     *             @OA\Property(property="job", ref="#/components/schemas/ServiceJob")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to assign fundi"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service job or fundi not found"
     *     )
     * )
     */
    public function assignFundi(Request $request, ServiceJob $serviceJob)
    {
        // ... existing code ...
    }

    /**
     * Update the status of a service job.
     *
     * @OA\Post(
     *     path="/service-jobs/{id}/status",
     *     tags={"Service Jobs"},
     *     summary="Update job status",
     *     description="Update the status of a service job",
     *     operationId="updateStatus",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Service job ID",
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
     *                 enum={"open", "assigned", "in_progress", "completed", "cancelled"},
     *                 example="in_progress"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Job status updated successfully"),
     *             @OA\Property(property="job", ref="#/components/schemas/ServiceJob")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to update status"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service job not found"
     *     )
     * )
     */
    public function updateStatus(Request $request, ServiceJob $serviceJob)
    {
        // ... existing code ...
    }
} 