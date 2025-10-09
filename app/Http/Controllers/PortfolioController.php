<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\PortfolioMedia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PortfolioController extends Controller
{
    /**
     * Get fundi portfolio (visible items only for customers)
     */
    public function getFundiPortfolio(Request $request, $fundiId): JsonResponse
    {
        try {
            $user = Auth::user();
            $isOwner = $user && $user->id == $fundiId;
            
            $query = Portfolio::with('media')->where('fundi_id', $fundiId);
            
            // If not the owner, only show approved and visible items
            if (!$isOwner) {
                $query->where('status', 'approved')->where('is_visible', true);
            }
            
            $portfolio = $query->orderBy('created_at', 'desc')->get();

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
     * Get current user's portfolio
     */
    public function getMyPortfolio(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->isFundi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only fundis can view their portfolio'
                ], 403);
            }

            $portfolio = Portfolio::with('media')
                ->where('fundi_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Portfolio retrieved successfully',
                'data' => $portfolio,
                'portfolio_count' => $user->getPortfolioCount(),
                'visible_count' => $user->getVisiblePortfolioCount(),
                'can_add_more' => $user->canAddPortfolioItem()
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
            $user = Auth::user();

            if (!$user->isFundi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only fundis can create portfolio items'
                ], 403);
            }

            // Check portfolio work limit (max 5)
            if (!$user->canAddPortfolioItem()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached the maximum limit of 5 portfolio items. Please delete an existing item to add a new one.',
                    'portfolio_count' => $user->getPortfolioCount()
                ], 400);
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
                'status' => 'pending', // New items need approval
                'is_visible' => false, // Not visible until approved
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Portfolio item created successfully and submitted for approval',
                'data' => $portfolio,
                'portfolio_count' => $user->getPortfolioCount() + 1,
                'can_add_more' => $user->getPortfolioCount() < 4 // 4 because we just added one
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
     * Get portfolio status and limits
     */
    public function getPortfolioStatus(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->isFundi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only fundis can view portfolio status'
                ], 403);
            }

            $portfolioCount = $user->getPortfolioCount();
            $visibleCount = $user->getVisiblePortfolioCount();
            $pendingCount = $user->portfolio()->where('status', 'pending')->count();
            $rejectedCount = $user->portfolio()->where('status', 'rejected')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_items' => $portfolioCount,
                    'visible_items' => $visibleCount,
                    'pending_items' => $pendingCount,
                    'rejected_items' => $rejectedCount,
                    'can_add_more' => $user->canAddPortfolioItem(),
                    'max_items' => 5,
                    'remaining_slots' => max(0, 5 - $portfolioCount)
                ],
                'message' => 'Portfolio status retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve portfolio status',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving portfolio status'
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

    /**
     * Get all portfolio items (admin only)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        try {
            $query = Portfolio::with(['fundi', 'media']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by fundi
            if ($request->has('fundi_id')) {
                $query->where('fundi_id', $request->fundi_id);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 15);
            $portfolios = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Portfolio items retrieved successfully',
                'data' => $portfolios->items(),
                'pagination' => [
                    'current_page' => $portfolios->currentPage(),
                    'per_page' => $portfolios->perPage(),
                    'total' => $portfolios->total(),
                    'last_page' => $portfolios->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve portfolio items',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Update portfolio item (admin)
     */
    public function adminUpdate(Request $request, $id): JsonResponse
    {
        try {
            $portfolio = Portfolio::find($id);

            if (!$portfolio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Portfolio item not found'
                ], 404);
            }

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'status' => 'sometimes|in:pending,approved,rejected',
                'is_visible' => 'sometimes|boolean',
                'rejection_reason' => 'sometimes|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $portfolio->update($request->only(['status', 'is_visible', 'rejection_reason']));

            return response()->json([
                'success' => true,
                'message' => 'Portfolio item updated successfully',
                'data' => $portfolio->fresh(['fundi', 'media'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update portfolio item',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Delete portfolio item (admin)
     */
    public function adminDestroy($id): JsonResponse
    {
        try {
            $portfolio = Portfolio::find($id);

            if (!$portfolio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Portfolio item not found'
                ], 404);
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
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
}
