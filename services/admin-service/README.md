# Admin Service

System administration and configuration management service.

## Overview

- **Port**: 8090
- **Database**: `candidacy_admin`
- **Framework**: Laravel 10

## Features

- ✅ System settings management
- ✅ Application configuration
- ✅ AI provider configuration
- ✅ System health monitoring
- ✅ Service status tracking

## API Endpoints

```http
GET    /api/settings                # Get all settings
PUT    /api/settings                # Update settings (bulk)
GET    /api/settings/{key}          # Get single setting
PUT    /api/settings/{key}          # Update single setting
GET    /api/settings/category/{cat} # Get settings by category
GET    /api/system-health           # Get all services health status
GET    /api/health                  # Service health check
```

## Settings Categories

### General Settings
- `app_name` - Application name
- `company_name` - Company name
- `contact_email` - Contact email
- `candidate_portal_url` - Applicant portal base URL
- `max_upload_size` - Maximum file upload size (MB)

### AI Settings
- `enable_ai` - Enable/disable AI features
- `ai_provider` - AI provider (ollama/openrouter)
- `ollama_url` - Ollama instance URL
- `ollama_model` - Model for general AI tasks
- `ollama_matching_model` - Model for candidate matching
- `openrouter_api_key` - OpenRouter API key

### Notification Settings
- `enable_notifications` - Enable/disable notifications
- `email_from_address` - Email sender address
- `email_from_name` - Email sender name

## System Health Monitoring

The system health endpoint checks all microservices:

```bash
curl http://localhost:8080/api/system-health
```

**Response:**
```json
{
  "services": [
    {
      "service": "auth-service",
      "status": "online",
      "response_time": "16ms"
    },
    ...
  ],
  "timestamp": "2025-12-23T03:00:00+00:00"
}
```

## Recent Fixes (2025-12-23)

- ✅ Added Shared namespace configuration
- ✅ Updated SettingController to extend BaseApiController
- ✅ Fixed routes syntax errors
- ✅ Improved system health check with HTTP client

## Development

The database schema is managed via DBML. Always edit `schema.dbml` at the root and run `make dbml-sql`.

```bash
# Sync local database
make dbml-init

# Start service
docker-compose up -d admin-service
```
