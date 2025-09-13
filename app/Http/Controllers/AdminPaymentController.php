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
    public function getPaymentPlans(Request $request): JsonResponse
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
    public function createPaymentPlan(Request $request): JsonResponse
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
     * Update a payment plan
     */
    public function updatePaymentPlan(Request $request, $id): JsonResponse
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
    public function deletePaymentPlan(Request $request, $id): JsonResponse
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
    public function getPaymentTransactions(Request $request): JsonResponse
    {
        try {
            $transactions = PaymentTransaction::with(['user', 'paymentPlan'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Payment transactions retrieved successfully',
                'data' => $transactions
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
