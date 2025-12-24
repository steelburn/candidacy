#!/bin/bash
set -e

echo "🗄️  Initializing databases from DBML..."
echo ""

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is required but not installed."
    echo "   Please install Node.js to use DBML-based database initialization."
    exit 1
fi

# Install dependencies if needed
if [ ! -d "node_modules" ]; then
    echo "📦 Installing dependencies..."
    npm install
    echo ""
fi

# Generate SQL from DBML
echo "🔨 Generating SQL from DBML..."
npm run dbml:sql
echo ""

# Database credentials from .env
if [ -f .env ]; then
    set -a
    source <(grep -v '^#' .env | grep -v '^$' | sed 's/#.*$//')
    set +a
fi

DB_USER="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-root}"
DB_ROOT_PASSWORD="${DB_PASSWORD:-root}"

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
max_attempts=60
attempt=0

until docker compose exec -T mysql mysqladmin -u root -p${DB_ROOT_PASSWORD} ping -h localhost --silent 2>/dev/null; do
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
)

echo "📊 Creating databases and applying schema..."
echo ""

for service in "${!services[@]}"; do
    database="${services[$service]}"
    sql_file="database/sql/${database}.sql"
    
    echo "  📁 $database"
    
    # Drop existing database for clean initialization
    echo "     Dropping existing database (if exists)..."
    docker compose exec -T mysql mysql -u root -p${DB_ROOT_PASSWORD} -e "DROP DATABASE IF EXISTS \`${database}\`;" 2>/dev/null
    
    # Create database
    echo "     Creating database..."
    docker compose exec -T mysql mysql -u root -p${DB_ROOT_PASSWORD} -e "CREATE DATABASE \`${database}\`;" 2>/dev/null
    
    # Grant permissions
    docker compose exec -T mysql mysql -u root -p${DB_ROOT_PASSWORD} -e "GRANT ALL PRIVILEGES ON \`${database}\`.* TO '${DB_USER}'@'%';" 2>/dev/null
    
    # Apply schema if SQL file exists
    if [ -f "$sql_file" ]; then
        echo "     Applying schema from $sql_file..."
        docker compose exec -T mysql mysql -u root -p${DB_ROOT_PASSWORD} ${database} < "$sql_file" 2>/dev/null
        echo "     ✅ Schema applied"
    else
        echo "     ⚠️  No SQL file found at $sql_file"
    fi
    
    echo ""
done

# Flush privileges
docker compose exec -T mysql mysql -u root -p${DB_ROOT_PASSWORD} -e "FLUSH PRIVILEGES;" 2>/dev/null

echo "✅ Database initialization complete!"
echo ""
echo "📊 Databases created:"
for database in "${services[@]}"; do
    echo "   - $database"
done
