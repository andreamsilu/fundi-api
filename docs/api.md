Perfect! Let’s merge everything — full API documentation + admin module + budget handling + system monitoring + Laravel log access — into a single, unified Fundi App API documentation.

📘 Fundi App – Unified API Documentation (with Admin Control & System Monitoring)
Base URL:
https://api.fundiapp.com/v1

Authentication:
Method: Laravel Sanctum (Token-based)


Header:


Authorization: Bearer {access_token}


1. Authentication APIs
Method
Endpoint
Description
Role
POST
/auth/register
Register user (fundi/customer)
All
POST
/auth/login
Login user
All
POST
/auth/logout
Logout
All

Request Example (Register):
{
  "phone": "+255712345678",
  "password": "password123",
  "role": "fundi"
}

Response Example:
{
  "id": 1,
  "phone": "+255712345678",
  "role": "fundi",
  "token": "sanctum_token_here"
}


2. Users & Fundi Profiles
Method
Endpoint
Description
Role
GET
/users/me
Get own profile
All
PATCH
/users/me/fundi-profile
Update fundi profile
Fundi
GET
/admin/users
List all users
Admin
GET
/admin/users/{id}
Get user details
Admin
PATCH
/admin/users/{id}
Update user info/status
Admin
DELETE
/admin/users/{id}
Delete user
Admin
GET
/admin/fundi_profiles
List all fundi profiles
Admin
PATCH
/admin/fundi_profiles/{id}/verify
Approve/reject fundi verification
Admin


3. Categories
Method
Endpoint
Description
Role
GET
/categories
List categories
All
POST
/admin/categories
Create category
Admin
PATCH
/admin/categories/{id}
Update category
Admin
DELETE
/admin/categories/{id}
Delete category
Admin


4. Jobs & Applications
4.1 Jobs
Method
Endpoint
Description
Role
GET
/jobs
List jobs
Customer/Admin
POST
/jobs
Post job
Customer
GET
/admin/jobs
List all jobs
Admin
GET
/admin/jobs/{id}
Get job details
Admin
PATCH
/admin/jobs/{id}
Update/cancel job
Admin
DELETE
/admin/jobs/{id}
Delete job
Admin

4.2 Job Applications (with Budget Breakdown)
Method
Endpoint
Description
Role
POST
/jobs/{job_id}/apply
Fundi applies with budget
Fundi
GET
/jobs/{job_id}/applications
Customer views proposals
Customer
GET
/admin/job_applications
List all applications
Admin
PATCH
/admin/job_applications/{id}
Accept/reject application
Admin
DELETE
/admin/job_applications/{id}
Delete application
Admin

Application Request Example:
{
  "requirements": "Need 2 helpers, 3 days work",
  "budget_breakdown": {
    "materials": 20000,
    "labor": 15000,
    "transport": 5000
  },
  "estimated_time": 3
}

Response Example:
{
  "id": 301,
  "status": "pending",
  "total_budget": 40000
}


5. Portfolio & Media
Method
Endpoint
Description
Role
POST
/portfolio
Fundi adds portfolio
Fundi
GET
/portfolio/{fundi_id}
Get portfolio
Customer/Admin
POST
/portfolio_media
Upload media
Fundi/Admin
PATCH
/admin/portfolio/{id}
Edit portfolio
Admin
DELETE
/admin/portfolio/{id}
Delete portfolio
Admin


6. Payments (Pesapal)
Method
Endpoint
Description
Role
GET
/payments
List payments
Admin
POST
/payments
Make payment
Fundi/Customer
PATCH
/admin/payments/{id}
Update payment status
Admin
GET
/admin/payments/reports
Generate revenue report
Admin


7. Notifications (Firebase)
Method
Endpoint
Description
Role
GET
/notifications
List notifications
All
POST
/admin/notifications
Send push notification
Admin
PATCH
/admin/notifications/{id}
Mark read/unread
Admin
DELETE
/admin/notifications/{id}
Delete notification
Admin


8. Admin Settings & Monitoring
Settings
Method
Endpoint
Description
Role
GET
/admin/settings
View platform settings
Admin
PATCH
/admin/settings
Update fees, payment model, subscriptions
Admin

System Monitoring
Feature
Endpoint
Description
Role
Active Users
/admin/monitor/active-users
Number of active fundis/customers
Admin
Jobs Summary
/admin/monitor/jobs-summary
Number of jobs by status
Admin
Payments Summary
/admin/monitor/payments-summary
Revenue by subscription/application/job fees
Admin
System Health
/admin/monitor/system-health
Uptime, DB status, queue size, storage usage
Admin
API Logs
/admin/monitor/api-logs
Track requests, errors, suspicious activity
Admin
Sessions
/admin/sessions
List active sessions
Admin
Force Logout
/admin/sessions/{id}
Force logout a user
Admin
Laravel Log Viewer
/admin/logs
View laravel.log content
Admin

Laravel Log Endpoint Response Example:
{
  "log_lines": [
    "[2025-09-09 16:00:01] production.ERROR: Undefined variable: job_id",
    "[2025-09-09 16:01:22] production.INFO: Job created successfully, ID: 101"
  ]
}


9. Technical Notes
Authentication: Laravel Sanctum, role-based access


File Storage: AWS S3 / Firebase Storage with signed URLs


Notifications: Firebase Cloud Messaging


Payments: Pesapal for fees & subscriptions


Budget Handling: budget_breakdown JSON in job applications, total_budget calculated server-side


Monitoring: Admin can view logs, transactions, sessions, performance metrics, Laravel logs


Security: bcrypt passwords, token expiry, audit logs, multi-device session tracking, rate limiting



✅ Conclusion:
 This unified documentation now fully supports:
Admin control across all modules


Detailed budget proposals per job with total calculation


Customer visibility of all fundi proposals


System monitoring including Laravel logs


All mobile, admin, payment, notification, and monitoring endpoints


Secure, production-ready architecture



I can next update the ERD visually to show:
budget_breakdown in job_applications


Admin control relations across all modules


Laravel log tracking


Do you want me to generate that ERD diagram?

