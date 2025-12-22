#!/bin/bash

# End-to-End Testing Script
# Tests complete workflows from start to finish

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║        End-to-End Testing - Candidacy Platform                ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Configuration
API_GATEWAY="http://localhost:8080"
AUTH_TOKEN=""
CANDIDATE_ID=""
VACANCY_ID=""
MATCH_ID=""

TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Function to run test
run_test() {
    local description=$1
    shift
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    echo -n "$description... "
    
    if "$@" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ PASS${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        return 0
    else
        echo -e "${RED}❌ FAIL${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
}

# Function to make API call
api_call() {
    local method=$1
    local endpoint=$2
    local data=$3
    local auth=${4:-true}
    
    local curl_cmd="curl -s -X $method"
    
    if [ "$auth" = "true" ] && [ -n "$AUTH_TOKEN" ]; then
        curl_cmd="$curl_cmd -H 'Authorization: Bearer $AUTH_TOKEN'"
    fi
    
    curl_cmd="$curl_cmd -H 'Content-Type: application/json' -H 'Accept: application/json'"
    
    if [ -n "$data" ]; then
        curl_cmd="$curl_cmd -d '$data'"
    fi
    
    curl_cmd="$curl_cmd '$API_GATEWAY$endpoint'"
    
    eval $curl_cmd
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Workflow 1: User Authentication and Setup"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Step 1: Check setup status
echo -n "Step 1: Checking setup status... "
SETUP_RESPONSE=$(api_call "GET" "/api/auth/setup/check" "" false)
if echo "$SETUP_RESPONSE" | grep -q "needs_setup"; then
    echo -e "${GREEN}✅ PASS${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${YELLOW}⚠️  Already set up${NC}"
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

# Step 2: Login (or create admin if needed)
echo -n "Step 2: Authenticating user... "
LOGIN_RESPONSE=$(api_call "POST" "/api/auth/login" '{"email":"admin@test.com","password":"password"}' false)

if echo "$LOGIN_RESPONSE" | grep -q "token"; then
    AUTH_TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
    echo -e "${GREEN}✅ PASS${NC} (Token obtained)"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${YELLOW}⚠️  Login failed, attempting to create admin...${NC}"
    CREATE_ADMIN=$(api_call "POST" "/api/auth/setup/create-admin" \
        '{"name":"Admin User","email":"admin@test.com","password":"password","password_confirmation":"password"}' false)
    
    if echo "$CREATE_ADMIN" | grep -q "token"; then
        AUTH_TOKEN=$(echo "$CREATE_ADMIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
        echo -e "${GREEN}✅ Admin created${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}❌ FAIL${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

if [ -z "$AUTH_TOKEN" ]; then
    echo -e "${RED}Cannot proceed without authentication token${NC}"
    exit 1
fi

# Step 3: Verify authentication
echo -n "Step 3: Verifying authentication... "
ME_RESPONSE=$(api_call "GET" "/api/auth/me" "")
if echo "$ME_RESPONSE" | grep -q "email"; then
    echo -e "${GREEN}✅ PASS${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}❌ FAIL${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Workflow 2: Vacancy Creation"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Step 1: Create a vacancy
echo -n "Step 1: Creating vacancy... "
VACANCY_DATA='{
    "title": "Senior Software Engineer",
    "department": "Engineering",
    "location": "Remote",
    "employment_type": "full-time",
    "experience_level": "senior",
    "description": "We are looking for a senior software engineer",
    "requirements": "5+ years of experience",
    "status": "open"
}'
VACANCY_RESPONSE=$(api_call "POST" "/api/vacancies" "$VACANCY_DATA")

if echo "$VACANCY_RESPONSE" | grep -q "id"; then
    VACANCY_ID=$(echo "$VACANCY_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    echo -e "${GREEN}✅ PASS${NC} (Vacancy ID: $VACANCY_ID)"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}❌ FAIL${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

# Step 2: List vacancies
echo -n "Step 2: Listing vacancies... "
VACANCIES_LIST=$(api_call "GET" "/api/vacancies" "")
if echo "$VACANCIES_LIST" | grep -q "data"; then
    echo -e "${GREEN}✅ PASS${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}❌ FAIL${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Workflow 3: Candidate Management"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Step 1: Create a candidate
echo -n "Step 1: Creating candidate... "
CANDIDATE_DATA='{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "+1234567890",
    "status": "active"
}'
CANDIDATE_RESPONSE=$(api_call "POST" "/api/candidates" "$CANDIDATE_DATA")

if echo "$CANDIDATE_RESPONSE" | grep -q "id"; then
    CANDIDATE_ID=$(echo "$CANDIDATE_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    echo -e "${GREEN}✅ PASS${NC} (Candidate ID: $CANDIDATE_ID)"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}❌ FAIL${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

# Step 2: List candidates
echo -n "Step 2: Listing candidates... "
CANDIDATES_LIST=$(api_call "GET" "/api/candidates" "")
if echo "$CANDIDATES_LIST" | grep -q "data"; then
    echo -e "${GREEN}✅ PASS${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}❌ FAIL${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Workflow 4: Admin Settings"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Step 1: Get admin settings
echo -n "Step 1: Getting admin settings... "
SETTINGS_RESPONSE=$(api_call "GET" "/api/admin/settings" "")
if echo "$SETTINGS_RESPONSE" | grep -q "settings"; then
    echo -e "${GREEN}✅ PASS${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}❌ FAIL${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

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
    echo -e "${GREEN}🎉 All end-to-end tests passed!${NC}"
    echo ""
    echo "Test artifacts created:"
    [ -n "$VACANCY_ID" ] && echo "  - Vacancy ID: $VACANCY_ID"
    [ -n "$CANDIDATE_ID" ] && echo "  - Candidate ID: $CANDIDATE_ID"
    exit 0
else
    PASS_RATE=$((PASSED_TESTS * 100 / TOTAL_TESTS))
    echo -e "${YELLOW}⚠️  Pass rate: $PASS_RATE%${NC}"
    exit 1
fi
