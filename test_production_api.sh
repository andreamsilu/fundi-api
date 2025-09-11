#!/bin/bash

BASE_URL="http://88.223.92.135:8002/api/v1"
echo "=== Fundi API Production Test ==="
echo "Testing production API at $BASE_URL"
echo ""

# Test credentials
PHONE="+255123456789"
PASSWORD="password123"

echo "1. Testing Login..."
LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"phone\": \"$PHONE\", \"password\": \"$PASSWORD\"}")

echo "$LOGIN_RESPONSE" | jq .

# Extract token
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token')

if [ "$TOKEN" != "null" ] && [ "$TOKEN" != "" ]; then
    echo ""
    echo "✅ Login successful! Token: ${TOKEN:0:20}..."
    
    echo ""
    echo "2. Testing Categories..."
    curl -s -X GET $BASE_URL/categories | jq '.data | length'
    
    echo ""
    echo "3. Testing Jobs (with auth)..."
    curl -s -X GET $BASE_URL/jobs \
      -H "Authorization: Bearer $TOKEN" | jq '.data.data | length'
    
    echo ""
    echo "4. Testing Job Applications..."
    curl -s -X GET $BASE_URL/job-applications \
      -H "Authorization: Bearer $TOKEN" | jq '.data.data | length'
    
    echo ""
    echo "5. Testing Portfolios..."
    curl -s -X GET $BASE_URL/portfolios \
      -H "Authorization: Bearer $TOKEN" | jq '.data.data | length'
    
    echo ""
    echo "6. Testing Notifications..."
    curl -s -X GET $BASE_URL/notifications \
      -H "Authorization: Bearer $TOKEN" | jq '.data.data | length'
    
    echo ""
    echo "7. Testing Payments..."
    curl -s -X GET $BASE_URL/payments \
      -H "Authorization: Bearer $TOKEN" | jq '.data.data | length'
    
    echo ""
    echo "8. Testing Ratings..."
    curl -s -X GET $BASE_URL/ratings \
      -H "Authorization: Bearer $TOKEN" | jq '.data.data | length'
    
    echo ""
    echo "9. Testing User Profile..."
    curl -s -X GET $BASE_URL/user/profile \
      -H "Authorization: Bearer $TOKEN" | jq '.data.phone // "Profile endpoint not found"'
    
    echo ""
    echo "10. Testing Admin Settings..."
    curl -s -X GET $BASE_URL/admin/settings \
      -H "Authorization: Bearer $TOKEN" | jq '.data.payments_enabled // "Admin settings not found"'
    
    echo ""
    echo "11. Testing Search..."
    curl -s -X GET "$BASE_URL/search?q=plumbing" \
      -H "Authorization: Bearer $TOKEN" | jq '.data.jobs | length // "Search not found"'
    
    echo ""
    echo "12. Testing Fundi Profiles..."
    curl -s -X GET $BASE_URL/fundis \
      -H "Authorization: Bearer $TOKEN" | jq '.data.data | length // "Fundis not found"'
    
    echo ""
    echo "✅ All production API tests completed!"
    
else
    echo "❌ Login failed!"
    echo "Response: $LOGIN_RESPONSE"
fi
