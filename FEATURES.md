# Candidacy Features

Comprehensive feature documentation for the AI-powered Candidacy recruitment platform.

## Overview

Candidacy is a complete recruitment management system with AI-powered capabilities for CV parsing, intelligent matching, and workflow automation. This document details all features across the platform.

---

## 🤖 AI-Powered Features

### CV Parsing
**Automatically extract structured data from resumes**

- **Supported Formats**: PDF, DOCX
- **Extracted Data**:
  - Personal information (name, email, phone)
  - Skills and technologies
  - Work experience (company, position, dates, description)
  - Education (institution, degree, field, dates)
- **AI Provider**: Configurable (Ollama local or OpenRouter cloud)
- **Model**: gemma2:2b (default, configurable)
- **Processing**: Asynchronous via queue
- **Accuracy**: High accuracy with structured resumes

**How it works:**
1. Candidate uploads CV via frontend or applicant portal
2. File stored securely in candidate service
3. CV sent to AI service for parsing
4. AI extracts structured data
5. Data automatically populates candidate profile
6. Manual editing available for corrections

### Job Description Generation
**AI creates compelling job descriptions from basic inputs**

- **Input Required**: Job title, department, key requirements
- **Output**: Markdown-formatted job description including:
  - Role overview
  - Key responsibilities
  - Required qualifications
  - Preferred qualifications
  - Benefits and perks
- **Customizable**: Generated content can be edited before publishing
- **Model**: gemma2:2b or other configured model

**How it works:**
1. HR enters basic job information
2. Clicks "Generate Description"
3. AI creates comprehensive JD
4. HR reviews and edits as needed
5. Publishes to vacancy listing

### Intelligent Candidate Matching
**Semantic matching between candidates and vacancies**

- **Match Scoring**: 0-100 score based on:
  - Skills overlap (40%)
  - Experience level (30%)
  - Education relevance (15%)
  - Domain knowledge (15%)
- **Detailed Analysis**: Markdown-formatted analysis with:
  - Overall fit percentage
  - Strengths (what candidate brings)
  - Gaps (what's missing)
  - Hiring recommendation
- **Threshold Filtering**: Configurable minimum match percentage (default: 70)
- **Batch Matching**: Match one candidate against all vacancies or vice versa

**Match Recommendations:**
- **Strong Match** (75-100): Highly recommended for interview
- **Good Match** (60-74): Worth considering
- **Weak Match** (40-59): Has significant gaps
- **No Match** (0-39): Not suitable

### Interview Questionnaire Generation
**AI-generated interview questions based on job requirements**

- **Input**: Vacancy title, requirements, difficulty level
- **Output**: Customized interview questions
- **Question Categories**: Technical, behavioral, situational
- **Difficulty Levels**: Junior, mid-level, senior
- **Customizable**: Questions can be edited or supplemented

---

## 👥 Candidate Management

### Candidate Profiles
- Create and manage candidate records
- Store personal information (name, email, phone)
- Track candidate status (active, inactive, hired)
- View complete candidate history

### CV Management
- Upload resumes (PDF/DOCX, max 10MB)
- Automatic parsing and data extraction
- Secure file storage
- Download original CV anytime

### Experience Tracking
- Add work experience manually or via CV parsing
- Track company, position, dates, description
- Support for current positions (no end date)
- Chronological display

### Education History
- Add education manually or via CV parsing
- Track institution, degree, field of study, dates
- Support for in-progress degrees
- Multiple degrees per candidate

### Skills Management
- Automatic skill extraction from CV
- Manual skill addition
- Proficiency levels (beginner, intermediate, advanced, expert)
- Skill categorization

---

## 💼 Vacancy Management

### Job Posting
- Create job postings with detailed information
- Fields: title, department, location, employment type
- Experience requirements
- Salary range (optional)
- Job description (markdown supported)
- Required skills list

### Vacancy Status
- **Open**: Actively recruiting
- **Closed**: Position filled or cancelled
- **On-Hold**: Temporarily paused

### Employment Types
- Full-time
- Part-time
- Contract/Freelance
- Internship

### Vacancy Analytics
- View applicant count
- See top matches
- Track time-to-fill
- Monitor vacancy status

---

## 🎯 Matching & Recommendations

### Automatic Matching
- Triggered when:
  - New candidate added
  - New vacancy created
  - CV uploaded
- Runs in background
- Results cached for performance

### Match Viewing
- View matches from candidate perspective
- View matches from vacancy perspective
- Sort by match score
- Filter by minimum threshold

### Match Analysis Display
- Markdown rendered as formatted HTML
- Clear sections for strengths and gaps
- Visual score indicator
- Recommendation badge

---

## 📅 Interview Management

### Interview Scheduling
- Schedule interviews with specific date/time
- Assign interviewer from user list
- Set interview duration
- Specify location (physical or virtual)
- Add pre-interview notes

### Interview Types
- Phone screening
- Technical assessment
- Behavioral interview
- Final round
- Panel interview

### Interview Status
- Scheduled
- Completed
- Cancelled
- No-show

### Feedback Collection
- Rating (1-5 stars)
- Technical skills assessment
- Communication evaluation
- Cultural fit assessment
- Strengths and concerns
- Hiring recommendation (hire, maybe, no hire)
- Additional notes

### Calendar Integration
- Structured data for calendar sync
- iCal export ready
- Email invitations via notification service

---

## 💰 Offer Management

### Offer Creation
- Link to candidate and vacancy
- Specify salary and currency
- Set employment type
- Propose start date
- List benefits and perks
- Set offer expiration date

### Offer Workflow
1. **Create**: HR creates offer
2. **Send**: Offer letter generated and emailed
3. **Pending**: Awaiting candidate response
4. **Accepted/Rejected**: Candidate responds
5. **Onboarding**: If accepted, triggers onboarding

### Offer Status
- Pending
- Accepted
- Rejected
- Withdrawn
- Expired

### Offer Letter Generation
- Template-based letter generation
- Includes all offer details
- Customizable per company
- PDF export ready

---

## 🎓 Onboarding

### Onboarding Workflow
- Automatically triggered when offer accepted
- Customizable checklist templates
- Role-based task lists
- Progress tracking

### Task Management
- Create tasks with deadlines
- Assign to HR, IT, Manager, or Employee
- Set priority (low, medium, high)
- Track completion status

### Task Categories
- Documentation (forms, contracts)
- Equipment (laptop, phone, access cards)
- Training (orientation, system training)
- Access (email, systems, building)
- Introduction (team meet, buddy assignment)

### Checklist Templates
- Software Engineer template
- HR Manager template
- Sales Representative template
- Custom templates per role

### Progress Tracking
- Completion percentage
- Overdue task alerts
- Task assignment notifications
- Completion confirmations

---

## 📊 Reporting & Analytics

### Dashboard Metrics
- Total candidates
- Active vacancies
- Interviews this month
- Pending offers
- Average time-to-hire
- Offer acceptance rate

### Pipeline Analytics
- Candidate distribution across stages
- Stage-to-stage conversion rates
- Bottleneck identification
- Pipeline health score

### Time-to-Hire
- Average days from application to hire
- Median time-to-hire
- Breakdown by department
- Historical trends

### Source Effectiveness
- Candidates by source (LinkedIn, referrals, job boards)
- Hire rate per source
- Conversion rate per source
- ROI analysis

### Interviewer Performance
- Interviews conducted
- Average rating given
- Feedback completion rate
- Hire rate for recommended candidates

### Hiring Trends
- Monthly/quarterly/yearly hiring patterns
- Department-wise hiring
- Forecasting and planning

---

## 🔔 Notifications

### Email Templates
**Candidate Notifications:**
- Application received confirmation
- Interview scheduled
- Interview reminder (24 hours before)
- Offer extended
- Offer accepted confirmation
- Welcome and onboarding instructions

**Interviewer Notifications:**
- Interview assigned
- Interview reminder
- Feedback request

**HR Notifications:**
- New candidate application
- Offer accepted/rejected
- Interview feedback submitted
- Task overdue

### Notification Features
- Template-based emails
- Variable substitution
- HTML and plain text versions
- Delivery tracking
- Retry logic for failures
- Queue-based async sending

---

## ⚙️ Admin & Configuration

### General Settings
- **Application Name**: Customize app name
- **Company Name**: Set company branding
- **Contact Email**: HR contact email
- **Candidate Portal URL**: Configure portal base URL (default: http://localhost:5173)
- **Login Background Image**: Set custom background image URL for login page

### AI Settings
- **AI Provider**: Choose Ollama (local) or OpenRouter (cloud)
- **Ollama URL**: Configure Ollama instance (supports external servers)
- **Matching Model**: Set AI model for matching (default: gemma2:2b)
- **Questionnaire Model**: Set AI model for questions (default: gemma2:2b)
- **Match Threshold**: Set minimum match percentage (default: 70)

### System Settings
- **Max Upload Size**: Configure file upload limit (default: 10MB)
- **Module Toggles**: Enable/disable features
- **Email Configuration**: SMTP settings
- **Maintenance Mode**: System maintenance toggle

---

## 🎨 Customization Features

### Login Page Customization
- Set custom background image via URL
- Configured in admin panel
- Supports any publicly accessible image URL
- Recommended: High-resolution images (1920px+)
- Example sources: Unsplash, company assets

**How to customize:**
1. Login as admin
2. Navigate to Admin panel
3. Enter image URL in "Login Background Image"
4. Save settings
5. Login page updates immediately

### Candidate Portal URL
- Configure base URL for candidate portal
- Used in:
  - Email links to candidates
  - Application submission links
  - Job posting links
- Default: http://localhost:5173
- Production: Set to actual domain

---

## 🔐 Security & Access Control

### User Roles
- **Admin**: Full system access, configuration management
- **HR Manager**: Manage vacancies, candidates, offers, onboarding
- **Recruiter**: Manage candidates, schedule interviews, view matches
- **Interviewer**: View assigned interviews, submit feedback
- **Viewer**: Read-only access to reports

### Authentication
- JWT token-based authentication
- Session management
- Password hashing (bcrypt)
- Token expiration
- Rate limiting on login attempts

### Authorization
- Role-based access control (RBAC)
- Endpoint-level permissions
- Resource-level permissions
- Activity logging

---

## 📈 Monitoring & Logging

### Centralized Logging
- **Loki**: Log aggregation
- **Promtail**: Log collection from all services
- **Grafana**: Log visualization and dashboards
- **Access**: http://localhost:3050 (admin/admin)

### Metrics Tracked
- Request count per endpoint
- Response times
- Error rates
- Service availability
- Queue lengths
- Database performance

### Health Monitoring
- Service health checks
- Database connectivity
- Redis connectivity
- External service status (AI)
- Disk and memory usage

---

## 🌐 Multi-Application Support

### Main Frontend (HR/Recruiter)
- **URL**: http://localhost:3001
- **Users**: HR, Recruiters, Admins, Interviewers
- **Features**: Full recruitment management
- **Technology**: Vue 3 + Vite

### Applicant Portal
- **URL**: http://localhost:5173
- **Users**: Job seekers, Candidates
- **Features**: Job browsing, application submission, status tracking
- **Technology**: Vue 3 + Vite

---

## 🚀 Performance Features

### Caching
- Settings cached for 10 minutes
- Match results cached for 1 hour
- Dashboard metrics cached for 5 minutes
- Public vacancy listings cached

### Async Processing
- CV parsing via queue
- Email sending via queue
- Batch matching via queue
- Report generation via queue

### Optimization
- Database indexing on key fields
- Connection pooling
- Response compression
- Code splitting in frontend
- Lazy loading of routes

---

## 📱 Responsive Design

All interfaces are fully responsive:
- Desktop (1920px+)
- Laptop (1366px - 1919px)
- Tablet (768px - 1365px)
- Mobile (< 768px)

---

## 🔄 Integration Capabilities

### Current Integrations
- **AI Providers**: Ollama, OpenRouter
- **Email**: SMTP, SendGrid, Mailgun (configurable)
- **Storage**: Local file system
- **Logging**: Loki/Grafana stack

### Future Integration Support
- Calendar systems (Google Calendar, Outlook)
- Job boards (LinkedIn, Indeed)
- Background check services
- HRIS systems
- Video interview platforms

---

## 📝 Markdown Support

### Where Markdown is Used
- Job descriptions
- Match analysis
- Interview questionnaires
- AI-generated content

### Rendering
- Frontend uses marked.js
- Markdown converted to HTML
- Sanitized for security
- Styled for readability

### Benefits
- Rich formatting
- Better readability
- Structured content
- Easy editing

---

## 🎯 Workflow Automation

### Automated Workflows
1. **CV Upload** → Parse → Extract Data → Save → Trigger Matching
2. **Vacancy Created** → Trigger Matching → Notify Recruiters
3. **Offer Accepted** → Create Onboarding → Send Welcome Email → Notify HR
4. **Interview Scheduled** → Send Calendar Invite → Send Reminders
5. **Match Found** → Notify Recruiter → Suggest Interview

### Event-Driven Architecture
- Services publish events
- Other services subscribe and react
- Decoupled and scalable
- Reliable message delivery

---

## 💡 Best Practices

### For Recruiters
- Upload CVs immediately for automatic parsing
- Review AI-generated matches before contacting candidates
- Use match analysis to prepare interview questions
- Submit interview feedback within 24 hours
- Keep candidate status updated

### For HR Managers
- Use AI job description generation as starting point
- Set realistic match thresholds
- Monitor pipeline metrics regularly
- Customize onboarding checklists per role
- Configure settings for optimal AI performance

### For Administrators
- Regularly review system logs in Grafana
- Monitor service health
- Keep AI models updated
- Backup databases regularly
- Review and update email templates

---

## 🔮 Roadmap Features

Planned enhancements:
- Mobile applications (iOS/Android)
- Video interview integration
- Advanced analytics with ML predictions
- Multi-language support
- Calendar synchronization
- Job board integrations
- Background check integration
- Offer letter e-signatures
- Candidate assessment tests
- Recruitment chatbot
