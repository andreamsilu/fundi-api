<?php

namespace App\Http\Controllers;

use App\Models\FundiProfile;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Schema(
 *     schema="FundiProfile",
 *     required={"user_id", "service_category_id", "experience_years", "hourly_rate", "availability_status"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="user_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="service_category_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="bio", type="string", example="Experienced plumber with 5 years of experience"),
 *     @OA\Property(property="experience_years", type="integer", minimum=0, example=5),
 *     @OA\Property(property="hourly_rate", type="number", format="float", minimum=0, example=25.00),
 *     @OA\Property(property="availability_status", type="string", enum={"available", "busy", "unavailable"}, example="available"),
 *     @OA\Property(property="location", type="string", example="Dar es Salaam, Tanzania"),
 *     @OA\Property(property="profile_photo", type="string", format="uri", example="https://example.com/photos/profile.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="service_category", ref="#/components/schemas/ServiceCategory")
 * )
 * 
 * @OA\Schema(
 *     schema="FundiProfileRequest",
 *     required={"service_category_id", "experience_years", "hourly_rate", "availability_status"},
 *     @OA\Property(property="service_category_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="bio", type="string", example="Experienced plumber with 5 years of experience"),
 *     @OA\Property(property="experience_years", type="integer", minimum=0, example=5),
 *     @OA\Property(property="hourly_rate", type="number", format="float", minimum=0, example=25.00),
 *     @OA\Property(property="availability_status", type="string", enum={"available", "busy", "unavailable"}, example="available"),
 *     @OA\Property(property="location", type="string", example="Dar es Salaam, Tanzania"),
 *     @OA\Property(property="profile_photo", type="string", format="binary")
 * )
 */
class FundiProfileController extends Controller
{
    /**
     * Display a listing of fundi profiles.
     *
     * @OA\Get(
     *     path="/fundi-profiles",
     *     tags={"Fundi Profiles"},
     *     summary="List all fundi profiles",
     *     description="Get a paginated list of all fundi profiles with optional filters",
     *     operationId="index",
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by service category ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by availability status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"available", "busy", "unavailable"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name or location",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of fundi profiles",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/FundiProfile")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        // ... existing code ...
    }

    /**
     * Store a newly created fundi profile.
     *
     * @OA\Post(
     *     path="/fundi-profiles",
     *     tags={"Fundi Profiles"},
     *     summary="Create a fundi profile",
     *     description="Create a new fundi profile for the authenticated user",
     *     operationId="store",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/FundiProfileRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Fundi profile created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fundi profile created successfully"),
     *             @OA\Property(property="profile", ref="#/components/schemas/FundiProfile")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User already has a fundi profile"
     *     )
     * )
     */
    public function store(Request $request)
    {
        // ... existing code ...
    }

    /**
     * Display the specified fundi profile.
     *
     * @OA\Get(
     *     path="/fundi-profiles/{id}",
     *     tags={"Fundi Profiles"},
     *     summary="Get fundi profile details",
     *     description="Get detailed information about a specific fundi profile",
     *     operationId="show",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Fundi profile ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fundi profile details",
     *         @OA\JsonContent(ref="#/components/schemas/FundiProfile")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fundi profile not found"
     *     )
     * )
     */
    public function show(FundiProfile $fundiProfile)
    {
        // ... existing code ...
    }

    /**
     * Update the specified fundi profile.
     *
     * @OA\Post(
     *     path="/fundi-profiles/{id}",
     *     tags={"Fundi Profiles"},
     *     summary="Update fundi profile",
     *     description="Update an existing fundi profile",
     *     operationId="update",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Fundi profile ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/FundiProfileRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fundi profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fundi profile updated successfully"),
     *             @OA\Property(property="profile", ref="#/components/schemas/FundiProfile")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to update this profile"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fundi profile not found"
     *     )
     * )
     */
    public function update(Request $request, FundiProfile $fundiProfile)
    {
        // ... existing code ...
    }

    /**
     * Remove the specified fundi profile.
     *
     * @OA\Delete(
     *     path="/fundi-profiles/{id}",
     *     tags={"Fundi Profiles"},
     *     summary="Delete fundi profile",
     *     description="Delete a fundi profile",
     *     operationId="destroy",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Fundi profile ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fundi profile deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fundi profile deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to delete this profile"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fundi profile not found"
     *     )
     * )
     */
    public function destroy(FundiProfile $fundiProfile)
    {
        // ... existing code ...
    }
} 