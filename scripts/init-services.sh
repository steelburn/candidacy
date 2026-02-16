#!/bin/bash

# Candidacy Services Initialization Script
# Handles dependencies, secrets, and migrations

set -e

echo "🚀 Initializing Candidacy Microservices..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# 1. Install Dependencies
echo "📦 Checking and installing dependencies..."
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

for service in "${SERVICES[@]}"; do
    if [ ! -d "services/$service/vendor" ] && [ ! -d "gateway/$service/vendor" ]; then
         # Check if it's the gateway (path difference)
         if [ "$service" == "api-gateway" ]; then
             if [ ! -d "gateway/api-gateway/vendor" ]; then
                echo "   🔨 Installing dependencies for $service..."
                docker compose run --rm --no-deps "$service" composer install --no-interaction --prefer-dist > /dev/null 2>&1
                echo "   ✅ $service dependencies installed"
             fi
         else
             if [ ! -d "services/$service/vendor" ]; then
                echo "   🔨 Installing dependencies for $service..."
                docker compose run --rm --no-deps "$service" composer install --no-interaction --prefer-dist > /dev/null 2>&1
                echo "   ✅ $service dependencies installed"
             fi
         fi
    fi
done
echo "✅ Dependencies check complete"
echo ""

# 2. Start Services (if not running)
echo "🔄 Ensuring services are running..."
docker compose up -d
echo "✅ Services are up"
echo ""

# 3. Wait for Database
echo "⏳ Waiting for Database to be ready..."
max_retries=60
counter=0
while ! docker compose exec mysql mysqladmin ping -h"localhost" --silent; do
    counter=$((counter+1))
    if [ $counter -eq $max_retries ]; then
        echo "❌ Error: Database failed to start after $max_retries seconds"
        exit 1
    fi
    echo "   ... waiting for database ($counter/$max_retries)"
    sleep 2
done
echo "✅ Database is ready"
echo ""

# 4. Generate Secrets for Auth Service
echo "🔐 Checking Auth Service secrets..."
if docker compose exec auth-service php artisan tinker --execute="echo env('JWT_SECRET');" 2>/dev/null | grep -q "^$"; then
    echo "   🔑 Generatng JWT Secret..."
    docker compose exec auth-service php artisan jwt:secret --force
else
    # Double check if "Secret is not set" might still be an issue by checking .env content indirectly or just ensuring key exists
    # For now, we rely on the fact that we just set it up.
    # If the previous check returns empty, it means it's not set.
    # Note: tinker output might contain other text, so we'll do a simpler check.
    # We will just run it if we suspect it's missing, or rely on a file check if mounted.
    
    # Check if .env has JWT_SECRET
    if ! docker compose exec auth-service grep -q "JWT_SECRET=" .env; then
         echo "   🔑 JWT_SECRET missing in .env, generating..."
         docker compose exec auth-service php artisan jwt:secret --force
    fi
fi
echo "✅ Secrets check complete"
echo ""

# 5. Run Migrations
echo "📊 Running migrations..."
./scripts/run-migrations.sh

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Initialization Complete! 🚀"
echo "   You can now access the application at http://localhost:3501"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
