# API Gateway

The API Gateway serves as the single entry point for all client requests in the Candidacy microservices architecture. It routes requests to the appropriate backend services and handles cross-cutting concerns.

## Overview

- **Port**: 8080
- **Framework**: Laravel 10
- **Purpose**: Request routing, service discovery, and centralized access point

## Features

- ✅ **Request Proxying**: Routes requests to appropriate microservices
- ✅ **Service Discovery**: Maps route prefixes to backend services
- ✅ **Header Forwarding**: Preserves authentication and custom headers
- ✅ **Error Handling**: Centralized error responses
- ✅ **Logging**: Request/response logging for monitoring

## Service Mappings

The gateway routes requests based on URL prefixes to the following services:

| Route Prefix | Service | Internal URL |
|-------------|---------|--------------|
| `/api/auth/*` | auth-service | `http://auth-service:8080` |
| `/api/candidates/*` | candidate-service | `http://candidate-service:8080` |
| `/api/vacancies/*` | vacancy-service | `http://vacancy-service:8080` |
| `/api/matches/*` | matching-service | `http://matching-service:8080` |
| `/api/interviews/*` | interview-service | `http://interview-service:8080` |
| `/api/offers/*` | offer-service | `http://offer-service:8080` |
| `/api/admin/*` | admin-service | `http://admin-service:8080` |
| `/api/settings/*` | admin-service | `http://admin-service:8080` |
| `/api/system-health/*` | admin-service | `http://admin-service:8080` |
| `/api/portal/*` | candidate-service | `http://candidate-service:8080` |
| `/api/notifications/*` | notification-service | `http://notification-service:8080` |
| `/api/reports/*` | reporting-service | `http://reporting-service:8080` |
| `/api/users/*` | auth-service | `http://auth-service:8080` |
| `/api/roles/*` | auth-service | `http://auth-service:8080` |
| `/api/document-parser/*` | document-parser-service | `http://document-parser-service:8080` |

## How It Works

1. **Client Request**: Client sends request to `http://localhost:8080/api/{service}/{endpoint}`
2. **Route Matching**: Gateway extracts service prefix from URL
3. **Service Lookup**: Maps prefix to internal service URL
4. **Request Forwarding**: Proxies request to target service with all headers
5. **Response Return**: Returns service response to client

## Configuration

### Environment Variables

```env
APP_NAME=api-gateway
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080
```

### Service URLs

Services are accessed via Docker network using service names:
- Format: `http://{service-name}:8080`
- Example: `http://auth-service:8080/api/auth/login`

## Request Flow Example

```
Client Request:
POST http://localhost:8080/api/auth/login
Headers: Content-Type: application/json
Body: {"email": "user@example.com", "password": "password"}

↓

Gateway Processing:
1. Extract prefix: "auth"
2. Map to service: "auth-service"
3. Build target URL: http://auth-service:8080/api/auth/login
4. Forward request with headers and body

↓

Service Response:
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {...}
}

↓

Client receives response
```

## Error Handling

The gateway handles various error scenarios:

- **Service Not Found** (404): Unknown route prefix
- **Service Unavailable** (503): Target service is down
- **Timeout** (504): Service took too long to respond (300s timeout)
- **Bad Gateway** (502): Invalid response from service

## Recent Fixes (2025-12-23)

- ✅ Fixed Content-Type handling for GET requests
- ✅ Added missing service mappings (reports, users, roles, system-health)
- ✅ Improved error handling and logging
- ✅ Fixed request body forwarding

## Development

### Running Locally

```bash
cd gateway/api-gateway
composer install
php artisan serve --port=8080
```

### Testing

```bash
# Test gateway routing
curl http://localhost:8080/api/auth/login

# Check gateway logs
docker-compose logs -f api-gateway
```

## Monitoring

- **Logs**: Available via `docker-compose logs api-gateway`
- **Health**: Gateway itself doesn't have a health endpoint (routes to services)
- **Metrics**: Request/response times logged for each proxied request

## Architecture

```
┌─────────────────┐
│   Client App    │
│  (Frontend)     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  API Gateway    │
│  Port: 8080     │
└────────┬────────┘
         │
         ├──────────────┬──────────────┬──────────────┐
         ▼              ▼              ▼              ▼
    ┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐
    │  Auth   │   │Candidate│   │ Vacancy │   │   ...   │
    │ Service │   │ Service │   │ Service │   │Services │
    └─────────┘   └─────────┘   └─────────┘   └─────────┘
```

## Security

- **Authentication**: Gateway forwards `Authorization` headers to services
- **CORS**: Configured to allow frontend origins
- **Rate Limiting**: Can be configured per route
- **Request Validation**: Basic validation before proxying

## Troubleshooting

**Service not found errors**:
- Check service mapping in `GatewayController.php`
- Verify service name matches Docker Compose configuration

**Timeout errors**:
- Default timeout is 300 seconds
- Check if target service is responding
- Review service logs for slow operations

**Connection refused**:
- Ensure target service is running
- Verify Docker network connectivity
- Check service port configuration
