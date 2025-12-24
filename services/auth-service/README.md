# Auth Service

Authentication and user management service for the Candidacy recruitment system.

## Overview

- **Port**: 8081
- **Database**: `candidacy_auth`
- **Authentication**: JWT (tymon/jwt-auth)
- **Framework**: Laravel 10

## Features

### Authentication
- ✅ JWT-based authentication with `auth:api` guard
- ✅ User login and registration
- ✅ Token refresh and logout
- ✅ Password change functionality
- ✅ First-time admin setup

### User Management
- ✅ CRUD operations for users
- ✅ Role assignment and management
- ✅ User activation/deactivation
- ✅ Department and position tracking

### Role Management
- ✅ Predefined roles (Admin, HR Manager, Recruiter, Interviewer, Viewer)
- ✅ Role-based permissions
- ✅ User-role assignment

## API Endpoints

### Public Endpoints

```http
POST   /api/auth/login              # User login
POST   /api/auth/register           # User registration
GET    /api/setup/check             # Check if setup needed
POST   /api/setup/create-admin      # Create first admin user
```

### Protected Endpoints (require JWT token)

```http
# Authentication
POST   /api/auth/logout             # Logout user
POST   /api/auth/refresh            # Refresh JWT token
GET    /api/auth/me                 # Get current user
POST   /api/auth/change-password    # Change password

# User Management
GET    /api/users                   # List all users
POST   /api/users                   # Create new user
GET    /api/users/{id}              # Get user details
PUT    /api/users/{id}              # Update user
DELETE /api/users/{id}              # Delete user

# Role Management
GET    /api/roles                   # List all roles
GET    /api/roles/{id}              # Get role details
POST   /api/users/{userId}/roles    # Assign role to user
DELETE /api/users/{userId}/roles/{roleId}  # Remove role from user
```

## Authentication Flow

### Login

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@test.com",
    "password": "password"
  }'
```

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@test.com",
    "roles": []
  }
}
```

### Using the Token

```bash
curl -X GET http://localhost:8080/api/users \
  -H "Authorization: Bearer {access_token}"
```

## Database Schema

### Users Table
- `id` - Primary key
- `name` - User's full name
- `email` - Unique email address
- `password` - Hashed password
- `department` - User's department
- `position` - User's position
- `is_active` - Account status
- `email_verified_at` - Email verification timestamp
- `created_at`, `updated_at`, `deleted_at`

### Roles Table
- `id` - Primary key
- `name` - Role name (admin, hr_manager, recruiter, interviewer, viewer)
- `display_name` - Human-readable name
- `description` - Role description
- `permissions` - JSON array of permissions
- `created_at`, `updated_at`

### User_Roles Table
- `user_id` - Foreign key to users
- `role_id` - Foreign key to roles
- `assigned_at` - Assignment timestamp
- `assigned_by` - User who assigned the role

## Configuration

### JWT Settings

Token expiration and other JWT settings are configured in `config/jwt.php`:
- **TTL**: 60 minutes
- **Refresh TTL**: 20160 minutes (2 weeks)
- **Algorithm**: HS256

### Environment Variables

```env
APP_NAME=auth-service
DB_DATABASE=candidacy_auth
JWT_SECRET=your-secret-key
JWT_TTL=60
```

## User Roles

| Role | Permissions |
|------|-------------|
| **Admin** | Full system access, user management, system configuration |
| **HR Manager** | Manage vacancies, view all data, configure onboarding |
| **Recruiter** | Manage candidates, schedule interviews, view matches |
| **Interviewer** | View assigned interviews, submit feedback |
| **Viewer** | Read-only access to data |

## First-Time Setup

When no users exist in the system, use the setup endpoint:

```bash
curl -X POST http://localhost:8080/api/setup/create-admin \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin User",
    "email": "admin@test.com",
    "password": "password",
    "password_confirmation": "password"
  }'
```

This creates the first admin user and automatically logs them in.

## Recent Fixes (2025-12-23)

- ✅ Changed from `auth:sanctum` to `auth:api` for JWT compatibility
- ✅ Fixed Authenticate middleware to return JSON 401 responses
- ✅ Fixed Exception Handler for proper unauthenticated responses
- ✅ Fixed health check Redis dependency issue

## Development

### Running Locally

```bash
cd services/auth-service
composer install
php artisan migrate
php artisan db:seed
php artisan serve --port=8081
```

### Testing

```bash
php artisan test
php artisan test --filter=AuthenticationTest
```

## Health Check

```bash
curl http://localhost:8081/api/health
```

**Response:**
```json
{
  "status": "healthy",
  "service": "auth-service",
  "timestamp": "2025-12-23T03:00:00.000000Z",
  "checks": {
    "database": "ok"
  }
}
```

## Troubleshooting

**401 Unauthenticated errors**:
- Ensure you're including the JWT token in the `Authorization` header
- Check if token has expired (60 minute TTL)
- Verify you're using `Bearer` prefix: `Authorization: Bearer {token}`

**User creation fails**:
- Check if email already exists
- Verify password meets minimum requirements (6 characters)
- Ensure password_confirmation matches password

**Role assignment fails**:
- Verify both user and role exist
- Check if user already has the role
- Ensure you have admin permissions
