# Candidacy Platform — Browser Console Troubleshooting

**Date:** 2026-05-03
**Environment:** Production (`https://candidacy.comulo.app`)
**User:** admin@test.com / password

---

## Pages Visited & Console Status

| # | Page | URL | Result |
|---|------|-----|--------|
| 1 | Login | `/login` | PASS — no errors |
| 2 | Dashboard | `/dashboard` | PASS — no errors |
| 3 | Candidates List | `/candidates` | PASS — no errors |
| 4 | Vacancies List | `/vacancies` | PASS — no errors |
| 5 | Matches | `/matches` | **FAIL** — 500 from matching-service |
| 6 | Interviews | `/interviews` | PASS — no errors |
| 7 | Offers | `/offers` | **FAIL** — 500 from offer-service |
| 8 | Reports | `/reports` | PASS — no errors |
| 9 | System Health | `/admin/system` | PASS — no errors |
| 10 | Configuration | `/admin/configuration` | PASS — no errors |
| 11 | AI Providers | `/admin/ai-providers` | **FAIL** — 500 from admin-service |
| 12 | User Management | `/admin/users` | PASS — no errors |
| 13 | CV Jobs | `/admin/cv-jobs` | PASS — no errors |
| 14 | Workspaces | `/admin/workspaces` | PASS — no errors |
| 15 | Workspace Detail | `/tenants/:id` | PASS — no errors (invitation fix verified) |
| 16 | Create Candidate | `/candidates/create` | PASS — no errors |
| 17 | Create Vacancy | `/vacancies/create` | PASS — no errors (benign Vite/Suspense only) |

---

## Errors Identified

### ERROR-001 — Matches Page (500 Internal Server Error)

**Page:** `/matches`
**Error message:**
```
Failed to load resource: the server responded with a status of 500 ()
Failed to fetch matches: AxiosError: Request failed with status code 500
    at async fetchMatches (MatchList.vue:110:22)
```

**Affected endpoint:** `GET /matches` → proxied to `matching-service`
**Severity:** HIGH — page is non-functional
**Potential causes:**
- Missing `tenant_id` header causing matching-service to fail tenant-scoped query
- Database schema mismatch in matching-service
- Missing required configuration (e.g., AI provider not set up)
- 500 from matching-service's `/api/matches` endpoint

**Troubleshooting steps:**
- [ ] Check `matching-service` logs: `make logs-matching` or `docker compose logs matching-service`
- [ ] Verify tenant-service is running and returning valid `X-Tenant-ID` context
- [ ] Check if `candidate_id` / `vacancy_id` tables exist and have data
- [ ] Try with a tenant that has candidates and vacancies to see if it is a data issue

---

### ERROR-002 — Offers Page (500 Internal Server Error)

**Page:** `/offers`
**Error message:**
```
Failed to load resource: the server responded with a status of 500 ()
Failed to fetch offers: AxiosError: Request failed with status code 500
    at async fetchOffers (OfferList.vue:20:22)
```

**Affected endpoint:** `GET /offers` → proxied to `offer-service`
**Severity:** HIGH — page is non-functional
**Potential causes:**
- Missing `tenant_id` scope in offer-service query
- Database schema mismatch in offer-service
- Missing foreign key references (candidate_id, vacancy_id)
- Configuration issue in offer-service

**Troubleshooting steps:**
- [ ] Check `offer-service` logs: `make logs-offer` or `docker compose logs offer-service`
- [ ] Check if `offers` table exists and schema is correct
- [ ] Verify `candidate_id` and `vacancy_id` foreign key references are valid
- [ ] Check if the service requires seeding data

---

### ERROR-003 — AI Providers Page (500 Internal Server Error)

**Page:** `/admin/ai-providers`
**Error message:**
```
Failed to load resource: the server responded with a status of 500 ()
Using default provider configuration AxiosError: Request failed with status code 500
    at async loadProviders (AdminAIProviders.vue:77:22)
```

**Affected endpoint:** `GET /providers` → proxied to `ai-service`
**Severity:** HIGH — AI provider management is non-functional
**Potential causes:**
- Database schema mismatch in ai-service (missing `ai_providers` table)
- Missing AI provider seed data
- Configuration issue in ai-service

**Troubleshooting steps:**
- [ ] Check `ai-service` logs: `make logs-ai` or `docker compose logs ai-service`
- [ ] Verify `ai_providers` table exists in ai-service database
- [ ] Run `make dbml-check` to verify schema sync across services
- [ ] Seed AI provider data: `make shell S=ai-service` then `php artisan db:seed`

---

## Previously Fixed

### FIXED-001 — Invitation 404 (Double `/api/api/` prefix)

**Symptom:** `GET /api/api/tenants/test-uuid-123/invitations` → 404
**Root cause:** `TenantDetail.vue` was prefixing API calls with `/api/`, but `VITE_API_GATEWAY_URL` already included `/api`. Axios concatenated -> double `/api/api/`.
**Fix:** Removed `/api` prefix from 4 API calls in `frontend/web-app/src/views/tenants/TenantDetail.vue`:
- Line 343: `api.get('/api/tenants/...')` -> `api.get('/tenants/...')`
- Line 408: `api.delete('/api/tenants/...')` -> `api.delete('/tenants/...')`
- Line 420: `api.delete('/api/tenants/...')` -> `api.delete('/tenants/...')`
- Line 444: `api.put('/api/tenants/...')` -> `api.put('/tenants/...')`
**Status:** FIXED — verified on `/tenants/test-uuid-123` — no console errors

---

## Notes

- All other pages (Dashboard, Candidates, Vacancies, Interviews, Reports, System Health, Configuration, User Management, CV Jobs, Workspaces, Workspace Detail, Create forms) load with **zero console errors**.
- Benign messages from Vite dev server (`[vite] connecting...`, `[vite] connected.`, `<Suspense> is experimental`) are **not errors** — these appear because the frontend is running in dev mode with Vite HMR. They do not appear in production builds.
- The three 500 errors all share a similar pattern — each targets a different backend microservice. This suggests either a shared database initialization issue (tables not created/migrated) or a missing seed step across the matching, offer, and ai services.

---

## Fixes Applied (2026-05-03)

### FIXED-001 — Matches 500 (matching-service)
**Root cause:** `Shared\\` autoload path in `vendor/composer/autoload_psr4.php` was `/../var/www/shared` (wrong). Should be `/../shared`.
**Fix:** Changed `composer.json` autoload from `"Shared\\": "/var/www/shared/"` to `"Shared\\": "../shared/"` in `services/matching-service/composer.json`, then ran `composer dump-autoload` inside container.
**Note:** Volume mount `./services/matching-service:/var/www/html` overwrites built vendor files; must regenerate autoloader in running container, not just rebuild image.
**Status:** FIXED — `GET /api/matches` returns 200 with pagination JSON.

### FIXED-002 — Offers 500 (offer-service)
**Root cause:** Same as FIXED-001 — same broken `Shared\\` autoload path.
**Fix:** Same — `services/offer-service/composer.json` autoload fixed, `composer dump-autoload` in container.
**Status:** FIXED — `GET /api/offers` returns 200 with pagination JSON.

### FIXED-003 — AI Providers 500 (ai-service)
**Root cause:** Same as FIXED-001/002 — `Shared\\Services\\ConfigurationService` not found due to wrong autoload path.
**Fix:** Same — `services/ai-service/composer.json` autoload fixed, `composer dump-autoload` in container.
**Status:** FIXED — `GET /api/providers` returns 200 with provider array.

### Root Cause Summary
All 3 services had `"Shared\\": "/var/www/shared/"` in `composer.json` which Composer resolved to a relative path `../var/www/shared` at build time (incorrect). Working services (candidate, vacancy) had the path resolved correctly as `../shared`. The volume mount `./services/X-service:/var/www/html` overwrites built files, so rebuilding alone was insufficient — autoloader had to be regenerated inside running containers using the corrected `composer.json`.
