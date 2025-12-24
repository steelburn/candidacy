# Notification Service

Email and notification management service.

## Overview

- **Port**: 8091
- **Database**: `candidacy_notification`
- **Framework**: Laravel 10

## Features

- ✅ Email notifications
- ✅ Template management
- ✅ Notification queuing
- ✅ Delivery tracking
- ✅ Multiple notification types
- ✅ Configurable email settings

## API Endpoints

```http
POST   /api/notifications           # Send notification
GET    /api/notifications           # List notifications
GET    /api/notifications/{id}      # Get notification details
GET    /api/health                  # Service health check
```

## Notification Types

- Interview invitations
- Offer letters
- Application status updates
- Interview reminders
- Onboarding tasks
- System notifications

## Development

The database schema is managed via DBML. Always edit `schema.dbml` at the root and run `make dbml-sql`.

```bash
# Sync local database
make dbml-init

# Start service
docker-compose up -d notification-service
```
