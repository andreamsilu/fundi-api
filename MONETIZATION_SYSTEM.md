# Fundi App API - Hybrid Monetization System

## Overview

The Fundi App API implements a comprehensive hybrid monetization system that combines subscription-based and pay-per-use models to generate revenue from both fundis (service providers) and customers. The system ensures fair access to job opportunities while providing multiple revenue streams.

## System Architecture

### Core Components

1. **Subscription Tiers** - Three tiers (Free, Standard, Premium) with different features and application limits
2. **Credit System** - Pay-per-job application system using internal credits
3. **Premium Job Boosting** - Customer-paid job promotion system
4. **Revenue Tracking** - Comprehensive analytics and reporting
5. **Security Controls** - Protection against payment bypassing

## Database Schema

### Subscription System

#### `subscription_tiers` Table
- **id**: Primary key
- **name**: Tier name (Free, Standard, Premium)
- **slug**: URL-friendly identifier
- **monthly_price_tzs**: Monthly cost in Tanzanian Shillings
- **included_job_applications**: Number of free applications per month
- **features**: JSON array of tier features
- **is_active**: Whether tier is available
- **sort_order**: Display order

#### `fundi_subscriptions` Table
- **id**: Primary key
- **user_id**: Foreign key to users table
- **subscription_tier_id**: Foreign key to subscription_tiers
- **status**: active, expired, cancelled, suspended
- **starts_at**: Subscription start date
- **expires_at**: Subscription expiration date
- **remaining_applications**: Applications left in current period
- **last_reset_at**: When applications were last reset

### Credit System

#### `fundi_credits` Table
- **id**: Primary key
- **user_id**: Foreign key to users table
- **balance**: Current credit balance
- **total_purchased**: Total credits ever purchased
- **total_used**: Total credits used

#### `credit_transactions` Table
- **id**: Primary key
- **user_id**: Foreign key to users table
- **type**: purchase, usage, refund, bonus
- **amount**: Transaction amount
- **description**: Transaction description
- **reference**: External reference (payment ID)
- **payment_id**: Foreign key to payments table
- **job_id**: Foreign key to jobs table (if applicable)

### Job Application Fees

#### `job_application_fees` Table
- **id**: Primary key
- **job_id**: Foreign key to jobs table
- **fundi_id**: Foreign key to users table
- **fee_amount**: Fee charged for application
- **payment_type**: subscription or credits
- **credit_transaction_id**: Foreign key to credit_transactions
- **subscription_id**: Foreign key to fundi_subscriptions
- **status**: pending, paid, refunded

### Premium Job Boosting

#### `premium_job_boosters` Table
- **id**: Primary key
- **job_id**: Foreign key to jobs table
- **user_id**: Foreign key to users table (customer)
- **boost_type**: featured, urgent, premium
- **boost_fee**: Fee paid for boost
- **business_model**: c2c, b2c, c2b, b2b
- **starts_at**: Boost start date
- **expires_at**: Boost expiration date
- **status**: active, expired, cancelled
- **payment_id**: Foreign key to payments table

### Revenue Tracking

#### `revenue_tracking` Table
- **id**: Primary key
- **revenue_type**: subscription, credits, job_boost, application_fee
- **user_id**: Foreign key to users table
- **job_id**: Foreign key to jobs table (if applicable)
- **business_model**: c2c, b2c, c2b, b2b
- **amount**: Revenue amount
- **currency**: Currency code (TZS)
- **description**: Revenue description
- **payment_id**: Foreign key to payments table
- **revenue_date**: Date revenue was generated

## API Endpoints

### Subscription Management (Fundis Only)

#### GET `/api/v1/subscriptions/tiers`
Get all available subscription tiers.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Free",
      "slug": "free",
      "monthly_price_tzs": 0,
      "included_job_applications": 5,
      "features": ["basic_support": true],
      "is_active": true
    }
  ]
}
```

#### POST `/api/v1/subscriptions/subscribe`
Subscribe to a subscription tier.

**Request:**
```json
{
  "tier_id": 2
}
```

#### GET `/api/v1/subscriptions/current`
Get current subscription details.

#### POST `/api/v1/subscriptions/cancel`
Cancel current subscription.

### Credit Management (Fundis Only)

#### GET `/api/v1/credits/balance`
Get current credit balance.

#### POST `/api/v1/credits/purchase`
Purchase credits.

**Request:**
```json
{
  "amount": 10000,
  "payment_method": "mobile_money"
}
```

#### GET `/api/v1/credits/history`
Get credit transaction history.

### Job Applications (Fundis Only)

#### GET `/api/v1/jobs/{job}/application/eligibility`
Check if fundi can apply to a job.

**Response:**
```json
{
  "success": true,
  "data": {
    "can_apply": true,
    "reason": "Using subscription application",
    "application_fee": 1000,
    "payment_type": "subscription",
    "required_payment": null
  }
}
```

#### POST `/api/v1/jobs/{job}/apply`
Apply to a job with payment processing.

**Request:**
```json
{
  "message": "I can help with this job",
  "estimated_cost": 25000,
  "estimated_duration": "2 days"
}
```

### Premium Job Boosting (Customers Only)

#### POST `/api/v1/jobs/{job}/boost`
Boost a job to premium status.

**Request:**
```json
{
  "boost_type": "featured",
  "duration_days": 30
}
```

#### GET `/api/v1/jobs/{job}/boost/fee`
Get boost fee for a job.

#### GET `/api/v1/jobs/boosted`
Get customer's boosted jobs.

### Admin Revenue Reporting (Admins Only)

#### GET `/api/v1/admin/revenue/stats`
Get revenue statistics.

#### GET `/api/v1/admin/revenue/business-model`
Get revenue breakdown by business model.

#### GET `/api/v1/admin/revenue/user`
Get revenue for specific user.

## Pricing Structure

### Subscription Tiers

| Tier | Monthly Price | Applications | Features |
|------|---------------|--------------|----------|
| Free | 0 TZS | 5 | Basic support, standard visibility |
| Standard | 15,000 TZS | 25 | Enhanced visibility, priority support |
| Premium | 35,000 TZS | 75 | Verified badge, profile boost, analytics |

### Job Application Fees

| Job Value | Application Fee |
|-----------|-----------------|
| < 20,000 TZS | 500 TZS |
| 20,000–100,000 TZS | 1,000 TZS |
| > 100,000 TZS | 2,000–5,000 TZS |

### Job Boost Fees

| Business Model | Boost Fee |
|----------------|-----------|
| C2C | 500 TZS |
| B2C | 1,000 TZS |
| B2B | 10,000 TZS/month |
| C2B | 5,000 TZS per job |

## Security Features

### Payment Enforcement
- Fundis cannot apply to jobs without sufficient subscription applications or credits
- Customer contact information is hidden until payment is made
- All payments are processed through secure mobile money integration

### Anti-Bypass Protection
- Middleware enforces monetization rules on all job application routes
- Customer contact protection middleware hides sensitive information
- Revenue tracking ensures all transactions are recorded

## Business Logic

### Application Priority
1. **Subscription Applications**: Used first if available
2. **Credit Payments**: Used when subscription applications are exhausted
3. **Payment Required**: Application blocked if neither available

### Revenue Generation
- **Subscription Revenue**: Monthly recurring revenue from fundis
- **Credit Revenue**: One-time payments for additional applications
- **Boost Revenue**: Customer payments for job promotion
- **Application Fee Revenue**: Revenue from individual job applications

### Job Boosting
- Jobs must be boosted before fundis can see contact information
- Boost duration is typically 30 days
- Different boost types provide different visibility levels

## Integration Points

### Mobile Money Integration
- M-Pesa, TigoPesa, Airtel Money support
- Secure payment processing
- Real-time payment verification
- Webhook support for payment callbacks

### User Experience
- Clear pricing transparency
- Real-time balance updates
- Comprehensive transaction history
- Admin dashboard for revenue monitoring

## Testing

The system includes comprehensive test coverage:
- Unit tests for all models and services
- Feature tests for API endpoints
- Integration tests for payment flows
- Security tests for anti-bypass measures

Run tests with:
```bash
php artisan test --filter=MonetizationTest
```

## Deployment Considerations

### Database Migrations
Run all monetization migrations:
```bash
php artisan migrate
```

### Seeders
Seed subscription tiers:
```bash
php artisan db:seed --class=SubscriptionTierSeeder
```

### Configuration
Ensure mobile money credentials are configured in environment variables.

## Monitoring and Analytics

### Revenue Metrics
- Total revenue by type
- Revenue by business model
- User-specific revenue tracking
- Monthly/quarterly/yearly trends

### Usage Analytics
- Application success rates
- Credit utilization patterns
- Subscription retention rates
- Boost effectiveness metrics

## Future Enhancements

### Planned Features
- Dynamic pricing based on demand
- Bulk credit packages
- Referral bonuses
- Loyalty rewards
- Advanced analytics dashboard
- Automated subscription renewals

### Scalability Considerations
- Database indexing for performance
- Caching for frequently accessed data
- Queue processing for payment webhooks
- API rate limiting for security
