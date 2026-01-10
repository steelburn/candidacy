#!/bin/bash

# Candidacy Microservices Setup Script (with platform requirement bypass)
# This script will scaffold all microservices using Laravel

set -e

echo "🚀 Setting up Candidacy Microservices..."

# Array of services to create
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

# Create services directory if it doesn't exist
mkdir -p services

cd services

# Create each Laravel service
for service in "${SERVICES[@]}"; do
    if [ ! -d "$service" ]; then
        echo "📦 Creating $service..."
        composer create-project --prefer-dist laravel/laravel "$service" "10.*" --no-interaction --ignore-platform-req=ext-curl --ignore-platform-req=ext-zip
        
        # Copy .env.example to .env
        cp "$service/.env.example" "$service/.env"
        
        # Generate app key
        cd "$service"
        php artisan key:generate --force || echo "⚠️  Could not generate key, will be done in Docker"
        cd ..
        
        echo "✅ $service created successfully"
    else
        echo "⏭️  $service already exists, skipping..."
    fi
done

echo ""
echo "🎯 Creating API Gateway..."
cd ../gateway
mkdir -p api-gateway
if [ ! -d "api-gateway/.git" ]; then
    cd api-gateway
    if [ ! -f "composer.json" ]; then
        cd ..
        rm -rf api-gateway
        composer create-project --prefer-dist laravel/laravel "api-gateway" "10.*" --no-interaction --ignore-platform-req=ext-curl --ignore-platform-req=ext-zip
        cd api-gateway
        cp .env.example .env
        php artisan key:generate --force || echo "⚠️  Could not generate key, will be done in Docker"
    fi
    cd ..
fi

echo ""
echo "🎨 Setting up Main Frontend (HR/Recruiter)..."
cd ../frontend
if [ ! -d "web-app" ]; then
    npm create vite@latest web-app -- --template vue
    cd web-app
    npm install
    npm install axios pinia vue-router marked
    cd ..
    echo "✅ Main frontend created"
else
    echo "⏭️  Main frontend already exists, skipping..."
fi

echo ""
echo "🌐 Setting up Applicant Portal..."
if [ ! -d "applicant-web-app" ]; then
    npm create vite@latest applicant-web-app -- --template vue
    cd applicant-web-app
    npm install
    npm install axios pinia vue-router
    cd ..
    echo "✅ Applicant portal created"
else
    echo "⏭️  Applicant portal already exists, skipping..."
fi

cd ..

# Copy Dockerfiles to each service
echo ""
echo "📦 Copying Dockerfiles..."
for service in services/*/; do
    if [ -d "$service" ] && [ ! -f "$service/Dockerfile" ]; then
        cp infrastructure/docker/Dockerfile.laravel "$service/Dockerfile"
        echo "✅ Copied Dockerfile to $service"
    fi
done

if [ -d "gateway/api-gateway" ] && [ ! -f "gateway/api-gateway/Dockerfile" ]; then
    cp infrastructure/docker/Dockerfile.laravel gateway/api-gateway/Dockerfile
    echo "✅ Copied Dockerfile to gateway/api-gateway"
fi

if [ -d "frontend/web-app" ] && [ ! -f "frontend/web-app/Dockerfile.dev" ]; then
    cp infrastructure/docker/Dockerfile.frontend frontend/web-app/Dockerfile.dev
    echo "✅ Copied Dockerfile to frontend/web-app"
fi

if [ -d "frontend/applicant-web-app" ] && [ ! -f "frontend/applicant-web-app/Dockerfile" ]; then
    cp infrastructure/docker/Dockerfile.frontend frontend/applicant-web-app/Dockerfile
    echo "✅ Copied Dockerfile to frontend/applicant-web-app"
fi

echo ""
echo "✅ All services created successfully!"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 Next steps:"
echo "  1. Copy .env.example to .env: cp .env.example .env"
echo "  2. Configure your .env file with database credentials"
echo "  3. Start services: docker compose up -d"
echo "  4. Initialize from DBML: make dbml-init"
echo "  5. Seed databases: make seed"
echo ""
echo "🌐 Access the application:"
echo "  - Main Frontend (HR/Recruiter): http://localhost:3501"
echo "  - Applicant Portal:             http://localhost:5173"
echo "  - API Gateway:                  http://localhost:9080"
echo "  - Grafana (Monitoring):         http://localhost:3050"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
