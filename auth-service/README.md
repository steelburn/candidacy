# Auth Service

Authentication and authorization service for the Candidacy recruitment platform.

## Purpose

The Auth Service handles user authentication, session management, and role-based access control (RBAC) for the entire platform. It provides JWT/session-based authentication and manages user roles and permissions.

## Key Features

- **User Authentication**: Login/logout with JWT or session tokens
- **User Registration**: New user account creation
- **Role-Based Access Control**: Five distinct user roles with different permissions
- **Password Management**: Secure password hashing and reset functionality
- **Session Management**: Token generation and validation
- **User Profile**: User information management

## Technology Stack

- **Framework**: Laravel 11
- **Database**: MySQL (candidacy_auth)
- **Port**: 8081 (container internal: 8080)
- **Authentication**: Laravel Sanctum / JWT
- **Dependencies**: Redis for session storage

## User Roles

### Admin
- Full system access
- Configuration management
- User management
- All module access

### HR Manager
- Manage vacancies
- View all candidates
- Configure onboarding workflows
- Access all reports
- Manage offers

### Recruiter
- Manage candidates
- Schedule interviews
- View matches
- Upload CVs
- Create vacancies

### Interviewer
- View assigned interviews
- Submit interview feedback
- View candidate profiles (limited)

### Viewer
- Read-only access
- View reports and dashboards
- No create/update/delete permissions

## API Endpoints

### Register
```
POST /api/register
```
**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "recruiter"
}
```

### Login
```
POST /api/login
```
**Request Body:**
```json
{
  "email": "admin@candidacy.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "token": "jwt_token_here",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@candidacy.com",
    "role": "admin"
  }
}
```

### Get Current User
```
GET /api/me
```
Requires authentication token.

### Logout
```
POST /api/logout
```
Invalidates the current session/token.

### Update Profile
```
PUT /api/profile
```
**Request Body:**
```json
{
  "name": "Updated Name",
  "email": "newemail@example.com"
}
```

## Database Schema

### Users Table
- `id` - Primary key
- `name` - User full name
- `email` - Email address (unique)
- `password` - Hashed password
- `role` - User role (admin, hr_manager, recruiter, interviewer, viewer)
- `email_verified_at` - Email verification timestamp
- `remember_token` - Remember me token
- `created_at` - Timestamp
- `updated_at` - Timestamp

## Setup

### Run Migrations
```bash
docker-compose exec auth-service php artisan migrate
```

### Seed Default Users
```bash
docker-compose exec auth-service php artisan db:seed
```

This creates the default admin user:
- **Email**: admin@candidacy.com
- **Password**: password123

**⚠️ Change these credentials in production!**

## Usage Examples

### Frontend Login Flow
```javascript
const response = await axios.post('http://localhost:9080/api/auth/login', {
  email: 'admin@candidacy.com',
  password: 'password123'
});

const token = response.data.token;
// Store token and use in subsequent requests
```

### Authenticated Requests
```javascript
axios.get('http://localhost:9080/api/auth/me', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

## Development

### View Routes
```bash
docker-compose exec auth-service php artisan route:list
```

### Create New User
```bash
docker-compose exec auth-service php artisan tinker
>>> User::create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password'), 'role' => 'recruiter']);
```

### Run Tests
```bash
docker-compose exec auth-service php artisan test
```

## Security Features

- **Password Hashing**: Bcrypt hashing for all passwords
- **Token Expiration**: Configurable JWT token expiration
- **Rate Limiting**: Login attempt rate limiting
- **CORS**: Configured for frontend access
- **Session Security**: Secure session cookies in production

## Environment Variables

- `DB_HOST` - MySQL host (default: mysql)
- `DB_DATABASE` - Database name (candidacy_auth)
- `DB_USERNAME` - Database user
- `DB_PASSWORD` - Database password
- `REDIS_HOST` - Redis host for sessions
- `JWT_SECRET` - Secret key for JWT signing
- `SESSION_LIFETIME` - Session duration in minutes

## Integration

The Auth Service is consumed by:
- **API Gateway**: Routes all `/api/auth/*` requests here
- **Frontend**: Login page and authentication flows
- **All Services**: Token validation for protected endpoints

## Notes

- All passwords must be at least 8 characters
- Email addresses must be unique
- Role changes require admin privileges
- Tokens should be stored securely in frontend (httpOnly cookies recommended)
- Use HTTPS in production for secure token transmission
