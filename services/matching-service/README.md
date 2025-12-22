# Matching Service

AI-powered candidate-vacancy matching service for the Candidacy recruitment platform.

## Purpose

The Matching Service performs intelligent semantic matching between candidates and job vacancies using AI. It analyzes candidate profiles against vacancy requirements, generates match scores, and provides detailed analysis in markdown format.

## Key Features

- **Semantic Matching**: AI-powered candidate-vacancy compatibility analysis
- **Match Scoring**: 0-100 score based on skills, experience, and requirements
- **Detailed Analysis**: Markdown-formatted analysis with strengths and gaps
- **Threshold Filtering**: Configurable minimum match percentage
- **Batch Matching**: Match one candidate against multiple vacancies
- **Match History**: Track all matches for reporting

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL (candidacy_matching)
- **Port**: 8085 (container internal: 8080)
- **Dependencies**: AI Service, Candidate Service, Vacancy Service
- **Markdown Rendering**: Frontend renders analysis as HTML

## API Endpoints

### Get All Matches
```
GET /api/matches
```
Query parameters: `page`, `per_page`, `min_score`

### Get Matches for Candidate
```
GET /api/matches/candidate/{candidate_id}
```
Returns all matches for a specific candidate, sorted by score.

### Get Matches for Vacancy
```
GET /api/matches/vacancy/{vacancy_id}
```
Returns all matching candidates for a vacancy, sorted by score.

### Create Match
```
POST /api/matches
```
Triggers matching between a candidate and vacancy.

**Request Body:**
```json
{
  "candidate_id": 123,
  "vacancy_id": 456
}
```

**Response:**
```json
{
  "id": 789,
  "candidate_id": 123,
  "vacancy_id": 456,
  "score": 85,
  "analysis": "## Match Analysis\n\n**Overall Fit: 85%**\n\n### Strengths\n- Strong Python background with 5+ years\n- Excellent Django experience\n- Team leadership skills\n\n### Gaps\n- Limited cloud platform experience\n- No Kubernetes exposure\n\n### Recommendation\nStrong match. Candidate meets most requirements...",
  "recommendation": "strong_match",
  "created_at": "2024-01-01T00:00:00Z"
}
```

### Batch Match Candidate
```
POST /api/matches/batch-candidate
```
Matches one candidate against all open vacancies.

**Request Body:**
```json
{
  "candidate_id": 123
}
```

### Batch Match Vacancy
```
POST /api/matches/batch-vacancy
```
Matches one vacancy against all active candidates.

**Request Body:**
```json
{
  "vacancy_id": 456
}
```

### Get Match Details
```
GET /api/matches/{id}
```
Returns full match details including rendered analysis.

## Database Schema

### Matches Table
- `id` - Primary key
- `candidate_id` - Foreign key to candidate
- `vacancy_id` - Foreign key to vacancy
- `score` - Match score (0-100)
- `analysis` - Markdown-formatted analysis
- `recommendation` - Recommendation level (strong_match, good_match, weak_match, no_match)
- `created_at` - Timestamp
- `updated_at` - Timestamp

## Match Scoring

Scores are calculated by AI based on:
- **Skills Match** (40%): Overlap between candidate skills and requirements
- **Experience Level** (30%): Years of experience vs. required
- **Education** (15%): Degree and field relevance
- **Domain Knowledge** (15%): Industry-specific experience

### Score Ranges
- **90-100**: Excellent match - Highly recommended
- **75-89**: Strong match - Recommended
- **60-74**: Good match - Consider for interview
- **40-59**: Weak match - May lack key requirements
- **0-39**: Poor match - Not recommended

## Recommendation Levels

- `strong_match` - Score ≥ 75, highly recommended
- `good_match` - Score 60-74, worth considering
- `weak_match` - Score 40-59, has gaps
- `no_match` - Score < 40, not suitable

## Configuration

Match threshold is configurable via Admin Service:
- **Setting**: `match_threshold`
- **Default**: 70
- **Range**: 0-100

Only matches above the threshold are typically shown in the UI.

## Setup

### Run Migrations
```bash
docker-compose exec matching-service php artisan migrate
```

## AI Integration

The service calls AI Service for match analysis:

```php
$response = Http::post('http://ai-service:8080/api/match', [
    'candidate' => $candidateData,
    'vacancy' => $vacancyData
]);

$matchResult = $response->json();
```

## Markdown Analysis Format

The analysis is returned in markdown format:

```markdown
## Match Analysis

**Overall Fit: 85%**

### Strengths
- Strong Python background with 5+ years experience
- Excellent Django and REST API development
- Team leadership and mentoring experience

### Gaps
- Limited cloud platform experience (AWS/Azure)
- No Kubernetes or containerization exposure
- Missing DevOps skills mentioned in requirements

### Recommendation
Strong match overall. Candidate meets most core requirements and has excellent technical skills. The gaps in cloud/DevOps can be addressed through training. Recommend proceeding to interview stage.
```

The frontend renders this as formatted HTML for better readability.

## Development

### View Routes
```bash
docker-compose exec matching-service php artisan route:list
```

### Trigger Manual Match
```bash
docker-compose exec matching-service php artisan tinker
>>> $match = app('App\Http\Controllers\MatchController')->createMatch(123, 456);
```

### Run Tests
```bash
docker-compose exec matching-service php artisan test
```

## Environment Variables

- `DB_HOST` - MySQL host (default: mysql)
- `DB_DATABASE` - Database name (candidacy_matching)
- `REDIS_HOST` - Redis host for caching
- `AI_SERVICE_URL` - AI Service URL (http://ai-service:8080)
- `CANDIDATE_SERVICE_URL` - Candidate Service URL
- `VACANCY_SERVICE_URL` - Vacancy Service URL

## Integration

This service is consumed by:
- **Frontend**: Displays matches in candidate and vacancy views
- **Reporting Service**: Match statistics and analytics
- **API Gateway**: Routes `/api/matches/*` requests

## Performance Optimization

- Matches are cached for 1 hour
- Batch operations are queued for async processing
- Only active candidates and open vacancies are matched
- Scores are indexed for fast filtering

## Best Practices

- Run batch matching when new candidates are added
- Re-match when vacancy requirements change significantly
- Review matches above threshold first
- Use analysis to prepare interview questions
- Track match-to-hire conversion for AI model improvement

## Notes

- Matching is computationally expensive - use batch operations wisely
- Analysis quality depends on AI model configuration
- Scores are relative and should be compared within same vacancy
- Markdown rendering requires frontend library (marked.js)
- Match history is preserved for analytics
