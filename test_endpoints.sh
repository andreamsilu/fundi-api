#!/bin/bash

# Fundi API Endpoint Testing Script
# Comprehensive testing of all API endpoints

BASE_URL="http://localhost:8000/api/v1"
TOKEN=""
TEST_PHONE="+255123456789"
TEST_PASSWORD="password123"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counter
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Function to make HTTP requests
make_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    local expected_code=$4
    local test_name=$5
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    if [ -n "$data" ]; then
        response=$(curl -s -w "\n%{http_code}" -X $method \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            ${TOKEN:+-H "Authorization: Bearer $TOKEN"} \
            -d "$data" \
            "$BASE_URL$endpoint")
    else
        response=$(curl -s -w "\n%{http_code}" -X $method \
            -H "Accept: application/json" \
            ${TOKEN:+-H "Authorization: Bearer $TOKEN"} \
            "$BASE_URL$endpoint")
    fi
    
    http_code=$(echo "$response" | tail -n1)
    response_body=$(echo "$response" | head -n -1)
    
    if [ "$http_code" -eq "$expected_code" ]; then
        echo -e "${GREEN}✅ PASS${NC} - $test_name"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}❌ FAIL${NC} - $test_name (Expected: $expected_code, Got: $http_code)"
        echo "   Response: $(echo "$response_body" | head -c 100)..."
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
    echo
}

# Function to extract token from login response
extract_token() {
    local response=$1
    echo "$response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4
}

echo -e "${BLUE}🚀 Starting Fundi API Comprehensive Testing${NC}"
echo "=========================================="
echo

# Test 1: Public Endpoints
echo -e "${YELLOW}🌐 TESTING PUBLIC ENDPOINTS${NC}"
echo "=========================="
echo

make_request "GET" "/test" "" 200 "API Health Check"
make_request "GET" "/debug/auth" "" 200 "Debug Auth Endpoint"

# Test 2: Authentication Endpoints
echo -e "${YELLOW}🔐 TESTING AUTHENTICATION ENDPOINTS${NC}"
echo "==================================="
echo

# Login to get token
echo "Logging in to get authentication token..."
login_response=$(curl -s -X POST "$BASE_URL/auth/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"phone\":\"$TEST_PHONE\",\"password\":\"$TEST_PASSWORD\"}")

TOKEN=$(extract_token "$login_response")

if [ -n "$TOKEN" ]; then
    echo -e "${GREEN}✅ Login successful, token obtained${NC}"
else
    echo -e "${RED}❌ Login failed, no token obtained${NC}"
fi
echo

make_request "POST" "/auth/login" "{\"phone\":\"$TEST_PHONE\",\"password\":\"$TEST_PASSWORD\"}" 200 "User Login"
make_request "GET" "/auth/me" "" 200 "Get User Profile"

if [ -n "$TOKEN" ]; then
    make_request "POST" "/auth/token-info" "{\"token\":\"$TOKEN\"}" 200 "Token Information"
fi

# Test 3: Protected Endpoints
echo -e "${YELLOW}🛡️ TESTING PROTECTED ENDPOINTS${NC}"
echo "=============================="
echo

make_request "GET" "/test-protected" "" 200 "Protected Test Route"
make_request "GET" "/categories" "" 200 "Get Categories"
make_request "GET" "/jobs" "" 200 "Get Jobs"
make_request "GET" "/notifications" "" 200 "Get Notifications"
make_request "GET" "/users/me" "" 200 "Get User Profile (Alternative)"
make_request "GET" "/my-applications" "" 200 "Get My Applications"
make_request "GET" "/payments" "" 200 "Get Payments"
make_request "GET" "/ratings/my-ratings" "" 200 "Get My Ratings"

# Test 4: Job Endpoints
echo -e "${YELLOW}💼 TESTING JOB ENDPOINTS${NC}"
echo "========================"
echo

make_request "GET" "/jobs/1" "" 200 "Get Specific Job"
make_request "GET" "/jobs/1/applications" "" 200 "Get Job Applications"

# Test 5: Admin Endpoints
echo -e "${YELLOW}👑 TESTING ADMIN ENDPOINTS${NC}"
echo "=========================="
echo

make_request "GET" "/admin/users" "" 200 "Get All Users (Admin)"
make_request "GET" "/admin/jobs" "" 200 "Get All Jobs (Admin)"
make_request "GET" "/admin/payments" "" 200 "Get All Payments (Admin)"
make_request "GET" "/admin/monitor/system-health" "" 200 "Get System Health"
make_request "GET" "/admin/settings" "" 200 "Get Settings"

# Test 6: Error Handling
echo -e "${YELLOW}⚠️ TESTING ERROR HANDLING${NC}"
echo "========================="
echo

# Test without token
TOKEN=""
make_request "GET" "/auth/me" "" 401 "Access Protected Route Without Token"

# Test with invalid token
TOKEN="invalid_token_123"
make_request "GET" "/test-protected" "" 401 "Access Protected Route With Invalid Token"

# Test invalid login
make_request "POST" "/auth/login" "{\"phone\":\"+255999999999\",\"password\":\"wrongpassword\"}" 401 "Login With Invalid Credentials"

# Test non-existent endpoint
make_request "GET" "/non-existent-endpoint" "" 404 "Access Non-existent Endpoint"

# Test 7: Logout
echo -e "${YELLOW}🚪 TESTING LOGOUT${NC}"
echo "================="
echo

# Get fresh token for logout test
login_response=$(curl -s -X POST "$BASE_URL/auth/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"phone\":\"$TEST_PHONE\",\"password\":\"$TEST_PASSWORD\"}")

TOKEN=$(extract_token "$login_response")

if [ -n "$TOKEN" ]; then
    make_request "POST" "/auth/logout" "" 200 "User Logout"
    make_request "GET" "/auth/me" "" 401 "Access Protected Route After Logout"
fi

# Generate Report
echo -e "${BLUE}📊 TESTING REPORT${NC}"
echo "================="
echo

SUCCESS_RATE=0
if [ $TOTAL_TESTS -gt 0 ]; then
    SUCCESS_RATE=$(( (PASSED_TESTS * 100) / TOTAL_TESTS ))
fi

echo "Total Tests: $TOTAL_TESTS"
echo -e "Passed: ${GREEN}$PASSED_TESTS ✅${NC}"
echo -e "Failed: ${RED}$FAILED_TESTS ❌${NC}"
echo "Success Rate: $SUCCESS_RATE%"
echo

if [ $SUCCESS_RATE -ge 95 ]; then
    echo -e "${GREEN}🌟 EXCELLENT! API is performing exceptionally well!${NC}"
elif [ $SUCCESS_RATE -ge 90 ]; then
    echo -e "${GREEN}✅ GOOD! API is working well with minor issues.${NC}"
elif [ $SUCCESS_RATE -ge 80 ]; then
    echo -e "${YELLOW}⚠️ FAIR! API has some issues that need attention.${NC}"
else
    echo -e "${RED}❌ POOR! API has significant issues that need immediate attention.${NC}"
fi

echo
echo -e "${BLUE}🎯 Testing completed!${NC}"
