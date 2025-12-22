# Candidacy Applicant Portal

Self-service web application for candidates to apply for jobs and track their applications.

## Purpose

The Applicant Portal provides a candidate-facing interface where job seekers can browse open positions, submit applications, upload resumes, and track their application status throughout the recruitment process.

## Technology Stack

- **Framework**: Vue 3 with Composition API
- **Build Tool**: Vite
- **UI Library**: Custom components
- **Routing**: Vue Router
- **HTTP Client**: Axios
- **Port**: 5173 (mapped from container port 3000)

## Key Features

### Job Search & Browse
- Browse all open vacancies
- Search by title, department, location
- Filter by employment type
- View detailed job descriptions

### Application Submission
- Apply for positions online
- Upload resume (PDF/DOCX)
- Fill out application form
- Submit cover letter

### Application Tracking
- View all submitted applications
- Track application status
- See interview schedules
- View offer status

### Profile Management
- Create and update candidate profile
- Manage contact information
- Upload and update resume
- Track application history

## Access

- **URL**: http://localhost:5173
- **Configurable**: Portal URL can be set via admin panel (candidate_portal_url setting)

## Main Routes

- `/` - Home page with job listings
- `/jobs` - Browse all open positions
- `/jobs/:id` - Job detail and application page
- `/apply/:id` - Application form
- `/applications` - My applications (requires login)
- `/profile` - Candidate profile (requires login)
- `/login` - Candidate login
- `/register` - New candidate registration

## Setup

### Development Mode

```bash
cd frontend/applicant-web-app
npm install
npm run dev
```

Access at http://localhost:5173

### Docker Mode

```bash
docker-compose up applicant-frontend
```

Access at http://localhost:5173

### Build for Production

```bash
npm run build
```

Outputs to `dist/` directory.

## Environment Variables

Create `.env` file:

```
VITE_API_BASE_URL=http://localhost:8080/api
```

## Key Components

### JobList.vue
Displays all open vacancies with search and filtering.

### JobDetail.vue
Shows detailed job description and "Apply" button.

### ApplicationForm.vue
Form for submitting job applications with resume upload.

### ApplicationTracker.vue
Shows candidate's application status and history.

### CandidateProfile.vue
Allows candidates to manage their profile and resume.

## Application Process

1. **Browse Jobs**
   - Candidate views open positions
   - Filters by criteria
   - Clicks on job for details

2. **Apply**
   - Clicks "Apply Now"
   - Fills out application form
   - Uploads resume
   - Submits application

3. **Track Status**
   - Logs in to portal
   - Views "My Applications"
   - Sees current status:
     - Applied
     - Under Review
     - Interview Scheduled
     - Offer Extended
     - Hired / Rejected

4. **Interview**
   - Receives interview notification
   - Views interview details in portal
   - Attends interview

5. **Offer**
   - Receives offer notification
   - Views offer details
   - Accepts or rejects offer

## Application Status

Candidates can see their application progress:

- **Applied** - Application submitted
- **Under Review** - Being reviewed by HR
- **Interview Scheduled** - Interview arranged
- **Offer Extended** - Job offer made
- **Hired** - Offer accepted, onboarding started
- **Rejected** - Application not successful

## API Integration

Communicates with backend via API Gateway:

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL
});

// Example: Browse jobs
const response = await api.get('/vacancies?status=open');

// Example: Submit application
const formData = new FormData();
formData.append('vacancy_id', vacancyId);
formData.append('cv', cvFile);
await api.post('/candidates', formData);
```

## Authentication

Candidates can create accounts to:
- Save application progress
- Track multiple applications
- Update profile information
- Receive notifications

### Registration
```javascript
await api.post('/auth/register', {
  name: 'John Doe',
  email: 'john@example.com',
  password: 'password123',
  role: 'candidate'
});
```

### Login
```javascript
const response = await api.post('/auth/login', {
  email: 'john@example.com',
  password: 'password123'
});
const token = response.data.token;
```

## Resume Upload

Supports PDF and DOCX formats:

```javascript
const handleFileUpload = async (file) => {
  const formData = new FormData();
  formData.append('cv', file);
  
  await api.post('/candidates/upload-cv', formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  });
};
```

## Responsive Design

Optimized for all devices:
- Desktop (1920px+)
- Tablet (768px - 1919px)
- Mobile (< 768px)

## Development

### Run Dev Server
```bash
npm run dev
```

### Lint Code
```bash
npm run lint
```

### Build for Production
```bash
npm run build
```

## Configuration

### Portal URL
The portal URL is configurable via the admin panel in the main application:

1. Admin logs into main app (http://localhost:3001)
2. Navigates to Admin panel
3. Sets "Candidate Portal Base URL"
4. URL is used in:
   - Email notifications to candidates
   - Application links
   - Job posting links

Default: http://localhost:5173

## Email Integration

Candidates receive emails with links to portal:
- Application confirmation
- Interview invitation
- Offer notification
- Status updates

All links point to the configured portal URL.

## Features in Detail

### Job Search
- Real-time search
- Filter by:
  - Department
  - Location
  - Employment type
  - Experience level
- Sort by:
  - Date posted
  - Relevance

### Application Form
Fields:
- Personal information (name, email, phone)
- Resume upload (required)
- Cover letter (optional)
- LinkedIn profile (optional)
- Availability date

### Application Dashboard
Shows for each application:
- Job title and company
- Application date
- Current status
- Next steps
- Interview details (if scheduled)

## Browser Support

- Chrome/Edge: Latest 2 versions
- Firefox: Latest 2 versions
- Safari: Latest 2 versions
- Mobile browsers: iOS Safari, Chrome Mobile

## Performance

- Lazy loading for job listings
- Image optimization
- Code splitting
- Cached API responses
- Progressive web app (PWA) ready

## Security

- Secure file uploads
- Input validation
- XSS protection
- CSRF protection
- Rate limiting on API calls

## Accessibility

- WCAG 2.1 AA compliant
- Keyboard navigation
- Screen reader support
- High contrast mode
- Semantic HTML

## Notes

- Candidates don't need an account to browse jobs
- Account required to apply and track applications
- Resume parsing happens automatically after upload
- Application data is private and secure
- Candidates can withdraw applications
- Multiple applications allowed for different positions
- Email notifications for all status changes
