#!/bin/bash

# Script to rebuild all Docker containers with fresh autoload configuration
# This ensures all services can properly load shared libraries

set -e

echo "🔄 Rebuilding All Containers for Refactored Codebase"
echo "===================================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Step 1: Stopping all containers...${NC}"
docker compose down
echo -e "${GREEN}✓ All containers stopped${NC}"
echo ""

echo -e "${YELLOW}Step 2: Rebuilding base image...${NC}"
docker build -t candidacy-base:latest -f infrastructure/docker/Dockerfile.base .
echo -e "${GREEN}✓ Base image rebuilt${NC}"
echo ""

echo -e "${YELLOW}Step 3: Rebuilding containers (this may take a few minutes)...${NC}"
docker compose build --no-cache
echo -e "${GREEN}✓ All containers rebuilt${NC}"
echo ""

echo -e "${YELLOW}Step 4: Starting all services...${NC}"
docker compose up -d
echo -e "${GREEN}✓ All services started${NC}"
echo ""

echo -e "${YELLOW}Step 5: Waiting for services to initialize (30 seconds)...${NC}"
sleep 30
echo -e "${GREEN}✓ Services initialized${NC}"
echo ""

echo -e "${YELLOW}Step 6: Regenerating composer autoload for all services...${NC}"
SERVICES=(
    "auth-service"
    "candidate-service"
    "vacancy-service"
    "matching-service"
    "interview-service"
    "offer-service"
    "onboarding-service"
    "reporting-service"
    "admin-service"
    "notification-service"
    "ai-service"
)

for SERVICE in "${SERVICES[@]}"; do
    echo "  Processing $SERVICE..."
    docker compose exec -T $SERVICE composer dump-autoload --no-interaction --quiet 2>&1 | grep -v "Writing to directory" || true
    echo -e "  ${GREEN}✓${NC} $SERVICE autoload regenerated"
done
echo ""

echo -e "${YELLOW}Step 7: Restarting all services to apply changes...${NC}"
docker compose restart
echo -e "${GREEN}✓ All services restarted${NC}"
echo ""

echo -e "${YELLOW}Step 8: Waiting for services to be ready (20 seconds)...${NC}"
sleep 20
echo ""

echo -e "${YELLOW}Step 9: Testing services...${NC}"
echo ""

# Test auth service
echo -n "  Testing auth-service (login): "
AUTH_RESPONSE=$(curl -s -X POST http://localhost:8081/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password"}' 2>&1)
if echo "$AUTH_RESPONSE" | grep -q "token"; then
    echo -e "${GREEN}✓ Working${NC}"
else
    echo -e "${YELLOW}⚠ Check logs${NC}"
fi

# Test candidate service
echo -n "  Testing candidate-service (list): "
CANDIDATE_RESPONSE=$(curl -s http://localhost:8082/api/candidates?per_page=5 2>&1)
if echo "$CANDIDATE_RESPONSE" | grep -q "data"; then
    echo -e "${GREEN}✓ Working${NC}"
else
    echo -e "${YELLOW}⚠ Check logs${NC}"
fi

# Test vacancy service
echo -n "  Testing vacancy-service (list): "
VACANCY_RESPONSE=$(curl -s http://localhost:8083/api/vacancies 2>&1)
if echo "$VACANCY_RESPONSE" | grep -q "data"; then
    echo -e "${GREEN}✓ Working${NC}"
else
    echo -e "${YELLOW}⚠ Check logs${NC}"
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✅ Rebuild Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Service Status:"
docker compose ps --format "table {{.Service}}\t{{.Status}}" | head -15
echo ""
echo "Test Credentials:"
echo "  Email: admin@test.com"
echo "  Password: password"
echo ""
echo "Frontend URL: http://localhost:3501"
echo ""
echo "If any services show warnings above, check logs with:"
echo "  docker compose logs <service-name>"
echo ""
