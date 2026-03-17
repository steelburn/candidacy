# Makefile Command Reference

Complete reference for all Makefile commands in the Candidacy project.

---

## Overview

The Makefile provides 56 commands for common development tasks. This guide documents each command and its usage.

---

## Getting Started

### help
Display all available Makefile commands with descriptions.

```bash
make help
```

### setup
Complete platform setup (recommended for first-time installation).

```bash
make setup
```

**What it does:**
- Configures environment files
- Builds base Docker image
- Starts MySQL and Redis
- Initializes 13 databases from DBML
- Starts all 12 microservices
- Seeds configuration settings

---

## Service Management

### up
Start all services.

```bash
make up
```

### down
Stop all services.

```bash
make down
```

### restart
Restart all services.

```bash
make restart
```

### build
Build Docker images.

```bash
make build
```

### pull
Pull latest Docker images.

```bash
make pull
```

### status
Show status of all services.

```bash
make status
```

### clean
Clean up containers, volumes, and build artifacts.

```bash
make clean
```

---

## Logging

### logs
View combined logs for all services.

```bash
make logs
```

### logs-{service}
View logs for a specific service.

```bash
make logs-gateway     # API Gateway
make logs-auth       # Auth Service
make logs-candidate  # Candidate Service
make logs-vacancy    # Vacancy Service
make logs-ai         # AI Service
make logs-matching   # Matching Service
make logs-interview  # Interview Service
make logs-offer      # Offer Service
make logs-onboarding # Onboarding Service
make logs-reporting  # Reporting Service
make logs-admin      # Admin Service
make logs-notification    # Notification Service
make logs-document-parser # Document Parser Service
make logs-frontend  # Web App
make logs-applicant  # Applicant Portal
make logs-grafana    # Grafana Monitoring
make logs-parse-cv   # CV parsing workflow
```

---

## Database Management

### seed
Seed database with initial data.

```bash
make seed
```

### seed-config
Seed configuration settings.

```bash
make seed-config
```

### migrate-tenants
Run migrations for all tenants.

```bash
make migrate-tenants
```

### db-reset
Reset all databases (WARNING: data loss).

```bash
make db-reset
```

### clear-matches
Clear all match data.

```bash
make clear-matches
```

---

## DBML Schema Management

### dbml-validate
Validate DBML schema syntax.

```bash
make dbml-validate
```

### dbml-sql
Generate SQL from DBML schema.

```bash
make dbml-sql
```

### dbml-check
Check if databases match DBML schema.

```bash
make dbml-check
```

### dbml-init
Initialize databases from DBML schema.

```bash
make dbml-init
```

### dbml-reset
Reset databases from DBML (WARNING: data loss).

```bash
make dbml-reset
```

---

## Testing

### test
Run all tests.

```bash
make test
```

### test-backend
Test all backend services are running.

```bash
make test-backend
```

### test-api
Test API endpoints.

```bash
make test-api
```

### test-integration
Run integration tests.

```bash
make test-integration
```

### test-service S={service}
Test specific service.

```bash
make test-service S=candidate-service
```

### test-auth
Run authentication tests.

```bash
make test-auth
```

### test-candidate
Run candidate service tests.

```bash
make test-candidate
```

### test-vacancy
Run vacancy service tests.

```bash
make test-vacancy
```

### test-tenant-isolation
Test tenant isolation.

```bash
make test-tenant-isolation
```

### test-resumes
Test CV/resume parsing.

```bash
make test-resumes
```

---

## Development Tools

### shell S={service}
Open shell in a service container.

```bash
make shell S=candidate-service
```

### setup-env
Setup environment variables.

```bash
make setup-env
```

### generate-secrets
Generate application secrets.

```bash
make generate-secrets
```

### docs-php
Generate PHP documentation.

```bash
make docs-php
```

### docs-serve
Serve PHP documentation.

```bash
make docs-serve
```

---

## Cloudflare Tunnel

### tunnel-up
Start Cloudflare Tunnel.

```bash
make tunnel-up
```

### tunnel-down
Stop Cloudflare Tunnel.

```bash
make tunnel-down
```

### tunnel-logs
View Cloudflare Tunnel logs.

```bash
make tunnel-logs
```

### tunnel-status
Check Cloudflare Tunnel status.

```bash
make tunnel-status
```

---

## Common Workflows

### Development Workflow
```bash
# Start development
make up

# View logs
make logs

# Make code changes...

# Restart specific service
docker-compose restart candidate-service

# Stop development
make down
```

### Database Changes
```bash
# 1. Edit schema.dbml
# 2. Validate changes
make dbml-validate

# 3. Generate SQL
make dbml-sql

# 4. Apply to local database (data will be lost!)
make dbml-reset
```

### Running Tests
```bash
# Quick health check
make test-backend

# Full test suite
make test

# Specific tests
make test-api
make test-integration
```

### Troubleshooting
```bash
# Check service status
make status

# View specific logs
make logs-candidate

# Test connectivity
make test-backend

# Reset everything
make clean
make up
```

---

## Environment Variables

Makefile uses these key variables:

| Variable | Description | Default |
|----------|-------------|---------|
| `COMPOSE_PROJECT_NAME` | Docker project name | candidacy |
| `COMPOSE_FILE` | Compose file path | docker-compose.yml |

---

## File Structure Reference

```
.
├── Makefile                 # Main makefile
├── docker-compose.yml       # Service definitions
├── .env                     # Environment variables
├── schema.dbml              # Database schema
├── services/                # Microservices
├── gateway/                 # API Gateway
├── frontend/                # Frontend apps
├── scripts/                # Helper scripts
└── infrastructure/          # Infrastructure configs
```

---

## Related Documentation

- [QUICKSTART.md](QUICKSTART.md) - Quick start guide
- [TESTING.md](TESTING.md) - Testing guide
- [DEPLOYMENT.md](DEPLOYMENT.md) - Production deployment
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Common issues
