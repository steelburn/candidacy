# Candidate Service

Candidate management, CV processing, and applicant portal service.

## Overview

- **Port**: 8082
- **Database**: `candidacy_candidate`
- **Framework**: Laravel 10

## Features

- ✅ Candidate CRUD operations
- ✅ CV upload and parsing (PDF/DOCX)
- ✅ AI-powered skill extraction
- ✅ Experience and education tracking
- ✅ Candidate portal token generation
- ✅ Bulk CV upload
- ✅ Candidate metrics

## API Endpoints

```http
GET    /api/candidates              # List candidates (paginated)
POST   /api/candidates              # Create candidate
GET    /api/candidates/{id}         # Get candidate details
PUT    /api/candidates/{id}         # Update candidate
DELETE /api/candidates/{id}         # Delete candidate
POST   /api/candidates/parse-cv     # Parse CV with AI
POST   /api/candidates/bulk-upload  # Bulk upload CVs
POST   /api/candidates/{id}/generate-token  # Generate portal token
GET    /api/candidates/metrics/stats # Get candidate statistics
GET    /api/portal/validate-token/{token}   # Validate portal token
POST   /api/portal/submit-answers/{token}   # Submit questionnaire answers
```

## CV Processing

Upload and parse CVs with AI:

```bash
curl -X POST http://localhost:8080/api/candidates/parse-cv \
  -F "cv_file=@resume.pdf"
```

The AI extracts:
- Personal information
- Skills and competencies
- Work experience
- Education history
- Certifications

## Candidate Portal

Generate secure tokens for candidates to access their portal:

```bash
curl -X POST http://localhost:8080/api/candidates/1/generate-token \
  -H "Authorization: Bearer {token}" \
  -d '{"vacancy_id": 5}'
```

## Development

```bash
cd services/candidate-service
composer install
php artisan migrate
php artisan serve --port=8082
```
