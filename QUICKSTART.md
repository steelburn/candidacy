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

This will create/configure:
- 12 microservices using Laravel
- API Gateway
- Vue.js HR/Recruiter dashboard
- Vue.js Applicant portal

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

### 3. Initialize Databases (DBML-First)

The Candidacy system uses **Database-as-Code**. Initialize your databases directly from the DBML schema:

```bash
# Install dependencies for DBML tools
npm install

# Initialize all databases and apply schema
make dbml-init
```

### 4. Start Services

```bash
# Start all services with Docker Compose
docker-compose up -d

# View logs for all services
make logs

# Check running services
make status
```

### 5. Setup AI Models (Optional - for local AI)

```bash
# Pull models for candidate matching and questionnaires
docker-compose exec ollama ollama pull gemma2:2b

# Pull model for CV parsing
docker-compose exec ollama ollama pull llama3.2
```

### 6. Seed Initial Data

```bash
# Seeds admin user, roles, and default settings
make seed
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
  - Document Parser: http://localhost:8092

## Development Workflow

### Database Management (DBML)

Always edit `schema.dbml` to change your database structure:

```bash
# 1. Edit schema.dbml
# 2. Validate and generate SQL
make dbml-sql
# 3. Apply to local databases (drops data!)
make dbml-reset
```

### Managing Services

```bash
# Start specific services
docker-compose up auth-service candidate-service api-gateway

# Rebuild after code changes
docker-compose up -d --build service-name

# Access service shell
make shell S=candidate-service
```

### Viewing Logs

```bash
# Specific service
make logs-candidate

# Combined logs for CV parsing flow
make logs-parse-cv
```

## Service Architecture

### Service Communication

Services communicate via:
1. **HTTP REST APIs**: Synchronous requests (e.g., matching requests AI analysis)
2. **Redis Pub/Sub**: Asynchronous events (e.g., candidate created → trigger match)

### Unified Health Monitoring

Check the status of all services at once:
```bash
curl http://localhost:8080/api/system-health
```

## Testing

```bash
# Run all tests
make test

# Run specific service tests
make test-service S=auth-service
```

## Troubleshooting

### Schema Out of Sync
If you see unexpected database errors, verify your SQL files match the DBML:
```bash
make dbml-check
```

### Service Health
If a service is behaving unexpectedly, check its health endpoint:
`http://localhost:8080/api/system-health`

### AI Processing
Ensure Ollama is running and the models are pulled. You can check Ollama status in the Admin panel.

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
