# AI-Powered Candidate Tracking System

A comprehensive recruitment management system built with microservices architecture, featuring AI-powered CV parsing, intelligent candidate matching, and complete recruitment lifecycle tracking.

## 🏗️ Architecture

**Microservices Monorepo** with 11 independent services:

- **Auth Service** - User authentication and authorization
- **Candidate Service** - Candidate management and CV processing
- **Vacancy Service** - Job posting management
- **AI Service** - Centralized AI operations (Ollama/OpenRouter)
- **Matching Service** - AI-powered candidate-vacancy matching
- **Interview Service** - Interview scheduling and tracking
- **Offer Service** - Job offer management
- **Onboarding Service** - New hire onboarding workflow
- **Reporting Service** - Analytics and metrics
- **Admin Service** - System administration
- **Notification Service** - Email and notifications
- **Document Parser Service** - Dedicated asynchronous PDF/DOCX text extraction

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose
- PHP 8.2+
- Node.js 18+
- Ollama (optional, for local AI)

### Installation

```bash
# Clone repository
git clone <repository-url>
cd candidacy

# Initialize databases from DBML (Recommended)
make dbml-init

# Start all services with Docker Compose
docker-compose up -d

# Access the application
# Main Frontend (HR/Recruiter): http://localhost:3001
# Applicant Portal: http://localhost:5173
# API Gateway: http://localhost:8080
# Grafana (Monitoring): http://localhost:3050
```

### Development Setup (DBML-First)

The Candidacy application uses **Database-as-Code** with DBML (Database Markup Language).

```bash
# Validate DBML syntax
make dbml-validate

# Generate SQL from DBML
make dbml-sql

# Initialize databases from DBML
make dbml-init

# Reset databases from DBML (Drops data!)
make dbml-reset

# View logs
make logs
```

## 📁 Project Structure

```
candidacy/
├── services/              # Microservices
│   ├── auth-service/
│   ├── candidate-service/
│   ├── vacancy-service/
│   ├── ai-service/
│   ├── matching-service/
│   ├── interview-service/
│   ├── offer-service/
│   ├── onboarding-service/
│   ├── reporting-service/
│   ├── admin-service/
│   ├── notification-service/
│   └── document-parser-service/
├── gateway/               # API Gateway
│   └── api-gateway/
├── frontend/              # Frontend applications
│   ├── web-app/           # Main HR/Recruiter dashboard
│   └── applicant-web-app/ # Candidate self-service portal
├── shared/                # Shared libraries
├── infrastructure/        # Infrastructure configs
├── schema.dbml            # Single source of truth for database schema
└── docker-compose.yml
```

## 🔧 Technology Stack

- **Backend**: Laravel 10 (PHP)
- **Frontend**: Vue.js 3 with Vite
- **Database**: MySQL/MariaDB (Managed via DBML)
- **Message Broker**: Redis Pub/Sub
- **AI**: Ollama (local) / OpenRouter (cloud)
- **AI Models**: gemma2:2b (matching & questionnaires), llama3.2 (CV parsing)
- **Markdown Rendering**: md-editor-v3 for AI-generated content
- **Logging**: Loki + Promtail + Grafana
- **Containerization**: Docker

## 🤖 AI Features

- **Advanced Document Parsing**: IBM Granite Docling (258M VLM) for structure-preserving PDF parsing
  - Preserves tables, lists, sections, and formatting
  - DocTags markup output for better downstream processing
  - 85-95% accuracy for resume parsing (vs 60-70% with basic parser)
  - Automatic fallback to basic parser if unavailable
  - See [Document Parser Service](services/document-parser-service/DOCLING_CONFIG.md) for configuration
- **CV Analysis**: Extract skills, experience, education from parsed text
- **Job Description Generation**: AI-powered JD creation from basic inputs
- **Intelligent Matching**: Semantic matching between candidates and vacancies with scoring
- **Match Analysis**: Detailed markdown-formatted analysis rendered as HTML
- **Questionnaire Generation**: AI-generated interview questions based on job requirements
- **Configurable AI Provider**: Switch between Ollama (local) and OpenRouter (cloud)
- **Model Selection**: Configurable via admin panel per task type

## 👥 User Roles

- **Admin**: Full system access, documentation, service health, settings
- **HR Manager**: Manage vacancies, view all data, configure onboarding
- **Recruiter**: Manage candidates, schedule interviews, view matches
- **Interviewer**: View assigned interviews, submit feedback
- **Viewer**: Read-only access

## 📊 Key Features

- ✅ DBML-based Database-as-Code schema management
- ✅ AI-powered CV parsing with dedicated parsing service
- ✅ Intelligent candidate-vacancy matching with detailed analysis
- ✅ Complete interview management and scheduling
- ✅ Job offer tracking and acceptance workflow
- ✅ Automated onboarding with customizable checklists
- ✅ Comprehensive reporting and analytics
- ✅ Role-based access control (Admin, HR Manager, Recruiter, Interviewer, Viewer)
- ✅ Configurable login page background images
- ✅ Customizable candidate portal URL
- ✅ Markdown rendering for AI-generated content
- ✅ Centralized logging and monitoring (Loki/Grafana)
- ✅ Candidate self-service portal
- ✅ Module-based configuration via admin panel

## 🔐 Security

- **JWT-based authentication** (tymon/jwt-auth)
- **Authentication Guard**: `auth:api` for all protected routes
- **Shared Security Middleware**: Standardized security headers and protection across all services
- **Proper 401 JSON responses** for all unauthenticated requests
- Role-based permissions
- API rate limiting
- Activity logging and audit trails

## 📈 Scalability

- Microservices architecture with 12 independent services
- Asynchronous document parsing for high-volume uploads
- Event-driven communication via Redis Pub/Sub
- Database per service pattern
- Horizontal scaling ready via Docker Compose/Kubernetes

## 🧪 Testing

### Running Tests

```bash
# Run tests for a service
make test-service S=candidate-service

# Run all tests
make test
```

### Test Data Generation

All major models have factories for generating realistic test data.

### Health Checks

All services expose health check endpoints:

```bash
# Check service health
curl http://localhost:8080/api/system-health
```

## 📊 Quality & Maintenance

### Code Quality
- ✅ DBML Schema consistency across all services
- ✅ Standardized health check endpoints
- ✅ Complete factory coverage for testing
- ✅ Shared middleware for security and headers
- ✅ Clean codebase with standardized controller patterns

### Recent Improvements (Dec 2024)
- ✅ **Integrated IBM Granite Docling for advanced document processing**
  - 258M parameter vision-language model for PDF parsing
  - Structure preservation (tables, lists, sections)
  - 85-95% accuracy for resume parsing
  - Configurable performance tuning (timeout, resolution)
  - Comprehensive monitoring and metrics
- ✅ **Completed DBML-first database management transition**
  - Removed all legacy Laravel migration files
  - `schema.dbml` is now the single source of truth
  - Automated SQL generation and database initialization
- ✅ Created dedicated Document Parser Service
- ✅ Migrated to JWT-based authentication with `auth:api`
- ✅ Fixed shared namespace configuration
- ✅ Standardized API responses across all services
- ✅ Centralized system health monitoring via API Gateway

### Monitoring
- **Grafana Dashboard**: http://localhost:3050
- **Loki Logs**: Centralized logging for all 12 services
- **Health Endpoints**: `/api/health` on all services
- **API Gateway Metrics**: Unified view of service status

## 📚 Documentation

- **[QUICKSTART.md](QUICKSTART.md)** - Fast track setup guide
- **[DATABASE.md](DATABASE.md)** - DBML schema documentation and workflow
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Detailed system architecture
- **[SETUP_PROGRESS.md](SETUP_PROGRESS.md)** - Tracking setup and features
- **[CHANGELOG.md](CHANGELOG.md)** - History of all major changes
- **API Documentation** - Available at each service `/api/documentation`

## 🔧 Troubleshooting

### Common Issues

**Authentication Issues**
- Ensure you're using JWT tokens (not Sanctum)
- Login endpoint: `POST /api/auth/login`
- Include token in requests: `Authorization: Bearer {token}`
- Token expires after 60 minutes

**Health Check Failures**
- All services should report as "healthy" at `/api/health`
- Redis is optional (services work without it)
- Check database connectivity if health checks fail

**Service Communication**
- All inter-service communication goes through API Gateway (port 8080)
- Services use internal Docker network names (e.g., `auth-service:8080`)
- Gateway maps routes to appropriate services

**AI Service Issues**
- Configure AI provider in Admin panel
- For Ollama: ensure Ollama is running and accessible
- For OpenRouter: provide valid API key
- Default model: `gemma2:2b`

## 📝 License

[Your License Here]

## 🤝 Contributing

[Contributing guidelines]
