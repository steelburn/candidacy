# Vacancy Service

Job vacancy management service for the Candidacy recruitment platform.

## Purpose

The Vacancy Service manages job postings and vacancy lifecycle. It provides CRUD operations for vacancies and integrates with the AI Service for intelligent job description generation.

## Key Features

- **Vacancy CRUD**: Create, read, update, delete job postings
- **AI-Powered JD Generation**: Generate job descriptions from basic inputs
- **Status Management**: Track vacancy status (open, closed, on-hold)
- **Requirements Tracking**: Manage required skills and qualifications
- **Event Publishing**: Publishes events for vacancy lifecycle

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL (candidacy_vacancy)
- **Port**: 8083 (container internal: 8080)
- **Dependencies**: AI Service for JD generation, Redis for events

## API Endpoints

### List Vacancies
```
GET /api/vacancies
```
Query parameters: `page`, `per_page`, `status`, `search`

### Get Vacancy
```
GET /api/vacancies/{id}
```
Returns vacancy with full details including requirements.

### Create Vacancy
```
POST /api/vacancies
```
**Request Body:**
```json
{
  "title": "Senior Software Engineer",
  "department": "Engineering",
  "location": "Remote",
  "employment_type": "full-time",
  "experience_required": "5+ years",
  "salary_range": "$100,000 - $150,000",
  "description": "We are looking for...",
  "requirements": ["Python", "Django", "PostgreSQL"],
  "status": "open"
}
```

### Update Vacancy
```
PUT /api/vacancies/{id}
```

### Delete Vacancy
```
DELETE /api/vacancies/{id}
```

### Generate Job Description
```
POST /api/vacancies/{id}/generate-description
```
Uses AI to generate a comprehensive job description based on title, department, and requirements.

**Request Body:**
```json
{
  "title": "Senior Software Engineer",
  "department": "Engineering",
  "requirements": ["Python", "Django", "5+ years experience"]
}
```

**Response:**
```json
{
  "description": "AI-generated job description in markdown format..."
}
```

### Close Vacancy
```
POST /api/vacancies/{id}/close
```
Marks vacancy as closed.

### Reopen Vacancy
```
POST /api/vacancies/{id}/reopen
```
Reopens a closed vacancy.

## Database Schema

### Vacancies Table
- `id` - Primary key
- `title` - Job title
- `department` - Department name
- `location` - Job location
- `employment_type` - Type (full-time, part-time, contract, internship)
- `experience_required` - Experience requirement
- `salary_range` - Salary range (optional)
- `description` - Full job description (markdown supported)
- `requirements` - JSON array of required skills
- `status` - Status (open, closed, on-hold)
- `created_at` - Timestamp
- `updated_at` - Timestamp
- `closed_at` - Closure timestamp (nullable)

## Events Published

### VacancyCreated
Published when a new vacancy is created.
```json
{
  "vacancy_id": 456,
  "title": "Senior Software Engineer",
  "status": "open",
  "created_at": "2024-01-01T00:00:00Z"
}
```

### VacancyUpdated
Published when vacancy details are updated.

### VacancyClosed
Published when a vacancy is closed.

## Setup

### Run Migrations
```bash
docker-compose exec vacancy-service php artisan migrate
```

### Seed Sample Vacancies (Optional)
```bash
docker-compose exec vacancy-service php artisan db:seed
```

## AI Integration

The service uses AI Service for job description generation:

```php
$response = Http::post('http://ai-service:8080/api/generate-jd', [
    'title' => $title,
    'department' => $department,
    'requirements' => $requirements
]);

$generatedDescription = $response->json()['description'];
```

The generated description is in markdown format and can be:
- Used as-is
- Edited by HR before publishing
- Regenerated with different parameters

## Development

### View Routes
```bash
docker-compose exec vacancy-service php artisan route:list
```

### Run Tests
```bash
docker-compose exec vacancy-service php artisan test
```

## Environment Variables

- `DB_HOST` - MySQL host (default: mysql)
- `DB_DATABASE` - Database name (candidacy_vacancy)
- `REDIS_HOST` - Redis host for events
- `AI_SERVICE_URL` - AI Service URL (http://ai-service:8080)

## Employment Types

Supported employment types:
- `full-time` - Full-time position
- `part-time` - Part-time position
- `contract` - Contract/freelance
- `internship` - Internship/trainee

## Vacancy Status

- `open` - Actively recruiting
- `closed` - Position filled or cancelled
- `on-hold` - Temporarily paused

## Integration

This service is consumed by:
- **Matching Service**: Retrieves vacancy data for candidate matching
- **Interview Service**: Links interviews to vacancies
- **Offer Service**: Links offers to vacancies
- **Frontend**: Vacancy management UI
- **API Gateway**: Routes `/api/vacancies/*` requests

## Best Practices

- Use AI-generated descriptions as a starting point, then customize
- Keep requirements list specific and realistic
- Update status promptly when position is filled
- Include salary range when possible for better candidate matching
- Use markdown formatting in descriptions for better readability

## Notes

- Job descriptions support markdown formatting
- Requirements are stored as JSON array for flexible querying
- AI-generated descriptions are optimized for clarity and completeness
- Closed vacancies are retained for historical reporting
- Vacancies can be reopened if needed
