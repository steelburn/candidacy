#!/bin/bash

# Database Cleanup and Reinitialization Script
# Drops and recreates all databases for fresh testing

set -e

echo "🗑️  Database Cleanup and Reinitialization"
echo "=========================================="
echo ""

# Database configuration
DB_HOST="mysql"
DB_USER="root"
DB_PASS="root"

# List of all databases
DATABASES=(
    "candidacy_auth"
    "candidacy_candidate"
    "candidacy_vacancy"
    "candidacy_matching"
    "candidacy_interview"
    "candidacy_offer"
    "candidacy_onboarding"
    "candidacy_reporting"
    "candidacy_admin"
    "candidacy_notification"
    "candidacy_ai"
)

echo "Step 1: Dropping existing databases..."
for DB in "${DATABASES[@]}"; do
    echo "  - Dropping $DB..."
    docker compose exec -T mysql mysql -u$DB_USER -p$DB_PASS -e "DROP DATABASE IF EXISTS $DB;" 2>/dev/null || true
done
echo "✓ All databases dropped"
echo ""

echo "Step 2: Creating fresh databases..."
for DB in "${DATABASES[@]}"; do
    echo "  - Creating $DB..."
    docker compose exec -T mysql mysql -u$DB_USER -p$DB_PASS -e "CREATE DATABASE $DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
done
echo "✓ All databases created"
echo ""

echo "Step 3: Running migrations for all services..."

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
    echo "  - Migrating $SERVICE..."
    docker compose exec -T ${SERVICE%-service} php artisan migrate --force 2>&1 | grep -E "(Migrating|Migrated|Nothing to migrate)" || echo "    ⚠ No migrations or service not running"
done
echo "✓ All migrations completed"
echo ""

echo "Step 4: Seeding admin settings..."
docker compose exec -T admin php artisan db:seed --class=SettingsSeeder --force 2>/dev/null || echo "  ⚠ Settings seeder not found (optional)"
echo ""

echo "✅ Database cleanup and reinitialization complete!"
echo ""
echo "Next steps:"
echo "  1. Test candidate creation: POST /api/candidates"
echo "  2. Test vacancy creation: POST /api/vacancies"
echo "  3. Verify JSON field handling works correctly"
echo ""
