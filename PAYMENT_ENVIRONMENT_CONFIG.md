# Payment Environment Configuration

This document outlines the environment variables required for payment system configuration in the backend.

## Required Environment Variables

Add these variables to your `.env` file:

```env
# Payment Gateway Configuration
PESAPAL_CONSUMER_KEY=your_pesapal_consumer_key
PESAPAL_CONSUMER_SECRET=your_pesapal_consumer_secret
PESAPAL_BASE_URL=https://sandbox.pesapal.com
PESAPAL_ENVIRONMENT=sandbox
PESAPAL_CALLBACK_URL=${APP_URL}/api/payments/pesapal/callback
PESAPAL_CANCEL_URL=${APP_URL}/api/payments/cancel

# M-Pesa Configuration
MPESA_CONSUMER_KEY=your_mpesa_consumer_key
MPESA_CONSUMER_SECRET=your_mpesa_consumer_secret
MPESA_BASE_URL=https://sandbox.safaricom.co.ke
MPESA_ENVIRONMENT=sandbox
MPESA_CALLBACK_URL=${APP_URL}/api/payments/mpesa/callback

# Payment Configuration
PAYMENT_DEFAULT_CURRENCY=TZS
PAYMENT_MIN_AMOUNT=100
PAYMENT_MAX_AMOUNT=1000000
PAYMENT_TIMEOUT_MINUTES=30

# Payment Actions Configuration
PAYMENT_ACTIONS_JOB_POST_AMOUNT=1000
PAYMENT_ACTIONS_PREMIUM_PROFILE_AMOUNT=500
PAYMENT_ACTIONS_FEATURED_JOB_AMOUNT=2000
PAYMENT_ACTIONS_FUNDI_APPLICATION_AMOUNT=200
PAYMENT_ACTIONS_SUBSCRIPTION_MONTHLY_AMOUNT=5000
PAYMENT_ACTIONS_SUBSCRIPTION_YEARLY_AMOUNT=50000

# Payment Logging
PAYMENT_LOG_LEVEL=info
PAYMENT_LOG_RETENTION_DAYS=90
```

## Sample Payment URLs

### Pesapal URLs
- **Sandbox**: `https://sandbox.pesapal.com/pesapalapi/api/PostPesapalDirectOrderV4`
- **Production**: `https://www.pesapal.com/api/PostPesapalDirectOrderV4`

### M-Pesa URLs
- **Sandbox**: `https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest`
- **Production**: `https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest`

## Backend Configuration File

Create `config/payments.php`:

```php
<?php

return [
    'pesapal' => [
        'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
        'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
        'base_url' => env('PESAPAL_BASE_URL'),
        'callback_url' => env('PESAPAL_CALLBACK_URL'),
        'cancel_url' => env('PESAPAL_CANCEL_URL'),
        'currency' => env('PAYMENT_DEFAULT_CURRENCY', 'TZS'),
        'environment' => env('PESAPAL_ENVIRONMENT', 'sandbox'),
    ],
    
    'mpesa' => [
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'base_url' => env('MPESA_BASE_URL'),
        'callback_url' => env('MPESA_CALLBACK_URL'),
        'currency' => env('PAYMENT_DEFAULT_CURRENCY', 'TZS'),
        'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),
    ],
    
    'actions' => [
        'job_post' => [
            'amount' => env('PAYMENT_ACTIONS_JOB_POST_AMOUNT', 1000),
            'description' => 'Job Posting Fee',
        ],
        'premium_profile' => [
            'amount' => env('PAYMENT_ACTIONS_PREMIUM_PROFILE_AMOUNT', 500),
            'description' => 'Premium Profile Upgrade',
        ],
        'featured_job' => [
            'amount' => env('PAYMENT_ACTIONS_FEATURED_JOB_AMOUNT', 2000),
            'description' => 'Featured Job Listing',
        ],
        'fundi_application' => [
            'amount' => env('PAYMENT_ACTIONS_FUNDI_APPLICATION_AMOUNT', 200),
            'description' => 'Fundi Application Fee',
        ],
        'subscription_monthly' => [
            'amount' => env('PAYMENT_ACTIONS_SUBSCRIPTION_MONTHLY_AMOUNT', 5000),
            'description' => 'Monthly Subscription',
        ],
        'subscription_yearly' => [
            'amount' => env('PAYMENT_ACTIONS_SUBSCRIPTION_YEARLY_AMOUNT', 50000),
            'description' => 'Yearly Subscription',
        ],
    ],
    
    'limits' => [
        'min_amount' => env('PAYMENT_MIN_AMOUNT', 100),
        'max_amount' => env('PAYMENT_MAX_AMOUNT', 1000000),
        'timeout_minutes' => env('PAYMENT_TIMEOUT_MINUTES', 30),
    ],
    
    'logging' => [
        'level' => env('PAYMENT_LOG_LEVEL', 'info'),
        'retention_days' => env('PAYMENT_LOG_RETENTION_DAYS', 90),
    ],
];
```

## API Endpoints

The following endpoints should be implemented in your Laravel backend:

### Payment Management
- `POST /api/payments/create` - Create payment request
- `GET /api/payments/config` - Get payment configuration
- `POST /api/payments/callback` - Handle payment callbacks
- `GET /api/payments/status/{id}` - Get payment status
- `GET /api/payments/history` - Get payment history
- `POST /api/payments/retry/{id}` - Retry failed payment

### Payment Gateways
- `POST /api/payments/pesapal/process` - Process Pesapal payment
- `POST /api/payments/mpesa/process` - Process M-Pesa payment
- `POST /api/payments/pesapal/callback` - Pesapal callback
- `POST /api/payments/mpesa/callback` - M-Pesa callback

## Testing

### Sandbox Testing
1. Use sandbox URLs for testing
2. Use test credentials provided by payment gateways
3. Test all payment flows in sandbox environment
4. Verify callback handling

### Production Deployment
1. Update environment variables to production values
2. Use production payment gateway URLs
3. Ensure SSL certificates are valid
4. Test payment flows with small amounts first

## Security Considerations

1. **Environment Variables**: Never commit `.env` files to version control
2. **API Keys**: Store securely and rotate regularly
3. **Callbacks**: Verify callback signatures
4. **HTTPS**: Always use HTTPS in production
5. **Logging**: Don't log sensitive payment data

## Monitoring

1. **Payment Logs**: Monitor payment success/failure rates
2. **Error Tracking**: Set up alerts for payment errors
3. **Performance**: Monitor payment processing times
4. **Security**: Monitor for suspicious payment activities
