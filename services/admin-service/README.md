# Admin Service

System configuration and settings management service for the Candidacy recruitment platform.

## Purpose

The Admin Service manages all system-wide configuration settings, including AI provider configuration, module toggles, branding settings, and application preferences. It provides a centralized settings API consumed by other services and the frontend admin panel.

## Key Features

- **Settings Management**: CRUD operations for system settings
- **Category Organization**: Settings grouped by category (general, ai, system)
- **Type Safety**: Typed settings (string, boolean, integer)
- **Default Values**: Fallback defaults when settings not yet configured
- **Real-time Updates**: Settings changes immediately available to all services

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL (candidacy_admin)
- **Port**: 8090 (container internal: 8080)
- **Dependencies**: Redis for caching

## Available Settings

### General Settings
- `app_name` - Application name (default: "Candidacy")
- `company_name` - Company name for branding
- `contact_email` - HR contact email
- `candidate_portal_url` - Base URL for candidate portal links (default: http://localhost:5173)
- `login_background_image` - URL for login page background image

### AI Settings
- `enable_ai` - Enable/disable AI features (boolean)
- `ai_provider` - AI provider selection: "ollama" or "openrouter"
- `matching_model` - AI model for candidate matching (default: gemma2:2b)
- `questionnaire_model` - AI model for question generation (default: gemma2:2b)
- `match_threshold` - Minimum match percentage (default: 70)

### System Settings
- `max_upload_size` - Maximum file upload size in MB (default: 10)

## API Endpoints

### Get All Settings
```
GET /api/settings
```
Returns all settings with defaults for missing values.

### Get Single Setting
```
GET /api/settings/{key}
```
Returns a specific setting by key.

### Update Setting
```
PUT /api/settings/{key}
```
Updates a setting value.

**Request Body:**
```json
{
  "value": "new_value"
}
```

### Bulk Update Settings
```
POST /api/settings/bulk
```
Updates multiple settings at once.

**Request Body:**
```json
{
  "settings": {
    "app_name": "My Company ATS",
    "match_threshold": 75
  }
}
```

## Database Schema

### Settings Table
- `id` - Primary key
- `key` - Setting key (unique)
- `value` - Setting value (JSON encoded for complex types)
- `type` - Data type (string, boolean, integer)
- `category` - Setting category (general, ai, system)
- `created_at` - Timestamp
- `updated_at` - Timestamp

## Setup

### Run Migrations
```bash
docker-compose exec admin-service php artisan migrate
```

### Seed Default Settings
```bash
docker-compose exec admin-service php artisan db:seed
```

This will create all default settings with sensible values.

## Usage Examples

### Frontend Integration
The admin panel in the main frontend (http://localhost:3001/admin) provides a UI for managing these settings.

### Service Integration
Other services can fetch settings via HTTP:

```php
$response = Http::get('http://admin-service:8080/api/settings/candidate_portal_url');
$portalUrl = $response->json()['value'];
```

## Development

### View Routes
```bash
docker-compose exec admin-service php artisan route:list
```

### Clear Cache
```bash
docker-compose exec admin-service php artisan cache:clear
```

### Run Tests
```bash
docker-compose exec admin-service php artisan test
```

## Environment Variables

- `DB_HOST` - MySQL host (default: mysql)
- `DB_DATABASE` - Database name (candidacy_admin)
- `DB_USERNAME` - Database user (default: root)
- `DB_PASSWORD` - Database password
- `REDIS_HOST` - Redis host for caching

## Notes

- Settings are cached for performance
- Changes to settings require cache clear or TTL expiration
- All services should use the admin service as the single source of truth for configuration
- Default values are provided in controllers when settings don't exist in database
