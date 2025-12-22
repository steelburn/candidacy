# Candidacy Main Frontend (HR/Recruiter Dashboard)

Main web application for HR and recruitment teams using the Candidacy recruitment platform.

## Purpose

The main frontend provides a comprehensive dashboard for HR managers, recruiters, and administrators to manage the entire recruitment lifecycle. Built with Vue 3 and Vite, it offers a modern, responsive interface for all recruitment operations.

## Technology Stack

- **Framework**: Vue 3 with Composition API
- **Build Tool**: Vite
- **UI Library**: Custom components
- **Routing**: Vue Router
- **State Management**: Pinia
- **HTTP Client**: Axios
- **Markdown Rendering**: marked.js (for AI-generated content)
- **Port**: 3001 (mapped from container port 3000)

## Key Features

### Candidate Management
- View all candidates with search and filtering
- Upload and parse CVs (PDF/DOCX)
- View candidate profiles with experience and education
- Track candidate status through recruitment pipeline

### Vacancy Management
- Create and manage job postings
- AI-powered job description generation
- Track vacancy status (open, closed, on-hold)
- View applicants per vacancy

### Intelligent Matching
- View AI-powered candidate-vacancy matches
- Match scores and detailed analysis
- Markdown-rendered match analysis with strengths/gaps
- Batch matching capabilities

### Interview Scheduling
- Schedule interviews with calendar integration
- Assign interviewers
- Track interview status
- Collect and view interview feedback

### Offer Management
- Create job offers with compensation details
- Track offer status (pending, accepted, rejected)
- Generate offer letters
- Monitor offer acceptance rates

### Onboarding
- View onboarding progress for new hires
- Track onboarding task completion
- Manage onboarding checklists

### Admin Panel
- **Login Customization**: Set custom background image URL for login page
- **Portal Configuration**: Configure candidate portal base URL
- **AI Settings**: Configure AI provider (Ollama/OpenRouter), models, and thresholds
- **System Settings**: Manage application-wide settings

### Reporting & Analytics
- Recruitment metrics dashboard
- Time-to-hire analytics
- Pipeline visualization
- Conversion rates
- Source effectiveness

## Access

- **URL**: http://localhost:3001
- **Default Credentials**:
  - Email: admin@candidacy.com
  - Password: password123

## User Roles

The application supports different views based on user role:

- **Admin**: Full access to all features including admin panel
- **HR Manager**: Manage vacancies, candidates, offers, onboarding
- **Recruiter**: Manage candidates, schedule interviews, view matches
- **Interviewer**: View assigned interviews, submit feedback
- **Viewer**: Read-only access to reports and dashboards

## Main Routes

- `/` - Dashboard
- `/candidates` - Candidate list and management
- `/candidates/:id` - Candidate detail view
- `/vacancies` - Vacancy list and management
- `/vacancies/:id` - Vacancy detail view
- `/matches` - Candidate-vacancy matches
- `/interviews` - Interview schedule and management
- `/offers` - Offer tracking
- `/onboarding` - Onboarding management
- `/reports` - Analytics and reports
- `/admin` - Admin panel (admin only)
- `/login` - Login page with customizable background

## Setup

### Development Mode

```bash
cd frontend/web-app
npm install
npm run dev
```

Access at http://localhost:3000

### Docker Mode

```bash
docker-compose up frontend
```

Access at http://localhost:3001

### Build for Production

```bash
npm run build
```

Outputs to `dist/` directory.

## Environment Variables

Create `.env` file:

```
VITE_API_GATEWAY_URL=http://localhost:8080
```

## Key Components

### CandidateList.vue
Displays paginated list of candidates with search and filters.

### CandidateDetail.vue
Shows detailed candidate profile including CV, experience, education, skills, and matches.

### VacancyForm.vue
Form for creating/editing vacancies with AI job description generation.

### MatchAnalysis.vue
Displays AI-generated match analysis with markdown rendering.

### InterviewScheduler.vue
Calendar-based interface for scheduling interviews.

### Admin.vue
Admin panel for system configuration including:
- Login background image URL
- Candidate portal URL
- AI provider and model settings
- System preferences

## Markdown Rendering

AI-generated content (match analysis, job descriptions) is rendered as HTML using marked.js:

```javascript
import { marked } from 'marked';

const htmlContent = marked(markdownText);
```

This provides formatted, readable output for:
- Match analysis with strengths and gaps
- AI-generated job descriptions
- Interview questionnaires

## API Integration

All API calls go through the API Gateway:

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_GATEWAY_URL,
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

// Example: Fetch candidates
const response = await api.get('/api/candidates');
```

## State Management

Uses Pinia for global state:

```javascript
// stores/auth.js
export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null
  }),
  actions: {
    async login(credentials) {
      // Login logic
    }
  }
});
```

## Development

### Run Dev Server
```bash
npm run dev
```

### Lint Code
```bash
npm run lint
```

### Format Code
```bash
npm run format
```

### Type Check
```bash
npm run type-check
```

## Customization

### Login Background
Set via Admin panel:
1. Login as admin
2. Navigate to Admin panel
3. Enter image URL in "Login Background Image" field
4. Save settings

The login page will dynamically load the background image.

### Branding
Update company name and branding in Admin panel settings.

## Features in Detail

### CV Upload & Parsing
1. Navigate to candidate detail
2. Click "Upload CV"
3. Select PDF or DOCX file
4. AI automatically extracts:
   - Skills
   - Work experience
   - Education
   - Contact information

### AI-Powered Matching
1. Create or select a vacancy
2. View "Matches" tab
3. See ranked candidates with match scores
4. Click on match to view detailed analysis
5. Analysis shows:
   - Overall fit percentage
   - Strengths
   - Gaps
   - Recommendation

### Interview Scheduling
1. Select candidate and vacancy
2. Click "Schedule Interview"
3. Choose interviewer, date, time
4. Add location (physical or virtual)
5. Save - notifications sent automatically

## Browser Support

- Chrome/Edge: Latest 2 versions
- Firefox: Latest 2 versions
- Safari: Latest 2 versions

## Performance

- Code splitting for faster initial load
- Lazy loading for routes
- Image optimization
- API response caching
- Debounced search inputs

## Security

- JWT token-based authentication
- Token stored in httpOnly cookies (recommended)
- CORS configured for API gateway
- XSS protection via Vue's template escaping
- Input validation on all forms

## Notes

- All dates displayed in local timezone
- File uploads limited to 10MB (configurable via admin)
- Session timeout after 1 hour of inactivity
- Markdown content is sanitized before rendering
- Real-time updates via polling (WebSocket support planned)
