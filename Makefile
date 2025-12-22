# Candidacy Development Makefile

.PHONY: help setup up down restart logs migrate seed test clean shell
.PHONY: logs-auth logs-candidate logs-vacancy logs-ai logs-matching logs-interview
.PHONY: logs-offer logs-onboarding logs-reporting logs-admin logs-notification
.PHONY: logs-gateway logs-frontend logs-applicant logs-grafana
.PHONY: db-reset pull build

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
	@echo "  make migrate        - Run migrations for all services"
	@echo "  make seed           - Seed all databases"
	@echo "  make db-reset       - Reset all databases (WARNING: destructive)"
	@echo ""
	@echo "🔍 Monitoring Commands:"
	@echo "  make logs           - View all service logs"
	@echo "  make logs-gateway   - View API Gateway logs"
	@echo "  make logs-auth      - View Auth Service logs"
	@echo "  make logs-candidate - View Candidate Service logs"
	@echo "  make logs-vacancy   - View Vacancy Service logs"
	@echo "  make logs-ai        - View AI Service logs"
	@echo "  make logs-matching  - View Matching Service logs"
	@echo "  make logs-interview - View Interview Service logs"
	@echo "  make logs-offer     - View Offer Service logs"
	@echo "  make logs-onboarding- View Onboarding Service logs"
	@echo "  make logs-reporting - View Reporting Service logs"
	@echo "  make logs-admin     - View Admin Service logs"
	@echo "  make logs-notification - View Notification Service logs"
	@echo "  make logs-frontend  - View Main Frontend logs"
	@echo "  make logs-applicant - View Applicant Portal logs"
	@echo "  make logs-grafana   - View Grafana logs"
	@echo ""
	@echo "🧪 Testing Commands:"
	@echo "  make test           - Run all tests"
	@echo "  make test-auth      - Test Auth Service"
	@echo "  make test-candidate - Test Candidate Service"
	@echo "  make test-vacancy   - Test Vacancy Service"
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
	@echo "  3. Run 'make migrate' to set up databases"
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

migrate:
	@echo "📊 Running migrations for all services..."
	./scripts/run-migrations.sh

seed:
	@echo "🌱 Seeding databases..."
	@echo "Seeding Auth Service..."
	docker compose exec auth-service php artisan db:seed --force || true
	@echo "Seeding Admin Service..."
	docker compose exec admin-service php artisan db:seed --force || true
	@echo "✅ Seeding complete"

db-reset:
	@echo "⚠️  WARNING: This will delete all data!"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		echo "🗑️  Resetting databases..."; \
		docker compose down -v; \
		docker compose up -d mysql redis; \
		sleep 10; \
		docker compose up -d; \
		sleep 5; \
		make migrate; \
		make seed; \
		echo "✅ Database reset complete"; \
	fi

test:
	@echo "🧪 Running tests for all services..."
	@./scripts/run-tests.sh

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

logs-frontend:
	docker compose logs -f frontend

logs-applicant:
	docker compose logs -f applicant-frontend

logs-grafana:
	docker compose logs -f grafana
