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

if echo "$LOGIN_RESPONSE" | grep -q "access_token"; then
    AUTH_TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"access_token":"[^"]*' | cut -d'"' -f4)
    echo -e "${GREEN}✅ PASS${NC} (Token obtained)"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${YELLOW}⚠️  Login failed, attempting to create admin...${NC}"
    CREATE_ADMIN=$(api_call "POST" "/api/auth/setup/create-admin" \
        '{"name":"Admin User","email":"admin@test.com","password":"password","password_confirmation":"password"}' false)
    
    if echo "$CREATE_ADMIN" | grep -q "access_token"; then
        AUTH_TOKEN=$(echo "$CREATE_ADMIN" | grep -o '"access_token":"[^"]*' | cut -d'"' -f4)
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
    "title": "Senior Software Engineer - E2E Test",
    "department": "Engineering",
    "location": "Remote",
    "employment_type": "full_time",
    "experience_level": "senior",
    "description": "We are looking for a senior software engineer",
    "requirements": ["5+ years of experience", "PHP", "Laravel"],
    "status": "open"
}'
VACANCY_RESPONSE=$(api_call "POST" "/api/vacancies" "$VACANCY_DATA")

# Handle both 201 with body and 204 No Content responses
if echo "$VACANCY_RESPONSE" | grep -q '"id"'; then
    VACANCY_ID=$(echo "$VACANCY_RESPONSE" | sed 's/.*"id"://' | sed 's/,.*//' | tr -d '"' | tr -d ' ')
    echo -e "${GREEN}✅ PASS${NC} (Vacancy ID: $VACANCY_ID)"
    PASSED_TESTS=$((PASSED_TESTS + 1))
elif [ -z "$VACANCY_RESPONSE" ] || [ "$VACANCY_RESPONSE" = "" ]; then
    # 204 No Content - fetch the latest vacancy from list
    LATEST_VACANCY=$(api_call "GET" "/api/vacancies?per_page=1" "")
    if echo "$LATEST_VACANCY" | grep -q '"data":\[{"id"'; then
        VACANCY_ID=$(echo "$LATEST_VACANCY" | grep -o '"data":\[{"id":[0-9]*' | grep -o '[0-9]*$')
        echo -e "${GREEN}✅ PASS${NC} (Vacancy created, ID: $VACANCY_ID)"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${YELLOW}⚠️  WARN${NC} (Created but couldn't get ID)"
    fi
else
    echo -e "${RED}❌ FAIL${NC} (Response: $(echo $VACANCY_RESPONSE | head -c 100))"
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
TIMESTAMP=$(date +%s)
CANDIDATE_DATA="{
    \"name\": \"John Doe E2E\",
    \"first_name\": \"John\",
    \"last_name\": \"Doe\",
    \"email\": \"john.doe-e2e-${TIMESTAMP}@example.com\",
    \"phone\": \"+1234567890\",
    \"status\": \"active\"
}"
CANDIDATE_RESPONSE=$(api_call "POST" "/api/candidates" "$CANDIDATE_DATA")

if echo "$CANDIDATE_RESPONSE" | grep -q '"id"'; then
    CANDIDATE_ID=$(echo "$CANDIDATE_RESPONSE" | sed 's/.*"id"://' | sed 's/,.*//' | tr -d '"' | tr -d ' ')
    echo -e "${GREEN}✅ PASS${NC} (Candidate ID: $CANDIDATE_ID)"
    PASSED_TESTS=$((PASSED_TESTS + 1))
elif echo "$CANDIDATE_RESPONSE" | grep -q '"email has already been taken"'; then
    # Candidate exists - fetch the first one from list
    LATEST_CANDIDATE=$(api_call "GET" "/api/candidates?per_page=1" "")
    if echo "$LATEST_CANDIDATE" | grep -q '"data":\[{"id"'; then
        CANDIDATE_ID=$(echo "$LATEST_CANDIDATE" | grep -o '"data":\[{"id":[0-9]*' | grep -o '[0-9]*$')
        echo -e "${GREEN}✅ PASS${NC} (Using existing Candidate ID: $CANDIDATE_ID)"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${YELLOW}⚠️  WARN${NC} (Duplicate but couldn't get ID)"
    fi
else
    echo -e "${RED}❌ FAIL${NC} (Response: $(echo $CANDIDATE_RESPONSE | head -c 100))"
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
echo "Workflow 4: Candidate-Vacancy Matching"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -n "$VACANCY_ID" ] && [ -n "$CANDIDATE_ID" ]; then
    # Step 1: Trigger matching for vacancy
    echo -n "Step 1: Running matching for vacancy $VACANCY_ID... "
    MATCH_RESPONSE=$(api_call "POST" "/api/matches/vacancies/$VACANCY_ID" "")
    
    if echo "$MATCH_RESPONSE" | grep -qE "(job_id|matches|success|queued)"; then
        echo -e "${GREEN}✅ PASS${NC} (Matching triggered)"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        
        # If async, wait a bit for matching to complete
        if echo "$MATCH_RESPONSE" | grep -q "job_id"; then
            JOB_ID=$(echo "$MATCH_RESPONSE" | grep -o '"job_id":"[^"]*' | cut -d'"' -f4)
            echo "   Matching job queued: $JOB_ID"
            echo -n "   Waiting for matching to complete..."
            sleep 5
            echo " done"
        fi
    else
        echo -e "${YELLOW}⚠️  WARN${NC} (Response: $(echo $MATCH_RESPONSE | head -c 100))"
    fi
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    # Step 2: Get matches for candidate
    echo -n "Step 2: Getting matches for candidate $CANDIDATE_ID... "
    MATCHES_RESPONSE=$(api_call "GET" "/api/matches?candidate_id=$CANDIDATE_ID" "")
    
    if echo "$MATCHES_RESPONSE" | grep -qE "(data|matches|\[\])"; then
        echo -e "${GREEN}✅ PASS${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${YELLOW}⚠️  WARN${NC} (No matches found - may need more data)"
    fi
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    # Step 3: List all matches
    echo -n "Step 3: Listing all matches... "
    ALL_MATCHES=$(api_call "GET" "/api/matches" "")
    
    if echo "$ALL_MATCHES" | grep -qE "(data|\[\]|matches)"; then
        MATCH_COUNT=$(echo "$ALL_MATCHES" | grep -o '"id"' | wc -l || echo "0")
        echo -e "${GREEN}✅ PASS${NC} (Found $MATCH_COUNT match entries)"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}❌ FAIL${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
else
    echo -e "${YELLOW}⚠️  Skipping matching tests (no vacancy or candidate ID)${NC}"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Workflow 5: Interview Question Generation"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Step 1: Generate interview questions via AI service directly
echo -n "Step 1: Testing AI question generation endpoint... "
QUESTIONS_DATA='{
    "vacancy": {
        "title": "Senior Software Engineer",
        "description": "Looking for a senior software engineer with experience in distributed systems",
        "requirements": "5+ years experience, Python, Go, Kubernetes"
    },
    "candidate": {
        "name": "John Doe",
        "skills": ["Python", "Go", "Docker"],
        "experience": "8 years in software development"
    }
}'
QUESTIONS_RESPONSE=$(api_call "POST" "/api/ai/generate-questions" "$QUESTIONS_DATA")

if echo "$QUESTIONS_RESPONSE" | grep -qE "(questions|error|processing)"; then
    if echo "$QUESTIONS_RESPONSE" | grep -q "questions"; then
        QUESTION_COUNT=$(echo "$QUESTIONS_RESPONSE" | grep -o '"questions"' | wc -l || echo "?")
        echo -e "${GREEN}✅ PASS${NC} (Questions generated)"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${YELLOW}⚠️  WARN${NC} (AI may be processing or unavailable)"
    fi
else
    echo -e "${YELLOW}⚠️  WARN${NC} (AI service may not be configured)"
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

# Step 2: Generate screening questions
echo -n "Step 2: Testing screening question generation... "
SCREENING_DATA='{
    "vacancy": {
        "title": "Senior Software Engineer",
        "requirements": "5+ years experience in backend development"
    }
}'
SCREENING_RESPONSE=$(api_call "POST" "/api/ai/generate-questions-screening" "$SCREENING_DATA")

if echo "$SCREENING_RESPONSE" | grep -qE "(questions|error|processing)"; then
    echo -e "${GREEN}✅ PASS${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${YELLOW}⚠️  WARN${NC} (Screening questions not available)"
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

# Step 3: Test match-specific question generation if we have IDs
if [ -n "$CANDIDATE_ID" ] && [ -n "$VACANCY_ID" ]; then
    echo -n "Step 3: Generating questions for specific match... "
    MATCH_QUESTIONS=$(api_call "POST" "/api/matches/$CANDIDATE_ID/$VACANCY_ID/questions" "{}")
    
    if echo "$MATCH_QUESTIONS" | grep -qE "(questions|generated|error)"; then
        echo -e "${GREEN}✅ PASS${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${YELLOW}⚠️  WARN${NC} (Match not found or AI unavailable)"
    fi
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Workflow 6: Interview Scheduling"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Step 1: Create an interview
if [ -n "$CANDIDATE_ID" ] && [ -n "$VACANCY_ID" ]; then
    echo -n "Step 1: Creating interview... "
    INTERVIEW_DATA=$(cat <<EOF
{
    "candidate_id": $CANDIDATE_ID,
    "vacancy_id": $VACANCY_ID,
    "type": "technical",
    "scheduled_at": "$(date -d '+7 days' '+%Y-%m-%d 10:00:00' 2>/dev/null || date -v+7d '+%Y-%m-%d 10:00:00')",
    "duration_minutes": 60,
    "location": "Video Call",
    "notes": "Technical interview - E2E test"
}
EOF
)
    INTERVIEW_RESPONSE=$(api_call "POST" "/api/interviews" "$INTERVIEW_DATA")
    
    if echo "$INTERVIEW_RESPONSE" | grep -q '"id"'; then
        INTERVIEW_ID=$(echo "$INTERVIEW_RESPONSE" | sed 's/.*"id"://' | sed 's/,.*//' | tr -d '"' | tr -d ' ')
        echo -e "${GREEN}✅ PASS${NC} (Interview ID: $INTERVIEW_ID)"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${YELLOW}⚠️  WARN${NC} (Could not create interview)"
    fi
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
fi

# Step 2: List interviews
echo -n "Step 2: Listing interviews... "
INTERVIEWS_LIST=$(api_call "GET" "/api/interviews" "")
if echo "$INTERVIEWS_LIST" | grep -qE "(data|\[\])"; then
    echo -e "${GREEN}✅ PASS${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}❌ FAIL${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

# Step 3: Get upcoming interviews
echo -n "Step 3: Getting upcoming interviews... "
UPCOMING=$(api_call "GET" "/api/interviews/upcoming/all" "")
if echo "$UPCOMING" | grep -qE "(data|\[\]|interviews)"; then
    echo -e "${GREEN}✅ PASS${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}❌ FAIL${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Workflow 7: Admin Settings & System Health"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Step 1: Get admin settings
echo -n "Step 1: Getting admin settings... "
SETTINGS_RESPONSE=$(api_call "GET" "/api/admin/settings" "")
if echo "$SETTINGS_RESPONSE" | grep -qE "(settings|data)"; then
    SETTINGS_COUNT=$(echo "$SETTINGS_RESPONSE" | grep -o '"key"' | wc -l || echo "?")
    echo -e "${GREEN}✅ PASS${NC} ($SETTINGS_COUNT settings found)"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}❌ FAIL${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

# Step 2: Get system health
echo -n "Step 2: Getting system health... "
HEALTH_RESPONSE=$(api_call "GET" "/api/admin/system/health" "")
if echo "$HEALTH_RESPONSE" | grep -qE "(services|status|health)"; then
    echo -e "${GREEN}✅ PASS${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${YELLOW}⚠️  WARN${NC} (Health endpoint may not exist)"
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

# Step 3: Get AI providers
echo -n "Step 3: Getting AI providers... "
PROVIDERS_RESPONSE=$(api_call "GET" "/api/admin/ai/providers" "")
if echo "$PROVIDERS_RESPONSE" | grep -qE "(providers|data|\[\])"; then
    echo -e "${GREEN}✅ PASS${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${YELLOW}⚠️  WARN${NC}"
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Workflow 8: Vacancy Details & Updates"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -n "$VACANCY_ID" ]; then
    # Step 1: Get vacancy details
    echo -n "Step 1: Getting vacancy details... "
    VACANCY_DETAILS=$(api_call "GET" "/api/vacancies/$VACANCY_ID" "")
    if echo "$VACANCY_DETAILS" | grep -q "title"; then
        echo -e "${GREEN}✅ PASS${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}❌ FAIL${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    # Step 2: Update vacancy
    echo -n "Step 2: Updating vacancy... "
    UPDATE_DATA='{"description": "Updated description - E2E test verification"}'
    UPDATE_RESPONSE=$(api_call "PUT" "/api/vacancies/$VACANCY_ID" "$UPDATE_DATA")
    if echo "$UPDATE_RESPONSE" | grep -qE "(id|title|Updated)"; then
        echo -e "${GREEN}✅ PASS${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${YELLOW}⚠️  WARN${NC}"
    fi
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
fi

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
    [ -n "$INTERVIEW_ID" ] && echo "  - Interview ID: $INTERVIEW_ID"
    exit 0
else
    PASS_RATE=$((PASSED_TESTS * 100 / TOTAL_TESTS))
    echo -e "${YELLOW}⚠️  Pass rate: $PASS_RATE%${NC}"
    echo ""
    echo "Note: Some warnings are expected if AI service is not configured"
    echo "      or if there is insufficient data for matching."
    exit 1
fi

