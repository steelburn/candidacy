# Contributing to Candidacy

Thank you for your interest in contributing to Candidacy! This document provides guidelines and instructions for contributing.

## 🚀 Getting Started

### Prerequisites

- Docker & Docker Compose
- Node.js 18+
- PHP 8.2+ (for local development)
- Git

### Development Setup

```bash
# Clone the repository
git clone <repository-url>
cd candidacy

# Complete platform setup
make setup

# Or step-by-step:
make dbml-init    # Initialize databases
make up           # Start services
make seed         # Seed sample data

# View all available commands
make help
```

## 📁 Project Structure

```
candidacy/
├── services/              # 12 Laravel microservices
├── gateway/               # API Gateway
├── frontend/              # Vue.js applications
│   ├── web-app/           # HR/Recruiter dashboard
│   └── applicant-web-app/ # Candidate portal
├── shared/                # Shared PHP libraries
├── infrastructure/        # Docker, logging, Grafana configs
├── database/dbml/         # Database schema (DBML files)
└── scripts/               # Build and utility scripts
```

## 🔧 Development Workflow

### Database Changes (DBML-First)

We use **Database-as-Code** with DBML. Never edit SQL files directly:

```bash
# 1. Edit the appropriate file in database/dbml/
#    (e.g., candidate.dbml for candidate-service tables)

# 2. Validate syntax
make dbml-validate

# 3. Generate SQL
make dbml-sql

# 4. Apply changes (destroys data!)
make dbml-reset
```

### Service Development

```bash
# Access service shell
make shell S=candidate-service

# View service logs
make logs-candidate

# Run service tests
make test-service S=candidate-service
```

### Frontend Development

```bash
# Frontend logs
make logs-frontend

# Applicant portal logs
make logs-applicant
```

## 🧪 Testing

```bash
# Run all tests
make test

# Run specific service tests
make test-service S=auth-service

# Run backend tests only
make test-backend

# Run API endpoint tests
make test-api
```

## 📝 Code Standards

### Backend (PHP/Laravel)

- Follow PSR-12 coding standards
- Use `BaseApiController` for consistent API responses
- Add proper PHPDoc comments
- Use factories for test data
- Include health check endpoints (`/api/health`)

### Frontend (Vue.js)

- Use Composition API with `<script setup>`
- Follow existing component patterns
- Use design system CSS variables
- Add proper TypeScript types where applicable

### Database (DBML)

- Edit service-specific `.dbml` files (not schema.dbml directly)
- Follow existing naming conventions (snake_case)
- Add appropriate indexes for query optimization
- Document relationships in `relationships.dbml`

## 🔍 Pull Request Process

1. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes**
   - Follow code standards
   - Add/update tests
   - Update documentation if needed

3. **Test locally**
   ```bash
   make test
   ```

4. **Commit with clear messages**
   ```bash
   git commit -m "feat: add candidate bulk upload feature"
   ```
   
   Use conventional commits:
   - `feat:` - New feature
   - `fix:` - Bug fix
   - `docs:` - Documentation only
   - `refactor:` - Code refactoring
   - `test:` - Adding tests
   - `chore:` - Maintenance tasks

5. **Push and create PR**
   ```bash
   git push origin feature/your-feature-name
   ```

## 🐛 Reporting Issues

When reporting issues, please include:

- Description of the issue
- Steps to reproduce
- Expected vs actual behavior
- Service logs (`make logs-<service>`)
- Environment details

## 📚 Documentation

- Update relevant `.md` files when changing features
- Keep documentation in sync with Makefile commands
- Update service READMEs for service-specific changes

## 🏗️ Architecture Guidelines

- Each service should remain **independent** and **single-purpose**
- Use **HTTP REST** for synchronous communication
- Use **Redis Pub/Sub** for asynchronous events
- Follow **database-per-service** pattern
- Share code via `shared/` directory only when necessary

## 📞 Getting Help

- Check existing documentation in `/docs`
- Review service README files
- Run `make help` for available commands
- Check Grafana dashboards at http://localhost:3050

## License

By contributing, you agree that your contributions will be licensed under the [Apache License 2.0](LICENSE).
