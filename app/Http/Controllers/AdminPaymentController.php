<?php

namespace App\Http\Controllers;

use App\Models\PaymentPlan;
use App\Models\UserSubscription;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AdminPaymentController extends Controller
{
    /**
     * Get all payment plans
     */
    public function getPlans(Request $request): JsonResponse
    {
        try {
            $plans = PaymentPlan::orderBy('type')->orderBy('price')->get();

            return response()->json([
                'success' => true,
                'message' => 'Payment plans retrieved successfully',
                'data' => $plans
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment plans',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving payment plans'
            ], 500);
        }
    }

    /**
     * Create a new payment plan
     */
    public function createPlan(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'type' => 'required|string|in:free,subscription,pay_per_use',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'billing_cycle' => 'nullable|string|in:monthly,yearly',
                'features' => 'nullable|array',
                'limits' => 'nullable|array',
                'is_active' => 'boolean',
                'is_default' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // If setting as default, unset other defaults of same type
            if ($request->is_default) {
                PaymentPlan::where('type', $request->type)
                    ->update(['is_default' => false]);
            }

            $plan = PaymentPlan::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Payment plan created successfully',
                'data' => $plan
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment plan',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while creating payment plan'
            ], 500);
        }
    }

    /**
     * Get a payment plan by ID
     */
    public function getPlan(Request $request, $id): JsonResponse
    {
        try {
            $plan = PaymentPlan::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Payment plan retrieved successfully',
                'data' => $plan
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment plan',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Update a payment plan
     */
    public function updatePlan(Request $request, $id): JsonResponse
    {
        try {
            $plan = PaymentPlan::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'type' => 'sometimes|string|in:free,subscription,pay_per_use',
                'description' => 'nullable|string',
                'price' => 'sometimes|numeric|min:0',
                'billing_cycle' => 'nullable|string|in:monthly,yearly',
                'features' => 'nullable|array',
                'limits' => 'nullable|array',
                'is_active' => 'boolean',
                'is_default' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // If setting as default, unset other defaults of same type
            if ($request->is_default) {
                PaymentPlan::where('type', $request->type ?? $plan->type)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $plan->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Payment plan updated successfully',
                'data' => $plan
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment plan',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating payment plan'
            ], 500);
        }
    }

    /**
     * Delete a payment plan
     */
    public function deletePlan(Request $request, $id): JsonResponse
    {
        try {
            $plan = PaymentPlan::findOrFail($id);

            // Check if plan has active subscriptions
            $activeSubscriptions = UserSubscription::where('payment_plan_id', $id)
                ->where('status', 'active')
                ->count();

            if ($activeSubscriptions > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete plan with active subscriptions'
                ], 400);
            }

            $plan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment plan deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment plan',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting payment plan'
            ], 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStatistics(Request $request): JsonResponse
    {
        try {
            $stats = [
                'total_plans' => PaymentPlan::count(),
                'active_plans' => PaymentPlan::where('is_active', true)->count(),
                'total_subscriptions' => UserSubscription::count(),
                'active_subscriptions' => UserSubscription::where('status', 'active')->count(),
                'total_transactions' => PaymentTransaction::count(),
                'completed_transactions' => PaymentTransaction::where('status', 'completed')->count(),
                'total_revenue' => PaymentTransaction::where('status', 'completed')->sum('amount'),
                'monthly_revenue' => PaymentTransaction::where('status', 'completed')
                    ->where('created_at', '>=', now()->subMonth())
                    ->sum('amount'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Payment statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving statistics'
            ], 500);
        }
    }

    /**
     * Get all user subscriptions
     */
    public function getUserSubscriptions(Request $request): JsonResponse
    {
        try {
            $subscriptions = UserSubscription::with(['user', 'paymentPlan'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'User subscriptions retrieved successfully',
                'data' => $subscriptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user subscriptions',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving subscriptions'
            ], 500);
        }
    }

    /**
     * Get all payment transactions
     */
    public function getTransactions(Request $request): JsonResponse
    {
        try {
            $query = PaymentTransaction::with(['user', 'paymentPlan']);

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by payment method
            if ($request->has('payment_method') && $request->payment_method !== 'all') {
                $query->where('payment_method', $request->payment_method);
            }

            // Filter by payment type
            if ($request->has('payment_type') && $request->payment_type !== 'all') {
                $query->where('transaction_type', $request->payment_type);
            }

            // Filter by user ID
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            // Filter by amount range
            if ($request->has('min_amount')) {
                $query->where('amount', '>=', $request->min_amount);
            }
            if ($request->has('max_amount')) {
                $query->where('amount', '<=', $request->max_amount);
            }

            // Search by transaction ID, reference, or user name
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                      ->orWhere('gateway_reference', 'like', "%{$search}%")
                      ->orWhere('payment_reference', 'like', "%{$search}%")
                      ->orWhereHas('user', function($q2) use ($search) {
                          $q2->where('full_name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%")
                             ->orWhere('phone', 'like', "%{$search}%");
                      });
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Calculate statistics for current filtered results BEFORE pagination
            $statsQuery = clone $query;
            $stats = [
                'total_count' => $statsQuery->count(),
                'total_amount' => (clone $statsQuery)->sum('amount'),
                'completed_count' => (clone $statsQuery)->where('status', 'completed')->count(),
                'pending_count' => (clone $statsQuery)->where('status', 'pending')->count(),
                'failed_count' => (clone $statsQuery)->where('status', 'failed')->count(),
                'completed_amount' => (clone $statsQuery)->where('status', 'completed')->sum('amount'),
            ];

            // Pagination
            $perPage = $request->get('per_page', 20);
            $transactions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Payment transactions retrieved successfully',
                'data' => $transactions,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment transactions',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving transactions'
            ], 500);
        }
    }

    /**
     * Get payment statistics (enhanced with detailed breakdown)
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            // Date range filters
            $startDate = $request->get('start_date', now()->subMonth());
            $endDate = $request->get('end_date', now());

            // Overall statistics
            $totalRevenue = PaymentTransaction::where('status', 'completed')->sum('amount');
            $totalTransactions = PaymentTransaction::count();
            $activeSubscriptions = UserSubscription::where('status', 'active')->count();

            // Status breakdown
            $statusStats = PaymentTransaction::select('status', \DB::raw('count(*) as count'), \DB::raw('sum(amount) as total'))
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            // Payment method breakdown
            $methodStats = PaymentTransaction::select('payment_method', \DB::raw('count(*) as count'), \DB::raw('sum(amount) as total'))
                ->whereNotNull('payment_method')
                ->groupBy('payment_method')
                ->get();

            // Payment type breakdown
            $typeStats = PaymentTransaction::select('transaction_type', \DB::raw('count(*) as count'), \DB::raw('sum(amount) as total'))
                ->groupBy('transaction_type')
                ->get();

            // Recent trends (last 7 days)
            $trends = PaymentTransaction::where('created_at', '>=', now()->subDays(7))
                ->select(\DB::raw('DATE(created_at) as date'), \DB::raw('count(*) as count'), \DB::raw('sum(amount) as total'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            // Success rate
            $completedCount = PaymentTransaction::where('status', 'completed')->count();
            $failedCount = PaymentTransaction::where('status', 'failed')->count();
            $totalAttempts = $completedCount + $failedCount;
            $successRate = $totalAttempts > 0 ? ($completedCount / $totalAttempts) * 100 : 0;

            // Top users by transaction count
            $topUsers = PaymentTransaction::select('user_id', \DB::raw('count(*) as transaction_count'), \DB::raw('sum(amount) as total_spent'))
                ->with('user:id,name,email')
                ->groupBy('user_id')
                ->orderBy('total_spent', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Payment statistics retrieved successfully',
                'data' => [
                    'overview' => [
                        'total_revenue' => $totalRevenue,
                        'total_transactions' => $totalTransactions,
                        'active_subscriptions' => $activeSubscriptions,
                        'success_rate' => round($successRate, 2),
                    ],
                    'by_status' => [
                        'pending' => $statusStats->get('pending', ['count' => 0, 'total' => 0]),
                        'processing' => $statusStats->get('processing', ['count' => 0, 'total' => 0]),
                        'completed' => $statusStats->get('completed', ['count' => 0, 'total' => 0]),
                        'failed' => $statusStats->get('failed', ['count' => 0, 'total' => 0]),
                        'cancelled' => $statusStats->get('cancelled', ['count' => 0, 'total' => 0]),
                        'refunded' => $statusStats->get('refunded', ['count' => 0, 'total' => 0]),
                    ],
                    'by_method' => $methodStats,
                    'by_type' => $typeStats,
                    'trends' => $trends,
                    'top_users' => $topUsers,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Export transactions to CSV
     */
    public function exportTransactions(Request $request)
    {
        try {
            $query = PaymentTransaction::with(['user', 'paymentPlan']);

            // Apply same filters as getTransactions
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            $transactions = $query->get();

            $filename = 'transactions_' . now()->format('Y-m-d_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($transactions) {
                $file = fopen('php://output', 'w');
                
                // Headers
                fputcsv($file, [
                    'Transaction ID',
                    'User Name',
                    'User Email',
                    'Amount',
                    'Currency',
                    'Payment Method',
                    'Transaction Type',
                    'Status',
                    'Gateway Reference',
                    'Created At',
                    'Completed At',
                ]);

                // Data
                foreach ($transactions as $transaction) {
                    fputcsv($file, [
                        $transaction->transaction_id ?? $transaction->id,
                        $transaction->user->name ?? 'N/A',
                        $transaction->user->email ?? 'N/A',
                        $transaction->amount,
                        $transaction->currency,
                        $transaction->payment_method ?? 'N/A',
                        $transaction->transaction_type,
                        $transaction->status,
                        $transaction->gateway_reference ?? 'N/A',
                        $transaction->created_at->format('Y-m-d H:i:s'),
                        $transaction->paid_at ? $transaction->paid_at->format('Y-m-d H:i:s') : 'N/A',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export transactions',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get single transaction details
     */
    public function getTransactionDetails(Request $request, $id): JsonResponse
    {
        try {
            $transaction = PaymentTransaction::with(['user', 'paymentPlan'])
                ->findOrFail($id);

            // Additional details
            $relatedTransactions = PaymentTransaction::where('user_id', $transaction->user_id)
                ->where('id', '!=', $transaction->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Transaction details retrieved successfully',
                'data' => [
                    'transaction' => $transaction,
                    'related_transactions' => $relatedTransactions,
                    'user_stats' => [
                        'total_transactions' => PaymentTransaction::where('user_id', $transaction->user_id)->count(),
                        'total_spent' => PaymentTransaction::where('user_id', $transaction->user_id)
                            ->where('status', 'completed')
                            ->sum('amount'),
                        'pending_transactions' => PaymentTransaction::where('user_id', $transaction->user_id)
                            ->where('status', 'pending')
                            ->count(),
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving transaction details'
            ], 404);
        }
    }

    /**
     * Toggle payment plan status
     */
    public function togglePlanStatus(Request $request, $id): JsonResponse
    {
        try {
            $plan = PaymentPlan::findOrFail($id);
            $plan->is_active = !$plan->is_active;
            $plan->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment plan status updated successfully',
                'data' => $plan
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment plan status',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating status'
            ], 500);
        }
    }
}
