# Tenant Service

Multi-tenant management service for organizing multiple organizations on a single platform instance.

## Overview

- **Port**: 8092
- **Database**: `candidacy_tenant`
- **Framework**: Laravel 10

## Features

- ✅ Tenant (organization) CRUD operations
- ✅ Tenant user management and invitations
- ✅ Tenant settings and preferences
- ✅ API key management for integrations
- ✅ Tenant isolation enforcement

## API Endpoints

```http
GET    /api/tenants               # List all tenants (admin only)
POST   /api/tenants               # Create new tenant
GET    /api/tenants/{id}          # Get tenant details
PUT    /api/tenants/{id}          # Update tenant
DELETE /api/tenants/{id}          # Delete tenant

GET    /api/tenants/{id}/users    # List tenant users
POST   /api/tenants/{id}/invite  # Invite user to tenant

GET    /api/tenant-users          # Current user's tenant memberships
PUT    /api/tenant-users/{id}    # Update membership (role)

GET    /api/invitations           # List pending invitations
POST   /api/invitations/{token}  # Accept invitation

GET    /api/api-keys              # List API keys
POST   /api/api-keys              # Create API key
DELETE /api/api-keys/{id}         # Revoke API key
```

## Tenant Data Model

### Tenant
- `id` - Unique identifier
- `name` - Organization name
- `slug` - URL-friendly identifier
- `domain` - Custom domain (optional)
- `settings` - JSON configuration
- `created_at`, `updated_at`

### Tenant User
- `id` - Unique identifier
- `tenant_id` - Associated tenant
- `user_id` - User from auth service
- `role` - Role within tenant (owner, admin, member)
- `created_at`, `updated_at`

### Invitation
- `id` - Unique identifier
- `tenant_id` - Inviting tenant
- `email` - Invitee email
- `role` - Role to grant
- `token` - Unique acceptance token
- `expires_at` - Expiration date
- `created_at`, `updated_at`

### API Key
- `id` - Unique identifier
- `tenant_id` - Owner tenant
- `name` - Descriptive name
- `key_hash` - Hashed API key (secure)
- `last_used_at` - Last usage timestamp
- `expires_at` - Optional expiration
- `created_at`, `updated_at`

## Multitenancy Architecture

The platform uses **shared database with tenant_id column** approach:

```
┌─────────────────────────────────────────────────────────┐
│                    Tenant Isolation                      │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────┐    ┌──────────────────────────────────┐  │
│  │  Tenant  │───▶│           Services               │  │
│  │ Service  │    ├──────────────────────────────────┤  │
│  └──────────┘    │  • Auth Service                  │  │
│       │          │  • Candidate Service             │  │
│       ▼          │  • Vacancy Service                │  │
│  ┌──────────┐    │  • ...                           │  │
│  │  Tenant  │    │                                  │  │
│  │ Context  │───▶│  All queries filtered by         │  │
│  │Middleware│    │  tenant_id automatically        │  │
│  └──────────┘    └──────────────────────────────────┘  │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

## Tenant Resolution

Tenant is resolved from:

1. **JWT Claims**: `tenant_id` claim in authentication token
2. **HTTP Header**: `X-Tenant-ID` header (API access)
3. **Subdomain**: `{tenant}.yourdomain.com` (future)

## Development

```bash
# Sync schema
make dbml-sql

# Run service
docker-compose up -d tenant-service
```

## Related Documentation

- [MULTITENANCY.md](../MULTITENANCY.md) - Full multitenancy guide
- [DATABASE.md](../DATABASE.md) - Database schema
