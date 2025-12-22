# Candidate Service

Candidate management and CV processing service for the Candidacy recruitment platform.

## Purpose

The Candidate Service manages all candidate-related operations including profile management, CV upload and parsing, experience tracking, education history, and skills management. It integrates with the AI Service for intelligent CV parsing.

## Key Features

- **Candidate CRUD**: Create, read, update, delete candidate profiles
- **CV Upload & Parsing**: Upload resumes (PDF/DOCX) and extract structured data using AI
- **Experience Tracking**: Manage work experience history
- **Education Management**: Track educational background
- **Skills Extraction**: AI-powered skill identification from CVs
- **File Storage**: Secure resume file storage
- **Event Publishing**: Publishes events for candidate lifecycle

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL (candidacy_candidate)
- **Port**: 8082 (container internal: 8080)
- **File Storage**: Local storage in `/storage/uploads`
- **Queue**: Redis for async CV processing
- **Dependencies**: AI Service for CV parsing

## API Endpoints

### List Candidates
```
GET /api/candidates
```
Query parameters: `page`, `per_page`, `search`, `status`

### Get Candidate
```
GET /api/candidates/{id}
```
Returns candidate with experience, education, and skills.

### Create Candidate
```
POST /api/candidates
```
**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "status": "active"
}
```

### Update Candidate
```
PUT /api/candidates/{id}
```

### Delete Candidate
```
DELETE /api/candidates/{id}
```

### Upload CV
```
POST /api/candidates/{id}/upload-cv
```
**Request:** Multipart form data with `cv` file (PDF or DOCX)

**Process:**
1. Validates file type and size
2. Stores file in `/storage/uploads`
3. Sends to AI Service for parsing
4. Extracts and saves: skills, experience, education
5. Updates candidate profile with parsed data

### Get Candidate Experience
```
GET /api/candidates/{id}/experience
```

### Add Experience
```
POST /api/candidates/{id}/experience
```
**Request Body:**
```json
{
  "company": "Tech Corp",
  "position": "Software Engineer",
  "start_date": "2020-01-01",
  "end_date": "2023-12-31",
  "description": "Developed web applications..."
}
```

### Get Candidate Education
```
GET /api/candidates/{id}/education
```

### Add Education
```
POST /api/candidates/{id}/education
```
**Request Body:**
```json
{
  "institution": "University of Technology",
  "degree": "Bachelor of Science",
  "field_of_study": "Computer Science",
  "start_date": "2016-09-01",
  "end_date": "2020-06-30"
}
```

## Database Schema

### Candidates Table
- `id` - Primary key
- `first_name` - First name
- `last_name` - Last name
- `email` - Email address (unique)
- `phone` - Phone number
- `status` - Status (active, inactive, hired)
- `cv_path` - Path to uploaded CV file
- `created_at` - Timestamp
- `updated_at` - Timestamp

### Experience Table
- `id` - Primary key
- `candidate_id` - Foreign key to candidates
- `company` - Company name
- `position` - Job title
- `start_date` - Start date
- `end_date` - End date (nullable for current)
- `description` - Job description

### Education Table
- `id` - Primary key
- `candidate_id` - Foreign key to candidates
- `institution` - School/university name
- `degree` - Degree type
- `field_of_study` - Major/field
- `start_date` - Start date
- `end_date` - End date

### Skills Table
- `id` - Primary key
- `candidate_id` - Foreign key to candidates
- `name` - Skill name
- `proficiency` - Proficiency level (beginner, intermediate, advanced, expert)

## Events Published

### CandidateCreated
Published when a new candidate is created.
```json
{
  "candidate_id": 123,
  "email": "john@example.com",
  "created_at": "2024-01-01T00:00:00Z"
}
```

### CVUploaded
Published when a CV is successfully uploaded and parsed.
```json
{
  "candidate_id": 123,
  "cv_path": "/storage/uploads/cv_123.pdf",
  "parsed_data": {...}
}
```

## Setup

### Run Migrations
```bash
docker-compose exec candidate-service php artisan migrate
```

### Create Upload Directory
```bash
docker-compose exec candidate-service mkdir -p storage/uploads
docker-compose exec candidate-service chmod 775 storage/uploads
```

## AI Integration

The service integrates with AI Service for CV parsing:

```php
$response = Http::post('http://ai-service:8080/api/parse-cv', [
    'file' => base64_encode(file_get_contents($cvPath))
]);

$parsedData = $response->json();
// Extract skills, experience, education from parsed data
```

## Development

### View Routes
```bash
docker-compose exec candidate-service php artisan route:list
```

### Process Queue Jobs
```bash
docker-compose exec candidate-service php artisan queue:work
```

### Run Tests
```bash
docker-compose exec candidate-service php artisan test
```

## Environment Variables

- `DB_HOST` - MySQL host (default: mysql)
- `DB_DATABASE` - Database name (candidacy_candidate)
- `REDIS_HOST` - Redis host for queues
- `AI_SERVICE_URL` - AI Service URL (http://ai-service:8080)
- `QUEUE_CONNECTION` - Queue driver (redis)

## File Upload Configuration

- **Supported Formats**: PDF, DOCX
- **Max File Size**: Configured via admin service (default: 10MB)
- **Storage Path**: `/storage/uploads`
- **Naming Convention**: `cv_{candidate_id}_{timestamp}.{extension}`

## Integration

This service is consumed by:
- **Matching Service**: Retrieves candidate data for matching
- **Interview Service**: Gets candidate info for interviews
- **Frontend**: Candidate management UI
- **API Gateway**: Routes `/api/candidates/*` requests

## Notes

- CV parsing is asynchronous via queue jobs
- Large CV files may take time to process
- Skills are automatically extracted and deduplicated
- Experience and education can be manually added or auto-extracted
- Candidate email must be unique across the system
