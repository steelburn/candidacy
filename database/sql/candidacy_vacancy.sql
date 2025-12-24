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

CREATE INDEX `idx_vacancies_status` ON `vacancies` (`status`);

CREATE INDEX `idx_vacancies_department` ON `vacancies` (`department`);

CREATE INDEX `idx_vacancies_location` ON `vacancies` (`location`);

CREATE INDEX `idx_vacancies_employment_type` ON `vacancies` (`employment_type`);

CREATE INDEX `idx_vacancies_created_at` ON `vacancies` (`created_at`);

CREATE INDEX `idx_vacancies_updated_at` ON `vacancies` (`updated_at`);

CREATE INDEX `idx_vacancies_status_dept` ON `vacancies` (`status`, `department`);

CREATE INDEX `idx_vacancies_status_created` ON `vacancies` (`status`, `created_at`);

ALTER TABLE `vacancies` COMMENT = 'Job vacancies/positions
Employment types: full_time, part_time, contract, intern
Experience levels: entry, mid, senior, lead, executive
Statuses: draft, open, closed, on_hold
Work modes: on-site, remote, hybrid';

ALTER TABLE `vacancy_questions` COMMENT = 'Custom questions for vacancy applications';

ALTER TABLE `required_skills` COMMENT = 'Placeholder table for required skills (currently unused)';

ALTER TABLE `vacancy_questions` ADD FOREIGN KEY (`vacancy_id`) REFERENCES `vacancies` (`id`);

