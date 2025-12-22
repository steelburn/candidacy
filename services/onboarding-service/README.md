# Onboarding Service

New hire onboarding workflow management service for the Candidacy recruitment platform.

## Purpose

The Onboarding Service manages the complete onboarding process for new hires. It provides customizable checklists, task tracking, and onboarding status management to ensure smooth integration of new employees.

## Key Features

- **Customizable Checklists**: Create onboarding task templates
- **Task Tracking**: Monitor completion of onboarding tasks
- **Status Management**: Track onboarding progress
- **Role-Based Tasks**: Different checklists for different roles
- **Deadline Management**: Set and track task deadlines
- **Document Collection**: Track required documents

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL (candidacy_onboarding)
- **Port**: 8088 (container internal: 8080)
- **Dependencies**: Candidate Service, Offer Service

## API Endpoints

### List Onboarding Records
```
GET /api/onboarding
```
Query parameters: `page`, `per_page`, `status`, `candidate_id`

### Get Onboarding Record
```
GET /api/onboarding/{id}
```
Returns onboarding record with all tasks.

### Create Onboarding
```
POST /api/onboarding
```
Triggered automatically when offer is accepted.

**Request Body:**
```json
{
  "candidate_id": 123,
  "offer_id": 789,
  "start_date": "2024-02-01",
  "checklist_template": "software_engineer"
}
```

### Update Onboarding
```
PUT /api/onboarding/{id}
```

### Get Tasks
```
GET /api/onboarding/{id}/tasks
```
Returns all tasks for an onboarding record.

### Create Task
```
POST /api/onboarding/{id}/tasks
```
**Request Body:**
```json
{
  "title": "Complete I-9 Form",
  "description": "Fill out employment eligibility verification",
  "category": "documentation",
  "due_date": "2024-01-25",
  "assigned_to": "hr",
  "priority": "high"
}
```

### Complete Task
```
POST /api/onboarding/{id}/tasks/{task_id}/complete
```
Marks a task as completed.

### Get Onboarding Progress
```
GET /api/onboarding/{id}/progress
```
Returns completion percentage and statistics.

## Database Schema

### Onboarding Table
- `id` - Primary key
- `candidate_id` - Foreign key to candidate
- `offer_id` - Foreign key to offer
- `start_date` - Employee start date
- `status` - Status (pending, in_progress, completed)
- `completion_percentage` - Progress (0-100)
- `created_at` - Timestamp
- `updated_at` - Timestamp

### Onboarding Tasks Table
- `id` - Primary key
- `onboarding_id` - Foreign key to onboarding
- `title` - Task title
- `description` - Task description
- `category` - Category (documentation, equipment, training, access)
- `due_date` - Task deadline
- `assigned_to` - Who handles this (hr, it, manager, employee)
- `priority` - Priority (low, medium, high)
- `status` - Status (pending, in_progress, completed)
- `completed_at` - Completion timestamp
- `notes` - Additional notes
- `created_at` - Timestamp
- `updated_at` - Timestamp

## Onboarding Status

- `pending` - Onboarding not yet started
- `in_progress` - Onboarding in progress
- `completed` - All tasks completed

## Task Categories

- `documentation` - Forms, contracts, policies
- `equipment` - Laptop, phone, access cards
- `training` - Orientation, system training
- `access` - Email, systems, building access
- `introduction` - Team introductions, buddy assignment

## Task Priority

- `low` - Can be completed anytime
- `medium` - Should be completed soon
- `high` - Must be completed before start date or first week

## Assigned To

- `hr` - HR department handles
- `it` - IT department handles
- `manager` - Direct manager handles
- `employee` - New hire completes themselves

## Checklist Templates

### Software Engineer Template
```json
[
  {
    "title": "Complete I-9 Form",
    "category": "documentation",
    "assigned_to": "hr",
    "priority": "high",
    "due_days": -1
  },
  {
    "title": "Setup Development Environment",
    "category": "equipment",
    "assigned_to": "it",
    "priority": "high",
    "due_days": 0
  },
  {
    "title": "Complete Security Training",
    "category": "training",
    "assigned_to": "employee",
    "priority": "medium",
    "due_days": 7
  }
]
```

### HR Manager Template
```json
[
  {
    "title": "Review Company Policies",
    "category": "documentation",
    "assigned_to": "employee",
    "priority": "high",
    "due_days": 3
  },
  {
    "title": "ATS System Training",
    "category": "training",
    "assigned_to": "hr",
    "priority": "high",
    "due_days": 5
  }
]
```

## Setup

### Run Migrations
```bash
docker-compose exec onboarding-service php artisan migrate
```

### Seed Checklist Templates
```bash
docker-compose exec onboarding-service php artisan db:seed
```

## Development

### View Routes
```bash
docker-compose exec onboarding-service php artisan route:list
```

### Create Test Onboarding
```bash
docker-compose exec onboarding-service php artisan tinker
>>> Onboarding::create([...]);
```

### Run Tests
```bash
docker-compose exec onboarding-service php artisan test
```

## Environment Variables

- `DB_HOST` - MySQL host (default: mysql)
- `DB_DATABASE` - Database name (candidacy_onboarding)
- `REDIS_HOST` - Redis host for events

## Integration

This service is consumed by:
- **Offer Service**: Triggers onboarding when offer accepted
- **Frontend**: Onboarding management UI
- **Notification Service**: Send task reminders
- **Reporting Service**: Onboarding completion metrics
- **API Gateway**: Routes `/api/onboarding/*` requests

## Workflow Example

1. **Offer Accepted**
   - Offer Service publishes OfferAccepted event
   - Onboarding Service creates onboarding record
   - Checklist template applied based on role
   - Status: pending

2. **Pre-Start Tasks**
   - HR completes documentation tasks
   - IT prepares equipment
   - Employee completes remote forms
   - Status: in_progress

3. **First Day**
   - Employee receives equipment
   - Access credentials provided
   - Orientation completed

4. **First Week**
   - Training sessions completed
   - Team introductions done
   - Systems access verified

5. **Completion**
   - All tasks marked complete
   - Status: completed
   - Onboarding survey sent

## Events Published

### OnboardingStarted
```json
{
  "onboarding_id": 123,
  "candidate_id": 456,
  "start_date": "2024-02-01"
}
```

### OnboardingCompleted
```json
{
  "onboarding_id": 123,
  "candidate_id": 456,
  "completed_at": "2024-02-10T00:00:00Z"
}
```

### TaskOverdue
```json
{
  "task_id": 789,
  "onboarding_id": 123,
  "due_date": "2024-01-25"
}
```

## Best Practices

- Create role-specific checklist templates
- Set realistic deadlines for tasks
- Assign tasks to appropriate departments
- Track completion percentage
- Send reminders for overdue tasks
- Collect feedback after onboarding
- Update templates based on feedback

## Notifications

The service triggers notifications for:
- Onboarding started → Welcome email to new hire
- Task assigned → Notification to assignee
- Task due soon → Reminder
- Task overdue → Escalation
- Onboarding completed → Congratulations message

## Notes

- Onboarding is triggered automatically when offer is accepted
- Tasks can be added/removed during onboarding
- Completion percentage auto-calculated
- Overdue tasks are flagged automatically
- Templates can be customized per department/role
- Historical onboarding data retained for analytics
- Buddy/mentor assignment can be tracked as a task
