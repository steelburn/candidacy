# Reporting Service

Analytics and metrics service for the Candidacy recruitment platform.

## Purpose

The Reporting Service provides comprehensive analytics, metrics, and insights across the recruitment lifecycle. It aggregates data from all services to generate reports on recruitment performance, pipeline health, and key metrics.

## Key Features

- **Recruitment Metrics**: Time-to-hire, cost-per-hire, source effectiveness
- **Pipeline Analytics**: Candidate flow through recruitment stages
- **Conversion Rates**: Stage-to-stage conversion tracking
- **Hiring Trends**: Historical hiring patterns and forecasts
- **Interviewer Performance**: Interview completion and feedback metrics
- **Vacancy Analytics**: Open positions, fill rates, time-to-fill
- **Dashboard Data**: Real-time KPIs for executive dashboards

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL (candidacy_reporting)
- **Port**: 8089 (container internal: 8080)
- **Dependencies**: All other services for data aggregation

## API Endpoints

### Get Dashboard Metrics
```
GET /api/reports/metrics
```
Returns key metrics for dashboard.

**Response:**
```json
{
  "total_candidates": 1250,
  "active_vacancies": 15,
  "interviews_this_month": 45,
  "offers_pending": 8,
  "average_time_to_hire": 28,
  "offer_acceptance_rate": 85
}
```

### Get Pipeline Report
```
GET /api/reports/pipeline
```
Shows candidate distribution across stages.

**Response:**
```json
{
  "applied": 450,
  "screening": 120,
  "interview": 45,
  "offer": 12,
  "hired": 8
}
```

### Get Time-to-Hire Report
```
GET /api/reports/time-to-hire
```
Query parameters: `start_date`, `end_date`, `department`

**Response:**
```json
{
  "average_days": 28,
  "median_days": 25,
  "by_department": {
    "Engineering": 32,
    "Sales": 21,
    "Marketing": 26
  }
}
```

### Get Source Effectiveness
```
GET /api/reports/source-effectiveness
```
Analyzes which candidate sources are most effective.

**Response:**
```json
{
  "sources": [
    {
      "name": "LinkedIn",
      "candidates": 450,
      "hired": 25,
      "conversion_rate": 5.6
    },
    {
      "name": "Referrals",
      "candidates": 120,
      "hired": 18,
      "conversion_rate": 15.0
    }
  ]
}
```

### Get Interviewer Performance
```
GET /api/reports/interviewer-performance
```
Query parameters: `interviewer_id`, `start_date`, `end_date`

**Response:**
```json
{
  "interviewer_id": 789,
  "interviews_conducted": 45,
  "average_rating": 4.2,
  "feedback_completion_rate": 95,
  "hire_rate": 35
}
```

### Get Vacancy Analytics
```
GET /api/reports/vacancy-analytics
```
**Response:**
```json
{
  "total_vacancies": 50,
  "open_vacancies": 15,
  "filled_this_month": 8,
  "average_time_to_fill": 32,
  "fill_rate": 85
}
```

### Get Hiring Trends
```
GET /api/reports/hiring-trends
```
Query parameters: `period` (monthly, quarterly, yearly)

**Response:**
```json
{
  "period": "monthly",
  "data": [
    {"month": "2024-01", "hires": 12},
    {"month": "2024-02", "hires": 15},
    {"month": "2024-03", "hires": 10}
  ]
}
```

### Get Conversion Rates
```
GET /api/reports/conversion-rates
```
**Response:**
```json
{
  "applied_to_screening": 26.7,
  "screening_to_interview": 37.5,
  "interview_to_offer": 26.7,
  "offer_to_hired": 85.0
}
```

## Database Schema

### Reports Table
- `id` - Primary key
- `report_type` - Type of report
- `generated_at` - Generation timestamp
- `data` - JSON report data
- `parameters` - JSON query parameters
- `created_at` - Timestamp

## Key Metrics Definitions

### Time-to-Hire
Days from candidate application to offer acceptance.

### Time-to-Fill
Days from vacancy opening to offer acceptance.

### Cost-per-Hire
Total recruitment costs divided by number of hires.

### Offer Acceptance Rate
Percentage of offers that are accepted.

### Source Effectiveness
Conversion rate from application to hire by source.

### Fill Rate
Percentage of open positions that are filled.

## Report Types

- `dashboard` - Real-time KPI dashboard
- `pipeline` - Recruitment pipeline analysis
- `time_to_hire` - Time-to-hire metrics
- `source_effectiveness` - Source performance
- `interviewer_performance` - Interviewer metrics
- `vacancy_analytics` - Vacancy statistics
- `hiring_trends` - Historical trends
- `conversion_rates` - Stage conversion rates

## Setup

### Run Migrations
```bash
docker-compose exec reporting-service php artisan migrate
```

## Data Aggregation

The service aggregates data from:
- **Candidate Service**: Candidate counts, sources
- **Vacancy Service**: Vacancy counts, status
- **Interview Service**: Interview metrics
- **Offer Service**: Offer metrics, acceptance rates
- **Matching Service**: Match statistics
- **Onboarding Service**: Onboarding completion

## Development

### View Routes
```bash
docker-compose exec reporting-service php artisan route:list
```

### Generate Test Report
```bash
docker-compose exec reporting-service php artisan tinker
>>> app('App\Services\ReportGenerator')->generateDashboard();
```

### Run Tests
```bash
docker-compose exec reporting-service php artisan test
```

## Environment Variables

- `DB_HOST` - MySQL host (default: mysql)
- `DB_DATABASE` - Database name (candidacy_reporting)
- `REDIS_HOST` - Redis host for caching

## Integration

This service is consumed by:
- **Frontend**: Dashboard and reports UI
- **API Gateway**: Routes `/api/reports/*` requests

## Caching Strategy

- Dashboard metrics: Cached for 5 minutes
- Historical reports: Cached for 1 hour
- Trend analysis: Cached for 24 hours
- Real-time data: No caching

## Performance Optimization

- Pre-aggregate common metrics
- Use database indexes on date fields
- Cache frequently accessed reports
- Async report generation for complex queries
- Pagination for large datasets

## Best Practices

- Schedule regular report generation
- Archive old reports for historical analysis
- Set up alerts for metric thresholds
- Export reports to CSV/PDF for sharing
- Track metrics over time for trends
- Compare metrics across departments

## Export Formats

Reports can be exported in:
- JSON (API default)
- CSV (for spreadsheets)
- PDF (for presentations)
- Excel (for detailed analysis)

## Scheduled Reports

Configure automated report generation:
- Daily: Pipeline snapshot
- Weekly: Hiring metrics summary
- Monthly: Comprehensive recruitment report
- Quarterly: Executive dashboard

## Notes

- All metrics are calculated in real-time unless cached
- Historical data is preserved for trend analysis
- Reports can be filtered by date range, department, or other criteria
- Custom reports can be created via API
- Data accuracy depends on proper data entry in source services
- Metrics are anonymized for privacy compliance
