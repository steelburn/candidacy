# Notification Service

Email and notification management service for the Candidacy recruitment platform.

## Purpose

The Notification Service handles all email and notification delivery across the platform. It provides templated emails, notification queuing, delivery tracking, and multi-channel notification support.

## Key Features

- **Email Templates**: Pre-built templates for common scenarios
- **Template Variables**: Dynamic content insertion
- **Notification Queuing**: Async delivery via Redis queues
- **Delivery Tracking**: Track sent, delivered, failed notifications
- **Multi-Channel**: Email, SMS (future), push notifications (future)
- **Retry Logic**: Automatic retry for failed deliveries
- **Unsubscribe Management**: Handle opt-outs

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL (candidacy_notification)
- **Port**: 8091 (container internal: 8080)
- **Queue**: Redis for async processing
- **Email Provider**: SMTP / SendGrid / Mailgun (configurable)

## API Endpoints

### Send Notification
```
POST /api/notifications/send
```
**Request Body:**
```json
{
  "to": "candidate@example.com",
  "template": "interview_scheduled",
  "variables": {
    "candidate_name": "John Doe",
    "interview_date": "2024-01-15",
    "interview_time": "14:00",
    "interviewer_name": "Jane Smith",
    "location": "Zoom Meeting Room"
  }
}
```

### Get Notification Status
```
GET /api/notifications/{id}
```
Returns delivery status and details.

### List Notifications
```
GET /api/notifications
```
Query parameters: `page`, `per_page`, `status`, `recipient`

### Resend Notification
```
POST /api/notifications/{id}/resend
```
Retries failed notification.

### Get Templates
```
GET /api/notifications/templates
```
Lists all available email templates.

## Database Schema

### Notifications Table
- `id` - Primary key
- `recipient` - Email address
- `template` - Template name
- `subject` - Email subject
- `body` - Email body (HTML)
- `variables` - JSON template variables
- `status` - Status (queued, sent, delivered, failed, bounced)
- `channel` - Channel (email, sms, push)
- `sent_at` - Send timestamp
- `delivered_at` - Delivery timestamp
- `failed_at` - Failure timestamp
- `error_message` - Error details if failed
- `retry_count` - Number of retry attempts
- `created_at` - Timestamp
- `updated_at` - Timestamp

## Notification Status

- `queued` - In queue, not yet sent
- `sent` - Sent to email provider
- `delivered` - Successfully delivered
- `failed` - Delivery failed
- `bounced` - Email bounced
- `opened` - Email opened (if tracking enabled)
- `clicked` - Link clicked (if tracking enabled)

## Email Templates

### Candidate Templates

#### interview_scheduled
**Subject**: Interview Scheduled - {{vacancy_title}}
**Variables**: candidate_name, interview_date, interview_time, interviewer_name, location, vacancy_title

#### offer_extended
**Subject**: Job Offer - {{vacancy_title}}
**Variables**: candidate_name, vacancy_title, salary, start_date, expiration_date, offer_letter

#### offer_accepted_confirmation
**Subject**: Welcome to {{company_name}}!
**Variables**: candidate_name, company_name, start_date, onboarding_link

#### application_received
**Subject**: Application Received - {{vacancy_title}}
**Variables**: candidate_name, vacancy_title, application_date

#### interview_reminder
**Subject**: Interview Reminder - Tomorrow at {{interview_time}}
**Variables**: candidate_name, interview_date, interview_time, location

### Interviewer Templates

#### interview_assigned
**Subject**: Interview Assigned - {{candidate_name}}
**Variables**: interviewer_name, candidate_name, interview_date, interview_time, vacancy_title

#### feedback_reminder
**Subject**: Please Submit Interview Feedback
**Variables**: interviewer_name, candidate_name, interview_date

### HR Templates

#### candidate_applied
**Subject**: New Candidate Application
**Variables**: candidate_name, vacancy_title, application_date, cv_link

#### offer_accepted
**Subject**: Offer Accepted - {{candidate_name}}
**Variables**: candidate_name, vacancy_title, start_date

#### offer_rejected
**Subject**: Offer Declined - {{candidate_name}}
**Variables**: candidate_name, vacancy_title, rejection_reason

## Setup

### Run Migrations
```bash
docker-compose exec notification-service php artisan migrate
```

### Configure Email Provider
Edit `.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@candidacy.com
MAIL_FROM_NAME="Candidacy ATS"
```

### Start Queue Worker
```bash
docker-compose exec notification-service php artisan queue:work
```

## Development

### View Routes
```bash
docker-compose exec notification-service php artisan route:list
```

### Send Test Email
```bash
docker-compose exec notification-service php artisan tinker
>>> Mail::to('test@example.com')->send(new InterviewScheduled($data));
```

### View Queue Jobs
```bash
docker-compose exec notification-service php artisan queue:monitor
```

### Run Tests
```bash
docker-compose exec notification-service php artisan test
```

## Environment Variables

- `DB_HOST` - MySQL host (default: mysql)
- `DB_DATABASE` - Database name (candidacy_notification)
- `REDIS_HOST` - Redis host for queues
- `MAIL_MAILER` - Email driver (smtp, sendgrid, mailgun)
- `MAIL_HOST` - SMTP host
- `MAIL_PORT` - SMTP port
- `MAIL_USERNAME` - SMTP username
- `MAIL_PASSWORD` - SMTP password
- `MAIL_FROM_ADDRESS` - From email address
- `MAIL_FROM_NAME` - From name

## Integration

This service is consumed by:
- **All Services**: Send notifications via events
- **Frontend**: Notification history and status
- **API Gateway**: Routes `/api/notifications/*` requests

## Event Listeners

The service listens to events from other services:

- **CandidateCreated** → Send welcome email
- **InterviewScheduled** → Send interview confirmation
- **OfferExtended** → Send offer letter
- **OfferAccepted** → Send congratulations
- **OnboardingStarted** → Send onboarding instructions

## Template Variables

Common variables available in all templates:
- `{{company_name}}` - Company name from settings
- `{{app_name}}` - Application name
- `{{current_year}}` - Current year
- `{{support_email}}` - Support email address

## Retry Logic

Failed notifications are automatically retried:
- **1st retry**: After 5 minutes
- **2nd retry**: After 30 minutes
- **3rd retry**: After 2 hours
- **Max retries**: 3 attempts

After 3 failed attempts, notification is marked as permanently failed.

## Best Practices

- Use templates for consistency
- Test emails before sending to candidates
- Monitor delivery rates
- Handle bounces and unsubscribes
- Keep email content concise
- Include unsubscribe link (for marketing emails)
- Use plain text alternative for HTML emails
- Personalize with candidate name

## Tracking

Optional email tracking features:
- **Open tracking**: Pixel-based tracking
- **Click tracking**: Link tracking
- **Delivery confirmation**: Provider webhooks

## Unsubscribe Management

Candidates can opt-out of:
- Marketing emails
- Job alerts
- Newsletter

They cannot opt-out of:
- Transactional emails (interview confirmations, offers)
- Account-related emails

## Queue Management

Monitor queue health:
```bash
# View queue size
docker-compose exec notification-service php artisan queue:size

# Clear failed jobs
docker-compose exec notification-service php artisan queue:flush

# Retry failed jobs
docker-compose exec notification-service php artisan queue:retry all
```

## Notes

- All emails are queued for async delivery
- Templates support HTML and plain text
- Delivery tracking requires provider support
- Failed emails are logged for debugging
- Attachments supported (CV, offer letters)
- Rate limiting prevents spam
- Bounce handling updates candidate email status
- Templates can be customized per company
