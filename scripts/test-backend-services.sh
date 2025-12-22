#!/bin/bash

# Comprehensive Backend Service Testing Script
# Tests all microservices with PHPUnit and provides detailed reporting

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║        Backend Service Testing - Candidacy Platform           ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Array of all services
SERVICES=(
    "auth-service"
    "candidate-service"
    "vacancy-service"
    "ai-service"
    "matching-service"
    "interview-service"
    "offer-service"
    "onboarding-service"
    "reporting-service"
    "admin-service"
    "notification-service"
)

# Arrays to track results
declare -a PASSED_TESTS
declare -a FAILED_TESTS
declare -a SKIPPED_TESTS

TOTAL_TESTS=0
TOTAL_ASSERTIONS=0
TOTAL_FAILURES=0

# Function to check if service is running
check_service_running() {
    local service=$1
    if docker compose ps | grep -q "$service.*Up"; then
        return 0
    else
        return 1
    fi
}

# Function to run tests for a service
test_service() {
    local service=$1
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo -e "${BLUE}🧪 Testing: $service${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    # Check if service is running
    if ! check_service_running "$service"; then
        echo -e "${YELLOW}⚠️  Service $service is not running${NC}"
        SKIPPED_TESTS+=("$service")
        echo ""
        return
    fi
    
    # Run PHPUnit tests
    local output
    local exit_code
    
    output=$(docker compose exec -T "$service" php artisan test --colors=never 2>&1) || exit_code=$?
    
    if [ -z "$exit_code" ]; then
        exit_code=0
    fi
    
    # Parse test results
    if echo "$output" | grep -q "Tests:"; then
        local tests_line=$(echo "$output" | grep "Tests:" | tail -1)
        echo "$tests_line"
        
        # Extract numbers
        local passed=$(echo "$tests_line" | grep -oP '\d+(?= passed)' || echo "0")
        local failed=$(echo "$tests_line" | grep -oP '\d+(?= failed)' || echo "0")
        
        if [ "$exit_code" -eq 0 ] && [ "$failed" -eq 0 ]; then
            echo -e "${GREEN}✅ $service: All tests passed${NC}"
            PASSED_TESTS+=("$service")
        else
            echo -e "${RED}❌ $service: Tests failed${NC}"
            FAILED_TESTS+=("$service")
            echo ""
            echo "Error details:"
            echo "$output" | tail -20
        fi
    else
        # No tests found or error running tests
        if echo "$output" | grep -q "No tests executed"; then
            echo -e "${YELLOW}⚠️  $service: No tests found${NC}"
            SKIPPED_TESTS+=("$service")
        else
            echo -e "${RED}❌ $service: Error running tests${NC}"
            FAILED_TESTS+=("$service")
            echo ""
            echo "Error output:"
            echo "$output" | tail -20
        fi
    fi
    
    echo ""
}

# Main testing loop
echo "Starting tests for ${#SERVICES[@]} services..."
echo ""

for service in "${SERVICES[@]}"; do
    test_service "$service"
done

# Print summary
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                      Test Summary                              ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}✅ Passed:  ${#PASSED_TESTS[@]} services${NC}"
echo -e "${RED}❌ Failed:  ${#FAILED_TESTS[@]} services${NC}"
echo -e "${YELLOW}⚠️  Skipped: ${#SKIPPED_TESTS[@]} services${NC}"
echo ""

if [ ${#PASSED_TESTS[@]} -gt 0 ]; then
    echo "Passed services:"
    for service in "${PASSED_TESTS[@]}"; do
        echo -e "  ${GREEN}✓${NC} $service"
    done
    echo ""
fi

if [ ${#FAILED_TESTS[@]} -gt 0 ]; then
    echo "Failed services:"
    for service in "${FAILED_TESTS[@]}"; do
        echo -e "  ${RED}✗${NC} $service"
    done
    echo ""
fi

if [ ${#SKIPPED_TESTS[@]} -gt 0 ]; then
    echo "Skipped services:"
    for service in "${SKIPPED_TESTS[@]}"; do
        echo -e "  ${YELLOW}⊘${NC} $service"
    done
    echo ""
fi

# Exit with appropriate code
if [ ${#FAILED_TESTS[@]} -eq 0 ]; then
    echo -e "${GREEN}🎉 All backend service tests passed!${NC}"
    exit 0
else
    echo -e "${RED}⚠️  Some tests failed. Please review the errors above.${NC}"
    exit 1
fi
