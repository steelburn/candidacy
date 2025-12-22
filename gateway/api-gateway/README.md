# API Gateway

Central API gateway for the Candidacy recruitment platform microservices architecture.

## Purpose

The API Gateway serves as the single entry point for all client requests to the microservices backend. It handles request routing, CORS, authentication, rate limiting, and provides a unified API interface for frontend applications.

## Technology Stack

- **Framework**: Laravel 11
- **Port**: 8080
- **Dependencies**: All microservices, Redis for caching

## Key Features

- **Request Routing**: Routes requests to appropriate microservices
- **CORS Handling**: Configures cross-origin resource sharing for frontends
- **Rate Limiting**: Prevents API abuse
- **Request/Response Logging**: Tracks all API traffic
- **Error Handling**: Unified error responses
- **Health Checks**: Monitor service availability

## Service Routing Map

The gateway routes requests to microservices based on URL patterns:

```
/api/auth/*          → auth-service:8080
/api/candidates/*    → candidate-service:8080
/api/vacancies/*     → vacancy-service:8080
/api/ai/*            → ai-service:8080
/api/matches/*       → matching-service:8080
/api/interviews/*    → interview-service:8080
/api/offers/*        → offer-service:8080
/api/onboarding/*    → onboarding-service:8080
/api/reports/*       → reporting-service:8080
/api/settings/*      → admin-service:8080
/api/notifications/* → notification-service:8080
```

## Access

- **URL**: http://localhost:8080
- **Health Check**: http://localhost:8080/health

## API Endpoints

### Health Check
```
GET /health
```
Returns gateway and service health status.

**Response:**
```json
{
  "status": "healthy",
  "services": {
    "auth-service": "up",
    "candidate-service": "up",
    "vacancy-service": "up",
    "ai-service": "up",
    "matching-service": "up",
    "interview-service": "up",
    "offer-service": "up",
    "onboarding-service": "up",
    "reporting-service": "up",
    "admin-service": "up",
    "notification-service": "up"
  }
}
```

### Service Status
```
GET /api/status
```
Returns detailed status of all microservices.

## Request Flow

1. **Client Request**
   - Frontend sends request to gateway
   - Example: `GET http://localhost:8080/api/candidates`

2. **Gateway Processing**
   - Validates request
   - Checks authentication (if required)
   - Applies rate limiting
   - Logs request

3. **Service Routing**
   - Determines target service from URL
   - Forwards request to microservice
   - Example: Routes to `http://candidate-service:8080/api/candidates`

4. **Response Handling**
   - Receives response from microservice
   - Adds CORS headers
   - Logs response
   - Returns to client

## CORS Configuration

Configured to allow requests from:
- http://localhost:3001 (Main frontend)
- http://localhost:5173 (Applicant portal)
- Production frontend URLs (configurable)

Allowed methods: GET, POST, PUT, DELETE, OPTIONS
Allowed headers: Content-Type, Authorization, Accept

## Rate Limiting

Default limits:
- **Authenticated users**: 1000 requests per minute
- **Unauthenticated users**: 100 requests per minute
- **Login endpoint**: 5 attempts per minute

Rate limit headers included in responses:
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640000000
```

## Authentication

The gateway validates JWT tokens for protected endpoints:

```
Authorization: Bearer <jwt_token>
```

Public endpoints (no auth required):
- `/api/auth/login`
- `/api/auth/register`
- `/api/vacancies` (GET only)
- `/health`

## Error Handling

Unified error response format:

```json
{
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "Candidate not found",
    "status": 404
  }
}
```

Common error codes:
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Internal Server Error
- `503` - Service Unavailable

## Setup

### Run Migrations
```bash
docker-compose exec api-gateway php artisan migrate
```

### Configure Services
Edit `.env`:
```
AUTH_SERVICE_URL=http://auth-service:8080
CANDIDATE_SERVICE_URL=http://candidate-service:8080
VACANCY_SERVICE_URL=http://vacancy-service:8080
AI_SERVICE_URL=http://ai-service:8080
MATCHING_SERVICE_URL=http://matching-service:8080
INTERVIEW_SERVICE_URL=http://interview-service:8080
OFFER_SERVICE_URL=http://offer-service:8080
ONBOARDING_SERVICE_URL=http://onboarding-service:8080
REPORTING_SERVICE_URL=http://reporting-service:8080
ADMIN_SERVICE_URL=http://admin-service:8080
NOTIFICATION_SERVICE_URL=http://notification-service:8080
```

## Development

### View Routes
```bash
docker-compose exec api-gateway php artisan route:list
```

### View Logs
```bash
docker-compose logs -f api-gateway
```

### Test Gateway
```bash
curl http://localhost:8080/health
```

### Run Tests
```bash
docker-compose exec api-gateway php artisan test
```

## Logging

All requests are logged with:
- Timestamp
- HTTP method
- URL path
- Response status
- Response time
- Client IP
- User agent

Logs are sent to:
- **Development**: stderr (Docker logs)
- **Production**: Loki via Promtail

## Caching

The gateway caches:
- Service health status (30 seconds)
- Public vacancy listings (5 minutes)
- Settings from admin service (10 minutes)

Cache can be cleared:
```bash
docker-compose exec api-gateway php artisan cache:clear
```

## Load Balancing

For production, the gateway can be deployed behind a load balancer:
- Multiple gateway instances
- Round-robin distribution
- Health check endpoint for load balancer
- Session affinity not required (stateless)

## Monitoring

Metrics available:
- Request count per endpoint
- Average response time
- Error rate
- Service availability
- Rate limit hits

Integrated with Grafana for visualization.

## Security

- **HTTPS**: Required in production
- **API Keys**: Optional for third-party integrations
- **IP Whitelisting**: Configurable for admin endpoints
- **Request Validation**: Input sanitization
- **SQL Injection Protection**: Parameterized queries
- **XSS Protection**: Output escaping

## Performance Optimization

- **Connection Pooling**: Reuses connections to services
- **Response Compression**: Gzip compression enabled
- **Caching**: Reduces redundant service calls
- **Async Logging**: Non-blocking log writes
- **Keep-Alive**: HTTP keep-alive for persistent connections

## Environment Variables

- `APP_ENV` - Environment (development, production)
- `APP_DEBUG` - Debug mode (true/false)
- `LOG_LEVEL` - Logging level (debug, info, warning, error)
- `REDIS_HOST` - Redis host for caching
- `CORS_ALLOWED_ORIGINS` - Allowed frontend URLs
- `RATE_LIMIT_PER_MINUTE` - Rate limit threshold

## Service Discovery

Currently uses static service URLs. Future enhancements:
- Consul for service discovery
- Automatic service registration
- Dynamic routing based on service health

## Failover

If a service is unavailable:
1. Gateway returns 503 Service Unavailable
2. Error is logged
3. Health check marks service as down
4. Automatic retry after 30 seconds

## API Versioning

Supports API versioning via URL:
- `/api/v1/candidates` - Version 1
- `/api/v2/candidates` - Version 2 (future)

Current version: v1 (implicit, no version in URL)

## Documentation

API documentation available via Swagger/OpenAPI:
- **URL**: http://localhost:8080/api/documentation
- Auto-generated from route definitions
- Interactive API testing

## Notes

- Gateway is stateless - can be horizontally scaled
- All service URLs use internal Docker network names
- External clients only access the gateway
- Services should not be directly accessible from outside
- Gateway handles all authentication and authorization
- Request/response transformation can be added as middleware
- WebSocket support planned for real-time features
