# Candidacy Web Application

Main frontend application for HR/Recruiters to manage the recruitment process.

## Overview

- **Port**: 3001
- **Framework**: Vue.js 3 + Vite
- **UI Library**: Custom components with modern design
- **State Management**: Vue Composition API
- **HTTP Client**: Axios

## Features

### Candidate Management
- ✅ View and search candidates
- ✅ Upload and parse CVs (PDF/DOCX)
- ✅ Bulk CV upload
- ✅ View candidate details and history
- ✅ Generate portal tokens

### Vacancy Management
- ✅ Create and manage job postings
- ✅ AI-powered job description generation
- ✅ Work mode selection (On-site, Remote, Hybrid)
- ✅ Interview questionnaire management
- ✅ WYSIWYG Markdown editor

### Matching
- ✅ View candidate-vacancy matches
- ✅ AI-powered match analysis
- ✅ Match scoring and filtering
- ✅ Generate interview questions
- ✅ Dismiss/restore matches

### Interviews
- ✅ Schedule interviews
- ✅ Multiple interview types
- ✅ Interview feedback collection
- ✅ Upcoming interviews dashboard

### Offers
- ✅ Create and manage offers
- ✅ Track offer status
- ✅ Offer acceptance workflow

### Reports & Dashboard
- ✅ Candidate metrics & statistics
- ✅ Vacancy analytics
- ✅ Hiring pipeline visualization
- ✅ Performance metrics
- ✅ System health monitoring

### AI & Document Parsing
- ✅ PDF/DOCX text extraction
- ✅ Skill & experience enrichment
- ✅ Job description generation
- ✅ Candidate matching analysis
- ✅ Interview question generation

## Authentication

Uses JWT authentication:
- Login at `/login`
- Token stored in localStorage
- Auto-refresh on expiration
- Redirects to login on 401

## Development

```bash
cd frontend/web-app
npm install
npm run dev
```

Access at http://localhost:3001

## Build

```bash
npm run build
```

## Environment Variables

```env
VITE_API_GATEWAY_URL=http://localhost:8080
```

## Routes

- `/` - Dashboard
- `/login` - Login page
- `/candidates` - Candidate list
- `/candidates/:id` - Candidate details
- `/vacancies` - Vacancy list
- `/vacancies/:id` - Vacancy details
- `/matches` - Match list
- `/interviews` - Interview list
- `/offers` - Offer list
- `/reports` - Reports dashboard
- `/admin` - Admin panel
