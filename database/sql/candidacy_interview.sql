CREATE TABLE `interviews` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL COMMENT 'Logical FK to tenants table',
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

CREATE INDEX `idx_interviews_tenant_id` ON `interviews` (`tenant_id`);

CREATE INDEX `idx_interviews_candidate_id` ON `interviews` (`candidate_id`);

CREATE INDEX `idx_interviews_vacancy_id` ON `interviews` (`vacancy_id`);

CREATE INDEX `idx_interviews_interviewer_id` ON `interviews` (`interviewer_id`);

CREATE INDEX `idx_interviews_status` ON `interviews` (`status`);

CREATE INDEX `idx_interviews_type` ON `interviews` (`type`);

CREATE INDEX `idx_interviews_scheduled_at` ON `interviews` (`scheduled_at`);

CREATE INDEX `idx_interviews_created_at` ON `interviews` (`created_at`);

CREATE INDEX `idx_interviews_tenant_status` ON `interviews` (`tenant_id`, `status`);

CREATE INDEX `idx_interviews_interviewer_schedule` ON `interviews` (`interviewer_id`, `scheduled_at`);

CREATE INDEX `idx_interviews_candidate_schedule` ON `interviews` (`candidate_id`, `scheduled_at`);

CREATE INDEX `idx_interviews_status_schedule` ON `interviews` (`status`, `scheduled_at`);

ALTER TABLE `interviews` COMMENT = 'Interview scheduling and management
Stages: screening, technical, behavioral, final
Types: in_person, video, phone
Statuses: scheduled, completed, cancelled, rescheduled
Logical FKs to candidate, vacancy, and user (interviewer) services';

ALTER TABLE `interview_feedback` COMMENT = 'Interview feedback and scoring
Scores: 1-10 scale
Recommendations: strong_hire, hire, maybe, no_hire
Logical FK to user (reviewer) service';

ALTER TABLE `interview_feedback` ADD FOREIGN KEY (`interview_id`) REFERENCES `interviews` (`id`);

