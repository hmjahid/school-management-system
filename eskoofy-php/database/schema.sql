-- ============================================================
-- Eskoofy School Management System — Complete MySQL Schema
-- Full port of ALL Laravel migrations
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ROLES & PERMISSIONS (Spatie-style tables)
-- ============================================================

CREATE TABLE IF NOT EXISTS `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `description` VARCHAR(191) NULL,
  `guard_name` VARCHAR(191) NOT NULL DEFAULT 'web',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `guard_name` VARCHAR(191) NOT NULL DEFAULT 'web',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_has_permissions` (
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `model_has_roles` (
  `role_id` BIGINT UNSIGNED NOT NULL,
  `model_type` VARCHAR(191) NOT NULL,
  `model_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `model_id`, `model_type`),
  KEY `model_has_roles_model_type_model_id_index` (`model_type`, `model_id`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `model_has_permissions` (
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `model_type` VARCHAR(191) NOT NULL,
  `model_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
  KEY `model_has_permissions_model_type_model_id_index` (`model_type`, `model_id`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. USERS
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(191) NULL,
  `address` VARCHAR(191) NULL,
  `gender` VARCHAR(191) NULL,
  `date_of_birth` DATE NULL,
  `photo` VARCHAR(191) NULL,
  `password` VARCHAR(191) NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `email_verified_at` TIMESTAMP NULL,
  `role` ENUM('student','admin','teacher','guardian','super_admin') NULL DEFAULT NULL,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `users_email_role_unique` (`email`, `role_id`),
  UNIQUE KEY `users_phone_role_unique` (`phone`, `role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` VARCHAR(191) NOT NULL,
  `token` VARCHAR(191) NOT NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. ACADEMIC YEARS & SESSIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS `academic_years` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `session` VARCHAR(191) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_current` TINYINT(1) NOT NULL DEFAULT 0,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_years_session_unique` (`session`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `academic_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `is_current` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(191) NOT NULL DEFAULT 'upcoming',
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_sessions_name_unique` (`name`),
  UNIQUE KEY `academic_sessions_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. SCHOOL CLASSES
-- ============================================================

CREATE TABLE IF NOT EXISTS `school_classes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(20) NULL,
  `description` TEXT NULL,
  `grade_level` INT NULL,
  `academic_session_id` BIGINT UNSIGNED NULL,
  `class_teacher_id` BIGINT UNSIGNED NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `seating_capacity` INT NULL,
  `max_students` INT NOT NULL DEFAULT 30,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `monthly_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `admission_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `exam_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `other_fees` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `shift` VARCHAR(191) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `school_classes_academic_session_id_foreign` (`academic_session_id`),
  KEY `school_classes_parent_id_foreign` (`parent_id`),
  CONSTRAINT `school_classes_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `school_classes_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. SECTIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS `sections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `class_id` BIGINT UNSIGNED NULL,
  `capacity` INT NOT NULL DEFAULT 30,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `class_teacher_id` BIGINT UNSIGNED NULL,
  `academic_year_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sections_slug_unique` (`slug`),
  KEY `sections_class_teacher_id_foreign` (`class_teacher_id`),
  KEY `sections_academic_year_id_foreign` (`academic_year_id`),
  KEY `sections_class_id_foreign` (`class_id`),
  CONSTRAINT `sections_class_teacher_id_foreign` FOREIGN KEY (`class_teacher_id`) REFERENCES `users` (`id`),
  CONSTRAINT `sections_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  CONSTRAINT `sections_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. BATCHES
-- ============================================================

CREATE TABLE IF NOT EXISTS `batches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NULL,
  `description` TEXT NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `academic_session_id` BIGINT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `status` VARCHAR(191) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `batches_academic_session_id_foreign` (`academic_session_id`),
  KEY `batches_course_id_foreign` (`course_id`),
  CONSTRAINT `batches_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. COURSES
-- ============================================================

CREATE TABLE IF NOT EXISTS `courses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courses_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `batches`
  ADD CONSTRAINT `batches_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;

-- ============================================================
-- 8. GUARDIANS
-- ============================================================

CREATE TABLE IF NOT EXISTS `guardians` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `relation_type` VARCHAR(191) NOT NULL,
  `occupation` VARCHAR(100) NULL,
  `company_name` VARCHAR(191) NULL,
  `company_address` VARCHAR(191) NULL,
  `emergency_contact_name` VARCHAR(191) NULL,
  `emergency_contact_phone` VARCHAR(191) NULL,
  `emergency_contact_relation` VARCHAR(191) NULL,
  `phone` VARCHAR(20) NULL,
  `company` VARCHAR(100) NULL,
  `nid_number` VARCHAR(50) NULL,
  `passport_number` VARCHAR(50) NULL,
  `driving_license` VARCHAR(50) NULL,
  `nationality` VARCHAR(100) NOT NULL DEFAULT 'Bangladeshi',
  `religion` VARCHAR(50) NULL,
  `blood_group` VARCHAR(5) NULL,
  `present_address` TEXT NULL,
  `permanent_address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `zip_code` VARCHAR(20) NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'Bangladesh',
  `office_phone` VARCHAR(20) NULL,
  `emergency_contact` VARCHAR(20) NULL,
  `relationship` VARCHAR(50) NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `monthly_income` DECIMAL(12,2) NULL,
  `education_level` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `guardians_user_id_foreign` (`user_id`),
  CONSTRAINT `guardians_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. TEACHERS
-- ============================================================

CREATE TABLE IF NOT EXISTS `teachers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `employee_id` VARCHAR(191) NULL UNIQUE,
  `qualification` VARCHAR(191) NULL,
  `gender` VARCHAR(10) NULL,
  `blood_group` VARCHAR(5) NULL,
  `date_of_birth` DATE NULL,
  `religion` VARCHAR(50) NULL,
  `nationality` VARCHAR(100) NOT NULL DEFAULT 'Bangladeshi',
  `phone` VARCHAR(20) NULL,
  `emergency_contact` VARCHAR(20) NULL,
  `present_address` TEXT NULL,
  `permanent_address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `zip_code` VARCHAR(20) NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'Bangladesh',
  `joining_date` DATE NULL,
  `leaving_date` DATE NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `bank_name` VARCHAR(100) NULL,
  `bank_account_number` VARCHAR(50) NULL,
  `bank_branch` VARCHAR(100) NULL,
  `salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `salary_type` VARCHAR(20) NOT NULL DEFAULT 'monthly',
  `nid_number` VARCHAR(50) NULL,
  `passport_number` VARCHAR(50) NULL,
  `driving_license` VARCHAR(50) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `teachers_user_id_foreign` (`user_id`),
  CONSTRAINT `teachers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. SUBJECTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `subjects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NOT NULL,
  `teacher_id` BIGINT UNSIGNED NULL,
  `type` VARCHAR(191) NOT NULL DEFAULT 'core',
  `short_name` VARCHAR(191) NULL,
  `credit_hours` INT NOT NULL DEFAULT 3,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subjects_code_unique` (`code`),
  KEY `subjects_teacher_id_foreign` (`teacher_id`),
  CONSTRAINT `subjects_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. SCHOOL CLASS <-> SUBJECT PIVOT
-- ============================================================

CREATE TABLE IF NOT EXISTS `school_class_subject` (
  `school_class_id` BIGINT UNSIGNED NOT NULL,
  `subject_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`school_class_id`, `subject_id`),
  KEY `school_class_subject_subject_id_foreign` (`subject_id`),
  CONSTRAINT `school_class_subject_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `school_class_subject_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. TEACHER PIVOT TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS `class_teacher` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` BIGINT UNSIGNED NOT NULL,
  `class_id` BIGINT UNSIGNED NOT NULL,
  `is_class_teacher` TINYINT(1) NOT NULL DEFAULT 0,
  `academic_session_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_teacher_unique` (`teacher_id`, `class_id`, `academic_session_id`),
  CONSTRAINT `class_teacher_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_teacher_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_teacher_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `class_subject_teacher` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` BIGINT UNSIGNED NOT NULL,
  `subject_id` BIGINT UNSIGNED NOT NULL,
  `class_id` BIGINT UNSIGNED NULL,
  `academic_session_id` BIGINT UNSIGNED NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_subject_teacher_unique` (`teacher_id`, `subject_id`, `class_id`, `academic_session_id`),
  CONSTRAINT `class_subject_teacher_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_subject_teacher_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_subject_teacher_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `class_subject_teacher_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `section_teacher` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` BIGINT UNSIGNED NOT NULL,
  `section_id` BIGINT UNSIGNED NOT NULL,
  `subject_id` BIGINT UNSIGNED NULL,
  `academic_session_id` BIGINT UNSIGNED NULL,
  `is_class_teacher` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_teacher_unique` (`teacher_id`, `section_id`, `subject_id`, `academic_session_id`),
  CONSTRAINT `section_teacher_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `section_teacher_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `section_teacher_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `section_teacher_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. GUARDIAN <-> STUDENT PIVOT
-- ============================================================

CREATE TABLE IF NOT EXISTS `guardian_student` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guardian_id` BIGINT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `relationship` VARCHAR(191) NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guardian_student_unique` (`guardian_id`, `student_id`),
  KEY `guardian_student_student_id_is_primary_index` (`student_id`, `is_primary`),
  CONSTRAINT `guardian_student_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE CASCADE,
  CONSTRAINT `guardian_student_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. STUDENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `students` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `class_id` BIGINT UNSIGNED NOT NULL,
  `section_id` BIGINT UNSIGNED NULL,
  `batch_id` BIGINT UNSIGNED NULL,
  `guardian_id` BIGINT UNSIGNED NULL,
  `admission_number` VARCHAR(191) NOT NULL,
  `admission_date` DATE NOT NULL,
  `roll_number` VARCHAR(191) NULL,
  `attendance_percentage` DECIMAL(5,2) NULL,
  `blood_group` VARCHAR(5) NULL,
  `religion` VARCHAR(50) NULL,
  `nationality` VARCHAR(100) NOT NULL DEFAULT 'Bangladeshi',
  `nid_number` VARCHAR(50) NULL,
  `birth_certificate_number` VARCHAR(50) NULL,
  `first_name` VARCHAR(191) NULL,
  `last_name` VARCHAR(191) NULL,
  `gender` ENUM('male','female','other') NULL,
  `date_of_birth` DATE NULL,
  `email` VARCHAR(191) NULL,
  `phone` VARCHAR(191) NULL,
  `address` TEXT NULL,
  `permanent_address` TEXT NULL,
  `present_address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `postal_code` VARCHAR(20) NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'Bangladesh',
  `phone_1` VARCHAR(20) NULL,
  `phone_2` VARCHAR(20) NULL,
  `father_name` VARCHAR(100) NULL,
  `father_phone` VARCHAR(20) NULL,
  `father_occupation` VARCHAR(100) NULL,
  `mother_name` VARCHAR(100) NULL,
  `mother_phone` VARCHAR(20) NULL,
  `mother_occupation` VARCHAR(100) NULL,
  `parent_name` VARCHAR(100) NULL,
  `parent_phone` VARCHAR(20) NULL,
  `parent_email` VARCHAR(100) NULL,
  `parent_occupation` VARCHAR(100) NULL,
  `parent_address` TEXT NULL,
  `monthly_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `transport_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active','inactive','graduated','transferred') NOT NULL DEFAULT 'active',
  `is_notable` TINYINT(1) NOT NULL DEFAULT 0,
  `achievement` TEXT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_admission_number_unique` (`admission_number`),
  KEY `students_user_id_foreign` (`user_id`),
  KEY `students_class_id_foreign` (`class_id`),
  KEY `students_section_id_foreign` (`section_id`),
  KEY `students_batch_id_foreign` (`batch_id`),
  KEY `students_guardian_id_foreign` (`guardian_id`),
  KEY `students_admission_id_foreign` (`admission_id`),
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 15. EXAMS
-- ============================================================

CREATE TABLE IF NOT EXISTS `exams` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NULL,
  `description` TEXT NULL,
  `type` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `start_date` DATETIME NULL,
  `end_date` DATETIME NULL,
  `duration` INT NULL,
  `total_marks` FLOAT NULL,
  `passing_marks` FLOAT NULL,
  `grading_type` VARCHAR(191) NULL,
  `grading_scale` JSON NULL,
  `weightage` FLOAT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  `publish_date` DATETIME NULL,
  `publish_remarks` TEXT NULL,
  `academic_session_id` BIGINT UNSIGNED NULL,
  `batch_id` BIGINT UNSIGNED NULL,
  `section_id` BIGINT UNSIGNED NULL,
  `subject_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `exams_academic_session_id_foreign` (`academic_session_id`),
  KEY `exams_batch_id_foreign` (`batch_id`),
  KEY `exams_section_id_foreign` (`section_id`),
  KEY `exams_subject_id_foreign` (`subject_id`),
  KEY `exams_created_by_foreign` (`created_by`),
  KEY `exams_updated_by_foreign` (`updated_by`),
  CONSTRAINT `exams_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exams_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exams_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exams_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exams_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exams_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 16. EXAM RESULTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `exam_results` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id` BIGINT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `subject_id` BIGINT UNSIGNED NULL,
  `total_marks` DECIMAL(8,2) NULL,
  `obtained_marks` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `grade` VARCHAR(10) NULL,
  `grade_point` DECIMAL(4,2) NULL,
  `remarks` TEXT NULL,
  `status` ENUM('pending','passed','failed','absent','malpractice') NOT NULL DEFAULT 'pending',
  `submitted_by` BIGINT UNSIGNED NULL,
  `submitted_at` TIMESTAMP NULL,
  `reviewed_by` BIGINT UNSIGNED NULL,
  `reviewed_at` TIMESTAMP NULL,
  `review_remarks` TEXT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  `published_at` TIMESTAMP NULL,
  `published_by` BIGINT UNSIGNED NULL,
  `publish_remarks` TEXT NULL,
  `unpublished_at` TIMESTAMP NULL,
  `unpublished_by` BIGINT UNSIGNED NULL,
  `unpublish_remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `exam_results_exam_id_student_id_index` (`exam_id`, `student_id`),
  KEY `exam_results_status_index` (`status`),
  KEY `exam_results_is_published_index` (`is_published`),
  KEY `exam_results_student_id_foreign` (`student_id`),
  KEY `exam_results_submitted_by_foreign` (`submitted_by`),
  KEY `exam_results_reviewed_by_foreign` (`reviewed_by`),
  KEY `exam_results_published_by_foreign` (`published_by`),
  KEY `exam_results_unpublished_by_foreign` (`unpublished_by`),
  CONSTRAINT `exam_results_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_results_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  FULLTEXT KEY `exam_results_fulltext_index` (`remarks`, `review_remarks`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 17. ATTENDANCES
-- ============================================================

CREATE TABLE IF NOT EXISTS `attendances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `school_class_id` BIGINT UNSIGNED NOT NULL,
  `subject_id` BIGINT UNSIGNED NULL,
  `batch_id` BIGINT UNSIGNED NULL,
  `section_id` BIGINT UNSIGNED NULL,
  `teacher_id` BIGINT UNSIGNED NULL,
  `academic_session_id` BIGINT UNSIGNED NULL,
  `date` DATE NOT NULL,
  `status` VARCHAR(20) NULL,
  `type` VARCHAR(30) NOT NULL DEFAULT 'daily',
  `period` VARCHAR(50) NULL,
  `remarks` TEXT NULL,
  `marked_by` BIGINT UNSIGNED NOT NULL,
  `recorded_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `attendances_unique_per_student_date_type` (`student_id`, `date`, `type`),
  KEY `attendances_school_class_id_foreign` (`school_class_id`),
  KEY `attendances_subject_id_foreign` (`subject_id`),
  KEY `attendances_marked_by_foreign` (`marked_by`),
  KEY `attendances_batch_id_foreign` (`batch_id`),
  KEY `attendances_section_id_foreign` (`section_id`),
  KEY `attendances_teacher_id_foreign` (`teacher_id`),
  KEY `attendances_academic_session_id_foreign` (`academic_session_id`),
  KEY `attendances_recorded_by_foreign` (`recorded_by`),
  KEY `attendances_updated_by_foreign` (`updated_by`),
  PRIMARY KEY (`id`),
  CONSTRAINT `attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_marked_by_foreign` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendances_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendances_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendances_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendances_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendances_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 18. FEES
-- ============================================================

CREATE TABLE IF NOT EXISTS `fees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `class_id` BIGINT UNSIGNED NOT NULL,
  `section_id` BIGINT UNSIGNED NULL,
  `student_id` BIGINT UNSIGNED NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `fee_type` VARCHAR(191) NOT NULL,
  `frequency` VARCHAR(191) NOT NULL DEFAULT 'one_time',
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `fine_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fine_type` VARCHAR(191) NOT NULL DEFAULT 'fixed',
  `fine_grace_days` INT NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_type` VARCHAR(191) NOT NULL DEFAULT 'fixed',
  `status` ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
  `metadata` JSON NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fees_code_unique` (`code`),
  KEY `fees_class_id_foreign` (`class_id`),
  KEY `fees_section_id_foreign` (`section_id`),
  KEY `fees_student_id_foreign` (`student_id`),
  KEY `fees_created_by_foreign` (`created_by`),
  CONSTRAINT `fees_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`),
  CONSTRAINT `fees_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  CONSTRAINT `fees_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `fees_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 19. FEE PAYMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `fee_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(191) NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `fee_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fine_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(10,2) NOT NULL,
  `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_date` DATE NOT NULL,
  `month` VARCHAR(191) NULL,
  `year` INT NULL,
  `payment_method` VARCHAR(191) NOT NULL,
  `transaction_id` VARCHAR(191) NULL,
  `bank_name` VARCHAR(191) NULL,
  `check_number` VARCHAR(191) NULL,
  `status` ENUM('pending','paid','partial','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `metadata` JSON NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fee_payments_invoice_number_unique` (`invoice_number`),
  KEY `fee_payments_student_id_foreign` (`student_id`),
  KEY `fee_payments_fee_id_foreign` (`fee_id`),
  KEY `fee_payments_created_by_foreign` (`created_by`),
  KEY `fee_payments_approved_by_foreign` (`approved_by`),
  CONSTRAINT `fee_payments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `fee_payments_fee_id_foreign` FOREIGN KEY (`fee_id`) REFERENCES `fees` (`id`),
  CONSTRAINT `fee_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fee_payments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 20. PAYMENTS (polymorphic)
-- ============================================================

CREATE TABLE IF NOT EXISTS `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `paymentable_type` VARCHAR(191) NULL,
  `paymentable_id` BIGINT UNSIGNED NULL,
  `invoice_number` VARCHAR(191) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `due_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `fine_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL,
  `payment_method` ENUM('cash','bank_transfer','cheque','bkash','nagad','rocket','stripe','paypal','other') NOT NULL,
  `payment_status` ENUM('pending','processing','completed','failed','refunded','cancelled','expired') NOT NULL DEFAULT 'pending',
  `refund_status` VARCHAR(191) NOT NULL DEFAULT 'not_refunded',
  `payment_date` DATE NULL,
  `due_date` DATE NULL,
  `reference_number` VARCHAR(191) NULL,
  `transaction_id` VARCHAR(191) NULL,
  `payment_details` JSON NULL,
  `notes` TEXT NULL,
  `metadata` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_invoice_number_unique` (`invoice_number`),
  KEY `payments_invoice_number_payment_status_payment_method_index` (`invoice_number`, `payment_status`, `payment_method`),
  KEY `payments_paymentable_type_paymentable_id_index` (`paymentable_type`, `paymentable_id`),
  KEY `payments_created_by_foreign` (`created_by`),
  KEY `payments_updated_by_foreign` (`updated_by`),
  KEY `payments_refund_status_index` (`refund_status`),
  CONSTRAINT `payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FULLTEXT KEY `payments_fulltext_index` (`invoice_number`, `reference_number`, `transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 21. PAYMENT GATEWAYS
-- ============================================================

CREATE TABLE IF NOT EXISTS `payment_gateways` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NOT NULL,
  `type` ENUM('bank','mobile_financial_service','online_payment','other') NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_online` TINYINT(1) NOT NULL DEFAULT 0,
  `has_api` TINYINT(1) NOT NULL DEFAULT 0,
  `sandbox_url` VARCHAR(191) NULL,
  `live_url` VARCHAR(191) NULL,
  `test_mode` TINYINT(1) NOT NULL DEFAULT 1,
  `api_key` TEXT NULL,
  `api_secret` TEXT NULL,
  `api_username` VARCHAR(191) NULL,
  `api_password` TEXT NULL,
  `callback_url` VARCHAR(191) NULL,
  `webhook_url` VARCHAR(191) NULL,
  `success_url` VARCHAR(191) NULL,
  `cancel_url` VARCHAR(191) NULL,
  `ipn_url` VARCHAR(191) NULL,
  `logo` VARCHAR(191) NULL,
  `description` TEXT NULL,
  `instructions` TEXT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'BDT',
  `fee_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `fee_fixed` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `min_amount` DECIMAL(12,2) NULL,
  `max_amount` DECIMAL(12,2) NULL,
  `supported_currencies` JSON NULL,
  `extra_attributes` JSON NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_gateways_code_unique` (`code`),
  KEY `payment_gateways_is_active_is_online_type_index` (`is_active`, `is_online`, `type`),
  FULLTEXT KEY `payment_gateways_fulltext_index` (`name`, `code`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 22. PAYMENT WEBHOOK EVENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `payment_webhook_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gateway` VARCHAR(50) NOT NULL,
  `payload_hash` VARCHAR(64) NOT NULL,
  `headers` JSON NULL,
  `payload` JSON NULL,
  `processed_at` TIMESTAMP NULL,
  `payment_id` BIGINT UNSIGNED NULL,
  `result_status` VARCHAR(40) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_webhook_events_payload_hash_unique` (`payload_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 23. RECURRING PAYMENT PROFILES
-- ============================================================

CREATE TABLE IF NOT EXISTS `recurring_payment_profiles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id` VARCHAR(191) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `paymentable_type` VARCHAR(191) NOT NULL,
  `paymentable_id` BIGINT UNSIGNED NOT NULL,
  `gateway` VARCHAR(191) NOT NULL,
  `gateway_profile_id` VARCHAR(191) NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'BDT',
  `billing_period` ENUM('day','week','month','year') NOT NULL,
  `billing_frequency` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `start_date` DATETIME NOT NULL,
  `next_billing_date` DATETIME NOT NULL,
  `end_date` DATETIME NULL,
  `status` ENUM('active','suspended','cancelled','expired') NOT NULL DEFAULT 'active',
  `payment_method_token` VARCHAR(191) NULL,
  `card_last4` VARCHAR(4) NULL,
  `card_brand` VARCHAR(191) NULL,
  `card_expiry` VARCHAR(191) NULL,
  `max_failures` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `failure_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recurring_payment_profiles_profile_id_unique` (`profile_id`),
  KEY `recurring_payment_profiles_user_id_status_index` (`user_id`, `status`),
  KEY `recurring_payment_profiles_next_billing_date_index` (`next_billing_date`),
  CONSTRAINT `recurring_payment_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 24. REFUNDS
-- ============================================================

CREATE TABLE IF NOT EXISTS `refunds` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `processed_by` BIGINT UNSIGNED NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'BDT',
  `transaction_id` VARCHAR(191) NULL,
  `status` ENUM('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `reason` VARCHAR(191) NULL,
  `processed_at` TIMESTAMP NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `refunds_transaction_id_index` (`transaction_id`),
  KEY `refunds_status_index` (`status`),
  KEY `refunds_created_at_index` (`created_at`),
  KEY `refunds_payment_id_foreign` (`payment_id`),
  KEY `refunds_user_id_foreign` (`user_id`),
  KEY `refunds_processed_by_foreign` (`processed_by`),
  CONSTRAINT `refunds_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refunds_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refunds_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 25. INVOICES
-- ============================================================

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `fee_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `due_date` DATE NOT NULL,
  `status` ENUM('paid','unpaid','overdue') NOT NULL DEFAULT 'unpaid',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `invoices_student_id_foreign` (`student_id`),
  KEY `invoices_fee_id_foreign` (`fee_id`),
  CONSTRAINT `invoices_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_fee_id_foreign` FOREIGN KEY (`fee_id`) REFERENCES `fees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 26. ADMISSIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS `admissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_number` VARCHAR(191) NOT NULL,
  `academic_session_id` BIGINT UNSIGNED NOT NULL,
  `batch_id` BIGINT UNSIGNED NOT NULL,
  `first_name` VARCHAR(191) NOT NULL,
  `last_name` VARCHAR(191) NOT NULL,
  `gender` ENUM('male','female','other') NOT NULL,
  `date_of_birth` DATE NOT NULL,
  `blood_group` VARCHAR(10) NULL,
  `religion` VARCHAR(191) NULL,
  `nationality` VARCHAR(191) NOT NULL DEFAULT 'Bangladeshi',
  `photo` VARCHAR(191) NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(191) NOT NULL,
  `address` TEXT NOT NULL,
  `city` VARCHAR(191) NOT NULL,
  `state` VARCHAR(191) NULL,
  `country` VARCHAR(191) NOT NULL DEFAULT 'Bangladesh',
  `postal_code` VARCHAR(191) NOT NULL,
  `father_name` VARCHAR(191) NOT NULL,
  `father_phone` VARCHAR(191) NOT NULL,
  `father_occupation` VARCHAR(191) NULL,
  `mother_name` VARCHAR(191) NOT NULL,
  `mother_phone` VARCHAR(191) NOT NULL,
  `mother_occupation` VARCHAR(191) NULL,
  `guardian_name` VARCHAR(191) NULL,
  `guardian_relation` VARCHAR(191) NULL,
  `guardian_phone` VARCHAR(191) NULL,
  `previous_school` VARCHAR(191) NULL,
  `previous_class` VARCHAR(191) NULL,
  `previous_grade` VARCHAR(191) NULL,
  `transfer_certificate` VARCHAR(191) NULL,
  `birth_certificate` VARCHAR(191) NULL,
  `other_documents` JSON NULL,
  `status` ENUM('draft','submitted','under_review','approved','rejected','waitlisted','enrolled','cancelled') NOT NULL DEFAULT 'draft',
  `rejection_reason` TEXT NULL,
  `admission_date` DATE NULL,
  `admission_notes` TEXT NULL,
  `submitted_at` TIMESTAMP NULL,
  `approved_at` TIMESTAMP NULL,
  `rejected_at` TIMESTAMP NULL,
  `enrolled_at` TIMESTAMP NULL,
  `cancelled_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `rejected_by` BIGINT UNSIGNED NULL,
  `admission_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_number` VARCHAR(64) NULL,
  `transaction_id` VARCHAR(128) NULL,
  `payment_method` VARCHAR(32) NULL,
  `payment_status` VARCHAR(32) NOT NULL DEFAULT 'unpaid',
  `paid_at` TIMESTAMP NULL,
  `verified_at` TIMESTAMP NULL,
  `verified_by` BIGINT UNSIGNED NULL,
  `payment_note` TEXT NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admissions_application_number_unique` (`application_number`),
  UNIQUE KEY `admissions_email_unique` (`email`),
  KEY `admissions_academic_session_id_foreign` (`academic_session_id`),
  KEY `admissions_batch_id_foreign` (`batch_id`),
  KEY `admissions_created_by_foreign` (`created_by`),
  KEY `admissions_updated_by_foreign` (`updated_by`),
  KEY `admissions_approved_by_foreign` (`approved_by`),
  KEY `admissions_rejected_by_foreign` (`rejected_by`),
  KEY `admissions_verified_by_foreign` (`verified_by`),
  KEY `admissions_payment_status_index` (`payment_status`),
  KEY `admissions_transaction_id_index` (`transaction_id`),
  KEY `admissions_application_number_status_email_phone_index` (`application_number`, `status`, `email`, `phone`),
  CONSTRAINT `admissions_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admissions_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admissions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admissions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admissions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admissions_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admissions_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FULLTEXT KEY `admissions_fulltext_index` (`first_name`, `last_name`, `email`, `phone`, `father_name`, `mother_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 27. ADMISSION DOCUMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `admission_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_id` BIGINT UNSIGNED NOT NULL,
  `type` ENUM('transfer_certificate','birth_certificate','photo','mark_sheet','character_certificate','migration_certificate','other') NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `file_path` VARCHAR(191) NOT NULL,
  `file_type` VARCHAR(191) NOT NULL,
  `file_size` INT NOT NULL,
  `description` TEXT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `review_notes` TEXT NULL,
  `reviewed_by` BIGINT UNSIGNED NULL,
  `reviewed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `admission_documents_admission_id_type_is_approved_index` (`admission_id`, `type`, `is_approved`),
  KEY `admission_documents_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `admission_documents_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admission_documents_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 28. ADMISSION SETTINGS
-- ============================================================

CREATE TABLE IF NOT EXISTS `admission_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `is_open` TINYINT(1) NOT NULL DEFAULT 1,
  `display_year` VARCHAR(9) NULL,
  `bar_title_en` VARCHAR(255) NULL,
  `bar_title_bn` VARCHAR(255) NULL,
  `closed_message_en` TEXT NULL,
  `closed_message_bn` TEXT NULL,
  `admission_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_number` VARCHAR(64) NULL,
  `payment_instructions_en` TEXT NULL,
  `payment_instructions_bn` TEXT NULL,
  `notice_en` TEXT NULL,
  `notice_bn` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 29. WEBSITE SETTINGS
-- ============================================================

CREATE TABLE IF NOT EXISTS `website_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_name` VARCHAR(191) NOT NULL,
  `school_name_bn` VARCHAR(191) NULL,
  `tagline` VARCHAR(191) NULL,
  `tagline_bn` VARCHAR(191) NULL,
  `logo_path` VARCHAR(191) NULL,
  `og_image_path` VARCHAR(191) NULL,
  `favicon_path` VARCHAR(191) NULL,
  `footer_logo_path` VARCHAR(191) NULL,
  `footer_logo_dark_path` VARCHAR(191) NULL,
  `established_year` YEAR NOT NULL,
  `address` VARCHAR(191) NOT NULL,
  `city` VARCHAR(191) NOT NULL,
  `state` VARCHAR(191) NOT NULL,
  `country` VARCHAR(191) NOT NULL,
  `postal_code` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `website` VARCHAR(191) NULL,
  `opening_hours` JSON NULL,
  `facebook_url` VARCHAR(191) NULL,
  `show_facebook` TINYINT(1) NOT NULL DEFAULT 1,
  `twitter_url` VARCHAR(191) NULL,
  `show_twitter` TINYINT(1) NOT NULL DEFAULT 1,
  `instagram_url` VARCHAR(191) NULL,
  `show_instagram` TINYINT(1) NOT NULL DEFAULT 1,
  `linkedin_url` VARCHAR(191) NULL,
  `show_linkedin` TINYINT(1) NOT NULL DEFAULT 1,
  `youtube_url` VARCHAR(191) NULL,
  `show_youtube` TINYINT(1) NOT NULL DEFAULT 1,
  `meta_title` TEXT NULL,
  `meta_description` TEXT NULL,
  `meta_keywords` TEXT NULL,
  `send_absence_sms` TINYINT(1) NOT NULL DEFAULT 0,
  `absence_sms_template` TEXT NULL,
  `sms_sender_id` VARCHAR(32) NULL,
  `theme_primary_color` VARCHAR(20) NULL,
  `theme_secondary_color` VARCHAR(20) NULL,
  `theme_font_family` VARCHAR(100) NULL,
  `theme_border_radius` VARCHAR(20) NULL,
  `theme_header_style` VARCHAR(20) NULL,
  `theme_footer_style` VARCHAR(20) NULL,
  `theme_button_style` VARCHAR(20) NULL,
  `theme_section_spacing` VARCHAR(20) NULL,
  `theme_style` VARCHAR(20) NOT NULL DEFAULT 'default',
  `academic_start_month` TINYINT UNSIGNED NULL,
  `student_id_prefix` VARCHAR(20) NULL,
  `timezone` VARCHAR(191) NOT NULL DEFAULT 'UTC',
  `default_locale` VARCHAR(8) NOT NULL DEFAULT 'en',
  `date_format` VARCHAR(191) NOT NULL DEFAULT 'Y-m-d',
  `time_format` VARCHAR(191) NOT NULL DEFAULT 'H:i',
  `maintenance_mode` TINYINT(1) NOT NULL DEFAULT 0,
  `maintenance_message` TEXT NULL,
  `section_visibility` JSON NULL,
  `bkash_merchant_number` VARCHAR(191) NULL,
  `bkash_api_key` VARCHAR(191) NULL,
  `bkash_api_secret` VARCHAR(191) NULL,
  `bkash_username` VARCHAR(191) NULL,
  `bkash_password` VARCHAR(191) NULL,
  `bkash_app_key` VARCHAR(191) NULL,
  `bkash_app_secret` VARCHAR(191) NULL,
  `bkash_sandbox` TINYINT(1) NOT NULL DEFAULT 1,
  `nagad_merchant_number` VARCHAR(191) NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'BDT',
  `default_payment_method` VARCHAR(50) NOT NULL DEFAULT 'bkash',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 30. WEBSITE CONTENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `website_contents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page` VARCHAR(191) NULL,
  `title` VARCHAR(191) NULL,
  `title_en` VARCHAR(191) NULL,
  `title_bn` VARCHAR(191) NULL,
  `content` JSON NULL,
  `content_en` JSON NULL,
  `content_bn` JSON NULL,
  `cms_input_mode` VARCHAR(16) NOT NULL DEFAULT 'json',
  `meta_description` VARCHAR(500) NULL,
  `meta_description_en` VARCHAR(500) NULL,
  `meta_description_bn` VARCHAR(500) NULL,
  `meta_keywords` VARCHAR(500) NULL,
  `images` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `settings` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `website_contents_page_unique` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 31. ABOUT CONTENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `about_contents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_name` VARCHAR(191) NOT NULL,
  `tagline` VARCHAR(191) NULL,
  `logo_path` VARCHAR(191) NULL,
  `favicon_path` VARCHAR(191) NULL,
  `established_year` YEAR NOT NULL,
  `about_summary` TEXT NOT NULL,
  `mission` TEXT NULL,
  `vision` TEXT NULL,
  `history` TEXT NULL,
  `core_values` JSON NULL,
  `contact_info` JSON NULL,
  `social_links` JSON NULL,
  `address` TEXT NOT NULL,
  `phone` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `website` VARCHAR(191) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 32. WEBSITE MEDIA
-- ============================================================

CREATE TABLE IF NOT EXISTS `website_media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NULL,
  `category` VARCHAR(191) NULL,
  `file_path` VARCHAR(191) NOT NULL,
  `mime_type` VARCHAR(191) NULL,
  `file_size` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `website_media_category_created_at_index` (`category`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 33. WEBSITE DOCUMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `website_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `category` VARCHAR(191) NULL,
  `file_path` VARCHAR(191) NOT NULL,
  `mime_type` VARCHAR(191) NULL,
  `file_size` BIGINT UNSIGNED NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `website_documents_category_is_published_index` (`category`, `is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 34. NEWS
-- ============================================================

CREATE TABLE IF NOT EXISTS `news` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `content` TEXT NOT NULL,
  `image_url` VARCHAR(191) NULL,
  `category` VARCHAR(191) NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  `is_event` TINYINT(1) NOT NULL DEFAULT 0,
  `published_at` TIMESTAMP NULL,
  `event_date` TIMESTAMP NULL,
  `event_location` VARCHAR(191) NULL,
  `author_name` VARCHAR(191) NULL,
  `author_avatar` VARCHAR(191) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `news_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 35. EVENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `location` VARCHAR(191) NULL,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NULL,
  `registration_deadline` DATETIME NULL,
  `max_attendees` INT NULL,
  `is_virtual` TINYINT(1) NOT NULL DEFAULT 0,
  `meeting_url` VARCHAR(191) NULL,
  `status` ENUM('draft','published','cancelled','completed') NOT NULL DEFAULT 'draft',
  `image` VARCHAR(191) NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `events_created_by_foreign` (`created_by`),
  CONSTRAINT `events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_attendees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('registered','attended','cancelled') NOT NULL DEFAULT 'registered',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_attendees_event_id_user_id_unique` (`event_id`, `user_id`),
  CONSTRAINT `event_attendees_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_attendees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 36. NOTICES
-- ============================================================

CREATE TABLE IF NOT EXISTS `notices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `title_bn` VARCHAR(191) NULL,
  `content` TEXT NOT NULL,
  `content_bn` TEXT NULL,
  `attachments` JSON NULL,
  `pinned` TINYINT(1) NOT NULL DEFAULT 0,
  `audience` JSON NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `notices_created_by_foreign` (`created_by`),
  CONSTRAINT `notices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 37. ANNOUNCEMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `title_bn` VARCHAR(191) NULL,
  `body` TEXT NULL,
  `body_bn` TEXT NULL,
  `audience` VARCHAR(191) NOT NULL DEFAULT 'all',
  `display_target` VARCHAR(191) NOT NULL DEFAULT 'header',
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `starts_at` TIMESTAMP NULL,
  `ends_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 38. GALLERIES
-- ============================================================

CREATE TABLE IF NOT EXISTS `galleries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `image_path` VARCHAR(191) NOT NULL,
  `category` VARCHAR(191) NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 39. CERTIFICATES
-- ============================================================

CREATE TABLE IF NOT EXISTS `certificates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `certificate_type` VARCHAR(50) NULL,
  `issue_date` DATE NULL,
  `certificate_number` VARCHAR(50) NULL,
  `template` JSON NOT NULL,
  `body` JSON NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `created_by` BIGINT UNSIGNED NOT NULL,
  `generated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificates_certificate_number_unique` (`certificate_number`),
  KEY `certificates_student_id_foreign` (`student_id`),
  KEY `certificates_created_by_foreign` (`created_by`),
  KEY `certificates_generated_by_foreign` (`generated_by`),
  CONSTRAINT `certificates_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certificates_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 40. ADMIT CARDS
-- ============================================================

CREATE TABLE IF NOT EXISTS `admit_cards` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id` BIGINT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `admit_card_number` VARCHAR(50) NOT NULL,
  `issue_date` DATE NOT NULL,
  `details` JSON NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'issued',
  `generated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admit_cards_admit_card_number_unique` (`admit_card_number`),
  UNIQUE KEY `admit_cards_exam_id_student_id_unique` (`exam_id`, `student_id`),
  KEY `admit_cards_student_id_foreign` (`student_id`),
  KEY `admit_cards_generated_by_foreign` (`generated_by`),
  CONSTRAINT `admit_cards_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admit_cards_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admit_cards_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 41. STUDENT ID CARDS
-- ============================================================

CREATE TABLE IF NOT EXISTS `student_id_cards` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `id_card_number` VARCHAR(50) NOT NULL,
  `issue_date` DATE NOT NULL,
  `expiry_date` DATE NULL,
  `blood_group` VARCHAR(10) NULL,
  `photo_url` TEXT NULL,
  `details` JSON NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `generated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id_cards_id_card_number_unique` (`id_card_number`),
  KEY `student_id_cards_student_id_foreign` (`student_id`),
  KEY `student_id_cards_generated_by_foreign` (`generated_by`),
  CONSTRAINT `student_id_cards_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_id_cards_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 42. ASSIGNMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `batch_id` BIGINT UNSIGNED NOT NULL,
  `subject_id` BIGINT UNSIGNED NOT NULL,
  `due_date` DATETIME NOT NULL,
  `total_marks` INT NULL,
  `file_path` TEXT NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `assignments_batch_id_foreign` (`batch_id`),
  KEY `assignments_subject_id_foreign` (`subject_id`),
  KEY `assignments_created_by_foreign` (`created_by`),
  CONSTRAINT `assignments_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 43. ASSIGNMENT SUBMISSIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS `assignment_submissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assignment_id` BIGINT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `file_path` VARCHAR(191) NULL,
  `notes` TEXT NULL,
  `submitted_at` TIMESTAMP NULL,
  `marks` INT NULL,
  `feedback` TEXT NULL,
  `graded_by` BIGINT UNSIGNED NULL,
  `graded_at` TIMESTAMP NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'submitted',
  `guardian_notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `assignment_submissions_assignment_id_foreign` (`assignment_id`),
  KEY `assignment_submissions_student_id_foreign` (`student_id`),
  KEY `assignment_submissions_graded_by_foreign` (`graded_by`),
  CONSTRAINT `assignment_submissions_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignment_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignment_submissions_graded_by_foreign` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 44. ROUTINES
-- ============================================================

CREATE TABLE IF NOT EXISTS `routines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_class_id` BIGINT UNSIGNED NOT NULL,
  `subject_id` BIGINT UNSIGNED NOT NULL,
  `teacher_id` BIGINT UNSIGNED NOT NULL,
  `day_of_week` TINYINT UNSIGNED NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `room_number` VARCHAR(191) NULL,
  `section_id` BIGINT UNSIGNED NULL,
  `batch_id` BIGINT UNSIGNED NULL,
  `academic_session_id` BIGINT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `type` VARCHAR(191) NOT NULL DEFAULT 'class',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `routines_school_class_id_foreign` (`school_class_id`),
  KEY `routines_subject_id_foreign` (`subject_id`),
  KEY `routines_teacher_id_foreign` (`teacher_id`),
  KEY `routines_section_id_foreign` (`section_id`),
  KEY `routines_batch_id_foreign` (`batch_id`),
  KEY `routines_academic_session_id_foreign` (`academic_session_id`),
  CONSTRAINT `routines_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `routines_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `routines_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `routines_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `routines_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `routines_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 45. CHART OF ACCOUNTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(32) NOT NULL,
  `name_en` VARCHAR(191) NOT NULL,
  `name_bn` VARCHAR(191) NULL,
  `type` VARCHAR(32) NOT NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chart_of_accounts_code_unique` (`code`),
  KEY `chart_of_accounts_type_index` (`type`),
  KEY `chart_of_accounts_parent_id_foreign` (`parent_id`),
  CONSTRAINT `chart_of_accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 46. LEDGER ENTRIES
-- ============================================================

CREATE TABLE IF NOT EXISTS `ledger_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `chart_of_account_id` BIGINT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `debit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `credit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `reference_type` VARCHAR(64) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `ledger_entries_date_chart_of_account_id_index` (`date`, `chart_of_account_id`),
  KEY `ledger_entries_reference_type_reference_id_index` (`reference_type`, `reference_id`),
  KEY `ledger_entries_chart_of_account_id_foreign` (`chart_of_account_id`),
  KEY `ledger_entries_created_by_foreign` (`created_by`),
  CONSTRAINT `ledger_entries_chart_of_account_id_foreign` FOREIGN KEY (`chart_of_account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ledger_entries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 47. EXPENSE CATEGORIES
-- ============================================================

CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `color` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 48. EXPENSES
-- ============================================================

CREATE TABLE IF NOT EXISTS `expenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `expense_category_id` BIGINT UNSIGNED NULL,
  `category` VARCHAR(64) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `date` DATE NOT NULL,
  `vendor` VARCHAR(191) NULL,
  `payment_method` VARCHAR(32) NOT NULL DEFAULT 'cash',
  `note` TEXT NULL,
  `chart_of_account_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_date_index` (`date`),
  KEY `expenses_category_index` (`category`),
  KEY `expenses_expense_category_id_foreign` (`expense_category_id`),
  KEY `expenses_chart_of_account_id_foreign` (`chart_of_account_id`),
  KEY `expenses_created_by_foreign` (`created_by`),
  CONSTRAINT `expenses_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_chart_of_account_id_foreign` FOREIGN KEY (`chart_of_account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 49. BUDGETS
-- ============================================================

CREATE TABLE IF NOT EXISTS `budgets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `expense_category_id` BIGINT UNSIGNED NULL,
  `period_type` ENUM('monthly','yearly','custom') NOT NULL DEFAULT 'monthly',
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `budgets_expense_category_id_foreign` (`expense_category_id`),
  CONSTRAINT `budgets_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 50. LEAVE TYPES
-- ============================================================

CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_en` VARCHAR(100) NOT NULL,
  `name_bn` VARCHAR(100) NULL,
  `days_per_year` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_paid` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 51. LEAVE REQUESTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` BIGINT UNSIGNED NOT NULL,
  `leave_type_id` BIGINT UNSIGNED NOT NULL,
  `from_date` DATE NOT NULL,
  `to_date` DATE NOT NULL,
  `reason` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `approver_id` BIGINT UNSIGNED NULL,
  `approver_note` TEXT NULL,
  `decided_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `leave_requests_status_index` (`status`),
  KEY `leave_requests_from_date_index` (`from_date`),
  KEY `leave_requests_teacher_id_foreign` (`teacher_id`),
  KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_requests_approver_id_foreign` (`approver_id`),
  CONSTRAINT `leave_requests_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 52. SALARY STRUCTURES
-- ============================================================

CREATE TABLE IF NOT EXISTS `salary_structures` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` BIGINT UNSIGNED NOT NULL,
  `basic` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `allowances` JSON NULL,
  `deductions` JSON NULL,
  `effective_from` DATE NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `salary_structures_teacher_id_is_active_index` (`teacher_id`, `is_active`),
  CONSTRAINT `salary_structures_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 53. PAYSLIPS
-- ============================================================

CREATE TABLE IF NOT EXISTS `payslips` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` BIGINT UNSIGNED NOT NULL,
  `month` TINYINT UNSIGNED NOT NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `basic` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_allowances` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_deductions` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `details` JSON NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `generated_at` TIMESTAMP NULL,
  `paid_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payslips_teacher_id_month_year_unique` (`teacher_id`, `month`, `year`),
  CONSTRAINT `payslips_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 54. STAFF ATTENDANCES
-- ============================================================

CREATE TABLE IF NOT EXISTS `staff_attendances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` BIGINT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'present',
  `check_in_at` TIMESTAMP NULL,
  `check_out_at` TIMESTAMP NULL,
  `note` TEXT NULL,
  `recorded_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_attendances_teacher_id_date_unique` (`teacher_id`, `date`),
  KEY `staff_attendances_date_index` (`date`),
  KEY `staff_attendances_status_index` (`status`),
  KEY `staff_attendances_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `staff_attendances_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_attendances_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 55. VEHICLES
-- ============================================================

CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `number` VARCHAR(64) NOT NULL,
  `type` VARCHAR(64) NULL,
  `capacity` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `driver_name` VARCHAR(191) NULL,
  `driver_phone` VARCHAR(32) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_number_unique` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 56. TRANSPORT ROUTES
-- ============================================================

CREATE TABLE IF NOT EXISTS `transport_routes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(32) NOT NULL,
  `fare` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `vehicle_id` BIGINT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transport_routes_code_unique` (`code`),
  KEY `transport_routes_vehicle_id_foreign` (`vehicle_id`),
  CONSTRAINT `transport_routes_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 57. TRANSPORT STOPS
-- ============================================================

CREATE TABLE IF NOT EXISTS `transport_stops` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `route_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `pickup_time` TIME NULL,
  `drop_time` TIME NULL,
  `sort` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `transport_stops_route_id_index` (`route_id`),
  CONSTRAINT `transport_stops_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `transport_routes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 58. TRANSPORT ASSIGNMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `transport_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `route_id` BIGINT UNSIGNED NOT NULL,
  `stop_id` BIGINT UNSIGNED NULL,
  `effective_from` DATE NOT NULL,
  `effective_to` DATE NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `transport_assignments_student_id_effective_from_index` (`student_id`, `effective_from`),
  KEY `transport_assignments_route_id_foreign` (`route_id`),
  KEY `transport_assignments_stop_id_foreign` (`stop_id`),
  CONSTRAINT `transport_assignments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transport_assignments_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `transport_routes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transport_assignments_stop_id_foreign` FOREIGN KEY (`stop_id`) REFERENCES `transport_stops` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 59. HOSTELS
-- ============================================================

CREATE TABLE IF NOT EXISTS `hostels` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `address` TEXT NULL,
  `description` TEXT NULL,
  `total_rooms` INT NOT NULL DEFAULT 0,
  `warden_name` VARCHAR(191) NULL,
  `warden_phone` VARCHAR(191) NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 60. HOSTEL ROOMS
-- ============================================================

CREATE TABLE IF NOT EXISTS `hostel_rooms` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hostel_id` BIGINT UNSIGNED NOT NULL,
  `room_number` VARCHAR(191) NOT NULL,
  `room_type` ENUM('single','double','triple','dormitory') NOT NULL DEFAULT 'double',
  `capacity` INT NOT NULL DEFAULT 2,
  `occupied` INT NOT NULL DEFAULT 0,
  `status` ENUM('available','full','maintenance') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hostel_rooms_hostel_id_room_number_unique` (`hostel_id`, `room_number`),
  CONSTRAINT `hostel_rooms_hostel_id_foreign` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 61. HOSTEL ASSIGNMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `hostel_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `room_id` BIGINT UNSIGNED NOT NULL,
  `check_in_date` DATE NOT NULL,
  `check_out_date` DATE NULL,
  `status` ENUM('active','inactive','expired') NOT NULL DEFAULT 'active',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hostel_assignments_student_id_foreign` (`student_id`),
  KEY `hostel_assignments_room_id_foreign` (`room_id`),
  CONSTRAINT `hostel_assignments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_assignments_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `hostel_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 62. BOOK CATEGORIES
-- ============================================================

CREATE TABLE IF NOT EXISTS `book_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 63. BOOKS
-- ============================================================

CREATE TABLE IF NOT EXISTS `books` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `author` VARCHAR(191) NULL,
  `publisher` VARCHAR(191) NULL,
  `isbn` VARCHAR(50) NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `shelf_location` VARCHAR(191) NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `available_quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `purchase_date` DATE NULL,
  `price` DECIMAL(10,2) NULL,
  `description` TEXT NULL,
  `cover_image` VARCHAR(191) NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `books_isbn_unique` (`isbn`),
  KEY `books_category_id_foreign` (`category_id`),
  KEY `books_created_by_foreign` (`created_by`),
  CONSTRAINT `books_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `book_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `books_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 64. BOOK ISSUES
-- ============================================================

CREATE TABLE IF NOT EXISTS `book_issues` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `book_id` BIGINT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NULL,
  `teacher_id` BIGINT UNSIGNED NULL,
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `return_date` DATE NULL,
  `status` VARCHAR(191) NOT NULL DEFAULT 'issued',
  `late_fee` DECIMAL(10,2) NULL,
  `fine_paid` TINYINT(1) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `issued_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `book_issues_book_id_foreign` (`book_id`),
  KEY `book_issues_student_id_foreign` (`student_id`),
  KEY `book_issues_teacher_id_foreign` (`teacher_id`),
  KEY `book_issues_issued_by_foreign` (`issued_by`),
  CONSTRAINT `book_issues_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_issues_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `book_issues_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `book_issues_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 65. LIBRARY SETTINGS
-- ============================================================

CREATE TABLE IF NOT EXISTS `library_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `late_fee_per_day` DECIMAL(8,2) NOT NULL DEFAULT 5.00,
  `max_books_per_student` INT UNSIGNED NOT NULL DEFAULT 3,
  `max_books_per_teacher` INT UNSIGNED NOT NULL DEFAULT 10,
  `issue_duration_days` INT UNSIGNED NOT NULL DEFAULT 14,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 66. SMS CAMPAIGNS
-- ============================================================

CREATE TABLE IF NOT EXISTS `sms_campaigns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `audience_type` VARCHAR(32) NOT NULL,
  `school_class_id` BIGINT UNSIGNED NULL,
  `section_id` BIGINT UNSIGNED NULL,
  `message` TEXT NOT NULL,
  `scheduled_at` TIMESTAMP NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `sent_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `sms_campaigns_status_index` (`status`),
  KEY `sms_campaigns_school_class_id_foreign` (`school_class_id`),
  KEY `sms_campaigns_section_id_foreign` (`section_id`),
  KEY `sms_campaigns_created_by_foreign` (`created_by`),
  CONSTRAINT `sms_campaigns_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_campaigns_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_campaigns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 67. SMS CAMPAIGN RECIPIENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `sms_campaign_recipients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sms_campaign_id` BIGINT UNSIGNED NOT NULL,
  `phone` VARCHAR(32) NOT NULL,
  `user_type` VARCHAR(32) NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'queued',
  `error` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `sms_campaign_recipients_status_index` (`status`),
  KEY `sms_campaign_recipients_sms_campaign_id_foreign` (`sms_campaign_id`),
  CONSTRAINT `sms_campaign_recipients_sms_campaign_id_foreign` FOREIGN KEY (`sms_campaign_id`) REFERENCES `sms_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 68. SCHEDULED NOTIFICATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS `scheduled_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `type` VARCHAR(191) NOT NULL,
  `channels` JSON NOT NULL,
  `recipients` JSON NOT NULL,
  `data` JSON NOT NULL,
  `schedule` JSON NOT NULL,
  `scheduled_at` TIMESTAMP NOT NULL,
  `sent_at` TIMESTAMP NULL,
  `status` VARCHAR(191) NOT NULL DEFAULT 'pending',
  `error_message` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `scheduled_notifications_status_scheduled_at_index` (`status`, `scheduled_at`),
  KEY `scheduled_notifications_created_by_foreign` (`created_by`),
  CONSTRAINT `scheduled_notifications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 69. NOTIFICATION TEMPLATES
-- ============================================================

CREATE TABLE IF NOT EXISTS `notification_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `key` VARCHAR(191) NOT NULL,
  `subject` VARCHAR(191) NULL,
  `content` LONGTEXT NULL,
  `sms_content` LONGTEXT NULL,
  `in_app_content` LONGTEXT NULL,
  `variables` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_templates_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 70. NOTIFICATION PREFERENCES
-- ============================================================

CREATE TABLE IF NOT EXISTS `notification_preferences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `notification_type` VARCHAR(191) NOT NULL,
  `email` TINYINT(1) NOT NULL DEFAULT 1,
  `sms` TINYINT(1) NOT NULL DEFAULT 0,
  `push` TINYINT(1) NOT NULL DEFAULT 1,
  `in_app` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_preferences_user_id_notification_type_unique` (`user_id`, `notification_type`),
  CONSTRAINT `notification_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 71. NOTIFICATION LOGS
-- ============================================================

CREATE TABLE IF NOT EXISTS `notification_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(191) NULL,
  `notifiable_type` VARCHAR(191) NOT NULL,
  `notifiable_id` BIGINT UNSIGNED NOT NULL,
  `content` LONGTEXT NULL,
  `channel` VARCHAR(191) NULL,
  `status` VARCHAR(191) NOT NULL DEFAULT 'pending',
  `error_message` TEXT NULL,
  `sent_at` TIMESTAMP NULL,
  `delivered_at` TIMESTAMP NULL,
  `opened_at` TIMESTAMP NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `notification_logs_notifiable_type_notifiable_id_index` (`notifiable_type`, `notifiable_id`),
  KEY `notification_logs_status_index` (`status`),
  KEY `notification_logs_channel_index` (`channel`),
  KEY `notification_logs_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 72. MESSAGES
-- ============================================================

CREATE TABLE IF NOT EXISTS `messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_id` BIGINT UNSIGNED NOT NULL,
  `receiver_id` BIGINT UNSIGNED NOT NULL,
  `subject` VARCHAR(191) NULL,
  `body` TEXT NOT NULL,
  `read_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `messages_sender_id_created_at_index` (`sender_id`, `created_at`),
  KEY `messages_receiver_id_created_at_index` (`receiver_id`, `created_at`),
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 73. TESTIMONIALS
-- ============================================================

CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NULL,
  `testimonial_type` VARCHAR(50) NULL,
  `testimonial_number` VARCHAR(50) NULL,
  `issue_date` DATE NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `generated_by` BIGINT UNSIGNED NULL,
  `student_id` BIGINT UNSIGNED NULL,
  `author_name` VARCHAR(191) NOT NULL,
  `author_designation` VARCHAR(191) NULL,
  `content` TEXT NOT NULL,
  `body` JSON NULL,
  `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `photo` VARCHAR(191) NULL,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `details` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `testimonials_testimonial_number_unique` (`testimonial_number`),
  KEY `testimonials_student_id_foreign` (`student_id`),
  KEY `testimonials_generated_by_foreign` (`generated_by`),
  CONSTRAINT `testimonials_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `testimonials_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 74. COMMITTEE MEMBERS
-- ============================================================

CREATE TABLE IF NOT EXISTS `committee_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `name_bn` VARCHAR(191) NULL,
  `designation` VARCHAR(191) NOT NULL,
  `designation_bn` VARCHAR(191) NULL,
  `photo` VARCHAR(191) NULL,
  `phone` VARCHAR(191) NULL,
  `email` VARCHAR(191) NULL,
  `bio` TEXT NULL,
  `bio_bn` TEXT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 75. VISITOR LOGS
-- ============================================================

CREATE TABLE IF NOT EXISTS `visitor_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `url` TEXT NOT NULL,
  `method` VARCHAR(10) NOT NULL,
  `user_agent` TEXT NULL,
  `referer` TEXT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `visitor_logs_created_at_index` (`created_at`),
  KEY `visitor_logs_ip_index` (`ip`),
  KEY `visitor_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `visitor_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 76. CAREERS
-- ============================================================

CREATE TABLE IF NOT EXISTS `careers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `description` TEXT NOT NULL,
  `requirements` TEXT NOT NULL,
  `type` ENUM('full-time','part-time','contract','internship') NOT NULL,
  `location` VARCHAR(191) NOT NULL,
  `salary_min` DECIMAL(10,2) NULL,
  `salary_max` DECIMAL(10,2) NULL,
  `deadline` DATE NOT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 77. JOB APPLICATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS `job_applications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `career_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(191) NOT NULL,
  `resume_path` VARCHAR(191) NOT NULL,
  `cover_letter` TEXT NULL,
  `status` ENUM('pending','reviewed','shortlisted','rejected','hired') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `job_applications_email_index` (`email`),
  KEY `job_applications_status_index` (`status`),
  KEY `job_applications_career_id_foreign` (`career_id`),
  CONSTRAINT `job_applications_career_id_foreign` FOREIGN KEY (`career_id`) REFERENCES `careers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 78. ACTIVITIES
-- ============================================================

CREATE TABLE IF NOT EXISTS `activities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(191) NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `message` TEXT NOT NULL,
  `icon` VARCHAR(191) NULL,
  `color` VARCHAR(191) NOT NULL DEFAULT 'primary',
  `properties` JSON NULL,
  `read_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `activities_user_id_foreign` (`user_id`),
  CONSTRAINT `activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 79. ACTIVITY LOG (Spatie-style)
-- ============================================================

CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `log_name` VARCHAR(191) NULL,
  `description` TEXT NOT NULL,
  `subject_type` VARCHAR(191) NULL,
  `subject_id` BIGINT UNSIGNED NULL,
  `event` VARCHAR(191) NULL,
  `causer_type` VARCHAR(191) NULL,
  `causer_id` BIGINT UNSIGNED NULL,
  `batch_uuid` CHAR(36) NULL,
  `properties` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `activity_log_log_name_index` (`log_name`),
  KEY `activity_log_subject_type_subject_id_index` (`subject_type`, `subject_id`),
  KEY `activity_log_causer_type_causer_id_index` (`causer_type`, `causer_id`),
  KEY `activity_log_batch_uuid_index` (`batch_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 80. CONTACT SUBMISSIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(32) NOT NULL DEFAULT 'contact',
  `name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `subject` VARCHAR(191) NULL,
  `message` TEXT NOT NULL,
  `meta` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 81. REFRESH TOKENS
-- ============================================================

CREATE TABLE IF NOT EXISTS `refresh_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token` VARCHAR(100) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `last_used_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `refresh_tokens_token_unique` (`token`),
  KEY `refresh_tokens_user_id_token_index` (`user_id`, `token`),
  CONSTRAINT `refresh_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 82. DEVICE TOKENS
-- ============================================================

CREATE TABLE IF NOT EXISTS `device_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token` VARCHAR(500) NOT NULL,
  `platform` VARCHAR(30) NULL,
  `device_name` VARCHAR(190) NULL,
  `app_version` VARCHAR(30) NULL,
  `last_used_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_tokens_user_id_token_unique` (`user_id`, `token`),
  KEY `device_tokens_token_index` (`token`),
  CONSTRAINT `device_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 83. DASHBOARD FAVORITES
-- ============================================================

CREATE TABLE IF NOT EXISTS `dashboard_favorites` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `url` VARCHAR(191) NOT NULL,
  `label` VARCHAR(191) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dashboard_favorites_user_id_url_unique` (`user_id`, `url`),
  CONSTRAINT `dashboard_favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 84. JOBS (Laravel Queue)
-- ============================================================

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(191) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(191) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED: Admin User
-- Password: password (bcrypt)
-- ============================================================

-- First ensure a super_admin role exists
INSERT INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`)
VALUES ('super_admin', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = `name`;

INSERT INTO `users` (`name`, `email`, `password`, `role_id`, `email_verified_at`, `created_at`, `updated_at`)
SELECT 'Admin', 'admin@eskoofy.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       `id`, NOW(), NOW(), NOW()
FROM `roles`
WHERE `name` = 'super_admin'
LIMIT 1
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 85. SMS LOGS
-- ============================================================

CREATE TABLE IF NOT EXISTS `sms_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipients` TEXT NOT NULL,
  `message` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'sent',
  `gateway` VARCHAR(32) NULL,
  `sms_status` MEDIUMTEXT NULL,
  `sent_by` BIGINT UNSIGNED NULL,
  `sent_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `sms_logs_status_index` (`status`),
  KEY `sms_logs_gateway_index` (`gateway`),
  KEY `sms_logs_sent_by_foreign` (`sent_by`),
  CONSTRAINT `sms_logs_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- END OF SCHEMA
-- ============================================================
