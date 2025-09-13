# SMS Integration Setup

## Required Environment Variables

Add the following environment variables to your `.env` file:

```env
# Next SMS Service Configuration
NEXT_SMS_AUTHORIZATION="Basic bXNpbHUyMTpwYXNzdzByZEAyMDI1"
NEXT_SMS_API_URL="https://messaging-service.co.tz/api/sms/v1/text/single"
NEXT_SMS_SENDER_ID="HARUSI"
```

## SMS Integration Features

The SMS integration has been implemented in the AuthController with the following features:

### 1. OTP Sending
- Automatically sends SMS when OTP is generated
- Supports different OTP types: registration, password_reset, phone_change
- Uses Next SMS service for delivery

### 2. Phone Number Formatting
- Automatically formats Tanzanian phone numbers to +255 format
- Handles various input formats (0XXXXXXXXX, 255XXXXXXXXX, +255XXXXXXXXX)
- Ensures proper formatting for SMS delivery

### 3. Error Handling
- Comprehensive error logging for SMS failures
- Graceful fallback when SMS service is unavailable
- Detailed logging for debugging

### 4. Message Content
- Professional SMS messages with OTP
- Includes 10-minute validity notice
- Security warning about not sharing the code
- Branded with "Fundi App" signature

## Usage

The SMS integration is automatically triggered when:
- User requests password reset (`/auth/forgot-password`)
- User requests OTP for registration (`/auth/send-otp`)
- User requests OTP for phone change (`/auth/send-otp`)

## Testing

In debug mode, the OTP is also returned in the API response for testing purposes. In production, the OTP is only sent via SMS.

## Logs

SMS-related logs can be found in Laravel's log files:
- Successful SMS delivery: `SMS sent successfully to +255XXXXXXXXX`
- SMS failures: `SMS API Error: HTTP XXX - Response`
- cURL errors: `SMS cURL Error: Error message`
