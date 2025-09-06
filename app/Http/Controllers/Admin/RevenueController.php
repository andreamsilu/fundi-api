<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RevenueTracking;
use App\Services\MonetizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RevenueController extends Controller
{
    protected MonetizationService $monetizationService;

    public function __construct(MonetizationService $monetizationService)
    {
        $this->monetizationService = $monetizationService;
    }

    /**
     * Get revenue statistics for admin dashboard.
     */
    public function getRevenueStats(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:day,week,month,year',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date'
        ]);

        $period = $request->get('period', 'month');
        $stats = $this->monetizationService->getRevenueStats($period);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get revenue breakdown by business model.
     */
    public function getRevenueByBusinessModel(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'business_model' => 'sometimes|in:c2c,b2c,c2b,b2b'
        ]);

        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $query = RevenueTracking::whereBetween('revenue_date', [$startDate, $endDate]);

        if ($request->has('business_model')) {
            $query->where('business_model', $request->business_model);
        }

        $revenue = $query->selectRaw('
                business_model,
                revenue_type,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            ')
            ->groupBy('business_model', 'revenue_type')
            ->orderBy('business_model')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'revenue_breakdown' => $revenue,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    /**
     * Get revenue by user (fundi or customer).
     */
    public function getRevenueByUser(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date'
        ]);

        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $revenue = RevenueTracking::where('user_id', $request->user_id)
            ->whereBetween('revenue_date', [$startDate, $endDate])
            ->with(['user', 'job'])
            ->orderBy('revenue_date', 'desc')
            ->get();

        $totalRevenue = $revenue->sum('amount');
        $revenueByType = $revenue->groupBy('revenue_type')->map(function ($items) {
            return [
                'total_amount' => $items->sum('amount'),
                'transaction_count' => $items->count()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'user_revenue' => $revenue,
                'total_revenue' => $totalRevenue,
                'revenue_by_type' => $revenueByType,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    /**
     * Get top revenue generating users.
     */
    public function getTopRevenueUsers(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date'
        ]);

        $limit = $request->get('limit', 10);
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $topUsers = RevenueTracking::whereBetween('revenue_date', [$startDate, $endDate])
            ->selectRaw('
                user_id,
                SUM(amount) as total_revenue,
                COUNT(*) as transaction_count,
                COUNT(DISTINCT revenue_type) as revenue_types
            ')
            ->with('user:id,name,phone,role')
            ->groupBy('user_id')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'top_users' => $topUsers,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    /**
     * Get revenue trends over time.
     */
    public function getRevenueTrends(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:day,week,month',
            'months' => 'sometimes|integer|min:1|max:24'
        ]);

        $period = $request->get('period', 'day');
        $months = $request->get('months', 6);

        $startDate = now()->subMonths($months);

        $trends = RevenueTracking::where('revenue_date', '>=', $startDate)
            ->selectRaw('
                DATE_FORMAT(revenue_date, ?) as period,
                revenue_type,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            ', [$this->getDateFormat($period)])
            ->groupBy('period', 'revenue_type')
            ->orderBy('period')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'trends' => $trends,
                'period' => $period,
                'months' => $months
            ]
        ]);
    }

    /**
     * Get detailed revenue report.
     */
    public function getDetailedReport(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'revenue_type' => 'sometimes|in:subscription,credits,job_boost,application_fee',
            'business_model' => 'sometimes|in:c2c,b2c,c2b,b2b',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $query = RevenueTracking::whereBetween('revenue_date', [
            $request->start_date,
            $request->end_date
        ])
        ->with(['user', 'job', 'payment', 'creditTransaction', 'subscription', 'booster']);

        if ($request->has('revenue_type')) {
            $query->where('revenue_type', $request->revenue_type);
        }

        if ($request->has('business_model')) {
            $query->where('business_model', $request->business_model);
        }

        $perPage = $request->get('per_page', 50);
        $revenue = $query->orderBy('revenue_date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $revenue
        ]);
    }

    /**
     * Get the date format for grouping.
     */
    private function getDateFormat(string $period): string
    {
        return match($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };
    }
}
