<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * List all categories
     */
    public function index(): JsonResponse
    {
        try {
            $categories = Category::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving categories'
            ], 500);
        }
    }
}
