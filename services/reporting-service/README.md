# Reporting Service

Analytics, metrics, and reporting service for recruitment data.

## Overview

- **Port**: 8089
- **Database**: `candidacy_reporting`
- **Framework**: Laravel 10

## Features

- ✅ Candidate metrics and statistics
- ✅ Vacancy analytics
- ✅ Hiring pipeline tracking
- ✅ Performance metrics
- ✅ Dashboard data aggregation

## API Endpoints

```http
GET    /api/reports/dashboard       # Aggregated dashboard data
GET    /api/reports/candidates      # Candidate metrics
GET    /api/reports/vacancies       # Vacancy metrics
GET    /api/reports/pipeline        # Hiring pipeline data
GET    /api/reports/performance     # Performance metrics
GET    /api/health                  # Service health check
```

## Dashboard Endpoint

The dashboard endpoint aggregates all metrics:

```bash
curl http://localhost:8080/api/reports/dashboard
```

**Response:**
```json
{
  "candidates": {
    "total_candidates": 150,
    "by_status": {
      "new": 20,
      "reviewing": 15,
      "shortlisted": 10,
      "interviewed": 8,
      "offered": 5,
      "hired": 2
    },
    "this_month": 25,
    "this_week": 8
  },
  "vacancies": {
    "total_vacancies": 30,
    "by_status": {...},
    "avg_time_to_fill": "45 days"
  },
  "pipeline": {
    "screening": 35,
    "shortlisted": 10,
    "interview": 8,
    "offer": 5,
    "hired": 2
  },
  "performance": {
    "avg_time_to_hire": "45 days",
    "offer_acceptance_rate": "80%",
    "interview_to_offer_ratio": "62%"
  }
}
```

## Metrics Collected

### Candidate Metrics
- Total candidates
- Candidates by status
- New candidates this month/week
- Candidate source tracking

### Vacancy Metrics
- Total vacancies
- Vacancies by status
- Average time to fill
- Open vs closed positions

### Pipeline Metrics
- Candidates at each stage
- Conversion rates between stages
- Drop-off analysis

### Performance Metrics
- Average time to hire
- Offer acceptance rate
- Interview to offer ratio
- Source effectiveness

## Recent Fixes (2025-12-23)

- ✅ Added Shared namespace configuration
- ✅ Updated ReportController to extend BaseApiController
- ✅ Created dashboard aggregation endpoint
- ✅ Fixed routes configuration

## Development

The database schema is managed via DBML. Always edit `schema.dbml` at the root and run `make dbml-sql`.

```bash
# Sync local database
make dbml-init

# Start service
docker-compose up -d reporting-service
```
