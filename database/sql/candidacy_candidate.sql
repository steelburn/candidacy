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

CREATE INDEX `idx_candidates_status` ON `candidates` (`status`);

CREATE INDEX `idx_candidates_created_at` ON `candidates` (`created_at`);

CREATE INDEX `idx_candidates_updated_at` ON `candidates` (`updated_at`);

CREATE INDEX `idx_candidates_status_created` ON `candidates` (`status`, `created_at`);

CREATE INDEX `idx_cv_parsing_jobs_status` ON `cv_parsing_jobs` (`status`);

CREATE INDEX `idx_cv_parsing_jobs_candidate_id` ON `cv_parsing_jobs` (`candidate_id`);

CREATE INDEX `idx_cv_parsing_jobs_created_at` ON `cv_parsing_jobs` (`created_at`);

CREATE INDEX `jobs_queue_index` ON `jobs` (`queue`);

ALTER TABLE `candidates` COMMENT = 'Job candidates with their profiles and information';

ALTER TABLE `cv_files` COMMENT = 'Uploaded CV/resume files and their parsing status';

ALTER TABLE `candidate_tokens` COMMENT = 'Access tokens for candidate portal (logical FK to vacancy service)';

ALTER TABLE `applicant_answers` COMMENT = 'Candidate answers to vacancy questions (logical FKs to vacancy service)';

ALTER TABLE `job_statuses` COMMENT = 'Candidate application status tracking (logical FK to vacancy service)';

ALTER TABLE `cv_parsing_jobs` COMMENT = 'Async AI CV parsing jobs - tracks background processing of CV text extraction';

ALTER TABLE `jobs` COMMENT = 'Database queue jobs table for fallback when Redis is unavailable';

ALTER TABLE `failed_jobs_candidate` COMMENT = 'Failed queue jobs for candidate service';

ALTER TABLE `cv_files` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

ALTER TABLE `candidate_tokens` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

ALTER TABLE `applicant_answers` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

ALTER TABLE `job_statuses` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

ALTER TABLE `cv_parsing_jobs` ADD FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

