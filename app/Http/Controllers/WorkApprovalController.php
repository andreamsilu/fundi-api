<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\WorkSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class WorkApprovalController extends Controller
{
    /**
     * Get portfolio items pending approval for a customer
     */
    public function getPendingPortfolioItems(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can view pending portfolio items'
                ], 403);
            }

            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);

            $portfolioItems = Portfolio::with(['fundi', 'media'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $portfolioItems,
                'message' => 'Pending portfolio items retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending portfolio items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a portfolio item
     */
    public function approvePortfolioItem(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can approve portfolio items'
                ], 403);
            }

            $portfolioItem = Portfolio::find($id);
            
            if (!$portfolioItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Portfolio item not found'
                ], 404);
            }

            if ($portfolioItem->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Portfolio item is not pending approval'
                ], 400);
            }

            $success = $portfolioItem->approve($user);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Portfolio item approved successfully',
                    'data' => $portfolioItem->fresh(['fundi', 'media'])
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve portfolio item'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve portfolio item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a portfolio item
     */
    public function rejectPortfolioItem(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can reject portfolio items'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'rejection_reason' => 'required|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $portfolioItem = Portfolio::find($id);
            
            if (!$portfolioItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Portfolio item not found'
                ], 404);
            }

            if ($portfolioItem->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Portfolio item is not pending approval'
                ], 400);
            }

            $success = $portfolioItem->reject($user, $request->rejection_reason);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Portfolio item rejected successfully',
                    'data' => $portfolioItem->fresh(['fundi', 'media'])
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject portfolio item'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject portfolio item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get work submissions pending approval for a customer
     */
    public function getPendingWorkSubmissions(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can view pending work submissions'
                ], 403);
            }

            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);

            $workSubmissions = WorkSubmission::with(['fundi', 'jobPosting', 'portfolio'])
                ->where('status', 'submitted')
                ->whereHas('jobPosting', function($query) use ($user) {
                    $query->where('customer_id', $user->id);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $workSubmissions,
                'message' => 'Pending work submissions retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending work submissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a work submission
     */
    public function approveWorkSubmission(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can approve work submissions'
                ], 403);
            }

            $workSubmission = WorkSubmission::find($id);
            
            if (!$workSubmission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work submission not found'
                ], 404);
            }

            // Check if the customer owns the job posting
            if ($workSubmission->jobPosting->customer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only approve work for your own job postings'
                ], 403);
            }

            if ($workSubmission->status !== 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Work submission is not pending approval'
                ], 400);
            }

            $workSubmission->update([
                'status' => 'approved',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Work submission approved successfully',
                'data' => $workSubmission->fresh(['fundi', 'jobPosting', 'portfolio'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve work submission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a work submission
     */
    public function rejectWorkSubmission(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can reject work submissions'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'rejection_reason' => 'required|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $workSubmission = WorkSubmission::find($id);
            
            if (!$workSubmission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work submission not found'
                ], 404);
            }

            // Check if the customer owns the job posting
            if ($workSubmission->jobPosting->customer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only reject work for your own job postings'
                ], 403);
            }

            if ($workSubmission->status !== 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Work submission is not pending approval'
                ], 400);
            }

            $workSubmission->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Work submission rejected successfully',
                'data' => $workSubmission->fresh(['fundi', 'jobPosting', 'portfolio'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject work submission: ' . $e->getMessage()
            ], 500);
        }
    }
}