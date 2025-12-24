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

CREATE INDEX `idx_offers_candidate_id` ON `offers` (`candidate_id`);

CREATE INDEX `idx_offers_vacancy_id` ON `offers` (`vacancy_id`);

CREATE INDEX `idx_offers_status` ON `offers` (`status`);

CREATE INDEX `idx_offers_expiry_date` ON `offers` (`expiry_date`);

CREATE INDEX `idx_offers_start_date` ON `offers` (`start_date`);

CREATE INDEX `idx_offers_created_at` ON `offers` (`created_at`);

CREATE INDEX `idx_offers_status_expiry` ON `offers` (`status`, `expiry_date`);

CREATE INDEX `idx_offers_candidate_status` ON `offers` (`candidate_id`, `status`);

ALTER TABLE `offers` COMMENT = 'Job offers extended to candidates
Statuses: pending, accepted, rejected, withdrawn, expired
Logical FKs to candidate and vacancy services';

