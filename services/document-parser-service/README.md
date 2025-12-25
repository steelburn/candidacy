# Document Parser Service

Dedicated microservice for high-performance, asynchronous text extraction from PDF and DOCX documents.

## Overview

- **Port**: 8092
- **Database**: `candidacy_document_parser`
- **Framework**: Laravel 10
- **Responsibilities**: PDF/DOCX Parsing, Text Cleaning

## Features

- ✅ **Advanced PDF parsing with IBM Granite Docling**
  - Structure preservation (tables, lists, sections)
  - DocTags markup output
  - 85-95% accuracy for resumes
- ✅ High-speed PDF text extraction (fallback)
- ✅ DOCX document parsing
- ✅ Text normalization and cleaning
- ✅ Asynchronous processing support
- ✅ Automatic fallback mechanism
- ✅ Performance metrics logging
- ✅ Detailed health monitoring

## Granite Docling Integration

This service uses IBM Granite Docling (258M parameter vision-language model) for advanced document understanding.

**Benefits:**
- Preserves document structure
- Better accuracy for complex layouts
- Handles tables and multi-column formats
- Outputs structured DocTags markup

**Configuration:** See [DOCLING_CONFIG.md](./DOCLING_CONFIG.md) for detailed setup and tuning options.

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

**Response (with Granite Docling):**
```json
{
  "job_id": 123,
  "status": "completed",
  "extracted_text": "Full extracted text with DocTags markup...",
  "page_count": 2,
  "parser": "granite-docling",
  "model": "ibm/granite-docling:258m"
}
```

**Response (fallback):**
```json
{
  "job_id": 123,
  "status": "completed",
  "extracted_text": "Basic text extraction...",
  "page_count": 2,
  "parser": "smalot-pdfparser"
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
