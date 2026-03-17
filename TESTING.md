# Candidacy Testing Guide

Comprehensive testing documentation for the AI-powered Candidacy recruitment platform.

## Overview

The Candidacy platform employs a multi-layered testing strategy to ensure reliability and quality across all microservices. This guide covers all testing approaches, tools, and procedures.

---

## Testing Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    Testing Pyramid                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│                      ┌─────────┐                                │
│                     │  E2E    │  ← Full workflow tests         │
│                    │ Tests   │                                 │
│                   └─────────┘                                   │
│                ┌───────────────┐                                │
│               │  Integration  │  ← Service interaction tests  │
│              │    Tests       │                                 │
│             └─────────────────┘                                │
│          ┌───────────────────────┐                               │
│         │    Backend Services    │  ← Unit & API tests          │
│        │       (PHPUnit)         │                               │
│       └─────────────────────────┘                               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Testing Types

### 1. Unit Tests (PHPUnit)

Each service includes PHPUnit tests for individual components.

**Location**: `{service}/tests/`

**Running Unit Tests**:

```bash
# Run all service tests
make test

# Run specific service tests
cd services/candidate-service
./vendor/bin/phpunit

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

**Example Test Structure**:
```
tests/
├── Feature/          # Feature tests (HTTP requests)
│   └── ExampleTest.php
└── Unit/             # Unit tests
    └── ExampleTest.php
```

### 2. API Endpoint Tests

Tests individual API endpoints across all services.

**Script**: [`scripts/test-api-endpoints.sh`](../scripts/test-api-endpoints.sh)

**Purpose**: Validates all REST API endpoints work correctly.

**Running**:

```bash
# Run API endpoint tests
bash scripts/test-api-endpoints.sh

# Or use Makefile
make test-api
```

**Tests Cover**:
- Authentication endpoints (`/api/auth/*`)
- Candidate CRUD operations
- Vacancy management
- Matching operations
- Interview scheduling
- Offer management
- Admin configurations

### 3. Backend Service Tests

Tests individual microservices for health and functionality.

**Script**: [`scripts/test-backend-services.sh`](../scripts/test-backend-services.sh)

**Purpose**: Verify each microservice is running and responding.

**Running**:

```bash
bash scripts/test-backend-services.sh
```

**Service Health Checks**:
| Service | Port | Endpoint |
|---------|------|----------|
| API Gateway | 8080 | `/api/health` |
| Auth Service | 8081 | `/api/health` |
| Candidate Service | 8082 | `/api/health` |
| Vacancy Service | 8083 | `/api/health` |
| AI Service | 8084 | `/api/health` |
| Matching Service | 8085 | `/api/health` |
| Interview Service | 8086 | `/api/health` |
| Offer Service | 8087 | `/api/health` |
| Onboarding Service | 8088 | `/api/health` |
| Reporting Service | 8089 | `/api/health` |
| Admin Service | 8090 | `/api/health` |
| Notification Service | 8091 | `/api/health` |
| Document Parser | 8095 | `/api/health` |

### 4. Integration Tests

Tests complete workflows across multiple services.

**Script**: [`scripts/test-integration.sh`](../scripts/test-integration.sh)

**Purpose**: Validates end-to-end data flows between services.

**Running**:

```bash
bash scripts/test-integration.sh
```

**Workflows Tested**:
1. User authentication flow
2. Candidate creation and CV parsing
3. Vacancy creation and matching
4. Interview scheduling process
5. Offer generation and sending

### 5. End-to-End (E2E) Tests

Complete platform testing from frontend to backend.

**Script**: [`scripts/test-e2e.sh`](../scripts/test-e2e.sh)

**Purpose**: Full workflow validation from user action to database persistence.

**Running**:

```bash
# Full E2E test suite
bash scripts/test-e2e.sh

# With custom API URL
PUBLIC_API_URL=https://api.example.com bash scripts/test-e2e.sh

# With public domain
PUBLIC_DOMAIN=example.com bash scripts/test-e2e.sh
```

**E2E Workflows**:

#### Workflow 1: Authentication
```
1. User login → Auth Service validates credentials
2. Returns JWT token
3. Token stored for subsequent requests
```

#### Workflow 2: Candidate Management
```
1. Create candidate → Candidate Service
2. Upload CV → Document Parser Service
3. AI parses CV → AI Service
4. Candidate profile updated with parsed data
```

#### Workflow 3: Vacancy Matching
```
1. Create vacancy → Vacancy Service
2. Match candidates → Matching Service
3. AI scores candidates → AI Service
4. Display ranked candidate list
```

#### Workflow 4: Interview Process
```
1. Schedule interview → Interview Service
2. Send notifications → Notification Service
3. Update candidate status
4. Record interview feedback
```

#### Workflow 5: Offer Management
```
1. Generate offer → Offer Service
2. Send offer to candidate
3. Track offer status (pending/accepted/rejected)
4. Initiate onboarding if accepted
```

### 6. Security Tests

Tests for security vulnerabilities and best practices.

**Script**: [`scripts/test-security.sh`](../scripts/test-security.sh)

**Purpose**: Identify potential security issues.

**Running**:

```bash
bash scripts/test-security.sh
```

**Security Checks**:
- SQL injection prevention
- XSS vulnerability scanning
- CSRF token validation
- Authentication bypass attempts
- Authorization checks
- Input validation
- API rate limiting

## Test Data

### Seed Data

The platform includes seed data for testing:

```bash
# Seed database with test data
make seed

# Or manually
docker-compose exec candidate-service php artisan db:seed
```

### Test Users

**Default Credentials**:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| Recruiter | recruiter@example.com | password |
| HR Manager | hr@example.com | password |

### Sample CVs

Location: `test_resume.pdf`

Use for CV parsing tests:
```bash
# Upload test CV
curl -X POST http://localhost:8080/api/candidates \
  -H "Authorization: Bearer $TOKEN" \
  -F "cv=@test_resume.pdf"
```

## Continuous Integration

### Running All Tests

```bash
# Full test suite
make test-all

# Or sequentially
make test-api && make test-backend && make test-integration && make test-e2e
```

### Test Reports

```bash
# Generate HTML coverage report
make coverage

# View test logs
docker-compose logs --tail=100 test-runner
```

## Troubleshooting

### Tests Failing

1. **Check services are running**:
   ```bash
   docker-compose ps
   ```

2. **Verify database connectivity**:
   ```bash
   docker-compose exec candidate-service php artisan migrate:status
   ```

3. **Check environment variables**:
   ```bash
   cat .env | grep -v PASSWORD
   ```

4. **Review logs**:
   ```bash
   docker-compose logs -f candidate-service
   ```

### Common Issues

| Issue | Solution |
|-------|----------|
| Auth tests failing | Check `SANCTUM_STATEFUL_DOMAINS` in .env |
| Database connection errors | Run `make dbml-init` |
| CV parsing timeout | Increase `AI_GENERATION_TIMEOUT` |
| Service unreachable | Run `make up` to start all services |

### Debug Mode

Enable detailed test output:

```bash
# PHPUNIT verbose
./vendor/bin/phpunit --verbose --debug

# Bash scripts verbose
bash -x scripts/test-e2e.sh
```

## Best Practices

### Writing New Tests

1. **Follow naming convention**: `Test{Feature}.php`
2. **Use descriptive test names**: `testCandidateCanUploadCV()`
3. **Arrange-Act-Assert pattern**:
   ```php
   public function test_candidate_creation()
   {
       // Arrange
       $candidateData = [...];
       
       // Act
       $response = $this->postJson('/api/candidates', $candidateData);
       
       // Assert
       $response->assertStatus(201);
   }
   ```
4. **Mock external services** when possible
5. **Clean up test data** in `tearDown()` method

### Test Data Management

- Use factories for consistent test data
- Clean database between tests
- Use transactions for rollback
- Avoid hardcoded IDs

---

## Additional Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Testing](https://laravel.com/docs/testing)
- [API Gateway](../gateway/api-gateway/README.md)
- [Database Schema](../DATABASE.md)
