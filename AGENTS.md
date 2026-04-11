# Agent Coding Guidelines - Candidacy Platform

## Overview
Candidacy is a microservices-based recruitment management platform with Laravel backends and Vue.js frontends.

---

## Build & Deployment Commands

### Main Commands
- `make setup` - Complete platform setup (recommended for first time)
- `make up` - Start all services
- `make down` - Stop all services
- `make restart` - Restart all services
- `make build` - Rebuild all Docker containers
- `make clean` - Clean up containers and volumes

### Database Commands (DBML-First)
- `make dbml-validate` - Validate DBML schema syntax
- `make dbml-sql` - Generate SQL from DBML
- `make dbml-check` - Check if generated SQL is in sync with DBML
- `make dbml-init` - Initialize databases from DBML
- `make dbml-reset` - Drop & recreate databases from DBML (WARNING: destructive)
- `make seed` - Seed all databases with sample data
- `make seed-config` - Seed configuration settings (27 settings)

### Service-Specific Commands
- `make shell S=<service>` - Access service shell (e.g., `make shell S=auth-service`)
- `make logs` - View all service logs
- `make logs-<service>` - View specific service logs (e.g., `make logs-auth`, `make logs-candidate`, etc.)

### API Gateway
- Frontend (HR/Recruiter): http://localhost:3501
- Applicant Portal: http://localhost:5173
- API Gateway: http://localhost:9080
- Grafana (Monitoring): http://localhost:3050 (admin/admin)

---

## Testing Commands

### Run All Tests
- `make test` - Run all tests (backend, API, integration, e2e)

### Individual Test Suites
- `make test-backend` - Run backend service tests (PHPUnit)
- `make test-api` - Run API endpoint tests
- `make test-integration` - Run integration tests
- `make test-e2e` - Run end-to-end workflow tests

### Service-Specific Tests
- `make test-service S=<service>` - Run tests for specific service (e.g., `make test-service S=auth-service`)
- `make test-tenant-isolation` - Run TenantIsolationTest on all services
- `make test-resumes` - Generate test resume PDF/DOCX from markdown

### Individual Service Test Commands
- `make test-auth`, `make test-candidate`, `make test-vacancy`, `make test-ai`, `make test-matching`, `make test-interview`, `make test-offer`, `make test-onboarding`, `make test-reporting`, `make test-admin`, `make test-notification`

---

## Frontend Commands

### Web App (HR/Recruiter Dashboard)
```bash
cd frontend/web-app
npm install
npm run dev    # Start development server (port 3000)
npm run build  # Build for production
npm run preview # Preview production build
```

### Applicant Web App
```bash
cd frontend/applicant-web-app
npm install
npm run dev    # Start development server (port 3000)
npm run build  # Build for production
npm run preview # Preview production build
```

### Frontend Build Tools
- Framework: Vue.js 3 + Vite
- State Management: Pinia
- HTTP Client: Axios
- Router: Vue Router 4

---

## Backend Commands (Laravel Microservices)

### Service Structure
Each service in `services/` is a Laravel 10 application with:
- `app/` - Application code
- `resources/` - Views, lang, js, css
- `database/` - Migrations, seeders, factories
- `routes/` - API routes
- `tests/` - PHPUnit tests

### Service Commands
```bash
make shell S=<service>
php artisan <command>
```

### Service-Specific Commands
- `php artisan migrate` - Run migrations
- `php artisan db:seed` - Seed database
- `php artisan test` - Run PHPUnit tests

---

## Code Style Guidelines

### PHP/Laravel Backend

#### Coding Standards
- Follow **PSR-12** coding standards
- Use Laravel 10 best practices
- Use `BaseApiController` for consistent API responses
- Add proper PHPDoc comments for all public methods
- Use type hints for parameters and return types

#### Naming Conventions
- **Classes**: PascalCase (e.g., `CandidateController`)
- **Methods**: camelCase (e.g., `listCandidates`)
- **Variables**: camelCase (e.g., `$candidateId`)
- **Database tables**: snake_case plural (e.g., `candidates`, `vacancies`)
- **Database columns**: snake_case (e.g., `first_name`, `created_at`)
- **Routes**: snake_case (e.g., `/api/candidates`, `/api/matches/candidates/{id}`)
- **Filenames**: match class names (e.g., `CandidateService.php`)

#### Error Handling
- Use Laravel's exception handler
- Return JSON responses with consistent structure
- Use HTTP status codes appropriately (200, 201, 400, 401, 403, 404, 422, 500)
- Validate input using Form Requests or validate() method

#### Testing
- Use PHPUnit for all backend tests
- Test all public methods
- Use factories for test data
- Mock external dependencies

#### Database (DBML-First)
- Edit `.dbml` files, never SQL files directly
- Follow existing naming conventions (snake_case)
- Add appropriate indexes for query optimization
- Document relationships in `relationships.dbml`
- Run `make dbml-check` before committing DB changes

---

### Vue.js Frontend

#### Coding Standards
- Use **Composition API** with `<script setup>`
- Follow existing component patterns
- Use design system CSS variables
- Add proper TypeScript types where applicable

#### Naming Conventions
- **Components**: PascalCase (e.g., `CandidateList.vue`, `DashboardLayout.vue`)
- **Props**: camelCase (e.g., `candidateId`, `showActions`)
- **Emits**: camelCase (e.g., `update-candidate`, `delete-record`)
- **Methods**: camelCase (e.g., `fetchCandidates`, `handleDelete`)
- **Variables/Functions**: camelCase (e.g., `isLoading`, `saveForm`)
- **Files**: PascalCase for components, camelCase for utilities (e.g., `useApi.js`, `errorHandler.js`)

#### Import Order
1. Vue & core libraries (vue, vue-router, pinia)
2. External dependencies (axios, dayjs, etc.)
3. Local utilities (utils, validators, errorHandler)
4. Services (api, api services)
5. Components (local components)
6. Composables (use* functions)
7. Stores (pinia stores)

#### State Management
- Use **Pinia** for state management
- Create features-specific stores in `src/stores/`
- Use composition API composables for component-level state

#### Error Handling
- Use `useErrorHandler` composable for consistent error display
- Parse API errors using `parseApiError` from `utils/errorHandler.js`
- Show user-friendly error messages
- Log errors with context for debugging

---

## Git Workflow

### Branching Strategy
- `main` - Production-ready code
- `develop` - Integration branch
- `feature/your-feature-name` - Feature branches
- `fix/issue-description` - Bug fix branches

### Commit Messages
Use **conventional commits**:
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation only
- `refactor:` - Code refactoring
- `test:` - Adding tests
- `chore:` - Maintenance tasks

Example: `git commit -m "feat: add candidate bulk upload feature"`

### Pull Request Process
1. Create feature branch from `develop`
2. Make changes following code standards
3. Add/update tests as needed
4. Test locally with `make test`
5. Update documentation if needed
6. Create PR with clear description

---

## Common Tasks

### Adding a New Service
1. Copy structure from existing service (e.g., `auth-service`)
2. Update `docker-compose.yml` with new service
3. Add service to `Makefile` log commands
4. Update `CONTRIBUTING.md` with service details
5. Run `make build` to rebuild images

### Database Changes
1. Edit `.dbml` file in `database/dbml/`
2. Run `make dbml-validate` to verify syntax
3. Run `make dbml-sql` to generate SQL
4. Run `make dbml-reset` to apply changes (WARNING: data loss)
5. Or run `make dbml-init` if database exists and data can be preserved

### Adding New API Endpoints
1. Add route in `services/*/routes/api.php`
2. Create controller method
3. Add API method in frontend `src/services/api.js`
4. Test with `make test-api`

---

## Key Files & Directories

### Root Level
- `Makefile` - Main build and test commands
- `docker-compose.yml` - Service definitions
- `CONTRIBUTING.md` - Contribution guidelines
- `AGENTS.md` - This file

### Services
- `services/` - 12 Laravel microservices
- `gateway/api-gateway/` - API Gateway (Vue.js with Vite)
- `frontend/web-app/` - HR/Recruiter dashboard
- `frontend/applicant-web-app/` - Candidate portal
- `shared/` - Shared PHP libraries
- `infrastructure/` - Docker, logging, monitoring configs
- `database/dbml/` - Database schema files
- `scripts/` - Build and utility scripts

---

## Prerequisites

### For Development
- Docker & Docker Compose
- PHP 8.2+ (for local development)
- Node.js 18+ (for frontend development)
- Git
- Ollama (optional, for local AI)

---

## Quick Reference

### Essential Commands
```bash
make setup              # Full platform setup
make up                 # Start services
make test               # Run all tests
make shell S=auth       # Access auth service shell
make logs               # View all logs
make dbml-reset         # Reset databases (destructive)
make clean              # Clean up containers
```

### Frontend Quick Commands
```bash
cd frontend/web-app && npm run dev      # Start HR dashboard
cd frontend/applicant-web-app && npm run dev  # Start applicant portal
```

### Backend Quick Commands
```bash
make shell S=candidate-service
php artisan test
php artisan migrate:fresh --seed
```
