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

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose
- PHP 8.1+
- Node.js 18+
- Ollama (optional, for local AI)

### Installation

```bash
# Clone repository
git clone <repository-url>
cd candidacy

# Start all services with Docker Compose
docker-compose up -d

# Access the application
# Main Frontend (HR/Recruiter): http://localhost:3001
# Applicant Portal: http://localhost:5173
# API Gateway: http://localhost:8080
# Grafana (Monitoring): http://localhost:3050
```

### Development Setup

```bash
# Start specific services
docker-compose up auth-service candidate-service

# View logs
docker-compose logs -f service-name

# Run migrations
docker-compose exec service-name php artisan migrate

# Stop all services
docker-compose down
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
│   └── notification-service/
├── gateway/               # API Gateway
│   └── api-gateway/
├── frontend/              # Frontend application
│   └── web-app/
├── shared/                # Shared libraries
├── infrastructure/        # Infrastructure configs
└── docker-compose.yml
```

## 🔧 Technology Stack

- **Backend**: Laravel/Lumen (PHP)
- **Frontend**: Vue.js 3 with Vite
- **Database**: MySQL/MariaDB (Database-per-service)
- **Message Broker**: Redis Pub/Sub
- **AI**: Ollama (local) / OpenRouter (cloud)
- **AI Models**: gemma2:2b for matching and questionnaires
- **Markdown Rendering**: marked.js for AI-generated content
- **Logging**: Loki + Promtail + Grafana
- **Containerization**: Docker

## 🤖 AI Features

- **CV Parsing**: Extract skills, experience, education from uploaded CVs (PDF/DOCX support)
- **Job Description Generation**: AI-powered JD creation from basic inputs
- **Intelligent Matching**: Semantic matching between candidates and vacancies with scoring
- **Match Analysis**: Detailed markdown-formatted analysis rendered as HTML
- **Questionnaire Generation**: AI-generated interview questions based on job requirements
- **Configurable AI Provider**: Switch between Ollama (local) and OpenRouter (cloud)
- **Model Selection**: Uses gemma2:2b for matching and questionnaires (configurable via admin panel)
- **External Ollama Support**: Can connect to external Ollama instances

## 👥 User Roles

- **Admin**: Full system access, configuration management
- **HR Manager**: Manage vacancies, view all data, configure onboarding
- **Recruiter**: Manage candidates, schedule interviews, view matches
- **Interviewer**: View assigned interviews, submit feedback
- **Viewer**: Read-only access

## 📊 Key Features

- ✅ AI-powered CV parsing and skill extraction
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
- ✅ Multi-tenant ready architecture

## 🔐 Security

- JWT/Session-based authentication
- Role-based permissions
- API rate limiting
- Activity logging and audit trails
- Configurable password policies

## 📈 Scalability

- Microservices architecture for independent scaling
- Event-driven communication
- Database per service pattern
- Horizontal scaling ready
- Caching layer for performance

## 🧪 Testing

```bash
# Run tests for a service
cd services/service-name
php artisan test

# Run all tests
./scripts/run-all-tests.sh
```

## 📚 API Documentation

Each service exposes OpenAPI/Swagger documentation:
- Auth: http://localhost:8081/api/documentation
- Candidate: http://localhost:8082/api/documentation
- etc.

## 🛠️ Administration

Access the admin panel at http://localhost:3001/admin to configure:

**General Settings:**
- Application name and company name
- Contact email
- Candidate portal base URL
- Login page background image URL

**AI Settings:**
- AI provider (Ollama/OpenRouter)
- Ollama URL (supports external instances)
- Matching model (default: gemma2:2b)
- Questionnaire generation model
- Match threshold percentage

**System Settings:**
- Maximum upload file size
- Module enable/disable toggles
- Email configuration
- System maintenance mode

## 📝 License

[Your License Here]

## 🤝 Contributing

[Contributing guidelines]
