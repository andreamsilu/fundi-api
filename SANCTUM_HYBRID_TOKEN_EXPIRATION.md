# 🔐 **SANCTUM HYBRID TOKEN EXPIRATION IMPLEMENTATION**

## **📋 OVERVIEW**

This implementation provides a **hybrid token expiration system** using Laravel Sanctum that combines:
- **Server-side validation** (primary security layer)
- **Client-side optimization** (UX enhancement)
- **Token refresh capability** (seamless user experience)

## **🏗️ ARCHITECTURE**

### **1. Backend (Laravel Sanctum) - Security Layer** 🔒

#### **Configuration** (`config/sanctum.php`)
```php
'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 60 * 24), // 24 hours
'refresh_expiration' => env('SANCTUM_REFRESH_EXPIRATION', 60 * 24 * 7), // 7 days
```

#### **Token Creation** (AuthController)
```php
$token = $user->createToken(
    'auth_token',
    ['*'], // Abilities
    TokenRefreshService::getTokenExpiration() // Expiration time
);
```

#### **Token Validation** (EnsureTokenIsValid Middleware)
- Validates token existence
- Checks expiration status
- Determines refresh availability
- Returns appropriate error codes

### **2. Frontend (Flutter) - UX Layer** 📱

#### **Token Storage** (SessionManager)
```dart
Future<void> saveToken(String token, {DateTime? expiresAt}) async {
  _authToken = token;
  await _box?.put(AppConstants.tokenKey, token);
  
  if (expiresAt != null) {
    await _box?.put('token_expires_at', expiresAt.toIso8601String());
  }
}
```

#### **Expiration Check** (SessionManager)
```dart
bool isTokenExpired() {
  if (_authToken == null) return true;
  
  final expiresAt = _box?.get('token_expires_at');
  if (expiresAt != null) {
    final expiration = DateTime.parse(expiresAt);
    return DateTime.now().isAfter(expiration);
  }
  
  return true; // Security first: expire if no timestamp
}
```

## **🔄 TOKEN LIFECYCLE**

### **1. Login/Register**
```
User → API → Sanctum Token (24h) → Mobile App
     ↓
  Store Token + Expiration Time
```

### **2. API Requests**
```
Mobile App → Check Local Expiration → API Request
     ↓
  If Expired: Try Refresh → New Token
     ↓
  If Refresh Fails: Redirect to Login
```

### **3. Token Refresh**
```
Expired Token → Refresh Endpoint → New Token (24h)
     ↓
  Update Local Storage + Session
```

## **🛡️ SECURITY FEATURES**

### **1. Server-Side Validation** ✅
- **Primary Security**: All tokens validated on server
- **Expiration Enforcement**: Server rejects expired tokens
- **Refresh Window**: 7-day window for token refresh
- **Token Revocation**: Immediate invalidation on logout

### **2. Client-Side Optimization** ✅
- **Proactive Refresh**: Refresh before expiration
- **Offline Detection**: Handle network failures gracefully
- **Secure Storage**: Encrypted local storage (Hive)
- **Automatic Cleanup**: Clear expired tokens

### **3. Error Handling** ✅
- **TOKEN_EXPIRED_REFRESH_AVAILABLE**: Can refresh
- **TOKEN_EXPIRED_NO_REFRESH**: Must re-login
- **INVALID_TOKEN**: Token not found
- **UNAUTHENTICATED**: No token provided

## **📡 API ENDPOINTS**

### **Authentication Endpoints**
```http
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/logout
GET  /api/v1/auth/me
```

### **Token Management Endpoints**
```http
POST /api/v1/auth/refresh-token
POST /api/v1/auth/token-info
```

### **Request/Response Examples**

#### **Login Response**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "id": "1",
    "phone": "+255123456789",
    "role": "fundi",
    "token": "1|abc123...",
    "expires_at": "2024-01-02T12:00:00.000Z"
  }
}
```

#### **Token Refresh Request**
```json
{
  "token": "1|abc123..."
}
```

#### **Token Refresh Response**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "token": "1|def456...",
    "expires_at": "2024-01-03T12:00:00.000Z",
    "user": {
      "id": "1",
      "phone": "+255123456789",
      "role": "fundi",
      "status": "active"
    }
  }
}
```

## **🔧 IMPLEMENTATION FILES**

### **Backend Files**
- `config/sanctum.php` - Sanctum configuration
- `app/Http/Middleware/EnsureTokenIsValid.php` - Token validation
- `app/Services/TokenRefreshService.php` - Token refresh logic
- `app/Http/Controllers/AuthController.php` - Auth endpoints
- `app/Models/User.php` - HasApiTokens trait

### **Frontend Files**
- `lib/core/services/session_manager.dart` - Token management
- `lib/features/auth/services/auth_service.dart` - Auth API calls
- `lib/core/network/api_client.dart` - HTTP client with token handling

## **⚙️ CONFIGURATION**

### **Environment Variables**
```env
SANCTUM_TOKEN_EXPIRATION=1440        # 24 hours in minutes
SANCTUM_REFRESH_EXPIRATION=10080     # 7 days in minutes
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

### **Database Tables**
- `personal_access_tokens` - Sanctum token storage
- `user_sessions` - Custom session tracking
- `audit_logs` - Security event logging

## **🚀 USAGE EXAMPLES**

### **1. Login with Token Expiration**
```dart
final response = await AuthService().login(phone, password);
if (response.success) {
  final token = response.data['token'];
  final expiresAt = DateTime.parse(response.data['expires_at']);
  
  await SessionManager().saveToken(token, expiresAt: expiresAt);
}
```

### **2. Check Token Before API Call**
```dart
if (SessionManager().isTokenExpired()) {
  final refreshed = await AuthService().refreshToken();
  if (!refreshed) {
    // Redirect to login
    return;
  }
}

// Proceed with API call
```

### **3. Handle Token Refresh**
```dart
Future<bool> refreshToken() async {
  final currentToken = await SessionManager().getToken();
  if (currentToken == null) return false;
  
  final response = await AuthService().refreshToken(currentToken);
  if (response.success) {
    final newToken = response.data['token'];
    final expiresAt = DateTime.parse(response.data['expires_at']);
    
    await SessionManager().saveToken(newToken, expiresAt: expiresAt);
    return true;
  }
  
  return false;
}
```

## **🔍 MONITORING & DEBUGGING**

### **Token Information Endpoint**
```http
POST /api/v1/auth/token-info
{
  "token": "1|abc123..."
}
```

### **Response**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "auth_token",
    "abilities": ["*"],
    "created_at": "2024-01-01T12:00:00.000Z",
    "expires_at": "2024-01-02T12:00:00.000Z",
    "last_used_at": "2024-01-01T15:30:00.000Z",
    "can_refresh": true,
    "is_expired": false
  }
}
```

## **✅ BENEFITS**

### **Security** 🔒
- **Server-side validation** prevents token manipulation
- **Automatic expiration** limits exposure window
- **Refresh window** balances security and UX
- **Audit logging** tracks all token operations

### **User Experience** 📱
- **Seamless refresh** without re-login
- **Proactive handling** prevents API failures
- **Offline resilience** with local validation
- **Fast response** with client-side checks

### **Developer Experience** 👨‍💻
- **Clear error codes** for different scenarios
- **Comprehensive logging** for debugging
- **Flexible configuration** for different environments
- **Well-documented APIs** for easy integration

## **🎯 BEST PRACTICES**

1. **Always validate tokens server-side** - Client-side checks are for optimization only
2. **Use HTTPS in production** - Protect tokens in transit
3. **Implement proper error handling** - Handle all token states gracefully
4. **Monitor token usage** - Track refresh patterns and security events
5. **Set appropriate expiration times** - Balance security and user experience
6. **Log security events** - Maintain audit trail for compliance

---

## **🚨 CRITICAL SECURITY NOTES**

- **Server validation is PRIMARY** - Client-side checks are for UX only
- **Never trust client-side expiration** - Always validate on server
- **Implement proper logout** - Revoke tokens immediately
- **Monitor for suspicious activity** - Track failed refresh attempts
- **Use secure storage** - Encrypt tokens in local storage
- **Regular security audits** - Review token usage patterns

This implementation provides enterprise-grade security while maintaining excellent user experience through intelligent token management.
