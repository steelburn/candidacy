# Vacancy Service

Job posting and vacancy management service.

## Overview

- **Port**: 8083
- **Database**: `candidacy_vacancy`
- **Framework**: Laravel 10

## Features

- ✅ Vacancy CRUD operations
- ✅ AI-powered job description generation
- ✅ Interview questionnaire management
- ✅ Work mode selection (On-site, Remote, Hybrid)
- ✅ Salary range tracking
- ✅ Vacancy metrics

## API Endpoints

```http
GET    /api/vacancies               # List vacancies (paginated)
POST   /api/vacancies               # Create vacancy
GET    /api/vacancies/{id}          # Get vacancy details
PUT    /api/vacancies/{id}          # Update vacancy
DELETE /api/vacancies/{id}          # Delete vacancy
POST   /api/vacancies/{id}/generate-description  # AI generate JD
POST   /api/vacancies/{id}/questions  # Add interview question
GET    /api/vacancies/{id}/questions  # Get questions
GET    /api/vacancies/metrics/stats   # Get vacancy statistics
```

## AI Job Description Generation

Generate job descriptions using AI:

```bash
curl -X POST http://localhost:8080/api/vacancies/1/generate-description \
  -H "Authorization: Bearer {token}"
```

Provide keywords and expectations for better AI generation.

## Development

The database schema is managed via DBML. Always edit `schema.dbml` at the root and run `make dbml-sql`.

```bash
# Sync local database
make dbml-init

# Start service
docker-compose up -d vacancy-service
```
