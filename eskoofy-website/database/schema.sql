-- ============================================================
-- Eskoofy Website — branding & licensing management DB schema
-- International-first: names/locales/amounts in English & USD, UTC.
-- MySQL 8 / 5.7 (utf8mb4).
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. CUSTOMERS
CREATE TABLE IF NOT EXISTS `customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `company` VARCHAR(191) NULL,
  `country` VARCHAR(64) NULL,
  `locale` VARCHAR(8) NOT NULL DEFAULT 'en',
  `role` VARCHAR(16) NOT NULL DEFAULT 'customer',
  `status` VARCHAR(16) NOT NULL DEFAULT 'active',
  `api_token` VARCHAR(128) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_email_unique` (`email`),
  UNIQUE KEY `customers_api_token_unique` (`api_token`),
  KEY `customers_role_index` (`role`),
  KEY `customers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. PLANS
CREATE TABLE IF NOT EXISTS `plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product` VARCHAR(16) NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(8) NOT NULL DEFAULT 'USD',
  `period` VARCHAR(16) NOT NULL DEFAULT 'one-time',
  `max_activations` INT NOT NULL DEFAULT 3,
  `features` JSON NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plans_product_slug_unique` (`product`, `slug`),
  KEY `plans_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. LICENSES
CREATE TABLE IF NOT EXISTS `licenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `license_key` VARCHAR(64) NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `plan_id` BIGINT UNSIGNED NULL,
  `product` VARCHAR(16) NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'active',
  `max_activations` INT NOT NULL DEFAULT 3,
  `starts_at` DATETIME NULL,
  `expires_at` DATETIME NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `licenses_license_key_unique` (`license_key`),
  KEY `licenses_customer_id_index` (`customer_id`),
  KEY `licenses_plan_id_index` (`plan_id`),
  KEY `licenses_status_index` (`status`),
  KEY `licenses_expires_at_index` (`expires_at`),
  CONSTRAINT `licenses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `licenses_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. LICENSE ACTIVATIONS
CREATE TABLE IF NOT EXISTS `license_activations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `license_id` BIGINT UNSIGNED NOT NULL,
  `domain` VARCHAR(191) NULL,
  `machine_id` VARCHAR(191) NULL,
  `ip_address` VARCHAR(64) NULL,
  `activated_at` DATETIME NULL,
  `deactivated_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_activations_license_domain_machine_unique` (`license_id`, `domain`, `machine_id`),
  KEY `license_activations_license_id_index` (`license_id`),
  CONSTRAINT `license_activations_license_id_foreign` FOREIGN KEY (`license_id`) REFERENCES `licenses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. PAYMENTS
CREATE TABLE IF NOT EXISTS `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `license_id` BIGINT UNSIGNED NULL,
  `plan_id` BIGINT UNSIGNED NULL,
  `gateway` VARCHAR(32) NOT NULL,
  `transaction_id` VARCHAR(191) NULL,
  `reference` VARCHAR(191) NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(8) NOT NULL DEFAULT 'USD',
  `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
  `paid_at` DATETIME NULL,
  `raw` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `payments_customer_id_index` (`customer_id`),
  KEY `payments_license_id_index` (`license_id`),
  KEY `payments_plan_id_index` (`plan_id`),
  KEY `payments_status_index` (`status`),
  CONSTRAINT `payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_license_id_foreign` FOREIGN KEY (`license_id`) REFERENCES `licenses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. SUBSCRIPTIONS
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `license_id` BIGINT UNSIGNED NOT NULL,
  `plan_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'active',
  `current_period_start` DATETIME NULL,
  `current_period_end` DATETIME NULL,
  `renews_at` DATETIME NULL,
  `gateway` VARCHAR(32) NULL,
  `gateway_subscription_id` VARCHAR(191) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_customer_id_index` (`customer_id`),
  KEY `subscriptions_license_id_index` (`license_id`),
  KEY `subscriptions_plan_id_index` (`plan_id`),
  KEY `subscriptions_status_index` (`status`),
  CONSTRAINT `subscriptions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_license_id_foreign` FOREIGN KEY (`license_id`) REFERENCES `licenses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. CONTACT MESSAGES
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `subject` VARCHAR(191) NULL,
  `message` TEXT NOT NULL,
  `read_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. ACTIVITY LOGS
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_type` VARCHAR(16) NOT NULL DEFAULT 'system',
  `actor_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(191) NOT NULL,
  `details` JSON NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_actor_index` (`actor_type`, `actor_id`),
  KEY `activity_logs_action_index` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. POST CATEGORIES (marketing blog)
CREATE TABLE IF NOT EXISTS `post_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `description` VARCHAR(400) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_categories_slug_unique` (`slug`),
  KEY `post_categories_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. POSTS (marketing blog / announcements)
CREATE TABLE IF NOT EXISTS `posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED NULL,
  `author_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `excerpt` VARCHAR(400) NULL,
  `content` LONGTEXT NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'draft',
  `featured_image` VARCHAR(255) NULL,
  `meta_title` VARCHAR(191) NULL,
  `meta_description` VARCHAR(255) NULL,
  `views` INT UNSIGNED NOT NULL DEFAULT 0,
  `published_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_slug_unique` (`slug`),
  KEY `posts_status_published_index` (`status`, `published_at`),
  KEY `posts_category_id_index` (`category_id`),
  KEY `posts_author_id_index` (`author_id`),
  CONSTRAINT `posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `post_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `posts_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Seed data
-- ============================================================

-- Admin customer (password: admin123) — change in production!
INSERT IGNORE INTO `customers` (`id`, `name`, `email`, `password`, `role`, `status`, `locale`, `created_at`, `updated_at`)
VALUES (1, 'Eskoofy Admin', 'admin@eskoofy.com', '$2y$12$V0siFMqG1/FSwS/hqKypNOWY5GY8eOV/QQlMKaknHkWPxY5CyVS9K', 'admin', 'active', 'en', NOW(), NOW());

-- Default plans (all amounts in USD — international standard).
INSERT IGNORE INTO `plans` (`id`, `product`, `name`, `slug`, `description`, `price`, `currency`, `period`, `max_activations`, `features`, `sort_order`, `active`, `created_at`, `updated_at`) VALUES
(1, 'app',     'Monthly',    'monthly',    'School management app — monthly license', 9.00,  'USD', 'monthly', 3, '["One school", "All modules", "Email support"]', 1, 1, NOW(), NOW()),
(2, 'app',     'Yearly',     'yearly',     'School management app — yearly license',  90.00, 'USD', 'yearly',  3, '["One school", "All modules", "Priority support"]', 2, 1, NOW(), NOW()),
(3, 'theme',   'Lifetime',   'lifetime',   'WordPress theme — lifetime license',      59.00, 'USD', 'one-time', 1, '["Unlimited sites", "Lifetime updates", "Support for 1 year"]', 1, 1, NOW(), NOW());

-- Blog categories (marketing).
INSERT IGNORE INTO `post_categories` (`id`, `name`, `slug`, `description`, `sort_order`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Announcements', 'announcements', 'News and updates from the Eskoofy team', 1, 1, NOW(), NOW()),
(2, 'Guides', 'guides', 'How-tos and buying guidance for schools', 2, 1, NOW(), NOW());

-- Example posts (one draft as an editor example).
INSERT IGNORE INTO `posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `excerpt`, `content`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Introducing Eskoofy: one school management system, two deployments', 'introducing-eskoofy-two-deployments',
 'The Eskoofy school management system now comes in two flavors — a Laravel application and a WordPress theme. Pick the deployment that fits your school.',
 '<p>Managing a school means managing admissions, attendance, fees, exams, results, transport, hostels and more. Eskoofy brings all of it into one place.</p>
<h2>Two deployments, one system</h2>
<p><a href="/products/app">Eskoofy School App</a> is the full Laravel application — run it yourself or let us host it. <a href="/products/theme">Eskoofy WP Theme</a> turns your existing WordPress site into a school management portal.</p>
<p>Both share the same modules, the same data model, and the same license server. See <a href="/pricing">pricing</a> to get started.</p>',
 'published', 12, NOW(), NOW(), NOW()),
(2, 2, 1, 'How to choose between the Eskoofy app and the WordPress theme', 'choose-app-or-theme',
 'A practical comparison to help your school decide between the self-hosted WordPress theme and the full application.',
 '<p>Choosing between the app and the theme mostly comes down to infrastructure and control.</p>
<h2>Choose the app if...</h2>
<p>...you want the complete package with all modules, an admin dashboard, and a license server that handles API validation automatically.</p>
<h2>Choose the theme if...</h2>
<p>...you already run WordPress and want your data on your own server with a familiar theme workflow.</p>
<p>Either way you can <a href="/contact">talk to us</a> before buying.</p>',
 'published', 8, NOW(), NOW(), NOW()),
(3, 1, 1, 'Coming soon: Paddle payments and the Eskoofy license portal', 'coming-soon-paddle-license-portal',
 'Payments via Paddle and the self-service license portal are on the roadmap.',
 '<p>We are adding Paddle as a payment gateway and upgrading the <a href="/account">customer license portal</a> with renewal reminders and activation history.</p>',
 'draft', 0, NULL, NOW(), NOW(), NOW());