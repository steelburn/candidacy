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

## Configuration Categories

The Admin Service manages settings across 8 categories with 40+ configurable options:

### System (`system`)
- `app.name`, `app.company_name`, `app.contact_email`
- `app.candidate_portal_url`, `app.timezone`, `app.language`

### AI (`ai`)
- `ai.provider` - ollama or openrouter
- `ai.ollama.url` - Ollama endpoint
- `ai.ollama.model.*` - Models for default, matching, cv_parsing
- `ai.generation.*` - timeout, temperature, context_length

### Document Parser (`document_parser`)
- `document_parser.use_granite_docling` - Enable IBM Granite Docling
- `document_parser.timeout`, `document_parser.image_resolution`

### Matching (`matching`)
- `matching.min_score_threshold` - Minimum score to save matches (default: 40)
- `matching.display_threshold` - UI filter threshold (default: 60)

### Recruitment, Storage, Features, UI
- See [FEATURES.md](/FEATURES.md) for complete settings reference

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
