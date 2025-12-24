# Interview Service

Interview scheduling and management service.

## Overview

- **Port**: 8086
- **Database**: `candidacy_interview`
- **Framework**: Laravel 10

## Features

- ✅ Interview scheduling
- ✅ Multiple interview types (in-person, phone, video)
- ✅ Interview feedback collection
- ✅ Interviewer assignment
- ✅ Interview status tracking
- ✅ Upcoming interviews list

## API Endpoints

```http
GET    /api/interviews              # List interviews
POST   /api/interviews              # Schedule interview
GET    /api/interviews/{id}         # Get interview details
PUT    /api/interviews/{id}         # Update interview
DELETE /api/interviews/{id}         # Cancel interview
POST   /api/interviews/{id}/feedback # Add feedback
GET    /api/interviews/upcoming/all  # Get upcoming interviews
GET    /api/interviews/metrics/stats # Get interview statistics
```

## Interview Types

- **In-Person**: Face-to-face interview
- **Phone**: Phone call interview
- **Video**: Video conference interview

## Development

The database schema is managed via DBML. Always edit `schema.dbml` at the root and run `make dbml-sql`.

```bash
# Sync local database
make dbml-init

# Start service
docker-compose up -d interview-service
```
