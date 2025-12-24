CREATE TABLE `settings` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `key` varchar(255) UNIQUE NOT NULL,
  `value` text,
  `type` varchar(255) NOT NULL DEFAULT "string",
  `category` varchar(255),
  `description` text,
  `is_public` boolean NOT NULL DEFAULT false,
  `created_at` timestamp,
  `updated_at` timestamp
);

ALTER TABLE `settings` COMMENT = 'Application-wide settings and configuration
Types: string, integer, boolean, json
Public settings are accessible without authentication';

