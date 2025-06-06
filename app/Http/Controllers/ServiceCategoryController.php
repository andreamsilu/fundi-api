<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    /**
     * Get all service categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $categories = ServiceCategory::all();

        return response()->json($categories);
    }

    /**
     * Create a new service category.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
     * Get a specific service category.
     *
     * @param ServiceCategory $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ServiceCategory $category)
    {
        return response()->json($category);
    }

    /**
     * Update a service category.
     *
     * @param ServiceCategory $category
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
     * Delete a service category.
     *
     * @param ServiceCategory $category
     * @return \Illuminate\Http\JsonResponse
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