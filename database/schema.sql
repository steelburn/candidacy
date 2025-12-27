CREATE TABLE `settings` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `key` varchar(255) UNIQUE NOT NULL,
  `value` text,
  `type` varchar(255) NOT NULL DEFAULT "string",
  `category` varchar(255),
  `description` text,
  `is_public` boolean NOT NULL DEFAULT false,
  `is_sensitive` boolean NOT NULL DEFAULT false,
  `validation_rules` json,
  `default_value` text,
  `service_scope` varchar(255),
  `requires_restart` boolean NOT NULL DEFAULT false,
  `version` int NOT NULL DEFAULT 1,
  `updated_by` bigint,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `setting_change_logs` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `setting_id` bigint NOT NULL,
  `old_value` text,
  `new_value` text,
  `changed_by` bigint,
  `changed_at` timestamp DEFAULT (CURRENT_TIMESTAMP),
  `ip_address` varchar(45),
  `user_agent` varchar(255)
);

CREATE TABLE `users` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) UNIQUE NOT NULL,
  `email_verified_at` timestamp,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100),
  `created_at` timestamp,
  `updated_at` timestamp,
  `deleted_at` timestamp,
  `is_active` boolean NOT NULL DEFAULT true
);

CREATE TABLE `roles` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) UNIQUE NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `permissions` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) UNIQUE NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `role_user` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `role_id` bigint NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `permission_role` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `permission_id` bigint NOT NULL,
  `role_id` bigint NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) PRIMARY KEY,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp
);

CREATE TABLE `personal_access_tokens` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) UNIQUE NOT NULL,
  `abilities` text,
  `last_used_at` timestamp,
  `expires_at` timestamp,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `failed_jobs` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `uuid` varchar(255) UNIQUE NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp DEFAULT (CURRENT_TIMESTAMP)
);

CREATE TABLE `candidates` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255),
  `email` varchar(255),
  `phone` varchar(255),
  `summary` text,
  `linkedin_url` varchar(255),
  `github_url` varchar(255),
  `portfolio_url` varchar(255),
  `skills` json,
  `experience` json,
  `education` json,
  `status` varchar(255) NOT NULL DEFAULT "new",
  `notes` text,
  `years_of_experience` int,
  `generated_cv_content` longtext,
  `pin_code` varchar(255),
  `created_at` timestamp,
  `updated_at` timestamp,
  `deleted_at` timestamp
);

CREATE TABLE `cv_files` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `candidate_id` bigint NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int NOT NULL,
  `extracted_text` longtext,
  `parsed_data` json,
  `parsing_status` varchar(50) DEFAULT "pending",
  `parsing_error` text,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `candidate_tokens` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `candidate_id` bigint NOT NULL,
  `token` varchar(255) UNIQUE NOT NULL,
  `vacancy_id` bigint,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `applicant_answers` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `candidate_id` bigint NOT NULL,
  `vacancy_id` bigint NOT NULL,
  `question_id` bigint NOT NULL,
  `answer` text NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `job_statuses` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `candidate_id` bigint NOT NULL,
  `vacancy_id` bigint NOT NULL,
  `status` varchar(255) NOT NULL,
  `notes` text,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `cv_parsing_jobs` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `candidate_id` bigint,
  `file_path` varchar(255) NOT NULL,
  `extracted_text` longtext,
  `status` varchar(50) NOT NULL DEFAULT "pending",
  `parsed_data` json,
  `error_message` text,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `jobs` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint NOT NULL,
  `reserved_at` int,
  `available_at` int NOT NULL,
  `created_at` int NOT NULL
);

CREATE TABLE `failed_jobs_candidate` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `uuid` varchar(255) UNIQUE NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp DEFAULT (CURRENT_TIMESTAMP)
);

CREATE TABLE `parse_jobs` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT "pending",
  `extracted_text` longtext,
  `error_message` text,
  `file_size` int,
  `page_count` int,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `failed_jobs_parser` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `uuid` varchar(255) UNIQUE NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp DEFAULT (CURRENT_TIMESTAMP)
);

CREATE TABLE `interviews` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `candidate_id` bigint NOT NULL,
  `vacancy_id` bigint NOT NULL,
  `interviewer_id` bigint,
  `stage` varchar(255) NOT NULL DEFAULT "screening",
  `scheduled_at` datetime NOT NULL,
  `duration_minutes` int NOT NULL DEFAULT 60,
  `location` varchar(255),
  `type` varchar(255) NOT NULL DEFAULT "video",
  `status` varchar(255) NOT NULL DEFAULT "scheduled",
  `notes` text,
  `interviewer_ids` json,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `interview_feedback` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `interview_id` bigint NOT NULL,
  `reviewer_id` bigint NOT NULL,
  `technical_score` int,
  `communication_score` int,
  `cultural_fit_score` int,
  `overall_score` int,
  `strengths` text,
  `weaknesses` text,
  `comments` text,
  `recommendation` varchar(255),
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `matches` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `candidate_id` bigint NOT NULL,
  `vacancy_id` bigint NOT NULL,
  `match_score` int NOT NULL DEFAULT 0,
  `analysis` json,
  `status` varchar(255) NOT NULL DEFAULT "pending",
  `interview_questions` json,
  `applied_at` timestamp,
  `questionnaire_completed` boolean DEFAULT false,
  `questionnaire_metadata` json,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `job_statuses_matching` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `candidate_id` bigint NOT NULL,
  `vacancy_id` bigint NOT NULL,
  `status` varchar(255) NOT NULL,
  `notes` text,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `matching_job_statuses` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `result` json,
  `error` text,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `failed_jobs_matching` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `uuid` varchar(255) UNIQUE NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp DEFAULT (CURRENT_TIMESTAMP)
);

CREATE TABLE `notification_templates` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(100) UNIQUE NOT NULL COMMENT 'Unique template identifier',
  `subject` varchar(255) NOT NULL COMMENT 'Email subject with variable placeholders',
  `body` text NOT NULL COMMENT 'Email body content with variable placeholders',
  `type` varchar(50) NOT NULL COMMENT 'interview_scheduled, offer_sent, reminder, etc.',
  `variables` json COMMENT 'List of available variables and their descriptions',
  `is_active` boolean DEFAULT true,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `notification_logs` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `template_id` bigint COMMENT 'Reference to template used, null if direct content',
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(255),
  `subject` varchar(255) NOT NULL,
  `body` text COMMENT 'Rendered email body',
  `type` varchar(50) NOT NULL COMMENT 'Notification type',
  `channel` varchar(20) DEFAULT "email" COMMENT 'email, sms, push',
  `status` varchar(20) DEFAULT "pending" COMMENT 'pending, sent, failed',
  `metadata` json COMMENT 'Additional context: candidate_id, vacancy_id, etc.',
  `sent_at` timestamp,
  `failed_at` timestamp,
  `error_message` text,
  `retry_count` int DEFAULT 0,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `offers` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `candidate_id` bigint NOT NULL,
  `vacancy_id` bigint NOT NULL,
  `salary_offered` decimal(12,2) NOT NULL,
  `currency` varchar(255) NOT NULL DEFAULT "USD",
  `benefits` json,
  `start_date` date,
  `offer_date` date NOT NULL,
  `expiry_date` date,
  `status` varchar(255) NOT NULL DEFAULT "pending",
  `terms` text,
  `candidate_response` text,
  `responded_at` timestamp,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `onboarding_checklists` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `candidate_id` bigint NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `description` text,
  `status` varchar(255) NOT NULL DEFAULT "pending",
  `due_date` date,
  `completed_at` timestamp,
  `notes` text,
  `order` int NOT NULL DEFAULT 0,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `vacancies` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `requirements` text,
  `responsibilities` text,
  `department` varchar(255),
  `location` varchar(255) NOT NULL,
  `employment_type` varchar(255) NOT NULL DEFAULT "full_time",
  `experience_level` varchar(255) NOT NULL DEFAULT "mid",
  `min_experience_years` int,
  `max_experience_years` int,
  `min_salary` decimal(10,2),
  `max_salary` decimal(10,2),
  `currency` varchar(255) NOT NULL DEFAULT "USD",
  `required_skills` json,
  `preferred_skills` json,
  `benefits` json,
  `status` varchar(255) NOT NULL DEFAULT "draft",
  `closing_date` date,
  `positions_available` int NOT NULL DEFAULT 1,
  `work_mode` varchar(255),
  `created_at` timestamp,
  `updated_at` timestamp,
  `deleted_at` timestamp
);

CREATE TABLE `vacancy_questions` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `vacancy_id` bigint NOT NULL,
  `question_text` varchar(255) NOT NULL,
  `question_type` varchar(255) NOT NULL DEFAULT "text",
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `required_skills` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE INDEX `idx_settings_category` ON `settings` (`category`);

CREATE INDEX `idx_settings_service_scope` ON `settings` (`service_scope`);

CREATE INDEX `idx_settings_is_public` ON `settings` (`is_public`);

CREATE INDEX `idx_setting_logs_setting_id` ON `setting_change_logs` (`setting_id`);

CREATE INDEX `idx_setting_logs_changed_at` ON `setting_change_logs` (`changed_at`);

CREATE INDEX `idx_setting_logs_changed_by` ON `setting_change_logs` (`changed_by`);

CREATE INDEX `idx_users_created_at` ON `users` (`created_at`);

CREATE UNIQUE INDEX `role_user_index_7` ON `role_user` (`user_id`, `role_id`);

CREATE UNIQUE INDEX `permission_role_index_8` ON `permission_role` (`permission_id`, `role_id`);

CREATE INDEX `personal_access_tokens_tokenable` ON `personal_access_tokens` (`tokenable_type`, `tokenable_id`);

CREATE INDEX `idx_candidates_status` ON `candidates` (`status`);

CREATE INDEX `idx_candidates_created_at` ON `candidates` (`created_at`);

CREATE INDEX `idx_candidates_updated_at` ON `candidates` (`updated_at`);

CREATE INDEX `idx_candidates_status_created` ON `candidates` (`status`, `created_at`);

CREATE INDEX `idx_cv_parsing_jobs_status` ON `cv_parsing_jobs` (`status`);

CREATE INDEX `idx_cv_parsing_jobs_candidate_id` ON `cv_parsing_jobs` (`candidate_id`);

CREATE INDEX `idx_cv_parsing_jobs_created_at` ON `cv_parsing_jobs` (`created_at`);

CREATE INDEX `jobs_queue_index` ON `jobs` (`queue`);

CREATE INDEX `idx_parse_jobs_status` ON `parse_jobs` (`status`);

CREATE INDEX `idx_parse_jobs_created_at` ON `parse_jobs` (`created_at`);

CREATE INDEX `idx_interviews_candidate_id` ON `interviews` (`candidate_id`);

CREATE INDEX `idx_interviews_vacancy_id` ON `interviews` (`vacancy_id`);

CREATE INDEX `idx_interviews_interviewer_id` ON `interviews` (`interviewer_id`);

CREATE INDEX `idx_interviews_status` ON `interviews` (`status`);

CREATE INDEX `idx_interviews_type` ON `interviews` (`type`);

CREATE INDEX `idx_interviews_scheduled_at` ON `interviews` (`scheduled_at`);

CREATE INDEX `idx_interviews_created_at` ON `interviews` (`created_at`);

CREATE INDEX `idx_interviews_interviewer_schedule` ON `interviews` (`interviewer_id`, `scheduled_at`);

CREATE INDEX `idx_interviews_candidate_schedule` ON `interviews` (`candidate_id`, `scheduled_at`);

CREATE INDEX `idx_interviews_status_schedule` ON `interviews` (`status`, `scheduled_at`);

CREATE INDEX `idx_matches_candidate_id` ON `matches` (`candidate_id`);

CREATE INDEX `idx_matches_vacancy_id` ON `matches` (`vacancy_id`);

CREATE INDEX `idx_matches_score` ON `matches` (`match_score`);

CREATE INDEX `idx_matches_status` ON `matches` (`status`);

CREATE INDEX `idx_matches_created_at` ON `matches` (`created_at`);

CREATE INDEX `idx_matches_score_status` ON `matches` (`match_score`, `status`);

CREATE INDEX `idx_matches_candidate_score` ON `matches` (`candidate_id`, `match_score`);

CREATE INDEX `idx_matches_vacancy_score` ON `matches` (`vacancy_id`, `match_score`);

CREATE UNIQUE INDEX `matches_candidate_id_vacancy_id_unique` ON `matches` (`candidate_id`, `vacancy_id`);

CREATE INDEX `idx_matching_job_statuses_status` ON `matching_job_statuses` (`status`);

CREATE INDEX `idx_matching_job_statuses_created` ON `matching_job_statuses` (`created_at`);

CREATE INDEX `notification_templates_index_41` ON `notification_templates` (`type`);

CREATE INDEX `notification_templates_index_42` ON `notification_templates` (`is_active`);

CREATE INDEX `notification_logs_index_43` ON `notification_logs` (`recipient_email`);

CREATE INDEX `notification_logs_index_44` ON `notification_logs` (`type`);

CREATE INDEX `notification_logs_index_45` ON `notification_logs` (`status`);

CREATE INDEX `notification_logs_index_46` ON `notification_logs` (`created_at`);

CREATE INDEX `idx_offers_candidate_id` ON `offers` (`candidate_id`);

CREATE INDEX `idx_offers_vacancy_id` ON `offers` (`vacancy_id`);

CREATE INDEX `idx_offers_status` ON `offers` (`status`);

CREATE INDEX `idx_offers_expiry_date` ON `offers` (`expiry_date`);

CREATE INDEX `idx_offers_start_date` ON `offers` (`start_date`);

CREATE INDEX `idx_offers_created_at` ON `offers` (`created_at`);

CREATE INDEX `idx_offers_status_expiry` ON `offers` (`status`, `expiry_date`);

CREATE INDEX `idx_offers_candidate_status` ON `offers` (`candidate_id`, `status`);

CREATE INDEX `idx_vacancies_status` ON `vacancies` (`status`);

CREATE INDEX `idx_vacancies_department` ON `vacancies` (`department`);

CREATE INDEX `idx_vacancies_location` ON `vacancies` (`location`);

CREATE INDEX `idx_vacancies_employment_type` ON `vacancies` (`employment_type`);

CREATE INDEX `idx_vacancies_created_at` ON `vacancies` (`created_at`);

CREATE INDEX `idx_vacancies_updated_at` ON `vacancies` (`updated_at`);

CREATE INDEX `idx_vacancies_status_dept` ON `vacancies` (`status`, `department`);

CREATE INDEX `idx_vacancies_status_created` ON `vacancies` (`status`, `created_at`);

ALTER TABLE `settings` COMMENT = 'Application-wide settings and configuration
Types: string, integer, boolean, json
Public settings are accessible without authentication
Sensitive settings are masked in UI (API keys, passwords)
Service scope indicates which service(s) use this setting
Validation rules stored as JSON schema';

ALTER TABLE `setting_change_logs` COMMENT = 'Audit trail for configuration changes
Tracks who changed what configuration, when, and from where
Enables configuration rollback and compliance tracking';

ALTER TABLE `users` COMMENT = 'System users (HR managers, recruiters, interviewers, admins)';

ALTER TABLE `roles` COMMENT = 'User roles: admin, hr_manager, recruiter, interviewer, viewer';

ALTER TABLE `permissions` COMMENT = 'Granular permissions for access control';

ALTER TABLE `role_user` COMMENT = 'Many-to-many relationship between users and roles';

ALTER TABLE `permission_role` COMMENT = 'Many-to-many relationship between permissions and roles';

ALTER TABLE `personal_access_tokens` COMMENT = 'Laravel Sanctum API tokens';

ALTER TABLE `candidates` COMMENT = 'Job candidates with their profiles and information';

ALTER TABLE `cv_files` COMMENT = 'Uploaded CV/resume files and their parsing status';

ALTER TABLE `candidate_tokens` COMMENT = 'Access tokens for candidate portal (logical FK to vacancy service)';

ALTER TABLE `applicant_answers` COMMENT = 'Candidate answers to vacancy questions (logical FKs to vacancy service)';

ALTER TABLE `job_statuses` COMMENT = 'Candidate application status tracking (logical FK to vacancy service)';

ALTER TABLE `cv_parsing_jobs` COMMENT = 'Async AI CV parsing jobs - tracks background processing of CV text extraction';

ALTER TABLE `jobs` COMMENT = 'Database queue jobs table for fallback when Redis is unavailable';

ALTER TABLE `failed_jobs_candidate` COMMENT = 'Failed queue jobs for candidate service';

ALTER TABLE `parse_jobs` COMMENT = 'Asynchronous document parsing jobs for PDF/DOCX files
Statuses: pending, processing, completed, failed';

ALTER TABLE `failed_jobs_parser` COMMENT = 'Failed queue jobs for document parser service';

ALTER TABLE `interviews` COMMENT = 'Interview scheduling and management
Stages: screening, technical, behavioral, final
Types: in_person, video, phone
Statuses: scheduled, completed, cancelled, rescheduled
Logical FKs to candidate, vacancy, and user (interviewer) services';

ALTER TABLE `interview_feedback` COMMENT = 'Interview feedback and scoring
Scores: 1-10 scale
Recommendations: strong_hire, hire, maybe, no_hire
Logical FK to user (reviewer) service';

ALTER TABLE `matches` COMMENT = 'AI-generated candidate-vacancy matches
Statuses: pending, reviewed, accepted, rejected, shortlisted, applied, dismissed
Logical FKs to candidate and vacancy services

Business Rules:
- Matches with scores below 40% are automatically discarded (not saved)
- Missing RECOMMENDATION in analysis triggers retry (up to 3 attempts)';

ALTER TABLE `job_statuses_matching` COMMENT = 'Job application status tracking (logical FKs to candidate and vacancy services)';

ALTER TABLE `matching_job_statuses` COMMENT = 'Background job tracking for matching service';

ALTER TABLE `failed_jobs_matching` COMMENT = 'Failed queue jobs for matching service';

ALTER TABLE `offers` COMMENT = 'Job offers extended to candidates
Statuses: pending, accepted, rejected, withdrawn, expired
Logical FKs to candidate and vacancy services';

ALTER TABLE `onboarding_checklists` COMMENT = 'Onboarding tasks and checklists for new hires
Statuses: pending, in_progress, completed
Logical FK to candidate service';

ALTER TABLE `vacancies` COMMENT = 'Job vacancies/positions
Employment types: full_time, part_time, contract, intern
Experience levels: entry, mid, senior, lead, executive
Statuses: draft, open, closed, on_hold
Work modes: on-site, remote, hybrid';

ALTER TABLE `vacancy_questions` COMMENT = 'Custom questions for vacancy applications';

ALTER TABLE `required_skills` COMMENT = 'Placeholder table for required skills (currently unused)';

ALTER TABLE `setting_change_logs` ADD FOREIGN KEY (`setting_id`) REFERENCES `settings` (`id`);

ALTER TABLE `role_user` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `role_user` ADD FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

ALTER TABLE `permission_role` ADD FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`);

ALTER TABLE `permission_role` ADD FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

ALTER TABLE `cv_files` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

ALTER TABLE `candidate_tokens` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

ALTER TABLE `applicant_answers` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

ALTER TABLE `job_statuses` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

ALTER TABLE `cv_parsing_jobs` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

ALTER TABLE `interview_feedback` ADD FOREIGN KEY (`interview_id`) REFERENCES `interviews` (`id`);

ALTER TABLE `notification_logs` ADD FOREIGN KEY (`template_id`) REFERENCES `notification_templates` (`id`);

ALTER TABLE `vacancy_questions` ADD FOREIGN KEY (`vacancy_id`) REFERENCES `vacancies` (`id`);

