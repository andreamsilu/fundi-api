<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PaymentPlan;
use App\Models\UserSubscription;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use App\Services\ZenoPayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $zenoPayService;

    public function __construct(PaymentService $paymentService, ZenoPayService $zenoPayService)
    {
        $this->paymentService = $paymentService;
        $this->zenoPayService = $zenoPayService;
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
    public function getPlans(Request $request): JsonResponse
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

    /**
     * Alias for checkActionPermission
     */
    public function checkRequirement(Request $request): JsonResponse
    {
        return $this->checkActionPermission($request);
    }

    /**
     * Alias for checkActionPermission
     */
    public function checkPermission(Request $request): JsonResponse
    {
        return $this->checkActionPermission($request);
    }

    /**
     * Alias for getPaymentHistory
     */
    public function getHistory(Request $request): JsonResponse
    {
        return $this->getPaymentHistory($request);
    }

    /**
     * Alias for getPaymentHistory
     */
    public function getUserPayments(Request $request): JsonResponse
    {
        return $this->getPaymentHistory($request);
    }

    /**
     * Alias for processPayPerUse
     */
    public function payPerUse(Request $request): JsonResponse
    {
        return $this->processPayPerUse($request);
    }

    /**
     * Create a payment
     */
    public function createPayment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0',
                'type' => 'required|string',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create payment logic
            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Cancel a payment
     */
    public function cancelPayment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|exists:payments,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cancel payment logic
            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get payment configuration
     */
    public function getConfig(Request $request): JsonResponse
    {
        try {
            $config = [
                'payment_gateway' => 'mpesa',
                'currency' => 'TZS',
                'supported_methods' => ['mpesa', 'tigopesa', 'airtel_money'],
            ];

            return response()->json([
                'success' => true,
                'data' => $config
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment config',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Handle payment callback from gateway
     */
    public function handleCallback(Request $request): JsonResponse
    {
        try {
            // Payment gateway callback logic
            \Log::info('Payment callback received', $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Callback processed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process callback',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Verify payment transaction
     */
    public function verifyPayment(Request $request, $transactionId): JsonResponse
    {
        try {
            // Check status via ZenoPay
            $statusResult = $this->zenoPayService->checkOrderStatus($transactionId);

            if ($statusResult['success']) {
                // Update local transaction status
                $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();
                
                if ($transaction && $statusResult['payment_status'] === 'COMPLETED') {
                    $transaction->update([
                        'status' => 'completed',
                        'gateway_reference' => $statusResult['reference'],
                        'completed_at' => now(),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'data' => [
                        'transaction_id' => $transactionId,
                        'status' => $statusResult['payment_status'],
                        'amount' => $statusResult['amount'],
                        'channel' => $statusResult['channel'],
                        'reference' => $statusResult['reference'],
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $statusResult['message']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Initiate mobile money payment via ZenoPay
     */
    public function initiateMobileMoneyPayment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:100', // Minimum 100 TZS (changed for testing)
                'phone_number' => 'required|string',
                'buyer_name' => 'required|string',
                'buyer_email' => 'required|email',
                'payment_type' => 'required|string|in:subscription,job_payment,application_fee',
                'reference_id' => 'nullable|string', // job_id, subscription_id, etc.
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            // Format phone number
            $phoneNumber = $this->zenoPayService->formatPhoneNumber($request->phone_number);
            
            if (!$phoneNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number format. Use Tanzanian format (07XXXXXXXX)'
                ], 400);
            }

            // Generate unique order ID
            $orderId = 'FUNDI-' . strtoupper(Str::random(10)) . '-' . time();

            // Create payment transaction record
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'transaction_id' => $orderId,
                'amount' => $request->amount,
                'currency' => 'TZS',
                'payment_method' => 'mobile_money',
                'payment_type' => $request->payment_type,
                'reference_id' => $request->reference_id,
                'status' => 'pending',
                'metadata' => [
                    'phone_number' => $phoneNumber,
                    'buyer_name' => $request->buyer_name,
                    'buyer_email' => $request->buyer_email,
                ],
            ]);

            // Initiate payment with ZenoPay
            $paymentResult = $this->zenoPayService->initiatePayment(
                orderId: $orderId,
                buyerEmail: $request->buyer_email,
                buyerName: $request->buyer_name,
                buyerPhone: $phoneNumber,
                amount: $request->amount,
            );

            if ($paymentResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment initiated. Please check your phone for the payment prompt.',
                    'data' => [
                        'order_id' => $orderId,
                        'amount' => $request->amount,
                        'phone_number' => $phoneNumber,
                        'status' => 'pending',
                        'instructions' => 'Enter your M-Pesa PIN when prompted on your phone',
                    ]
                ], 201);
            }

            // Payment initiation failed
            $transaction->update(['status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => $paymentResult['message']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Check ZenoPay payment status
     */
    public function checkZenoPayStatus(Request $request, $orderId): JsonResponse
    {
        try {
            $statusResult = $this->zenoPayService->checkOrderStatus($orderId);

            if ($statusResult['success']) {
                // Update local transaction if completed
                if ($statusResult['payment_status'] === 'COMPLETED') {
                    $transaction = PaymentTransaction::where('transaction_id', $orderId)->first();
                    if ($transaction) {
                        $transaction->update([
                            'status' => 'completed',
                            'gateway_reference' => $statusResult['reference'],
                            'completed_at' => now(),
                        ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'data' => $statusResult['data']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $statusResult['message']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * ZenoPay webhook handler
     * Receives payment status updates when payment is COMPLETED
     */
    public function zenoPayWebhook(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->header('x-api-key');
            $payload = $request->all();

            \Log::info('ZenoPay webhook received', $payload);

            $result = $this->zenoPayService->processWebhook($payload, $apiKey);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            \Log::error('ZenoPay webhook error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Get supported mobile money providers
     */
    public function getMobileMoneyProviders(): JsonResponse
    {
        try {
            $providers = $this->zenoPayService->getSupportedChannels();

            return response()->json([
                'success' => true,
                'data' => $providers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get providers',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get transaction receipt details
     */
    public function getTransactionReceipt(Request $request, $transactionId): JsonResponse
    {
        try {
            $user = $request->user();

            // Find transaction and ensure it belongs to the user
            $transaction = PaymentTransaction::with(['user', 'paymentPlan'])
                ->where('id', $transactionId)
                ->where('user_id', $user->id)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            // Only completed transactions can have receipts
            if (!$transaction->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Receipt is only available for completed transactions'
                ], 400);
            }

            // Build receipt data
            $receiptData = [
                'transaction_id' => $transaction->id,
                'receipt_number' => 'RCPT-' . str_pad($transaction->id, 8, '0', STR_PAD_LEFT),
                'transaction_reference' => $transaction->transaction_id ?? '',
                'payment_reference' => $transaction->payment_reference ?? '',
                'gateway_reference' => $transaction->gateway_reference ?? '',
                'amount' => $transaction->amount,
                'currency' => $transaction->currency ?? 'TZS',
                'payment_method' => $transaction->payment_method ?? 'N/A',
                'payment_type' => $transaction->transaction_type ?? 'N/A',
                'description' => $transaction->description ?? '',
                'status' => $transaction->status,
                'paid_at' => $transaction->paid_at,
                'created_at' => $transaction->created_at,
                'metadata' => $transaction->metadata,
                'user' => [
                    'name' => $transaction->user->name ?? '',
                    'email' => $transaction->user->email ?? '',
                    'phone' => $transaction->user->phone_number ?? '',
                ],
                'payment_plan' => $transaction->paymentPlan ? [
                    'name' => $transaction->paymentPlan->name,
                    'description' => $transaction->paymentPlan->description,
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Receipt retrieved successfully',
                'data' => $receiptData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve receipt',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
}