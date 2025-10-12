# 🔧 Job Permissions Fix - Customer vs Fundi Access Control

## Problem

Previously, **BOTH customers and fundis** had the `view_jobs` permission, which meant:
- ❌ Customers could browse ALL available jobs (doesn't make business sense)
- ❌ Customers could see jobs posted by other customers
- ❌ Platform behavior didn't match intended business logic

---

## ✅ Solution

### Corrected Permissions

| Role | view_jobs | create_jobs | edit_jobs | apply_jobs | Reasoning |
|------|-----------|-------------|-----------|-----------|-----------|
| **Customer** | ❌ NO | ✅ YES | ✅ YES | ❌ NO | Customers POST jobs, don't browse them |
| **Fundi** | ✅ YES | ❌ NO | ❌ NO | ✅ YES | Fundis BROWSE jobs to find work |
| **Admin** | ✅ YES | ✅ YES | ✅ YES | ✅ YES | Full access |

---

## 🎯 Correct Business Logic

### **CUSTOMER (Job Poster)**

**What They CAN Do:**
- ✅ POST new jobs (`POST /jobs`)
- ✅ VIEW their own posted jobs (`GET /jobs/my-jobs`)
- ✅ VIEW job details for jobs they own (`GET /jobs/{id}` - if they own it)
- ✅ EDIT their own jobs (`PATCH /jobs/{id}`)
- ✅ DELETE their own jobs (`DELETE /jobs/{id}`)
- ✅ VIEW fundis to hire (`GET /feeds/fundis`)
- ✅ MANAGE applications to their jobs
- ✅ APPROVE/REJECT applications

**What They CANNOT Do:**
- ❌ BROWSE all available jobs (`GET /jobs`)
- ❌ VIEW jobs posted by other customers
- ❌ APPLY to jobs (they post jobs, not apply to them)
- ❌ ACCESS job feed (`GET /feeds/jobs`)

---

### **FUNDI (Service Provider)**

**What They CAN Do:**
- ✅ BROWSE all available jobs (`GET /jobs`)
- ✅ VIEW job feed (`GET /feeds/jobs`)
- ✅ SEARCH jobs (`search_jobs` permission)
- ✅ VIEW job details before applying (`GET /jobs/{id}`)
- ✅ APPLY to jobs (`POST /jobs/{id}/apply`)
- ✅ VIEW their applications (`GET /job-applications/my-applications`)
- ✅ CREATE & manage portfolio
- ✅ RATE customers after job completion

**What They CANNOT Do:**
- ❌ POST new jobs (they apply to jobs, not create them)
- ❌ EDIT jobs (only job owner can edit)
- ❌ DELETE jobs (only job owner can delete)

---

## 🔄 Workflow Examples

### **Scenario 1: Customer Posts a Job**

```
Customer Action:
1. Logs in as customer (0654289825)
2. Navigates to "Post Job"
3. Creates job: "Fix Kitchen Sink" - 500,000 TZS
4. Views at GET /jobs/my-jobs ✅ (sees their own jobs)
5. Tries GET /jobs ❌ 403 Forbidden (no view_jobs permission)

Result: Customer can only see THEIR OWN jobs
```

---

### **Scenario 2: Fundi Browses & Applies**

```
Fundi Action:
1. Logs in as fundi (0654289827)
2. Navigates to "Browse Jobs"
3. Views GET /jobs ✅ (sees ALL available jobs)
4. Views GET /feeds/jobs ✅ (job feed with filters)
5. Selects job: "Fix Kitchen Sink"
6. Views GET /jobs/123 ✅ (sees job details)
7. Applies: POST /jobs/123/apply ✅

Result: Fundi can browse and apply to ANY job
```

---

### **Scenario 3: Customer Views Their Posted Job**

```
Customer Action:
1. Customer posted job ID 123
2. Views GET /jobs/123 ✅ (allowed because they own it)
3. Sees applications from fundis
4. Selects best fundi
5. Approves application ✅

Result: Customer can view details of jobs THEY posted
```

---

## 🔐 API Endpoint Access Control

### **Job Browse Endpoints** (Fundis Only)
```
GET /jobs                    → List all available jobs (Fundis only)
GET /feeds/jobs              → Job feed with filters (Fundis only)
GET /feeds/jobs/{id}         → Job details from feed (Fundis only)
```

**Permission Required:** `view_jobs`  
**Who Has It:** Fundis, Admins

---

### **Own Jobs Endpoints** (Everyone)
```
GET /jobs/my-jobs            → Customer's posted jobs OR Fundi's applied jobs
```

**Permission Required:** None (all users can view their own)

---

### **Specific Job Endpoints** (Owner or Fundis)
```
GET /jobs/{id}               → Job owner OR users with view_jobs permission
PATCH /jobs/{id}             → Job owner only (edit_jobs permission)
DELETE /jobs/{id}            → Job owner only (delete_jobs permission)
```

**Access Logic in Controller:**
```php
$isOwner = $job->customer_id === $user->id;
$canView = $user->can('view_jobs') || $isOwner;
```

---

### **Job Creation Endpoints** (Customers Only)
```
POST /jobs                   → Create new job
```

**Permission Required:** `create_jobs`  
**Who Has It:** Customers, Admins

---

## 📱 Mobile App Impact

### **Customer App Screens:**

**Dashboard:**
- ✅ Shows "My Posted Jobs" section
- ✅ Button: "Post New Job"
- ❌ NO "Browse Jobs" section (removed)

**Navigation:**
- ✅ "My Jobs" → `/jobs/my-jobs`
- ✅ "Post Job" → `/jobs` (POST)
- ✅ "Find Fundis" → `/feeds/fundis`
- ❌ Removed "Browse Jobs"

---

### **Fundi App Screens:**

**Dashboard:**
- ✅ Shows "Available Jobs" section
- ✅ Button: "Browse Jobs"
- ✅ Shows "My Applications" section

**Navigation:**
- ✅ "Browse Jobs" → `/jobs` or `/feeds/jobs`
- ✅ "My Applications" → `/job-applications/my-applications`
- ✅ "My Portfolio" → `/portfolio/my-portfolio`

---

## 🛠️ Code Changes

### 1. RolePermissionSeeder.php
```php
// BEFORE (WRONG)
'customer' => [
    'create_jobs', 'view_jobs', ...  // ❌ Had view_jobs
]

// AFTER (CORRECT)
'customer' => [
    'create_jobs', 'edit_jobs', 'delete_jobs',
    'manage_job_applications', 'approve_job_applications',
    'view_fundis', 'view_portfolio', ...
    // ✅ Removed 'view_jobs'
]
```

### 2. api.php Routes
```php
// BEFORE
Route::get('/jobs/{id}', [JobController::class, 'show'])
    ->middleware('jwt.permission:view_jobs'); // ❌ Blocked customers from viewing own jobs

// AFTER
Route::get('/jobs/{id}', [JobController::class, 'show']); 
// ✅ Permission checked in controller (allows owner OR view_jobs)
```

### 3. JobController.php
```php
// Added ownership check in show() method
$isOwner = $job->customer_id === $user->id;
$canView = $user->can('view_jobs') || $isOwner;

if (!$canView) {
    return response()->json(['error' => 'Forbidden'], 403);
}
```

---

## 🧪 Testing

### Test as Customer (0654289825)
```bash
# Should FAIL (403 Forbidden)
GET /jobs                    → ❌ No permission

# Should SUCCEED
GET /jobs/my-jobs            → ✅ Shows only their posted jobs
POST /jobs                   → ✅ Can create jobs
GET /jobs/123                → ✅ If they own job 123
GET /feeds/fundis            → ✅ Can browse fundis to hire
```

### Test as Fundi (0654289827)
```bash
# Should SUCCEED
GET /jobs                    → ✅ Shows all available jobs
GET /feeds/jobs              → ✅ Job feed with filters
GET /jobs/123                → ✅ Can view any job details
POST /jobs/123/apply         → ✅ Can apply to jobs

# Should FAIL (403 Forbidden)
POST /jobs                   → ❌ No create_jobs permission
```

---

## 📊 Permission Summary

### **Customer Permissions (12 total):**
1. ✅ create_jobs
2. ✅ edit_jobs
3. ✅ delete_jobs
4. ✅ manage_job_applications
5. ✅ approve_job_applications
6. ✅ view_fundis
7. ✅ view_portfolio
8. ✅ create_ratings
9. ✅ send_messages
10. ✅ view_messages
11. ✅ view_notifications
12. ✅ view_categories

**Key Removals:**
- ❌ view_jobs (don't browse other people's jobs)

---

### **Fundi Permissions (12 total):**
1. ✅ view_jobs (browse all jobs)
2. ✅ apply_jobs
3. ✅ view_job_feeds
4. ✅ search_jobs
5. ✅ create_portfolio
6. ✅ edit_portfolio
7. ✅ view_portfolio
8. ✅ create_ratings
9. ✅ send_messages
10. ✅ view_messages
11. ✅ view_notifications
12. ✅ view_categories

**Key Additions:**
- ✅ view_jobs (can browse job marketplace)
- ✅ view_job_feeds (can see job feeds)
- ✅ search_jobs (can search for jobs)

---

## 🎯 Business Logic Rationale

### Why Customers SHOULD NOT Browse Jobs

**Real-World Analogy:**
- On **Upwork**: Clients (customers) post jobs, Freelancers (fundis) browse them
- On **TaskRabbit**: Customers request tasks, Taskers browse them
- On **Uber**: Passengers request rides, Drivers see requests

**Fundi Platform:**
- ✅ Customers POST jobs (need help)
- ✅ Fundis BROWSE jobs (looking for work)
- ❌ Customers browsing jobs makes no sense (they're not looking for work)

### What WOULD Make Sense

If you wanted customers to browse jobs, it would be for:
- **Price Reference** - "What do similar jobs cost?"
- **Quality Reference** - "What does good work look like?"

**Better Solution:**
- Show **completed jobs** as examples (read-only)
- Create separate "Browse Examples" endpoint
- Don't give access to active job postings

---

## 🔄 Migration Path

### For Existing Customers

If customers already have the `view_jobs` permission in production:

```bash
# Re-run the RolePermissionSeeder
php artisan db:seed --class=RolePermissionSeeder

# Or manually remove permission
php artisan tinker
>>> $customer = Role::where('name', 'customer')->first();
>>> $customer->revokePermissionTo('view_jobs');
```

---

## 📝 Summary

| What Changed | Before | After |
|--------------|--------|-------|
| **Customer Access** | Could browse all jobs ❌ | Can only see own jobs ✅ |
| **Fundi Access** | Could browse all jobs ✅ | Can browse all jobs ✅ (unchanged) |
| **GET /jobs** | Everyone | Fundis only |
| **GET /jobs/{id}** | Everyone | Owner OR Fundis |
| **GET /jobs/my-jobs** | Everyone | Everyone (no permission check) |

---

## ✅ Fixed!

**The platform now follows correct marketplace logic:**
- Customers post jobs and hire fundis
- Fundis browse jobs and apply
- Clear separation of concerns
- Proper access control

---

**Last Updated:** 2025-10-12  
**Issue:** Customer role incorrectly had view_jobs permission  
**Fix:** Removed view_jobs from customer, added controller-level ownership checks  
**Status:** ✅ Resolved

