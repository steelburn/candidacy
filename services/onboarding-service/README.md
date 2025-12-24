# Onboarding Service

New hire onboarding workflow and checklist management.

## Overview

- **Port**: 8088
- **Database**: `candidacy_onboarding`
- **Framework**: Laravel 10

## Features

- ✅ Onboarding workflow management
- ✅ Customizable checklists
- ✅ Task assignment and tracking
- ✅ Document collection
- ✅ Progress monitoring
- ✅ Automated notifications

## API Endpoints

```http
GET    /api/onboarding              # List onboarding records
POST   /api/onboarding              # Create onboarding
GET    /api/onboarding/{id}         # Get onboarding details
PUT    /api/onboarding/{id}         # Update onboarding
DELETE /api/onboarding/{id}         # Delete onboarding
POST   /api/onboarding/{id}/tasks   # Add task
PUT    /api/onboarding/{id}/tasks/{taskId}  # Update task status
```

## Onboarding Workflow

1. Create onboarding for new hire
2. Assign checklist items
3. Track task completion
4. Collect required documents
5. Monitor progress
6. Complete onboarding

## Development

The database schema is managed via DBML. Always edit `schema.dbml` at the root and run `make dbml-sql`.

```bash
# Sync local database
make dbml-init

# Start service
docker-compose up -d onboarding-service
```
