#!/bin/bash

# Integration Testing Script
# Tests database connectivity, Redis, and service-to-service communication

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║         Integration Testing - Candidacy Platform              ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Function to run test
run_test() {
    local description=$1
    local command=$2
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    echo -n "Testing: $description... "
    
    if eval "$command" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ PASS${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        return 0
    else
        echo -e "${RED}❌ FAIL${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Container Health Tests"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if containers are running
run_test "MySQL container is running" "docker compose ps mysql | grep -q 'Up'"
run_test "Redis container is running" "docker compose ps redis | grep -q 'Up'"
run_test "Auth service is running" "docker compose ps auth-service | grep -q 'Up'"
run_test "Candidate service is running" "docker compose ps candidate-service | grep -q 'Up'"
run_test "API Gateway is running" "docker compose ps api-gateway | grep -q 'Up'"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Database Connectivity Tests"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test MySQL connectivity from services
run_test "Auth service can connect to MySQL" \
    "docker compose exec -T auth-service php artisan tinker --execute=\"DB::connection()->getPdo(); echo 'connected'\" | grep -q 'connected'"

run_test "Candidate service can connect to MySQL" \
    "docker compose exec -T candidate-service php artisan tinker --execute=\"DB::connection()->getPdo(); echo 'connected'\" | grep -q 'connected'"

run_test "Vacancy service can connect to MySQL" \
    "docker compose exec -T vacancy-service php artisan tinker --execute=\"DB::connection()->getPdo(); echo 'connected'\" | grep -q 'connected'"

run_test "Admin service can connect to MySQL" \
    "docker compose exec -T admin-service php artisan tinker --execute=\"DB::connection()->getPdo(); echo 'connected'\" | grep -q 'connected'"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Database Schema Tests (Table Existence)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if core tables exist
run_test "Auth service: users table exists" \
    "docker compose exec -T auth-service php artisan tinker --execute=\"echo (Schema::hasTable('users') ? 'exists' : 'missing')\" | grep -q 'exists'"

run_test "Candidate service: candidates table exists" \
    "docker compose exec -T candidate-service php artisan tinker --execute=\"echo (Schema::hasTable('candidates') ? 'exists' : 'missing')\" | grep -q 'exists'"

run_test "Vacancy service: vacancies table exists" \
    "docker compose exec -T vacancy-service php artisan tinker --execute=\"echo (Schema::hasTable('vacancies') ? 'exists' : 'missing')\" | grep -q 'exists'"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Redis Connectivity Tests"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test Redis connectivity
run_test "Redis is accepting connections" \
    "docker compose exec -T redis redis-cli ping | grep -q 'PONG'"

run_test "Auth service can connect to Redis" \
    "docker compose exec -T auth-service php -r 'Redis::connect(\"redis\", 6379);' 2>&1 | grep -qv 'Error'"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Service-to-Service Communication Tests"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test internal service communication
run_test "Candidate service can reach AI service" \
    "docker compose exec -T candidate-service curl -s http://ai-service:8080/api/health | grep -q 'ok'"

run_test "Matching service can reach Candidate service" \
    "docker compose exec -T matching-service curl -s http://candidate-service:8080/api/health | grep -q 'ok'"

run_test "Matching service can reach Vacancy service" \
    "docker compose exec -T matching-service curl -s http://vacancy-service:8080/api/health | grep -q 'ok'"

run_test "Vacancy service can reach AI service" \
    "docker compose exec -T vacancy-service curl -s http://ai-service:8080/api/health | grep -q 'ok'"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "API Gateway Routing Tests"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test API Gateway can route to services
run_test "Gateway routes to Auth service" \
    "curl -s http://localhost:8080/api/auth/health | grep -q 'ok'"

run_test "Gateway routes to Candidate service" \
    "curl -s http://localhost:8080/api/candidates/health | grep -q 'ok'"

run_test "Gateway routes to Vacancy service" \
    "curl -s http://localhost:8080/api/vacancies/health | grep -q 'ok'"

run_test "Gateway routes to Admin service" \
    "curl -s http://localhost:8080/api/admin/health | grep -q 'ok'"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Shared Library Tests"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test shared library is accessible
run_test "Auth service has access to shared libraries" \
    "docker compose exec -T auth-service test -d /var/www/shared"

run_test "Candidate service has access to shared libraries" \
    "docker compose exec -T candidate-service test -d /var/www/shared"

run_test "Vacancy service has access to shared libraries" \
    "docker compose exec -T vacancy-service test -d /var/www/shared"

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
    echo -e "${GREEN}🎉 All integration tests passed!${NC}"
    exit 0
else
    PASS_RATE=$((PASSED_TESTS * 100 / TOTAL_TESTS))
    echo -e "${YELLOW}⚠️  Pass rate: $PASS_RATE%${NC}"
    echo ""
    echo "💡 Tips for fixing integration issues:"
    echo "  - Ensure all services are running: docker compose ps"
    echo "  - Check service logs: make logs-<service>"
    echo "  - Verify DBML sync: make dbml-check"
    echo "  - Initialize databases: make dbml-init"
    echo "  - Restart services: make restart"
    exit 1
fi
