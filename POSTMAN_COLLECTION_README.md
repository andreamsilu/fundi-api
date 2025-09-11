# Fundi API Postman Collection

This repository contains a comprehensive Postman collection for testing the Fundi API endpoints.

## Files Included

1. **Fundi_API_Collection.postman_collection.json** - The main Postman collection with all API endpoints
2. **Fundi_API_Environment.postman_environment.json** - Environment variables for different configurations
3. **POSTMAN_COLLECTION_README.md** - This documentation file

## Setup Instructions

### 1. Import Collection and Environment

1. Open Postman
2. Click "Import" button
3. Import both files:
   - `Fundi_API_Collection.postman_collection.json`
   - `Fundi_API_Environment.postman_environment.json`

### 2. Configure Environment

1. Select the "Fundi API Environment" from the environment dropdown
2. Update the `base_url` variable if your API is running on a different host/port
3. Default base URL is set to `http://localhost:8000/api/v1`

### 3. Authentication Setup

The collection includes automatic token management:
- Login/Register requests automatically store the auth token
- All protected endpoints use the stored token
- Token is stored in the `auth_token` environment variable

## Collection Structure

### 1. Authentication
- **Register User** - Create new user account
- **Login User** - Authenticate user
- **Get Current User** - Get authenticated user profile
- **Refresh Token** - Refresh authentication token
- **Logout** - End user session

### 2. Categories
- **Get All Categories** - Retrieve all job categories

### 3. Jobs
- **Get All Jobs** - List all available jobs
- **Get Job by ID** - Get specific job details
- **Create Job** - Create new job (Customer/Admin only)
- **Update Job** - Update job details (Customer/Admin only)
- **Delete Job** - Delete job (Customer/Admin only)

### 4. Job Applications
- **Apply for Job** - Apply for a job (Fundi only)
- **Get My Applications** - Get fundi's applications
- **Get Job Applications** - Get applications for a job (Customer/Admin only)
- **Update Application Status** - Update application status (Customer/Admin only)
- **Delete Application** - Delete application

### 5. Users
- **Get My Profile** - Get current user profile
- **Update Fundi Profile** - Update fundi profile details
- **Get Fundi Profile** - Get public fundi profile

### 6. Portfolio
- **Get Fundi Portfolio** - Get fundi's portfolio items
- **Create Portfolio Item** - Add new portfolio item (Fundi/Admin only)
- **Update Portfolio Item** - Update portfolio item (Fundi/Admin only)
- **Delete Portfolio Item** - Delete portfolio item (Fundi/Admin only)
- **Upload Portfolio Media** - Upload media for portfolio

### 7. Payments
- **Get Payments** - List user payments
- **Create Payment** - Process new payment
- **Get Payment Requirements** - Get payment requirements
- **Check Payment Required** - Check if payment is required

### 8. Notifications
- **Get Notifications** - List user notifications
- **Mark Notification as Read** - Mark notification as read
- **Delete Notification** - Delete notification

### 9. Ratings & Reviews
- **Create Rating** - Rate a fundi (Customer only)
- **Get My Ratings** - Get customer's ratings
- **Get Fundi Ratings** - Get fundi's ratings
- **Update Rating** - Update existing rating
- **Delete Rating** - Delete rating

### 10. File Uploads
- **Upload Portfolio Media** - Upload media for portfolio (Fundi only)
- **Upload Job Media** - Upload media for job (Customer only)
- **Upload Profile Document** - Upload profile document (Fundi only)
- **Delete Media** - Delete uploaded media
- **Get Media URL** - Get media download URL

### 11. Admin
- **User Management** - Manage users, fundi profiles
- **Job Management** - Manage all jobs
- **Application Management** - Manage job applications
- **Payment Management** - Manage payments and reports
- **Notification Management** - Send and manage notifications
- **Category Management** - Manage job categories
- **Settings Management** - Manage system settings

### 12. Monitoring
- **Active Users** - Monitor active users
- **Jobs Summary** - Get jobs statistics
- **Payments Summary** - Get payments statistics
- **System Health** - Check system health
- **API Logs** - View API logs
- **Laravel Logs** - View Laravel logs

### 13. Audit Logs
- **Audit Logs** - View system audit logs
- **Statistics** - Get audit statistics
- **Failed Actions** - View failed actions
- **User Activity** - Track user activity
- **Security Events** - Monitor security events
- **API Errors** - View API errors
- **Export** - Export audit logs

## Usage Examples

### 1. Basic Authentication Flow

1. **Register a new user:**
   - Use "Authentication > Register User"
   - Update the request body with your details
   - The auth token will be automatically stored

2. **Login:**
   - Use "Authentication > Login User"
   - Provide phone and password
   - Token will be automatically stored

3. **Access protected endpoints:**
   - All protected endpoints will use the stored token
   - No need to manually add Authorization headers

### 2. Testing Job Workflow

1. **Create a job** (as Customer):
   - Use "Jobs > Create Job"
   - Fill in job details

2. **Apply for job** (as Fundi):
   - Use "Job Applications > Apply for Job"
   - Provide cover letter and proposed budget

3. **Review applications** (as Customer):
   - Use "Job Applications > Get Job Applications"
   - Update application status

### 3. Testing Admin Functions

1. **Login as Admin:**
   - Use admin credentials to login
   - Token will be stored automatically

2. **Manage users:**
   - Use "Admin > Get All Users"
   - Update user status or roles

3. **Monitor system:**
   - Use "Monitoring" endpoints to check system health

## Environment Variables

The collection uses the following environment variables:

- `base_url` - API base URL (default: http://localhost:8000/api/v1)
- `auth_token` - Authentication token (auto-populated)
- `user_id` - Current user ID
- `job_id` - Job ID for testing
- `fundi_id` - Fundi ID for testing
- `application_id` - Application ID for testing
- `portfolio_id` - Portfolio ID for testing
- `rating_id` - Rating ID for testing
- `notification_id` - Notification ID for testing
- `media_id` - Media ID for testing

## Role-Based Access

The API uses role-based access control:

- **Customer** - Can create jobs, manage applications, rate fundis
- **Fundi** - Can apply for jobs, manage portfolio, view applications
- **Admin** - Full access to all endpoints and admin functions

## Error Handling

The collection includes test scripts that:
- Automatically store auth tokens on successful login/register
- Check response status codes
- Display error messages for failed requests

## Testing Tips

1. **Start with Authentication** - Always login first to get a valid token
2. **Use Environment Variables** - Update IDs in environment variables for easier testing
3. **Check Response Codes** - Monitor response codes for proper error handling
4. **Test Different Roles** - Create users with different roles to test access control
5. **File Uploads** - Use the file upload endpoints to test media functionality

## Troubleshooting

### Common Issues

1. **401 Unauthorized** - Check if auth token is valid and not expired
2. **403 Forbidden** - Verify user has correct role for the endpoint
3. **422 Validation Error** - Check request body format and required fields
4. **500 Server Error** - Check server logs for detailed error information

### Debug Steps

1. Check environment variables are set correctly
2. Verify base URL is accessible
3. Ensure user is logged in and token is valid
4. Check user role has permission for the endpoint
5. Review request body format matches API requirements

## API Documentation

For detailed API documentation, refer to the Laravel API documentation or contact the development team.

## Support

For issues with the API or this collection, please contact the development team or create an issue in the project repository.

