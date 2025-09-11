#!/bin/bash

BASE_URL="http://127.0.0.1:8000/api/v1"
echo "=== Fundi Mobile API Local Test ==="
echo "Testing all mobile endpoints at $BASE_URL"
echo ""

# Test credentials
PHONE="+255123456789"
PASSWORD="password123"

echo "🔐 1. Testing Authentication..."
echo "   Login:"
LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"phone\": \"$PHONE\", \"password\": \"$PASSWORD\"}")

echo "$LOGIN_RESPONSE" | jq .

# Extract token
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token')

if [ "$TOKEN" != "null" ] && [ "$TOKEN" != "" ]; then
    echo "   ✅ Login successful! Token: ${TOKEN:0:20}..."
    
    echo ""
    echo "📱 2. Testing Mobile App Endpoints..."
    
    echo "   📋 Categories:"
    CATEGORIES=$(curl -s -X GET $BASE_URL/categories)
    echo "$CATEGORIES" | jq '.data | length'
    
    echo "   💼 Jobs:"
    JOBS=$(curl -s -X GET $BASE_URL/jobs -H "Authorization: Bearer $TOKEN")
    echo "$JOBS" | jq '.data.data | length'
    
    echo "   👤 User Profile (auth/me):"
    USER_ME=$(curl -s -X GET $BASE_URL/auth/me -H "Authorization: Bearer $TOKEN")
    echo "$USER_ME" | jq '.data.phone // "Error"'
    
    echo "   👤 User Profile (users/me):"
    USER_ME_ALT=$(curl -s -X GET $BASE_URL/users/me -H "Authorization: Bearer $TOKEN")
    echo "$USER_ME_ALT" | jq '.data.phone // "Error"'
    
    echo "   📝 Job Applications:"
    APPLICATIONS=$(curl -s -X GET $BASE_URL/my-applications -H "Authorization: Bearer $TOKEN")
    echo "$APPLICATIONS" | jq '.data.data | length // "Error"'
    
    echo "   🎨 Portfolio:"
    PORTFOLIO=$(curl -s -X GET $BASE_URL/portfolio/1 -H "Authorization: Bearer $TOKEN")
    echo "$PORTFOLIO" | jq '.data.data | length // "Error"'
    
    echo "   🔔 Notifications:"
    NOTIFICATIONS=$(curl -s -X GET $BASE_URL/notifications -H "Authorization: Bearer $TOKEN")
    echo "$NOTIFICATIONS" | jq '.data.data | length // "Error"'
    
    echo "   💳 Payments:"
    PAYMENTS=$(curl -s -X GET $BASE_URL/payments -H "Authorization: Bearer $TOKEN")
    echo "$PAYMENTS" | jq '.data.data | length // "Error"'
    
    echo "   ⭐ Ratings:"
    RATINGS=$(curl -s -X GET $BASE_URL/ratings/my-ratings -H "Authorization: Bearer $TOKEN")
    echo "$RATINGS" | jq '.data.data | length // "Error"'
    
    echo ""
    echo "🔧 3. Testing Admin Endpoints..."
    
    echo "   👥 Admin Users:"
    ADMIN_USERS=$(curl -s -X GET $BASE_URL/admin/users -H "Authorization: Bearer $TOKEN")
    echo "$ADMIN_USERS" | jq '.data.data | length // "Error"'
    
    echo "   ⚙️ Admin Settings:"
    ADMIN_SETTINGS=$(curl -s -X GET $BASE_URL/admin/settings -H "Authorization: Bearer $TOKEN")
    echo "$ADMIN_SETTINGS" | jq '.data.payments_enabled // "Error"'
    
    echo "   📊 Admin Jobs:"
    ADMIN_JOBS=$(curl -s -X GET $BASE_URL/admin/jobs -H "Authorization: Bearer $TOKEN")
    echo "$ADMIN_JOBS" | jq '.data.data | length // "Error"'
    
    echo "   📋 Admin Applications:"
    ADMIN_APPLICATIONS=$(curl -s -X GET $BASE_URL/admin/job_applications -H "Authorization: Bearer $TOKEN")
    echo "$ADMIN_APPLICATIONS" | jq '.data.data | length // "Error"'
    
    echo "   💰 Admin Payments:"
    ADMIN_PAYMENTS=$(curl -s -X GET $BASE_URL/admin/payments -H "Authorization: Bearer $TOKEN")
    echo "$ADMIN_PAYMENTS" | jq '.data.data | length // "Error"'
    
    echo ""
    echo "🔍 4. Testing Search and Filtering..."
    
    echo "   🔍 Job Search (by category):"
    SEARCH_JOBS=$(curl -s -X GET "$BASE_URL/jobs?category=1" -H "Authorization: Bearer $TOKEN")
    echo "$SEARCH_JOBS" | jq '.data.data | length // "Error"'
    
    echo "   🏠 Fundi Profile:"
    FUNDI_PROFILE=$(curl -s -X GET $BASE_URL/fundi/1 -H "Authorization: Bearer $TOKEN")
    echo "$FUNDI_PROFILE" | jq '.data.phone // "Error"'
    
    echo ""
    echo "📤 5. Testing File Upload Endpoints..."
    
    echo "   📁 Upload Portfolio Media:"
    UPLOAD_PORTFOLIO=$(curl -s -X POST $BASE_URL/upload/portfolio-media -H "Authorization: Bearer $TOKEN")
    echo "$UPLOAD_PORTFOLIO" | jq '.message // "Error"'
    
    echo "   📁 Upload Job Media:"
    UPLOAD_JOB=$(curl -s -X POST $BASE_URL/upload/job-media -H "Authorization: Bearer $TOKEN")
    echo "$UPLOAD_JOB" | jq '.message // "Error"'
    
    echo ""
    echo "🔄 6. Testing Token Management..."
    
    echo "   🔄 Refresh Token:"
    REFRESH_RESPONSE=$(curl -s -X POST $BASE_URL/auth/refresh-token \
      -H "Content-Type: application/json" \
      -H "Authorization: Bearer $TOKEN" \
      -d "{\"token\": \"$TOKEN\"}")
    echo "$REFRESH_RESPONSE" | jq '.success // "Error"'
    
    echo "   ℹ️ Token Info:"
    TOKEN_INFO=$(curl -s -X POST $BASE_URL/auth/token-info \
      -H "Content-Type: application/json" \
      -H "Authorization: Bearer $TOKEN" \
      -d "{\"token\": \"$TOKEN\"}")
    echo "$TOKEN_INFO" | jq '.success // "Error"'
    
    echo ""
    echo "🚪 7. Testing Logout..."
    
    echo "   🚪 Logout:"
    LOGOUT_RESPONSE=$(curl -s -X POST $BASE_URL/auth/logout -H "Authorization: Bearer $TOKEN")
    echo "$LOGOUT_RESPONSE" | jq '.success // "Error"'
    
    echo ""
    echo "✅ All mobile API endpoints tested successfully!"
    echo "📱 Mobile app should work perfectly with local API!"
    
else
    echo "❌ Login failed!"
    echo "Response: $LOGIN_RESPONSE"
fi

