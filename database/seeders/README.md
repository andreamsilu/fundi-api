# 🌱 Fundi API Database Seeders

## Quick Start

```bash
# Fresh migration with all seeds
php artisan migrate:fresh --seed

# Seed only (preserves existing data)
php artisan db:seed

# Seed specific table
php artisan db:seed --class=UserSeeder
```

## 📊 What Gets Seeded?

### ✅ Complete Data Coverage
- **23 Tables** fully seeded with realistic data
- **500-1,500 Records** across all tables
- **12 Test Users** across all roles
- **10 Job Postings** with applications and media
- **40-100 Payments** with full transaction details
- **100+ Notifications** and audit logs

---

## 🔑 Test Credentials

**All passwords:** `password123`

| Role | Phone | Use Case |
|------|-------|----------|
| **Admin** | 0754289824 | Full system access, admin panel |
| **Customer** | 0654289825 | Post jobs, hire fundis |
| **Fundi** | 0654289827 | Apply to jobs, manage portfolio |
| **Both** | 0754289832 | Dual role testing |

---

## 📋 Seeder Phases

### Phase 1: Foundation
- Roles & Permissions (54 permissions)
- Categories (12 job types)
- Admin Settings (system config)
- Payment Plans (5 plans)

### Phase 2: Users
- 12 Users across all roles
- Fundi Profiles
- Fundi Applications
- User Sessions

### Phase 3: Jobs
- 10 Job Postings
- 20-50 Job Media (photos/docs)
- 10-30 Job Applications

### Phase 4: Portfolio
- 20-40 Portfolio Items
- 60-320 Portfolio Media
- 10-30 Work Submissions

### Phase 5: Payments
- Payment Records
- Transaction Details
- User Subscriptions

### Phase 6-8: Interactions
- Ratings & Reviews
- Notifications
- Audit Logs

---

## 📖 Documentation

See [SEEDERS_DOCUMENTATION.md](./SEEDERS_DOCUMENTATION.md) for:
- Detailed seeder descriptions
- Data structures
- Migration references
- Maintenance guidelines

---

## 🇹🇿 Tanzania Context

All data reflects the Tanzania market:
- **Currency:** TZS (Tanzania Shillings)
- **Phone:** 0XXXXXXXXX format
- **NIDA:** 20-digit national ID
- **VETA:** Vocational training certificates
- **Payments:** M-Pesa, Tigo Pesa, Airtel Money
- **Locations:** Dar es Salaam, Arusha, Mwanza, etc.

---

## 🛠️ Troubleshooting

### Foreign Key Errors?
Seeds must run in order. Use `migrate:fresh --seed` to reset.

### Need More Data?
Adjust `rand()` ranges in individual seeders.

### Want Specific Data?
Run individual seeders: `php artisan db:seed --class=JobsSeeder`

### Reset Everything?
```bash
php artisan migrate:fresh --seed
```

---

## 📁 Seeder Files

```
database/seeders/
├── DatabaseSeeder.php              # Main orchestrator
├── RolePermissionSeeder.php        # Roles & permissions
├── UserSeeder.php                  # Test users
├── CategorySeeder.php              # Job categories
├── AdminSettingSeeder.php          # System settings
├── PaymentPlanSeeder.php           # Payment plans
├── FundiProfileSeeder.php          # Fundi profiles
├── FundiApplicationSeeder.php      # New fundi applications
├── FundiApplicationSectionSeeder.php # Application steps
├── JobsSeeder.php                  # Job postings
├── JobMediaSeeder.php              # Job photos/docs
├── JobApplicationsSeeder.php       # Applications to jobs
├── PortfoliosSeeder.php            # Portfolio items
├── PortfolioMediaSeeder.php        # Portfolio media
├── WorkSubmissionSeeder.php        # Completed work
├── PaymentSeeder.php               # Payments
├── PaymentTransactionSeeder.php    # Transaction details
├── UserSubscriptionSeeder.php      # Subscriptions
├── RatingReviewSeeder.php          # Reviews
├── NotificationSeeder.php          # Notifications
├── AuditLogSeeder.php              # Audit trail
├── UserSessionSeeder.php           # User sessions
├── README.md                       # This file
└── SEEDERS_DOCUMENTATION.md        # Full documentation
```

---

## ⚡ Quick Commands

```bash
# Complete reset with seeds
php artisan migrate:fresh --seed

# Seed only
php artisan db:seed

# Specific seeder
php artisan db:seed --class=UserSeeder

# Create new seeder
php artisan make:seeder NewSeeder

# Clear database
php artisan migrate:fresh

# Check database
php artisan tinker
>>> User::count()
>>> Job::count()
>>> Payment::count()
```

---

## 🎯 Development Workflow

1. **Initial Setup:**
   ```bash
   php artisan migrate:fresh --seed
   ```

2. **Add Feature:**
   - Create migration
   - Create seeder
   - Add to DatabaseSeeder.php
   - Test: `php artisan db:seed --class=YourSeeder`

3. **Test Data:**
   - Login with test credentials
   - Verify data in database
   - Check relationships

4. **Production:**
   - Never run seeders in production
   - Use migrations only
   - Backup before any changes

---

## 📞 Support

Questions? Check:
1. [SEEDERS_DOCUMENTATION.md](./SEEDERS_DOCUMENTATION.md) - Full details
2. `php artisan db:seed --help` - Command help
3. Migration files - Table structures
4. Laravel logs - `storage/logs/laravel.log`

---

**Happy Seeding!** 🌱✨

