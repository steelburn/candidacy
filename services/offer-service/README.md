# Offer Service

Job offer management service for the Candidacy recruitment platform.

## Purpose

The Offer Service manages the complete job offer lifecycle including offer creation, acceptance/rejection workflow, offer tracking, and offer letter generation. It coordinates between candidates, vacancies, and HR.

## Key Features

- **Offer Creation**: Create job offers with compensation details
- **Acceptance Workflow**: Track offer acceptance/rejection
- **Status Tracking**: Monitor offer status (pending, accepted, rejected, withdrawn)
- **Offer Letters**: Generate offer letter content
- **Expiration Management**: Track offer expiration dates
- **Compensation Details**: Manage salary, benefits, and perks

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL (candidacy_offer)
- **Port**: 8087 (container internal: 8080)
- **Dependencies**: Candidate Service, Vacancy Service

## API Endpoints

### List Offers
```
GET /api/offers
```
Query parameters: `page`, `per_page`, `status`, `candidate_id`, `vacancy_id`

### Get Offer
```
GET /api/offers/{id}
```
Returns offer with full details.

### Create Offer
```
POST /api/offers
```
**Request Body:**
```json
{
  "candidate_id": 123,
  "vacancy_id": 456,
  "salary": 120000,
  "currency": "USD",
  "employment_type": "full-time",
  "start_date": "2024-02-01",
  "benefits": ["Health Insurance", "401k", "Remote Work"],
  "expiration_date": "2024-01-20",
  "notes": "Includes signing bonus of $10,000"
}
```

### Update Offer
```
PUT /api/offers/{id}
```

### Accept Offer
```
POST /api/offers/{id}/accept
```
Candidate accepts the offer.

**Request Body:**
```json
{
  "accepted_at": "2024-01-15T10:00:00Z",
  "notes": "Excited to join the team!"
}
```

### Reject Offer
```
POST /api/offers/{id}/reject
```
Candidate rejects the offer.

**Request Body:**
```json
{
  "rejection_reason": "Accepted another opportunity",
  "notes": "Thank you for the opportunity"
}
```

### Withdraw Offer
```
POST /api/offers/{id}/withdraw
```
Company withdraws the offer.

**Request Body:**
```json
{
  "withdrawal_reason": "Position filled by another candidate"
}
```

### Generate Offer Letter
```
GET /api/offers/{id}/letter
```
Generates offer letter content.

**Response:**
```json
{
  "letter": "Dear John Doe,\n\nWe are pleased to offer you..."
}
```

## Database Schema

### Offers Table
- `id` - Primary key
- `candidate_id` - Foreign key to candidate
- `vacancy_id` - Foreign key to vacancy
- `salary` - Annual salary
- `currency` - Currency code (USD, EUR, etc.)
- `employment_type` - Type (full-time, part-time, contract)
- `start_date` - Proposed start date
- `benefits` - JSON array of benefits
- `status` - Status (pending, accepted, rejected, withdrawn)
- `expiration_date` - Offer expiration date
- `accepted_at` - Acceptance timestamp
- `rejected_at` - Rejection timestamp
- `rejection_reason` - Reason for rejection
- `withdrawal_reason` - Reason for withdrawal
- `notes` - Additional notes
- `created_at` - Timestamp
- `updated_at` - Timestamp

## Offer Status

- `pending` - Offer extended, awaiting response
- `accepted` - Candidate accepted the offer
- `rejected` - Candidate rejected the offer
- `withdrawn` - Company withdrew the offer
- `expired` - Offer expired without response

## Employment Types

- `full-time` - Full-time employment
- `part-time` - Part-time employment
- `contract` - Contract/freelance
- `internship` - Internship

## Common Benefits

Examples of benefits that can be included:
- Health Insurance
- Dental Insurance
- Vision Insurance
- 401k / Retirement Plan
- Paid Time Off (PTO)
- Remote Work
- Flexible Hours
- Professional Development Budget
- Gym Membership
- Stock Options
- Signing Bonus
- Relocation Assistance

## Setup

### Run Migrations
```bash
docker-compose exec offer-service php artisan migrate
```

## Offer Letter Template

The service generates offer letters using a template:

```
Dear [Candidate Name],

We are pleased to offer you the position of [Job Title] at [Company Name].

Position Details:
- Title: [Job Title]
- Department: [Department]
- Start Date: [Start Date]
- Employment Type: [Full-time/Part-time/Contract]

Compensation:
- Annual Salary: [Salary] [Currency]
- Benefits: [Benefits List]

This offer is valid until [Expiration Date]. Please confirm your acceptance by responding to this offer.

We look forward to welcoming you to our team!

Best regards,
[Company Name] HR Team
```

## Development

### View Routes
```bash
docker-compose exec offer-service php artisan route:list
```

### Create Test Offer
```bash
docker-compose exec offer-service php artisan tinker
>>> Offer::create([...]);
```

### Run Tests
```bash
docker-compose exec offer-service php artisan test
```

## Environment Variables

- `DB_HOST` - MySQL host (default: mysql)
- `DB_DATABASE` - Database name (candidacy_offer)
- `REDIS_HOST` - Redis host for events

## Integration

This service is consumed by:
- **Frontend**: Offer management UI
- **Onboarding Service**: Triggers onboarding when offer accepted
- **Notification Service**: Send offer letters and reminders
- **Reporting Service**: Offer acceptance rates
- **API Gateway**: Routes `/api/offers/*` requests

## Workflow Example

1. **Create Offer**
   - HR creates offer after successful interviews
   - Sets salary, benefits, start date
   - Sets expiration date (typically 7-14 days)

2. **Send Offer**
   - Offer letter generated
   - Sent to candidate via email
   - Status: pending

3. **Candidate Response**
   - **Accept**: Status → accepted, trigger onboarding
   - **Reject**: Status → rejected, record reason
   - **No Response**: Status → expired after expiration date

4. **Post-Acceptance**
   - Onboarding process initiated
   - Vacancy marked as filled
   - Other pending offers for same vacancy withdrawn

## Events Published

### OfferCreated
```json
{
  "offer_id": 789,
  "candidate_id": 123,
  "vacancy_id": 456,
  "salary": 120000
}
```

### OfferAccepted
```json
{
  "offer_id": 789,
  "candidate_id": 123,
  "accepted_at": "2024-01-15T10:00:00Z"
}
```

### OfferRejected
```json
{
  "offer_id": 789,
  "candidate_id": 123,
  "rejection_reason": "..."
}
```

## Best Practices

- Set realistic expiration dates (7-14 days typical)
- Include all benefits and perks in offer
- Be clear about start date expectations
- Document rejection reasons for analytics
- Withdraw other offers when one is accepted
- Generate formal offer letter for accepted offers
- Follow up before expiration date

## Notifications

The service triggers notifications for:
- Offer created → Send offer letter to candidate
- Offer expiring soon → Reminder to candidate
- Offer accepted → Notify HR and trigger onboarding
- Offer rejected → Notify HR
- Offer withdrawn → Notify candidate

## Notes

- Offers can be updated before acceptance
- Once accepted, offers cannot be modified
- Salary is stored as annual amount
- Benefits are flexible JSON array
- Expiration is enforced by background job
- Multiple offers can exist for same candidate (different vacancies)
- Accepted offers trigger onboarding workflow
- Offer letters can be customized per company
