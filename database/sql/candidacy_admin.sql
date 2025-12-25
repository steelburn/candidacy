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

CREATE INDEX `idx_settings_category` ON `settings` (`category`);

CREATE INDEX `idx_settings_service_scope` ON `settings` (`service_scope`);

CREATE INDEX `idx_settings_is_public` ON `settings` (`is_public`);

ALTER TABLE `settings` COMMENT = 'Application-wide settings and configuration
Types: string, integer, boolean, json
Public settings are accessible without authentication
Sensitive settings are masked in UI (API keys, passwords)
Service scope indicates which service(s) use this setting
Validation rules stored as JSON schema';

