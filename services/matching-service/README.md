# Matching Service

AI-powered candidate-vacancy matching service.

## Overview

- **Port**: 8085
- **Database**: `candidacy_matching`
- **Framework**: Laravel 10

## Features

- ✅ AI-powered semantic matching
- ✅ Match scoring (0-100%)
- ✅ Detailed match analysis
- ✅ Interview question generation
- ✅ Match dismissal and restoration
- ✅ Batch matching operations
- ✅ **Configurable Thresholds**: Min score (40%), display filter (60%)
- ✅ **Queue Isolation**: Dedicated `matching_queue` for reliable processing

## API Endpoints

```http
GET    /api/matches                 # List all matches
GET    /api/candidates/{id}/matches # Matches for candidate
GET    /api/vacancies/{id}/matches  # Matches for vacancy
POST   /api/matches/{cid}/{vid}/questions  # Generate questions
POST   /api/matches/{cid}/{vid}/dismiss    # Dismiss match
POST   /api/matches/{cid}/{vid}/restore    # Restore match
DELETE /api/matches/clear           # Clear all matches
```

## Matching Algorithm

Uses AI (gemma2:2b or llama3.2:3b) to:
1. Analyze candidate skills and experience
2. Compare with vacancy requirements
3. Generate match score (0-100%)
4. Provide detailed analysis in markdown

## Recent Fixes (2025-12-23)

- ✅ Added Shared namespace configuration
- ✅ Updated MatchController to extend BaseApiController

## Development

The database schema is managed via DBML. Always edit `schema.dbml` at the root and run `make dbml-sql`.

```bash
# Sync local database
make dbml-init

# Start service
docker-compose up -d matching-service
```
