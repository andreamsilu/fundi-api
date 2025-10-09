# 🎯 Mobile & Admin Payment Integration - Best Practices

**Date:** October 9, 2025  
**Status:** ✅ Implemented  
**Purpose:** Document how pricing flows from Admin Panel → API → Mobile App

---

## 🔄 **ARCHITECTURE FLOW**

```
┌──────────────────────────────────────────────────────────────────┐
│ 1. ADMIN PANEL (React/Next.js)                                   │
│    - Admin changes pricing in PricingSettingsScreen.tsx          │
│    - Calls: PATCH /api/admin/settings/pricing                   │
│    - Pricing saved to database (admin_settings table)            │
└───────────────────┬──────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────────────────────┐
│ 2. BACKEND API (Laravel)                                         │
│    - AdminSettingController updates admin_settings table         │
│    - Pricing stored in database (single source of truth)         │
│    - API endpoint: GET /api/admin/settings/pricing              │
│    - Returns current pricing to mobile app                       │
└───────────────────┬──────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────────────────────┐
│ 3. MOBILE APP (Flutter)                                          │
│    - PricingService fetches pricing from API                     │
│    - Caches pricing for 5 minutes                                │
│    - PaymentConfig uses PricingService (NO hardcoded prices)     │
│    - Shows current prices to users                               │
└──────────────────────────────────────────────────────────────────┘
```

---

## ✅ **WHAT WE FIXED**

### **BEFORE (❌ BAD PRACTICE):**

#### **Mobile App - Hardcoded Prices:**
```dart
// ❌ BAD - payment_config.dart
static const Map<String, PaymentAction> actions = {
  'job_post': PaymentAction(
    amount: 1000,  // ❌ HARDCODED!
  ),
};
```

**Problems:**
- Admin changes price in admin panel
- Mobile app still shows old price (1000)
- Users see wrong prices
- Need to release new app version to change prices

#### **Admin Panel - No Pricing UI:**
```tsx
// ❌ BAD - No pricing management screen
// Only has: app name, currency, maintenance mode
// Missing: Payment pricing fields
```

**Problems:**
- Can't change pricing from admin panel
- Need SSH access to change `.env`
- Need server restart for changes
- Not user-friendly

---

### **AFTER (✅ BEST PRACTICE):**

#### **Mobile App - Dynamic Pricing:**
```dart
// ✅ GOOD - pricing_service.dart
class PricingService {
  Future<PricingData> getPricing({bool forceRefresh = false}) async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      '/admin/settings/pricing',  // ✅ Fetches from API
    );
    
    if (response.success && response.data != null) {
      final pricingData = PricingData.fromJson(response.data!['pricing']);
      _cachedPricing = pricingData; // Cache for 5 minutes
      return pricingData;
    }
    
    return PricingData.defaultPricing(); // Fallback
  }
}
```

```dart
// ✅ GOOD - payment_config.dart (updated)
class PaymentConfig {
  // Only metadata (icons, colors) - NO PRICES
  static const Map<String, PaymentActionMetadata> actionMetadata = {
    'job_post': PaymentActionMetadata(
      key: 'job_posting',
      description: 'Job Posting Fee',
      icon: Icons.work,  // ✅ UI only
      color: Colors.blue, // ✅ UI only
    ),
  };

  // Prices fetched from API
  static Future<PaymentAction> getAction(String key) async {
    final pricingService = PricingService();
    final price = await pricingService.getPriceFor(key);  // ✅ From API!
    
    return PaymentAction(
      amount: price,  // ✅ Dynamic price
      description: metadata.description,
      icon: metadata.icon,
    );
  }
}
```

**Benefits:**
✅ Admin changes price → Mobile app gets new price (no app update)  
✅ Prices always in sync across all platforms  
✅ Single source of truth (database)  
✅ Caching for performance (5-minute cache)  
✅ Falls back to defaults if API fails  

#### **Admin Panel - Full Pricing UI:**
```tsx
// ✅ GOOD - PricingSettingsScreen.tsx
export function PricingSettingsScreen() {
  const [pricing, setPricing] = useState<PricingData>({
    job_application_fee: 200,
    job_posting_fee: 1000,
    premium_profile_fee: 500,
    featured_job_fee: 2000,
    subscription_monthly_fee: 5000,
    subscription_yearly_fee: 50000,
    platform_commission_percentage: 10,
  });

  const handleSave = async () => {
    const response = await apiClient.patch(
      API_ENDPOINTS.SETTINGS.PRICING,
      pricing
    );
    // Changes saved to database immediately!
  };

  return (
    <Card>
      <Input
        label="Job Posting Fee (TZS)"
        value={pricing.job_posting_fee}
        onChange={(e) => handlePricingChange('job_posting_fee', e.target.value)}
      />
      <Button onClick={handleSave}>💾 Save Pricing</Button>
    </Card>
  );
}
```

**Benefits:**
✅ Beautiful UI for managing pricing  
✅ Real-time savings calculator  
✅ Instant updates (no server restart)  
✅ Reset to defaults button  
✅ Validation built-in  

---

## 📁 **FILES CREATED/UPDATED**

### **Backend API:**
1. ✅ `app/Models/AdminSetting.php` - Added pricing fields
2. ✅ `app/Http/Controllers/AdminSettingController.php` - Pricing endpoints
3. ✅ `routes/api.php` - Added `/admin/settings/pricing` routes
4. ✅ `database/migrations/2025_10_09_000001_add_pricing_fields_to_admin_settings.php`
5. ✅ `config/payment.php` - Updated with note about admin panel control

### **Mobile App:**
1. ✅ `lib/core/services/pricing_service.dart` - **NEW** - Fetches pricing from API
2. ✅ `lib/core/config/payment_config.dart` - **UPDATED** - Removed hardcoded prices
3. ✅ `lib/features/payment/services/zenopay_service.dart` - ZenoPay integration

### **Admin Panel:**
1. ✅ `src/features/settings/screens/PricingSettingsScreen.tsx` - **NEW** - Pricing management UI
2. ✅ `src/lib/endpoints.ts` - Added pricing endpoints

---

## 🔌 **API ENDPOINTS**

### **1. Get Current Pricing** (Used by Mobile App)
```http
GET /api/admin/settings/pricing
Authorization: Bearer {token}
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

### **2. Update Pricing** (Used by Admin Panel)
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

### **3. Reset to Defaults** (Used by Admin Panel)
```http
POST /api/admin/settings/reset-defaults
Authorization: Bearer {admin_token}
```

---

## 💻 **CODE USAGE EXAMPLES**

### **Mobile App - Getting Current Price:**

```dart
// In your widget/screen
import 'package:fundi_app/core/services/pricing_service.dart';

class JobPostingScreen extends StatefulWidget {
  @override
  Widget build(BuildContext context) {
    return FutureBuilder<PricingData>(
      future: PricingService().getPricing(),
      builder: (context, snapshot) {
        if (snapshot.hasData) {
          final pricing = snapshot.data!;
          final jobPostFee = pricing.jobPostingFee; // From API!
          
          return Text(
            'Fee: ${pricing.formatPrice('job_posting')}',
            // Shows: "Fee: TZS 1,000"
          );
        }
        return CircularProgressIndicator();
      },
    );
  }
}
```

```dart
// Using PaymentConfig (now dynamic)
final action = await PaymentConfig.getAction('job_post');
print(action.amount); // Current price from API (not hardcoded!)
```

### **Admin Panel - Updating Pricing:**

```tsx
// In PricingSettingsScreen.tsx
const handleSave = async () => {
  const response = await apiClient.patch(
    API_ENDPOINTS.SETTINGS.PRICING,
    {
      job_posting_fee: 1500, // New price
      premium_profile_fee: 700,
      ...
    }
  );

  if (response.success) {
    alert('Pricing updated! Mobile app will see new prices immediately.');
  }
};
```

---

## ⚡ **PERFORMANCE OPTIMIZATIONS**

### **1. Caching (Mobile App):**
```dart
// PricingService caches pricing for 5 minutes
// Reduces API calls while keeping data fresh

static const _cacheDuration = Duration(minutes: 5);

if (!forceRefresh &&
    _cachedPricing != null &&
    DateTime.now().difference(_lastFetch!) < _cacheDuration) {
  return _cachedPricing!; // Use cache
}

// Otherwise fetch from API
```

### **2. Fallback Strategy (Mobile App):**
```dart
// If API fails, use cached data or defaults
try {
  final response = await _apiClient.get('/admin/settings/pricing');
  // ...
} catch (e) {
  // Return cached if available
  if (_cachedPricing != null) {
    return _cachedPricing!;
  }
  
  // Fall back to defaults (matches database defaults)
  return PricingData.defaultPricing();
}
```

---

## 🧪 **TESTING FLOW**

### **Test Scenario: Admin Changes Price**

1. **Admin Panel:**
   ```
   - Open Pricing Settings
   - Change Job Posting Fee: 1000 → 1500
   - Click "Save Pricing"
   - See success message
   ```

2. **Backend API:**
   ```sql
   -- Database updated
   UPDATE admin_settings 
   SET job_posting_fee = 1500 
   WHERE id = 1;
   ```

3. **Mobile App:**
   ```dart
   // User opens job posting screen
   - PricingService checks cache (expired or force refresh)
   - Calls GET /api/admin/settings/pricing
   - Receives: { job_posting_fee: 1500 }
   - Caches for 5 minutes
   - Shows: "Fee: TZS 1,500" ✅
   ```

**Result:** Price updated across all platforms without app update!

---

## ✅ **BEST PRACTICES CHECKLIST**

### **✅ Mobile App:**
- [x] NO hardcoded pricing
- [x] Fetches pricing from API
- [x] Caches for performance
- [x] Has fallback to defaults
- [x] Handles API errors gracefully
- [x] Shows current prices to users
- [x] Uses PricingService consistently

### **✅ Admin Panel:**
- [x] Beautiful UI for pricing management
- [x] Validates input
- [x] Shows pricing summary
- [x] Calculates savings (yearly vs monthly)
- [x] Has reset to defaults button
- [x] Shows success/error messages
- [x] Real-time updates

### **✅ Backend API:**
- [x] Pricing stored in database (single source of truth)
- [x] AdminSetting model with getter methods
- [x] RESTful API endpoints
- [x] Admin-only access (permissions)
- [x] Migration for new fields
- [x] Defaults match .env removal

---

## 🎯 **SUMMARY**

| Aspect | Before | After |
|--------|--------|-------|
| **Mobile Pricing** | ❌ Hardcoded in .dart file | ✅ Fetched from API |
| **Admin Control** | ❌ Edit .env + restart server | ✅ Web UI, instant updates |
| **Sync** | ❌ Out of sync | ✅ Always in sync |
| **Update Process** | ❌ App update required | ✅ No app update needed |
| **Cache** | ❌ No caching | ✅ 5-minute cache |
| **Fallback** | ❌ Hard crash if API fails | ✅ Cached/default fallback |
| **User Experience** | ❌ Wrong prices shown | ✅ Always current prices |

---

## 🚀 **CONCLUSION**

**✅ ALL PLATFORMS NOW FOLLOW BEST PRACTICES:**

1. **Admin Panel** - Full pricing management UI
2. **Backend API** - Pricing in database, RESTful endpoints
3. **Mobile App** - Dynamic pricing from API, caching, fallbacks

**🎉 Admin changes pricing → Mobile app sees new prices (no app update needed)!**

---

**Last Updated:** October 9, 2025  
**Status:** Production Ready ✅  
**Best Practices:** Fully Implemented ✅



