#!/bin/bash

# Master Test Orchestration Script
# Runs all test suites and provides comprehensive reporting

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Load environment variables if .env exists
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Test results tracking
INTEGRATION_PASSED=false
BACKEND_PASSED=false
API_PASSED=false
E2E_PASSED=false

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                                                                ║"
echo "║        Candidacy Platform - Comprehensive Test Suite          ║"
echo "║                                                                ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${CYAN}Running all test suites...${NC}"
echo ""

# Function to print section header
print_header() {
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo -e "${BOLD}$1${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
}

# 1. Integration Tests (run first to ensure infrastructure is ready)
print_header "Phase 1: Integration Tests"
echo "Testing database connectivity, Redis, and service communication..."
echo ""

if ./scripts/test-integration.sh; then
    INTEGRATION_PASSED=true
    echo ""
    echo -e "${GREEN}✅ Integration tests passed${NC}"
else
    echo ""
    echo -e "${RED}❌ Integration tests failed${NC}"
    echo -e "${YELLOW}⚠️  Skipping remaining tests due to integration failures${NC}"
    echo ""
    echo "Please fix integration issues before running other tests:"
    echo "  - Check if all services are running: docker compose ps"
    echo "  - Check service logs: make logs"
    echo "  - Verify DBML sync: make dbml-check"
    echo "  - Initialize databases: make dbml-init"
    exit 1
fi

# 2. Backend Service Tests
print_header "Phase 2: Backend Service Tests"
echo "Running PHPUnit tests for all microservices..."
echo ""

if ./scripts/test-backend-services.sh; then
    BACKEND_PASSED=true
    echo ""
    echo -e "${GREEN}✅ Backend service tests passed${NC}"
else
    echo ""
    echo -e "${RED}❌ Backend service tests failed${NC}"
fi

# 3. API Endpoint Tests
print_header "Phase 3: API Endpoint Tests"
echo "Testing API endpoints via HTTP requests..."
echo ""

if ./scripts/test-api-endpoints.sh; then
    API_PASSED=true
    echo ""
    echo -e "${GREEN}✅ API endpoint tests passed${NC}"
else
    echo ""
    echo -e "${RED}❌ API endpoint tests failed${NC}"
fi

# 4. End-to-End Tests
print_header "Phase 4: End-to-End Workflow Tests"
echo "Testing complete workflows..."
echo ""

if ./scripts/test-e2e.sh; then
    E2E_PASSED=true
    echo ""
    echo -e "${GREEN}✅ End-to-end tests passed${NC}"
else
    echo ""
    echo -e "${RED}❌ End-to-end tests failed${NC}"
fi

# Final Summary
echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                     Final Test Summary                        ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Print results
if [ "$INTEGRATION_PASSED" = true ]; then
    echo -e "  ${GREEN}✅ Integration Tests${NC}"
else
    echo -e "  ${RED}❌ Integration Tests${NC}"
fi

if [ "$BACKEND_PASSED" = true ]; then
    echo -e "  ${GREEN}✅ Backend Service Tests${NC}"
else
    echo -e "  ${RED}❌ Backend Service Tests${NC}"
fi

if [ "$API_PASSED" = true ]; then
    echo -e "  ${GREEN}✅ API Endpoint Tests${NC}"
else
    echo -e "  ${RED}❌ API Endpoint Tests${NC}"
fi

if [ "$E2E_PASSED" = true ]; then
    echo -e "  ${GREEN}✅ End-to-End Tests${NC}"
else
    echo -e "  ${RED}❌ End-to-End Tests${NC}"
fi

echo ""

# Determine overall result
if [ "$INTEGRATION_PASSED" = true ] && [ "$BACKEND_PASSED" = true ] && \
   [ "$API_PASSED" = true ] && [ "$E2E_PASSED" = true ]; then
    echo -e "${GREEN}${BOLD}╔════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}${BOLD}║                                                                ║${NC}"
    echo -e "${GREEN}${BOLD}║          🎉 ALL TESTS PASSED SUCCESSFULLY! 🎉                  ║${NC}"
    echo -e "${GREEN}${BOLD}║                                                                ║${NC}"
    echo -e "${GREEN}${BOLD}╚════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    exit 0
else
    echo -e "${YELLOW}╔════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${YELLOW}║                                                                ║${NC}"
    echo -e "${YELLOW}║              ⚠️  SOME TESTS FAILED ⚠️                          ║${NC}"
    echo -e "${YELLOW}║                                                                ║${NC}"
    echo -e "${YELLOW}╚════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Review the failed test output above"
    echo "  2. Fix the identified issues"
    echo "  3. Run 'make test' again to verify fixes"
    echo ""
    echo "You can also run individual test suites:"
    echo "  - make test-integration"
    echo "  - make test-backend"
    echo "  - make test-api"
    echo "  - make test-e2e"
    echo ""
    exit 1
fi
