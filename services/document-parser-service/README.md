# Document Parser Service

Dedicated microservice for high-performance, asynchronous text extraction from PDF and DOCX documents.

## Overview

- **Port**: 8092
- **Database**: `candidacy_document_parser`
- **Framework**: Laravel 10
- **Responsibilities**: PDF/DOCX Parsing, Text Cleaning

## Features

- ✅ High-speed PDF text extraction
- ✅ DOCX document parsing
- ✅ Text normalization and cleaning
- ✅ Asynchronous processing support
- ✅ Detailed health monitoring

## API Endpoints

```http
POST   /api/parse                # Parse a document (multipart/form-data)
GET    /api/health               # Service health check
```

### Parse Document

**Request:**
```bash
curl -X POST http://localhost:8080/api/document-parser/parse \
  -F "file=@resume.pdf"
```

**Response:**
```json
{
  "text": "Full extracted text from the document...",
  "metadata": {
    "filename": "resume.pdf",
    "extension": "pdf",
    "size": 123456
  }
}
```

## Integration Flow

1. **Candidate Service** receives a file upload.
2. **Candidate Service** forwards the file to **Document Parser Service**.
3. **Document Parser Service** returns raw text.
4. **Candidate Service** sends text to **AI Service** for semantic analysis.

## Development

```bash
# Generate SQL from DBML
make dbml-sql

# Initialize database
make dbml-init

# Start service
docker-compose up -d document-parser-service
```

## Health Check

```bash
curl http://localhost:8092/api/health
```
