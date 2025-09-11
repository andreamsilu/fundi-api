#!/bin/bash

echo "=== Fundi API Test Script ==="
echo "Testing local API at http://127.0.0.1:8000/api/v1/"
echo ""

echo "1. Testing Login..."
LOGIN_RESPONSE=$(curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"phone": "+255123456789", "password": "password123"}')

echo "$LOGIN_RESPONSE" | jq .

# Extract token
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token')

if [ "$TOKEN" != "null" ] && [ "$TOKEN" != "" ]; then
    echo ""
    echo "✅ Login successful! Token: ${TOKEN:0:20}..."
    
    echo ""
    echo "2. Testing Categories..."
    curl -s -X GET http://127.0.0.1:8000/api/v1/categories | jq '.data | length'
    
    echo ""
    echo "3. Testing Jobs (with auth)..."
    curl -s -X GET http://127.0.0.1:8000/api/v1/jobs \
      -H "Authorization: Bearer $TOKEN" | jq '.data.data | length'
    
    echo ""
    echo "4. Testing User Profile..."
    curl -s -X GET http://127.0.0.1:8000/api/v1/user/profile \
      -H "Authorization: Bearer $TOKEN" | jq '.data.phone'
    
    echo ""
    echo "✅ All tests completed!"
else
    echo "❌ Login failed!"
fi
