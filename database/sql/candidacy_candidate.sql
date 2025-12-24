CREATE TABLE `candidates` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) UNIQUE NOT NULL,
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

CREATE INDEX `idx_candidates_status` ON `candidates` (`status`);

CREATE INDEX `idx_candidates_created_at` ON `candidates` (`created_at`);

CREATE INDEX `idx_candidates_updated_at` ON `candidates` (`updated_at`);

CREATE INDEX `idx_candidates_status_created` ON `candidates` (`status`, `created_at`);

ALTER TABLE `candidates` COMMENT = 'Job candidates with their profiles and information';

ALTER TABLE `cv_files` COMMENT = 'Uploaded CV/resume files and their parsing status';

ALTER TABLE `candidate_tokens` COMMENT = 'Access tokens for candidate portal (logical FK to vacancy service)';

ALTER TABLE `applicant_answers` COMMENT = 'Candidate answers to vacancy questions (logical FKs to vacancy service)';

ALTER TABLE `job_statuses` COMMENT = 'Candidate application status tracking (logical FK to vacancy service)';

ALTER TABLE `cv_files` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

ALTER TABLE `candidate_tokens` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

ALTER TABLE `applicant_answers` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

ALTER TABLE `job_statuses` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

