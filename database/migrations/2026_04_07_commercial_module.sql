-- ============================================================
-- Commercial Accounting Module - Migration
-- Date: 2026-04-07
-- Database: acc1 (MariaDB / MySQL)
-- ============================================================
-- Run this file via phpMyAdmin → Import, or via mysql CLI:
--   mysql -u acc1 -p acc1 < 2026_04_07_commercial_module.sql
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;

-- ============================================================
-- 1. Alter journal_entries: add source_type, source_id
-- ============================================================

ALTER TABLE `journal_entries`
    ADD COLUMN IF NOT EXISTS `source_type` VARCHAR(50) NULL DEFAULT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `source_id`   INT UNSIGNED NULL DEFAULT NULL AFTER `source_type`;

-- Index for source lookup
CREATE INDEX IF NOT EXISTS `idx_je_source` ON `journal_entries` (`source_type`, `source_id`);

-- ============================================================
-- 2. settings table (key/value)
-- ============================================================

CREATE TABLE IF NOT EXISTS `settings` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_val` VARCHAR(500) NOT NULL DEFAULT '',
    `note`        VARCHAR(255) DEFAULT NULL,
    `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. customers
-- ============================================================

CREATE TABLE IF NOT EXISTS `customers` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`       VARCHAR(50)  NOT NULL,
    `name`       VARCHAR(255) NOT NULL,
    `phone`      VARCHAR(50)  DEFAULT NULL,
    `email`      VARCHAR(150) DEFAULT NULL,
    `address`    VARCHAR(500) DEFAULT NULL,
    `tax_no`     VARCHAR(100) DEFAULT NULL,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_customer_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. suppliers
-- ============================================================

CREATE TABLE IF NOT EXISTS `suppliers` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`       VARCHAR(50)  NOT NULL,
    `name`       VARCHAR(255) NOT NULL,
    `phone`      VARCHAR(50)  DEFAULT NULL,
    `email`      VARCHAR(150) DEFAULT NULL,
    `address`    VARCHAR(500) DEFAULT NULL,
    `tax_no`     VARCHAR(100) DEFAULT NULL,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_supplier_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. items (inventory / services)
-- ============================================================

CREATE TABLE IF NOT EXISTS `items` (
    `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `sku`         VARCHAR(100)     NOT NULL,
    `name`        VARCHAR(255)     NOT NULL,
    `unit`        VARCHAR(50)      DEFAULT 'وحدة',
    `cost_price`  DECIMAL(18,4)    NOT NULL DEFAULT 0.0000,
    `sale_price`  DECIMAL(18,4)    NOT NULL DEFAULT 0.0000,
    `vat_rate`    DECIMAL(6,4)     NOT NULL DEFAULT 0.1600,
    `is_active`   TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_item_sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. inventory_movements
-- ============================================================

CREATE TABLE IF NOT EXISTS `inventory_movements` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `item_id`       INT UNSIGNED  NOT NULL,
    `movement_date` DATE          NOT NULL,
    `movement_type` ENUM('in','out','adjust') NOT NULL DEFAULT 'in',
    `qty`           DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `unit_cost`     DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `source_type`   VARCHAR(50)   DEFAULT NULL,
    `source_id`     INT UNSIGNED  DEFAULT NULL,
    `created_at`    TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_im_item`   (`item_id`),
    KEY `idx_im_source` (`source_type`, `source_id`),
    CONSTRAINT `fk_im_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. sales_invoices
-- ============================================================

CREATE TABLE IF NOT EXISTS `sales_invoices` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `invoice_no`   VARCHAR(50)   NOT NULL,
    `invoice_date` DATE          NOT NULL,
    `customer_id`  INT UNSIGNED  NOT NULL,
    `status`       ENUM('draft','posted','void') NOT NULL DEFAULT 'draft',
    `subtotal`     DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `vat_total`    DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `total`        DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `notes`        TEXT          DEFAULT NULL,
    `created_at`   TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sales_invoice_no` (`invoice_no`),
    KEY `idx_si_customer` (`customer_id`),
    CONSTRAINT `fk_si_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. sales_invoice_lines
-- ============================================================

CREATE TABLE IF NOT EXISTS `sales_invoice_lines` (
    `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `sales_invoice_id` INT UNSIGNED  NOT NULL,
    `item_id`          INT UNSIGNED  DEFAULT NULL,
    `description`      VARCHAR(500)  NOT NULL DEFAULT '',
    `qty`              DECIMAL(18,4) NOT NULL DEFAULT 1.0000,
    `unit_price`       DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `vat_rate`         DECIMAL(6,4)  NOT NULL DEFAULT 0.1600,
    `vat_amount`       DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `line_total`       DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `line_no`          SMALLINT      NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_sil_invoice` (`sales_invoice_id`),
    KEY `idx_sil_item`    (`item_id`),
    CONSTRAINT `fk_sil_invoice` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sil_item`    FOREIGN KEY (`item_id`)          REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. purchase_invoices
-- ============================================================

CREATE TABLE IF NOT EXISTS `purchase_invoices` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `invoice_no`   VARCHAR(50)   NOT NULL,
    `invoice_date` DATE          NOT NULL,
    `supplier_id`  INT UNSIGNED  NOT NULL,
    `status`       ENUM('draft','posted','void') NOT NULL DEFAULT 'draft',
    `subtotal`     DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `vat_total`    DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `total`        DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `notes`        TEXT          DEFAULT NULL,
    `created_at`   TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_purchase_invoice_no` (`invoice_no`),
    KEY `idx_pi_supplier` (`supplier_id`),
    CONSTRAINT `fk_pi_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. purchase_invoice_lines
-- ============================================================

CREATE TABLE IF NOT EXISTS `purchase_invoice_lines` (
    `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `purchase_invoice_id` INT UNSIGNED  NOT NULL,
    `item_id`             INT UNSIGNED  DEFAULT NULL,
    `description`         VARCHAR(500)  NOT NULL DEFAULT '',
    `qty`                 DECIMAL(18,4) NOT NULL DEFAULT 1.0000,
    `unit_cost`           DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `vat_rate`            DECIMAL(6,4)  NOT NULL DEFAULT 0.1600,
    `vat_amount`          DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `line_total`          DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `line_no`             SMALLINT      NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_pil_invoice` (`purchase_invoice_id`),
    KEY `idx_pil_item`    (`item_id`),
    CONSTRAINT `fk_pil_invoice` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pil_item`    FOREIGN KEY (`item_id`)             REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. receipts (customer receipts)
-- ============================================================

CREATE TABLE IF NOT EXISTS `receipts` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `receipt_no`   VARCHAR(50)   NOT NULL,
    `receipt_date` DATE          NOT NULL,
    `customer_id`  INT UNSIGNED  DEFAULT NULL,
    `amount`       DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `method`       ENUM('cash','bank') NOT NULL DEFAULT 'cash',
    `status`       ENUM('draft','posted','void') NOT NULL DEFAULT 'draft',
    `notes`        TEXT          DEFAULT NULL,
    `created_at`   TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_receipt_no` (`receipt_no`),
    KEY `idx_receipt_customer` (`customer_id`),
    CONSTRAINT `fk_receipt_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. payments (supplier payments)
-- ============================================================

CREATE TABLE IF NOT EXISTS `payments` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `payment_no`   VARCHAR(50)   NOT NULL,
    `payment_date` DATE          NOT NULL,
    `supplier_id`  INT UNSIGNED  DEFAULT NULL,
    `amount`       DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `method`       ENUM('cash','bank') NOT NULL DEFAULT 'cash',
    `status`       ENUM('draft','posted','void') NOT NULL DEFAULT 'draft',
    `notes`        TEXT          DEFAULT NULL,
    `created_at`   TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payment_no` (`payment_no`),
    KEY `idx_payment_supplier` (`supplier_id`),
    CONSTRAINT `fk_payment_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. Control accounts in chart_of_accounts (insert if missing)
-- ============================================================
-- We use code-based inserts with IF NOT EXISTS pattern via INSERT IGNORE

-- Accounts Receivable (AR) - asset sub-account
INSERT IGNORE INTO `chart_of_accounts`
    (`code`, `name_ar`, `name_en`, `account_type`, `sub_type`, `parent_id`, `is_postable`, `opening_debit`, `opening_credit`, `is_active`)
VALUES
    ('1-3', 'ذمم العملاء (مدينون)', 'Accounts Receivable', 'asset', 'receivable', 1, 1, 0, 0, 1);

-- Accounts Payable (AP) - liability sub-account
INSERT IGNORE INTO `chart_of_accounts`
    (`code`, `name_ar`, `name_en`, `account_type`, `sub_type`, `parent_id`, `is_postable`, `opening_debit`, `opening_credit`, `is_active`)
VALUES
    ('2-1', 'ذمم الموردين (دائنون)', 'Accounts Payable', 'liability', 'payable', 4, 1, 0, 0, 1);

-- Sales Revenue
INSERT IGNORE INTO `chart_of_accounts`
    (`code`, `name_ar`, `name_en`, `account_type`, `sub_type`, `parent_id`, `is_postable`, `opening_debit`, `opening_credit`, `is_active`)
VALUES
    ('4-1', 'إيرادات المبيعات', 'Sales Revenue', 'revenue', 'sales', 6, 1, 0, 0, 1);

-- Inventory / COGS
INSERT IGNORE INTO `chart_of_accounts`
    (`code`, `name_ar`, `name_en`, `account_type`, `sub_type`, `parent_id`, `is_postable`, `opening_debit`, `opening_credit`, `is_active`)
VALUES
    ('1-4', 'المخزون', 'Inventory', 'asset', 'inventory', 1, 1, 0, 0, 1);

INSERT IGNORE INTO `chart_of_accounts`
    (`code`, `name_ar`, `name_en`, `account_type`, `sub_type`, `parent_id`, `is_postable`, `opening_debit`, `opening_credit`, `is_active`)
VALUES
    ('5-2', 'تكلفة البضاعة المباعة', 'Cost of Goods Sold', 'expense', 'cogs', 8, 1, 0, 0, 1);

-- VAT Output (VAT Payable)
INSERT IGNORE INTO `chart_of_accounts`
    (`code`, `name_ar`, `name_en`, `account_type`, `sub_type`, `parent_id`, `is_postable`, `opening_debit`, `opening_credit`, `is_active`)
VALUES
    ('2-2', 'ضريبة القيمة المضافة - مخرجات', 'VAT Output (Payable)', 'liability', 'vat_output', 4, 1, 0, 0, 1);

-- VAT Input (VAT Receivable)
INSERT IGNORE INTO `chart_of_accounts`
    (`code`, `name_ar`, `name_en`, `account_type`, `sub_type`, `parent_id`, `is_postable`, `opening_debit`, `opening_credit`, `is_active`)
VALUES
    ('1-5', 'ضريبة القيمة المضافة - مدخلات', 'VAT Input (Receivable)', 'asset', 'vat_input', 1, 1, 0, 0, 1);

-- Purchases account (for purchase invoices)
INSERT IGNORE INTO `chart_of_accounts`
    (`code`, `name_ar`, `name_en`, `account_type`, `sub_type`, `parent_id`, `is_postable`, `opening_debit`, `opening_credit`, `is_active`)
VALUES
    ('5-3', 'المشتريات', 'Purchases', 'expense', 'purchases', 8, 1, 0, 0, 1);

-- ============================================================
-- 14. Default settings (control account IDs)
-- We'll use SELECT to find the newly inserted accounts and store them.
-- These INSERT IGNORE statements will be updated later via the UI.
-- ============================================================

INSERT IGNORE INTO `settings` (`setting_key`, `setting_val`, `note`) VALUES
    ('vat_rate',            '0.16',  'معدل ضريبة القيمة المضافة الافتراضي'),
    ('account_ar',          '',      'حساب ذمم العملاء (Accounts Receivable)'),
    ('account_ap',          '',      'حساب ذمم الموردين (Accounts Payable)'),
    ('account_sales',       '',      'حساب إيرادات المبيعات'),
    ('account_inventory',   '',      'حساب المخزون'),
    ('account_cogs',        '',      'حساب تكلفة البضاعة المباعة'),
    ('account_vat_output',  '',      'حساب ضريبة القيمة المضافة - مخرجات'),
    ('account_vat_input',   '',      'حساب ضريبة القيمة المضافة - مدخلات'),
    ('account_purchases',   '',      'حساب المشتريات'),
    ('account_cash',        '',      'حساب الصندوق (نقد)'),
    ('account_bank',        '',      'حساب البنك');

-- Auto-populate settings from chart_of_accounts codes
UPDATE `settings` s
JOIN `chart_of_accounts` a ON a.code = '1-3'
SET s.setting_val = a.id
WHERE s.setting_key = 'account_ar' AND s.setting_val = '';

UPDATE `settings` s
JOIN `chart_of_accounts` a ON a.code = '2-1'
SET s.setting_val = a.id
WHERE s.setting_key = 'account_ap' AND s.setting_val = '';

UPDATE `settings` s
JOIN `chart_of_accounts` a ON a.code = '4-1'
SET s.setting_val = a.id
WHERE s.setting_key = 'account_sales' AND s.setting_val = '';

UPDATE `settings` s
JOIN `chart_of_accounts` a ON a.code = '1-4'
SET s.setting_val = a.id
WHERE s.setting_key = 'account_inventory' AND s.setting_val = '';

UPDATE `settings` s
JOIN `chart_of_accounts` a ON a.code = '5-2'
SET s.setting_val = a.id
WHERE s.setting_key = 'account_cogs' AND s.setting_val = '';

UPDATE `settings` s
JOIN `chart_of_accounts` a ON a.code = '2-2'
SET s.setting_val = a.id
WHERE s.setting_key = 'account_vat_output' AND s.setting_val = '';

UPDATE `settings` s
JOIN `chart_of_accounts` a ON a.code = '1-5'
SET s.setting_val = a.id
WHERE s.setting_key = 'account_vat_input' AND s.setting_val = '';

UPDATE `settings` s
JOIN `chart_of_accounts` a ON a.code = '5-3'
SET s.setting_val = a.id
WHERE s.setting_key = 'account_purchases' AND s.setting_val = '';

UPDATE `settings` s
JOIN `chart_of_accounts` a ON a.code = '1-1'
SET s.setting_val = a.id
WHERE s.setting_key = 'account_cash' AND s.setting_val = '';

UPDATE `settings` s
JOIN `chart_of_accounts` a ON a.code = '1-2'
SET s.setting_val = a.id
WHERE s.setting_key = 'account_bank' AND s.setting_val = '';
