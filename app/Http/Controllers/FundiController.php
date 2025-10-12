<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FundiProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Fundi Controller
 * 
 * Handles fundi-related operations including:
 * - Browsing verified fundis
 * - Viewing fundi profiles
 * - Managing fundi data
 * 
 * Follows MVC pattern with proper security and error handling
 */
class FundiController extends Controller
{
    /**
     * Get all verified fundis (for customers to browse)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Get users with fundi role
            $query = User::whereHas('roles', function($q) {
                $q->where('name', 'fundi');
            })->with(['fundiProfile', 'roles']);

            // Don't filter by status by default - let admin see all fundis
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Apply search filter
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%");
                });
            }

            // Filter by verification
            if ($request->has('verification')) {
                if ($request->verification === 'verified') {
                    $query->whereHas('fundiProfile', function($q) {
                        $q->where('is_verified', true);
                    });
                } elseif ($request->verification === 'unverified') {
                    $query->whereDoesntHave('fundiProfile', function($q) {
                        $q->where('is_verified', true);
                    });
                }
            }

            // Filter by location
            if ($request->has('location')) {
                $location = $request->location;
                $query->where('location', 'like', "%{$location}%");
            }

            // Filter by category (from skills)
            if ($request->has('category')) {
                $category = $request->category;
                $query->where(function($q) use ($category) {
                    $q->where('skills', 'like', "%{$category}%")
                      ->orWhereHas('fundiProfile', function($q) use ($category) {
                          $q->where('skills', 'like', "%{$category}%");
                      });
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $fundis = $query->paginate($perPage);

            // Transform the data to include verification_status
            $fundis->getCollection()->transform(function ($user) {
                $userData = $user->toArray();
                $userData['verification_status'] = $user->fundiProfile && $user->fundiProfile->is_verified 
                    ? 'verified' 
                    : 'pending';
                return $userData;
            });

            return response()->json([
                'success' => true,
                'message' => 'Fundis retrieved successfully',
                'data' => $fundis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fundis',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving fundis'
            ], 500);
        }
    }

    /**
     * Get a specific fundi by ID
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = User::whereHas('roles', function($q) {
                $q->where('name', 'fundi');
            })->with(['fundiProfile', 'roles', 'portfolio', 'ratingsReceived'])
            ->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fundi not found'
                ], 404);
            }

            // Calculate average rating
            $averageRating = $user->ratingsReceived()->avg('rating') ?? 0;
            $totalRatings = $user->ratingsReceived()->count();

            $fundiData = [
                'user' => $user,
                'average_rating' => round($averageRating, 1),
                'total_ratings' => $totalRatings,
                'portfolio_count' => $user->portfolio()->count(),
                'visible_portfolio_count' => $user->portfolio()->where('is_visible', true)->count(),
                'completed_jobs' => $user->jobApplications()->where('status', 'completed')->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Fundi retrieved successfully',
                'data' => $fundiData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fundi',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving fundi'
            ], 500);
        }
    }

    /**
     * Create a new fundi (promote user to fundi)
     * This is typically done through fundi application approval
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // This endpoint is for admin to manually promote a user to fundi
            // In practice, fundis are created through fundi application approval
            
            return response()->json([
                'success' => false,
                'message' => 'Use fundi application approval process to create fundis'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create fundi',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Update fundi information (admin only)
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = User::whereHas('roles', function($q) {
                $q->where('name', 'fundi');
            })->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fundi not found'
                ], 404);
            }

            // Update user fields
            $updateData = [];
            if ($request->has('full_name')) $updateData['full_name'] = $request->full_name;
            if ($request->has('email')) $updateData['email'] = $request->email;
            if ($request->has('status')) $updateData['status'] = $request->status;
            if ($request->has('location')) $updateData['location'] = $request->location;
            if ($request->has('bio')) $updateData['bio'] = $request->bio;
            if ($request->has('skills')) $updateData['skills'] = json_encode($request->skills);
            if ($request->has('languages')) $updateData['languages'] = json_encode($request->languages);

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            // Update fundi profile if exists
            if ($user->fundiProfile && $request->has('fundi_profile')) {
                $user->fundiProfile->update($request->fundi_profile);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fundi updated successfully',
                'data' => $user->load(['fundiProfile', 'roles'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fundi',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating fundi'
            ], 500);
        }
    }

    /**
     * Delete/ban a fundi (admin only)
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = User::whereHas('roles', function($q) {
                $q->where('name', 'fundi');
            })->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fundi not found'
                ], 404);
            }

            // Instead of deleting, ban the user
            $user->update(['status' => 'banned']);

            return response()->json([
                'success' => true,
                'message' => 'Fundi banned successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to ban fundi',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while banning fundi'
            ], 500);
        }
    }
}

