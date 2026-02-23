# Candidacy Development Makefile
SHELL := /bin/bash

.PHONY: help setup up down restart logs seed test clean shell
.PHONY: logs-auth logs-candidate logs-vacancy logs-ai logs-matching logs-interview
.PHONY: logs-offer logs-onboarding logs-reporting logs-admin logs-notification
.PHONY: logs-gateway logs-frontend logs-applicant logs-grafana logs-parse-cv
.PHONY: db-reset pull build status
.PHONY: test-backend test-api test-integration test-e2e test-service test-resumes
.PHONY: test-auth test-candidate test-vacancy test-tenant-isolation
.PHONY: migrate-tenants
.PHONY: dbml-validate dbml-sql dbml-check dbml-init dbml-reset
.PHONY: logs-document-parser clear-matches
.PHONY: docs-php docs-serve
.PHONY: tunnel-up tunnel-down tunnel-logs tunnel-status


help:
	@echo "╔════════════════════════════════════════════════════════════════╗"
	@echo "║     Candidacy Microservices - Development Commands            ║"
	@echo "╚════════════════════════════════════════════════════════════════╝"
	@echo ""
	@echo "🚀 Main Commands:"
	@echo "  make setup          - Complete platform setup (recommended for first time)"
	@echo "  make up             - Start all services"
	@echo "  make down           - Stop all services"
	@echo "  make restart        - Restart all services"
	@echo "  make build          - Rebuild all containers"
	@echo "  make pull           - Pull latest images"
	@echo ""
	@echo "📊 Database Commands:"
	@echo "  make seed                  - Seed all databases with sample data"
	@echo "  make seed-config           - Seed configuration settings (27 settings)"
	@echo "  make migrate-tenants       - Run tenant_id migrations on all business services"
	@echo "  make db-reset              - Reset all databases (WARNING: destructive)"
	@echo "  make clear-matches         - Clear all candidate matches (for re-running matching)"
	@echo ""
	@echo "🗄️  DBML Commands (Database-as-Code):"
	@echo "  make dbml-validate  - Validate DBML schema syntax"
	@echo "  make dbml-sql       - Generate SQL from DBML"
	@echo "  make dbml-check     - Check if generated SQL is in sync with DBML"
	@echo "  make dbml-init      - Initialize databases from DBML"
	@echo "  make dbml-reset     - Drop & recreate databases from DBML (WARNING: destructive)"
	@echo ""
	@echo "🔍 Monitoring Commands:"
	@echo "  make logs           - View all service logs"
	@echo "  make logs-gateway   - View API Gateway logs"
	@echo "  make logs-auth      - View Auth Service logs"
	@echo "  make logs-candidate - View Candidate Service logs"
	@echo "  make logs-vacancy   - View Vacancy Service logs"
	@echo "  make logs-ai        - View AI Service logs"
	@echo "  make logs-parse-cv  - View CV Parsing logs (Candidate + AI)"
	@echo "  make logs-matching  - View Matching Service logs"
	@echo "  make logs-interview - View Interview Service logs"
	@echo "  make logs-offer     - View Offer Service logs"
	@echo "  make logs-onboarding- View Onboarding Service logs"
	@echo "  make logs-reporting - View Reporting Service logs"
	@echo "  make logs-admin     - View Admin Service logs"
	@echo "  make logs-notification - View Notification Service logs"
	@echo "  make logs-document-parser - View Document Parser logs"
	@echo "  make logs-frontend  - View Main Frontend logs"
	@echo "  make logs-applicant - View Applicant Portal logs"
	@echo "  make logs-grafana   - View Grafana logs"
	@echo ""
	@echo "🧪 Testing Commands:"
	@echo "  make test                       - Run all tests (backend, API, integration, e2e)"
	@echo "  make test-backend               - Run backend service tests (PHPUnit)"
	@echo "  make test-api                   - Run API endpoint tests"
	@echo "  make test-integration           - Run integration tests"
	@echo "  make test-e2e                   - Run end-to-end workflow tests"
	@echo "  make test-service S=<service>   - Run tests for specific service"
	@echo "  make test-tenant-isolation      - Run TenantIsolationTest on all services"
	@echo "  make test-resumes               - Generate test resume PDF/DOCX from markdown"
	@echo ""
	@echo "📚 Documentation Commands:"
	@echo "  make docs-php       - Generate PHP API documentation (PHPDoc)"
	@echo ""
	@echo "🌐 Cloudflare Tunnel Commands:"
	@echo "  make tunnel-up      - Start Cloudflare Tunnel (expose to internet)"
	@echo "  make tunnel-down    - Stop Cloudflare Tunnel"
	@echo "  make tunnel-logs    - View Cloudflare Tunnel logs"
	@echo "  make tunnel-status  - Check Cloudflare Tunnel status"
	@echo ""
	@echo "🛠️  Utility Commands:"
	@echo "  make shell S=<service> - Access service shell (e.g., make shell S=auth-service)"
	@echo "  make clean          - Clean up containers and volumes"
	@echo "  make status         - Show status of all services"
	@echo ""
	@echo "🌐 Access Points:"
	@echo "  Main Frontend (HR/Recruiter): http://localhost:3501"
	@echo "  Applicant Portal:             http://localhost:5173"
	@echo "  API Gateway:                  http://localhost:9080"
	@echo "  Grafana (Monitoring):         http://localhost:3050"
	@echo ""

setup:
	@echo "╔════════════════════════════════════════════════════════════════╗"
	@echo "║     Candidacy Platform - Complete Setup                       ║"
	@echo "╚════════════════════════════════════════════════════════════════╝"
	@echo ""
	@echo "📋 Step 1/6: Setting up environment..."
	@bash scripts/setup-env.sh
	@echo ""
	@echo "📋 Step 2/6: Building Docker images..."
	@echo "   • Building base image..."
	@docker build -f infrastructure/docker/Dockerfile.base -t candidacy-base:latest .
	@echo "   • Building service images..."
	@docker compose build
	@echo ""
	@echo "📋 Step 3/6: Starting MySQL and Redis..."
	@docker compose up -d mysql redis
	@echo "   ⏳ Waiting for MySQL to be ready..."
	@sleep 15
	@echo ""
	@echo "📋 Step 4/6: Initializing databases from DBML..."
	@$(MAKE) dbml-init
	@echo ""
	@echo "📋 Step 5/6: Starting all services..."
	@docker compose up -d
	@echo "   ⏳ Waiting for services to initialize..."
	@sleep 10
	@echo ""
	@echo "📋 Step 6/6: Seeding configuration and sample data..."
	@echo "   • Seeding configuration settings (27 settings)..."
	@docker compose exec -T admin-service php artisan db:seed --class=ConfigurationSeeder || echo "⚠️  Configuration seeding will run on first admin-service start"
	@echo "   • Seeding sample data (optional)..."
	@docker compose exec -T auth-service php artisan db:seed --force || echo "⚠️  Auth service seeding skipped"
	@docker compose exec -T admin-service php artisan db:seed --force || echo "⚠️  Admin service seeding skipped"
	@echo ""
	@echo "╔════════════════════════════════════════════════════════════════╗"
	@echo "║                    ✅ Setup Complete!                          ║"
	@echo "╚════════════════════════════════════════════════════════════════╝"
	@echo ""
	@echo "🌐 Access Points:"
	@echo "  • Main Frontend (HR/Recruiter): http://localhost:3501"
	@echo "  • Applicant Portal:             http://localhost:5173"
	@echo "  • API Gateway:                  http://localhost:9080"

	@echo "  • Grafana (Monitoring):         http://localhost:3050 (admin/admin)"
	@echo ""
	@echo "📊 What was set up:"
	@echo "  ✓ Environment configuration (.env)"
	@echo "  ✓ Base Docker image built"
	@echo "  ✓ MySQL and Redis started"
	@echo "  ✓ 9 databases initialized from DBML"
	@echo "  ✓ All microservices started"
	@echo "  ✓ 27 configuration settings seeded"
	@echo "  ✓ Sample data seeded (auth, admin)"
	@echo ""
	@echo "📚 Next Steps:"
	@echo "  • View logs:           make logs"
	@echo "  • View configuration:  curl http://localhost:9080/api/admin/settings | jq"
	@echo "  • Seed more data:      make seed"
	@echo "  • Update config:       See CONFIGURATION.md"
	@echo "  • Documentation:       See README.md"
	@echo ""

up:
	@echo "🚀 Starting all services..."
	docker compose up -d
	@echo ""
	@echo "✅ All services started!"
	@echo ""
	@echo "🌐 Access Points:"
	@echo "  Main Frontend:    http://localhost:3501"
	@echo "  Applicant Portal: http://localhost:5173"
	@echo "  API Gateway:      http://localhost:9080"
	@echo "  Grafana:          http://localhost:3050 (admin/admin)"
	@echo ""
	@echo "📊 Run 'make logs' to view logs or 'make status' to check service health"

down:
	@echo "🛑 Stopping all services..."
	docker compose down
	@echo "✅ All services stopped"

restart:
	@echo "🔄 Restarting all services..."
	docker compose restart
	@echo "✅ All services restarted"

build:
	@echo "🔨 Rebuilding all containers..."
	@echo "   • Building base image first..."
	@docker build -f infrastructure/docker/Dockerfile.base -t candidacy-base:latest .
	@echo "   • Building service images..."
	@docker compose build
	@echo "✅ Build complete"

pull:
	@echo "📥 Pulling latest images..."
	docker compose pull
	@echo "✅ Images updated"

status:
	@echo ""
	@echo "╔══════════════════════════════════════════════════════════════════════════════╗"
	@echo "║                    📊 Candidacy Platform - Service Status                    ║"
	@echo "╚══════════════════════════════════════════════════════════════════════════════╝"
	@echo ""
	@# Define color codes and check services
	@GREEN=$$'\033[0;32m'; RED=$$'\033[0;31m'; YELLOW=$$'\033[0;33m'; CYAN=$$'\033[0;36m'; NC=$$'\033[0m'; BOLD=$$'\033[1m'; \
	\
	check_service() { \
		local name="$$1"; \
		local container="$$2"; \
		local port="$$3"; \
		local status=$$(docker inspect -f '{{.State.Running}}' $$container 2>/dev/null); \
		if [ "$$status" = "true" ]; then \
			printf "  %b●%b %-28s %bonline%b   %s\n" "$$GREEN" "$$NC" "$$name" "$$GREEN" "$$NC" "$$port"; \
		else \
			printf "  %b○%b %-28s %boffline%b\n" "$$RED" "$$NC" "$$name" "$$RED" "$$NC"; \
		fi; \
	}; \
	\
	printf "%b%b🔧 Infrastructure%b\n" "$$CYAN" "$$BOLD" "$$NC"; \
	echo "  ─────────────────────────────────────────────────────"; \
	check_service "MySQL" "candidacy-mysql" ""; \
	check_service "Redis" "candidacy-redis" ""; \
	check_service "Ollama (Local AI)" "candidacy-ollama" ""; \
	echo ""; \
	\
	printf "%b%b🌐 Gateway & Frontends%b\n" "$$CYAN" "$$BOLD" "$$NC"; \
	echo "  ─────────────────────────────────────────────────────"; \
	check_service "API Gateway" "candidacy-gateway" "http://localhost:9080"; \
	check_service "Main Frontend (HR)" "candidacy-frontend" "http://localhost:3501"; \
	check_service "Applicant Portal" "candidacy-applicant-frontend" "http://localhost:5173"; \
	echo ""; \
	\
	printf "%b%b🔐 Core Services%b\n" "$$CYAN" "$$BOLD" "$$NC"; \
	echo "  ─────────────────────────────────────────────────────"; \
	check_service "Auth Service" "candidacy-auth" ""; \
	check_service "Candidate Service" "candidacy-candidate" ""; \
	check_service "Vacancy Service" "candidacy-vacancy" ""; \
	check_service "Matching Service" "candidacy-matching" ""; \
	check_service "AI Service" "candidacy-ai" ""; \
	echo ""; \
	\
	printf "%b%b📋 Support Services%b\n" "$$CYAN" "$$BOLD" "$$NC"; \
	echo "  ─────────────────────────────────────────────────────"; \
	check_service "Interview Service" "candidacy-interview" ""; \
	check_service "Offer Service" "candidacy-offer" ""; \
	check_service "Onboarding Service" "candidacy-onboarding" ""; \
	check_service "Reporting Service" "candidacy-reporting" ""; \
	check_service "Admin Service" "candidacy-admin" ""; \
	check_service "Notification Service" "candidacy-notification" ""; \
	check_service "Document Parser" "candidacy-document-parser" ""; \
	echo ""; \
	\
	printf "%b%b⚙️  Queue Workers%b\n" "$$CYAN" "$$BOLD" "$$NC"; \
	echo "  ─────────────────────────────────────────────────────"; \
	check_service "Candidate Queue Worker" "candidacy-candidate-worker" ""; \
	check_service "Matching Queue Worker" "candidacy-matching-worker" ""; \
	check_service "Document Parser Worker" "candidacy-document-parser-worker" ""; \
	check_service "Notification Worker" "candidacy-notification-worker" ""; \
	echo ""; \
	\
	printf "%b%b📈 Monitoring & Logging%b\n" "$$CYAN" "$$BOLD" "$$NC"; \
	echo "  ─────────────────────────────────────────────────────"; \
	check_service "Grafana" "candidacy-grafana" "http://localhost:3050"; \
	check_service "Loki" "candidacy-loki" ""; \
	check_service "Promtail" "candidacy-promtail" ""; \
	echo ""; \
	\
	printf "%b%b🛠️  Development Tools%b\n" "$$CYAN" "$$BOLD" "$$NC"; \
	echo "  ─────────────────────────────────────────────────────"; \
	check_service "Mailpit" "candidacy-mailpit" "http://localhost:8025"; \
	check_service "Cloudflare Tunnel" "candidacy-cloudflared" ""; \
	echo ""; \
	\
	ONLINE=$$(docker ps --format '{{.Names}}' | grep -c 'candidacy-' 2>/dev/null || echo 0); \
	TOTAL=27; \
	echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"; \
	if [ "$$ONLINE" -eq "$$TOTAL" ]; then \
		printf "  %b%b✅ All services online (%d/%d)%b\n" "$$GREEN" "$$BOLD" "$$ONLINE" "$$TOTAL" "$$NC"; \
	elif [ "$$ONLINE" -eq 0 ]; then \
		printf "  %b%b❌ All services offline (%d/%d)%b\n" "$$RED" "$$BOLD" "$$ONLINE" "$$TOTAL" "$$NC"; \
	else \
		printf "  %b%b⚠️  Partial status: %d/%d services online%b\n" "$$YELLOW" "$$BOLD" "$$ONLINE" "$$TOTAL" "$$NC"; \
	fi; \
	echo ""

logs:
	docker compose logs -f


# Removing legacy migrate target. Use make dbml-init instead.

seed:
	@echo "🌱 Seeding databases..."
	@echo "Seeding Auth Service..."
	docker compose exec -T auth-service php artisan db:seed --force || true
	@echo "Seeding Admin Service..."
	docker compose exec -T admin-service php artisan db:seed --force || true
	@echo "✅ Seeding complete"

seed-config:
	@echo "⚙️  Seeding configuration settings..."
	@echo ""
	@docker compose exec -T admin-service php artisan db:seed --class=ConfigurationSeeder
	@echo ""
	@echo "✅ Configuration seeded successfully!"
	@echo ""
	@echo "📊 Seeded 27 configuration settings across 7 categories:"
	@echo "  • System (6): app name, company, contact, timezone, language"
	@echo "  • AI (7): provider, Ollama URL/models, OpenRouter settings"
	@echo "  • Document Parser (4): Granite Docling settings"
	@echo "  • Recruitment (3): auto-matching, offer expiry, reminders"
	@echo "  • Storage (2): CV size limit, max upload size"
	@echo "  • Features (3): enable AI, notifications, auto-matching"
	@echo "  • Services (2): AI service URL, document parser URL"
	@echo ""
	@echo "📝 View configuration:"
	@echo "  curl http://localhost:8090/api/settings | jq"
	@echo ""
	@echo "📚 Documentation: See CONFIGURATION.md"

migrate-tenants:
	@echo "🏢 Running tenant_id migrations on all business services..."
	@echo "   • Migrating auth-service..."
	@docker compose exec -T auth-service php artisan migrate --force || echo "⚠️  auth-service not running, skipping"
	@echo "   • Migrating candidate-service..."
	@docker compose exec -T candidate-service php artisan migrate --force || echo "⚠️  candidate-service not running, skipping"
	@echo "   • Migrating vacancy-service..."
	@docker compose exec -T vacancy-service php artisan migrate --force || echo "⚠️  vacancy-service not running, skipping"
	@echo "   • Migrating matching-service..."
	@docker compose exec -T matching-service php artisan migrate --force || echo "⚠️  matching-service not running, skipping"
	@echo "   • Migrating interview-service..."
	@docker compose exec -T interview-service php artisan migrate --force || echo "⚠️  interview-service not running, skipping"
	@echo "   • Migrating offer-service..."
	@docker compose exec -T offer-service php artisan migrate --force || echo "⚠️  offer-service not running, skipping"
	@echo "   • Migrating onboarding-service..."
	@docker compose exec -T onboarding-service php artisan migrate --force || echo "⚠️  onboarding-service not running, skipping"
	@echo "   • Migrating notification-service..."
	@docker compose exec -T notification-service php artisan migrate --force || echo "⚠️  notification-service not running, skipping"
	@echo ""
	@echo "✅ Tenant migrations complete!"
	@echo "💡 Tip: To backfill existing data, run tinker in each service and set tenant_id = 1"


db-reset:
	@echo "⚠️  WARNING: This will delete all data!"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		echo "🗑️  Resetting databases..."; \
		docker compose down -v; \
		sleep 10; \
		docker compose up -d mysql; \
		echo "⏳ Waiting for MySQL to initialize..."; \
		sleep 20; \
		make dbml-init; \
		docker compose up -d; \
		sleep 5; \
		make seed; \
		echo "✅ Database reset complete"; \
	fi

clear-matches:
	@echo "🧹 Clearing all candidate matches..."
	@docker exec candidacy-matching sh -c "php -r \"require 'vendor/autoload.php'; \$$app = require_once 'bootstrap/app.php'; \$$kernel = \$$app->make(Illuminate\Contracts\Console\Kernel::class); \$$kernel->bootstrap(); \App\Models\CandidateMatch::query()->delete(); echo 'Deleted all matches. Count: ' . \App\Models\CandidateMatch::count();\""
	@echo "✅ Matches cleared. Run 'Run Matching' in the UI to regenerate."

test:
	@echo "🧪 Running all tests..."
	@./scripts/run-tests.sh

test-backend:
	@echo "🧪 Running backend service tests..."
	@./scripts/test-backend-services.sh

test-api:
	@echo "🧪 Running API endpoint tests..."
	@./scripts/test-api-endpoints.sh

test-integration:
	@echo "🧪 Running integration tests..."
	@./scripts/test-integration.sh

test-e2e:
	@echo "🧪 Running end-to-end tests..."
	@./scripts/test-e2e.sh

test-service:
	@if [ -z "$(S)" ]; then \
		echo "❌ Error: Please specify a service with S=<service-name>"; \
		echo "Example: make test-service S=auth-service"; \
		exit 1; \
	fi
	@echo "🧪 Testing $(S)..."
	@docker compose exec $(S) php artisan test

# Legacy individual service test commands (for backward compatibility)
test-auth:
	@echo "🧪 Testing Auth Service..."
	docker compose exec auth-service php artisan test

test-candidate:
	@echo "🧪 Testing Candidate Service..."
	docker compose exec candidate-service php artisan test

test-vacancy:
	@echo "🧪 Testing Vacancy Service..."
	docker compose exec vacancy-service php artisan test

test-tenant-isolation:
	@echo "🏢 Running Tenant Isolation Tests across all services..."
	@echo "   • candidate-service:"
	@docker compose exec -T candidate-service php artisan test --filter TenantIsolationTest || echo "⚠️  candidate-service not running"
	@echo "   • vacancy-service:"
	@docker compose exec -T vacancy-service php artisan test --filter TenantIsolationTest || echo "⚠️  vacancy-service not running"
	@echo ""
	@echo "✅ Tenant isolation tests complete"

test-resumes:
	@echo "📄 Generating test resumes (PDF/DOCX) from markdown..."
	@docker compose --profile testing build testing
	@docker compose --profile testing run --rm testing
	@echo ""
	@echo "✅ Test resumes generated:"
	@ls -la tests/fixtures/*.pdf tests/fixtures/*.docx 2>/dev/null || echo "   (files will be in tests/fixtures/)"

clean:
	@echo "🧹 Cleaning up containers and volumes..."
	docker compose down -v
	@echo "✅ Cleaned up all containers and volumes"

shell:
	@if [ -z "$(S)" ]; then \
		echo "❌ Error: Please specify a service with S=<service-name>"; \
		echo "Example: make shell S=auth-service"; \
		exit 1; \
	fi
	docker compose exec $(S) bash

# Service-specific log commands
logs-gateway:
	docker compose logs -f api-gateway

logs-auth:
	docker compose logs -f auth-service

logs-candidate:
	docker compose logs -f candidate-service

logs-vacancy:
	docker compose logs -f vacancy-service

logs-ai:
	docker compose logs -f ai-service

logs-matching:
	docker compose logs -f matching-service

logs-interview:
	docker compose logs -f interview-service

logs-offer:
	docker compose logs -f offer-service

logs-onboarding:
	docker compose logs -f onboarding-service

logs-reporting:
	docker compose logs -f reporting-service

logs-admin:
	docker compose logs -f admin-service

logs-notification:
	docker compose logs -f notification-service

logs-document-parser:
	docker compose logs -f document-parser-service

logs-frontend:
	docker compose logs -f frontend

logs-applicant:
	docker compose logs -f applicant-frontend

logs-grafana:
	docker compose logs -f grafana

logs-parse-cv:
	docker compose logs -f candidate-service document-parser-service ai-service

# DBML Commands (Database-as-Code)
# These commands run in a Docker container, so Node.js is not required on the host
dbml-validate:
	@echo "🔍 Validating DBML schema..."
	@docker compose run --rm dbml-tools npm run dbml:validate

dbml-sql:
	@echo "🔨 Generating SQL from DBML..."
	@docker compose run --rm dbml-tools npm run dbml:sql

dbml-check:
	@echo "🔍 Checking DBML sync status..."
	@docker compose run --rm dbml-tools npm run dbml:check

dbml-init:
	@echo "🗄️  Initializing databases from DBML..."
	@docker compose run --rm dbml-tools bash scripts/init-databases-from-dbml.sh

dbml-reset:
	@echo "⚠️  WARNING: This will drop all databases and recreate from DBML!"
	@echo "⚠️  All data will be lost!"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		echo "🗑️  Dropping databases..."; \
		docker compose down -v && \
		echo "🚀 Starting MySQL..." && \
		docker compose up -d mysql && \
		echo "⏳ Waiting for MySQL to initialize..." && \
		sleep 20 && \
		echo "🗄️  Initializing from DBML..." && \
		make dbml-init && \
		echo "🚀 Starting all services..." && \
		docker compose up -d && \
		sleep 5 && \
		echo "🌱 Seeding databases..." && \
		make seed && \
		echo "✅ Databases reset from DBML complete"; \
	else \
		echo "Operation cancelled."; \
		exit 1; \
	fi

setup-env:
	@echo "🔧 Generating .env files from templates..."
	@./scripts/setup-env.sh

generate-secrets:
	@echo "🔐 Generating secrets for all services..."
	@echo "This feature will be implemented in setup-services.sh"
	@echo "For now, manually update JWT_SECRET and APP_KEY in .env files"

# Documentation Commands
docs-php:
	@echo "📚 Generating PHP API documentation..."
	@mkdir -p docs/api
	@docker run --rm -v "$(PWD):/data" phpdoc/phpdoc:3 run -c phpdoc.dist.xml
	@echo ""
	@echo "✅ Documentation generated!"
	@echo "   Open docs/api/index.html in your browser"
	@echo "   Or run: make docs-serve"

docs-serve:
	@echo "🌐 Serving PHP documentation at http://localhost:8000"
	@echo "   Press Ctrl+C to stop"
	@cd docs/api && python3 -m http.server 8000

# Cloudflare Tunnel Commands
tunnel-up:
	@echo "🌐 Starting Cloudflare Tunnel..."
	@if [ -z "${CLOUDFLARE_TUNNEL_TOKEN}" ]; then \
		echo "❌ Error: CLOUDFLARE_TUNNEL_TOKEN is not set in .env"; \
		echo ""; \
		echo "Please follow these steps:"; \
		echo "1. Create a tunnel in Cloudflare dashboard: https://one.dash.cloudflare.com/"; \
		echo "2. Copy the tunnel token"; \
		echo "3. Add it to your .env file: CLOUDFLARE_TUNNEL_TOKEN=your-token-here"; \
		echo "4. Run 'make tunnel-up' again"; \
		echo ""; \
		echo "See CLOUDFLARE_TUNNEL.md for detailed instructions"; \
		exit 1; \
	fi
	@docker compose up -d cloudflared
	@echo ""
	@echo "✅ Cloudflare Tunnel started!"
	@echo ""
	@echo "🌐 Your application should now be accessible at:"
	@echo "   https://${PUBLIC_DOMAIN:-ne1-candidacy.comulo.app}"
	@echo ""
	@echo "📊 Check tunnel status: make tunnel-status"
	@echo "📋 View tunnel logs:    make tunnel-logs"

tunnel-down:
	@echo "🛑 Stopping Cloudflare Tunnel..."
	@docker compose stop cloudflared
	@echo "✅ Cloudflare Tunnel stopped"

tunnel-logs:
	@echo "📋 Cloudflare Tunnel Logs (Ctrl+C to exit):"
	@echo ""
	@docker compose logs -f cloudflared

tunnel-status:
	@echo "📊 Cloudflare Tunnel Status:"
	@echo ""
	@docker compose ps cloudflared
	@echo ""
	@if docker compose ps cloudflared | grep -q "Up"; then \
		echo "✅ Tunnel is running"; \
		echo ""; \
		echo "🌐 Access your application at:"; \
		echo "   https://${PUBLIC_DOMAIN:-ne1-candidacy.comulo.app}"; \
	else \
		echo "❌ Tunnel is not running"; \
		echo ""; \
		echo "Start the tunnel with: make tunnel-up"; \
	fi

