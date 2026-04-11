# Authentication Requirements Specifications

## Overview
This document defines the authentication requirements for the Candidacy recruitment platform, covering user authentication, authorization, session management, and security protocols across all services.

---

## 1. Authentication Methods

### 1.1 Email/Password Authentication
- **Primary auth method** for all user types (HR staff, recruiters, candidates)
- Support secure password hashing (bcrypt with minimum 12 rounds)
- Implement "forgot password" with time-limited reset tokens (24-hour expiration)
- Enforce password strength requirements:
  - Minimum 8 characters
  - At least one uppercase letter
  - At least one lowercase letter
  - At least one number
  - At least one special character

### 1.2 OAuth 2.0 (Future Enhancement)
- Support Google OAuth for candidate SSO
- Support Microsoft OAuth for enterprise HR users
- Implementation roadmap for Phase 2+

### 1.3 Multi-Factor Authentication (MFA)
- Optional MFA for sensitive roles (HR admins, recruiters)
- Support TOTP (Time-based One-Time Password) via authenticator apps
- Backup codes generation and storage

---

## 2. User Roles & Authorization

### 2.1 Role Definitions
| Role | Scope | Permissions |
|------|-------|-------------|
| **Super Admin** | Platform | All system access, service management |
| **Tenant Admin** | Organization | Tenant configuration, user management |
| **Recruiter** | Tenant | Vacancy management, candidate evaluation |
| **HR Staff** | Tenant | Reporting, analytics, offer management |
| **Candidate** | Self | Profile management, applications, interview prep |

### 2.2 Authorization Framework
- **Tenant Isolation**: Users can only access data within their tenant
- **RBAC (Role-Based Access Control)**: Routes and resources protected by role
- **ABAC (Attribute-Based Access Control)**: Fine-grained permissions (optional, Phase 2)

---

## 3. Session Management

### 3.1 Session Creation
- Create JWT tokens on successful login
- Token payload includes: `user_id`, `tenant_id`, `role`, `iat`, `exp`
- Short-lived access tokens (15 minutes)
- Long-lived refresh tokens (7 days) stored in HTTP-only cookies

### 3.2 Token Security
- **HTTP-only cookies** for refresh tokens (prevent XSS attacks)
- **CSRF protection** on state-changing requests
- **Secure flag** on all cookies (HTTPS only)
- **SameSite=Strict** cookie policy

### 3.3 Session Expiration
- Access token expiration: 15 minutes
- Refresh token expiration: 7 days
- Idle session timeout: 30 minutes (optional, configurable)
- Force logout on suspicious activity

---

## 4. API Gateway Authentication

### 4.1 Token Validation
- All API requests require valid access token in `Authorization: Bearer <token>` header
- Gateway validates token signature and expiration
- Rejects expired or invalid tokens with 401 Unauthorized

### 4.2 Service-to-Service Authentication
- Services authenticate via shared API keys or JWT tokens
- Service tokens scoped to specific service operations
- No cross-tenant data access without proper authorization

---

## 5. Password Management

### 5.1 Password Storage
- Hash passwords using bcrypt (cost factor: 12)
- Never store plaintext passwords
- Use Laravel's `Hash::make()` for hashing

### 5.2 Password Reset Flow
1. User requests password reset with email
2. Generate secure token (random 64-char string)
3. Send reset link valid for 24 hours
4. User confirms new password
5. Invalidate all existing sessions
6. Log password change event

### 5.3 Password Change
- Require current password verification
- Invalidate refresh tokens after password change (force re-login)
- Send security notification email

---

## 6. Login & Registration

### 6.1 User Registration
- **Candidates**: Self-registration via applicant portal
  - Verify email (confirmation link)
  - Complete profile before applying
- **HR/Recruiters**: Invitation-only by tenant admin
  - Auto-generate temporary password
  - Require password change on first login

### 6.2 Login Flow
1. Submit email and password to Auth Service
2. Validate credentials
3. Check account status (active/suspended/locked)
4. Generate JWT tokens
5. Return access token + refresh token (HTTP-only cookie)
6. Log login event with timestamp and IP address

### 6.3 Failed Login Attempts
- Track failed attempts per account
- Lock account after 5 failed attempts (30-minute lockout)
- Send security alert email on lockout
- Allow immediate unlock via password reset

### 6.4 Logout
- Invalidate refresh token
- Clear session data
- Log logout event

---

## 7. Tenant Isolation & Multi-Tenancy

### 7.1 Request Context
- Extract `tenant_id` from JWT token or request header
- Validate tenant access in middleware
- Prevent accessing other tenants' data

### 7.2 Database Isolation
- Tenant-specific databases or schema-based isolation
- Query filters ensure tenant_id match
- Foreign key constraints prevent cross-tenant access

### 7.3 Cross-Tenant Prevention
- Reject requests with mismatched tenant_id
- Log unauthorized cross-tenant attempts
- Alert admins of suspicious activity

---

## 8. Security Requirements

### 8.1 HTTPS/TLS
- All auth endpoints require HTTPS (TLS 1.2+)
- SSL certificate validation in production
- HSTS headers enforced

### 8.2 API Security
- **Rate limiting**: Max 10 login attempts per minute per IP
- **CORS**: Restrict to approved domains only
- **X-Frame-Options**: DENY (prevent clickjacking)
- **Content-Security-Policy**: Strict headers

### 8.3 Audit Logging
- Log all auth events: login, logout, password change, token refresh
- Include: user_id, timestamp, IP address, user agent
- Retention: Minimum 90 days
- Searchable audit trail in admin panel

### 8.4 Suspicious Activity Detection
- Multiple failed logins from different IPs
- Impossible travel detection (login from different countries in short time)
- Unusual login times for the user
- Device fingerprinting (future enhancement)

---

## 9. Token Refresh

### 9.1 Refresh Token Flow
1. Client sends refresh token (HTTP-only cookie)
2. Auth Service validates token
3. Check token hasn't been revoked or tampered with
4. Generate new access token
5. Optionally rotate refresh token
6. Return new access token

### 9.2 Token Rotation
- Optional refresh token rotation (security best practice)
- Old token invalidated upon new token generation
- Detect token replay attacks (use refresh token once)

---

## 10. Authentication Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/auth/register` | POST | User registration (candidates) |
| `/api/auth/login` | POST | User login |
| `/api/auth/logout` | POST | User logout |
| `/api/auth/refresh` | POST | Refresh access token |
| `/api/auth/forgot-password` | POST | Request password reset |
| `/api/auth/reset-password` | POST | Reset password with token |
| `/api/auth/change-password` | POST | Change current password |
| `/api/auth/verify-email` | POST | Verify email address |
| `/api/auth/me` | GET | Get current user info |
| `/api/auth/mfa/setup` | POST | Setup MFA (future) |

---

## 11. Error Handling

### 11.1 Auth Error Codes
| Code | Status | Message |
|------|--------|---------|
| 401 | Unauthorized | Invalid credentials |
| 401 | Unauthorized | Token expired |
| 403 | Forbidden | Insufficient permissions |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Auth service error |

### 11.2 User-Facing Messages
- Generic error messages (avoid revealing user existence)
- Specific validation errors on registration/password change
- Clear instructions for account recovery

---

## 12. Implementation Checklist

- [ ] Auth Service scaffolding in Laravel 10
- [ ] JWT token generation and validation
- [ ] Password hashing and verification
- [ ] Email verification system
- [ ] Password reset flow
- [ ] Login/logout endpoints
- [ ] Tenant isolation middleware
- [ ] Rate limiting middleware
- [ ] Audit logging system
- [ ] CORS and security headers
- [ ] Unit & integration tests
- [ ] API documentation (OpenAPI/Swagger)
- [ ] Admin auth dashboard
- [ ] Security monitoring and alerts
