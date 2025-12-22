# Interview Service

Interview scheduling and management service for the Candidacy recruitment platform.

## Purpose

The Interview Service manages the complete interview lifecycle including scheduling, status tracking, feedback collection, and interviewer assignment. It coordinates between candidates, interviewers, and vacancies.

## Key Features

- **Interview Scheduling**: Create and schedule interviews
- **Status Tracking**: Track interview progress (scheduled, completed, cancelled)
- **Feedback Collection**: Collect and store interviewer feedback
- **Interviewer Assignment**: Assign interviewers to interviews
- **Calendar Integration Ready**: Structured data for calendar sync
- **Reminder System**: Track interview dates for notifications

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL (candidacy_interview)
- **Port**: 8086 (container internal: 8080)
- **Dependencies**: Candidate Service, Vacancy Service, Auth Service

## API Endpoints

### List Interviews
```
GET /api/interviews
```
Query parameters: `page`, `per_page`, `status`, `candidate_id`, `vacancy_id`, `interviewer_id`

### Get Interview
```
GET /api/interviews/{id}
```
Returns interview with full details including feedback.

### Create Interview
```
POST /api/interviews
```
**Request Body:**
```json
{
  "candidate_id": 123,
  "vacancy_id": 456,
  "interviewer_id": 789,
  "scheduled_at": "2024-01-15T14:00:00Z",
  "duration_minutes": 60,
  "location": "Zoom Meeting Room",
  "interview_type": "technical",
  "notes": "Focus on Python and system design"
}
```

### Update Interview
```
PUT /api/interviews/{id}
```

### Cancel Interview
```
POST /api/interviews/{id}/cancel
```
**Request Body:**
```json
{
  "cancellation_reason": "Candidate unavailable"
}
```

### Complete Interview
```
POST /api/interviews/{id}/complete
```
Marks interview as completed.

### Submit Feedback
```
POST /api/interviews/{id}/feedback
```
**Request Body:**
```json
{
  "rating": 4,
  "technical_skills": "Strong Python knowledge, good problem-solving",
  "communication": "Excellent communication and presentation skills",
  "cultural_fit": "Great team player, aligns with company values",
  "strengths": "Technical expertise, quick learner",
  "concerns": "Limited experience with cloud platforms",
  "recommendation": "hire",
  "notes": "Highly recommend for the position"
}
```

### Get Interviews by Interviewer
```
GET /api/interviews/interviewer/{interviewer_id}
```
Returns all interviews assigned to a specific interviewer.

### Get Upcoming Interviews
```
GET /api/interviews/upcoming
```
Returns all scheduled interviews in the next 7 days.

## Database Schema

### Interviews Table
- `id` - Primary key
- `candidate_id` - Foreign key to candidate
- `vacancy_id` - Foreign key to vacancy
- `interviewer_id` - Foreign key to user (interviewer)
- `scheduled_at` - Interview date/time
- `duration_minutes` - Interview duration
- `location` - Interview location (physical/virtual)
- `interview_type` - Type (phone, technical, behavioral, final)
- `status` - Status (scheduled, completed, cancelled, no_show)
- `notes` - Pre-interview notes
- `cancellation_reason` - Reason if cancelled
- `created_at` - Timestamp
- `updated_at` - Timestamp

### Interview Feedback Table
- `id` - Primary key
- `interview_id` - Foreign key to interview
- `rating` - Overall rating (1-5)
- `technical_skills` - Technical assessment
- `communication` - Communication assessment
- `cultural_fit` - Culture fit assessment
- `strengths` - Candidate strengths
- `concerns` - Areas of concern
- `recommendation` - Recommendation (hire, maybe, no_hire)
- `notes` - Additional notes
- `created_at` - Timestamp

## Interview Types

- `phone` - Initial phone screening
- `technical` - Technical assessment
- `behavioral` - Behavioral/HR interview
- `final` - Final round with leadership
- `panel` - Panel interview

## Interview Status

- `scheduled` - Interview is scheduled
- `completed` - Interview completed, feedback submitted
- `cancelled` - Interview cancelled
- `no_show` - Candidate didn't show up

## Feedback Recommendations

- `hire` - Recommend to hire
- `maybe` - Borderline, needs discussion
- `no_hire` - Do not recommend

## Setup

### Run Migrations
```bash
docker-compose exec interview-service php artisan migrate
```

## Calendar Integration

The service provides structured data for calendar integration:

```json
{
  "title": "Technical Interview - John Doe",
  "start": "2024-01-15T14:00:00Z",
  "end": "2024-01-15T15:00:00Z",
  "location": "Zoom Meeting Room",
  "attendees": [
    "candidate@example.com",
    "interviewer@company.com"
  ],
  "description": "Technical interview for Senior Software Engineer position"
}
```

This can be used to:
- Generate iCal files
- Sync with Google Calendar
- Send calendar invites via email

## Development

### View Routes
```bash
docker-compose exec interview-service php artisan route:list
```

### Schedule Test Interview
```bash
docker-compose exec interview-service php artisan tinker
>>> Interview::create([...]);
```

### Run Tests
```bash
docker-compose exec interview-service php artisan test
```

## Environment Variables

- `DB_HOST` - MySQL host (default: mysql)
- `DB_DATABASE` - Database name (candidacy_interview)
- `REDIS_HOST` - Redis host for events

## Integration

This service is consumed by:
- **Frontend**: Interview scheduling UI and calendar
- **Notification Service**: Send interview reminders
- **Reporting Service**: Interview statistics
- **API Gateway**: Routes `/api/interviews/*` requests

## Workflow Example

1. **Schedule Interview**
   - HR/Recruiter creates interview
   - System assigns interviewer
   - Notification sent to both parties

2. **Conduct Interview**
   - Interviewer accesses interview details
   - Interview takes place
   - Status remains "scheduled"

3. **Submit Feedback**
   - Interviewer submits feedback form
   - Rating and recommendation recorded
   - Status changes to "completed"

4. **Decision Making**
   - HR reviews feedback
   - Decision made based on recommendation
   - Offer extended or candidate rejected

## Best Practices

- Schedule interviews at least 48 hours in advance
- Always include location/meeting link
- Set realistic duration (30-60 minutes typical)
- Submit feedback within 24 hours of interview
- Include specific examples in feedback
- Be honest in recommendations

## Notifications

The service publishes events for:
- Interview scheduled → Send calendar invite
- Interview in 24 hours → Send reminder
- Interview completed → Request feedback
- Interview cancelled → Notify all parties

## Notes

- Interviews can be rescheduled by updating scheduled_at
- Feedback is optional but highly recommended
- Multiple interviews can be scheduled for same candidate
- Interviewer must have "interviewer" or higher role
- Past interviews are retained for historical analysis
- No-show status can be set manually if candidate doesn't attend
