# Candidacy Microservices - Quick Start Guide

## Prerequisites

- Docker & Docker Compose
- PHP 8.2+
- Composer
- Node.js 18+
- Git

## Initial Setup

### 1. Clone and Setup

```bash
# Navigate to project directory
cd /home/steelburn/Development/candidacy

# Make setup script executable
chmod +x scripts/setup-services.sh

# Run setup script to create all services
./scripts/setup-services.sh
```

This will create:
- 11 microservices using Laravel
- API Gateway
- Vue.js frontend

### 2. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit .env with your configuration
# Especially configure:
# - AI_PROVIDER (ollama or openrouter)
# - OLLAMA_URL if using local Ollama
# - OPENROUTER_API_KEY if using OpenRouter
```

### 3. Start Services

```bash
# Start all services with Docker Compose
docker-compose up -d

# View logs
docker-compose logs -f

# Check running services
docker-compose ps
```

### 4. Install Ollama (Optional - for local AI)

```bash
# Pull Ollama image and start
docker-compose exec ollama ollama pull gemma2:2b

# Or use other models like mistral or llama2
docker-compose exec ollama ollama pull mistral
```

### 5. Run Migrations

```bash
# Run migrations for each service
docker-compose exec auth-service php artisan migrate
docker-compose exec candidate-service php artisan migrate
docker-compose exec vacancy-service php artisan migrate
docker-compose exec matching-service php artisan migrate
docker-compose exec interview-service php artisan migrate
docker-compose exec offer-service php artisan migrate
docker-compose exec onboarding-service php artisan migrate
docker-compose exec reporting-service php artisan migrate
docker-compose exec admin-service php artisan migrate
docker-compose exec notification-service php artisan migrate
```

Or use the migration script:
```bash
chmod +x scripts/run-migrations.sh
./scripts/run-migrations.sh
```

### 6. Seed Initial Data

```bash
# Seed admin user and roles
docker-compose exec auth-service php artisan db:seed

# Seed admin settings (AI config, portal URLs, etc.)
docker-compose exec admin-service php artisan db:seed
```

### 7. Access the Application

- **Main Frontend (HR/Recruiter)**: http://localhost:3001
- **Applicant Portal**: http://localhost:5173
- **API Gateway**: http://localhost:8080
- **Grafana (Monitoring)**: http://localhost:3050 (admin/admin)
- **Individual Services**:
  - Auth: http://localhost:8081
  - Candidate: http://localhost:8082
  - Vacancy: http://localhost:8083
  - AI: http://localhost:8084
  - Matching: http://localhost:8085
  - Interview: http://localhost:8086
  - Offer: http://localhost:8087
  - Onboarding: http://localhost:8088
  - Reporting: http://localhost:8089
  - Admin: http://localhost:8090
  - Notification: http://localhost:8091

## Development Workflow

### Running Individual Services

```bash
# Start specific services only
docker-compose up auth-service candidate-service api-gateway frontend

# Stop services
docker-compose down

# Rebuild after code changes
docker-compose up --build
```

### Viewing Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f auth-service

# Last 100 lines
docker-compose logs --tail=100 candidate-service
```

### Running Commands in Services

```bash
# Access service shell
docker-compose exec auth-service bash

# Run artisan commands
docker-compose exec auth-service php artisan tinker
docker-compose exec auth-service php artisan route:list

# Clear cache
docker-compose exec auth-service php artisan cache:clear
```

## Service Architecture

### Service Communication

Services communicate via:
1. **HTTP REST APIs**: Synchronous requests using ServiceClient
2. **Redis Pub/Sub**: Asynchronous events using EventPublisher

### Event Flow Example

```
CandidateService → publishes "CandidateCreated" event
     ↓
Redis Pub/Sub
     ↓
MatchingService → subscribes and triggers matching algorithm
```

### Database Strategy

Each service has its own database:
- candidacy_auth
- candidacy_candidate
- candidacy_vacancy
- etc.

This ensures loose coupling and independent scaling.

## Testing

### Unit Tests

```bash
# Run tests for a service
docker-compose exec auth-service php artisan test

# With coverage
docker-compose exec auth-service php artisan test --coverage
```

### Integration Tests

```bash
# Run integration test suite
./scripts/run-integration-tests.sh
```

## Troubleshooting

### Services not starting

```bash
# Check logs
docker-compose logs

# Rebuild containers
docker-compose down
docker-compose up --build

# Clear volumes
docker-compose down -v
```

### Database connection issues

```bash
# Check MySQL is running
docker-compose ps mysql

# Check databases exist
docker-compose exec mysql mysql -uroot -proot -e "SHOW DATABASES;"
```

### Ollama not responding

```bash
# Check Ollama status
docker-compose exec ollama ollama list

# Pull model if missing
docker-compose exec ollama ollama pull mistral
```

## Default Credentials

- **Admin User**: admin@candidacy.com
- **Password**: password123
- **Grafana**: admin / admin

(Change these in production!)

## Admin Panel Configuration

After logging in, access the Admin panel at http://localhost:3001/admin to configure:

**General Settings:**
- **Login Background Image**: Set a custom background URL for the login page
- **Candidate Portal URL**: Configure the base URL for candidate portal links (default: http://localhost:5173)
- **Company Name**: Customize company branding
- **Contact Email**: Set HR contact email

**AI Settings:**
- **AI Provider**: Choose between Ollama (local) or OpenRouter (cloud)
- **Ollama URL**: Configure external Ollama instance (default: http://192.168.88.120:11434)
- **Matching Model**: Set AI model for candidate matching (default: gemma2:2b)
- **Questionnaire Model**: Set AI model for question generation (default: gemma2:2b)
- **Match Threshold**: Set minimum match percentage (default: 70)

## Production Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for production deployment instructions using Kubernetes.

## API Documentation

Each service exposes Swagger/OpenAPI documentation at:
`http://service-url/api/documentation`

## Support

For issues, please check:
1. Service logs
2. Database connectivity
3. Redis connectivity
4. AI service configuration

## License

[Your License]
