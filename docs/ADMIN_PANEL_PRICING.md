# 💰 Admin Panel Pricing Management

**Date:** October 9, 2025  
**Status:** ✅ Active  
**Control:** Admin Panel Only (No more .env editing!)

---

## 🎯 **WHAT CHANGED**

### **Before (Bad):**
```bash
# .env file
PAYMENT_ACTIONS_JOB_POST_AMOUNT=1000
PAYMENT_ACTIONS_PREMIUM_PROFILE_AMOUNT=500
PAYMENT_ACTIONS_FEATURED_JOB_AMOUNT=2000
...
```

❌ **Problems:**
- Need server access to change prices
- Require server restart
- Not user-friendly
- No validation

### **After (Good):**
```
Admin Panel → Settings → Pricing
[Edit prices in UI] → Save
```

✅ **Benefits:**
- No server access needed
- Instant updates (no restart)
- User-friendly interface
- Built-in validation
- Audit trail

---

## 📋 **WHAT TO REMOVE FROM .ENV**

Open your `/var/www/html/myprojects/fundi-api/.env` and **comment out or delete** these lines:

```bash
# ❌ DELETE OR COMMENT OUT THESE LINES:
# PAYMENT_ACTIONS_JOB_POST_AMOUNT=1000
# PAYMENT_ACTIONS_PREMIUM_PROFILE_AMOUNT=500
# PAYMENT_ACTIONS_FEATURED_JOB_AMOUNT=2000
# PAYMENT_ACTIONS_FUNDI_APPLICATION_AMOUNT=200
# PAYMENT_ACTIONS_SUBSCRIPTION_MONTHLY_AMOUNT=5000
# PAYMENT_ACTIONS_SUBSCRIPTION_YEARLY_AMOUNT=50000
```

### **Keep These in .ENV:**
```bash
# ✅ KEEP - General payment configuration
PAYMENT_DEFAULT_CURRENCY=TZS
PAYMENT_MIN_AMOUNT=100
PAYMENT_MAX_AMOUNT=1000000
PAYMENT_TIMEOUT_MINUTES=30

# ✅ KEEP - Payment logging
PAYMENT_LOG_LEVEL=info
PAYMENT_LOG_RETENTION_DAYS=90

# ✅ KEEP - ZenoPay configuration
ZENOPAY_API_KEY=your-api-key
ZENOPAY_BASE_URL=https://zenoapi.com
ZENOPAY_WEBHOOK_URL="${APP_URL}/api/payments/zenopay/webhook"
ZENOPAY_ENABLED=true
```

---

## 🗄️ **DATABASE CHANGES**

### **New Migration:**
```bash
php artisan migrate
```

This adds to `admin_settings` table:
- `premium_profile_fee` (decimal, default 500)
- `featured_job_fee` (decimal, default 2000)
- `subscription_monthly_fee` (decimal, default 5000)
- `subscription_yearly_fee` (decimal, default 50000)
- `platform_commission_percentage` (decimal, default 10)

---

## 🔌 **NEW API ENDPOINTS**

### **1. Get Current Pricing**
```http
GET /api/admin/settings/pricing
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "success": true,
  "message": "Pricing retrieved successfully",
  "data": {
    "pricing": {
      "job_application_fee": 200,
      "job_posting_fee": 1000,
      "premium_profile_fee": 500,
      "featured_job_fee": 2000,
      "subscription_monthly_fee": 5000,
      "subscription_yearly_fee": 50000,
      "platform_commission_percentage": 10
    },
    "enabled": {
      "payments_enabled": false,
      "job_application_fee_enabled": false,
      "job_posting_fee_enabled": false,
      "subscription_enabled": false
    },
    "mode": "free"
  }
}
```

### **2. Update Pricing**
```http
PATCH /api/admin/settings/pricing
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "job_application_fee": 300,
  "job_posting_fee": 1500,
  "premium_profile_fee": 700,
  "featured_job_fee": 2500,
  "subscription_monthly_fee": 6000,
  "subscription_yearly_fee": 60000,
  "platform_commission_percentage": 12
}
```

**Response:**
```json
{
  "success": true,
  "message": "Pricing updated successfully",
  "data": {
    "job_application_fee": 300,
    "job_posting_fee": 1500,
    ...
  }
}
```

### **3. Reset to Defaults**
```http
POST /api/admin/settings/reset-defaults
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "success": true,
  "message": "Settings reset to defaults successfully",
  "data": { ... }
}
```

---

## 💻 **CODE USAGE**

### **In Your Controllers:**

#### **Before (Old Way):**
```php
❌ $fee = env('PAYMENT_ACTIONS_JOB_POST_AMOUNT', 1000);
```

#### **After (New Way):**
```php
✅ use App\Models\AdminSetting;

$settings = AdminSetting::getSingleton();
$fee = $settings->getJobPostingFee();

// Or get all pricing at once:
$pricing = $settings->getAllPricing();
// Returns:
// [
//   'job_application_fee' => 200,
//   'job_posting_fee' => 1000,
//   'premium_profile_fee' => 500,
//   ...
// ]
```

### **Available Getter Methods:**

```php
$settings = AdminSetting::getSingleton();

// Individual fees
$settings->getJobApplicationFee();      // 200
$settings->getJobPostingFee();          // 1000
$settings->getPremiumProfileFee();      // 500
$settings->getFeaturedJobFee();         // 2000
$settings->getMonthlySubscriptionFee(); // 5000
$settings->getYearlySubscriptionFee();  // 50000
$settings->getPlatformCommission();     // 10

// All pricing at once
$settings->getAllPricing();  // array of all fees

// Check enabled states
$settings->isFreeMode();                      // true/false
$settings->isJobPostingFeeRequired();         // true/false
$settings->isJobApplicationFeeRequired();     // true/false
$settings->isSubscriptionRequired();          // true/false
```

---

## 🎨 **ADMIN PANEL UI (Frontend)**

### **Add to Admin Panel:**

**File:** `/mnt/e/MSILU/fundi/admin-panel/src/features/settings/PricingSettings.tsx`

```typescript
import { useState, useEffect } from 'react';
import { apiClient } from '@/lib/api-client';
import { API_ENDPOINTS } from '@/lib/endpoints';

export function PricingSettings() {
  const [pricing, setPricing] = useState({
    job_application_fee: 200,
    job_posting_fee: 1000,
    premium_profile_fee: 500,
    featured_job_fee: 2000,
    subscription_monthly_fee: 5000,
    subscription_yearly_fee: 50000,
    platform_commission_percentage: 10,
  });

  useEffect(() => {
    loadPricing();
  }, []);

  const loadPricing = async () => {
    const response = await apiClient.get(API_ENDPOINTS.ADMIN.SETTINGS_PRICING);
    if (response.success) {
      setPricing(response.data.pricing);
    }
  };

  const handleSave = async () => {
    const response = await apiClient.patch(
      API_ENDPOINTS.ADMIN.SETTINGS_PRICING,
      pricing
    );
    
    if (response.success) {
      alert('Pricing updated successfully!');
    }
  };

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold">Platform Pricing</h2>
      
      <div className="grid grid-cols-2 gap-4">
        <div>
          <label>Job Application Fee (TZS)</label>
          <input
            type="number"
            value={pricing.job_application_fee}
            onChange={(e) => setPricing({
              ...pricing,
              job_application_fee: parseFloat(e.target.value)
            })}
          />
        </div>

        <div>
          <label>Job Posting Fee (TZS)</label>
          <input
            type="number"
            value={pricing.job_posting_fee}
            onChange={(e) => setPricing({
              ...pricing,
              job_posting_fee: parseFloat(e.target.value)
            })}
          />
        </div>

        <div>
          <label>Premium Profile Fee (TZS)</label>
          <input
            type="number"
            value={pricing.premium_profile_fee}
            onChange={(e) => setPricing({
              ...pricing,
              premium_profile_fee: parseFloat(e.target.value)
            })}
          />
        </div>

        <div>
          <label>Featured Job Fee (TZS)</label>
          <input
            type="number"
            value={pricing.featured_job_fee}
            onChange={(e) => setPricing({
              ...pricing,
              featured_job_fee: parseFloat(e.target.value)
            })}
          />
        </div>

        <div>
          <label>Monthly Subscription (TZS)</label>
          <input
            type="number"
            value={pricing.subscription_monthly_fee}
            onChange={(e) => setPricing({
              ...pricing,
              subscription_monthly_fee: parseFloat(e.target.value)
            })}
          />
        </div>

        <div>
          <label>Yearly Subscription (TZS)</label>
          <input
            type="number"
            value={pricing.subscription_yearly_fee}
            onChange={(e) => setPricing({
              ...pricing,
              subscription_yearly_fee: parseFloat(e.target.value)
            })}
          />
        </div>

        <div className="col-span-2">
          <label>Platform Commission (%)</label>
          <input
            type="number"
            max="100"
            value={pricing.platform_commission_percentage}
            onChange={(e) => setPricing({
              ...pricing,
              platform_commission_percentage: parseFloat(e.target.value)
            })}
          />
        </div>
      </div>

      <button
        onClick={handleSave}
        className="px-4 py-2 bg-blue-600 text-white rounded"
      >
        Save Pricing
      </button>
    </div>
  );
}
```

### **Add Endpoint to Admin Panel:**

**File:** `/mnt/e/MSILU/fundi/admin-panel/src/lib/endpoints.ts`

```typescript
// Add to ADMIN object:
ADMIN: {
  ...
  SETTINGS: '/admin/settings',
  SETTINGS_PRICING: '/admin/settings/pricing',
  SETTINGS_RESET: '/admin/settings/reset-defaults',
}
```

---

## 🔄 **MIGRATION STEPS**

### **Step 1: Update .env**
```bash
# Comment out pricing variables
# Keep only general payment config
```

### **Step 2: Run Migration**
```bash
cd /var/www/html/myprojects/fundi-api
php artisan migrate
```

### **Step 3: Verify Database**
```bash
php artisan tinker

>>> $settings = \App\Models\AdminSetting::getSingleton();
>>> $settings->getAllPricing();

# Should show:
# [
#   "job_application_fee" => 200.00,
#   "job_posting_fee" => 1000.00,
#   ...
# ]
```

### **Step 4: Test API Endpoints**
```bash
# Get pricing
curl -X GET http://localhost:8000/api/admin/settings/pricing \
  -H "Authorization: Bearer your-admin-token"

# Update pricing
curl -X PATCH http://localhost:8000/api/admin/settings/pricing \
  -H "Authorization: Bearer your-admin-token" \
  -H "Content-Type: application/json" \
  -d '{
    "job_posting_fee": 1500,
    "premium_profile_fee": 600
  }'
```

### **Step 5: Add UI to Admin Panel**
- Create `PricingSettings.tsx` component
- Add route `/admin/settings/pricing`
- Link from settings menu

---

## ✅ **VERIFICATION**

### **Checklist:**

- [ ] .env pricing variables removed/commented
- [ ] Migration run successfully
- [ ] `AdminSetting::getSingleton()` returns default values
- [ ] API endpoints work (GET/PATCH pricing)
- [ ] Admin panel can view pricing
- [ ] Admin panel can update pricing
- [ ] Changes reflect immediately (no server restart)

---

## 🎉 **BENEFITS OF THIS APPROACH**

| Aspect | Old (.env) | New (Admin Panel) |
|--------|-----------|-------------------|
| **Accessibility** | Need server access | Web UI access |
| **Updates** | Require restart | Instant |
| **User-Friendly** | Technical | Visual UI |
| **Validation** | None | Built-in |
| **Audit Trail** | No | Yes |
| **Rollback** | Manual | Reset button |
| **Multi-Admin** | No | Yes |
| **Testing** | Hard | Easy |

---

## 📝 **SUMMARY**

**✅ Pricing is now controlled from Admin Panel!**

1. **Remove** pricing variables from `.env`
2. **Run** migration to add database fields
3. **Use** `AdminSetting::getSingleton()` in code
4. **Manage** all pricing from admin panel UI

**No more server access needed to change prices! 🎉**

---

**Last Updated:** October 9, 2025  
**Status:** Production Ready ✅



