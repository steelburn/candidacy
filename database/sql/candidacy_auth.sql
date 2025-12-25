CREATE TABLE `users` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) UNIQUE NOT NULL,
  `email_verified_at` timestamp,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100),
  `created_at` timestamp,
  `updated_at` timestamp,
  `deleted_at` timestamp,
  `is_active` boolean NOT NULL DEFAULT true
);

CREATE TABLE `roles` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) UNIQUE NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `permissions` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) UNIQUE NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `role_user` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `role_id` bigint NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `permission_role` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `permission_id` bigint NOT NULL,
  `role_id` bigint NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) PRIMARY KEY,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp
);

CREATE TABLE `personal_access_tokens` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) UNIQUE NOT NULL,
  `abilities` text,
  `last_used_at` timestamp,
  `expires_at` timestamp,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `failed_jobs` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `uuid` varchar(255) UNIQUE NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp DEFAULT (CURRENT_TIMESTAMP)
);

CREATE INDEX `idx_users_created_at` ON `users` (`created_at`);

CREATE UNIQUE INDEX `role_user_index_7` ON `role_user` (`user_id`, `role_id`);

CREATE UNIQUE INDEX `permission_role_index_8` ON `permission_role` (`permission_id`, `role_id`);

CREATE INDEX `personal_access_tokens_tokenable` ON `personal_access_tokens` (`tokenable_type`, `tokenable_id`);

ALTER TABLE `users` COMMENT = 'System users (HR managers, recruiters, interviewers, admins)';

ALTER TABLE `roles` COMMENT = 'User roles: admin, hr_manager, recruiter, interviewer, viewer';

ALTER TABLE `permissions` COMMENT = 'Granular permissions for access control';

ALTER TABLE `role_user` COMMENT = 'Many-to-many relationship between users and roles';

ALTER TABLE `permission_role` COMMENT = 'Many-to-many relationship between permissions and roles';

ALTER TABLE `personal_access_tokens` COMMENT = 'Laravel Sanctum API tokens';

ALTER TABLE `role_user` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `role_user` ADD FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

ALTER TABLE `permission_role` ADD FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`);

ALTER TABLE `permission_role` ADD FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

