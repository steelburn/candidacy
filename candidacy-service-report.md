# Candidacy Service Status Report

**Date:** April 30, 2026
**URL Tested:** https://candidacy.comulo.app

---

## 1. Service Startup Status

### Initial Result: PARTIALLY SUCCESSFUL ✅
- `make up` started 27 services
- All core Laravel services started initially but most crashed immediately
- Queue workers started but are now restarting

### Current Service Status

**Online:** 13/27 services
- ✅ Core infrastructure: MySQL, Redis, Ollama, Mailpit, Loki, Grafana, Promtail
- ✅ Frontend: candidacy-frontend (port 3501), candidacy-applicant-frontend (port 5173)
- ✅ Cloudflare Tunnel, Frontend container

**Offline/Crashed:** 14/27 services
- ❌ Core Laravel services: auth-service, candidate-service, vacancy-service, matching-service, ai-service, interview-service, offer-service, onboarding-service, reporting-service, admin-service, notification-service, document-parser-service, tenant-service, matching-queue-worker
- ❌ Queue workers: candidate-queue-worker, matching-queue-worker, document-parser-worker, notification-worker

---

## 2. URL Testing Results

### Visual Test (Browser Analysis)

**✅ Page loaded successfully**

The Candidacy login page is accessible and functional:
- **Login form** with email/password fields and eye toggle
- **Sign In** button with gradient styling
- **Demo Account** credentials: `admin@test.com / password`
- **Branding**: Candidacy logo with purple/blue gradient square
- **Design**: Dark theme with glassmorphism effect
- **Footer**: "© 2025 Candidacy. All rights reserved"

### Issues Found
- **500 errors** when API calls fail (health checks)
- **"Could not load UI settings, using defaults"** - configuration API returning 500
- **Multiple resource loading failures** from API endpoints

---

## 3. Root Causes & Issues Found

### 🔴 CRITICAL: Docker Networking Issue
- **Problem:** Custom bridge networking fails in this environment
- **Error:** `docker network create candidate_network` fails with "bridge interface cannot be assigned an address"
- **Impact:** Cannot use custom Docker networks - only default bridge network available
- **Fix needed:** Replace all `candidacy-network` references with `bridge` in docker-compose.yml

### 🔴 CRITICAL: Composer/php Version Mismatch
- **Problem:** `composer:latest` requires PHP 8.4+ but Dockerfile.base uses PHP 8.2-fpm
- **Error:** `composer dump-autoload failed - PHP version >= 8.4.0 required`
- **Impact:** All Laravel services fail to build
- **Fix applied:** Changed `composer:latest` to `composer:2.7` in Dockerfile.base

### 🟡 HIGH: Vendor Directory Volume Mounts
- **Problem:** `docker-compose.yml` has anonymous volume `- /var/www/html/vendor` for all Laravel services
- **Impact:** Vendor directory inside containers is overwritten/empty on startup
- **Fix applied:** Removed all `/var/www/html/vendor` volumes from docker-compose.yml

### 🟡 HIGH: Composer.lock/JSON Mismatches (6 services)
- **Problem:** Multiple services have `tymon/jwt-auth` required in composer.json but not in composer.lock
- **Affected services:**
  1. auth-service
  2. candidate-service
  3. vacancy-service
  4. interview-service
  5. offer-service
  6. onboarding-service
  7. notification-service
  8. reporting-service
  9. admin-service
  10. tenant-service
  11. document-parser-service
  12. matching-service
- **Fix applied:** Ran `composer update` on all affected services
- **Remaining:** auth-service, candidate-service, vacancy-service, reporting-service, admin-service may still need manual attention

### 🟡 MEDIUM: Gateway Container Crashes
- **Problem:** Gateway service crashes on startup
- **Error:** `Class "NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider" not found`
- **Impact:** API Gateway unavailable → frontend can't reach backend APIs
- **Root Cause:** Either corrupted vendor directory or missing collision package
- **Fix needed:** Rebuild gateway service or install collision package

### 🟢 LOW: Database Migration
- **No database migrations run** after composer fixes
- Services will need manual migration when they come up

---

## 4. Recommended Action Plan

### Immediate (fix and restart)
```bash
# 1. Rebuild all services with fixed dependency
make build  # Dockerfile.base now uses composer:2.7, vendor mounts removed

# 2. Clear stale containers
make clean

# 3. Start fresh
make up
```

### If services still failing
```bash
# For each service showing CollisionServiceProvider error:
docker exec candidacy-gateway composer install

# For each service showing composer.lock issues:
cd services/<service> && composer update
```

### Manual database setup (if needed)
```bash
# Access each service shell and run migrations
make shell S=auth-service
php artisan migrate

# Repeat for all services
```

---

## 5. Health Check Summary

### Working Components
| Component | Status | URL |
|-----------|--------|-----|
| Frontend | ✅ Running | http://localhost:3501 |
| Applicant Portal | ✅ Running | http://localhost:5173 |
| Grafana | ✅ Running | http://localhost:3050 |
| Mailpit | ✅ Running | http://localhost:8025 |
| MySQL | ✅ Running | localhost:3306 |
| Redis | ✅ Running | localhost:6379 |
| Ollama | ✅ Running | localhost:11434 |

### Failing Components
| Component | Status | Error |
|-----------|--------|-------|
| API Gateway | ❌ Crashes | CollisionServiceProvider not found |
| Auth Service | ❌ Not responding | Composer vendor missing |
| Candidate Service | ❌ Not responding | Composer vendor missing |
| All other Laravel services | ❌ Not responding | Same issues |

---

## Summary

**Overall Status:** ⚠️ DEGRADED
- Frontend pages load but backend APIs are offline
- Core infrastructure (database, cache, monitoring) is healthy
- Fix applied: Dockerfile dependency pinning, vendor mount removal, composer updates
- Needs: Rebuild and restart with clean state
