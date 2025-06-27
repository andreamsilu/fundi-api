# Fundi API Documentation

## Overview
This document provides comprehensive documentation for the Fundi API, which serves as the backend for the Fundi mobile application. The API follows RESTful principles and uses token-based authentication.

## Base URL
```
https://api.fundi.com/v1
```

## Authentication
The API uses Laravel Sanctum for authentication. All protected endpoints require a Bearer token in the Authorization header.

```
Authorization: Bearer <your_token>
```

## API Endpoints

### Authentication

#### Register User
```http
POST /auth/register
```
Request Body:
```json
{
    "name": "string",
    "email": "string",
    "password": "string",
    "phone": "string",
    "role": "client|fundi"
}
```

#### Login
```http
POST /auth/login
```
Request Body:
```json
{
    "email": "string",
    "password": "string"
}
```

#### Logout
```http
POST /auth/logout
```
Headers:
- Authorization: Bearer token

#### Get User Profile
```http
GET /auth/user
```
Headers:
- Authorization: Bearer token

### OTP Verification
```http
POST /otp/send
```
Request Body:
```json
{
    "phone": "string"
}
```

### Fundi Management

#### List Fundis
```http
GET /fundis
```
Query Parameters:
- category_id (optional)
- search (optional)
- rating (optional)
- location (optional)

#### Get Fundi Details
```http
GET /fundis/{fundi_id}
```

#### Update Fundi Profile
```http
PUT /fundi/profile
```
Headers:
- Authorization: Bearer token (Fundi role required)
Request Body:
```json
{
    "name": "string",
    "phone": "string",
    "location": "string",
    "bio": "string",
    "skills": ["string"]
}
```

### Jobs

#### List Jobs
```http
GET /jobs
```
Query Parameters:
- status (optional)
- category_id (optional)
- location (optional)

#### Create Job
```http
POST /jobs
```
Headers:
- Authorization: Bearer token
Request Body:
```json
{
    "title": "string",
    "description": "string",
    "category_id": "integer",
    "location": "string",
    "budget": "decimal"
}
```

#### Get Job Details
```http
GET /jobs/{job_id}
```

#### Update Job
```http
PUT /jobs/{job_id}
```
Headers:
- Authorization: Bearer token

#### Cancel Job
```http
POST /jobs/{job_id}/cancel
```
Headers:
- Authorization: Bearer token

### Bookings

#### List Bookings
```http
GET /bookings
```
Headers:
- Authorization: Bearer token

#### Create Booking
```http
POST /bookings
```
Headers:
- Authorization: Bearer token
Request Body:
```json
{
    "job_id": "integer",
    "fundi_id": "integer",
    "scheduled_date": "datetime",
    "notes": "string"
}
```

#### Get Booking Details
```http
GET /bookings/{booking_id}
```
Headers:
- Authorization: Bearer token

#### Update Booking Status
```http
PUT /bookings/{booking_id}
```
Headers:
- Authorization: Bearer token
Request Body:
```json
{
    "status": "accepted|rejected|completed"
}
```

### Reviews

#### List Fundi Reviews
```http
GET /reviews/fundi/{fundi_id}
```

#### Create Review
```http
POST /reviews
```
Headers:
- Authorization: Bearer token
Request Body:
```json
{
    "fundi_id": "integer",
    "rating": "integer",
    "comment": "string"
}
```

### Notifications

#### List Notifications
```http
GET /notifications
```
Headers:
- Authorization: Bearer token

#### Get Unread Count
```http
GET /notifications/unread-count
```
Headers:
- Authorization: Bearer token

#### Mark as Read
```http
POST /notifications/{notification_id}/read
```
Headers:
- Authorization: Bearer token

### Chat

#### List Chats
```http
GET /chats
```
Headers:
- Authorization: Bearer token

#### Get Chat Details
```http
GET /chats/{chat_id}
```
Headers:
- Authorization: Bearer token

#### Send Message
```http
POST /chats/{chat_id}/messages
```
Headers:
- Authorization: Bearer token
Request Body:
```json
{
    "message": "string"
}
```

### Payments

#### Initialize Payment
```http
POST /payments/initialize
```
Headers:
- Authorization: Bearer token
Request Body:
```json
{
    "amount": "decimal",
    "booking_id": "integer",
    "payment_method": "string"
}
```

#### Get Payment History
```http
GET /payments/history
```
Headers:
- Authorization: Bearer token

## Error Responses

The API uses standard HTTP status codes and returns error responses in the following format:

```json
{
    "error": {
        "message": "Error description",
        "code": "ERROR_CODE"
    }
}
```

Common HTTP Status Codes:
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Server Error

## Rate Limiting
The API implements rate limiting to ensure fair usage. The current limits are:
- 60 requests per minute for authenticated users
- 30 requests per minute for unauthenticated users

## Best Practices
1. Always handle API errors gracefully
2. Implement proper token management
3. Cache responses when appropriate
4. Implement retry logic for failed requests
5. Use pagination for list endpoints
6. Implement proper error handling for network issues

## Security Considerations
1. Always use HTTPS
2. Never store tokens in plain text
3. Implement proper token refresh mechanism
4. Handle session expiration gracefully
5. Implement proper input validation
6. Use secure storage for sensitive data

## Mobile App Implementation Guidelines
1. Implement proper state management
2. Use proper caching mechanisms
3. Implement offline support where possible
4. Use proper error handling and user feedback
5. Implement proper loading states
6. Use proper navigation patterns
7. Implement proper form validation
8. Use proper image handling and caching
9. Implement proper push notification handling
10. Use proper location services 