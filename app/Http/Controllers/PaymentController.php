<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PaymentPlan;
use App\Models\UserSubscription;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get user's current payment plan and subscription status
     */
    public function getCurrentPlan(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $plan = $this->paymentService->getUserPaymentPlan($user);
            $subscription = $this->paymentService->getUserActiveSubscription($user);

            return response()->json([
                'success' => true,
                'message' => 'Payment plan retrieved successfully',
                'data' => [
                    'plan' => $plan,
                    'subscription' => $subscription,
                    'is_active' => $subscription ? $subscription->isActive() : false,
                    'days_remaining' => $subscription ? $subscription->getDaysRemaining() : null,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment plan',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving payment plan'
            ], 500);
        }
    }

    /**
     * Get all available payment plans
     */
    public function getAvailablePlans(Request $request): JsonResponse
    {
        try {
            $plans = $this->paymentService->getAvailablePlans();

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
     * Subscribe to a payment plan
     */
    public function subscribe(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|integer|exists:payment_plans,id',
                'duration_days' => 'sometimes|integer|min:1|max:365',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $plan = PaymentPlan::findOrFail($request->plan_id);

            // Check if user can subscribe to this plan
            if (!$this->paymentService->canPerformAction($user, 'subscribe')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot subscribe to this plan at this time'
                ], 403);
            }

            // Create subscription
            $durationDays = $request->duration_days ?? 30;
            $subscription = $this->paymentService->createSubscription($user, $plan, $durationDays);

            // Create payment transaction
            $transaction = $this->paymentService->createTransaction(
                $user,
                $plan,
                'subscription',
                $plan->price,
                null,
                "Subscription to {$plan->name} for {$durationDays} days"
            );

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully',
                'data' => [
                    'subscription' => $subscription,
                    'transaction' => $transaction,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while creating subscription'
            ], 500);
        }
    }

    /**
     * Cancel user's subscription
     */
    public function cancelSubscription(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $subscription = $this->paymentService->getUserActiveSubscription($user);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found'
                ], 404);
            }

            $subscription->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully',
                'data' => $subscription
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while cancelling subscription'
            ], 500);
        }
    }

    /**
     * Get user's payment history
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $limit = $request->get('limit', 50);
            $history = $this->paymentService->getUserPaymentHistory($user, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Payment history retrieved successfully',
                'data' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment history',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving payment history'
            ], 500);
        }
    }

    /**
     * Check if user can perform specific action
     */
    public function checkActionPermission(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|string|in:post_job,apply_job,browse_fundis,message_fundi',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $action = $request->action;
            $canPerform = $this->paymentService->canPerformAction($user, $action);

            return response()->json([
                'success' => true,
                'message' => 'Action permission checked successfully',
                'data' => [
                    'action' => $action,
                    'can_perform' => $canPerform,
                    'plan' => $this->paymentService->getUserPaymentPlan($user),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check action permission',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while checking permission'
            ], 500);
        }
    }

    /**
     * Process pay-per-use payment
     */
    public function processPayPerUse(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|string|in:post_job,apply_job',
                'reference_id' => 'sometimes|string',
                'amount' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $plan = $this->paymentService->getUserPaymentPlan($user);

            if (!$plan->isPayPerUse()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pay-per-use is not available for your current plan'
                ], 403);
            }

            // Create payment transaction
            $transaction = $this->paymentService->createTransaction(
                $user,
                $plan,
                $request->action,
                $request->amount,
                $request->reference_id,
                "Pay-per-use payment for {$request->action}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Pay-per-use transaction created successfully',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process pay-per-use payment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing payment'
            ], 500);
        }
    }
}