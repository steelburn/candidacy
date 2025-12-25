# AI Service

Centralized AI operations service providing semantic analysis, matching, and generation capabilities using Ollama or OpenRouter.

## Overview

- **Port**: 8084
- **Database**: `candidacy_ai`
- **Framework**: Laravel 10
- **AI Providers**: Ollama (local), OpenRouter (cloud)

## Features

- ✅ CV analysis (skills, experience, education extraction)
- ✅ Intelligent candidate-vacancy matching
- ✅ Job description generation from keywords
- ✅ Automated interview questionnaire generation
- ✅ Automated interview questionnaire generation
- ✅ Configurable models per task type
- ✅ **Extended Timeout**: 300s timeout for handling complex/local model generation

## API Endpoints

```http
POST   /api/analyze-cv              # Analyze extracted CV text
POST   /api/generate-jd             # Generate job description
POST   /api/match                   # Calculate match score and analysis
POST   /api/generate-questions      # Generate interview questions
GET    /api/health                  # Service health check
```

## AI Configuration

Settings are managed via the **Admin Service** and can be adjusted in the HR Dashboard:

| Setting | Description |
|---------|-------------|
| `ai_provider` | `ollama` or `openrouter` |
| `ollama_url` | URL of the Ollama server |
| `ollama_model` | Primary model (e.g., `gemma2:2b`) |
| `ollama_matching_model` | Dedicated matching model |
| `match_threshold` | Minimum score (0-100) for "Matching" status |

## Development

```bash
# Sync schema
make dbml-sql

# Run service
docker-compose up -d ai-service
```

## Health Check

```bash
curl http://localhost:8084/api/health
```
