<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Job;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
     * Get all payments with pagination and filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPayments(Request $request): JsonResponse
    {
        try {
            $query = Payment::with(['user', 'payable'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      })
                      ->orWhere('payment_method', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 15);
            $payments = $query->paginate($perPage);

            return response()->json([
                'data' => $payments->items(),
                'meta' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching payments: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch payments'], 500);
        }
    }

    /**
     * Get payment by ID
     *
     * @param int $paymentId
     * @return JsonResponse
     */
    public function getPayment(int $paymentId): JsonResponse
    {
        try {
            $payment = Payment::with(['user', 'payable'])->findOrFail($paymentId);

            return response()->json(['data' => $payment]);
        } catch (\Exception $e) {
            Log::error('Error fetching payment: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch payment'], 500);
        }
    }

    /**
     * Get payment history for a specific user
     *
     * @param int $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserPaymentHistory(int $userId, Request $request): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            $query = Payment::where('user_id', $userId)
                ->with(['user', 'payable'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            $perPage = $request->get('per_page', 15);
            $payments = $query->paginate($perPage);

            return response()->json([
                'data' => $payments->items(),
                'meta' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user payment history: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch user payment history'], 500);
        }
    }

    /**
     * Get payment statistics
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_payments' => Payment::count(),
                'total_amount' => Payment::where('status', 'completed')->sum('amount'),
                'pending_payments' => Payment::where('status', 'pending')->count(),
                'completed_payments' => Payment::where('status', 'completed')->count(),
                'failed_payments' => Payment::where('status', 'failed')->count(),
                'payments_by_method' => Payment::select('payment_method', DB::raw('count(*) as count'))
                    ->groupBy('payment_method')
                    ->pluck('count', 'payment_method')
                    ->toArray(),
                'payments_by_status' => Payment::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'monthly_revenue' => Payment::select(
                        DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                        DB::raw('SUM(amount) as amount'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->where('status', 'completed')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
                    ->toArray(),
                'top_payment_methods' => Payment::select(
                        'payment_method',
                        DB::raw('COUNT(*) as count'),
                        DB::raw('SUM(amount) as total_amount')
                    )
                    ->groupBy('payment_method')
                    ->orderBy('count', 'desc')
                    ->get()
                    ->toArray(),
            ];

            return response()->json(['data' => $stats]);
        } catch (\Exception $e) {
            Log::error('Error fetching payment stats: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch payment stats'], 500);
        }
    }

    /**
     * Get payment analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '30d');
            $groupBy = $request->get('group_by', 'day');

            // Calculate analytics based on period
            $totalRevenue = Payment::where('status', 'completed')->sum('amount');
            $totalPayments = Payment::count();
            $completedPayments = Payment::where('status', 'completed')->count();
            $failedPayments = Payment::where('status', 'failed')->count();
            $refundedPayments = Payment::where('status', 'refunded')->count();

            $analytics = [
                'total_revenue' => $totalRevenue,
                'average_transaction_value' => $completedPayments > 0 ? $totalRevenue / $completedPayments : 0,
                'success_rate' => $totalPayments > 0 ? ($completedPayments / $totalPayments) * 100 : 0,
                'failure_rate' => $totalPayments > 0 ? ($failedPayments / $totalPayments) * 100 : 0,
                'refund_rate' => $completedPayments > 0 ? ($refundedPayments / $completedPayments) * 100 : 0,
                'monthly_growth' => 15.5, // Mock value - would calculate based on previous period
                'top_users' => Payment::select(
                        'user_id',
                        DB::raw('COUNT(*) as total_payments'),
                        DB::raw('SUM(amount) as total_amount')
                    )
                    ->groupBy('user_id')
                    ->orderBy('total_amount', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($payment) {
                        $user = User::find($payment->user_id);
                        return [
                            'user_id' => $payment->user_id,
                            'user_name' => $user->name ?? 'Unknown User',
                            'total_payments' => $payment->total_payments,
                            'total_amount' => $payment->total_amount,
                        ];
                    })
                    ->toArray(),
            ];

            return response()->json(['data' => $analytics]);
        } catch (\Exception $e) {
            Log::error('Error fetching payment analytics: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch payment analytics'], 500);
        }
    }

    /**
     * Update payment status
     *
     * @param int $paymentId
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePaymentStatus(int $paymentId, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,completed,failed,cancelled,refunded',
                'reason' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $payment = Payment::findOrFail($paymentId);
            $payment->update([
                'status' => $request->status,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'status_change_reason' => $request->reason,
                    'status_changed_at' => now()->toISOString(),
                    'status_changed_by' => Auth::id(),
                ]),
            ]);

            return response()->json([
                'message' => 'Payment status updated successfully',
                'data' => $payment,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating payment status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update payment status'], 500);
        }
    }

    /**
     * Process refund for a payment
     *
     * @param int $paymentId
     * @param Request $request
     * @return JsonResponse
     */
    public function processRefund(int $paymentId, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'nullable|numeric|min:0.01',
                'reason' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $payment = Payment::findOrFail($paymentId);

            if ($payment->status !== 'completed') {
                return response()->json(['error' => 'Only completed payments can be refunded'], 400);
            }

            $refundAmount = $request->amount ?? $payment->amount;

            if ($refundAmount > $payment->amount) {
                return response()->json(['error' => 'Refund amount cannot exceed payment amount'], 400);
            }

            $payment->update([
                'status' => 'refunded',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'refund_amount' => $refundAmount,
                    'refund_reason' => $request->reason,
                    'refunded_at' => now()->toISOString(),
                    'refunded_by' => Auth::id(),
                ]),
            ]);

            return response()->json([
                'message' => 'Refund processed successfully',
                'data' => $payment,
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing refund: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process refund'], 500);
        }
    }

    /**
     * Retry failed payment
     *
     * @param int $paymentId
     * @return JsonResponse
     */
    public function retryPayment(int $paymentId): JsonResponse
    {
        try {
            $payment = Payment::findOrFail($paymentId);

            if ($payment->status !== 'failed') {
                return response()->json(['error' => 'Only failed payments can be retried'], 400);
            }

            $payment->update([
                'status' => 'pending',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'retry_attempted_at' => now()->toISOString(),
                    'retry_attempted_by' => Auth::id(),
                ]),
            ]);

            return response()->json([
                'message' => 'Payment retry initiated successfully',
                'data' => $payment,
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrying payment: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retry payment'], 500);
        }
    }

    /**
     * Export payments data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportPayments(Request $request): JsonResponse
    {
        try {
            // In a real implementation, you would generate and return the export file
            return response()->json(['message' => 'Payment export functionality would be implemented here']);
        } catch (\Exception $e) {
            Log::error('Error exporting payments: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to export payments'], 500);
        }
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