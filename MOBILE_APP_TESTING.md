# Fundi API Mobile App Testing

This directory contains comprehensive test scripts for testing the Fundi API mobile app endpoints.

## Test Scripts

### 1. `test_mobile_app.sh` - Complete Test Suite
A comprehensive test script that covers all 50+ mobile app endpoints including:
- Authentication (Registration, Login, Logout, Password Management)
- User Management (Profile Updates, Fundi Profiles)
- Job Management (Create, View, Apply, Manage Jobs)
- Portfolio Management (Create, View, Update Portfolios)
- Feed System (Fundi Feed, Job Feed, Nearby Fundis)
- Notifications (Get, Mark as Read, Clear)
- Payments (Plans, History, Permissions)
- Settings (Privacy, Notifications, Themes, Languages)
- Admin Functions (User Management, Job Management, System Monitoring)

### 2. `test_mobile_app_quick.sh` - Quick Test Suite
A streamlined test script that covers the 20 most essential mobile app endpoints for quick validation.

## Usage

### Prerequisites
- Ensure your Laravel API server is running
- Make sure the database is set up with proper migrations
- Ensure all required environment variables are configured

### Running the Tests

#### Complete Test Suite
```bash
# Test against localhost (default)
./test_mobile_app.sh

# Test against specific URL
./test_mobile_app.sh http://your-api-domain.com/api/v1

# Test against production
./test_mobile_app.sh https://api.fundiapp.com/v1
```

#### Quick Test Suite
```bash
# Test against localhost (default)
./test_mobile_app_quick.sh

# Test against specific URL
./test_mobile_app_quick.sh http://your-api-domain.com/api/v1
```

### Test Configuration

The scripts use the following default configuration:
- **Base URL**: `http://localhost:8000/api/v1`
- **Customer Phone**: `+255712345678`
- **Fundi Phone**: `+255712345679`
- **Admin Phone**: `+255712345680`
- **Password**: `password123`

You can modify these values in the script files if needed.

## Test Coverage

### Authentication Endpoints
- ✅ User Registration (Customer, Fundi, Admin)
- ✅ User Login
- ✅ User Logout
- ✅ Password Change
- ✅ Forgot Password
- ✅ OTP Verification
- ✅ Send OTP

### User Management
- ✅ Get User Profile
- ✅ Update User Profile
- ✅ Update Fundi Profile
- ✅ Get Fundi Profile by ID

### Job Management
- ✅ Create Job (Customer)
- ✅ Get All Jobs
- ✅ Get Job by ID
- ✅ Update Job
- ✅ Delete Job
- ✅ Apply for Job (Fundi)
- ✅ Get Job Applications
- ✅ Get My Applications

### Portfolio Management
- ✅ Create Portfolio (Fundi)
- ✅ Get My Portfolio
- ✅ Get Portfolio Status
- ✅ Get Fundi Portfolio by ID
- ✅ Update Portfolio
- ✅ Delete Portfolio

### Feed System
- ✅ Get Fundi Feed
- ✅ Get Job Feed
- ✅ Get Fundi Profile Details
- ✅ Get Job Details
- ✅ Get Nearby Fundis

### Notifications
- ✅ Get Notifications
- ✅ Mark Notification as Read
- ✅ Mark All Notifications as Read
- ✅ Delete Notification
- ✅ Clear All Notifications
- ✅ Get Notification Settings
- ✅ Update Notification Settings
- ✅ Send Test Notification

### Payments
- ✅ Get Payment Plans
- ✅ Get Current Payment Plan
- ✅ Get Payment History
- ✅ Check Action Permission
- ✅ Subscribe to Plan
- ✅ Cancel Subscription
- ✅ Process Pay-per-Use

### Settings
- ✅ Get Settings
- ✅ Update Settings
- ✅ Get Themes
- ✅ Get Languages
- ✅ Update Privacy Settings
- ✅ Update Notification Settings

### Fundi Applications
- ✅ Get Application Requirements
- ✅ Get Application Progress
- ✅ Get Application Status
- ✅ Submit Application Section
- ✅ Submit Final Application

### Work Approval
- ✅ Get Pending Portfolio Items
- ✅ Get Pending Work Submissions
- ✅ Approve/Reject Portfolio Items
- ✅ Approve/Reject Work Submissions

### Admin Functions
- ✅ User Management
- ✅ Role Management
- ✅ Permission Management
- ✅ Job Management
- ✅ Application Management
- ✅ Portfolio Management
- ✅ Category Management
- ✅ Payment Management
- ✅ System Monitoring

## Test Results

The scripts provide colored output for easy reading:
- 🟢 **Green**: Test passed
- 🔴 **Red**: Test failed
- 🟡 **Yellow**: Test section header

At the end of each test run, you'll see a summary:
- Total number of tests
- Number of passed tests
- Number of failed tests
- Overall status

## Troubleshooting

### Common Issues

1. **Connection Refused**
   - Ensure your Laravel server is running
   - Check if the port is correct (default: 8000)
   - Verify the base URL is correct

2. **Authentication Errors**
   - Ensure users are being created successfully
   - Check if tokens are being extracted properly
   - Verify Sanctum is configured correctly

3. **Permission Errors**
   - Ensure roles and permissions are set up
   - Check if middleware is working correctly
   - Verify user roles are assigned properly

4. **Database Errors**
   - Ensure migrations are run
   - Check if database is accessible
   - Verify foreign key constraints

### Debug Mode

To see detailed responses for failed tests, you can modify the scripts to print the full response:

```bash
# Add this line after each curl command to see full response
echo "Full response: $response"
```

## Customization

### Adding New Tests

To add new tests to the scripts:

1. Add a new test section with a descriptive header
2. Make the API request using curl
3. Check the response for success indicators
4. Call `print_result` with appropriate parameters

### Modifying Test Data

You can modify the test data by changing the JSON payloads in the curl commands:

```bash
# Example: Change job title
-d "{\"title\":\"Your Custom Job Title\",\"description\":\"...\",...}"
```

### Testing Different Environments

The scripts support different environments by passing the base URL as a parameter:

```bash
# Development
./test_mobile_app.sh http://localhost:8000/api/v1

# Staging
./test_mobile_app.sh https://staging-api.fundiapp.com/v1

# Production
./test_mobile_app.sh https://api.fundiapp.com/v1
```

## Integration with CI/CD

These scripts can be integrated into your CI/CD pipeline:

```yaml
# Example GitHub Actions workflow
- name: Test Mobile App API
  run: |
    chmod +x test_mobile_app.sh
    ./test_mobile_app.sh ${{ env.API_BASE_URL }}
```

## Support

For issues with the test scripts or API endpoints, please:
1. Check the Laravel logs for detailed error messages
2. Verify your environment configuration
3. Ensure all dependencies are installed
4. Contact the development team for assistance

## License

This testing suite is part of the Fundi API project and follows the same license terms.
