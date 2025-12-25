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

ALTER TABLE `matches` COMMENT = 'AI-generated candidate-vacancy matches
Statuses: pending, reviewed, accepted, rejected, shortlisted, applied, dismissed
Logical FKs to candidate and vacancy services

Business Rules:
- Matches with scores below 40% are automatically discarded (not saved)
- Missing RECOMMENDATION in analysis triggers retry (up to 3 attempts)';

ALTER TABLE `job_statuses_matching` COMMENT = 'Job application status tracking (logical FKs to candidate and vacancy services)';

ALTER TABLE `matching_job_statuses` COMMENT = 'Background job tracking for matching service';

ALTER TABLE `failed_jobs_matching` COMMENT = 'Failed queue jobs for matching service';

