# Offer Service

Job offer management and tracking service.

## Overview

- **Port**: 8087
- **Database**: `candidacy_offer`
- **Framework**: Laravel 10

## Features

- ✅ Offer creation and management
- ✅ Offer acceptance/rejection workflow
- ✅ Salary and benefits tracking
- ✅ Offer expiration dates
- ✅ Offer status tracking
- ✅ Offer metrics

## API Endpoints

```http
GET    /api/offers                  # List offers
POST   /api/offers                  # Create offer
GET    /api/offers/{id}             # Get offer details
PUT    /api/offers/{id}             # Update offer
DELETE /api/offers/{id}             # Delete offer
POST   /api/offers/{id}/respond     # Accept/reject offer
GET    /api/offers/metrics/stats    # Get offer statistics
```

## Offer Workflow

1. Create offer for candidate
2. Send offer to candidate
3. Candidate accepts or rejects
4. Track offer status
5. Generate metrics

## Development

The database schema is managed via DBML. Always edit `schema.dbml` at the root and run `make dbml-sql`.

```bash
# Sync local database
make dbml-init

# Start service
docker-compose up -d offer-service
```
