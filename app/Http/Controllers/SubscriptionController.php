<?php

namespace App\Http\Controllers;

use App\Models\FundiSubscription;
use App\Models\SubscriptionTier;
use App\Services\MonetizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    protected MonetizationService $monetizationService;

    public function __construct(MonetizationService $monetizationService)
    {
        $this->monetizationService = $monetizationService;
    }

    /**
     * Get all available subscription tiers.
     */
    public function getTiers(): JsonResponse
    {
        $tiers = SubscriptionTier::active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tiers
        ]);
    }

    /**
     * Get fundi's current subscription.
     */
    public function getCurrentSubscription(): JsonResponse
    {
        $fundi = Auth::user();
        
        if ($fundi->role !== 'fundi') {
            return response()->json([
                'success' => false,
                'message' => 'Only fundis can have subscriptions'
            ], 403);
        }

        $subscription = $this->monetizationService->getActiveSubscription($fundi);
        
        if (!$subscription) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No active subscription found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => $subscription->load('subscriptionTier'),
                'remaining_applications' => $subscription->remaining_applications,
                'expires_at' => $subscription->expires_at,
                'is_active' => $subscription->isActive()
            ]
        ]);
    }

    /**
     * Subscribe to a subscription tier.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'tier_id' => 'required|exists:subscription_tiers,id'
        ]);

        $fundi = Auth::user();
        
        if ($fundi->role !== 'fundi') {
            return response()->json([
                'success' => false,
                'message' => 'Only fundis can subscribe'
            ], 403);
        }

        $tier = SubscriptionTier::findOrFail($request->tier_id);
        
        if (!$tier->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This subscription tier is not available'
            ], 400);
        }

        $result = $this->monetizationService->subscribeFundi($fundi, $tier);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Cancel current subscription.
     */
    public function cancelSubscription(): JsonResponse
    {
        $fundi = Auth::user();
        
        if ($fundi->role !== 'fundi') {
            return response()->json([
                'success' => false,
                'message' => 'Only fundis can cancel subscriptions'
            ], 403);
        }

        $subscription = $this->monetizationService->getActiveSubscription($fundi);
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        $subscription->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully'
        ]);
    }

    /**
     * Get subscription history.
     */
    public function getSubscriptionHistory(): JsonResponse
    {
        $fundi = Auth::user();
        
        if ($fundi->role !== 'fundi') {
            return response()->json([
                'success' => false,
                'message' => 'Only fundis can view subscription history'
            ], 403);
        }

        $subscriptions = FundiSubscription::where('user_id', $fundi->id)
            ->with('subscriptionTier')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }
}
