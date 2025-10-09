<?php

namespace App\Http\Controllers;

use App\Models\AdminSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Settings Controller
 * 
 * Manages platform-wide settings including payment pricing,
 * platform modes, and other administrative configurations.
 */
class AdminSettingController extends Controller
{
    /**
     * Get current admin settings
     */
    public function index(): JsonResponse
    {
        $settings = AdminSetting::getSingleton();

        return response()->json([
            'success' => true,
            'message' => 'Admin settings retrieved successfully',
            'data' => $settings,
        ]);
    }

    /**
     * Update admin settings
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Platform mode
            'payments_enabled' => 'sometimes|boolean',
            'payment_model' => 'sometimes|in:free,subscription,pay_per_use',
            
            // Subscription settings
            'subscription_enabled' => 'sometimes|boolean',
            'subscription_fee' => 'sometimes|numeric|min:0',
            'subscription_period' => 'sometimes|in:monthly,yearly',
            
            // Job fees
            'job_application_fee_enabled' => 'sometimes|boolean',
            'job_application_fee' => 'sometimes|numeric|min:0',
            'job_posting_fee_enabled' => 'sometimes|boolean',
            'job_posting_fee' => 'sometimes|numeric|min:0',
            
            // Additional fees
            'premium_profile_fee' => 'sometimes|numeric|min:0',
            'featured_job_fee' => 'sometimes|numeric|min:0',
            'subscription_monthly_fee' => 'sometimes|numeric|min:0',
            'subscription_yearly_fee' => 'sometimes|numeric|min:0',
            'platform_commission_percentage' => 'sometimes|numeric|min:0|max:100',
        ]);

        $settings = AdminSetting::getSingleton();
        $settings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Admin settings updated successfully',
            'data' => $settings->fresh(),
        ]);
    }

    /**
     * Get all pricing information
     */
    public function getPricing(): JsonResponse
    {
        $settings = AdminSetting::getSingleton();

        return response()->json([
            'success' => true,
            'message' => 'Pricing retrieved successfully',
            'data' => [
                'pricing' => $settings->getAllPricing(),
                'enabled' => [
                    'payments_enabled' => $settings->payments_enabled,
                    'job_application_fee_enabled' => $settings->job_application_fee_enabled,
                    'job_posting_fee_enabled' => $settings->job_posting_fee_enabled,
                    'subscription_enabled' => $settings->subscription_enabled,
                ],
                'mode' => $settings->isFreeMode() ? 'free' : 'paid',
            ],
        ]);
    }

    /**
     * Update pricing only
     */
    public function updatePricing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_application_fee' => 'required|numeric|min:0',
            'job_posting_fee' => 'required|numeric|min:0',
            'premium_profile_fee' => 'required|numeric|min:0',
            'featured_job_fee' => 'required|numeric|min:0',
            'subscription_monthly_fee' => 'required|numeric|min:0',
            'subscription_yearly_fee' => 'required|numeric|min:0',
            'platform_commission_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $settings = AdminSetting::getSingleton();
        $settings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pricing updated successfully',
            'data' => $settings->getAllPricing(),
        ]);
    }

    /**
     * Reset settings to defaults
     */
    public function resetToDefaults(): JsonResponse
    {
        $settings = AdminSetting::getSingleton();
        
        $settings->update([
            'payments_enabled' => false,
            'payment_model' => 'free',
            'subscription_enabled' => false,
            'job_application_fee_enabled' => false,
            'job_posting_fee_enabled' => false,
            'job_application_fee' => 200,
            'job_posting_fee' => 1000,
            'premium_profile_fee' => 500,
            'featured_job_fee' => 2000,
            'subscription_monthly_fee' => 5000,
            'subscription_yearly_fee' => 50000,
            'platform_commission_percentage' => 10,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Settings reset to defaults successfully',
            'data' => $settings->fresh(),
        ]);
    }
}



