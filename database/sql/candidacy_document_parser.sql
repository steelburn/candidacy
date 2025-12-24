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

CREATE INDEX `idx_parse_jobs_status` ON `parse_jobs` (`status`);

CREATE INDEX `idx_parse_jobs_created_at` ON `parse_jobs` (`created_at`);

ALTER TABLE `parse_jobs` COMMENT = 'Asynchronous document parsing jobs for PDF/DOCX files
Statuses: pending, processing, completed, failed';

