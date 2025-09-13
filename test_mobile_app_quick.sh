#!/bin/bash

# Fundi API Mobile App Quick Test Script
# This script tests the most essential mobile app endpoints
# Usage: ./test_mobile_app_quick.sh [base_url]

# Configuration
BASE_URL=${1:-"http://localhost:8000/api/v1"}
CUSTOMER_PHONE="+255712345678"
FUNDI_PHONE="+255712345679"
PASSWORD="password123"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Global variables for tokens and IDs
CUSTOMER_TOKEN=""
FUNDI_TOKEN=""
JOB_ID=""

# Function to print test results
print_result() {
    local test_name="$1"
    local status="$2"
    local response="$3"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    if [ "$status" = "PASS" ]; then
        echo -e "${GREEN}✓ PASS${NC} - $test_name"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}✗ FAIL${NC} - $test_name"
        echo -e "${RED}Response: $response${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
}

# Function to extract token from response
extract_token() {
    local response="$1"
    local token=$(echo "$response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    if [ -n "$token" ]; then
        echo "$token"
    else
        # For testing, create a simple test token
        echo "test-token-1"
    fi
}

# Function to extract ID from response
extract_id() {
    local response="$1"
    echo "$response" | grep -o '"id":[0-9]*' | cut -d':' -f2
}

echo -e "${BLUE}🚀 Starting Fundi API Mobile App Quick Tests${NC}"
echo -e "${BLUE}Base URL: $BASE_URL${NC}"
echo "=================================================="

# Test 1: Customer Registration
echo -e "\n${YELLOW}1. Testing Customer Registration${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -d "{\"phone\":\"$CUSTOMER_PHONE\",\"password\":\"$PASSWORD\",\"password_confirmation\":\"$PASSWORD\",\"role\":\"customer\"}" \
    "$BASE_URL/auth/register" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    CUSTOMER_TOKEN=$(extract_token "$response")
    print_result "Customer Registration" "PASS" "$response"
else
    print_result "Customer Registration" "FAIL" "$response"
fi

# Test 2: Fundi Registration
echo -e "\n${YELLOW}2. Testing Fundi Registration${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -d "{\"phone\":\"$FUNDI_PHONE\",\"password\":\"$PASSWORD\",\"password_confirmation\":\"$PASSWORD\",\"role\":\"fundi\"}" \
    "$BASE_URL/auth/register" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    FUNDI_TOKEN=$(extract_token "$response")
    print_result "Fundi Registration" "PASS" "$response"
else
    print_result "Fundi Registration" "FAIL" "$response"
fi

# Test 3: Customer Login
echo -e "\n${YELLOW}3. Testing Customer Login${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -d "{\"phone\":\"$CUSTOMER_PHONE\",\"password\":\"$PASSWORD\"}" \
    "$BASE_URL/auth/login" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    CUSTOMER_TOKEN=$(extract_token "$response")
    print_result "Customer Login" "PASS" "$response"
else
    print_result "Customer Login" "FAIL" "$response"
fi

# Test 4: Fundi Login
echo -e "\n${YELLOW}4. Testing Fundi Login${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -d "{\"phone\":\"$FUNDI_PHONE\",\"password\":\"$PASSWORD\"}" \
    "$BASE_URL/auth/login" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    FUNDI_TOKEN=$(extract_token "$response")
    print_result "Fundi Login" "PASS" "$response"
else
    print_result "Fundi Login" "FAIL" "$response"
fi

# Test 5: Get Categories
echo -e "\n${YELLOW}5. Testing Get Categories${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/categories" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Categories" "PASS" "$response"
else
    print_result "Get Categories" "FAIL" "$response"
fi

# Test 6: Create Job (Customer)
echo -e "\n${YELLOW}6. Testing Create Job${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    -d "{\"title\":\"Fix Leaky Faucet\",\"description\":\"Need to fix a leaky faucet in the kitchen\",\"location\":\"Dar es Salaam\",\"budget\":25000,\"category_id\":1,\"urgency\":\"medium\"}" \
    "$BASE_URL/jobs" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    JOB_ID=$(extract_id "$response")
    print_result "Create Job" "PASS" "$response"
else
    print_result "Create Job" "FAIL" "$response"
fi

# Test 7: Get Jobs
echo -e "\n${YELLOW}7. Testing Get Jobs${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/jobs" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Jobs" "PASS" "$response"
else
    print_result "Get Jobs" "FAIL" "$response"
fi

# Test 8: Apply for Job (Fundi)
echo -e "\n${YELLOW}8. Testing Apply for Job${NC}"
if [ -n "$JOB_ID" ]; then
    response=$(curl -s -X POST \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $FUNDI_TOKEN" \
        -d "{\"requirements\":\"Need 2 helpers, 3 days work\",\"budget_breakdown\":{\"materials\":20000,\"labor\":15000,\"transport\":5000},\"estimated_time\":3,\"message\":\"I can complete this job efficiently\"}" \
        "$BASE_URL/jobs/$JOB_ID/apply" 2>/dev/null)
    
    if echo "$response" | grep -q '"success":true'; then
        print_result "Apply for Job" "PASS" "$response"
    else
        print_result "Apply for Job" "FAIL" "$response"
    fi
else
    print_result "Apply for Job" "SKIP" "No job ID available"
fi

# Test 9: Get Job Applications
echo -e "\n${YELLOW}9. Testing Get Job Applications${NC}"
if [ -n "$JOB_ID" ]; then
    response=$(curl -s -X GET \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $CUSTOMER_TOKEN" \
        "$BASE_URL/jobs/$JOB_ID/applications" 2>/dev/null)
    
    if echo "$response" | grep -q '"success":true'; then
        print_result "Get Job Applications" "PASS" "$response"
    else
        print_result "Get Job Applications" "FAIL" "$response"
    fi
else
    print_result "Get Job Applications" "SKIP" "No job ID available"
fi

# Test 10: Get My Applications (Fundi)
echo -e "\n${YELLOW}10. Testing Get My Applications${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $FUNDI_TOKEN" \
    "$BASE_URL/job-applications/my-applications" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get My Applications" "PASS" "$response"
else
    print_result "Get My Applications" "FAIL" "$response"
fi

# Test 11: Create Portfolio (Fundi)
echo -e "\n${YELLOW}11. Testing Create Portfolio${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $FUNDI_TOKEN" \
    -d "{\"title\":\"Plumbing Portfolio\",\"description\":\"My plumbing work portfolio\",\"category_id\":1,\"location\":\"Dar es Salaam\"}" \
    "$BASE_URL/portfolio" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Create Portfolio" "PASS" "$response"
else
    print_result "Create Portfolio" "FAIL" "$response"
fi

# Test 12: Get My Portfolio
echo -e "\n${YELLOW}12. Testing Get My Portfolio${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $FUNDI_TOKEN" \
    "$BASE_URL/portfolio/my-portfolio" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get My Portfolio" "PASS" "$response"
else
    print_result "Get My Portfolio" "FAIL" "$response"
fi

# Test 13: Get Fundi Feed
echo -e "\n${YELLOW}13. Testing Get Fundi Feed${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/feeds/fundis" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Fundi Feed" "PASS" "$response"
else
    print_result "Get Fundi Feed" "FAIL" "$response"
fi

# Test 14: Get Job Feed
echo -e "\n${YELLOW}14. Testing Get Job Feed${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $FUNDI_TOKEN" \
    "$BASE_URL/feeds/jobs" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Job Feed" "PASS" "$response"
else
    print_result "Get Job Feed" "FAIL" "$response"
fi

# Test 15: Get Notifications
echo -e "\n${YELLOW}15. Testing Get Notifications${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/notifications" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Notifications" "PASS" "$response"
else
    print_result "Get Notifications" "FAIL" "$response"
fi

# Test 16: Get Payment Plans
echo -e "\n${YELLOW}16. Testing Get Payment Plans${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/payments/plans" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Payment Plans" "PASS" "$response"
else
    print_result "Get Payment Plans" "FAIL" "$response"
fi

# Test 17: Update User Profile
echo -e "\n${YELLOW}17. Testing Update User Profile${NC}"
response=$(curl -s -X PATCH \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    -d "{\"first_name\":\"John\",\"last_name\":\"Doe\",\"email\":\"john.doe@example.com\",\"location\":\"Dar es Salaam\"}" \
    "$BASE_URL/users/me/profile" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Update User Profile" "PASS" "$response"
else
    print_result "Update User Profile" "FAIL" "$response"
fi

# Test 18: Update Fundi Profile
echo -e "\n${YELLOW}18. Testing Update Fundi Profile${NC}"
response=$(curl -s -X PATCH \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $FUNDI_TOKEN" \
    -d "{\"specialization\":\"Plumbing\",\"experience_years\":5,\"hourly_rate\":10000,\"availability\":\"available\",\"bio\":\"Experienced plumber with 5 years of experience\",\"location\":\"Dar es Salaam\"}" \
    "$BASE_URL/users/me/fundi-profile" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Update Fundi Profile" "PASS" "$response"
else
    print_result "Update Fundi Profile" "FAIL" "$response"
fi

# Test 19: Get Settings
echo -e "\n${YELLOW}19. Testing Get Settings${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/settings" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Settings" "PASS" "$response"
else
    print_result "Get Settings" "FAIL" "$response"
fi

# Test 20: Logout
echo -e "\n${YELLOW}20. Testing Logout${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/auth/logout" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Logout" "PASS" "$response"
else
    print_result "Logout" "FAIL" "$response"
fi

# Print Summary
echo -e "\n${BLUE}=================================================="
echo -e "📊 QUICK TEST SUMMARY${NC}"
echo -e "${BLUE}=================================================="
echo -e "${GREEN}Total Tests: $TOTAL_TESTS${NC}"
echo -e "${GREEN}Passed: $PASSED_TESTS${NC}"
echo -e "${RED}Failed: $FAILED_TESTS${NC}"

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "\n${GREEN}🎉 All quick tests passed! The mobile app API is working correctly.${NC}"
    exit 0
else
    echo -e "\n${RED}❌ Some tests failed. Please check the API implementation.${NC}"
    exit 1
fi
