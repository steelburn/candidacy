# Candidacy System Architecture

Comprehensive architecture documentation for the AI-powered Candidacy recruitment platform.

## Overview

Candidacy is built using a **microservices architecture** with 11 independent services, two frontend applications, and a central API gateway. The system leverages event-driven communication, database-per-service pattern, and AI integration for intelligent recruitment operations.

## Architecture Diagram

```mermaid
graph TB
    subgraph "Frontend Layer"
        WEB[Main Frontend<br/>Vue 3 - Port 3001]
        APP[Applicant Portal<br/>Vue 3 - Port 5173]
    end
    
    subgraph "Gateway Layer"
        GW[API Gateway<br/>Laravel - Port 8080]
    end
    
    subgraph "Microservices Layer"
        AUTH[Auth Service<br/>Port 8081]
        CAND[Candidate Service<br/>Port 8082]
        VAC[Vacancy Service<br/>Port 8083]
        AI[AI Service<br/>Port 8084]
        MATCH[Matching Service<br/>Port 8085]
        INT[Interview Service<br/>Port 8086]
        OFF[Offer Service<br/>Port 8087]
        ONB[Onboarding Service<br/>Port 8088]
        REP[Reporting Service<br/>Port 8089]
        ADM[Admin Service<br/>Port 8090]
        NOT[Notification Service<br/>Port 8091]
    end
    
    subgraph "Data Layer"
        MYSQL[(MySQL<br/>Database per Service)]
        REDIS[(Redis<br/>Cache & Events)]
    end
    
    subgraph "AI Layer"
        OLLAMA[Ollama<br/>Local AI]
        OPENROUTER[OpenRouter<br/>Cloud AI]
    end
    
    subgraph "Monitoring Layer"
        LOKI[Loki<br/>Log Aggregation]
        PROMTAIL[Promtail<br/>Log Collection]
        GRAFANA[Grafana<br/>Visualization]
    end
    
    WEB --> GW
    APP --> GW
    GW --> AUTH
    GW --> CAND
    GW --> VAC
    GW --> AI
    GW --> MATCH
    GW --> INT
    GW --> OFF
    GW --> ONB
    GW --> REP
    GW --> ADM
    GW --> NOT
    
    AUTH --> MYSQL
    CAND --> MYSQL
    VAC --> MYSQL
    MATCH --> MYSQL
    INT --> MYSQL
    OFF --> MYSQL
    ONB --> MYSQL
    REP --> MYSQL
    ADM --> MYSQL
    NOT --> MYSQL
    
    CAND --> REDIS
    VAC --> REDIS
    MATCH --> REDIS
    NOT --> REDIS
    
    AI --> OLLAMA
    AI --> OPENROUTER
    CAND --> AI
    VAC --> AI
    MATCH --> AI
    
    PROMTAIL --> LOKI
    LOKI --> GRAFANA
```

## Core Principles

### 1. Microservices Architecture
Each service is:
- **Independent**: Can be deployed, scaled, and updated separately
- **Single Responsibility**: Handles one business domain
- **Loosely Coupled**: Minimal dependencies on other services
- **Technology Agnostic**: Can use different tech stacks if needed

### 2. Database Per Service
Each service has its own database:
- `candidacy_auth` - User authentication data
- `candidacy_candidate` - Candidate profiles, CVs, experience
- `candidacy_vacancy` - Job postings and requirements
- `candidacy_matching` - Match records and scores
- `candidacy_interview` - Interview schedules and feedback
- `candidacy_offer` - Job offers and acceptance
- `candidacy_onboarding` - Onboarding tasks and progress
- `candidacy_reporting` - Aggregated metrics
- `candidacy_admin` - System settings
- `candidacy_notification` - Notification history

Benefits:
- Data isolation and security
- Independent scaling
- Schema evolution without affecting other services
- Technology flexibility

### 3. Event-Driven Communication
Services communicate via:
- **Synchronous**: HTTP REST APIs for request-response
- **Asynchronous**: Redis Pub/Sub for events

Example event flow:
```
Candidate uploads CV
  ↓
Candidate Service publishes "CVUploaded" event
  ↓
Matching Service subscribes and triggers matching
  ↓
Notification Service subscribes and sends confirmation email
```

## Service Responsibilities

### Auth Service
- User authentication and authorization
- JWT token generation and validation
- Role-based access control (RBAC)
- User management

### Candidate Service
- Candidate profile management
- CV upload and storage
- Experience and education tracking
- Skills management
- Integrates with AI Service for CV parsing

### Vacancy Service
- Job posting management
- Vacancy lifecycle tracking
- Integrates with AI Service for JD generation

### AI Service
- Centralized AI operations
- CV parsing (extract skills, experience, education)
- Job description generation
- Candidate-vacancy matching
- Interview questionnaire generation
- Supports Ollama (local) and OpenRouter (cloud)

### Matching Service
- AI-powered candidate-vacancy matching
- Match scoring (0-100)
- Detailed analysis generation
- Batch matching operations
- Integrates with AI, Candidate, and Vacancy services

### Interview Service
- Interview scheduling and management
- Interviewer assignment
- Feedback collection
- Status tracking

### Offer Service
- Job offer creation and management
- Acceptance/rejection workflow
- Offer letter generation
- Expiration tracking

### Onboarding Service
- New hire onboarding workflow
- Customizable task checklists
- Progress tracking
- Deadline management

### Reporting Service
- Analytics and metrics aggregation
- Dashboard KPIs
- Recruitment pipeline analytics
- Time-to-hire, conversion rates
- Interviewer performance metrics

### Admin Service
- System configuration management
- Settings API for all services
- AI provider configuration
- Branding and customization settings

### Notification Service
- Email and notification delivery
- Template management
- Delivery tracking
- Queue-based async sending

## Communication Patterns

### 1. Synchronous (HTTP REST)
Used for:
- Request-response operations
- Data retrieval
- Real-time operations

Example:
```
Frontend → API Gateway → Candidate Service
GET /api/candidates/123
← Candidate data
```

### 2. Asynchronous (Redis Pub/Sub)
Used for:
- Event notifications
- Decoupled operations
- Background processing

Example:
```
Offer Service publishes "OfferAccepted" event
  ↓ Redis Pub/Sub
Onboarding Service subscribes → Creates onboarding record
Notification Service subscribes → Sends congratulations email
Vacancy Service subscribes → Marks vacancy as filled
```

### 3. Service-to-Service
Services can call each other directly:
```
Matching Service → Candidate Service (get candidate data)
Matching Service → Vacancy Service (get vacancy data)
Matching Service → AI Service (perform matching)
```

## Data Flow Examples

### CV Upload and Parsing
```
1. User uploads CV via Frontend
   ↓
2. Frontend → API Gateway → Candidate Service
   ↓
3. Candidate Service stores file
   ↓
4. Candidate Service → AI Service (parse CV)
   ↓
5. AI Service → Ollama/OpenRouter (extract data)
   ↓
6. AI Service returns parsed data
   ↓
7. Candidate Service saves skills, experience, education
   ↓
8. Candidate Service publishes "CVUploaded" event
   ↓
9. Matching Service triggers auto-matching
```

### Candidate Matching
```
1. HR creates vacancy via Frontend
   ↓
2. Frontend → API Gateway → Vacancy Service
   ↓
3. Vacancy Service publishes "VacancyCreated" event
   ↓
4. Matching Service subscribes to event
   ↓
5. Matching Service → Candidate Service (get all candidates)
   ↓
6. For each candidate:
   Matching Service → AI Service (calculate match)
   ↓
7. AI Service returns score and analysis
   ↓
8. Matching Service saves match records
   ↓
9. Frontend displays top matches
```

### Offer to Onboarding
```
1. HR creates offer via Frontend
   ↓
2. Offer Service creates offer record
   ↓
3. Notification Service sends offer email
   ↓
4. Candidate accepts offer (via Applicant Portal)
   ↓
5. Offer Service publishes "OfferAccepted" event
   ↓
6. Onboarding Service subscribes to event
   ↓
7. Onboarding Service creates onboarding record
   ↓
8. Onboarding Service applies checklist template
   ↓
9. Notification Service sends welcome email
```

## Scalability

### Horizontal Scaling
Services can be scaled independently:
- **High Load Services**: Candidate, Matching, AI
- **Medium Load**: Vacancy, Interview, Offer
- **Low Load**: Admin, Onboarding

### Vertical Scaling
- AI Service benefits from GPU resources
- Database can be scaled with read replicas
- Redis can be clustered for high availability

### Caching Strategy
- **API Gateway**: Service health, public data
- **Admin Service**: Settings (10 min TTL)
- **Matching Service**: Match results (1 hour TTL)
- **Reporting Service**: Dashboard metrics (5 min TTL)

## Security

### Authentication Flow
```
1. User logs in via Frontend
   ↓
2. Frontend → API Gateway → Auth Service
   ↓
3. Auth Service validates credentials
   ↓
4. Auth Service generates JWT token
   ↓
5. Frontend stores token
   ↓
6. All subsequent requests include token
   ↓
7. API Gateway validates token
   ↓
8. Request forwarded to target service
```

### Security Layers
- **API Gateway**: CORS, rate limiting, token validation
- **Services**: Role-based access control
- **Database**: Encrypted connections, user isolation
- **Files**: Secure storage, access control
- **Logs**: Sensitive data redaction

## Monitoring & Logging

### Centralized Logging
```
All Services → Docker logs
  ↓
Promtail (log collection)
  ↓
Loki (log aggregation)
  ↓
Grafana (visualization)
```

### Metrics Tracked
- Request count per endpoint
- Response times
- Error rates
- Service availability
- Queue lengths
- Database connections

### Health Checks
Each service exposes `/health` endpoint:
- Database connectivity
- Redis connectivity
- External service connectivity (AI)
- Disk space
- Memory usage

## Deployment

### Docker Compose (Development)
```bash
docker-compose up -d
```

All services run in containers with:
- Shared network for inter-service communication
- Volume mounts for development
- Environment variable configuration

### Production (Kubernetes - Planned)
- Each service as a Deployment
- Horizontal Pod Autoscaling
- Service mesh for traffic management
- Persistent volumes for databases
- Ingress for external access

## Technology Stack Summary

| Layer | Technology |
|-------|------------|
| Frontend | Vue 3, Vite, Pinia, Axios |
| Gateway | Laravel 11 |
| Services | Laravel 11 |
| Database | MySQL 8.0 |
| Cache/Events | Redis 7 |
| AI | Ollama, OpenRouter |
| Logging | Loki, Promtail, Grafana |
| Containerization | Docker, Docker Compose |

## Future Enhancements

- **Service Mesh**: Istio for advanced traffic management
- **API Gateway**: Kong or custom solution with more features
- **Message Queue**: RabbitMQ or Kafka for reliable event delivery
- **Service Discovery**: Consul for dynamic service registration
- **Configuration Management**: Vault for secrets management
- **Distributed Tracing**: Jaeger for request tracing
- **WebSockets**: Real-time updates for frontend
- **GraphQL**: Alternative API interface
- **Mobile Apps**: Native iOS/Android applications

## Best Practices

1. **Service Independence**: Each service should be deployable independently
2. **API Versioning**: Use versioned APIs for backward compatibility
3. **Error Handling**: Consistent error responses across services
4. **Logging**: Structured logging with correlation IDs
5. **Testing**: Unit tests for each service, integration tests for flows
6. **Documentation**: Keep API docs up to date
7. **Monitoring**: Set up alerts for critical metrics
8. **Security**: Regular security audits and updates
