# Candidacy - Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added - 2026-03-19

#### JWT Authentication Migration (Phase 1-5 Complete)

**Migration from Laravel Sanctum to Tymon JWT Authentication**

All microservices and API Gateway have been migrated from Laravel Sanctum to Tymon JWT for stateless API authentication.

**Phase 1 — Auth Service**
- Removed `laravel/sanctum` from composer.json
- Added `tymon/jwt-auth` package
- Updated User model to implement JWTSubject interface
- Updated auth.php config with JWT guard

**Phase 2 — Microservices Migration**
- Migrated all 12 microservices from Sanctum to JWT:
  - admin-service, ai-service, candidate-service, document-parser-service
  - interview-service, matching-service, notification-service
  - offer-service, onboarding-service, reporting-service
  - vacancy-service, tenant-service
- Added `config/jwt.php` configuration files to all services
- Updated User models to implement JWTSubject
- Added JWT API guard to auth.php
- Removed `config/sanctum.php` from all services

**Phase 3 — API Gateway**
- Updated GatewayController for JWT token validation
- Added jwt.php configuration
- Removed sanctum.php configuration

**Phase 4 — Frontend Integration**
- Updated frontend/web-app/src/services/api.js for JWT authentication
- JWT token stored in localStorage
- Bearer token included in Authorization header

**Phase 5 — Final Cleanup**
- Completed tenant-service migration
- Created release tag v2.0.0-jwt-migration-complete

**Breaking Changes**
- All API endpoints now require JWT Bearer token authentication
- Session-based authentication no longer supported for API routes
- Use `GET /auth/login` to obtain JWT token

### Added - 2026-02-23

#### Multitenancy Implementation (Phases 1–3)

**Phase 1 — Auth Service: JWT Tenant Claims**
- **JWT claims** — `getJWTCustomClaims()` now embeds `tenant_id`, `user_id`, and `roles` in every token
- **Switch-tenant endpoint** — `POST /auth/switch-tenant` validates user belongs to requested tenant, updates `current_tenant_id`, invalidates old token, and issues a fresh JWT
- **Migration** — `2026_02_23_000001_add_current_tenant_id_to_users_table.php` safely adds `current_tenant_id` column to `users` table if missing

**Phase 2 — Business Services: Data Isolation**
- **`BelongsToTenant` trait** applied to 13 models across 7 services:
  - `Candidate`, `CvFile`, `CvParsingJob` (candidate-service)
  - `Vacancy`, `VacancyQuestion` (vacancy-service)
  - `CandidateMatch` (matching-service)
  - `Interview`, `InterviewFeedback` (interview-service)
  - `Offer` (offer-service)
  - `OnboardingChecklist` (onboarding-service)
  - `NotificationTemplate`, `NotificationLog` (notification-service)
- **Idempotent migrations** adding `tenant_id` + index to all affected tables (safe to re-run)
- **Kernel aliases** — `tenant` and `require.tenant` middleware registered in all 7 service Kernels

**Phase 3 — API Gateway: Tenant Service Routing**
- `tenants` and `invitations` route prefixes added to `GatewayController` service map

**DBML Updates**
- Added `tenant_id` column to `cv_files`, `cv_parsing_jobs`, `vacancy_questions`, `interview_feedback`

**Testing**
- `TenantIsolationTest` added to `candidate-service` and `vacancy-service` (7 test cases each)
- `setUp`/`tearDown` tenant context added to `CandidateManagementTest` and `VacancyManagementTest`
- `tenant_id` default added to `CandidateFactory`, `VacancyFactory`, `InterviewFactory`, `OfferFactory`

**Makefile**
- `make migrate-tenants` — runs `php artisan migrate --force` on all 8 services
- `make test-tenant-isolation` — runs `TenantIsolationTest` across candidate and vacancy services
- Fixed stale `docker-compose` → `docker compose` in all tunnel targets

### Added - 2026-01-09

#### Containerized DBML Tools
- **Eliminated Node.js Host Dependency** - DBML operations now run in Docker container
  - Created `infrastructure/docker/Dockerfile.dbml` with Node.js 20 Alpine base
  - Added `dbml-tools` service to `docker-compose.yml` with `dbml` profile
  - Updated all `make dbml-*` commands to use `docker compose run --rm dbml-tools`
  - Removed Node.js installation check from `scripts/init-databases-from-dbml.sh`
  - Benefits:
    - ✅ No Node.js required on host machine
    - ✅ Consistent Node.js version across all developers
    - ✅ Isolated npm dependencies (no host pollution)
    - ✅ Automatic container cleanup after use
    - ✅ CI/CD ready without additional setup
- **Documentation** - Created comprehensive `docs/DBML-TOOLS.md`
  - Architecture overview and technical details
  - Usage examples and troubleshooting guide
  - Migration guide for existing setups
- **Updated Prerequisites** in `README.md`
  - Node.js now marked as optional (only for frontend development)
  - PHP marked as optional (only for local development)

#### Cloudflare Tunnel Integration
- **Public Access via Cloudflare Tunnel** - Expose application to internet without port forwarding
  - Added `cloudflared` service to `docker-compose.yml`
  - Automatic HTTPS with Cloudflare's SSL certificates
  - Built-in DDoS protection and global CDN
  - No inbound firewall ports required
  - Configuration via `CLOUDFLARE_TUNNEL_TOKEN` environment variable
- **CORS Configuration** - Updated API Gateway to accept public domain requests
  - Dynamic CORS origins based on `PUBLIC_DOMAIN` environment variable
  - Supports both local and public access simultaneously
- **Frontend Configuration** - Environment variables support public URLs
  - `VITE_API_GATEWAY_URL` uses `PUBLIC_API_URL` when available
  - Falls back to localhost for local development
- **Makefile Commands** - Added tunnel management commands
  - `make tunnel-up` - Start Cloudflare Tunnel
  - `make tunnel-down` - Stop Cloudflare Tunnel
  - `make tunnel-logs` - View tunnel logs
  - `make tunnel-status` - Check tunnel status
- **Documentation** - Created comprehensive `CLOUDFLARE_TUNNEL.md`
  - Step-by-step setup guide with screenshots
  - Troubleshooting section for common issues
  - Security considerations and best practices
  - Advanced configuration options
- **Environment Variables** - Added to `.env.example`
  - `CLOUDFLARE_TUNNEL_TOKEN` - Tunnel authentication token
  - `PUBLIC_DOMAIN` - Public domain (e.g., ne1-candidacy.comulo.app)
  - `PUBLIC_API_URL` - Public API endpoint URL

### Fixed - 2025-12-29

#### End-to-End Test Script (`test-e2e.sh`)
- **Token Extraction** - Fixed authentication token extraction from login response
  - Changed from `"token"` to `"access_token"` pattern to match actual API response format
  - Applied to both login and admin creation flows
- **Vacancy Creation Payload** - Fixed vacancy data format validation errors
  - Changed `employment_type` from `full-time` to `full_time` (underscore format)
  - Changed `requirements` from string to array format as expected by API
  - Added fallback ID lookup for 204 No Content responses
- **Candidate Creation** - Fixed duplicate email errors on repeated test runs
  - Added timestamp-based unique email generation (`john.doe-e2e-{timestamp}@example.com`)
  - Added `name` field required by candidate API
  - Added fallback to use existing candidate ID on duplicate
- **ID Extraction** - Improved JSON ID extraction patterns
  - Replaced fragile `grep -o '"id":[0-9]*'` with robust `sed` pipeline
  - Handles IDs appearing at any position in JSON response

### Added - 2025-12-26

#### UI Redesign - Sidebar-Based Dashboard Layout
- **New Dashboard Layout** (`DashboardLayout.vue`)
  - Collapsible sidebar navigation replacing top header navigation
  - Modern stat cards with gradient icons for key metrics
  - Pipeline visualization with animated bar charts
  - Recent candidates widget with avatar initials
- **Component Architecture**
  - `AppSidebar.vue` - Collapsible navigation with role-based menu items
  - `AppHeader.vue` - Compact top header with user profile and search
  - Consistent layout variables in `variables.css` and `layout.css`
- **Beautified Login Page** (`Login.vue`)
  - Glassmorphism design with backdrop blur effects
  - Animated gradient background with floating orbs
  - Wider login container (600px) with improved responsive breakpoints
  - Configurable background image via admin settings
- **List View Enhancements**
  - Modernized `CandidateList.vue`, `MatchList.vue`, `OfferList.vue`
  - Consistent action buttons with icons and hover effects
  - Improved card layouts and typography

#### Configuration Management Enhancements
- **Specialized Input Controls** in Admin panel
  - Color picker for `ui.primary_color` setting
  - Dropdown selects for AI provider, date/time formats
  - Range sliders with percentage display for threshold settings
  - Toggle switches for boolean settings
- **New Configuration Categories**
  - `matching` category with configurable thresholds
  - Extended `ai` category with generation parameters (timeout, temperature, context length)
  - `ui` category with sidebar width, items per page, dark mode toggle
- **AI Generation Parameters** (configurable in Admin)
  - `ai.generation.timeout` - AI request timeout (30-600 seconds)
  - `ai.generation.temperature` - Response creativity (0.0-1.0)
  - `ai.generation.context_length` - Context window size (2048-32768)
- **Matching Thresholds** (moved to configuration)
  - `matching.min_score_threshold` - Minimum score to save matches (default: 40)
  - `matching.max_retry_attempts` - Retry count for failed AI responses (default: 3)
  - `matching.display_threshold` - UI filter threshold (default: 60)

### Added - 2025-12-26 (continued)

#### AI Matching Quality Improvements
- **Match Score Filtering** - Matches with scores below 40% are now automatically discarded
  - Prevents low-relevance matches from cluttering the UI
  - Applied in both synchronous (`MatchController`) and async (`MatchJob`) flows
- **Recommendation Retry Logic** - If AI fails to provide a RECOMMENDATION section:
  - System automatically retries up to 3 times
  - Logs warning on each retry attempt
  - Saves best available result after all attempts
- **Typo-Proof Analysis Parsing** - Frontend parsers now handle AI output inconsistencies:
  - Spelling variants: `STRENGTHS`, `STRENGHTHS`, `STRENTHS`
  - Flexible spacing: `GAPS :`, `GAPs:`, `GAPS:`
  - Label variants: `WEAKNESSES` as alternative to `GAPS`
  - Case-insensitive matching throughout

#### UI Enhancements
- **Beautified Matches Tab** (`CandidateDetail.vue`)
  - Modern glassmorphism cards for analysis sections
  - Color-coded panels: Green (Strengths), Amber (Gaps), Blue (Recommendation)
  - Smooth hover animations and transitions
  - Improved typography and spacing
- **Graceful Empty States** - Empty Strengths/Gaps sections now show placeholder text
  - "No specific strengths highlighted" / "No specific gaps identified"
  - Maintains layout integrity even with partial AI responses

### Fixed - 2025-12-26
- **MatchJob Ignoring Score Threshold** - Background matching job was saving all matches regardless of score
- **Analysis Parsing Failures** - Strict regex was failing on AI typos, now uses permissive patterns

### Fixed - 2025-12-24

#### Build System
- **Database Configuration Syntax Errors** - Fixed duplicate Redis configuration blocks
  - Removed duplicate 'default' and 'cache' Redis configurations from lines 151-161
  - Affected 9 services: auth, vacancy, matching, interview, offer, onboarding, reporting, admin, notification
  - Each service's `config/database.php` reduced from 167 to 152 lines
  - Build now completes successfully without syntax errors

#### Database Initialization
- **init-databases-from-dbml.sh Script Fixes** - Fixed 3 critical issues:
  1. **Unsafe .env parsing** - Replaced `export $(grep...)` with safer `set -a; source; set +a` pattern
     - Now properly handles comment lines and inline comments
     - Prevents "export: `#': not a valid identifier" errors
  2. **Variable ordering** - Moved `.env` loading before MySQL readiness check
     - `DB_ROOT_PASSWORD` now defined before use
     - Fixes MySQL connection failures during initialization
  3. **Table already exists errors** - Added `DROP DATABASE IF EXISTS` before `CREATE DATABASE`
     - Ensures clean initialization on repeated runs
     - Prevents "ERROR 1050 (42S01): Table already exists" errors
  - All 9 databases now initialize successfully

### Added - 2025-12-23

#### Testing Infrastructure
- **CandidateMatchFactory** - Complete factory for generating test match data
  - Helper states: `highScore()`, `pending()`
  - Generates realistic match scores, analysis, and interview questions
- **InterviewFactory** - Complete factory for generating test interview data
  - Helper states: `upcoming()`, `completed()`, `video()`
  - Supports all interview stages and types
- **OfferFactory** - Complete factory for generating test offer data
  - Helper states: `pending()`, `accepted()`, `rejected()`
  - Generates realistic salary offers and benefits packages

#### Database Migrations
- **Candidate Service Migration** - `2025_12_23_022218_add_missing_fields_to_candidates_table.php`
  - Adds 8 missing fields to candidates table
  - Fields: `years_of_experience`, `current_location`, `preferred_location`, `expected_salary`, `notice_period`, `pin_code`, `generated_cv_content`, `certifications`

#### Health Check Endpoints
- Implemented comprehensive health check endpoints across all 11 microservices
- Added `HealthController` with database and Redis connectivity checks
- Standardized `/api/health` endpoint returning structured JSON with service status

### Changed - 2025-12-23

#### Authentication
- **Auth Service** - Fixed Sanctum authentication in tests
  - Enhanced `TestCase.php` with authentication helpers
  - Changed middleware from `auth:api` to `auth:sanctum`
  - Added `authenticatedUser()`, `actingAsUser()`, and `authenticatedJson()` helper methods

#### Database Schemas
- **CandidateFactory** - Aligned with actual database schema
  - Removed non-existent fields: `years_of_experience`, `notice_period`
  - Added proper JSON structures for `skills`, `experience`, `education`
  - Fixed status values to match database enum
- **VacancyFactory** - Aligned with actual database schema
  - Fixed enum values: `full_time`, `part_time`, `contract`, `intern`
  - Added missing salary and experience fields
  - Added JSON structures for skills and benefits

#### Models
- **CandidateMatch** - Removed `HasJsonFields` trait dependency
  - JSON fields work correctly with `$casts` alone
- **Offer** - Removed `HasJsonFields` trait dependency
  - JSON fields work correctly with `$casts` alone

### Fixed - 2025-12-23

#### Code Quality
- Removed debug `console.log` statements from frontend
  - `Admin.vue` - 2 statements removed
  - `Login.vue` - 2 statements removed
  - `CandidateForm.vue` - 2 statements removed
- Cleaned up unnecessary logging statements

#### Schema Consistency
- Resolved all schema mismatches between models and migrations
- Fixed factory definitions to match actual database columns
- Ensured all `$fillable` arrays align with migration schemas

### Technical Debt Resolution

#### Phase 1: Critical Fixes ✅
- ✅ Fixed authentication in tests (401 errors)
- ✅ Aligned database schemas (factory mismatches)
- ✅ Implemented health check endpoints (all 11 services)

#### Phase 2: Code Quality ✅
- ✅ Cleaned up debug code (6 console.logs removed)
- ✅ Reviewed dependencies (no critical updates needed)

#### Deferred for Future
- Database index optimization (Phase 3)
- Cache strategy implementation (Phase 3)
- Log rotation configuration (Phase 4)

---

## Previous Releases

### [1.0.0] - 2025-12-18

#### Added
- Complete microservices architecture with 11 services
- Vue.js frontend applications (Admin and Applicant portals)
- API Gateway with routing and authentication
- Comprehensive logging infrastructure (Loki, Promtail, Grafana)
- AI-powered CV parsing and job matching
- Interview scheduling and management
- Offer generation and tracking
- Full recruitment workflow implementation

#### Services Implemented
1. Auth Service - User authentication and authorization
2. Admin Service - System settings and configuration
3. Candidate Service - Candidate management and CV processing
4. Vacancy Service - Job posting management
5. AI Service - AI-powered parsing and matching
6. Matching Service - Candidate-vacancy matching
7. Interview Service - Interview scheduling
8. Offer Service - Offer management
9. Onboarding Service - New hire onboarding
10. Reporting Service - Analytics and reporting
11. Notification Service - Email and notifications

---

## Summary Statistics

### Total Changes (2025-12-23)
- **Files Created:** 4 (1 migration + 3 factories)
- **Files Modified:** 35 (routes, models, tests)
- **Services Updated:** 11 (all microservices)
- **Tests Fixed:** Auth service test suite
- **Debug Statements Removed:** 6
- **Factory Coverage:** 100% of major models

### Quality Improvements
- ✅ All tests now pass with proper authentication
- ✅ All factories generate valid test data
- ✅ All models align with database schemas
- ✅ All services have health check endpoints
- ✅ Cleaner, production-ready codebase

---

[Unreleased]: https://github.com/yourusername/candidacy/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/yourusername/candidacy/releases/tag/v1.0.0
