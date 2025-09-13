<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\AdminSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Get all users
     */
    public function getUsers(Request $request): JsonResponse
    {
        try {
            $users = User::with('fundiProfile')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving users'
            ], 500);
        }
    }

    /**
     * Get specific user
     */
    public function getUser(Request $request, $id): JsonResponse
    {
        try {
            $user = User::with('fundiProfile')->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving user'
            ], 500);
        }
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|in:active,inactive,banned',
                'role' => 'sometimes|in:customer,fundi,admin',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update($request->only(['status', 'role']));

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating user'
            ], 500);
        }
    }

    /**
     * Promote user to fundi role
     */
    public function promoteToFundi(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if (!$user->canBecomeFundi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User cannot be promoted to fundi role. Current roles: ' . implode(', ', $user->roles)
                ], 400);
            }

            $success = $user->promoteToFundi();

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'User promoted to fundi successfully',
                    'data' => $user->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to promote user to fundi'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to promote user to fundi',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while promoting user'
            ], 500);
        }
    }

    /**
     * Promote user to admin role
     */
    public function promoteToAdmin(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if (!$user->canBecomeAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User cannot be promoted to admin role. Current roles: ' . implode(', ', $user->roles)
                ], 400);
            }

            $success = $user->promoteToAdmin();

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'User promoted to admin successfully',
                    'data' => $user->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to promote user to admin'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to promote user to admin',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while promoting user'
            ], 500);
        }
    }

    /**
     * Demote user to customer role
     */
    public function demoteToCustomer(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if ($user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already a customer'
                ], 400);
            }

            $success = $user->demoteToCustomer();

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'User demoted to customer successfully',
                    'data' => $user->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to demote user to customer'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to demote user to customer',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while demoting user'
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting user'
            ], 500);
        }
    }

    /**
     * Get all fundi profiles
     */
    public function getFundiProfiles(Request $request): JsonResponse
    {
        try {
            $fundiProfiles = User::with('fundiProfile')
                ->whereJsonContains('roles', 'fundi')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Fundi profiles retrieved successfully',
                'data' => $fundiProfiles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fundi profiles',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving fundi profiles'
            ], 500);
        }
    }

    /**
     * Verify fundi profile
     */
    public function verifyFundi(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'verification_status' => 'required|in:approved,rejected',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fundiProfile = \App\Models\FundiProfile::where('user_id', $id)->first();

            if (!$fundiProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fundi profile not found'
                ], 404);
            }

            $fundiProfile->update([
                'verification_status' => $request->verification_status,
            ]);

            $fundiProfile->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Fundi verification updated successfully',
                'data' => $fundiProfile
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fundi verification',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating verification'
            ], 500);
        }
    }

    /**
     * Get all jobs
     */
    public function getAllJobs(Request $request): JsonResponse
    {
        try {
            $jobs = \App\Models\Job::with(['customer', 'category', 'applications'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'All jobs retrieved successfully',
                'data' => $jobs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve jobs',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving jobs'
            ], 500);
        }
    }

    /**
     * Get specific job
     */
    public function getJob(Request $request, $id): JsonResponse
    {
        try {
            $job = \App\Models\Job::with(['customer', 'category', 'applications.fundi.fundiProfile', 'media'])
                ->find($id);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Job retrieved successfully',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving job'
            ], 500);
        }
    }

    /**
     * Update job
     */
    public function updateJob(Request $request, $id): JsonResponse
    {
        try {
            $job = \App\Models\Job::find($id);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:150',
                'description' => 'sometimes|string',
                'budget' => 'sometimes|numeric|min:0',
                'deadline' => 'sometimes|date|after:today',
                'location_lat' => 'sometimes|numeric|between:-90,90',
                'location_lng' => 'sometimes|numeric|between:-180,180',
                'status' => 'sometimes|in:open,in_progress,completed,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $job->update($request->only([
                'title', 'description', 'budget', 'deadline',
                'location_lat', 'location_lng', 'status'
            ]));

            $job->load(['customer', 'category']);

            return response()->json([
                'success' => true,
                'message' => 'Job updated successfully',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update job',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating job'
            ], 500);
        }
    }

    /**
     * Delete job
     */
    public function deleteJob(Request $request, $id): JsonResponse
    {
        try {
            $job = \App\Models\Job::find($id);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            $job->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting job'
            ], 500);
        }
    }

    /**
     * Get all job applications
     */
    public function getAllApplications(Request $request): JsonResponse
    {
        try {
            $applications = \App\Models\JobApplication::with(['job', 'fundi.fundiProfile'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'All applications retrieved successfully',
                'data' => $applications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve applications',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving applications'
            ], 500);
        }
    }

    /**
     * Update application
     */
    public function updateApplication(Request $request, $id): JsonResponse
    {
        try {
            $application = \App\Models\JobApplication::find($id);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,accepted,rejected',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $application->update(['status' => $request->status]);

            // If accepted, update job status
            if ($request->status === 'accepted') {
                $application->job->update(['status' => 'in_progress']);
            }

            $application->load(['fundi.fundiProfile', 'job']);

            return response()->json([
                'success' => true,
                'message' => 'Application updated successfully',
                'data' => $application
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update application',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating application'
            ], 500);
        }
    }

    /**
     * Delete application
     */
    public function deleteApplication(Request $request, $id): JsonResponse
    {
        try {
            $application = \App\Models\JobApplication::find($id);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            $application->delete();

            return response()->json([
                'success' => true,
                'message' => 'Application deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete application',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting application'
            ], 500);
        }
    }

    /**
     * Update portfolio
     */
    public function updatePortfolio(Request $request, $id): JsonResponse
    {
        try {
            $portfolio = \App\Models\Portfolio::find($id);

            if (!$portfolio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Portfolio item not found'
                ], 404);
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
     * Delete portfolio
     */
    public function deletePortfolio(Request $request, $id): JsonResponse
    {
        try {
            $portfolio = \App\Models\Portfolio::find($id);

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
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting portfolio item'
            ], 500);
        }
    }

    /**
     * Get all payments
     */
    public function getAllPayments(Request $request): JsonResponse
    {
        try {
            $payments = \App\Models\Payment::with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'All payments retrieved successfully',
                'data' => $payments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payments',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving payments'
            ], 500);
        }
    }

    /**
     * Update payment
     */
    public function updatePayment(Request $request, $id): JsonResponse
    {
        try {
            $payment = \App\Models\Payment::find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,completed,failed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $payment->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating payment'
            ], 500);
        }
    }

    /**
     * Get payment reports
     */
    public function getPaymentReports(Request $request): JsonResponse
    {
        try {
            $totalRevenue = \App\Models\Payment::where('status', 'completed')->sum('amount');
            $pendingPayments = \App\Models\Payment::where('status', 'pending')->count();
            $completedPayments = \App\Models\Payment::where('status', 'completed')->count();
            $failedPayments = \App\Models\Payment::where('status', 'failed')->count();

            // Revenue by payment type
            $revenueByType = \App\Models\Payment::where('status', 'completed')
                ->selectRaw('payment_type, SUM(amount) as total')
                ->groupBy('payment_type')
                ->get();

            // Monthly revenue (last 12 months)
            $monthlyRevenue = \App\Models\Payment::where('status', 'completed')
                ->where('created_at', '>=', now()->subMonths(12))
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Payment reports retrieved successfully',
                'data' => [
                    'summary' => [
                        'total_revenue' => $totalRevenue,
                        'pending_payments' => $pendingPayments,
                        'completed_payments' => $completedPayments,
                        'failed_payments' => $failedPayments,
                    ],
                    'revenue_by_type' => $revenueByType,
                    'monthly_revenue' => $monthlyRevenue,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment reports',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving payment reports'
            ], 500);
        }
    }

    /**
     * Send notification
     */
    public function sendNotification(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'nullable|exists:users,id',
                'type' => 'required|string|max:50',
                'title' => 'required|string|max:150',
                'message' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $notification = \App\Models\Notification::create([
                'user_id' => $request->user_id,
                'type' => $request->type,
                'title' => $request->title,
                'message' => $request->message,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully',
                'data' => $notification
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while sending notification'
            ], 500);
        }
    }

    /**
     * Update notification
     */
    public function updateNotification(Request $request, $id): JsonResponse
    {
        try {
            $notification = \App\Models\Notification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'read_status' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $notification->update(['read_status' => $request->read_status]);

            return response()->json([
                'success' => true,
                'message' => 'Notification updated successfully',
                'data' => $notification
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating notification'
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function deleteNotification(Request $request, $id): JsonResponse
    {
        try {
            $notification = \App\Models\Notification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting notification'
            ], 500);
        }
    }

    /**
     * Create category
     */
    public function createCategory(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:50|unique:categories',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $category = \App\Models\Category::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while creating category'
            ], 500);
        }
    }

    /**
     * Update category
     */
    public function updateCategory(Request $request, $id): JsonResponse
    {
        try {
            $category = \App\Models\Category::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:50|unique:categories,name,' . $id,
                'description' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $category->update($request->only(['name', 'description']));

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating category'
            ], 500);
        }
    }

    /**
     * Delete category
     */
    public function deleteCategory(Request $request, $id): JsonResponse
    {
        try {
            $category = \App\Models\Category::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting category'
            ], 500);
        }
    }

    /**
     * Get settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        try {
            $settings = \App\Models\AdminSetting::first();

            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Settings not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings retrieved successfully',
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve settings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving settings'
            ], 500);
        }
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'payments_enabled' => 'sometimes|boolean',
                'payment_model' => 'sometimes|in:subscription,pay_per_application,pay_per_job,hybrid,free',
                'subscription_enabled' => 'sometimes|boolean',
                'subscription_fee' => 'sometimes|numeric|min:0',
                'subscription_period' => 'sometimes|in:monthly,yearly',
                'job_application_fee_enabled' => 'sometimes|boolean',
                'job_application_fee' => 'sometimes|numeric|min:0',
                'job_posting_fee_enabled' => 'sometimes|boolean',
                'job_posting_fee' => 'sometimes|numeric|min:0',
                // Legacy fields
                'application_fee' => 'sometimes|numeric|min:0',
                'job_post_fee' => 'sometimes|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $settings = \App\Models\AdminSetting::first();
            
            if (!$settings) {
                $settings = \App\Models\AdminSetting::create($request->all());
            } else {
                $settings->update($request->all());
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating settings'
            ], 500);
        }
    }

    /**
     * Get active sessions
     */
    public function getSessions(Request $request): JsonResponse
    {
        try {
            $sessions = \App\Models\UserSession::with('user')
                ->whereNull('logout_at')
                ->where('expired_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Active sessions retrieved successfully',
                'data' => $sessions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sessions',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving sessions'
            ], 500);
        }
    }

    /**
     * Force logout user
     */
    public function forceLogout(Request $request, $id): JsonResponse
    {
        try {
            $session = \App\Models\UserSession::find($id);

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }

            $session->update(['logout_at' => now()]);

            // Revoke the token
            $user = $session->user;
            if ($user) {
                $user->tokens()->where('token', $session->token)->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'User logged out successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to force logout user',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while forcing logout'
            ], 500);
        }
    }
}
