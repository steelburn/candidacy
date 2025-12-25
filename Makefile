# Candidacy Development Makefile
SHELL := /bin/bash

.PHONY: help setup up down restart logs seed test clean shell
.PHONY: logs-auth logs-candidate logs-vacancy logs-ai logs-matching logs-interview
.PHONY: logs-offer logs-onboarding logs-reporting logs-admin logs-notification
.PHONY: logs-gateway logs-frontend logs-applicant logs-grafana logs-parse-cv
.PHONY: db-reset pull build status
.PHONY: test-backend test-api test-integration test-e2e test-service
.PHONY: test-auth test-candidate test-vacancy
.PHONY: dbml-validate dbml-sql dbml-check dbml-init dbml-reset
.PHONY: logs-document-parser

help:
	@echo "╔════════════════════════════════════════════════════════════════╗"
	@echo "║     Candidacy Microservices - Development Commands            ║"
	@echo "╚════════════════════════════════════════════════════════════════╝"
	@echo ""
	@echo "🚀 Main Commands:"
	@echo "  make setup          - Initial setup (create all services)"
	@echo "  make up             - Start all services"
	@echo "  make down           - Stop all services"
	@echo "  make restart        - Restart all services"
	@echo "  make build          - Rebuild all containers"
	@echo "  make pull           - Pull latest images"
	@echo ""
	@echo "📊 Database Commands:"
	@echo "  make seed           - Seed all databases"
	@echo "  make db-reset       - Reset all databases (WARNING: destructive)"
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
	@echo "  make test           - Run all tests (backend, API, integration, e2e)"
	@echo "  make test-backend   - Run backend service tests (PHPUnit)"
	@echo "  make test-api       - Run API endpoint tests"
	@echo "  make test-integration - Run integration tests"
	@echo "  make test-e2e       - Run end-to-end workflow tests"
	@echo "  make test-service S=<service> - Run tests for specific service"
	@echo ""
	@echo "🛠️  Utility Commands:"
	@echo "  make shell S=<service> - Access service shell (e.g., make shell S=auth-service)"
	@echo "  make clean          - Clean up containers and volumes"
	@echo "  make status         - Show status of all services"
	@echo ""
	@echo "🌐 Access Points:"
	@echo "  Main Frontend (HR/Recruiter): http://localhost:3001"
	@echo "  Applicant Portal:             http://localhost:5173"
	@echo "  API Gateway:                  http://localhost:8080"
	@echo "  Grafana (Monitoring):         http://localhost:3050"
	@echo ""

setup:
	@echo "🚀 Setting up Candidacy Microservices..."
	./scripts/setup-services.sh
	@if [ ! -f .env ]; then cp .env.example .env; fi
	@echo ""
	@echo "✅ Setup complete!"
	@echo "📝 Next steps:"
	@echo "  1. Edit .env file with your configuration"
	@echo "  2. Run 'make up' to start all services"
	@echo "  3. Run 'make dbml-init' to set up databases"
	@echo "  4. Run 'make seed' to populate initial data"

up:
	@echo "🚀 Starting all services..."
	docker compose up -d
	@echo ""
	@echo "✅ All services started!"
	@echo ""
	@echo "🌐 Access Points:"
	@echo "  Main Frontend:    http://localhost:3001"
	@echo "  Applicant Portal: http://localhost:5173"
	@echo "  API Gateway:      http://localhost:8080"
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
	docker compose build
	@echo "✅ Build complete"

pull:
	@echo "📥 Pulling latest images..."
	docker compose pull
	@echo "✅ Images updated"

status:
	@echo "📊 Service Status:"
	@docker compose ps

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
dbml-validate:
	@echo "🔍 Validating DBML schema..."
	@npm run dbml:validate

dbml-sql:
	@echo "🔨 Generating SQL from DBML..."
	@npm run dbml:sql

dbml-check:
	@echo "🔍 Checking DBML sync status..."
	@npm run dbml:check

dbml-init:
	@echo "🗄️  Initializing databases from DBML..."
	@npm run dbml:init

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
