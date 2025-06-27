# Fundi API Documentation

## Base URL
All API endpoints are prefixed with `/api/v1`

## Authentication
The API uses Bearer token authentication. Include the token in the Authorization header:
```
Authorization: Bearer <your-token>
```

## Rate Limiting
The API is rate limited to 60 requests per minute per user/IP.

## Endpoints

### Authentication
- `POST /auth/login` - Login user
- `POST /auth/register` - Register new user
- `POST /auth/logout` - Logout user
- `GET /auth/user` - Get authenticated user details

### Users (Fundi)
- `GET /fundis` - List all fundis
- `GET /fundis/{fundi}` - Get fundi details
- `PUT /fundi/profile` - Update fundi profile (requires fundi role)

### Jobs
- `GET /jobs` - List all jobs with optional filters
- `POST /jobs` - Create new job (requires permission: create jobs)
- `GET /jobs/{job}` - Get job details
- `PUT /jobs/{job}` - Update job (requires permission: edit own jobs)
- `POST /jobs/{job}/cancel` - Cancel job (requires permission: edit own jobs)
- `GET /jobs/mine` - Get jobs created by authenticated user

### Service Categories (Admin Only)
- `GET /service-categories` - List all service categories
- `POST /service-categories` - Create new service category
- `PUT /service-categories/{category}` - Update service category
- `DELETE /service-categories/{category}` - Delete service category

### Bookings
- `GET /bookings` - List user's bookings (customer or fundi)
- `POST /bookings` - Create new booking
- `GET /bookings/{booking}` - Get booking details
- `PUT /bookings/{booking}` - Update booking status
- `POST /bookings/{booking}/cancel` - Cancel booking

### Reviews
- `GET /reviews/fundi/{fundi}` - Get fundi reviews
- `POST /reviews` - Create new review
- `DELETE /reviews/{review}` - Delete review (requires permission: delete own reviews)

### Notifications
- `GET /notifications` - List notifications
- `GET /notifications/unread-count` - Get unread notifications count
- `POST /notifications/{notification}/read` - Mark notification as read
- `POST /notifications/read-all` - Mark all notifications as read
- `DELETE /notifications/{notification}` - Delete notification

### Chats
- `GET /chats` - List chats
- `GET /chats/{chat}` - Get chat details
- `POST /chats` - Create new chat
- `POST /chats/{chat}/messages` - Send message
- `POST /chats/{chat}/read` - Mark chat as read
- `GET /chats/unread-count` - Get unread messages count

### Payments
- `POST /payments/initialize` - Initialize payment
- `GET /payments/history` - Get payment history
- `GET /payments/{payment}` - Get payment details
- `POST /webhooks/stripe` - Stripe webhook endpoint (no auth required)

## Response Format
All responses follow this format:
```json
{
    "data": {},  // Response data
    "meta": {},  // Metadata (if applicable)
    "links": {}  // Pagination links (if applicable)
}
```

## Error Responses
All error responses follow this format:
```json
{
    "message": "Error message",
    "errors": {},  // Validation errors (if applicable)
    "status": 400  // HTTP status code
}
```

## Status Codes
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Unprocessable Entity
- 500: Internal Server Error

## Pagination
Endpoints that return lists are paginated with 10 items per page. Pagination metadata is included in the response:
```json
{
    "data": [],
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 10,
        "total": 100
    },
    "links": {
        "first": "/api/v1/endpoint?page=1",
        "last": "/api/v1/endpoint?page=10",
        "prev": null,
        "next": "/api/v1/endpoint?page=2"
    }
}
```
