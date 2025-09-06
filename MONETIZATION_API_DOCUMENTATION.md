# Fundi App API - Monetization System Endpoints

## Overview
This document provides comprehensive API documentation for the hybrid monetization system endpoints, including request/response examples and authentication requirements.

## Authentication
All endpoints require authentication via Laravel Sanctum token. Include the token in the Authorization header:
```
Authorization: Bearer {your-token}
```

---

## 🔐 Subscription Management (Fundis Only)

### GET `/api/v1/subscriptions/tiers`
Get all available subscription tiers.

**Authentication:** Required (Fundi role)

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
      "features": {
        "basic_support": true,
        "profile_visibility": "standard"
      },
      "is_active": true,
      "sort_order": 1
    },
    {
      "id": 2,
      "name": "Standard",
      "slug": "standard",
      "monthly_price_tzs": 15000,
      "included_job_applications": 25,
      "features": {
        "basic_support": true,
        "profile_visibility": "enhanced",
        "priority_support": true
      },
      "is_active": true,
      "sort_order": 2
    },
    {
      "id": 3,
      "name": "Premium",
      "slug": "premium",
      "monthly_price_tzs": 35000,
      "included_job_applications": 75,
      "features": {
        "basic_support": true,
        "profile_visibility": "premium",
        "priority_support": true,
        "verified_badge": true,
        "profile_boost": true
      },
      "is_active": true,
      "sort_order": 3
    }
  ]
}
```

### GET `/api/v1/subscriptions/current`
Get fundi's current subscription details.

**Authentication:** Required (Fundi role)

**Response:**
```json
{
  "success": true,
  "data": {
    "subscription": {
      "id": 1,
      "user_id": 123,
      "subscription_tier_id": 2,
      "status": "active",
      "starts_at": "2024-12-20T00:00:00.000000Z",
      "expires_at": "2025-01-20T00:00:00.000000Z",
      "remaining_applications": 20,
      "last_reset_at": "2024-12-20T00:00:00.000000Z",
      "subscription_tier": {
        "id": 2,
        "name": "Standard",
        "slug": "standard",
        "monthly_price_tzs": 15000,
        "included_job_applications": 25
      }
    },
    "remaining_applications": 20,
    "expires_at": "2025-01-20T00:00:00.000000Z",
    "is_active": true
  }
}
```

### POST `/api/v1/subscriptions/subscribe`
Subscribe to a subscription tier.

**Authentication:** Required (Fundi role)

**Request:**
```json
{
  "tier_id": 2
}
```

**Response:**
```json
{
  "success": true,
  "message": "Subscription initiated successfully",
  "payment": {
    "id": 456,
    "user_id": 123,
    "amount": 15000,
    "currency": "TZS",
    "status": "pending",
    "payment_method": "mobile_money",
    "payment_provider": "mobile_money"
  },
  "subscription": {
    "id": 1,
    "user_id": 123,
    "subscription_tier_id": 2,
    "status": "active",
    "starts_at": "2024-12-20T00:00:00.000000Z",
    "expires_at": "2025-01-20T00:00:00.000000Z",
    "remaining_applications": 25,
    "last_reset_at": "2024-12-20T00:00:00.000000Z"
  }
}
```

### POST `/api/v1/subscriptions/cancel`
Cancel current subscription.

**Authentication:** Required (Fundi role)

**Response:**
```json
{
  "success": true,
  "message": "Subscription cancelled successfully"
}
```

### GET `/api/v1/subscriptions/history`
Get subscription history.

**Authentication:** Required (Fundi role)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 123,
      "subscription_tier_id": 2,
      "status": "active",
      "starts_at": "2024-12-20T00:00:00.000000Z",
      "expires_at": "2025-01-20T00:00:00.000000Z",
      "subscription_tier": {
        "id": 2,
        "name": "Standard",
        "slug": "standard"
      }
    }
  ]
}
```

---

## 💳 Credit Management (Fundis Only)

### GET `/api/v1/credits/balance`
Get current credit balance.

**Authentication:** Required (Fundi role)

**Response:**
```json
{
  "success": true,
  "data": {
    "balance": 5000.00,
    "total_purchased": 10000.00,
    "total_used": 5000.00,
    "available_balance": 5000.00
  }
}
```

### POST `/api/v1/credits/purchase`
Purchase credits.

**Authentication:** Required (Fundi role)

**Request:**
```json
{
  "amount": 10000,
  "payment_method": "mobile_money"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Credit purchase initiated successfully",
  "payment": {
    "id": 789,
    "user_id": 123,
    "amount": 10000,
    "currency": "TZS",
    "status": "pending",
    "payment_method": "mobile_money"
  },
  "credit_transaction": {
    "id": 1,
    "user_id": 123,
    "type": "purchase",
    "amount": 10000,
    "description": "Credit purchase via mobile money",
    "payment_id": 789
  }
}
```

### GET `/api/v1/credits/history`
Get credit transaction history.

**Authentication:** Required (Fundi role)

**Query Parameters:**
- `type` (optional): purchase, usage, refund, bonus
- `per_page` (optional): 1-100, default 15

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "user_id": 123,
        "type": "purchase",
        "amount": 10000,
        "description": "Credit purchase via mobile money",
        "payment_id": 789,
        "job_id": null,
        "created_at": "2024-12-20T10:00:00.000000Z"
      },
      {
        "id": 2,
        "user_id": 123,
        "type": "usage",
        "amount": 1000,
        "description": "Job application fee for job #456",
        "payment_id": null,
        "job_id": 456,
        "created_at": "2024-12-20T11:00:00.000000Z"
      }
    ],
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 2
  }
}
```

### GET `/api/v1/credits/stats`
Get credit usage statistics.

**Authentication:** Required (Fundi role)

**Response:**
```json
{
  "success": true,
  "data": {
    "current_balance": 5000.00,
    "total_purchased": 10000.00,
    "total_used": 5000.00,
    "purchase_count": 1,
    "usage_count": 5,
    "refund_count": 0,
    "monthly_usage": [
      {
        "month": "2024-11",
        "total_amount": 2000.00,
        "transaction_count": 2
      },
      {
        "month": "2024-12",
        "total_amount": 3000.00,
        "transaction_count": 3
      }
    ]
  }
}
```

---

## 📝 Job Applications (Fundis Only)

### GET `/api/v1/jobs/{job}/application/eligibility`
Check if fundi can apply to a job.

**Authentication:** Required (Fundi role)

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

### POST `/api/v1/jobs/{job}/apply`
Apply to a job with payment processing.

**Authentication:** Required (Fundi role)
**Middleware:** enforce.monetization

**Request:**
```json
{
  "message": "I can help with this job. I have 5 years experience in plumbing.",
  "estimated_cost": 25000,
  "estimated_duration": "2 days"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Application submitted successfully",
  "data": {
    "booking": {
      "id": 1,
      "job_id": 456,
      "fundi_id": 123,
      "customer_id": 789,
      "service_job_id": 456,
      "description": "I can help with this job. I have 5 years experience in plumbing.",
      "estimated_cost": 25000,
      "estimated_duration": "2 days",
      "status": "pending",
      "payment_status": "paid"
    },
    "application_fee": {
      "id": 1,
      "job_id": 456,
      "fundi_id": 123,
      "fee_amount": 1000,
      "payment_type": "subscription",
      "status": "paid"
    }
  }
}
```

### GET `/api/v1/applications/history`
Get fundi's application history.

**Authentication:** Required (Fundi role)

**Query Parameters:**
- `status` (optional): pending, paid, refunded
- `per_page` (optional): 1-100, default 15

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "job_id": 456,
        "fundi_id": 123,
        "fee_amount": 1000,
        "payment_type": "subscription",
        "status": "paid",
        "job": {
          "id": 456,
          "title": "Fix leaking pipe",
          "description": "Kitchen sink pipe is leaking"
        },
        "created_at": "2024-12-20T10:00:00.000000Z"
      }
    ],
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

### GET `/api/v1/applications/stats`
Get application statistics for fundi.

**Authentication:** Required (Fundi role)

**Response:**
```json
{
  "success": true,
  "data": {
    "total_applications": 15,
    "paid_applications": 12,
    "pending_applications": 3,
    "total_fees_paid": 12000.00,
    "subscription_applications": 8,
    "credit_applications": 4,
    "monthly_fees": [
      {
        "month": "2024-11",
        "total_fees": 5000.00,
        "application_count": 5
      },
      {
        "month": "2024-12",
        "total_fees": 7000.00,
        "application_count": 7
      }
    ]
  }
}
```

---

## ⭐ Premium Job Boosting (Customers Only)

### POST `/api/v1/jobs/{job}/boost`
Boost a job to premium status.

**Authentication:** Required (Customer role)

**Request:**
```json
{
  "boost_type": "featured",
  "duration_days": 30
}
```

**Response:**
```json
{
  "success": true,
  "message": "Job boost payment initiated successfully",
  "payment": {
    "id": 101,
    "user_id": 789,
    "amount": 500,
    "currency": "TZS",
    "status": "pending",
    "payment_method": "mobile_money"
  },
  "booster": {
    "id": 1,
    "job_id": 456,
    "user_id": 789,
    "boost_type": "featured",
    "boost_fee": 500,
    "business_model": "c2c",
    "starts_at": "2024-12-20T00:00:00.000000Z",
    "expires_at": "2025-01-19T00:00:00.000000Z",
    "status": "active"
  }
}
```

### GET `/api/v1/jobs/{job}/boost/fee`
Get boost fee for a job.

**Authentication:** Required (Customer role)

**Query Parameters:**
- `boost_type`: featured, urgent, premium

**Response:**
```json
{
  "success": true,
  "data": {
    "boost_fee": 500,
    "business_model": "c2c",
    "boost_type": "featured",
    "currency": "TZS"
  }
}
```

### GET `/api/v1/jobs/boosted`
Get customer's boosted jobs.

**Authentication:** Required (Customer role)

**Query Parameters:**
- `status` (optional): active, expired, cancelled
- `per_page` (optional): 1-100, default 15

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "job_id": 456,
        "user_id": 789,
        "boost_type": "featured",
        "boost_fee": 500,
        "business_model": "c2c",
        "status": "active",
        "expires_at": "2025-01-19T00:00:00.000000Z",
        "job": {
          "id": 456,
          "title": "Fix leaking pipe",
          "is_featured": true
        }
      }
    ],
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

### POST `/api/v1/jobs/boost/{booster}/cancel`
Cancel a job boost.

**Authentication:** Required (Customer role)

**Response:**
```json
{
  "success": true,
  "message": "Job boost cancelled successfully"
}
```

### GET `/api/v1/jobs/boost/stats`
Get boost statistics for customer.

**Authentication:** Required (Customer role)

**Response:**
```json
{
  "success": true,
  "data": {
    "total_boosts": 5,
    "active_boosts": 2,
    "expired_boosts": 3,
    "total_spent": 2500.00,
    "c2c_boosts": 3,
    "b2c_boosts": 1,
    "b2b_boosts": 1,
    "c2b_boosts": 0,
    "monthly_spending": [
      {
        "month": "2024-11",
        "total_spent": 1000.00,
        "boost_count": 2
      },
      {
        "month": "2024-12",
        "total_spent": 1500.00,
        "boost_count": 3
      }
    ]
  }
}
```

---

## 📊 Admin Revenue Reporting (Admins Only)

### GET `/api/v1/admin/revenue/stats`
Get revenue statistics for admin dashboard.

**Authentication:** Required (Admin role)

**Query Parameters:**
- `period` (optional): day, week, month, year (default: month)

**Response:**
```json
{
  "success": true,
  "data": {
    "period": "month",
    "total_revenue": 150000.00,
    "subscription_revenue": 75000.00,
    "credit_revenue": 45000.00,
    "boost_revenue": 30000.00,
    "breakdown": [
      {
        "revenue_type": "subscription",
        "business_model": "c2c",
        "total_amount": 50000.00,
        "transaction_count": 10
      },
      {
        "revenue_type": "credits",
        "business_model": "c2c",
        "total_amount": 30000.00,
        "transaction_count": 50
      }
    ],
    "start_date": "2024-12-01",
    "end_date": "2024-12-20"
  }
}
```

### GET `/api/v1/admin/revenue/business-model`
Get revenue breakdown by business model.

**Authentication:** Required (Admin role)

**Query Parameters:**
- `start_date` (optional): YYYY-MM-DD
- `end_date` (optional): YYYY-MM-DD
- `business_model` (optional): c2c, b2c, c2b, b2b

**Response:**
```json
{
  "success": true,
  "data": {
    "revenue_breakdown": [
      {
        "business_model": "c2c",
        "revenue_type": "subscription",
        "total_amount": 50000.00,
        "transaction_count": 10
      },
      {
        "business_model": "c2c",
        "revenue_type": "credits",
        "total_amount": 30000.00,
        "transaction_count": 50
      }
    ],
    "start_date": "2024-12-01",
    "end_date": "2024-12-20"
  }
}
```

### GET `/api/v1/admin/revenue/user`
Get revenue for specific user.

**Authentication:** Required (Admin role)

**Query Parameters:**
- `user_id` (required): User ID
- `start_date` (optional): YYYY-MM-DD
- `end_date` (optional): YYYY-MM-DD

**Response:**
```json
{
  "success": true,
  "data": {
    "user_revenue": [
      {
        "id": 1,
        "revenue_type": "subscription",
        "user_id": 123,
        "amount": 15000.00,
        "description": "Monthly subscription payment",
        "revenue_date": "2024-12-20"
      }
    ],
    "total_revenue": 15000.00,
    "revenue_by_type": {
      "subscription": {
        "total_amount": 15000.00,
        "transaction_count": 1
      }
    },
    "start_date": "2024-12-01",
    "end_date": "2024-12-20"
  }
}
```

### GET `/api/v1/admin/revenue/top-users`
Get top revenue generating users.

**Authentication:** Required (Admin role)

**Query Parameters:**
- `limit` (optional): 1-100, default 10
- `start_date` (optional): YYYY-MM-DD
- `end_date` (optional): YYYY-MM-DD

**Response:**
```json
{
  "success": true,
  "data": {
    "top_users": [
      {
        "user_id": 123,
        "total_revenue": 50000.00,
        "transaction_count": 25,
        "revenue_types": 3,
        "user": {
          "id": 123,
          "name": "John Doe",
          "phone": "+255123456789",
          "role": "fundi"
        }
      }
    ],
    "start_date": "2024-12-01",
    "end_date": "2024-12-20"
  }
}
```

### GET `/api/v1/admin/revenue/trends`
Get revenue trends over time.

**Authentication:** Required (Admin role)

**Query Parameters:**
- `period` (optional): day, week, month (default: day)
- `months` (optional): 1-24, default 6

**Response:**
```json
{
  "success": true,
  "data": {
    "trends": [
      {
        "period": "2024-12-20",
        "revenue_type": "subscription",
        "total_amount": 5000.00,
        "transaction_count": 2
      },
      {
        "period": "2024-12-20",
        "revenue_type": "credits",
        "total_amount": 3000.00,
        "transaction_count": 15
      }
    ],
    "period": "day",
    "months": 6
  }
}
```

### GET `/api/v1/admin/revenue/report`
Get detailed revenue report.

**Authentication:** Required (Admin role)

**Query Parameters:**
- `start_date` (required): YYYY-MM-DD
- `end_date` (required): YYYY-MM-DD
- `revenue_type` (optional): subscription, credits, job_boost, application_fee
- `business_model` (optional): c2c, b2c, c2b, b2b
- `per_page` (optional): 1-100, default 50

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "revenue_type": "subscription",
        "user_id": 123,
        "job_id": null,
        "business_model": "c2c",
        "amount": 15000.00,
        "currency": "TZS",
        "description": "Monthly subscription payment",
        "revenue_date": "2024-12-20",
        "user": {
          "id": 123,
          "name": "John Doe"
        }
      }
    ],
    "current_page": 1,
    "last_page": 1,
    "per_page": 50,
    "total": 1
  }
}
```

---

## 🔒 Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "tier_id": ["The tier id field is required."]
  }
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Only fundis can have subscriptions"
}
```

### 402 Payment Required
```json
{
  "success": false,
  "message": "Insufficient subscription applications and credits",
  "error_code": "MONETIZATION_REQUIRED",
  "data": {
    "required_payment": 1000,
    "payment_type": "credits",
    "subscription_required": false,
    "credits_required": true
  }
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "No active subscription found"
}
```

---

## 📋 Summary

The monetization system provides 25+ new API endpoints across 5 main categories:

1. **Subscription Management** (5 endpoints) - Fundi subscription tiers and management
2. **Credit Management** (4 endpoints) - Pay-per-job credit system
3. **Job Applications** (4 endpoints) - Job application with payment processing
4. **Premium Job Boosting** (5 endpoints) - Customer job promotion system
5. **Admin Revenue Reporting** (6 endpoints) - Comprehensive analytics and reporting

All endpoints include proper authentication, validation, error handling, and comprehensive response documentation.
