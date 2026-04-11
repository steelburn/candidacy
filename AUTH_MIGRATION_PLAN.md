# Authentication Migration Plan: Sanctum to Tymon JWT

**Document Version:** 1.0  
**Date:** 2026-03-19  
**Status:** ✅ Completed

---

## Migration Completion Summary

**Completed On:** 2026-03-19

### Summary of Changes

The JWT authentication migration has been successfully completed. The following changes were made:

| Component | Change |
|-----------|--------|
| **Auth Service** | Installed `tymon/jwt-auth` package, configured JWT guard |
| **All Services** | Updated `config/auth.php` to use `auth:api` guard with JWT |
| **User Models** | Implemented `JWTSubject` interface in all User models |
| **API Gateway** | Added JWT validation and header injection (`X-User-ID`, `X-Tenant-ID`) |
| **Frontend** | Updated to use Bearer token authentication |
| **Documentation** | Updated ARCHITECTURE.md, CONFIGURATION.md, DEPLOYMENT.md, QUICKSTART.md, README.md |

### What Was Done

1. ✅ Installed `tymon/jwt-auth` package in all services
2. ✅ Created JWT configuration (`config/jwt.php`) in all services
3. ✅ Updated User models to implement `JWTSubject` interface
4. ✅ Configured `auth:api` guard as default
5. ✅ Updated API Gateway for JWT validation and claims extraction
6. ✅ Updated frontend authentication to use Bearer tokens
7. ✅ Removed Sanctum dependencies and references
8. ✅ Updated all documentation files
9. ✅ Updated `.env.example` with JWT configuration variables

### JWT Configuration

- **Package**: `tymon/jwt-auth` v1.x
- **Guard**: `auth:api`
- **Token TTL**: Configurable via `JWT_TTL` (default: 60 minutes)
- **Refresh TTL**: Configurable via `JWT_REFRESH_TTL` (default: 2 weeks)

---

**Note:** This document serves as historical reference. All authentication now uses JWT.

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Pre-Migration Checklist](#2-pre-migration-checklist)
3. [Phase-by-Phase Execution Plan](#3-phase-by-phase-execution-plan)
4. [Detailed Code Changes](#4-detailed-code-changes)
5. [Testing and Verification](#5-testing-and-verification)
6. [Rollback Procedures](#6-rollback-procedures)
7. [Post-Migration Cleanup](#7-post-migration-cleanup)

---

## 1. Executive Summary

### Current Issues with Sanctum

The current authentication implementation using Laravel Sanctum has the following limitations:

| Issue | Impact | Reason for Migration |
|-------|--------|----------------------|
| **Stateful cookie dependency** | Not suitable for pure API/microservices architecture | Sanctum relies heavily on CSRF cookies, which adds complexity for SPA and mobile clients |
| **Token management** | Limited token control | Sanctum tokens don't support fine-grained expiration or refresh without additional configuration |
| **Scalability** | Session-based state | Stateful authentication doesn't scale well across multiple auth-service instances |
| **Mobile/SPA compatibility** | Extra complexity | Requires CORS configuration and stateful domain setup |
| **Token revocation** | Limited control | Personal access tokens table adds complexity for token management |

### Why Tymon JWT?

- **Stateless authentication**: Perfect for microservices and API-first architecture
- **Better token control**: Access and refresh tokens with configurable expiration
- **Industry standard**: JWT is the industry standard for API authentication
- **Scalability**: No session storage required, works across multiple service instances
- **Mobile-ready**: Native support for Bearer token authentication

### Migration Scope

- **Primary Service**: [`auth-service/`](auth-service/)
- **Frontend Clients**: [`frontend/web-app/`](frontend/web-app/), [`frontend/applicant-web-app/`](frontend/applicant-web-app/)
- **Database**: Users table, Personal Access Tokens table (to be deprecated)
- **Configuration Files**: Multiple Laravel configuration files

---

## 2. Pre-Migration Checklist

### 2.1 Git & Backup

```bash
# [ ] Create a new branch for migration
git checkout -b feature/migrate-sanctum-to-jwt

# [ ] Create a backup tag
git tag backup-pre-jwt-migration-$(date +%Y%m%d)

# [ ] Backup database (execute in your database container)
docker exec candidacy-db mysqldump -u root -p candidatedb > backup_pre_jwt_$(date +%Y%m%d).sql

# [ ] Backup environment files
cp auth-service/.env auth-service/.env.backup.$(date +%Y%m%d)
```

### 2.2 Current State Verification

```bash
# [ ] Verify Sanctum is currently working
curl -X GET http://localhost:8081/api/user -H "Authorization: Bearer <test_token>"

# [ ] Document current API routes using auth middleware
grep -r "auth:sanctum\|auth:api\|auth:jwt" auth-service/routes/

# [ ] Check current users table structure
docker exec candidacy-db mysql -u root -p candidatedb -e "DESCRIBE users;"

# [ ] Note down any active tokens (for user notification post-migration)
docker exec candidacy-db mysql -u root -p candidatedb -e "SELECT COUNT(*) FROM personal_access_tokens;"
```

### 2.3 Pre-Flight Checks

- [ ] All tests pass on current codebase
- [ ] Database backup is verified and restorable
- [ ] Access to production/staging environments (if applicable)
- [ ] Communication plan for users (tokens will be invalidated)
- [ ] Downtime window scheduled (recommended: low-traffic period)

---

## 3. Phase-by-Phase Execution Plan

### Phase 1: Preparation & Package Installation

| Step | Action | Estimated Time |
|------|--------|----------------|
| 1.1 | Install tymon/jwt-auth package | 5 minutes |
| 1.2 | Publish JWT configuration | 2 minutes |
| 1.3 | Generate JWT secret | 2 minutes |
| 1.4 | Update User model | 5 minutes |
| 1.5 | Configure auth guard | 5 minutes |

### Phase 2: Middleware & Configuration Updates

| Step | Action | Estimated Time |
|------|--------|----------------|
| 2.1 | Update API routes middleware | 10 minutes |
| 2.2 | Configure CORS for JWT | 5 minutes |
| 2.3 | Update authentication middleware | 5 minutes |
| 2.4 | Add JWT authentication to Kernel | 5 minutes |

### Phase 3: Controller Updates

| Step | Action | Estimated Time |
|------|--------|----------------|
| 3.1 | Create AuthController (if needed) | 15 minutes |
| 3.2 | Implement login endpoint with JWT | 10 minutes |
| 3.3 | Implement logout endpoint | 5 minutes |
| 3.4 | Implement token refresh endpoint | 5 minutes |
| 3.5 | Update user info endpoint | 5 minutes |

### Phase 4: Frontend Updates

| Step | Action | Estimated Time |
|------|--------|----------------|
| 4.1 | Update API service configuration | 10 minutes |
| 4.2 | Add token refresh logic | 15 minutes |
| 4.3 | Handle 401 responses | 5 minutes |
| 4.4 | Test authentication flow | 20 minutes |

### Phase 5: Database Cleanup

| Step | Action | Estimated Time |
|------|--------|----------------|
| 5.1 | Create migration to handle tokens | 10 minutes |
| 5.2 | Run migrations | 5 minutes |
| 5.3 | Verify database schema | 5 minutes |

### Phase 6: Testing & Verification

| Step | Action | Estimated Time |
|------|--------|----------------|
| 6.1 | Run unit tests | 10 minutes |
| 6.2 | Run integration tests | 15 minutes |
| 6.3 | Manual API testing | 20 minutes |
| 6.4 | Frontend E2E testing | 30 minutes |

---

## 4. Detailed Code Changes

### 4.1 composer.json

**File**: [`auth-service/composer.json`](auth-service/composer.json)

**Change**: Replace Sanctum with JWT package

```json
// BEFORE:
"laravel/sanctum": "^3.3",

// AFTER:
"tymon/jwt-auth": "^2.0",
```

**Command**:
```bash
cd auth-service && composer remove laravel/sanctum && composer require tymon/jwt-auth:^2.0
```

### 4.2 User Model

**File**: [`auth-service/app/Models/User.php`](auth-service/app/Models/User.php:1-62)

The User model already has partial JWT support. Update to remove Sanctum trait:

```php
// BEFORE:
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

// AFTER:
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;
```

**Note**: The JWT methods (`getJWTIdentifier()`, `getJWTCustomClaims()`) are already implemented.

### 4.3 Authentication Configuration

**File**: [`auth-service/config/auth.php`](auth-service/config/auth.php:38-47)

Verify the API guard uses JWT:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

### 4.4 JWT Configuration

**Create new file**: [`auth-service/config/jwt.php`](auth-service/config/jwt.php)

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    |
    | This key is used to sign the JWT tokens. Generate one using:
    | php artisan jwt:secret
    |
    */
    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Keys
    |--------------------------------------------------------------------------
    |
    | Public and private keys for JWT signing. Required for RS256 algorithm.
    |
    */
    'keys' => [
        'public' => env('JWT_PUBLIC_KEY'),
        'private' => env('JWT_PRIVATE_KEY'),
        'passphrase' => env('JWT_PASSPHRASE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT Time to Live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token will be valid for.
    | Defaults to 1 hour.
    |
    */
    'ttl' => env('JWT_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Refresh Token Time to Live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the refresh token will be 
    | valid for. Defaults to 2 weeks.
    |
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | Specify the algorithm used to sign the token.
    | Supported: HS256, HS384, HS512, RS256, RS384, RS512, ES256, ES384, ES512
    |
    */
    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    |
    | Specify which claims are required to be present in the token.
    |
    */
    'required_claims' => ['iss', 'iat', 'exp', 'nbf', 'sub', 'jti'],

    /*
    |--------------------------------------------------------------------------
    | Persistent Claims
    |--------------------------------------------------------------------------
    |
    | Claims that will be persisted in the refresh token.
    |
    */
    'persistent_claims' => [],

    /*
    |--------------------------------------------------------------------------
    | Lock Subject
    |--------------------------------------------------------------------------
    |
    | When enabled, the 'sub' claim will be set to the user's ID.
    |
    */
    'lock_subject' => true,

    /*
    |--------------------------------------------------------------------------
    | Leeway
    |--------------------------------------------------------------------------
    |
    | This option gives you some flexibility to use JWT with date comparison.
    | It represents the number of seconds that should be allowed as a "leeway"
    | when dealing with "exp", "iat", "nbf" claims.
    |
    */
    'leeway' => env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Enabled
    |--------------------------------------------------------------------------
    |
    | When enabled, tokens cannot be used once they're expired or invalidated.
    |
    */
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Grace Period
    |--------------------------------------------------------------------------
    |
    | The grace period (in seconds) after which a token is considered expired
    | and will be added to the blacklist. This allows for clock skew tolerance.
    |
    */
    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    /*
    |--------------------------------------------------------------------------
    | Decrypt
    |--------------------------------------------------------------------------
    |
    | When enabled, tokens will be decrypted automatically.
    |
    */
    'decrypt' => env('JWT_DECRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Specify the various JWT providers used within the application.
    |
    */
    'providers' => [
        'jwt' => Tymon\JWTAuth\Providers\JWT\Lcobucci::class,
        'auth' => Tymon\JWTAuth\Providers\Auth\Illuminate::class,
        'storage' => Tymon\JWTAuth\Providers\Storage\Illuminate::class,
    ],
];
```

### 4.5 API Routes

**File**: [`auth-service/routes/api.php`](auth-service/routes/api.php:1-19)

Replace Sanctum middleware with JWT:

```php
// BEFORE:
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// AFTER:
Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    
    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});
```

### 4.6 HTTP Kernel

**File**: [`auth-service/app/Http/Kernel.php`](auth-service/app/Http/Kernel.php:55-68)

Add JWT middleware alias:

```php
// Add to $middlewareAliases array:
'jwt' => \Tymon\JWTAuth\Http\Middleware\Authenticate::class,
'jwt.auth' => \Tymon\JWTAuth\Http\Middleware\Authenticate::class,
'jwt.refresh' => \Tymon\JWTAuth\Http\Middleware\RefreshToken::class,
```

### 4.7 Authentication Middleware

**File**: [`auth-service/app/Http/Middleware/Authenticate.php`](auth-service/app/Http/Middleware/Authenticate.php:1-23)

Update to return proper JWT error responses:

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API requests (including JWT auth), always return JSON response
        // rather than redirecting to a login page
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        return route('login');
    }

    /**
     * Handle unauthenticated requests.
     */
    protected function unauthenticated($request, array $guards)
    {
        throw new \Illuminate\Auth\AuthenticationException(
            'Unauthenticated.',
            $guards,
            null
        );
    }
}
```

### 4.8 Auth Service Provider

**File**: [`auth-service/app/Providers/AuthServiceProvider.php`](auth-service/app/Providers/AuthServiceProvider.php:1-26)

Register JWT facade:

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Configure JWT auth guard
        Auth::resolveUsersUsing(function ($guard = null) {
            return auth($guard)->user();
        });
    }
}
```

### 4.9 Auth Controller

**Create new file**: [`auth-service/app/Http/Controllers/AuthController.php`](auth-service/app/Http/Controllers/AuthController.php)

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Login user and create JWT token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'error' => 'Invalid credentials',
                'message' => 'The provided credentials do not match our records.'
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 201);
    }

    /**
     * Get authenticated user.
     */
    public function me(): JsonResponse
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Logout user (invalidate token).
     */
    public function logout(): JsonResponse
    {
        try {
            auth('api')->logout();
            return response()->json([
                'message' => 'Successfully logged out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Failed to logout',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh JWT token.
     */
    public function refresh(): JsonResponse
    {
        try {
            return $this->respondWithToken(auth('api')->refresh());
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Token refresh failed',
                'message' => 'Could not refresh token. Please login again.'
            ], 401);
        }
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }
}
```

### 4.10 CORS Configuration

**File**: [`auth-service/config/cors.php`](auth-service/config/cors.php)

Update to allow JWT Bearer tokens:

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Authorization'],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

### 4.11 Environment Variables

**File**: [`auth-service/.env`](auth-service/.env)

Add/modify these variables:

```bash
# JWT Configuration
JWT_SECRET=
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256
JWT_BLACKLIST_ENABLED=true
JWT_LEEWAY=0

# CORS (if not already set)
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000
```

### 4.12 Frontend API Service

**File**: [`frontend/web-app/src/services/api.js`](frontend/web-app/src/services/api.js:1-60)

The frontend already uses Bearer token pattern. Update to handle JWT-specific responses:

```javascript
import axios from 'axios'

const API_GATEWAY_URL = import.meta.env.VITE_API_GATEWAY_URL || 'http://localhost:8080'

const api = axios.create({
    baseURL: API_GATEWAY_URL,
    timeout: 130000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
})

// Request interceptor
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token')
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }
        return config
    },
    (error) => {
        return Promise.reject(error)
    }
)

// Response interceptor with JWT refresh logic
api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config

        // Handle token expiration
        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true

            try {
                const refreshToken = localStorage.getItem('refresh_token')
                if (refreshToken) {
                    const response = await axios.post(`${API_GATEWAY_URL}/auth/refresh`, {
                        refresh_token: refreshToken
                    })
                    
                    const { access_token } = response.data
                    localStorage.setItem('token', access_token)
                    
                    originalRequest.headers.Authorization = `Bearer ${access_token}`
                    return api(originalRequest)
                }
            } catch (refreshError) {
                // Refresh failed, redirect to login
                localStorage.removeItem('token')
                localStorage.removeItem('refresh_token')
                localStorage.removeItem('user')
                window.location.href = '/login'
            }
        }

        // Handle other 401 errors (invalid token, etc.)
        if (error.response?.status === 401) {
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            if (!originalRequest._skipAuthRedirect) {
                window.location.href = '/login'
            }
        }
        
        return Promise.reject(error)
    }
)

// Auth API - Updated for JWT
export const authAPI = {
    login: (credentials) => api.post('/auth/login', credentials),
    register: (data) => api.post('/auth/register', data),
    logout: () => {
        const config = { _skipAuthRedirect: true }
        return api.post('/auth/logout', {}, config)
    },
    me: () => api.get('/auth/me'),
    refresh: () => api.post('/auth/refresh'),
    // ... rest of the API
}

export default api
```

---

## 5. Testing and Verification

### 5.1 Unit Tests

Run existing tests to ensure nothing is broken:

```bash
cd auth-service
php artisan test --filter=Auth
```

### 5.2 API Endpoint Testing

Test all authentication endpoints:

```bash
# Base URL
BASE_URL="http://localhost:8081/api"

# 1. Test Registration
curl -X POST "${BASE_URL}/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# 2. Test Login
curl -X POST "${BASE_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'

# 3. Test Protected Route (replace TOKEN with actual token)
curl -X GET "${BASE_URL}/auth/user" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"

# 4. Test Token Refresh
curl -X POST "${BASE_URL}/auth/refresh" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"

# 5. Test Logout
curl -X POST "${BASE_URL}/auth/logout" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 5.3 Expected Responses

**Login Success**:
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

**Login Failure**:
```json
{
  "error": "Invalid credentials",
  "message": "The provided credentials do not match our records."
}
```

**Protected Route**:
```json
{
  "id": 1,
  "name": "Test User",
  "email": "test@example.com",
  "email_verified_at": null,
  "created_at": "2026-03-19T00:00:00.000000Z",
  "updated_at": "2026-03-19T00:00:00.000000Z"
}
```

### 5.4 Frontend Testing Checklist

- [ ] User can register successfully
- [ ] User can login and receives token
- [ ] Token is stored in localStorage
- [ ] Subsequent requests include Bearer token
- [ ] Token refresh works automatically
- [ ] Logout clears token and redirects to login
- [ ] Invalid/expired token shows login page
- [ ] Error messages display properly

---

## 6. Rollback Procedures

### 6.1 If Migration Fails Completely

```bash
# 1. Revert git changes
git checkout backup-pre-jwt-migration-YYYYMMDD

# 2. Restore composer.json
git checkout HEAD -- auth-service/composer.json

# 3. Restore database from backup
docker exec -i candidacy-db mysql -u root -p candidatedb < backup_pre_jwt_YYYYMMDD.sql

# 4. Reinstall composer packages
cd auth-service
composer install

# 5. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 6.2 Partial Rollback (Keep JWT, Revert Specific Changes)

```bash
# Revert specific file
git checkout HEAD -- auth-service/routes/api.php

# Clear route cache
php artisan route:clear
```

### 6.3 Emergency Token Restoration

If users need to be restored to their previous tokens (not applicable for this migration as tokens will be new):

```bash
# Users will need to re-login after migration
# This is expected behavior and users should be notified in advance
```

---

## 7. Post-Migration Cleanup

### 7.1 Remove Sanctum Components

```bash
# Remove Sanctum from composer (if not already done)
cd auth-service
composer remove laravel/sanctum

# Optional: Drop personal_access_tokens table (no longer needed)
php artisan make:migration drop_personal_access_tokens_table
```

**Migration file content**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }

    public function down(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
};
```

### 7.2 Update Documentation

- [ ] Update API documentation with new JWT endpoints
- [ ] Update ARCHITECTURE.md
- [ ] Update README.md authentication section

### 7.3 Monitoring & Verification

```bash
# Monitor authentication logs
tail -f auth-service/storage/logs/laravel.log | grep -i auth

# Check for authentication errors in production
curl -X GET "http://localhost:8081/api/auth/user" \
  -H "Authorization: Bearer invalid_token" \
  -H "Accept: application/json"
```

### 7.4 Final Checklist

- [ ] All tests passing
- [ ] All users can login with new JWT system
- [ ] Token refresh working correctly
- [ ] Logout invalidating tokens
- [ ] No authentication errors in logs
- [ ] Frontend applications working
- [ ] CORS configured for all clients
- [ ] JWT secret properly secured in production
- [ ] Old tokens invalidated

---

## Appendix A: Quick Reference Commands

```bash
# Generate JWT secret
cd auth-service && php artisan jwt:secret

# Verify JWT secret is set
php artisan tinker
>>> config('jwt.secret')

# Test JWT auth manually
php artisan tinker
>>> auth()->login(App\Models\User::first())
>>> auth()->getToken()->get()
```

## Appendix B: Troubleshooting

| Issue | Solution |
|-------|----------|
| Token expired during request | Implement refresh token logic in frontend |
| CORS errors | Check `CORS_ALLOWED_ORIGINS` in .env |
| 401 on valid token | Verify JWT_SECRET is set in .env |
| Login returns 401 | Check password hash and credentials |
| Middleware not found | Add JWT middleware to Kernel.php |

## Appendix C: Rollback Checklist

- [ ] Reverted composer.json
- [ ] Restored database backup
- [ ] Cleared all caches
- [ ] Verified Sanctum working
- [ ] Notified users of rollback

---

**Document End**

*This document should be reviewed and updated as the migration progresses.*
