#!/bin/bash

# Fundi API Mobile App Test Script
# This script tests all mobile app endpoints for the Fundi API
# Usage: ./test_mobile_app.sh [base_url]

# Configuration
BASE_URL=${1:-"http://localhost:8000/api/v1"}
CUSTOMER_PHONE="+255712345678"
FUNDI_PHONE="+255712345679"
ADMIN_PHONE="+255712345680"
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
ADMIN_TOKEN=""
JOB_ID=""
APPLICATION_ID=""
PORTFOLIO_ID=""
CATEGORY_ID=""

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

# Function to make API request
make_request() {
    local method="$1"
    local endpoint="$2"
    local data="$3"
    local token="$4"
    local expected_status="$5"
    
    local headers="Content-Type: application/json"
    if [ -n "$token" ]; then
        headers="$headers -H Authorization: Bearer $token"
    fi
    
    local response
    if [ -n "$data" ]; then
        response=$(curl -s -w "\n%{http_code}" -X "$method" \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $token" \
            -d "$data" \
            "$BASE_URL$endpoint" 2>/dev/null)
    else
        response=$(curl -s -w "\n%{http_code}" -X "$method" \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $token" \
            "$BASE_URL$endpoint" 2>/dev/null)
    fi
    
    local http_code=$(echo "$response" | tail -n1)
    local body=$(echo "$response" | head -n -1)
    
    if [ "$http_code" = "$expected_status" ]; then
        echo "PASS"
    else
        echo "FAIL - Expected: $expected_status, Got: $http_code"
    fi
}

# Function to extract value from JSON response
extract_json_value() {
    local json="$1"
    local key="$2"
    echo "$json" | grep -o "\"$key\":\"[^\"]*\"" | cut -d'"' -f4
}

# Function to extract token from response
extract_token() {
    local response="$1"
    echo "$response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4
}

# Function to extract ID from response
extract_id() {
    local response="$1"
    echo "$response" | grep -o '"id":[0-9]*' | cut -d':' -f2
}

echo -e "${BLUE}🚀 Starting Fundi API Mobile App Tests${NC}"
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

# Test 3: Admin Registration
echo -e "\n${YELLOW}3. Testing Admin Registration${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -d "{\"phone\":\"$ADMIN_PHONE\",\"password\":\"$PASSWORD\",\"password_confirmation\":\"$PASSWORD\",\"role\":\"admin\"}" \
    "$BASE_URL/auth/register" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    ADMIN_TOKEN=$(extract_token "$response")
    print_result "Admin Registration" "PASS" "$response"
else
    print_result "Admin Registration" "FAIL" "$response"
fi

# Test 4: Customer Login
echo -e "\n${YELLOW}4. Testing Customer Login${NC}"
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

# Test 5: Fundi Login
echo -e "\n${YELLOW}5. Testing Fundi Login${NC}"
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

# Test 6: Get Categories
echo -e "\n${YELLOW}6. Testing Get Categories${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/categories" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    CATEGORY_ID=$(extract_id "$response")
    print_result "Get Categories" "PASS" "$response"
else
    print_result "Get Categories" "FAIL" "$response"
fi

# Test 7: Create Job (Customer)
echo -e "\n${YELLOW}7. Testing Create Job${NC}"
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

# Test 8: Get Jobs
echo -e "\n${YELLOW}8. Testing Get Jobs${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/jobs" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Jobs" "PASS" "$response"
else
    print_result "Get Jobs" "FAIL" "$response"
fi

# Test 9: Get Job by ID
echo -e "\n${YELLOW}9. Testing Get Job by ID${NC}"
if [ -n "$JOB_ID" ]; then
    response=$(curl -s -X GET \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $CUSTOMER_TOKEN" \
        "$BASE_URL/jobs/$JOB_ID" 2>/dev/null)
    
    if echo "$response" | grep -q '"success":true'; then
        print_result "Get Job by ID" "PASS" "$response"
    else
        print_result "Get Job by ID" "FAIL" "$response"
    fi
else
    print_result "Get Job by ID" "SKIP" "No job ID available"
fi

# Test 10: Apply for Job (Fundi)
echo -e "\n${YELLOW}10. Testing Apply for Job${NC}"
if [ -n "$JOB_ID" ]; then
    response=$(curl -s -X POST \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $FUNDI_TOKEN" \
        -d "{\"requirements\":\"Need 2 helpers, 3 days work\",\"budget_breakdown\":{\"materials\":20000,\"labor\":15000,\"transport\":5000},\"estimated_time\":3,\"message\":\"I can complete this job efficiently\"}" \
        "$BASE_URL/jobs/$JOB_ID/apply" 2>/dev/null)
    
    if echo "$response" | grep -q '"success":true'; then
        APPLICATION_ID=$(extract_id "$response")
        print_result "Apply for Job" "PASS" "$response"
    else
        print_result "Apply for Job" "FAIL" "$response"
    fi
else
    print_result "Apply for Job" "SKIP" "No job ID available"
fi

# Test 11: Get Job Applications
echo -e "\n${YELLOW}11. Testing Get Job Applications${NC}"
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

# Test 12: Get My Applications (Fundi)
echo -e "\n${YELLOW}12. Testing Get My Applications${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $FUNDI_TOKEN" \
    "$BASE_URL/job-applications/my-applications" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get My Applications" "PASS" "$response"
else
    print_result "Get My Applications" "FAIL" "$response"
fi

# Test 13: Create Portfolio (Fundi)
echo -e "\n${YELLOW}13. Testing Create Portfolio${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $FUNDI_TOKEN" \
    -d "{\"title\":\"Plumbing Portfolio\",\"description\":\"My plumbing work portfolio\",\"category_id\":1,\"location\":\"Dar es Salaam\"}" \
    "$BASE_URL/portfolio" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    PORTFOLIO_ID=$(extract_id "$response")
    print_result "Create Portfolio" "PASS" "$response"
else
    print_result "Create Portfolio" "FAIL" "$response"
fi

# Test 14: Get My Portfolio
echo -e "\n${YELLOW}14. Testing Get My Portfolio${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $FUNDI_TOKEN" \
    "$BASE_URL/portfolio/my-portfolio" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get My Portfolio" "PASS" "$response"
else
    print_result "Get My Portfolio" "FAIL" "$response"
fi

# Test 15: Get Fundi Feed
echo -e "\n${YELLOW}15. Testing Get Fundi Feed${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/feeds/fundis" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Fundi Feed" "PASS" "$response"
else
    print_result "Get Fundi Feed" "FAIL" "$response"
fi

# Test 16: Get Job Feed
echo -e "\n${YELLOW}16. Testing Get Job Feed${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $FUNDI_TOKEN" \
    "$BASE_URL/feeds/jobs" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Job Feed" "PASS" "$response"
else
    print_result "Get Job Feed" "FAIL" "$response"
fi

# Test 17: Get Nearby Fundis
echo -e "\n${YELLOW}17. Testing Get Nearby Fundis${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/feeds/nearby-fundis?latitude=-6.7924&longitude=39.2083&radius=10" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Nearby Fundis" "PASS" "$response"
else
    print_result "Get Nearby Fundis" "FAIL" "$response"
fi

# Test 18: Get Notifications
echo -e "\n${YELLOW}18. Testing Get Notifications${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/notifications" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Notifications" "PASS" "$response"
else
    print_result "Get Notifications" "FAIL" "$response"
fi

# Test 19: Get Payment Plans
echo -e "\n${YELLOW}19. Testing Get Payment Plans${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/payments/plans" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Payment Plans" "PASS" "$response"
else
    print_result "Get Payment Plans" "FAIL" "$response"
fi

# Test 20: Get Current Payment Plan
echo -e "\n${YELLOW}20. Testing Get Current Payment Plan${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/payments/current-plan" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Current Payment Plan" "PASS" "$response"
else
    print_result "Get Current Payment Plan" "FAIL" "$response"
fi

# Test 21: Update User Profile
echo -e "\n${YELLOW}21. Testing Update User Profile${NC}"
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

# Test 22: Update Fundi Profile
echo -e "\n${YELLOW}22. Testing Update Fundi Profile${NC}"
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

# Test 23: Get Fundi Profile by ID
echo -e "\n${YELLOW}23. Testing Get Fundi Profile by ID${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/users/fundi/2" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Fundi Profile by ID" "PASS" "$response"
else
    print_result "Get Fundi Profile by ID" "FAIL" "$response"
fi

# Test 24: Get Settings
echo -e "\n${YELLOW}24. Testing Get Settings${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/settings" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Settings" "PASS" "$response"
else
    print_result "Get Settings" "FAIL" "$response"
fi

# Test 25: Send OTP
echo -e "\n${YELLOW}25. Testing Send OTP${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -d "{\"phone\":\"$CUSTOMER_PHONE\",\"type\":\"registration\"}" \
    "$BASE_URL/auth/send-otp" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Send OTP" "PASS" "$response"
else
    print_result "Send OTP" "FAIL" "$response"
fi

# Test 26: Forgot Password
echo -e "\n${YELLOW}26. Testing Forgot Password${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -d "{\"phone\":\"$CUSTOMER_PHONE\"}" \
    "$BASE_URL/auth/forgot-password" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Forgot Password" "PASS" "$response"
else
    print_result "Forgot Password" "FAIL" "$response"
fi

# Test 27: Change Password
echo -e "\n${YELLOW}27. Testing Change Password${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    -d "{\"current_password\":\"$PASSWORD\",\"password\":\"newpassword123\",\"password_confirmation\":\"newpassword123\"}" \
    "$BASE_URL/auth/change-password" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Change Password" "PASS" "$response"
else
    print_result "Change Password" "FAIL" "$response"
fi

# Test 28: Logout
echo -e "\n${YELLOW}28. Testing Logout${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/auth/logout" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Logout" "PASS" "$response"
else
    print_result "Logout" "FAIL" "$response"
fi

# Test 29: Get Fundi Application Requirements
echo -e "\n${YELLOW}29. Testing Get Fundi Application Requirements${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $FUNDI_TOKEN" \
    "$BASE_URL/fundi-applications/requirements" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Fundi Application Requirements" "PASS" "$response"
else
    print_result "Get Fundi Application Requirements" "FAIL" "$response"
fi

# Test 30: Get Fundi Application Status
echo -e "\n${YELLOW}30. Testing Get Fundi Application Status${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $FUNDI_TOKEN" \
    "$BASE_URL/fundi-applications/status" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Fundi Application Status" "PASS" "$response"
else
    print_result "Get Fundi Application Status" "FAIL" "$response"
fi

# Test 31: Get Portfolio Status
echo -e "\n${YELLOW}31. Testing Get Portfolio Status${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $FUNDI_TOKEN" \
    "$BASE_URL/portfolio/status" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Portfolio Status" "PASS" "$response"
else
    print_result "Get Portfolio Status" "FAIL" "$response"
fi

# Test 32: Get Payment History
echo -e "\n${YELLOW}32. Testing Get Payment History${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/payments/history" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Payment History" "PASS" "$response"
else
    print_result "Get Payment History" "FAIL" "$response"
fi

# Test 33: Check Action Permission
echo -e "\n${YELLOW}33. Testing Check Action Permission${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    -d "{\"action\":\"create_job\"}" \
    "$BASE_URL/payments/check-permission" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Check Action Permission" "PASS" "$response"
else
    print_result "Check Action Permission" "FAIL" "$response"
fi

# Test 34: Get Notification Settings
echo -e "\n${YELLOW}34. Testing Get Notification Settings${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/notifications/settings" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Notification Settings" "PASS" "$response"
else
    print_result "Get Notification Settings" "FAIL" "$response"
fi

# Test 35: Mark All Notifications as Read
echo -e "\n${YELLOW}35. Testing Mark All Notifications as Read${NC}"
response=$(curl -s -X PUT \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/notifications/read-all" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Mark All Notifications as Read" "PASS" "$response"
else
    print_result "Mark All Notifications as Read" "FAIL" "$response"
fi

# Test 36: Clear All Notifications
echo -e "\n${YELLOW}36. Testing Clear All Notifications${NC}"
response=$(curl -s -X DELETE \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/notifications/clear-all" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Clear All Notifications" "PASS" "$response"
else
    print_result "Clear All Notifications" "FAIL" "$response"
fi

# Test 37: Get Themes
echo -e "\n${YELLOW}37. Testing Get Themes${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/settings/themes" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Themes" "PASS" "$response"
else
    print_result "Get Themes" "FAIL" "$response"
fi

# Test 38: Get Languages
echo -e "\n${YELLOW}38. Testing Get Languages${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    "$BASE_URL/settings/languages" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Languages" "PASS" "$response"
else
    print_result "Get Languages" "FAIL" "$response"
fi

# Test 39: Update Privacy Settings
echo -e "\n${YELLOW}39. Testing Update Privacy Settings${NC}"
response=$(curl -s -X PUT \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    -d "{\"profile_visibility\":\"public\",\"contact_visibility\":\"private\"}" \
    "$BASE_URL/settings/privacy" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Update Privacy Settings" "PASS" "$response"
else
    print_result "Update Privacy Settings" "FAIL" "$response"
fi

# Test 40: Update Notification Settings
echo -e "\n${YELLOW}40. Testing Update Notification Settings${NC}"
response=$(curl -s -X PUT \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    -d "{\"push_notifications\":true,\"email_notifications\":false,\"sms_notifications\":true}" \
    "$BASE_URL/settings/notifications" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Update Notification Settings" "PASS" "$response"
else
    print_result "Update Notification Settings" "FAIL" "$response"
fi

# Test 41: Send Test Notification
echo -e "\n${YELLOW}41. Testing Send Test Notification${NC}"
response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $CUSTOMER_TOKEN" \
    -d "{\"title\":\"Test Notification\",\"message\":\"This is a test notification\"}" \
    "$BASE_URL/notifications/test" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Send Test Notification" "PASS" "$response"
else
    print_result "Send Test Notification" "FAIL" "$response"
fi

# Test 42: Get Work Approval Pending Items
echo -e "\n${YELLOW}42. Testing Get Work Approval Pending Items${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    "$BASE_URL/work-approval/portfolio-pending" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Work Approval Pending Items" "PASS" "$response"
else
    print_result "Get Work Approval Pending Items" "FAIL" "$response"
fi

# Test 43: Get Work Approval Pending Submissions
echo -e "\n${YELLOW}43. Testing Get Work Approval Pending Submissions${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    "$BASE_URL/work-approval/submissions-pending" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Work Approval Pending Submissions" "PASS" "$response"
else
    print_result "Get Work Approval Pending Submissions" "FAIL" "$response"
fi

# Test 44: Get Admin Users
echo -e "\n${YELLOW}44. Testing Get Admin Users${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    "$BASE_URL/admin/users" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Admin Users" "PASS" "$response"
else
    print_result "Get Admin Users" "FAIL" "$response"
fi

# Test 45: Get Admin Jobs
echo -e "\n${YELLOW}45. Testing Get Admin Jobs${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    "$BASE_URL/admin/jobs" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Admin Jobs" "PASS" "$response"
else
    print_result "Get Admin Jobs" "FAIL" "$response"
fi

# Test 46: Get Admin Job Applications
echo -e "\n${YELLOW}46. Testing Get Admin Job Applications${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    "$BASE_URL/admin/job-applications" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Admin Job Applications" "PASS" "$response"
else
    print_result "Get Admin Job Applications" "FAIL" "$response"
fi

# Test 47: Get Admin Portfolios
echo -e "\n${YELLOW}47. Testing Get Admin Portfolios${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    "$BASE_URL/admin/portfolio" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Admin Portfolios" "PASS" "$response"
else
    print_result "Get Admin Portfolios" "FAIL" "$response"
fi

# Test 48: Get Admin Payment Plans
echo -e "\n${YELLOW}48. Testing Get Admin Payment Plans${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    "$BASE_URL/admin/payment-plans" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Admin Payment Plans" "PASS" "$response"
else
    print_result "Get Admin Payment Plans" "FAIL" "$response"
fi

# Test 49: Get Admin Payment Statistics
echo -e "\n${YELLOW}49. Testing Get Admin Payment Statistics${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    "$BASE_URL/admin/payment-statistics" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get Admin Payment Statistics" "PASS" "$response"
else
    print_result "Get Admin Payment Statistics" "FAIL" "$response"
fi

# Test 50: Get System Health
echo -e "\n${YELLOW}50. Testing Get System Health${NC}"
response=$(curl -s -X GET \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    "$BASE_URL/admin/monitor/system-health" 2>/dev/null)

if echo "$response" | grep -q '"success":true'; then
    print_result "Get System Health" "PASS" "$response"
else
    print_result "Get System Health" "FAIL" "$response"
fi

# Print Summary
echo -e "\n${BLUE}=================================================="
echo -e "📊 TEST SUMMARY${NC}"
echo -e "${BLUE}=================================================="
echo -e "${GREEN}Total Tests: $TOTAL_TESTS${NC}"
echo -e "${GREEN}Passed: $PASSED_TESTS${NC}"
echo -e "${RED}Failed: $FAILED_TESTS${NC}"

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "\n${GREEN}🎉 All tests passed! The mobile app API is working correctly.${NC}"
    exit 0
else
    echo -e "\n${RED}❌ Some tests failed. Please check the API implementation.${NC}"
    exit 1
fi
