<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentValidationService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Get user payments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $payments = Payment::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Payments retrieved successfully',
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
     * Create payment
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0',
                'payment_type' => 'required|in:subscription,application_fee,job_posting',
                'pesapal_reference' => 'required|string|max:100|unique:payments',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $payment = Payment::create([
                'user_id' => $request->user()->id,
                'amount' => $request->amount,
                'payment_type' => $request->payment_type,
                'pesapal_reference' => $request->pesapal_reference,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully',
                'data' => $payment
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while creating payment'
            ], 500);
        }
    }

    /**
     * Get payment requirements for user
     */
    public function getRequirements(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $requirements = PaymentValidationService::getPaymentRequirements($user);

            return response()->json([
                'success' => true,
                'message' => 'Payment requirements retrieved successfully',
                'data' => $requirements
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment requirements',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving payment requirements'
            ], 500);
        }
    }

    /**
     * Check if user needs payment for specific action
     */
    public function checkPaymentRequired(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|in:post_job,apply_job',
                'job_id' => 'required_if:action,apply_job|exists:jobs,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $context = [];
            
            if ($request->action === 'apply_job' && $request->job_id) {
                $context['job'] = \App\Models\Job::find($request->job_id);
            }

            $validation = PaymentValidationService::validatePayment($user, $request->action, $context);

            return response()->json([
                'success' => true,
                'message' => 'Payment validation completed',
                'data' => $validation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate payment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while validating payment'
            ], 500);
        }
    }
}
