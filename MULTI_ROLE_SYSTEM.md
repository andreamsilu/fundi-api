# 🔄 Multi-Role User System

## Overview

The Fundi App now supports a **flexible multi-role user system** where users can dynamically switch between different roles (Customer/Fundi) rather than being locked into a single role. This creates a more fluid marketplace where anyone can be both a service provider and service requester.

## 🎯 Key Concepts

### **Role Flexibility**
- **Users can have multiple roles simultaneously**
- **Dynamic role switching during app usage**
- **Separate profiles and ratings for each role context**
- **Business model compatibility based on current active role**

### **Role Types**
1. **Client Role** - Can request services, post jobs
2. **Fundi Role** - Can provide services, accept jobs
3. **Business Roles** - Can act as both client and provider in B2B scenarios

## 🚀 How It Works

### **Step 1: User Registration**
Users can select multiple roles during registration:
```json
{
  "name": "John Doe",
  "phone": "+256700123456",
  "roles": ["client", "fundi"],
  "user_type": "individual"
}
```

### **Step 2: Dynamic Role Switching**
Users can switch between roles in the app:
```json
POST /api/v1/uac/switch-role
{
  "role": "fundi"
}
```

### **Step 3: Context-Aware Operations**
- **As Client**: Can post jobs, request services
- **As Fundi**: Can browse jobs, accept bookings
- **App automatically adapts** based on current role

## 📱 API Endpoints

### **User Role Switching (UAC)**
```http
GET    /api/v1/uac/available-roles      # Get available roles
POST   /api/v1/uac/switch-role          # Switch current role
GET    /api/v1/uac/profile-status       # Get profile completion for current role
GET    /api/v1/uac/role-statistics      # Get statistics for both roles
```

### **Example Responses**

#### **Get Available Roles**
```json
{
  "data": {
    "available_roles": ["client", "fundi"],
    "current_role": "client",
    "can_switch": true
  }
}
```

#### **Role Statistics**
```json
{
  "data": {
    "fundi": {
      "rating": 4.8,
      "reviews_count": 15,
      "completed_jobs": 23,
      "total_earnings": 1250.00
    },
    "client": {
      "rating": 4.9,
      "reviews_count": 8,
      "posted_jobs": 12,
      "completed_jobs": 10
    }
  }
}
```

## 🔧 User Model Methods

### **Role Checking Methods**
```php
// Check if user can act as specific roles
$user->canActAsFundi();      // Can provide services
$user->canActAsClient();     // Can request services

// Check current active role
$user->isActingAsFundi();    // Currently acting as fundi
$user->isActingAsClient();   // Currently acting as client

// Get available roles for switching
$user->getAvailableRoles();  // ['client', 'fundi']
```

### **Role Switching Methods**
```php
// Switch to a different role
$user->switchRole('fundi');

// Get current active role
$user->getCurrentRole();     // 'fundi' or 'client'
```

### **Profile Management**
```php
// Get profile completion for current role
$user->getCurrentRoleProfileCompletion();  // 85

// Check if profile is complete for current role
$user->hasCompletedCurrentRoleProfile();   // true/false

// Get required fields for current role
$user->getRequiredProfileFields();         // ['name', 'phone', 'bio', 'skills']
```

### **Rating & Statistics**
```php
// Get ratings for different roles
$user->fundi_rating;         // Rating as service provider
$user->client_rating;        // Rating as service requester
$user->current_role_rating;  // Rating for current active role

// Get comprehensive statistics
$user->getRoleStatistics();  // Complete stats for both roles
```

## 🏗️ Business Model Integration

### **Dynamic Compatibility**
The system automatically checks business model compatibility based on the user's **current active role**:

```php
// User acting as client
if ($user->isActingAsClient() && $user->canBeClientInBusinessModel('c2c')) {
    // Can post C2C jobs
}

// User acting as fundi
if ($user->isActingAsFundi() && $user->canBeProviderInBusinessModel('b2c')) {
    // Can accept B2C jobs
}
```

### **Role-Based Permissions**
```php
// Check if user can currently post jobs
$user->canPostJobs();        // true if acting as client

// Check if user can currently accept jobs
$user->canAcceptJobs();      // true if acting as fundi
```

## 📊 Database Structure

### **User Roles Table (Spatie)**
```sql
-- Users can have multiple roles
user_has_roles:
- user_id: 1
- role_id: 1 (client)
- user_id: 1  
- role_id: 2 (fundi)
```

### **Session-Based Role Switching**
```php
// Store current role in session
session(['current_role' => 'fundi']);

// Retrieve current role
$currentRole = session('current_role') ?? $user->getPrimaryRoleAttribute();
```

## 🔄 Use Cases

### **Example 1: Individual Multi-Role User**
```php
// John registers as both client and fundi
$john = User::create([
    'name' => 'John Doe',
    'roles' => ['client', 'fundi']
]);

// John acts as client to hire a plumber
$john->switchRole('client');
$john->canPostJobs();        // true
$john->canAcceptJobs();      // false

// John switches to fundi to offer cleaning services
$john->switchRole('fundi');
$john->canPostJobs();        // false
$john->canAcceptJobs();      // true
```

### **Example 2: Business Multi-Role User**
```php
// ABC Company can both request and provide services
$abcCompany = User::create([
    'name' => 'ABC Company',
    'user_type' => 'business',
    'roles' => ['businessClient', 'businessProvider']
]);

// Requesting construction services
$abcCompany->switchRole('businessClient');
$abcCompany->canPostJobs();        // true

// Providing consulting services
$abcCompany->switchRole('businessProvider');
$abcCompany->canAcceptJobs();      // true
```

## 🎨 Frontend Integration

### **Role Switching UI**
```javascript
// Get available roles
const { data } = await api.get('/roles/available');

// Show role switcher if user has multiple roles
if (data.can_switch) {
    renderRoleSwitcher(data.available_roles, data.current_role);
}

// Handle role switching
const switchRole = async (newRole) => {
    await api.post('/roles/switch', { role: newRole });
    // Refresh UI based on new role
    refreshUserInterface();
};
```

### **Context-Aware UI**
```javascript
// Show different UI based on current role
if (user.current_role === 'client') {
    renderClientDashboard();  // Job posting, service requests
} else if (user.current_role === 'fundi') {
    renderFundiDashboard();   // Available jobs, bookings
}
```

## 🔒 Security & Permissions

### **Role-Based Access Control**
```php
// Routes automatically check current role
Route::middleware('role:client')->group(function () {
    Route::post('jobs', [JobController::class, 'store']);
});

// Controllers verify current role context
public function store(Request $request) {
    if (!$this->user->isActingAsClient()) {
        abort(403, 'Must be acting as client to post jobs');
    }
}
```

### **Permission Validation**
```php
// Check permissions for current role
if ($user->isActingAsFundi() && $user->can('accept jobs')) {
    // Allow job acceptance
}

if ($user->isActingAsClient() && $user->can('create jobs')) {
    // Allow job creation
}
```

## 🚀 Benefits

### **1. Increased User Engagement**
- Users can participate in multiple ways
- Higher retention through role flexibility
- More service opportunities

### **2. Better Market Dynamics**
- Fluid supply and demand
- Natural role switching based on needs
- Optimized service matching

### **3. Scalable Business Models**
- Supports all business models (C2C, B2C, C2B, B2B)
- Natural evolution of user relationships
- Platform-agnostic service facilitation

### **4. Enhanced User Experience**
- Single account for multiple purposes
- Seamless role transitions
- Context-aware interfaces

## 🔮 Future Enhancements

### **Advanced Role Management**
- **Role-specific settings** and preferences
- **Role-based notifications** and alerts
- **Role switching analytics** and insights

### **Smart Role Suggestions**
- **AI-powered role recommendations** based on user behavior
- **Automatic role optimization** for better matching
- **Role performance metrics** and suggestions

### **Enhanced Business Logic**
- **Role-based pricing** and commission structures
- **Role-specific verification** requirements
- **Advanced role permissions** and workflows

## 📝 Implementation Notes

### **Migration Considerations**
- Existing users maintain their current roles
- New multi-role functionality is additive
- Backward compatibility maintained

### **Performance Optimizations**
- Role queries optimized with database indexes
- Session-based role switching for fast access
- Cached role permissions and capabilities

### **Testing Strategy**
- **Unit tests** for role switching logic
- **Integration tests** for role-based workflows
- **User acceptance tests** for multi-role scenarios

---

## 🎯 Summary

The multi-role user system transforms the Fundi App from a static role-based platform into a **dynamic, fluid marketplace** where users can seamlessly switch between being service providers and service requesters. This creates a more engaging, scalable, and user-friendly ecosystem that naturally supports all business models while maintaining security and proper access control.

**Key Takeaway**: Users are no longer locked into single roles - they can be both customers and fundis, creating a truly flexible service marketplace.
