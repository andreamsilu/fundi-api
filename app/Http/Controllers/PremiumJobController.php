<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\PremiumJobBooster;
use App\Services\MonetizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PremiumJobController extends Controller
{
    protected MonetizationService $monetizationService;

    public function __construct(MonetizationService $monetizationService)
    {
        $this->monetizationService = $monetizationService;
    }

    /**
     * Boost a job to premium status.
     */
    public function boostJob(Request $request, Job $job): JsonResponse
    {
        $request->validate([
            'boost_type' => ['required', Rule::in(['featured', 'urgent', 'premium'])],
            'duration_days' => 'sometimes|integer|min:1|max:90'
        ]);

        $customer = Auth::user();
        
        // Check if user owns the job
        if ($job->user_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only boost your own jobs'
            ], 403);
        }

        // Check if job is still open
        if ($job->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Only open jobs can be boosted'
            ], 400);
        }

        // Check if job is already boosted
        $existingBooster = PremiumJobBooster::where('job_id', $job->id)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>', now())
            ->first();

        if ($existingBooster) {
            return response()->json([
                'success' => false,
                'message' => 'This job is already boosted'
            ], 400);
        }

        $result = $this->monetizationService->processJobBoost(
            $customer,
            $job,
            $request->boost_type
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get boost fee for a job.
     */
    public function getBoostFee(Request $request, Job $job): JsonResponse
    {
        $request->validate([
            'boost_type' => ['required', Rule::in(['featured', 'urgent', 'premium'])]
        ]);

        $boostFee = $this->monetizationService->calculateBoostFee(
            $job->business_model,
            $request->boost_type
        );

        return response()->json([
            'success' => true,
            'data' => [
                'boost_fee' => $boostFee,
                'business_model' => $job->business_model,
                'boost_type' => $request->boost_type,
                'currency' => 'TZS'
            ]
        ]);
    }

    /**
     * Get customer's boosted jobs.
     */
    public function getBoostedJobs(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'sometimes|in:active,expired,cancelled',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $customer = Auth::user();

        $query = PremiumJobBooster::where('user_id', $customer->id)
            ->with(['job', 'payment'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->get('per_page', 15);
        $boosters = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $boosters
        ]);
    }

    /**
     * Cancel a job boost.
     */
    public function cancelBoost(PremiumJobBooster $booster): JsonResponse
    {
        $customer = Auth::user();

        // Check if user owns the booster
        if ($booster->user_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only cancel your own job boosts'
            ], 403);
        }

        // Check if booster is active
        if ($booster->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This boost is not active'
            ], 400);
        }

        $booster->update(['status' => 'cancelled']);

        // Update job featured status if no other active boosters
        $activeBoosters = PremiumJobBooster::where('job_id', $booster->job_id)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>', now())
            ->count();

        if ($activeBoosters === 0) {
            $booster->job->update(['is_featured' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job boost cancelled successfully'
        ]);
    }

    /**
     * Get boost statistics for customer.
     */
    public function getBoostStats(): JsonResponse
    {
        $customer = Auth::user();

        $stats = [
            'total_boosts' => PremiumJobBooster::where('user_id', $customer->id)->count(),
            'active_boosts' => PremiumJobBooster::where('user_id', $customer->id)
                ->where('status', 'active')
                ->where('starts_at', '<=', now())
                ->where('expires_at', '>', now())
                ->count(),
            'expired_boosts' => PremiumJobBooster::where('user_id', $customer->id)
                ->where('status', 'expired')
                ->count(),
            'total_spent' => PremiumJobBooster::where('user_id', $customer->id)->sum('boost_fee'),
            'c2c_boosts' => PremiumJobBooster::where('user_id', $customer->id)
                ->where('business_model', 'c2c')
                ->count(),
            'b2c_boosts' => PremiumJobBooster::where('user_id', $customer->id)
                ->where('business_model', 'b2c')
                ->count(),
            'b2b_boosts' => PremiumJobBooster::where('user_id', $customer->id)
                ->where('business_model', 'b2b')
                ->count(),
            'c2b_boosts' => PremiumJobBooster::where('user_id', $customer->id)
                ->where('business_model', 'c2b')
                ->count(),
        ];

        // Monthly boost spending for the last 6 months
        $monthlySpending = PremiumJobBooster::where('user_id', $customer->id)
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(boost_fee) as total_spent, COUNT(*) as boost_count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $stats['monthly_spending'] = $monthlySpending;

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
