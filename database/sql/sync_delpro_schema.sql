-- =============================================================================
-- DELPRO — Sinkronisasi skema database (MySQL / MariaDB)
-- =============================================================================
-- Untuk tim setelah pull repository terbaru.
--
-- CARA TERMUDAH (disarankan):
--   1. git pull
--   2. composer install
--   3. php artisan migrate
--
-- File ini dipakai jika tim TIDAK bisa migrate / ingin manual di phpMyAdmin.
-- Ganti nama database jika perlu:
--   USE tapjblct;
--
-- Catatan:
-- - Jika muncul error "Duplicate column name" → kolom sudah ada, lanjutkan baris berikutnya.
-- - MySQL 8.0.29+ / MariaDB 10.5.2+ mendukung ADD COLUMN IF NOT EXISTS.
-- - Pada MySQL lama, hapus baris ALTER yang sudah ada atau abaikan error duplikat.
-- =============================================================================

-- USE tapjblct;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. TABEL: teams (untuk team_id di projects)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teams` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `teams` (`id`, `name`, `created_at`, `updated_at`)
VALUES (1, 'Tim default', NOW(), NOW());

-- -----------------------------------------------------------------------------
-- 2. TABEL: users — kolom profil & auth (register mahasiswa / dosen)
-- -----------------------------------------------------------------------------
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(255) NULL AFTER `name`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `username` VARCHAR(100) NULL AFTER `full_name`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `role` VARCHAR(32) NOT NULL DEFAULT 'student' AFTER `password`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `nim` VARCHAR(32) NULL AFTER `role`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `nidn` VARCHAR(32) NULL AFTER `nim`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `birth_place_date` VARCHAR(255) NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `address` TEXT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `phone` VARCHAR(32) NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `gender` VARCHAR(20) NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `jurusan` VARCHAR(255) NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `fakultas` VARCHAR(255) NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `faculty_id` BIGINT UNSIGNED NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `study_program_id` BIGINT UNSIGNED NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `profile_photo` VARCHAR(255) NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `batch_year` SMALLINT UNSIGNED NULL;

-- Index unik (abaikan error jika sudah ada)
-- ALTER TABLE `users` ADD UNIQUE INDEX `users_username_unique` (`username`);
-- ALTER TABLE `users` ADD UNIQUE INDEX `users_nim_unique` (`nim`);

-- -----------------------------------------------------------------------------
-- 3. TABEL: projects — kolom tambahan
-- -----------------------------------------------------------------------------
ALTER TABLE `projects` ADD COLUMN IF NOT EXISTS `team_id` BIGINT UNSIGNED NULL AFTER `id`;
ALTER TABLE `projects` ADD COLUMN IF NOT EXISTS `problem_definition` TEXT NULL AFTER `description`;
ALTER TABLE `projects` ADD COLUMN IF NOT EXISTS `logo` VARCHAR(255) NULL AFTER `problem_definition`;
ALTER TABLE `projects` ADD COLUMN IF NOT EXISTS `lecturer_email` VARCHAR(255) NULL;
ALTER TABLE `projects` ADD COLUMN IF NOT EXISTS `submitted_at` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `projects` ADD COLUMN IF NOT EXISTS `group_name` VARCHAR(255) NULL;
ALTER TABLE `projects` ADD COLUMN IF NOT EXISTS `course_name` VARCHAR(255) NULL;
ALTER TABLE `projects` ADD COLUMN IF NOT EXISTS `lecturer_name` VARCHAR(255) NULL;
ALTER TABLE `projects` ADD COLUMN IF NOT EXISTS `planned_months` TINYINT UNSIGNED NULL;

-- Status proyek yang dipakai aplikasi:
--   draft | pending_approval | active | completed | rejected | archived

-- -----------------------------------------------------------------------------
-- 4. TABEL: project_members (anggota proyek per email)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `role` VARCHAR(32) NOT NULL DEFAULT 'member',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_members_project_id_user_id_unique` (`project_id`, `user_id`),
  KEY `project_members_project_id_foreign` (`project_id`),
  KEY `project_members_user_id_foreign` (`user_id`),
  CONSTRAINT `project_members_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. TABEL: project_notifications (lonceng / notifikasi)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `recipient_email` VARCHAR(255) NOT NULL,
  `type` VARCHAR(64) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NULL,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_notifications_project_id_foreign` (`project_id`),
  CONSTRAINT `project_notifications_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. TABEL: tasks (Project Planning / penyusunan)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `milestone_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `parent_task_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `assigned_to` BIGINT UNSIGNED NULL DEFAULT NULL,
  `task_title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `priority` VARCHAR(32) NOT NULL DEFAULT 'medium',
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `progress_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `start_date` DATE NULL DEFAULT NULL,
  `due_date` DATE NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_project_id_foreign` (`project_id`),
  CONSTRAINT `tasks_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. TABEL: milestones
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `milestones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(255) NULL DEFAULT NULL,
  `phase` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `milestones_project_id_foreign` (`project_id`),
  CONSTRAINT `milestones_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `milestones` (`id`, `project_id`, `name`, `phase`, `created_at`, `updated_at`)
VALUES (1, NULL, 'Milestone default', 'umum', NOW(), NOW());

-- -----------------------------------------------------------------------------
-- 8. TABEL: discussions (komentar / diskusi proyek)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `discussions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `task_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discussions_project_id_foreign` (`project_id`),
  KEY `discussions_user_id_foreign` (`user_id`),
  CONSTRAINT `discussions_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discussions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. Tabel lama dari migration awal (jika belum ada — dari 2024_05_07)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `group_name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_groups_project_id_foreign` (`project_id`),
  CONSTRAINT `project_groups_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `group_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role` VARCHAR(255) NOT NULL DEFAULT 'member',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group_members_group_id_foreign` (`group_id`),
  KEY `group_members_user_id_foreign` (`user_id`),
  CONSTRAINT `group_members_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `project_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `group_milestones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` BIGINT UNSIGNED NOT NULL,
  `phase` VARCHAR(255) NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `deadline` DATE NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group_milestones_group_id_foreign` (`group_id`),
  CONSTRAINT `group_milestones_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `project_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ct_metrics` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` BIGINT UNSIGNED NOT NULL,
  `metric_name` VARCHAR(255) NOT NULL,
  `score` DECIMAL(3,1) NOT NULL,
  `max_score` DECIMAL(3,1) NOT NULL DEFAULT 10.0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ct_metrics_group_id_foreign` (`group_id`),
  CONSTRAINT `ct_metrics_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `project_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `peer_reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` BIGINT UNSIGNED NOT NULL,
  `category` VARCHAR(255) NOT NULL,
  `score` DECIMAL(2,1) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `peer_reviews_group_id_foreign` (`group_id`),
  CONSTRAINT `peer_reviews_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `project_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `group_evaluations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` BIGINT UNSIGNED NOT NULL,
  `lecturer_id` BIGINT UNSIGNED NOT NULL,
  `feedback` TEXT NOT NULL,
  `overall_score` DECIMAL(3,1) NULL DEFAULT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `evaluated_at` DATE NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group_evaluations_group_id_foreign` (`group_id`),
  KEY `group_evaluations_lecturer_id_foreign` (`lecturer_id`),
  CONSTRAINT `group_evaluations_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `project_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_evaluations_lecturer_id_foreign` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 10. (Opsional) Tandai migration Laravel sudah jalan — hanya jika pakai SQL manual
--     dan TIDAK menjalankan php artisan migrate
-- -----------------------------------------------------------------------------
/*
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2026_05_12_000001_add_full_name_to_users_table_when_missing', 99),
('2026_05_12_000002_create_teams_table', 99),
('2026_05_15_000001_add_register_profile_columns_to_users_table', 99),
('2026_05_15_000002_add_user_auth_columns_to_users_table', 99),
('2026_05_16_000001_sync_application_schema', 99),
('2026_05_16_000002_add_project_collaboration_schema', 99),
('2026_05_16_000003_add_project_detail_fields_to_projects_table', 99)
ON DUPLICATE KEY UPDATE migration = migration;
*/

-- Selesai.
