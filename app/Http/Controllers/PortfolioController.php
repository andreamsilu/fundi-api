<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\PortfolioMedia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PortfolioController extends Controller
{
    /**
     * Get fundi portfolio
     */
    public function getFundiPortfolio(Request $request, $fundiId): JsonResponse
    {
        try {
            $portfolio = Portfolio::with('media')
                ->where('fundi_id', $fundiId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Portfolio retrieved successfully',
                'data' => $portfolio
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve portfolio',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving portfolio'
            ], 500);
        }
    }

    /**
     * Create portfolio item
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isFundi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only fundis can create portfolio items'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:150',
                'description' => 'required|string',
                'skills_used' => 'nullable|string',
                'duration_hours' => 'nullable|integer|min:1',
                'budget' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $portfolio = Portfolio::create([
                'fundi_id' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'skills_used' => $request->skills_used,
                'duration_hours' => $request->duration_hours,
                'budget' => $request->budget,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Portfolio item created successfully',
                'data' => $portfolio
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create portfolio item',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while creating portfolio item'
            ], 500);
        }
    }

    /**
     * Update portfolio item
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $portfolio = Portfolio::find($id);

            if (!$portfolio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Portfolio item not found'
                ], 404);
            }

            if ($portfolio->fundi_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update this portfolio item'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:150',
                'description' => 'sometimes|string',
                'skills_used' => 'sometimes|string',
                'duration_hours' => 'sometimes|integer|min:1',
                'budget' => 'sometimes|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $portfolio->update($request->only([
                'title', 'description', 'skills_used', 'duration_hours', 'budget'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Portfolio item updated successfully',
                'data' => $portfolio
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update portfolio item',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating portfolio item'
            ], 500);
        }
    }

    /**
     * Delete portfolio item
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $portfolio = Portfolio::find($id);

            if (!$portfolio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Portfolio item not found'
                ], 404);
            }

            if ($portfolio->fundi_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this portfolio item'
                ], 403);
            }

            $portfolio->delete();

            return response()->json([
                'success' => true,
                'message' => 'Portfolio item deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete portfolio item',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting portfolio item'
            ], 500);
        }
    }

    /**
     * Upload portfolio media (Legacy - use FileUploadController instead)
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'This endpoint is deprecated. Use POST /api/v1/upload/portfolio-media instead'
        ], 410);
    }
}
