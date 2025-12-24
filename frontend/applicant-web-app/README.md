# Candidacy Applicant Portal

Self-service portal for candidates to access their application information.

## Overview

- **Port**: 5173
- **Framework**: Vue.js 3 + Vite
- **Access**: Token-based (no login required)
- **HTTP Client**: Axios

## Features

- ✅ Token-based access (no registration needed)
- ✅ View application status
- ✅ Answer interview questionnaires
- ✅ View match analysis
- ✅ Submit responses
- ✅ Track application progress

## Access Flow

1. Recruiter generates portal token for candidate
2. Candidate receives link with token
3. Candidate accesses portal using token
4. Token validates and shows candidate info
5. Candidate can view and respond to questionnaires

## Development

```bash
cd frontend/applicant-web-app
npm install
npm run dev
```

Access at http://localhost:5173

## Build

```bash
npm run build
```

## Environment Variables

```env
VITE_API_GATEWAY_URL=http://localhost:8080
```

## Token Format

Portal URLs look like:
```
http://localhost:5173/portal/{token}
```

Tokens are:
- Generated per candidate-vacancy pair
- Time-limited
- Single-use or multi-use (configurable)
- Validated on each request
