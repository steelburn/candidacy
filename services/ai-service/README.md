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
- ✅ Configurable models per task type
- ✅ **Generation Parameters**: Timeout, temperature, context length
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

| Setting | Description | Default |
|---------|-------------|--------|
| `ai.provider` | `ollama` or `openrouter` | ollama |
| `ai.ollama.url` | URL of the Ollama server | http://192.168.88.120:11535 |
| `ai.ollama.model.default` | Default model for general tasks | mistral |
| `ai.ollama.model.matching` | Dedicated matching model | llama3.2:3b |
| `ai.generation.timeout` | Generation timeout (seconds) | 300 |
| `ai.generation.temperature` | Response creativity (0.0-1.0) | 0.7 |
| `ai.generation.context_length` | Context window size | 8192 |

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
