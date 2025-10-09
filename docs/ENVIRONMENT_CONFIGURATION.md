# Environment Configuration Guide

## Backend API (.env)

Create a `.env` file in the root of your Laravel project with the following configuration:

```bash
# Application
APP_NAME=Fundi-API
APP_ENV=local
APP_KEY=base64:your-generated-key-here
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/myprojects/fundi-api/database/database.sqlite
# For MySQL/PostgreSQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=fundi_db
# DB_USERNAME=root
# DB_PASSWORD=

# JWT Authentication
JWT_SECRET=your-jwt-secret-key-here
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=database

# Mail Configuration (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@fundiapp.com"
MAIL_FROM_NAME="${APP_NAME}"

# SMS Configuration (for OTP)
SMS_PROVIDER=twilio
SMS_FROM_NUMBER=your-sms-number
TWILIO_SID=your-twilio-sid
TWILIO_AUTH_TOKEN=your-twilio-auth-token

# File Storage
FILESYSTEM_DISK=local
# For AWS S3:
# FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=us-east-1
# AWS_BUCKET=
# AWS_URL=

# Payment Gateway - ZenoPay (Tanzania Mobile Money)
PAYMENT_PROVIDER=zenopay
ZENOPAY_API_KEY=your-zenopay-api-key
ZENOPAY_BASE_URL=https://zenoapi.com
ZENOPAY_WEBHOOK_URL="${APP_URL}/api/payments/zenopay/webhook"
ZENOPAY_ENABLED=true

# Legacy M-Pesa Direct Integration (if needed)
# MPESA_CONSUMER_KEY=your-mpesa-consumer-key
# MPESA_CONSUMER_SECRET=your-mpesa-consumer-secret
# MPESA_SHORTCODE=your-shortcode
# MPESA_PASSKEY=your-passkey

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:*,http://127.0.0.1:*

# Laravel Telescope (for debugging)
TELESCOPE_ENABLED=true
TELESCOPE_WATCHERS_REQUESTS=true
```

---

## Mobile App (.env)

Create a `.env` file in the root of your Flutter project:

```bash
# API Configuration
API_BASE_URL=http://192.168.1.100:8000/api
API_VERSION=v1
API_TIMEOUT=30000

# Environment
APP_ENV=development
# APP_ENV=production

# Firebase Configuration (for push notifications)
FIREBASE_PROJECT_ID=your-firebase-project-id
FIREBASE_API_KEY=your-firebase-api-key
FIREBASE_APP_ID=your-firebase-app-id
FIREBASE_MESSAGING_SENDER_ID=your-messaging-sender-id

# Feature Flags
ENABLE_ANALYTICS=false
ENABLE_CRASH_REPORTING=false
ENABLE_OFFLINE_MODE=true

# App Configuration
APP_NAME=Fundi
APP_VERSION=1.0.0

# Payment Configuration
PAYMENT_PUBLIC_KEY=your-payment-public-key

# Google Maps API Key (for location features)
GOOGLE_MAPS_API_KEY=your-google-maps-api-key

# Debugging
DEBUG_MODE=true
VERBOSE_LOGGING=true
```

---

## Setup Instructions

### Backend API Setup

1. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

3. **Generate JWT secret:**
   ```bash
   php artisan jwt:secret
   ```

4. **Run migrations:**
   ```bash
   php artisan migrate --seed
   ```

5. **Start the server:**
   ```bash
   php artisan serve
   ```

### Mobile App Setup

1. **Create .env file:**
   ```bash
   touch .env
   # Copy contents from .env.example and update values
   ```

2. **Install dependencies:**
   ```bash
   flutter pub get
   ```

3. **Update API_BASE_URL:**
   - For Android Emulator: `http://10.0.2.2:8000/api`
   - For iOS Simulator: `http://127.0.0.1:8000/api`
   - For Real Device: `http://YOUR_LOCAL_IP:8000/api`

4. **Run the app:**
   ```bash
   flutter run
   ```

---

## Production Configuration

### Backend API (Production)

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.fundiapp.com

DB_CONNECTION=mysql
DB_HOST=your-database-host
DB_PORT=3306
DB_DATABASE=fundi_production
DB_USERNAME=your-username
DB_PASSWORD=your-secure-password

# Use Redis for cache in production
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Use queue for background jobs
QUEUE_CONNECTION=redis

# SSL/TLS
FORCE_HTTPS=true
```

### Mobile App (Production)

```bash
API_BASE_URL=https://api.fundiapp.com/api
APP_ENV=production
DEBUG_MODE=false
ENABLE_ANALYTICS=true
ENABLE_CRASH_REPORTING=true
```

---

## Security Best Practices

1. **Never commit .env files to version control**
   - Add `.env` to `.gitignore`
   - Use `.env.example` as a template

2. **Use strong JWT secrets**
   - Generate with: `openssl rand -base64 64`

3. **Enable HTTPS in production**
   - Force HTTPS in Laravel
   - Use SSL certificates

4. **Secure API keys**
   - Use environment variables
   - Never hardcode in source code
   - Use secure storage in mobile app

5. **Rate limiting**
   - Configure in Laravel middleware
   - Protect against brute force attacks

6. **Database security**
   - Use strong passwords
   - Limit database user permissions
   - Enable SSL for database connections

---

## Troubleshooting

### Common Issues

**Issue: "Connection refused" from mobile app**
- Check if backend API is running
- Verify API_BASE_URL is correct
- Check firewall settings
- For Android emulator, use `10.0.2.2` instead of `localhost`

**Issue: "JWT token expired"**
- Check JWT_TTL configuration
- Implement token refresh in mobile app
- Verify server time is correct

**Issue: "Database connection failed"**
- Verify database credentials
- Check database server is running
- Ensure database exists
- Check database permissions

**Issue: "CORS errors"**
- Configure CORS_ALLOWED_ORIGINS in .env
- Update CORS middleware in Laravel
- Check API URL format

---

## Environment Variables Reference

### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| APP_KEY | Laravel encryption key | `base64:...` |
| JWT_SECRET | JWT token signing key | `your-secret-key` |
| DB_* | Database credentials | Various |
| API_BASE_URL | Backend API URL (mobile) | `http://192.168.1.100:8000/api` |

### Optional Variables

| Variable | Description | Default |
|----------|-------------|---------|
| JWT_TTL | Token expiration (minutes) | `60` |
| API_TIMEOUT | Request timeout (ms) | `30000` |
| CACHE_DRIVER | Cache storage driver | `file` |
| QUEUE_CONNECTION | Queue driver | `sync` |
| LOG_LEVEL | Logging level | `debug` |

---

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log` (API)
- Run diagnostics: `php artisan config:cache`
- Clear cache: `php artisan cache:clear`
- Verify configuration: `php artisan config:show`

