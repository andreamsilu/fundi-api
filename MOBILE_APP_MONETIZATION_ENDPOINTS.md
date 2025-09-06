# Mobile App - Monetization Endpoints Guide

## Overview
This guide provides the essential endpoints for mobile app integration with the Fundi App monetization system. These endpoints are optimized for mobile app usage with simplified responses and mobile-specific features.

---

## 🔐 **Fundi App - Subscription Management**

### 1. Get Available Subscription Plans
```
GET /api/v1/subscriptions/tiers
```
**Purpose:** Display subscription plans in mobile app settings/subscription screen

**Mobile Response:**
```json
{
  "success": true,
  "plans": [
    {
      "id": 1,
      "name": "Free",
      "price": 0,
      "currency": "TZS",
      "applications": 5,
      "features": ["Basic Support"],
      "popular": false
    },
    {
      "id": 2,
      "name": "Standard",
      "price": 15000,
      "currency": "TZS", 
      "applications": 25,
      "features": ["Enhanced Visibility", "Priority Support"],
      "popular": true
    },
    {
      "id": 3,
      "name": "Premium",
      "price": 35000,
      "currency": "TZS",
      "applications": 75,
      "features": ["Verified Badge", "Profile Boost", "Analytics"],
      "popular": false
    }
  ]
}
```

### 2. Get Current Subscription Status
```
GET /api/v1/subscriptions/current
```
**Purpose:** Show subscription status in app header/profile

**Mobile Response:**
```json
{
  "success": true,
  "subscription": {
    "plan": "Standard",
    "applications_left": 20,
    "expires_in_days": 15,
    "is_active": true,
    "auto_renew": true
  }
}
```

### 3. Subscribe to Plan
```
POST /api/v1/subscriptions/subscribe
```
**Purpose:** Handle subscription purchase in mobile app

**Mobile Request:**
```json
{
  "tier_id": 2,
  "payment_method": "mobile_money",
  "phone": "+255123456789"
}
```

**Mobile Response:**
```json
{
  "success": true,
  "message": "Subscription payment initiated",
  "payment": {
    "reference": "mm_abc123",
    "amount": 15000,
    "status": "pending"
  }
}
```

---

## 💳 **Fundi App - Credit Management**

### 4. Get Credit Balance
```
GET /api/v1/credits/balance
```
**Purpose:** Display credit balance in app header/wallet screen

**Mobile Response:**
```json
{
  "success": true,
  "balance": {
    "available": 5000,
    "currency": "TZS",
    "formatted": "5,000 TZS"
  }
}
```

### 5. Purchase Credits
```
POST /api/v1/credits/purchase
```
**Purpose:** Buy credits from mobile app

**Mobile Request:**
```json
{
  "amount": 10000,
  "payment_method": "mobile_money",
  "phone": "+255123456789"
}
```

**Mobile Response:**
```json
{
  "success": true,
  "message": "Credit purchase initiated",
  "payment": {
    "reference": "mm_xyz789",
    "amount": 10000,
    "status": "pending"
  }
}
```

### 6. Get Credit History
```
GET /api/v1/credits/history?per_page=20
```
**Purpose:** Show credit transactions in wallet screen

**Mobile Response:**
```json
{
  "success": true,
  "transactions": [
    {
      "id": 1,
      "type": "purchase",
      "amount": 10000,
      "description": "Credit purchase",
      "date": "2024-12-20T10:00:00Z",
      "status": "completed"
    },
    {
      "id": 2,
      "type": "usage",
      "amount": -1000,
      "description": "Job application fee",
      "date": "2024-12-20T11:00:00Z",
      "status": "completed"
    }
  ],
  "has_more": false
}
```

---

## 📝 **Fundi App - Job Applications**

### 7. Check Job Application Eligibility
```
GET /api/v1/jobs/{job_id}/application/eligibility
```
**Purpose:** Show if fundi can apply and what payment is needed

**Mobile Response:**
```json
{
  "success": true,
  "can_apply": true,
  "payment_required": {
    "amount": 1000,
    "currency": "TZS",
    "method": "subscription",
    "description": "Using subscription application"
  },
  "job": {
    "id": 456,
    "title": "Fix leaking pipe",
    "application_fee": 1000
  }
}
```

### 8. Apply to Job
```
POST /api/v1/jobs/{job_id}/apply
```
**Purpose:** Submit job application with payment processing

**Mobile Request:**
```json
{
  "message": "I can help with this job. I have 5 years experience.",
  "estimated_cost": 25000,
  "estimated_duration": "2 days"
}
```

**Mobile Response:**
```json
{
  "success": true,
  "message": "Application submitted successfully",
  "application": {
    "id": 1,
    "job_id": 456,
    "status": "pending",
    "fee_paid": 1000,
    "payment_method": "subscription"
  }
}
```

### 9. Get My Applications
```
GET /api/v1/applications/history?per_page=20
```
**Purpose:** Show fundi's job applications in "My Applications" screen

**Mobile Response:**
```json
{
  "success": true,
  "applications": [
    {
      "id": 1,
      "job": {
        "id": 456,
        "title": "Fix leaking pipe",
        "location": "Dar es Salaam",
        "budget": "20,000 - 30,000 TZS"
      },
      "status": "pending",
      "applied_at": "2024-12-20T10:00:00Z",
      "fee_paid": 1000
    }
  ],
  "has_more": false
}
```

---

## ⭐ **Customer App - Job Boosting**

### 10. Get Job Boost Options
```
GET /api/v1/jobs/{job_id}/boost/fee?boost_type=featured
```
**Purpose:** Show boost options when customer wants to promote job

**Mobile Response:**
```json
{
  "success": true,
  "boost_options": {
    "featured": {
      "price": 500,
      "currency": "TZS",
      "duration_days": 30,
      "description": "Make your job stand out"
    },
    "urgent": {
      "price": 1000,
      "currency": "TZS", 
      "duration_days": 7,
      "description": "Mark as urgent"
    }
  }
}
```

### 11. Boost Job
```
POST /api/v1/jobs/{job_id}/boost
```
**Purpose:** Boost job visibility

**Mobile Request:**
```json
{
  "boost_type": "featured",
  "payment_method": "mobile_money",
  "phone": "+255123456789"
}
```

**Mobile Response:**
```json
{
  "success": true,
  "message": "Job boost payment initiated",
  "boost": {
    "type": "featured",
    "price": 500,
    "duration_days": 30,
    "status": "pending"
  }
}
```

### 12. Get My Boosted Jobs
```
GET /api/v1/jobs/boosted?per_page=20
```
**Purpose:** Show customer's boosted jobs in "My Jobs" screen

**Mobile Response:**
```json
{
  "success": true,
  "boosted_jobs": [
    {
      "id": 1,
      "job": {
        "id": 456,
        "title": "Fix leaking pipe",
        "status": "open"
      },
      "boost_type": "featured",
      "expires_at": "2025-01-19T00:00:00Z",
      "status": "active"
    }
  ],
  "has_more": false
}
```

---

## 📱 **Mobile App - Job Listings with Monetization**

### 13. Get Jobs with Monetization Info
```
GET /api/v1/jobs?featured=true&per_page=20
```
**Purpose:** Show jobs with monetization data in job listings

**Mobile Response:**
```json
{
  "success": true,
  "jobs": [
    {
      "id": 456,
      "title": "Fix leaking pipe",
      "description": "Kitchen sink pipe is leaking",
      "location": "Dar es Salaam",
      "budget": "20,000 - 30,000 TZS",
      "is_featured": true,
      "boost_type": "featured",
      "application_fee": 1000,
      "currency": "TZS",
      "customer": {
        "name": "John Doe",
        "contact_locked": true,
        "unlock_message": "Apply to unlock contact info"
      }
    }
  ],
  "has_more": true
}
```

### 14. Get Job Details with Monetization
```
GET /api/v1/jobs/{job_id}
```
**Purpose:** Show job details with monetization info

**Mobile Response:**
```json
{
  "success": true,
  "job": {
    "id": 456,
    "title": "Fix leaking pipe",
    "description": "Kitchen sink pipe is leaking",
    "location": "Dar es Salaam",
    "budget": "20,000 - 30,000 TZS",
    "is_featured": true,
    "boost_type": "featured",
    "application_fee": 1000,
    "currency": "TZS",
    "customer": {
      "name": "John Doe",
      "contact_locked": true,
      "unlock_message": "Apply to unlock contact info"
    },
    "can_apply": true,
    "payment_required": {
      "amount": 1000,
      "method": "subscription"
    }
  }
}
```

---

## 📊 **Mobile App - Statistics & Analytics**

### 15. Get Fundi Dashboard Stats
```
GET /api/v1/credits/stats
```
**Purpose:** Show fundi's monetization stats in dashboard

**Mobile Response:**
```json
{
  "success": true,
  "stats": {
    "credits": {
      "balance": 5000,
      "used_this_month": 3000,
      "purchased_this_month": 10000
    },
    "applications": {
      "total": 15,
      "this_month": 8,
      "fees_paid": 8000
    },
    "subscription": {
      "plan": "Standard",
      "applications_left": 20,
      "renews_in_days": 15
    }
  }
}
```

### 16. Get Customer Boost Stats
```
GET /api/v1/jobs/boost/stats
```
**Purpose:** Show customer's boost statistics

**Mobile Response:**
```json
{
  "success": true,
  "stats": {
    "total_boosts": 5,
    "active_boosts": 2,
    "total_spent": 2500,
    "this_month": {
      "boosts": 3,
      "spent": 1500
    }
  }
}
```

---

## 🔔 **Mobile App - Payment Status & Webhooks**

### 17. Check Payment Status
```
GET /api/v1/payments/{payment_id}
```
**Purpose:** Check payment status after mobile money transaction

**Mobile Response:**
```json
{
  "success": true,
  "payment": {
    "id": 789,
    "status": "completed",
    "amount": 10000,
    "currency": "TZS",
    "reference": "mm_abc123",
    "completed_at": "2024-12-20T10:05:00Z"
  }
}
```

### 18. Payment Webhook (Mobile Money Callback)
```
POST /api/v1/webhooks/mobile-money
```
**Purpose:** Handle mobile money payment callbacks

**Webhook Payload:**
```json
{
  "event": "payment.completed",
  "data": {
    "reference": "mm_abc123",
    "amount": 10000,
    "currency": "TZS",
    "status": "completed",
    "phone": "+255123456789"
  }
}
```

---

## 📱 **Mobile App UI Integration Points**

### **Fundi App Screens:**
1. **Dashboard** - Show subscription status, credit balance, application stats
2. **Wallet** - Credit balance, purchase credits, transaction history
3. **Job Listings** - Show application fees, boost status
4. **Job Details** - Show payment requirements, eligibility
5. **My Applications** - Application history with fees paid
6. **Settings** - Subscription management, payment methods

### **Customer App Screens:**
1. **Job Creation** - Boost options during job posting
2. **My Jobs** - Boost status, boost management
3. **Job Listings** - Featured jobs, boost indicators
4. **Payment** - Mobile money integration for boosts

### **Common Mobile Features:**
- **Push Notifications** - Payment confirmations, subscription renewals
- **Offline Support** - Cache subscription status, credit balance
- **Deep Linking** - Direct links to job applications, boost options
- **Biometric Auth** - Secure payment confirmations

---

## 🚀 **Quick Start for Mobile Developers**

### **Essential Endpoints for MVP:**
1. `GET /api/v1/subscriptions/tiers` - Show plans
2. `GET /api/v1/credits/balance` - Show balance
3. `GET /api/v1/jobs/{id}/application/eligibility` - Check eligibility
4. `POST /api/v1/jobs/{id}/apply` - Apply to job
5. `GET /api/v1/jobs/boosted` - Show boosted jobs

### **Authentication Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### **Error Handling:**
- Always check `success` field in response
- Handle `402 Payment Required` for insufficient credits
- Show appropriate error messages from `message` field

This guide provides all the essential endpoints needed for mobile app integration with the monetization system! 📱✨
