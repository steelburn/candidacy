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

ALTER TABLE `onboarding_checklists` COMMENT = 'Onboarding tasks and checklists for new hires
Statuses: pending, in_progress, completed
Logical FK to candidate service';

