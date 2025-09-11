#!/bin/bash

BASE_URL="http://127.0.0.1:8000/api/v1"
echo "=== Fundi API Complete Test ==="
echo "Testing all endpoints at $BASE_URL"
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
    curl -s -X GET $BASE_URL/categories | jq '.data | length'
    
    echo "   💼 Jobs:"
    curl -s -X GET $BASE_URL/jobs -H "Authorization: Bearer $TOKEN" | jq '.data.data | length'
    
    echo "   👤 User Profile (auth/me):"
    curl -s -X GET $BASE_URL/auth/me -H "Authorization: Bearer $TOKEN" | jq '.data.phone // "Error"'
    
    echo "   👤 User Profile (users/me):"
    curl -s -X GET $BASE_URL/users/me -H "Authorization: Bearer $TOKEN" | jq '.data.phone // "Error"'
    
    echo "   📝 Job Applications:"
    curl -s -X GET $BASE_URL/my-applications -H "Authorization: Bearer $TOKEN" | jq '.data.data | length // "Error"'
    
    echo "   🎨 Portfolio:"
    curl -s -X GET $BASE_URL/portfolio/1 -H "Authorization: Bearer $TOKEN" | jq '.data.data | length // "Error"'
    
    echo "   🔔 Notifications:"
    curl -s -X GET $BASE_URL/notifications -H "Authorization: Bearer $TOKEN" | jq '.data.data | length // "Error"'
    
    echo "   💳 Payments:"
    curl -s -X GET $BASE_URL/payments -H "Authorization: Bearer $TOKEN" | jq '.data.data | length // "Error"'
    
    echo "   ⭐ Ratings:"
    curl -s -X GET $BASE_URL/ratings/my-ratings -H "Authorization: Bearer $TOKEN" | jq '.data.data | length // "Error"'
    
    echo ""
    echo "🔧 3. Testing Admin Endpoints..."
    
    echo "   👥 Admin Users:"
    curl -s -X GET $BASE_URL/admin/users -H "Authorization: Bearer $TOKEN" | jq '.data.data | length // "Error"'
    
    echo "   ⚙️ Admin Settings:"
    curl -s -X GET $BASE_URL/admin/settings -H "Authorization: Bearer $TOKEN" | jq '.data.payments_enabled // "Error"'
    
    echo "   📊 Admin Jobs:"
    curl -s -X GET $BASE_URL/admin/jobs -H "Authorization: Bearer $TOKEN" | jq '.data.data | length // "Error"'
    
    echo "   📋 Admin Applications:"
    curl -s -X GET $BASE_URL/admin/job_applications -H "Authorization: Bearer $TOKEN" | jq '.data.data | length // "Error"'
    
    echo "   💰 Admin Payments:"
    curl -s -X GET $BASE_URL/admin/payments -H "Authorization: Bearer $TOKEN" | jq '.data.data | length // "Error"'
    
    echo ""
    echo "🔍 4. Testing Search and Filtering..."
    
    echo "   🔍 Job Search (by category):"
    curl -s -X GET "$BASE_URL/jobs?category=1" -H "Authorization: Bearer $TOKEN" | jq '.data.data | length // "Error"'
    
    echo "   🏠 Fundi Profile:"
    curl -s -X GET $BASE_URL/fundi/1 -H "Authorization: Bearer $TOKEN" | jq '.data.phone // "Error"'
    
    echo ""
    echo "📤 5. Testing File Upload Endpoints..."
    
    echo "   📁 Upload Portfolio Media:"
    curl -s -X POST $BASE_URL/upload/portfolio-media -H "Authorization: Bearer $TOKEN" | jq '.message // "Error"'
    
    echo "   📁 Upload Job Media:"
    curl -s -X POST $BASE_URL/upload/job-media -H "Authorization: Bearer $TOKEN" | jq '.message // "Error"'
    
    echo ""
    echo "🔄 6. Testing Token Management..."
    
    echo "   🔄 Refresh Token:"
    curl -s -X POST $BASE_URL/auth/refresh-token \
      -H "Content-Type: application/json" \
      -H "Authorization: Bearer $TOKEN" \
      -d "{\"token\": \"$TOKEN\"}" | jq '.success // "Error"'
    
    echo "   ℹ️ Token Info:"
    curl -s -X POST $BASE_URL/auth/token-info \
      -H "Content-Type: application/json" \
      -H "Authorization: Bearer $TOKEN" \
      -d "{\"token\": \"$TOKEN\"}" | jq '.success // "Error"'
    
    echo ""
    echo "🚪 7. Testing Logout..."
    
    echo "   🚪 Logout:"
    curl -s -X POST $BASE_URL/auth/logout -H "Authorization: Bearer $TOKEN" | jq '.success // "Error"'
    
    echo ""
    echo "✅ All API endpoints tested!"
    echo "📱 Mobile app should work perfectly with local API!"
    
else
    echo "❌ Login failed!"
    echo "Response: $LOGIN_RESPONSE"
fi

