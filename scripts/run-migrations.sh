#!/bin/bash

# Run migrations for all services

set -e

echo "🔄 Running migrations for all services..."
echo ""

SERVICES=(
    "api-gateway"
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

FAILED_SERVICES=()

for service in "${SERVICES[@]}"; do
    echo "📊 Migrating $service..."
    if docker compose exec "$service" php artisan migrate --force 2>/dev/null; then
        echo "✅ $service migrated successfully"
    else
        echo "⚠️  $service migration failed or service not running"
        FAILED_SERVICES+=("$service")
    fi
    echo ""
done

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ ${#FAILED_SERVICES[@]} -eq 0 ]; then
    echo "✅ All migrations completed successfully!"
else
    echo "⚠️  Some migrations failed:"
    for service in "${FAILED_SERVICES[@]}"; do
        echo "  - $service"
    done
    echo ""
    echo "💡 Tip: Make sure all services are running with 'docker compose ps'"
    exit 1
fi
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

