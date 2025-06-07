<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="ServiceCategory",
 *     required={"name", "description"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="Plumbing"),
 *     @OA\Property(property="description", type="string", example="Professional plumbing services including repairs, installations, and maintenance"),
 *     @OA\Property(property="icon", type="string", format="uri", example="https://example.com/icons/plumbing.png"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="fundi_profiles", type="array", @OA\Items(ref="#/components/schemas/FundiProfile"))
 * )
 * 
 * @OA\Schema(
 *     schema="ServiceCategoryRequest",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", example="Plumbing"),
 *     @OA\Property(property="description", type="string", example="Professional plumbing services including repairs, installations, and maintenance"),
 *     @OA\Property(property="icon", type="string", format="binary")
 * )
 */
class ServiceCategoryController extends Controller
{
    /**
     * Display a listing of service categories.
     *
     * @OA\Get(
     *     path="/service-categories",
     *     tags={"Service Categories"},
     *     summary="List all service categories",
     *     description="Get a list of all service categories with their associated fundi profiles",
     *     operationId="index",
     *     @OA\Response(
     *         response=200,
     *         description="List of service categories",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/ServiceCategory")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $categories = ServiceCategory::all();

        return response()->json($categories);
    }

    /**
     * Store a newly created service category.
     *
     * @OA\Post(
     *     path="/service-categories",
     *     tags={"Service Categories"},
     *     summary="Create a service category",
     *     description="Create a new service category (admin only)",
     *     operationId="store",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/ServiceCategoryRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service category created successfully"),
     *             @OA\Property(property="category", ref="#/components/schemas/ServiceCategory")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin access required"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:service_categories'],
            'description' => ['required', 'string', 'max:1000'],
        ]);

        $category = ServiceCategory::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Service category created successfully',
            'category' => $category,
        ], 201);
    }

    /**
     * Display the specified service category.
     *
     * @OA\Get(
     *     path="/service-categories/{id}",
     *     tags={"Service Categories"},
     *     summary="Get service category details",
     *     description="Get detailed information about a specific service category",
     *     operationId="show",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Service category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service category details",
     *         @OA\JsonContent(ref="#/components/schemas/ServiceCategory")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service category not found"
     *     )
     * )
     */
    public function show(ServiceCategory $category)
    {
        return response()->json($category);
    }

    /**
     * Update the specified service category.
     *
     * @OA\Post(
     *     path="/service-categories/{id}",
     *     tags={"Service Categories"},
     *     summary="Update service category",
     *     description="Update an existing service category (admin only)",
     *     operationId="update",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Service category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/ServiceCategoryRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service category updated successfully"),
     *             @OA\Property(property="category", ref="#/components/schemas/ServiceCategory")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin access required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service category not found"
     *     )
     * )
     */
    public function update(ServiceCategory $category, Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:service_categories,name,' . $category->id],
            'description' => ['required', 'string', 'max:1000'],
        ]);

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Service category updated successfully',
            'category' => $category,
        ]);
    }

    /**
     * Remove the specified service category.
     *
     * @OA\Delete(
     *     path="/service-categories/{id}",
     *     tags={"Service Categories"},
     *     summary="Delete service category",
     *     description="Delete a service category (admin only)",
     *     operationId="destroy",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Service category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service category deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin access required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service category not found"
     *     )
     * )
     */
    public function destroy(ServiceCategory $category)
    {
        // Check if category is in use
        if ($category->fundiProfiles()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category that is assigned to fundis',
            ], 400);
        }

        $category->delete();

        return response()->json([
            'message' => 'Service category deleted successfully',
        ]);
    }
} 