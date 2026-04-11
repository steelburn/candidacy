# Pending Items

## High-priority TODOs (Identified in Codebase)

- [ ] frontend/web-app: Connect error handler to an external logging service (e.g., Sentry, LogRocket) (`frontend/web-app/src/utils/errorHandler.js`).
- [ ] frontend/applicant-web-app: Parse analysis if it's text, or display summary in the Match View (`frontend/applicant-web-app/src/views/MatchView.vue`).
- [ ] services/tenant-service: Dispatch event to the notification service to send an email when inviting a tenant (`app/Http/Controllers/Api/TenantInvitationController.php`).

## Upcoming Features (Needs Scoping)

### Technical & Infrastructure
- [ ] **Background Processing**: Introduce a message queue (e.g., Redis/RabbitMQ) for interview scheduling notifications instead of the slow synchronous HTTP calls currently in `InterviewController` (interview-service).
- [ ] **Advanced Analytics**: Implement machine learning predictions for time-to-hire and pipeline bottleneck forecasting.

### Third-Party Integrations
- [ ] **Calendar Synchronization**: Sync scheduled interviews with Google Calendar and Outlook, sending automated iCal invitations.
- [ ] **Job Board Integrations**: Allow posting vacancies directly to LinkedIn, Indeed, etc.
- [ ] **Background Checks**: Integrate with 3rd-party services to auto-initiate background checks upon offer extension.
- [ ] **Video Interviews**: Native support for creating and joining Zoom/Google Meet links directly from the platform.

### Product Enhancements
- [ ] **Mobile Applications**: Develop native iOS and Android apps for on-the-go recruiters.
- [ ] **Multi-Language Support**: Complete localization of both the internal HR web app and the applicant portal.
- [ ] **E-Signatures**: Integrate document signing (e.g., DocuSign) for offer letters and contracts.
- [ ] **Candidate Assessments**: Send automated skills testing forms before the technical interview phase.
- [ ] **Recruitment Chatbot**: Implement an AI chatbot to guide candidates through initial screening on the applicant portal.
