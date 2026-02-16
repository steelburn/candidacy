CREATE TABLE `onboarding_checklists` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL COMMENT 'Logical FK to tenants table',
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

CREATE INDEX `idx_onboarding_tenant_id` ON `onboarding_checklists` (`tenant_id`);

CREATE INDEX `idx_onboarding_candidate_id` ON `onboarding_checklists` (`candidate_id`);

CREATE INDEX `idx_onboarding_status` ON `onboarding_checklists` (`status`);

CREATE INDEX `idx_onboarding_tenant_status` ON `onboarding_checklists` (`tenant_id`, `status`);

ALTER TABLE `onboarding_checklists` COMMENT = 'Onboarding tasks and checklists for new hires
Statuses: pending, in_progress, completed
Logical FK to candidate service';

