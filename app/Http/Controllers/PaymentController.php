<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Job;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Initialize a payment for a booking or job
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initialize(Request $request): JsonResponse
    {
        $request->validate([
            'payable_type' => 'required|in:booking,job',
            'payable_id' => 'required|integer',
            'payment_method' => 'required|in:mobile_money',
            'phone' => 'required|string',
            'currency' => 'required|string|size:3',
        ]);

        try {
            // Get the payable model
            $payable = $this->getPayableModel($request->payable_type, $request->payable_id);

            if (!$payable) {
                return response()->json(['message' => 'Invalid payable model'], 404);
            }

            // Create payment record
            $payment = $this->paymentService->createPayment(
                Auth::user(),
                $payable->amount,
                $request->currency,
                $request->payment_method,
                $payable
            );

            // Initiate mobile money payment
            $init = $this->paymentService->initiateMobileMoney($payment, $request->string('phone'));

            return response()->json([
                'message' => 'Payment initialized successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payment_reference' => $init['payment_reference'],
                    'status' => $init['status'],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initialize payment: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Failed to initialize payment'], 500);
        }
    }

    /**
     * Handle Stripe webhook
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        try {
            $this->paymentService->handleMobileMoneyCallback($payload);
            return response()->json(['message' => 'Webhook handled successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to handle webhook: ' . $e->getMessage(), [
                'payload' => $payload,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Webhook handling failed'], 500);
        }
    }

    /**
     * Get payment history for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => 'nullable|in:pending,completed,failed',
            'payment_method' => 'nullable|in:mobile_money',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $payments = $this->paymentService->getUserPaymentHistory(Auth::user(), $filters);

        return response()->json([
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ]
        ]);
    }

    /**
     * Get payment details
     *
     * @param string $paymentId
     * @return JsonResponse
     */
    public function show(string $paymentId): JsonResponse
    {
        $payment = Auth::user()->payments()
            ->with('payable')
            ->findOrFail($paymentId);

        return response()->json([
            'data' => $payment
        ]);
    }

    /**
     * Get the payable model based on type and ID
     *
     * @param string $type
     * @param int $id
     * @return Booking|Job|null
     */
    protected function getPayableModel(string $type, int $id)
    {
        return match ($type) {
            'booking' => Booking::find($id),
            'job' => Job::find($id),
            default => null,
        };
    }
} 