# 🌱 Fundi API Database Seeders Documentation

## Overview

This document provides comprehensive details about all database seeders in the Fundi API. Each seeder populates specific tables with realistic test data that matches the production environment of the Tanzania Fundi marketplace.

---

## 📋 Table of Contents

1. [Seeding Order](#seeding-order)
2. [Seeder Details](#seeder-details)
3. [Running Seeders](#running-seeders)
4. [Test Credentials](#test-credentials)
5. [Data Statistics](#data-statistics)

---

## Seeding Order

Seeders are executed in strict dependency order to satisfy foreign key constraints:

### Phase 1: Foundation (No Dependencies)
1. **RolePermissionSeeder** - Roles & Permissions
2. **CategorySeeder** - Job Categories
3. **AdminSettingSeeder** - System Settings
4. **PaymentPlanSeeder** - Payment Plan Definitions

### Phase 2: Users & Profiles
5. **UserSeeder** - Users with Roles
6. **UserSessionSeeder** - Active User Sessions
7. **FundiProfileSeeder** - Fundi Professional Profiles
8. **FundiApplicationSeeder** - Fundi Applications
9. **FundiApplicationSectionSeeder** - Multi-step Application Data

### Phase 3: Jobs & Applications
10. **JobsSeeder** - Job Postings
11. **JobMediaSeeder** - Job Photos/Documents
12. **JobApplicationsSeeder** - Fundi Applications to Jobs

### Phase 4: Portfolio & Work
13. **PortfoliosSeeder** - Fundi Portfolio Items
14. **PortfolioMediaSeeder** - Portfolio Images/Videos
15. **WorkSubmissionSeeder** - Completed Work Submissions

### Phase 5: Payments & Transactions
16. **PaymentSeeder** - Payment Records
17. **PaymentTransactionSeeder** - Detailed Transaction Data
18. **UserSubscriptionSeeder** - Active/Expired Subscriptions

### Phase 6: Interactions & Feedback
19. **RatingReviewSeeder** - Customer Reviews

### Phase 7: Communications
20. **NotificationSeeder** - User Notifications

### Phase 8: Audit & Monitoring
21. **AuditLogSeeder** - Comprehensive Audit Trail

---

## Seeder Details

### 1. RolePermissionSeeder
**Table:** `roles`, `permissions`, `model_has_roles`, `role_has_permissions`  
**Migration:** `2025_09_25_074243_recreate_tables_for_laravel_permission.php`

**Purpose:** Sets up Spatie Laravel Permission system with roles and granular permissions.

**Roles Created:**
- **customer** - Can post jobs, view fundis, create ratings
- **fundi** - Can apply to jobs, manage portfolio, view jobs
- **admin** - Full system access
- **moderator** - Content moderation, user management (limited)
- **support** - View-only access for customer support

**Permissions Created:** 54 total permissions across:
- Job Management (10)
- Portfolio Management (7)
- Work Approval (3)
- User Management (7)
- Rating & Review (5)
- Messaging (4)
- Notification (4)
- Payment (4)
- System Administration (8)
- Category Management (5)
- Fundi Management (5)

---

### 2. CategorySeeder
**Table:** `categories`  
**Migration:** `2025_09_09_162943_create_categories_table.php`

**Purpose:** Creates job categories for the Tanzania market.

**Categories (12):**
1. Plumbing
2. Electrical
3. Carpentry
4. Masonry
5. Painting
6. Roofing
7. Flooring
8. HVAC
9. Landscaping
10. Cleaning
11. Moving
12. General Maintenance

---

### 3. AdminSettingSeeder
**Table:** `admin_settings`  
**Migration:** `2025_09_09_162912_create_admin_settings_table.php`

**Purpose:** Initializes system-wide settings.

**Default Configuration:**
```php
- payments_enabled: true
- payment_model: 'free' (no charges)
- subscription_enabled: false
- subscription_fee: 5000 TZS/month
- job_application_fee_enabled: false
- job_application_fee: 1000 TZS
- job_posting_fee_enabled: false
- job_posting_fee: 2000 TZS
```

---

### 4. PaymentPlanSeeder
**Table:** `payment_plans`  
**Migration:** `2025_01_15_000001_create_payment_plans_table.php`

**Purpose:** Defines available payment plans and pricing tiers.

**Plans (5):**
1. **Free Plan** - 0 TZS, unlimited features
2. **Basic Subscription** - 5,000 TZS/month, enhanced features
3. **Premium Subscription** - 50,000 TZS/year, all features + API
4. **Pay Per Job** - 500 TZS/job, pay as you go
5. **Pay Per Application** - 200 TZS/application, for fundis

---

### 5. UserSeeder
**Table:** `users`, `model_has_roles`  
**Migration:** `0001_01_01_000000_create_users_table.php`

**Purpose:** Creates test users across all roles.

**Users Created (12):**

| Phone | Role | Status | Password |
|-------|------|--------|----------|
| 0754289824 | Admin | Active | password123 |
| 0654289825 | Customer | Active | password123 |
| 0754289826 | Customer | Active | password123 |
| 0654289827 | Fundi | Active | password123 |
| 0754289828 | Fundi | Active | password123 |
| 0654289829 | Fundi | Inactive | password123 |
| 0754289830 | Customer | Inactive | password123 |
| 0654289831 | Fundi | Banned | password123 |
| 0754289832 | Fundi + Customer | Active | password123 |
| 0754289834 | Moderator | Active | password123 |
| 0654289835 | Support | Active | password123 |
| 0654289836 | Admin + Fundi | Active | password123 |

**All NIDA numbers:** 20 digits (Tanzania format)

---

### 6. UserSessionSeeder
**Table:** `user_sessions` (custom table)  
**Migration:** Custom migration for session tracking

**Purpose:** Creates active and expired user sessions for testing.

**Data Generated:**
- **Sessions per user:** 1-5 sessions
- **Status distribution:** 30% active, rest expired/terminated
- **Device types:** 60% desktop, 35% mobile, 5% tablet
- **Browsers:** 65% Chrome, 15% Firefox, 10% Safari, 8% Edge, 2% Opera
- **Operating Systems:** 50% Windows, 25% macOS, 10% Linux, 15% Mobile
- **Locations:** Tanzania cities (Dar es Salaam, Arusha, Mwanza, etc.)
- **Session duration:** 24 hours

---

### 7. FundiProfileSeeder
**Table:** `fundi_profiles`  
**Migration:** `2025_09_09_162901_create_fundi_profiles_table.php`

**Purpose:** Creates professional profiles for all fundi users.

**Profile Data:**
- **Full names:** Tanzania-style names (John Mwalimu, Mary Mkono, etc.)
- **Skills:** Category-matched skills (Plumbing, Electrical, etc.)
- **Experience:** 1-15 years
- **Location:** Tanzania GPS coordinates (Lat: ~-6.0, Lng: ~35.0)
- **VETA certificates:** Some have, some don't
- **Verification status:** Based on user status

---

### 8. FundiApplicationSeeder
**Table:** `fundi_applications`  
**Migration:** `2025_01_15_000000_create_fundi_applications_table.php`

**Purpose:** Creates applications from customers who want to become fundis.

**Application Data:**
- **Applicants:** 5 customers
- **Status:** pending, approved, rejected
- **Skills:** 4 skills per applicant
- **Languages:** 1-3 languages (Swahili, English, French, Arabic, Italian)
- **Locations:** Tanzania cities with specific districts
- **Portfolio images:** 0-4 images per application
- **VETA certificates:** Realistic format (VETA/NVA/YYYY/XXXXX)
- **Bio:** Professional descriptions
- **Rejection reasons:** For rejected applications

---

### 9. FundiApplicationSectionSeeder
**Table:** `fundi_application_sections`  
**Migration:** `2025_09_12_093001_create_fundi_application_sections_table.php`

**Purpose:** Breaks down fundi applications into progressive steps.

**Sections (5 per application):**
1. **personal_information** - Name, phone, email, NIDA, DOB, gender
2. **professional_details** - VETA cert, experience, specialization, employers
3. **skills_experience** - Skills, languages, bio, rates, tools
4. **documents** - NIDA photos, certificates, profile photo
5. **portfolio** - Images, project descriptions, client references

**Completion:** First 3 sections always complete, last 2 vary by application status

---

### 10. JobsSeeder
**Table:** `job_postings`  
**Migration:** `2025_09_10_195030_create_job_postings_table.php`

**Purpose:** Creates diverse job postings from customers.

**Jobs Created (10):**
1. Kitchen Renovation - 65M TZS, 30 days, Open
2. Bathroom Remodeling - 39M TZS, 21 days, Open
3. Living Room Painting - 13M TZS, 14 days, In Progress
4. Garden Landscaping - 31.2M TZS, 45 days, Open
5. Roof Repair - 20.8M TZS, 7 days, Urgent
6. Electrical Installation - 9.1M TZS, 10 days, Open
7. Flooring Installation - 46.8M TZS, 25 days, Open
8. Fence Installation - 15.6M TZS, 18 days, Completed
9. Plumbing Repair - 6.5M TZS, 5 days, Open
10. Window Installation - 57.2M TZS, 35 days, Open

**All locations:** Dar es Salaam coordinates (-6.7924, 39.2083)

---

### 11. JobMediaSeeder
**Table:** `job_media`  
**Migration:** `2025_09_09_162911_create_job_media_table.php`

**Purpose:** Adds photos, documents, and videos to job postings.

**Media per Job:** 2-5 items
**Media Types:**
- **Images** (70%) - Site photos, reference designs, current state
- **Documents** (20%) - Requirements PDFs, specifications
- **Videos** (10%) - Site walkthroughs, requirement videos

**File Sizes:**
- Images: 500KB - 5MB
- Documents: 100KB - 2MB
- Videos: 5MB - 50MB

---

### 12. JobApplicationsSeeder
**Table:** `job_applications`  
**Migration:** `2025_09_09_162902_create_job_applications_table.php`

**Purpose:** Creates applications from fundis to job postings.

**Applications:** 1-3 per job
**Data Included:**
- Requirements text
- Budget breakdown (materials, labor, permits)
- Estimated time (days)
- Status: pending, accepted, rejected
- No duplicate applications (same fundi + same job)

---

### 13. PortfoliosSeeder
**Table:** `portfolio`  
**Migration:** `2025_09_09_162902_create_portfolio_table.php`

**Purpose:** Creates portfolio items showcasing fundi work.

**Portfolio Items:** 2-4 per fundi
**Templates (10):**
1. Modern Kitchen Renovation - 120 hrs, 25K, Approved
2. Luxury Bathroom Remodel - 80 hrs, 18K, Approved
3. Commercial Office Painting - 200 hrs, 15K, Approved
4. Garden Landscaping Design - 150 hrs, 12K, Pending
5. Roof Repair - 40 hrs, 8K, Approved
6. Electrical Panel Upgrade - 24 hrs, 5K, Approved
7. Hardwood Flooring - 100 hrs, 20K, Rejected
8. Fence Installation - 60 hrs, 6K, Approved
9. Plumbing System Overhaul - 80 hrs, 12K, Pending
10. Window Replacement - 90 hrs, 25K, Approved

**Status:**
- Approved items are visible
- Pending items not visible
- Rejected items have rejection reasons

---

### 14. PortfolioMediaSeeder
**Table:** `portfolio_media`  
**Migration:** `2025_09_09_162911_create_portfolio_media_table.php`

**Purpose:** Adds high-quality images and videos to portfolio items.

**Media per Portfolio:** 3-8 items
**Media Distribution:**
- 90% Images (800KB - 6MB high quality)
- 10% Videos (10MB - 100MB)

**Captions:**
- Before photos
- Work in progress
- Final results
- Detail shots
- Client satisfaction

**First image:** Always marked as featured

---

### 15. WorkSubmissionSeeder
**Table:** `work_submissions`  
**Migration:** `2025_09_12_060636_create_work_submissions_table.php`

**Purpose:** Creates submitted work for customer approval.

**Submissions:** 1-3 per job
**Data:**
- Completion descriptions (20 templates)
- Work images (2 per submission)
- Status: submitted, approved, rejected
- Reviewer tracking (customer ID)
- Rejection reasons for rejected work

---

### 16. PaymentSeeder
**Table:** `payments`  
**Migration:** `2025_09_09_162923_create_payments_table.php`

**Purpose:** Creates payment records for various transaction types.

**Payment Types:**
1. **Job Payments** - For completed jobs (full budget)
2. **Subscriptions** - 5,000 TZS monthly (30% of fundis)
3. **Application Fees** - 1,000 TZS (50% of accepted applications)
4. **Job Posting Fees** - 2,000 TZS (40% of jobs)

**Status Distribution:**
- ✅ 85% Completed
- ⏳ 10% Pending
- ❌ 5% Failed

**References:** PAY[UNIQID][RANDOM] format

---

### 17. PaymentTransactionSeeder
**Table:** `payment_transactions`  
**Migration:** `2025_01_15_000003_create_payment_transactions_table.php`

**Purpose:** Creates detailed transaction records for each payment.

**Gateway Distribution:**
- ZenoPay (mobile money aggregator)
- M-Pesa (Vodacom)
- Tigo Pesa
- Airtel Money
- Card payments
- Bank transfers

**Transaction Data:**
- Unique transaction IDs
- Gateway references
- Payer details (phone, name)
- Gateway fees (1-2.5% based on method)
- Net amounts
- Callback data
- Error messages for failed transactions

---

### 18. UserSubscriptionSeeder
**Table:** `user_subscriptions`  
**Migration:** `2025_01_15_000002_create_user_subscriptions_table.php`

**Purpose:** Creates active and expired user subscriptions.

**Coverage:** 30% of users have subscriptions
**Status Distribution:**
- 80% Active
- 15% Expired
- 5% Cancelled

**Subscription Details:**
- Start date: 1-90 days ago
- Duration: Based on plan (monthly/yearly)
- Auto-renew: 70% enabled for active
- Payment methods: Mobile money (dominant), cards, bank transfers
- Transaction IDs: SUB-[UNIQID]-[RANDOM]

---

### 19. RatingReviewSeeder
**Table:** `ratings_reviews`  
**Migration:** `2025_09_09_162912_create_ratings_reviews_table.php`

**Purpose:** Creates customer reviews for fundis after job completion.

**Coverage:** 80% of completed jobs get ratings

**Rating Distribution:**
- ⭐⭐⭐⭐⭐ (5 stars) - 30%
- ⭐⭐⭐⭐ (4 stars) - 35%
- ⭐⭐⭐ (3 stars) - 20%
- ⭐⭐ (2 stars) - 10%
- ⭐ (1 star) - 5%

**Reviews:**
- 20 positive review templates
- 10 negative review templates
- Created within 1 week of job completion

---

### 20. NotificationSeeder
**Table:** `notifications`  
**Migration:** `2025_09_09_162923_create_notifications_table.php`

**Purpose:** Creates user notifications for various events.

**Notifications per User:** 5-15
**Read Status:** 70% marked as read

**Notification Types:**
- 📝 Job Application (new application received)
- ✅ Job Approved (application accepted)
- ❌ Job Rejected (application declined)
- 💰 Payment Received
- ⭐ Rating Received
- 💬 Message Received
- ℹ️ System Notification

**Specific Notifications:**
- Auto-created for job applications
- Status-based notifications for applications

---

### 21. AuditLogSeeder
**Table:** `audit_logs`  
**Migration:** `2025_09_09_163000_create_audit_logs_table.php`

**Purpose:** Creates comprehensive audit trail for security and compliance.

**Logs per User:** 10-50 (last 3 months)

**Action Types (24):**
- User actions (login, logout, register, profile update)
- Job actions (create, update, delete, apply)
- Portfolio actions (create, update, delete)
- Payment actions (create, update)
- Rating actions (create, update)
- Notification actions (create, read)
- Admin actions (user/job management, settings)
- System actions (startup, shutdown, maintenance, backup)

**Log Data:**
- IP addresses (varied)
- User agents (Chrome, Firefox, Safari, Edge)
- Status (success, failed, pending)
- Resource type and ID
- Detailed metadata per action type

---

## Running Seeders

### Run All Seeders
```bash
php artisan db:seed
```

### Run with Fresh Migration
```bash
php artisan migrate:fresh --seed
```

### Run Specific Seeder
```bash
php artisan db:seed --class=UserSeeder
```

### Run Multiple Specific Seeders
```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=CategorySeeder
```

---

## Test Credentials

### Admin Account
```
Phone: 0754289824
Password: password123
Role: Admin
Status: Active
```

### Customer Accounts
```
Phone: 0654289825 | Password: password123 | Status: Active
Phone: 0754289826 | Password: password123 | Status: Active
Phone: 0754289830 | Password: password123 | Status: Inactive
```

### Fundi Accounts
```
Phone: 0654289827 | Password: password123 | Status: Active
Phone: 0754289828 | Password: password123 | Status: Active
Phone: 0654289829 | Password: password123 | Status: Inactive
Phone: 0654289831 | Password: password123 | Status: Banned
```

### Multi-Role Accounts
```
Phone: 0754289832 | Roles: Fundi + Customer | Status: Active
Phone: 0654289836 | Roles: Admin + Fundi | Status: Active
```

### Staff Accounts
```
Phone: 0754289834 | Role: Moderator | Status: Active
Phone: 0654289835 | Role: Support | Status: Active
```

---

## Data Statistics

### Total Records Created

| Table | Records | Details |
|-------|---------|---------|
| **roles** | 5 | customer, fundi, admin, moderator, support |
| **permissions** | 54 | Granular access control |
| **users** | 12 | Across all roles |
| **user_sessions** | 12-60 | Active/expired sessions |
| **categories** | 12 | Job categories |
| **admin_settings** | 1 | System configuration |
| **payment_plans** | 5 | Free, subscriptions, pay-per-use |
| **fundi_profiles** | 6+ | Professional profiles |
| **fundi_applications** | 5 | New fundi applications |
| **fundi_application_sections** | 25 | 5 sections × 5 applications |
| **job_postings** | 10 | Various statuses |
| **job_media** | 20-50 | Photos, docs, videos |
| **job_applications** | 10-30 | Applications to jobs |
| **portfolio** | 20-40 | Fundi portfolio items |
| **portfolio_media** | 60-320 | 3-8 per portfolio |
| **work_submissions** | 10-30 | Completed work |
| **payments** | 40-100 | All payment types |
| **payment_transactions** | 40-100 | Transaction details |
| **user_subscriptions** | ~3-4 | 30% of users |
| **ratings_reviews** | 8-20 | Customer feedback |
| **notifications** | 60-180 | 5-15 per user |
| **audit_logs** | 120-600 | 10-50 per user |

### Total: **~500-1,500 records** depending on random generation

---

## Notes

### Tanzania Market Context
- **Currency:** Tanzania Shillings (TZS)
- **Phone Format:** 0XXXXXXXXX or +255XXXXXXXXX (10 digits)
- **NIDA Numbers:** 20 digits (National ID)
- **VETA Certificates:** Vocational training certification
- **Mobile Money:** M-Pesa, Tigo Pesa, Airtel Money (dominant payment methods)
- **Locations:** Major Tanzania cities and GPS coordinates

### Data Quality
- All dates are realistic (past dates for creation, future for deadlines)
- All foreign keys properly linked
- No orphaned records
- Realistic budget ranges in TZS
- Proper status transitions
- Weighted random distributions for realistic data

### Security
- All passwords are hashed with bcrypt
- Tokens use secure random generation
- NIDA numbers follow Tanzania format
- Phone numbers follow Tanzania telecom format

---

## Maintenance

### Adding New Seeders
1. Create seeder: `php artisan make:seeder NewTableSeeder`
2. Implement `run()` method with realistic data
3. Add to `DatabaseSeeder.php` in correct dependency order
4. Document in this file
5. Test: `php artisan db:seed --class=NewTableSeeder`

### Updating Existing Seeders
1. Modify seeder file
2. Test with fresh migration: `php artisan migrate:fresh --seed`
3. Update documentation
4. Verify foreign key constraints
5. Check data consistency

---

## Support

For issues or questions about seeders:
1. Check migration files for table structure
2. Review foreign key constraints
3. Verify seeding order in `DatabaseSeeder.php`
4. Test individual seeders
5. Check Laravel logs: `storage/logs/laravel.log`

---

**Last Updated:** 2025-01-12  
**Laravel Version:** 12.x  
**Database:** SQLite (development), MySQL (production compatible)

