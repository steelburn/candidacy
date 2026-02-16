#!/bin/bash
set -e

echo "🗄️  Initializing databases from DBML..."
echo ""

# Generate SQL from DBML
echo "🔨 Generating SQL from DBML..."
npm run dbml:sql
echo ""

# Database credentials from environment or defaults
DB_HOST="${DB_HOST:-mysql}"
DB_USER="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-root}"

# MySQL client command with SSL disabled (--skip-ssl works with both MySQL and MariaDB clients)
MYSQL_CMD="mysql --skip-ssl -h ${DB_HOST} -u ${DB_USER} -p${DB_PASSWORD}"

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
max_attempts=60
attempt=0

until $MYSQL_CMD -e "SELECT 1" > /dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ $attempt -eq $max_attempts ]; then
        echo "❌ MySQL failed to start after $max_attempts attempts"
        exit 1
    fi
    echo "   Attempt $attempt/$max_attempts..."
    sleep 2
done
echo "✅ MySQL is ready"
echo ""

# Service to database mapping
declare -A services=(
    ["auth-service"]="candidacy_auth"
    ["candidate-service"]="candidacy_candidate"
    ["vacancy-service"]="candidacy_vacancy"
    ["matching-service"]="candidacy_matching"
    ["interview-service"]="candidacy_interview"
    ["offer-service"]="candidacy_offer"
    ["onboarding-service"]="candidacy_onboarding"
    ["admin-service"]="candidacy_admin"
    ["document-parser-service"]="candidacy_document_parser"
    ["notification-service"]="candidacy_notification"
    ["ai-service"]="candidacy_ai"
)

echo "📊 Creating databases and applying schema..."
echo ""

for service in "${!services[@]}"; do
    database="${services[$service]}"
    sql_file="database/sql/${database}.sql"
    
    echo "  📁 $database"
    
    # Drop existing database for clean initialization
    echo "     Dropping existing database (if exists)..."
    $MYSQL_CMD -e "DROP DATABASE IF EXISTS \`${database}\`;" 2>/dev/null
    
    # Create database
    echo "     Creating database..."
    $MYSQL_CMD -e "CREATE DATABASE \`${database}\`;" 2>/dev/null
    
    # Grant permissions
    $MYSQL_CMD -e "GRANT ALL PRIVILEGES ON \`${database}\`.* TO '${DB_USER}'@'%';" 2>/dev/null
    
    # Apply schema if SQL file exists
    if [ -f "$sql_file" ]; then
        echo "     Applying schema from $sql_file..."
        $MYSQL_CMD ${database} < "$sql_file" 2>/dev/null
        echo "     ✅ Schema applied"
    else
        echo "     ⚠️  No SQL file found at $sql_file"
    fi
    
    echo ""
done

# Flush privileges
$MYSQL_CMD -e "FLUSH PRIVILEGES;" 2>/dev/null

echo "✅ Database initialization complete!"
echo ""
echo "📊 Databases created:"
for database in "${services[@]}"; do
    echo "   - $database"
done
