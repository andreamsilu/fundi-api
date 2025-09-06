<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\FundiCredits;
use App\Services\MonetizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CreditController extends Controller
{
    protected MonetizationService $monetizationService;

    public function __construct(MonetizationService $monetizationService)
    {
        $this->monetizationService = $monetizationService;
    }

    /**
     * Get fundi's credit balance.
     */
    public function getBalance(): JsonResponse
    {
        $fundi = Auth::user();
        
        if ($fundi->role !== 'fundi') {
            return response()->json([
                'success' => false,
                'message' => 'Only fundis can have credits'
            ], 403);
        }

        $credits = $this->monetizationService->getFundiCredits($fundi);

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $credits->balance,
                'total_purchased' => $credits->total_purchased,
                'total_used' => $credits->total_used,
                'available_balance' => $credits->balance
            ]
        ]);
    }

    /**
     * Purchase credits.
     */
    public function purchaseCredits(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000|max:100000',
            'payment_method' => ['required', Rule::in(['mobile_money', 'bank_transfer'])]
        ]);

        $fundi = Auth::user();
        
        if ($fundi->role !== 'fundi') {
            return response()->json([
                'success' => false,
                'message' => 'Only fundis can purchase credits'
            ], 403);
        }

        $result = $this->monetizationService->purchaseCredits(
            $fundi,
            $request->amount,
            $request->payment_method
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get credit transaction history.
     */
    public function getTransactionHistory(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['sometimes', Rule::in(['purchase', 'usage', 'refund', 'bonus'])],
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $fundi = Auth::user();
        
        if ($fundi->role !== 'fundi') {
            return response()->json([
                'success' => false,
                'message' => 'Only fundis can view credit history'
            ], 403);
        }

        $query = CreditTransaction::where('user_id', $fundi->id)
            ->with(['job', 'payment'])
            ->orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Get credit usage statistics.
     */
    public function getUsageStats(): JsonResponse
    {
        $fundi = Auth::user();
        
        if ($fundi->role !== 'fundi') {
            return response()->json([
                'success' => false,
                'message' => 'Only fundis can view credit statistics'
            ], 403);
        }

        $credits = $this->monetizationService->getFundiCredits($fundi);
        
        $stats = [
            'current_balance' => $credits->balance,
            'total_purchased' => $credits->total_purchased,
            'total_used' => $credits->total_used,
            'purchase_count' => $credits->creditTransactions()->purchases()->count(),
            'usage_count' => $credits->creditTransactions()->usage()->count(),
            'refund_count' => $credits->creditTransactions()->refunds()->count(),
        ];

        // Monthly usage for the last 6 months
        $monthlyUsage = CreditTransaction::where('user_id', $fundi->id)
            ->where('type', 'usage')
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total_amount, COUNT(*) as transaction_count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $stats['monthly_usage'] = $monthlyUsage;

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
