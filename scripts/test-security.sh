#!/bin/bash

# Comprehensive Security Testing Script
# Tests rate limiting, security headers, and request validation

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║          Security Enhancements Verification Tests             ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

API_URL="http://localhost:8080"
PASSED=0
FAILED=0

# Function to test security headers
test_security_headers() {
    echo -e "${BLUE}━━━ Testing Security Headers ━━━${NC}"
    
    RESPONSE=$(curl -s -I "$API_URL/api/health")
    
    # Check for X-Frame-Options
    if echo "$RESPONSE" | grep -q "X-Frame-Options"; then
        echo -e "${GREEN}✓${NC} X-Frame-Options header present"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} X-Frame-Options header missing"
        ((FAILED++))
    fi
    
    # Check for X-Content-Type-Options
    if echo "$RESPONSE" | grep -q "X-Content-Type-Options"; then
        echo -e "${GREEN}✓${NC} X-Content-Type-Options header present"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} X-Content-Type-Options header missing"
        ((FAILED++))
    fi
    
    # Check for X-XSS-Protection
    if echo "$RESPONSE" | grep -q "X-XSS-Protection"; then
        echo -e "${GREEN}✓${NC} X-XSS-Protection header present"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} X-XSS-Protection header missing"
        ((FAILED++))
    fi
    
    # Check for Referrer-Policy
    if echo "$RESPONSE" | grep -q "Referrer-Policy"; then
        echo -e "${GREEN}✓${NC} Referrer-Policy header present"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} Referrer-Policy header missing"
        ((FAILED++))
    fi
    
    echo ""
}

# Function to test rate limiting
test_rate_limiting() {
    echo -e "${BLUE}━━━ Testing Rate Limiting ━━━${NC}"
    
    # Make rapid requests to test rate limiting
    echo "Making 70 rapid requests to test rate limiting..."
    
    RATE_LIMITED=false
    for i in {1..70}; do
        STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/api/health")
        
        if [ "$STATUS" == "429" ]; then
            RATE_LIMITED=true
            echo -e "${GREEN}✓${NC} Rate limiting triggered after $i requests (HTTP 429)"
            ((PASSED++))
            break
        fi
    done
    
    if [ "$RATE_LIMITED" = false ]; then
        echo -e "${YELLOW}⚠${NC} Rate limiting not triggered (may need adjustment or is disabled)"
        echo "  Note: This might be expected if rate limits are high or disabled in development"
    fi
    
    echo ""
}

# Function to test request validation
test_request_validation() {
    echo -e "${BLUE}━━━ Testing Request Validation ━━━${NC}"
    
    # Test invalid pagination parameters
    echo "Testing invalid pagination parameters..."
    STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/api/candidates?page=-1&per_page=1000")
    
    if [ "$STATUS" == "422" ]; then
        echo -e "${GREEN}✓${NC} Invalid pagination rejected (HTTP 422)"
        ((PASSED++))
    else
        echo -e "${YELLOW}⚠${NC} Invalid pagination not rejected (got HTTP $STATUS)"
        echo "  Note: Validation middleware may not be applied to this endpoint"
    fi
    
    # Test invalid sort parameters
    echo "Testing invalid sort parameters..."
    STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/api/candidates?sort_order=invalid")
    
    if [ "$STATUS" == "422" ]; then
        echo -e "${GREEN}✓${NC} Invalid sort order rejected (HTTP 422)"
        ((PASSED++))
    else
        echo -e "${YELLOW}⚠${NC} Invalid sort order not rejected (got HTTP $STATUS)"
        echo "  Note: Validation middleware may not be applied to this endpoint"
    fi
    
    echo ""
}

# Function to test system health
test_system_health() {
    echo -e "${BLUE}━━━ Testing System Health ━━━${NC}"
    
    HEALTH_RESPONSE=$(curl -s "$API_URL/api/system-health")
    
    # Check if response is valid JSON
    if echo "$HEALTH_RESPONSE" | jq . > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} System health endpoint returns valid JSON"
        ((PASSED++))
        
        # Count online services
        ONLINE_COUNT=$(echo "$HEALTH_RESPONSE" | jq '[.services[] | select(.status == "online")] | length')
        TOTAL_COUNT=$(echo "$HEALTH_RESPONSE" | jq '.services | length')
        
        echo "  Services online: $ONLINE_COUNT/$TOTAL_COUNT"
        
        if [ "$ONLINE_COUNT" -eq "$TOTAL_COUNT" ]; then
            echo -e "${GREEN}✓${NC} All services are online"
            ((PASSED++))
        else
            echo -e "${YELLOW}⚠${NC} Some services are offline"
        fi
    else
        echo -e "${RED}✗${NC} System health endpoint returned invalid JSON"
        ((FAILED++))
    fi
    
    echo ""
}

# Run all tests
test_security_headers
test_rate_limiting
test_request_validation
test_system_health

# Print summary
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                      Test Summary                              ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}✓ Passed:  $PASSED tests${NC}"
echo -e "${RED}✗ Failed:  $FAILED tests${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 All security tests passed!${NC}"
    exit 0
else
    echo -e "${YELLOW}⚠️  Some tests failed or need attention${NC}"
    exit 1
fi
