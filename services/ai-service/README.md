# AI Service

Centralized AI operations service for the Candidacy recruitment platform.

## Purpose

The AI Service provides centralized artificial intelligence capabilities for the entire platform. It supports multiple AI providers (Ollama for local deployment, OpenRouter for cloud) and handles CV parsing, job description generation, candidate matching, and questionnaire generation.

## Key Features

- **Multi-Provider Support**: Ollama (local) or OpenRouter (cloud)
- **CV Parsing**: Extract structured data from resumes (PDF/DOCX)
- **Job Description Generation**: Create compelling JDs from basic inputs
- **Candidate Matching**: Semantic matching between candidates and vacancies
- **Questionnaire Generation**: AI-generated interview questions
- **Configurable Models**: Support for various LLM models
- **External Ollama Support**: Connect to external Ollama instances

## Technology Stack

- **Framework**: Laravel 11
- **Database**: Not used (stateless service)
- **Port**: 8084 (container internal: 8080)
- **AI Providers**: Ollama, OpenRouter
- **Default Models**: gemma2:2b for matching and questionnaires
- **Dependencies**: Admin Service for configuration

## Supported AI Providers

### Ollama (Local)
- **Pros**: Free, private, no API costs
- **Cons**: Requires GPU/CPU resources, slower
- **Models**: gemma2:2b, mistral, llama2, etc.
- **Configuration**: Set `OLLAMA_URL` in environment or admin settings

### OpenRouter (Cloud)
- **Pros**: Fast, no local resources needed
- **Cons**: API costs, requires internet
- **Models**: GPT-4, Claude, Gemini, etc.
- **Configuration**: Set `OPENROUTER_API_KEY` in environment

## API Endpoints

### Parse CV
```
POST /api/parse-cv
```
Extracts structured information from uploaded CV.

**Request Body:**
```json
{
  "file": "base64_encoded_file_content",
  "filename": "resume.pdf"
}
```

**Response:**
```json
{
  "personal_info": {
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890"
  },
  "skills": ["Python", "Django", "PostgreSQL"],
  "experience": [
    {
      "company": "Tech Corp",
      "position": "Software Engineer",
      "start_date": "2020-01",
      "end_date": "2023-12",
      "description": "..."
    }
  ],
  "education": [
    {
      "institution": "University",
      "degree": "Bachelor of Science",
      "field": "Computer Science",
      "graduation_year": "2020"
    }
  ]
}
```

### Generate Job Description
```
POST /api/generate-jd
```
Creates a comprehensive job description.

**Request Body:**
```json
{
  "title": "Senior Software Engineer",
  "department": "Engineering",
  "requirements": ["Python", "5+ years experience", "Team leadership"]
}
```

**Response:**
```json
{
  "description": "# Senior Software Engineer\n\n## About the Role\n..."
}
```

### Match Candidate to Vacancy
```
POST /api/match
```
Analyzes candidate-vacancy fit and provides detailed analysis.

**Request Body:**
```json
{
  "candidate": {
    "skills": ["Python", "Django"],
    "experience": [...],
    "education": [...]
  },
  "vacancy": {
    "title": "Software Engineer",
    "requirements": ["Python", "Django"],
    "description": "..."
  }
}
```

**Response:**
```json
{
  "score": 85,
  "analysis": "## Match Analysis\n\n**Overall Fit: 85%**\n\n### Strengths\n- Strong Python background...\n\n### Gaps\n- Limited cloud experience...",
  "recommendation": "strong_match"
}
```

### Generate Questionnaire
```
POST /api/generate-questionnaire
```
Creates interview questions based on job requirements.

**Request Body:**
```json
{
  "vacancy_title": "Senior Software Engineer",
  "requirements": ["Python", "System Design", "Leadership"],
  "difficulty": "senior"
}
```

**Response:**
```json
{
  "questions": [
    {
      "question": "Describe your experience with Python...",
      "category": "technical",
      "difficulty": "medium"
    }
  ]
}
```

## Configuration

The AI Service reads configuration from the Admin Service:

- `ai_provider` - "ollama" or "openrouter"
- `matching_model` - Model for matching (default: gemma2:2b)
- `questionnaire_model` - Model for questions (default: gemma2:2b)
- `ollama_url` - Ollama instance URL (supports external instances)

### External Ollama Configuration

To use an external Ollama instance:

1. Set in Admin panel: Ollama URL = `http://your-server:11434`
2. Or set environment variable: `OLLAMA_URL=http://your-server:11434`

Example for external server:
```
OLLAMA_URL=http://192.168.88.120:11434
```

## Setup

### Using Ollama (Local)

```bash
# Pull the model
docker-compose exec ollama ollama pull gemma2:2b

# Verify model is available
docker-compose exec ollama ollama list
```

### Using OpenRouter (Cloud)

```bash
# Set API key in environment
OPENROUTER_API_KEY=your_api_key_here
```

## Development

### Test CV Parsing
```bash
curl -X POST http://localhost:8084/api/parse-cv \
  -H "Content-Type: application/json" \
  -d '{"file": "base64_content", "filename": "test.pdf"}'
```

### View Logs
```bash
docker-compose logs -f ai-service
```

### Monitor Performance
```bash
# Check Ollama status
curl http://localhost:11434/api/tags

# View AI service health
curl http://localhost:8084/health
```

## Environment Variables

- `OLLAMA_URL` - Ollama instance URL (default: http://ollama:11434)
- `OPENROUTER_API_KEY` - OpenRouter API key (if using cloud)
- `AI_TIMEOUT` - Request timeout in seconds (default: 120)
- `LOG_CHANNEL` - Logging channel (stderr for Docker)

## Performance Considerations

### Ollama (Local)
- **CPU**: Slower, suitable for development
- **GPU**: Much faster, recommended for production
- **Memory**: Requires 4-8GB RAM depending on model
- **Model Size**: gemma2:2b (~1.5GB), mistral (~4GB)

### OpenRouter (Cloud)
- **Latency**: 1-5 seconds depending on model
- **Cost**: Pay per token
- **Reliability**: Depends on internet connection

## Model Selection

### gemma2:2b (Default)
- **Size**: ~1.5GB
- **Speed**: Fast
- **Quality**: Good for most tasks
- **Best for**: Matching, questionnaires

### mistral
- **Size**: ~4GB
- **Speed**: Medium
- **Quality**: Better reasoning
- **Best for**: Complex analysis

### llama2
- **Size**: ~4GB
- **Speed**: Medium
- **Quality**: Good general purpose
- **Best for**: Job descriptions

## Integration

This service is consumed by:
- **Candidate Service**: CV parsing
- **Vacancy Service**: Job description generation
- **Matching Service**: Candidate-vacancy matching
- **Interview Service**: Questionnaire generation

## Error Handling

The service handles:
- Model not available errors
- Timeout errors (long-running requests)
- Invalid input format errors
- Provider connection errors

All errors are logged and returned with appropriate HTTP status codes.

## Notes

- CV parsing supports PDF and DOCX formats
- All AI-generated content is in markdown format
- Match scores range from 0-100
- Analysis includes strengths, gaps, and recommendations
- Questionnaires are customized by difficulty level
- External Ollama instances must be network-accessible
- Use cloud providers for production if local GPU unavailable
