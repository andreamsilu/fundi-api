<?php

/**
 * Payment Configuration
 * 
 * This file contains all payment-related configuration for the Fundi platform.
 * It integrates with ZenoPay for mobile money payments in Tanzania.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Provider
    |--------------------------------------------------------------------------
    |
    | The default payment gateway to use for processing payments.
    | Currently supports: 'zenopay'
    |
    */
    'provider' => env('PAYMENT_PROVIDER', 'zenopay'),

    /*
    |--------------------------------------------------------------------------
    | Payment Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for all payments in the platform.
    | TZS = Tanzanian Shilling
    |
    */
    'currency' => env('PAYMENT_DEFAULT_CURRENCY', 'TZS'),

    /*
    |--------------------------------------------------------------------------
    | Payment Limits
    |--------------------------------------------------------------------------
    |
    | Minimum and maximum amounts that can be processed through the platform.
    | Values are in the default currency (TZS).
    |
    */
    'min_amount' => env('PAYMENT_MIN_AMOUNT', 100),
    'max_amount' => env('PAYMENT_MAX_AMOUNT', 1000000),

    /*
    |--------------------------------------------------------------------------
    | Payment Timeout
    |--------------------------------------------------------------------------
    |
    | The number of minutes a payment can remain in 'pending' status before
    | being automatically marked as expired/failed.
    |
    */
    'timeout_minutes' => env('PAYMENT_TIMEOUT_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Platform Action Fees
    |--------------------------------------------------------------------------
    |
    | IMPORTANT: These fees are now managed from the Admin Panel!
    | The values below are ONLY used as fallback defaults.
    | 
    | To change pricing, go to:
    | Admin Panel → Settings → Payment Settings
    |
    | Values are in TZS (Tanzanian Shillings).
    |
    */
    'actions' => [
        // These are fallback defaults - actual values come from AdminSetting model
        'job_post' => 1000,
        'premium_profile' => 500,
        'featured_job' => 2000,
        'fundi_application' => 200,
        'subscription_monthly' => 5000,
        'subscription_yearly' => 50000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Get Pricing from Admin Panel
    |--------------------------------------------------------------------------
    |
    | Helper function to get current pricing from admin settings.
    | Always use this instead of the hardcoded values above.
    |
    */
    'get_pricing' => function() {
        $settings = \App\Models\AdminSetting::getSingleton();
        return $settings->getAllPricing();
    },

    /*
    |--------------------------------------------------------------------------
    | Payment Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for payment transaction logging and retention.
    |
    */
    'logging' => [
        'level' => env('PAYMENT_LOG_LEVEL', 'info'),
        'retention_days' => env('PAYMENT_LOG_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    |
    | Additional settings for payment transaction handling.
    |
    */
    'transaction' => [
        'auto_expire' => true,
        'auto_expire_minutes' => env('PAYMENT_TIMEOUT_MINUTES', 30),
        'allow_refunds' => true,
        'refund_window_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | ZenoPay Gateway Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to ZenoPay mobile money gateway.
    | These are loaded from services.php but referenced here for convenience.
    |
    */
    'zenopay' => [
        'fee_percentage' => env('ZENOPAY_TRANSACTION_FEE_PERCENTAGE', 2.5),
        'max_retries' => env('ZENOPAY_MAX_RETRIES', 3),
        'retry_delay_seconds' => env('ZENOPAY_RETRY_DELAY_SECONDS', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Payment Methods
    |--------------------------------------------------------------------------
    |
    | Payment methods available on the platform.
    |
    */
    'methods' => [
        'mobile_money' => [
            'enabled' => true,
            'providers' => ['M-Pesa', 'Tigo Pesa', 'Airtel Money'],
        ],
        'bank_transfer' => [
            'enabled' => false,
        ],
        'card' => [
            'enabled' => false,
        ],
    ],

];

