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

CREATE INDEX `idx_settings_category` ON `settings` (`category`);

CREATE INDEX `idx_settings_service_scope` ON `settings` (`service_scope`);

CREATE INDEX `idx_settings_is_public` ON `settings` (`is_public`);

CREATE INDEX `idx_setting_logs_setting_id` ON `setting_change_logs` (`setting_id`);

CREATE INDEX `idx_setting_logs_changed_at` ON `setting_change_logs` (`changed_at`);

CREATE INDEX `idx_setting_logs_changed_by` ON `setting_change_logs` (`changed_by`);

ALTER TABLE `settings` COMMENT = 'Application-wide settings and configuration
Types: string, integer, boolean, json
Public settings are accessible without authentication
Sensitive settings are masked in UI (API keys, passwords)
Service scope indicates which service(s) use this setting
Validation rules stored as JSON schema';

ALTER TABLE `setting_change_logs` COMMENT = 'Audit trail for configuration changes
Tracks who changed what configuration, when, and from where
Enables configuration rollback and compliance tracking';

ALTER TABLE `setting_change_logs` ADD FOREIGN KEY (`setting_id`) REFERENCES `settings` (`id`);

