<?php

namespace App\Http\Controllers;

use App\Models\Payment;
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
}
