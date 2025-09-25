# Fundi Mobile API Testing Guide

This guide explains how to test the Fundi mobile app API endpoints using the provided Postman collection.

## Setup Instructions

### 1. Import the Collection
1. Open Postman
2. Click "Import" button
3. Select the `postman_mobile_tests.json` file
4. The collection will be imported with all test cases

### 2. Configure Environment Variables
The collection uses the following variables that you can set in Postman:

- `baseUrl`: Your API base URL (default: `http://localhost:8000/api`)
- `authToken`: Authentication token (auto-set after login)
- `userId`: Current user ID (auto-set after login)
- `fundiId`: Fundi ID for testing (auto-set from feeds)
- `jobId`: Job ID for testing (auto-set from feeds)

### 3. Update Base URL
If your API is running on a different host/port, update the `baseUrl` variable:
- Right-click the collection → "Edit"
- Go to "Variables" tab
- Update the `baseUrl` value

## Test Categories

### 1. Authentication Tests
- **Health Check**: Tests API availability
- **Register User**: Creates a new user account
- **Login User**: Authenticates user and sets auth token
- **Get Current User**: Retrieves authenticated user profile
- **Logout**: Ends user session

### 2. User Management Tests
- **Get User Profile**: Retrieves user profile with stats
- **Update User Profile**: Updates basic user information
- **Update Fundi Profile**: Updates fundi-specific information

### 3. Categories Tests
- **Get Categories**: Retrieves available job categories

### 4. Feeds Tests
- **Get Fundi Feed**: Retrieves paginated list of fundis
- **Get Job Feed**: Retrieves paginated list of jobs
- **Get Fundi Profile**: Gets detailed fundi profile with portfolio
- **Get Job Details**: Gets detailed job information

### 5. Jobs Tests
- **Create Job**: Creates a new job posting
- **Get My Jobs**: Retrieves user's job postings
- **Apply to Job**: Applies to a job as a fundi

### 6. Portfolio Tests
- **Create Portfolio Item**: Creates a new portfolio entry
- **Get My Portfolio**: Retrieves user's portfolio
- **Get Fundi Portfolio**: Retrieves specific fundi's portfolio

### 7. Payments Tests
- **Get Current Plan**: Retrieves user's current subscription
- **Get Payment Plans**: Lists available subscription plans
- **Get Payment History**: Retrieves payment transaction history
- **Check Permission**: Verifies if user can perform an action

### 8. Notifications Tests
- **Get Notifications**: Retrieves user notifications
- **Mark All as Read**: Marks all notifications as read

### 9. Ratings Tests
- **Create Rating**: Creates a rating for a fundi
- **Get Fundi Ratings**: Retrieves ratings for a specific fundi

### 10. Settings Tests
- **Get Settings**: Retrieves user settings
- **Update Settings**: Updates user preferences

## Running Tests

### Individual Test
1. Select any request in the collection
2. Click "Send"
3. Check the "Test Results" tab for assertions

### Collection Runner
1. Click the collection name
2. Click "Run" button
3. Select tests to run
4. Click "Run Fundi Mobile API Tests"

### Automated Testing
The collection includes automated tests that:
- Verify response status codes
- Check response structure
- Auto-set variables for dependent requests
- Validate data types and required fields

## Test Data Requirements

### Prerequisites
1. **Database**: Ensure your database is set up with:
   - Categories (at least 1 category)
   - Payment plans
   - Basic system configuration

2. **File Uploads**: For portfolio tests, prepare sample image files

### Sample Data
The tests use these sample values:
- **User**: test@example.com / password123
- **Job**: "Fix Kitchen Sink" with budget 5000
- **Portfolio**: "Kitchen Renovation" project

## Expected Responses

### Success Responses
All successful API calls return:
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Responses
Error responses include:
```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error information"
}
```

## Common Issues & Solutions

### 1. Authentication Errors
- **401 Unauthorized**: Check if auth token is set correctly
- **403 Forbidden**: User lacks required permissions/roles

### 2. Validation Errors
- **422 Unprocessable Entity**: Check request body format
- **400 Bad Request**: Verify required fields are provided

### 3. File Upload Issues
- Ensure file paths are correct in form-data
- Check file size limits
- Verify supported file types

### 4. Database Issues
- Ensure database is running
- Check if required tables exist
- Verify foreign key relationships

## Testing Workflows

### Customer Workflow
1. Register/Login as customer
2. Browse fundi feed
3. View fundi profiles
4. Create job posting
5. Check payment plans
6. Update settings

### Fundi Workflow
1. Register/Login as fundi
2. Update fundi profile
3. Browse job feed
4. Apply to jobs
5. Create portfolio items
6. Check applications

### Multi-Role User Workflow
1. Login with multiple roles
2. Switch between customer/fundi features
3. Test role-specific endpoints
4. Verify proper access control

## Performance Testing

### Load Testing
- Run collection multiple times simultaneously
- Monitor response times
- Check for rate limiting

### Pagination Testing
- Test with different page sizes
- Verify pagination metadata
- Test edge cases (empty results, last page)

## Security Testing

### Authentication
- Test with invalid tokens
- Test expired tokens
- Test token refresh

### Authorization
- Test role-based access
- Test permission boundaries
- Test admin-only endpoints

## Monitoring & Debugging

### Console Logging
The API client includes request/response logging. Check your Laravel logs for:
- Request details
- Response data
- Error traces
- Performance metrics

### Postman Console
- View request/response details
- Check test results
- Monitor variable values

## Best Practices

1. **Run tests in order**: Some tests depend on previous ones
2. **Check prerequisites**: Ensure database is populated
3. **Use realistic data**: Test with actual use case scenarios
4. **Monitor performance**: Watch for slow responses
5. **Test edge cases**: Empty results, invalid data, etc.
6. **Verify security**: Test unauthorized access attempts

## Troubleshooting

### Collection Not Working
1. Check base URL configuration
2. Verify API server is running
3. Check network connectivity
4. Review API logs

### Tests Failing
1. Check response format matches expectations
2. Verify database state
3. Check authentication tokens
4. Review error messages

### Performance Issues
1. Check database indexes
2. Monitor server resources
3. Review query performance
4. Check for N+1 queries

## Support

For issues with the API or tests:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Review API documentation
3. Check database migrations
4. Verify environment configuration

## Additional Resources

- [Laravel API Documentation](https://laravel.com/docs/api)
- [Postman Testing Guide](https://learning.postman.com/docs/writing-scripts/test-scripts/)
- [Fundi API Documentation](./API_DOCUMENTATION.md)

