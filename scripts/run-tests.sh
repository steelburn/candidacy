#!/bin/bash

# Run tests for all services

set -e

echo "🧪 Running tests for all services..."
echo ""

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

FAILED_TESTS=()
PASSED_TESTS=()

for service in "${SERVICES[@]}"; do
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "🧪 Testing $service..."
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    if docker compose exec "$service" php artisan test 2>/dev/null; then
        echo "✅ $service tests passed"
        PASSED_TESTS+=("$service")
    else
        echo "❌ $service tests failed or service not running"
        FAILED_TESTS+=("$service")
    fi
    echo ""
done

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 Test Summary"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Passed: ${#PASSED_TESTS[@]}"
echo "❌ Failed: ${#FAILED_TESTS[@]}"
echo ""

if [ ${#FAILED_TESTS[@]} -eq 0 ]; then
    echo "🎉 All tests passed successfully!"
    exit 0
else
    echo "⚠️  Failed services:"
    for service in "${FAILED_TESTS[@]}"; do
        echo "  - $service"
    done
    echo ""
    echo "💡 Tip: Make sure all services are running with 'docker compose ps'"
    exit 1
fi
