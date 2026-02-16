#!/bin/bash
set -e

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

echo "🔧 Fixing dependencies for all services..."

for service in "${SERVICES[@]}"; do
    echo "📦 Installing dependencies for $service..."
    docker compose run --rm --no-deps "$service" composer install --no-interaction --prefer-dist
    echo "✅ $service dependencies installed"
    echo ""
done

echo "✅ All dependencies installed!"
echo "🔄 Please run 'make up' (or 'docker compose up -d') to restart services."
