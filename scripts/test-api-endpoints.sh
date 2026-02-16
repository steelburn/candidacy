#!/bin/bash

# API Endpoint Testing Script
# Tests critical API endpoints for all services

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Load environment variables if .env exists
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║           API Endpoint Testing - Candidacy Platform            ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Configuration
API_GATEWAY="${PUBLIC_API_URL:-http://localhost:9080}"
echo "Using API Gateway: $API_GATEWAY"
AUTH_TOKEN=""

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Function to test endpoint
test_endpoint() {
    local method=$1
    local endpoint=$2
    local expected_status=$3
    local description=$4
    local data=$5
    local auth_required=${6:-false}
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    echo -n "Testing: $description... "
    
    local curl_cmd="curl -s -w '\n%{http_code}' -X $method"
    
    if [ "$auth_required" = "true" ] && [ -n "$AUTH_TOKEN" ]; then
        curl_cmd="$curl_cmd -H 'Authorization: Bearer $AUTH_TOKEN'"
    fi
    
    curl_cmd="$curl_cmd -H 'Content-Type: application/json' -H 'Accept: application/json'"
    
    if [ -n "$data" ]; then
        curl_cmd="$curl_cmd -d '$data'"
    fi
    
    curl_cmd="$curl_cmd '$API_GATEWAY$endpoint'"
    
    local response
    response=$(eval $curl_cmd 2>/dev/null)
    
    local status_code=$(echo "$response" | tail -1)
    local body=$(echo "$response" | head -n -1)
    
    if [ "$status_code" = "$expected_status" ]; then
        echo -e "${GREEN}✅ PASS${NC} (Status: $status_code)"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        return 0
    else
        echo -e "${RED}❌ FAIL${NC} (Expected: $expected_status, Got: $status_code)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        if [ -n "$body" ]; then
            echo "  Response: $(echo $body | head -c 200)"
        fi
        return 1
    fi
}

# Function to extract token from login response
extract_token() {
    local response=$1
    echo "$response" | grep -o '"token":"[^"]*' | cut -d'"' -f4
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Health Check Tests"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Health checks for all services
test_endpoint "GET" "/api/auth/health" "200" "Auth Service Health Check"
test_endpoint "GET" "/api/candidates/health" "200" "Candidate Service Health Check"
test_endpoint "GET" "/api/vacancies/health" "200" "Vacancy Service Health Check"
test_endpoint "GET" "/api/ai/health" "200" "AI Service Health Check"
test_endpoint "GET" "/api/matches/health" "200" "Matching Service Health Check"
test_endpoint "GET" "/api/interviews/health" "200" "Interview Service Health Check"
test_endpoint "GET" "/api/offers/health" "200" "Offer Service Health Check"
test_endpoint "GET" "/api/onboarding/health" "200" "Onboarding Service Health Check"
test_endpoint "GET" "/api/reports/health" "200" "Reporting Service Health Check"
test_endpoint "GET" "/api/admin/health" "200" "Admin Service Health Check"
test_endpoint "GET" "/api/notifications/health" "200" "Notification Service Health Check"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Authentication Tests"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test setup check
test_endpoint "GET" "/api/auth/setup/check" "200" "Setup Check Endpoint"

# Try to login (this might fail if no user exists, which is expected)
echo -n "Attempting login to get auth token... "
LOGIN_RESPONSE=$(curl -s -X POST "$API_GATEWAY/api/auth/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"admin@test.com","password":"password"}' 2>/dev/null)

if echo "$LOGIN_RESPONSE" | grep -q "token"; then
    AUTH_TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
    echo -e "${GREEN}✅ Success${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${YELLOW}⚠️  No admin user found (expected for fresh install)${NC}"
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

# Test protected endpoints if we have a token
if [ -n "$AUTH_TOKEN" ]; then
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "Protected Endpoint Tests (with authentication)"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    test_endpoint "GET" "/api/auth/me" "200" "Get Current User" "" true
    test_endpoint "GET" "/api/candidates" "200" "List Candidates" "" true
    test_endpoint "GET" "/api/vacancies" "200" "List Vacancies" "" true
    test_endpoint "GET" "/api/admin/settings" "200" "Get Admin Settings" "" true
    
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "Vacancy CRUD Tests"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    # Create vacancy
    test_endpoint "POST" "/api/vacancies" "201" "Create Vacancy" \
        '{"title":"API Test Vacancy","department":"Engineering","location":"Remote","employment_type":"full-time","experience_level":"senior","description":"Test vacancy","requirements":"5+ years","status":"open"}' true
    
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "Matching Service Tests"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    test_endpoint "GET" "/api/matches" "200" "List All Matches" "" true
    test_endpoint "GET" "/api/matches?candidate_id=1" "200" "Get Matches for Candidate" "" true
    
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "Interview Service Tests"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    test_endpoint "GET" "/api/interviews" "200" "List Interviews" "" true
    test_endpoint "GET" "/api/interviews/upcoming/all" "200" "Get Upcoming Interviews" "" true
    
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "AI Service Tests"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    # Note: These may return error responses if AI is not configured, but endpoint should exist
    echo -n "Testing: AI Generate Questions endpoint... "
    QUESTIONS_RESPONSE=$(curl -s -w '\n%{http_code}' -X POST "$API_GATEWAY/api/ai/generate-questions" \
        -H "Authorization: Bearer $AUTH_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{"vacancy":{"title":"Test","requirements":"Test"},"candidate":{"skills":["PHP"]}}' 2>/dev/null)
    QUESTIONS_STATUS=$(echo "$QUESTIONS_RESPONSE" | tail -1)
    if [[ "$QUESTIONS_STATUS" =~ ^(200|201|400|500)$ ]]; then
        echo -e "${GREEN}✅ PASS${NC} (Status: $QUESTIONS_STATUS - endpoint exists)"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}❌ FAIL${NC} (Status: $QUESTIONS_STATUS)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Unauthenticated Access Tests (should fail)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# These should return 401 or 403
test_endpoint "GET" "/api/candidates" "401" "List Candidates (no auth)" "" false
test_endpoint "GET" "/api/vacancies" "401" "List Vacancies (no auth)" "" false

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                      Test Summary                              ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "Total Tests:  $TOTAL_TESTS"
echo -e "${GREEN}Passed:       $PASSED_TESTS${NC}"
echo -e "${RED}Failed:       $FAILED_TESTS${NC}"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}🎉 All API endpoint tests passed!${NC}"
    exit 0
else
    PASS_RATE=$((PASSED_TESTS * 100 / TOTAL_TESTS))
    echo -e "${YELLOW}⚠️  Pass rate: $PASS_RATE%${NC}"
    exit 1
fi
