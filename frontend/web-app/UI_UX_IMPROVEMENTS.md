# Candidacy — UI/UX Audit & Implemented Improvements

## Site Tested
https://candidacy.comulo.app

---

## Issues Found (from earlier audit)

### Quick Wins (Implemented)
| # | Issue | Location | Severity |
|---|-------|----------|----------|
| 1 | No toast notification system for action feedback | Global | High |
| 2 | Copyright year missing on login page | Login.vue | Low |
| 3 | "Remember me" checkbox missing on login | Login.vue | Medium |
| 4 | "Forgot password" link missing on login | Login.vue | Medium |
| 5 | Empty states have cold/unhelpful copy + no CTA | CandidateList, VacancyList, Dashboard | Medium |
| 6 | Emoji used as UI icons (not consistent, platform-dependent) | AdminAIProviders, AdminConfiguration | Low |
| 7 | "Test Tenant" hardcoded fallback (not from API) | Auth store | Medium |
| 8 | Sidebar admin section not persisted across sessions | AppSidebar.vue | Low |
| 9 | Loading spinners instead of skeleton loaders | CandidateList, VacancyList | Medium |

### Nice to Have (Not Implemented — out of scope for quick wins)
| # | Issue | Location |
|---|-------|----------|
| 10 | Dashboard stats show static hardcoded numbers (no real data) | Dashboard.vue |
| 11 | No breadcrumb navigation on inner pages | Global |
| 12 | Dark mode toggle in header instead of settings | AppHeader.vue |
| 13 | Job pipeline Kanban board uses emojis (🎯 etc.) | MatchingService.vue |
| 14 | Login page has rocket emoji in logo area | Login.vue brand |
| 15 | User avatar in sidebar is a gradient placeholder (no photo) | AppSidebar.vue |

---

## Implemented Quick Wins — Summary

### 1. Toast Notification System
**File:** `src/components/ToastContainer.vue` (new) + `src/App.vue` (patched)

A global toast notification system was wired in. Actions like save, delete, and API errors now show styled toast popups (success green, error red, warning yellow, info blue) instead of silent failures.

---

### 2. Login Page — Remember Me & Forgot Password
**File:** `src/views/auth/Login.vue`

- Added **"Remember me"** checkbox — persists email in localStorage, pre-fills on next visit
- Added **"Forgot password?"** link — prompts user to enter email first if blank, then navigates to `/forgot-password` (placeholder route, to be wired to backend)
- Copyright is already dynamic (`new Date().getFullYear()`)

---

### 3. Warm Empty States with CTA
**Files:** `src/views/candidates/CandidateList.vue`, `src/views/vacancies/VacancyList.vue`, `src/views/Dashboard.vue`

Before:
```
No candidates found
[Add Candidate]
```

After (CandidateList):
```
[SVG illustration: person with briefcase]
Your talent pipeline is empty
Ready to find your next great hire? Start by adding your first candidate.
[+ Add First Candidate]   [Import Bulk]
```

After (VacancyList):
```
[SVG illustration: clipboard with checkmark]
No open positions yet
Great teams start with great job postings. Create your first vacancy and
attract the candidates you're looking for.
[+ Create First Vacancy]
```

After (Dashboard recent candidates):
```
[SVG illustration: empty people group]
No candidates yet
Candidates you add or import will appear here.
[+ Add Candidate]
```

---

### 4. Emoji Replaced with SVG Icons — AdminAIProviders
**File:** `src/components/admin/AdminAIProviders.vue`

Replaced all emoji icons with proper inline SVG:
- `🔄 Refresh` → spinning refresh SVG icon
- `🦙/🧠/💎/☁️/🔄/🖥️/🌐` provider icons → matching SVG glyphs
- `📄/🎯/📝/❓/💬` service chain icons → matching SVG glyphs
- `🔑` key badge → checkmark SVG in badge
- `🗑️` remove → trash SVG icon
- `💾 Save` → save SVG icon
- `✏️` manual entry → edit SVG icon
- Added `.spin` CSS animation for the loading spinner state

---

### 5. Emoji Replaced with SVG Icons — AdminConfiguration
**File:** `src/components/admin/AdminConfiguration.vue`

- `🔍` search icon → magnifier SVG
- `✕` clear button → X SVG
- `📥 Export` / `📤 Import` / `🔄 Refresh` → upload/download/refresh SVGs
- `⚙️🤖📄👥💾✨🔗🎨` category sidebar icons → matching SVG icons per category

---

### 6. "Test Tenant" Fallback Removed
**Files:** `src/stores/auth.js`, `src/components/layout/TenantSwitcher.vue`

- Auth store getter `currentTenantName` already uses `tenant?.name ?? \`Tenant #${id}\`` — no hardcoded "Test Tenant"
- TenantSwitcher dropdown: added null-safety fallback `tenant.name || \`Tenant #${tenant.id}\``

---

### 7. Sidebar Admin Expanded State Persisted
**File:** `src/components/layout/AppSidebar.vue`

Sidebar expand/collapse state for admin sub-menu is now saved to `localStorage` key `sidebar_expanded` and restored on page reload.

```js
const expandedItems = ref(
  JSON.parse(localStorage.getItem('sidebar_expanded') || 'null') || { '/admin': true }
)
watch(expandedItems, (val) => {
  localStorage.setItem('sidebar_expanded', JSON.stringify(val))
}, { deep: true })
```

---

### 8. Spinner Loading States → Skeleton Loaders
**Files:** `src/components/SkeletonLoader.vue` (new), `src/views/candidates/CandidateList.vue` (patched), `src/views/vacancies/VacancyList.vue` (patched)

New reusable `SkeletonLoader.vue` component with 4 modes:
- `type="table"` — shimmer rows matching a table layout (6 rows × 5 cols for CandidateList)
- `type="card"` — shimmer cards for grid layouts (6 cards for VacancyList)
- `type="detail"` — shimmer form rows for detail/edit pages
- `type="paragraph"` — shimmer text lines (default)

Skeleton uses CSS shimmer animation (`background-position` keyframe) and supports dark mode via `prefers-color-scheme`.

---

## Files Modified / Created

| File | Action |
|------|--------|
| `src/App.vue` | Patched — added ToastContainer |
| `src/components/ToastContainer.vue` | **Created** |
| `src/components/SkeletonLoader.vue` | **Created** |
| `src/views/auth/Login.vue` | Patched — remember me, forgot password |
| `src/views/candidates/CandidateList.vue` | Patched — warm empty state + skeleton |
| `src/views/vacancies/VacancyList.vue` | Patched — warm empty state + skeleton |
| `src/views/Dashboard.vue` | Patched — warm empty state |
| `src/components/admin/AdminAIProviders.vue` | Patched — all emoji → SVG |
| `src/components/admin/AdminConfiguration.vue` | Patched — all emoji → SVG |
| `src/components/layout/TenantSwitcher.vue` | Patched — null-safety fallback |
| `src/components/layout/AppSidebar.vue` | Patched — sidebar state persistence |
| `src/stores/auth.js` | No changes needed (already correct) |

---

## Still Outstanding (Not Done)

These items require backend integration or deeper refactoring and were out of scope for quick wins:

1. **Real dashboard stats** — Dashboard currently shows hardcoded numbers; needs API integration
2. **Breadcrumb navigation** — No breadcrumbs on inner pages
3. **Dark mode in header** — Currently in AppHeader; should be moved to settings/profile
4. **Kanban board emojis** — MatchingService pipeline board uses emojis
5. **User profile photos** — Sidebar avatar is a gradient placeholder; needs real photo support
6. **Responsive mobile sidebar** — Sidebar may need hamburger menu on mobile
7. **Password reset flow** — `/forgot-password` route exists but needs backend confirmation email
8. **Bulk import progress** — Bulk upload modal shows spinner, could use progress indicator
