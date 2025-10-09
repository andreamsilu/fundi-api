# Fundi API Documentation

**Version:** 1.0  
**Base URL:** `http://your-domain.com/api`  
**Authentication:** JWT Bearer Token

---

## Table of Contents

1. [Authentication](#authentication)
2. [User Management](#user-management)
3. [Job Management](#job-management)
4. [Job Applications](#job-applications)
5. [Categories](#categories)
6. [Feeds](#feeds)
7. [Portfolio](#portfolio)
8. [Ratings & Reviews](#ratings--reviews)
9. [Fundi Applications](#fundi-applications)
10. [Work Approval](#work-approval)
11. [Payments](#payments)
12. [Notifications](#notifications)
13. [Dashboard](#dashboard)
14. [Search](#search)

---

## Response Format

All API responses follow this standard format:

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { /* response data */ }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": { /* validation errors if any */ }
}
```

---

## Authentication

### Register
Create a new user account.

**Endpoint:** `POST /auth/register`

**Request Body:**
```json
{
  "full_name": "John Doe",
  "phone": "0712345678",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "nida_number": "19900101-12345-67890-12"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User successfully registered",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "full_name": "John Doe",
      "phone": "0712345678",
      "email": "john@example.com",
      "roles": ["customer"]
    }
  }
}
```

---

### Login
Authenticate and receive JWT token.

**Endpoint:** `POST /auth/login`

**Request Body:**
```json
{
  "phone": "0712345678",
  "password": "password123"
}
```

**Response:** Same as register

---

### Refresh Token
Refresh the JWT token before it expires.

**Endpoint:** `POST /auth/refresh`

**Headers:** 
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "access_token": "new_token_here",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

---

### Logout
Invalidate the current token.

**Endpoint:** `POST /auth/logout`

**Headers:** 
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Successfully logged out"
}
```

---

## User Management

### Get Current User
Get authenticated user's profile.

**Endpoint:** `GET /users/me`

**Headers:** 
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "User profile retrieved successfully",
  "data": {
    "id": 1,
    "full_name": "John Doe",
    "phone": "0712345678",
    "email": "john@example.com",
    "profile_image_url": "https://...",
    "location": "Dar es Salaam",
    "bio": "Professional carpenter...",
    "skills": ["carpentry", "welding"],
    "languages": ["Swahili", "English"],
    "roles": ["customer", "fundi"],
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

---

### Update Profile
Update user profile information.

**Endpoint:** `PATCH /users/me`

**Headers:** 
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "full_name": "John Doe Updated",
  "email": "newemail@example.com",
  "location": "Arusha",
  "bio": "Updated bio..."
}
```

**Response:** Returns updated user object

---

## Job Management

### Get Available Jobs (Public Feed)
Get list of all available jobs.

**Endpoint:** `GET /jobs`

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `limit` (int): Items per page (default: 15)
- `category_id` (int): Filter by category
- `status` (string): Filter by status (open, in_progress, completed, cancelled)
- `lat` (float): Latitude for location filter
- `lng` (float): Longitude for location filter
- `radius` (float): Radius in km for location filter

**Response:**
```json
{
  "success": true,
  "message": "Jobs retrieved successfully",
  "data": {
    "jobs": [
      {
        "id": 1,
        "title": "Need a carpenter",
        "description": "Building a cabinet...",
        "location": "Dar es Salaam",
        "budget": 150000,
        "category": {
          "id": 1,
          "name": "Carpentry"
        },
        "customer": {
          "id": 2,
          "full_name": "Jane Smith",
          "phone": "0723456789"
        },
        "status": "open",
        "deadline": "2024-12-31",
        "applications_count": 5,
        "created_at": "2024-01-15T10:00:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 50,
      "last_page": 4
    }
  }
}
```

---

### Get My Jobs
Get jobs created by the authenticated user.

**Endpoint:** `GET /jobs/my-jobs`

**Query Parameters:** Same as Get Available Jobs

**Response:** Same format as Get Available Jobs

---

### Create Job
Create a new job posting.

**Endpoint:** `POST /jobs`

**Request Body:**
```json
{
  "title": "Need a plumber urgently",
  "description": "Fixing broken pipes in bathroom...",
  "location": "Dar es Salaam",
  "budget": 80000,
  "category_id": 2,
  "urgency": "high",
  "deadline": "2024-12-31",
  "preferred_time": "morning",
  "location_lat": -6.7924,
  "location_lng": 39.2083,
  "required_skills": ["plumbing", "pipe_fitting"],
  "image_urls": ["https://..."]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Job created successfully",
  "data": { /* job object */ }
}
```

---

### Get Job by ID
Get details of a specific job.

**Endpoint:** `GET /jobs/{id}`

**Response:** Single job object

---

### Update Job
Update an existing job (only job owner).

**Endpoint:** `PATCH /jobs/{id}`

**Request Body:** Partial job object (only fields to update)

**Response:** Updated job object

---

### Delete Job
Delete a job (only job owner).

**Endpoint:** `DELETE /jobs/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Job deleted successfully"
}
```

---

## Job Applications

### Apply for Job
Submit an application for a job.

**Endpoint:** `POST /jobs/{jobId}/apply`

**Request Body:**
```json
{
  "requirements": {
    "message": "I am experienced in...",
    "estimated_days": 5
  },
  "budget_breakdown": {
    "labor": 56000,
    "materials": 16000,
    "transport": 8000
  },
  "estimated_time": 120
}
```

**Response:**
```json
{
  "success": true,
  "message": "Application submitted successfully",
  "data": {
    "id": 1,
    "job_id": 10,
    "fundi_id": 5,
    "status": "pending",
    "requirements": { /* ... */ },
    "budget_breakdown": { /* ... */ },
    "created_at": "2024-01-15T10:00:00Z"
  }
}
```

---

### Get My Applications
Get applications submitted by the authenticated fundi.

**Endpoint:** `GET /job-applications/my-applications`

**Response:**
```json
{
  "success": true,
  "data": [ /* array of applications */ ]
}
```

---

### Get Job Applications
Get all applications for a specific job (job owner only).

**Endpoint:** `GET /jobs/{jobId}/applications`

**Response:** Array of applications

---

### Update Application Status
Accept or reject a job application (job owner only).

**Endpoint:** `PATCH /job-applications/{id}/status`

**Request Body:**
```json
{
  "status": "accepted"  // or "rejected"
}
```

**Response:** Updated application object

---

## Categories

### Get All Categories
Get list of all job categories.

**Endpoint:** `GET /categories`

**Response:**
```json
{
  "success": true,
  "message": "Categories retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Carpentry",
      "description": "Wood working and furniture"
    },
    {
      "id": 2,
      "name": "Plumbing",
      "description": "Water and drainage systems"
    }
  ]
}
```

---

### Get Category by ID
Get a single category.

**Endpoint:** `GET /categories/{id}`

---

## Feeds

### Get Job Feed
Get curated feed of jobs.

**Endpoint:** `GET /feeds/jobs`

**Query Parameters:**
- `page`, `limit`, `category_id`, `location`, `min_budget`, `max_budget`

---

### Get Fundi Feed
Get list of fundis with their profiles.

**Endpoint:** `GET /feeds/fundis`

**Query Parameters:**
- `page`, `limit`, `category`, `location`, `min_rating`

**Response:**
```json
{
  "success": true,
  "data": {
    "fundis": [
      {
        "id": 5,
        "full_name": "Ahmed Hassan",
        "location": "Dar es Salaam",
        "skills": ["carpentry", "furniture"],
        "average_rating": 4.8,
        "total_ratings": 25,
        "completed_jobs": 42,
        "portfolio_count": 5
      }
    ],
    "pagination": { /* ... */ }
  }
}
```

---

### Get Fundi Profile
Get detailed profile of a fundi.

**Endpoint:** `GET /feeds/fundis/{id}`

**Response:** Detailed fundi profile with portfolio

---

### Get Job Details
Get detailed job information from feed.

**Endpoint:** `GET /feeds/jobs/{id}`

---

## Portfolio

### Get My Portfolio
Get portfolio items of authenticated fundi.

**Endpoint:** `GET /portfolio/my-portfolio`

**Response:**
```json
{
  "success": true,
  "message": "Portfolio retrieved successfully",
  "data": [
    {
      "id": 1,
      "fundi_id": 5,
      "title": "Modern Kitchen Cabinet",
      "description": "Custom kitchen cabinet...",
      "skills_used": "carpentry, design",
      "duration_hours": 40,
      "budget": 250000,
      "status": "approved",
      "is_visible": true,
      "media": [
        {
          "id": 1,
          "media_url": "https://...",
          "media_type": "image"
        }
      ],
      "created_at": "2024-01-10T00:00:00Z"
    }
  ],
  "portfolio_count": 5,
  "visible_count": 4,
  "can_add_more": false
}
```

---

### Get Portfolio Status
Get portfolio statistics and limits.

**Endpoint:** `GET /portfolio/status`

**Response:**
```json
{
  "success": true,
  "data": {
    "total_items": 5,
    "visible_items": 4,
    "pending_items": 1,
    "rejected_items": 0,
    "can_add_more": false,
    "max_items": 5,
    "remaining_slots": 0
  }
}
```

---

### Get Fundi Portfolio
Get portfolio of a specific fundi.

**Endpoint:** `GET /portfolio/{fundiId}`

**Response:** Array of approved and visible portfolio items

---

### Create Portfolio Item
Add a new portfolio item.

**Endpoint:** `POST /portfolio`

**Request Body:**
```json
{
  "title": "Wooden Dining Table",
  "description": "6-seater dining table made from mahogany...",
  "skills_used": "carpentry, woodworking",
  "duration_hours": 60,
  "budget": 350000
}
```

**Response:**
```json
{
  "success": true,
  "message": "Portfolio item created successfully and submitted for approval",
  "data": { /* portfolio item */ }
}
```

---

### Update Portfolio Item
Update existing portfolio item.

**Endpoint:** `PATCH /portfolio/{id}`

**Request Body:** Partial portfolio object

---

### Delete Portfolio Item
Delete a portfolio item.

**Endpoint:** `DELETE /portfolio/{id}`

---

## Ratings & Reviews

### Create Rating
Rate a fundi after job completion.

**Endpoint:** `POST /ratings`

**Request Body:**
```json
{
  "fundi_id": 5,
  "rating": 5,
  "review": "Excellent work, very professional and timely!",
  "job_id": 10
}
```

**Response:**
```json
{
  "success": true,
  "message": "Rating created successfully",
  "data": {
    "id": 1
  }
}
```

---

### Get My Ratings
Get ratings given by authenticated user.

**Endpoint:** `GET /ratings/my-ratings`

---

### Get Fundi Ratings
Get all ratings for a specific fundi.

**Endpoint:** `GET /ratings/fundi/{fundiId}`

**Response:**
```json
{
  "success": true,
  "data": {
    "ratings": [
      {
        "id": 1,
        "customer_name": "Jane Doe",
        "rating": 5,
        "review": "Great work!",
        "created_at": "2024-01-15T00:00:00Z"
      }
    ],
    "average_rating": 4.8,
    "total_ratings": 25
  }
}
```

---

### Update Rating
Update an existing rating.

**Endpoint:** `PATCH /ratings/{id}`

---

### Delete Rating
Delete a rating.

**Endpoint:** `DELETE /ratings/{id}`

---

## Fundi Applications

### Get Requirements
Get requirements to become a fundi.

**Endpoint:** `GET /fundi-applications/requirements`

**Response:**
```json
{
  "success": true,
  "data": {
    "requirements": {
      "full_name": "Full legal name as per NIDA",
      "phone_number": "Active phone number",
      "nida_number": "Valid NIDA number (20 digits)",
      "veta_certificate": "VETA certificate number",
      /* ... */
    },
    "process": { /* application process steps */ },
    "statuses": { /* possible statuses */ }
  }
}
```

---

### Get Application Status
Get current user's fundi application status.

**Endpoint:** `GET /fundi-applications/status`

**Response:**
```json
{
  "success": true,
  "message": "Fundi application status retrieved successfully",
  "data": {
    "id": 1,
    "user_id": 5,
    "status": "pending",
    "full_name": "Ahmed Hassan",
    /* ... other application fields */
    "created_at": "2024-01-10T00:00:00Z"
  }
}
```

---

### Submit Application
Submit a fundi application.

**Endpoint:** `POST /fundi-applications`

**Request Body:**
```json
{
  "full_name": "Ahmed Hassan",
  "phone_number": "0712345678",
  "email": "ahmed@example.com",
  "nida_number": "19900101-12345-67890-12",
  "veta_certificate": "VETA123456",
  "location": "Dar es Salaam",
  "bio": "Experienced carpenter with 10 years...",
  "skills": ["carpentry", "furniture", "woodworking"],
  "languages": ["Swahili", "English"],
  "portfolio_images": ["https://...", "https://..."]
}
```

---

### Submit Section
Submit individual sections of application.

**Endpoint:** `POST /fundi-applications/sections`

**Request Body:**
```json
{
  "section_name": "personal_info",
  "section_data": {
    "full_name": "Ahmed Hassan",
    "date_of_birth": "1990-01-01",
    "gender": "male"
  }
}
```

**Section Names:**
- `personal_info`
- `contact_info`
- `professional_info`
- `documents`
- `portfolio`

---

### Get Application Progress
Get progress of sectioned application.

**Endpoint:** `GET /fundi-applications/progress`

**Response:**
```json
{
  "success": true,
  "data": {
    "completed_sections": 3,
    "total_sections": 5,
    "percentage": 60,
    "sections": {
      "personal_info": true,
      "contact_info": true,
      "professional_info": true,
      "documents": false,
      "portfolio": false
    }
  }
}
```

---

### Get Section Data
Get data for a specific section.

**Endpoint:** `GET /fundi-applications/sections/{sectionName}`

---

### Submit Final Application
Submit final application after all sections complete.

**Endpoint:** `POST /fundi-applications/submit`

---

### Get All Applications (Admin)
Get all fundi applications (admin only).

**Endpoint:** `GET /fundi-applications`

**Query Parameters:**
- `status` (string): Filter by status
- `limit` (int): Per page

---

### Update Application Status (Admin)
Approve or reject application.

**Endpoint:** `PATCH /fundi-applications/{id}/status`

**Request Body:**
```json
{
  "status": "approved",
  "rejection_reason": "Optional rejection reason"
}
```

---

## Work Approval

### Get Pending Portfolio Items
Get portfolio items awaiting approval (customers).

**Endpoint:** `GET /work-approval/portfolio-pending`

**Query Parameters:**
- `page`, `per_page`

---

### Get Pending Work Submissions
Get work submissions awaiting approval.

**Endpoint:** `GET /work-approval/submissions-pending`

---

### Approve Portfolio Item
Approve a portfolio item.

**Endpoint:** `POST /work-approval/portfolio/{id}/approve`

---

### Reject Portfolio Item
Reject a portfolio item.

**Endpoint:** `POST /work-approval/portfolio/{id}/reject`

**Request Body:**
```json
{
  "rejection_reason": "Images are not clear enough"
}
```

---

### Approve Work Submission
Approve completed work submission.

**Endpoint:** `POST /work-approval/submissions/{id}/approve`

---

### Reject Work Submission
Reject work submission and request revision.

**Endpoint:** `POST /work-approval/submissions/{id}/reject`

**Request Body:**
```json
{
  "rejection_reason": "Work does not meet specifications"
}
```

---

## Payments

### Get Payment Plans
Get available subscription plans.

**Endpoint:** `GET /payments/plans`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Basic Plan",
      "price": 10000,
      "duration_days": 30,
      "features": {
        "max_jobs": 5,
        "priority_support": false
      }
    }
  ]
}
```

---

### Get Current Plan
Get user's current subscription plan.

**Endpoint:** `GET /payments/current-plan`

---

### Subscribe to Plan
Subscribe to a payment plan.

**Endpoint:** `POST /payments/subscribe`

**Request Body:**
```json
{
  "plan_id": 1,
  "payment_method": "mpesa",
  "phone_number": "0712345678"
}
```

---

### Check Payment Requirement
Check if user has payment/subscription requirement.

**Endpoint:** `POST /payments/check-requirement`

**Request Body:**
```json
{
  "feature": "create_job"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "has_access": true,
    "reason": "User has active subscription"
  }
}
```

---

## Notifications

### Get Notifications
Get user's notifications.

**Endpoint:** `GET /notifications`

**Query Parameters:**
- `page`, `limit`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "New job application",
      "message": "Ahmed Hassan applied to your job",
      "type": "job_application",
      "is_read": false,
      "created_at": "2024-01-15T10:00:00Z"
    }
  ]
}
```

---

### Mark as Read
Mark notification as read.

**Endpoint:** `PATCH /notifications/{id}/read`

---

### Delete Notification
Delete a notification.

**Endpoint:** `DELETE /notifications/{id}`

---

## Dashboard

### Get Dashboard Overview
Get dashboard statistics based on user role.

**Endpoint:** `GET /dashboard/overview`

**Response (Customer):**
```json
{
  "success": true,
  "data": {
    "user_type": "customer",
    "statistics": {
      "total_jobs_posted": 15,
      "active_jobs": 3,
      "completed_jobs": 10,
      "total_applications_received": 45,
      "total_spent": 1500000
    },
    "recent_jobs": [ /* ... */ ],
    "recent_applications": [ /* ... */ ]
  }
}
```

**Response (Fundi):**
```json
{
  "success": true,
  "data": {
    "user_type": "fundi",
    "statistics": {
      "total_applications": 30,
      "accepted_applications": 20,
      "pending_applications": 5,
      "active_jobs": 3,
      "completed_jobs": 17,
      "total_earned": 2500000,
      "portfolio_items": 5,
      "average_rating": 4.8
    },
    "recent_applications": [ /* ... */ ],
    "active_jobs": [ /* ... */ ]
  }
}
```

---

### Get Job Statistics
Get job statistics over time.

**Endpoint:** `GET /dashboard/job-statistics`

**Query Parameters:**
- `period` (string): day, week, month, year

**Response:**
```json
{
  "success": true,
  "data": {
    "period": "month",
    "statistics": [
      {
        "period": "2024-01",
        "total_jobs": 45,
        "open_jobs": 15,
        "in_progress_jobs": 10,
        "completed_jobs": 20,
        "average_budget": 125000
      }
    ]
  }
}
```

---

### Get Payment Statistics
Get payment statistics over time.

**Endpoint:** `GET /dashboard/payment-statistics`

**Query Parameters:**
- `period` (string): day, week, month, year

---

### Get Application Statistics
Get application success rate and counts.

**Endpoint:** `GET /dashboard/application-statistics`

**Response:**
```json
{
  "success": true,
  "data": {
    "total": 50,
    "pending": 10,
    "accepted": 35,
    "rejected": 5,
    "acceptance_rate": 70.0
  }
}
```

---

## Search

### Get Search Suggestions
Get search suggestions for jobs/fundis.

**Endpoint:** `GET /search/suggestions`

**Query Parameters:**
- `q` (string): Search query
- `type` (string): jobs, fundis, or all

**Response:**
```json
{
  "success": true,
  "data": {
    "jobs": [ /* job suggestions */ ],
    "fundis": [ /* fundi suggestions */ ],
    "categories": [ /* category suggestions */ ]
  }
}
```

---

## Error Codes

| HTTP Status | Description |
|-------------|-------------|
| 200 | Success |
| 201 | Resource created |
| 400 | Bad request |
| 401 | Unauthorized (invalid/missing token) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Resource not found |
| 422 | Validation error |
| 429 | Too many requests (rate limit) |
| 500 | Server error |

---

## Rate Limiting

- **Default:** 60 requests per minute per user
- **Authentication:** 5 login attempts per minute
- Headers include:
  - `X-RateLimit-Limit`
  - `X-RateLimit-Remaining`
  - `X-RateLimit-Reset`

---

## Pagination

All list endpoints support pagination:

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `limit` (int): Items per page (default: 15, max: 100)

**Response includes:**
```json
{
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

## Permissions

Required permissions for protected endpoints:

| Feature | Permission |
|---------|------------|
| View jobs | `view_jobs` |
| Create jobs | `create_jobs` |
| Apply to jobs | `apply_jobs` |
| View portfolio | `view_portfolio` |
| Create portfolio | `create_portfolio` |
| Rate fundis | `create_ratings` |
| Approve work | `approve_work` |
| View dashboard | `view_dashboard` |

---

## Support

For API support or questions:
- Email: api-support@fundiapp.com
- Documentation: https://docs.fundiapp.com
- GitHub: https://github.com/fundiapp/api

