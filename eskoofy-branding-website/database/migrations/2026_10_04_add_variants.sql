-- ============================================================
-- Eskoofy Website — add the `variant` column (bd | int)
--
-- Upgrade path for existing installs. `database/schema.sql` already contains
-- these columns for fresh installs; run this only when upgrading in place.
--
-- Why: `payments.variant` was the only place a market profile was recorded, so
-- licenses and customers could not be split by BD vs INT. A *variant* is a
-- market build profile (bd | int) — it is NOT one of the four products
-- (app | php | theme | node), which stay in `plans.product`.
--
-- `plans` intentionally gets NO variant column: prices are USD-canonical and
-- the BD figure is derived for display (App\Services\VariantResolver).
--
-- Safe to run more than once — each statement is guarded.
-- MySQL 8 / 5.7.
--
-- The new columns are NULL-able on purpose. `NOT NULL DEFAULT 'int'` would
-- back-fill every pre-existing row with 'int', and because 'int' is itself a
-- valid variant the backfill script could no longer distinguish "resolved to
-- international" from "never resolved" — so every Bangladeshi customer and
-- license would be permanently misreported as international. NULL means
-- "not yet resolved"; the backfill fills it in.
-- ============================================================

-- -------------------------------------------------------------- customers ----
SET @has_customers_variant := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'variant'
);
SET @sql := IF(@has_customers_variant = 0,
    'ALTER TABLE `customers` ADD COLUMN `variant` VARCHAR(8) NULL DEFAULT NULL AFTER `locale`',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_customers_variant_idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'customers_variant_index'
);
SET @sql := IF(@has_customers_variant_idx = 0,
    'ALTER TABLE `customers` ADD KEY `customers_variant_index` (`variant`)',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------------- licenses ----
SET @has_licenses_variant := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'licenses' AND COLUMN_NAME = 'variant'
);
SET @sql := IF(@has_licenses_variant = 0,
    'ALTER TABLE `licenses` ADD COLUMN `variant` VARCHAR(8) NULL DEFAULT NULL AFTER `product`',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_licenses_variant_idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'licenses' AND INDEX_NAME = 'licenses_variant_index'
);
SET @sql := IF(@has_licenses_variant_idx = 0,
    'ALTER TABLE `licenses` ADD KEY `licenses_variant_index` (`variant`)',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- A composite index backs the Product x Variant matrix, which groups by both.
SET @has_licenses_product_variant_idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'licenses' AND INDEX_NAME = 'licenses_product_variant_index'
);
SET @sql := IF(@has_licenses_product_variant_idx = 0,
    'ALTER TABLE `licenses` ADD KEY `licenses_product_variant_index` (`product`, `variant`)',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------------ plans ----
-- Node.js plans. All four products must have monthly + yearly stock so that
-- every cell of the Product x Variant matrix is purchasable.
INSERT IGNORE INTO `plans`
    (`product`, `name`, `slug`, `description`, `price`, `currency`, `period`,
     `max_activations`, `features`, `sort_order`, `active`, `created_at`, `updated_at`)
VALUES
    ('node', 'Monthly', 'monthly',
     'Node.js school system — monthly subscription (one school, all modules)',
     12.00, 'USD', 'monthly', 3,
     '["One school", "All modules", "Next.js + Prisma stack", "Automatic cloud backups", "Branded documents & watermarks", "Email support"]',
     1, 1, NOW(), NOW()),
    ('node', 'Yearly', 'yearly',
     'Node.js school system — yearly subscription (one school, all modules)',
     120.00, 'USD', 'yearly', 3,
     '["One school", "All modules", "Next.js + Prisma stack", "Automatic cloud backups", "Branded documents & watermarks", "Priority support"]',
     2, 1, NOW(), NOW());

-- --------------------------------------------------------------- backfill ----
-- The ALTERs above leave every existing row NULL. Populate the real values with
-- the idempotent CLI script — it resolves each row as
-- explicit -> latest payment variant -> BD country -> 'int':
--
--     php database/backfill_variants.php --dry-run
--     php database/backfill_variants.php --apply
--
-- Rollback (only safe if no BD rows exist yet):
--     ALTER TABLE `licenses`  DROP KEY `licenses_product_variant_index`,
--                           DROP KEY `licenses_variant_index`,
--                           DROP COLUMN `variant`;
--     ALTER TABLE `customers` DROP KEY `customers_variant_index`,
--                           DROP COLUMN `variant`;