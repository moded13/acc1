-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 08, 2026 at 01:37 PM
-- Server version: 10.5.29-MariaDB
-- PHP Version: 8.4.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `acc1`
--

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts`
--

CREATE TABLE `chart_of_accounts` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `account_type` enum('asset','liability','equity','revenue','expense','other') NOT NULL,
  `sub_type` varchar(50) DEFAULT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `is_postable` tinyint(1) NOT NULL DEFAULT 1,
  `opening_debit` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `opening_credit` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chart_of_accounts`
--

INSERT INTO `chart_of_accounts` (`id`, `code`, `name_ar`, `name_en`, `account_type`, `sub_type`, `parent_id`, `is_postable`, `opening_debit`, `opening_credit`, `is_active`, `notes`, `created_at`, `updated_at`) VALUES
(1, '1', 'الأصول', 'Assets', 'asset', NULL, NULL, 0, 0.0000, 0.0000, 1, NULL, '2026-04-06 16:04:11', '2026-04-07 09:01:46'),
(2, '1-1', 'الصندوق', 'Cash', 'asset', 'cash', 1, 1, 0.0000, 0.0000, 1, NULL, '2026-04-06 16:04:11', '2026-04-06 16:04:11'),
(3, '1-2', 'البنك', 'Bank', 'asset', 'bank', 1, 1, 0.0000, 0.0000, 1, NULL, '2026-04-06 16:04:11', '2026-04-06 16:04:11'),
(4, '2', 'الخصوم', 'Liabilities', 'liability', NULL, NULL, 0, 0.0000, 0.0000, 1, NULL, '2026-04-06 16:04:11', '2026-04-07 09:01:46'),
(5, '3', 'حقوق الملكية', 'Equity', 'equity', NULL, NULL, 0, 0.0000, 0.0000, 1, NULL, '2026-04-06 16:04:11', '2026-04-07 09:01:46'),
(6, '4', 'الإيرادات', 'Revenue', 'revenue', NULL, NULL, 0, 0.0000, 0.0000, 1, NULL, '2026-04-06 16:04:11', '2026-04-07 09:01:46'),
(7, '4-1', 'مبيعات', 'Sales', 'revenue', 'sales', 4, 1, 0.0000, 0.0000, 1, NULL, '2026-04-06 16:04:11', '2026-04-06 16:04:11'),
(8, '5', 'المصروفات', 'Expenses', 'expense', NULL, NULL, 0, 0.0000, 0.0000, 1, NULL, '2026-04-06 16:04:11', '2026-04-07 09:01:46'),
(9, '5-1', 'مصروفات عامة', 'General Expense', 'expense', 'general', 5, 1, 0.0000, 0.0000, 1, NULL, '2026-04-06 16:04:11', '2026-04-06 16:04:11'),
(10, 'a1', 'محمد', NULL, 'asset', NULL, NULL, 0, 0.0000, 0.0000, 1, NULL, '2026-04-07 09:19:38', '2026-04-07 09:19:38'),
(11, '1-3', 'ذمم العملاء (مدينون)', 'Accounts Receivable', 'asset', 'receivable', 1, 1, 0.0000, 0.0000, 1, NULL, '2026-04-07 12:14:05', '2026-04-07 12:14:05'),
(12, '2-1', 'ذمم الموردين (دائنون)', 'Accounts Payable', 'liability', 'payable', 4, 1, 0.0000, 0.0000, 1, NULL, '2026-04-07 12:14:05', '2026-04-07 12:14:05'),
(14, '1-4', 'المخزون', 'Inventory', 'asset', 'inventory', 1, 1, 0.0000, 0.0000, 1, NULL, '2026-04-07 12:14:05', '2026-04-07 12:14:05'),
(15, '5-2', 'تكلفة البضاعة المباعة', 'Cost of Goods Sold', 'expense', 'cogs', 8, 1, 0.0000, 0.0000, 1, NULL, '2026-04-07 12:14:05', '2026-04-07 12:14:05'),
(16, '2-2', 'ضريبة القيمة المضافة - مخرجات', 'VAT Output (Payable)', 'liability', 'vat_output', 4, 1, 0.0000, 0.0000, 1, NULL, '2026-04-07 12:14:05', '2026-04-07 12:14:05'),
(17, '1-5', 'ضريبة القيمة المضافة - مدخلات', 'VAT Input (Receivable)', 'asset', 'vat_input', 1, 1, 0.0000, 0.0000, 1, NULL, '2026-04-07 12:14:05', '2026-04-07 12:14:05'),
(18, '5-3', 'المشتريات', 'Purchases', 'expense', 'purchases', 8, 1, 0.0000, 0.0000, 1, NULL, '2026-04-07 12:14:05', '2026-04-07 12:14:05');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `tax_no` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `code`, `name`, `phone`, `email`, `address`, `tax_no`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'a1', 'محمد', '0799152525', 'admin@yourdomain.com', '111111111111111', '152545', 1, '2026-04-08 07:20:12', '2026-04-08 07:20:12');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `movement_date` date NOT NULL,
  `movement_type` enum('in','out','adjust') NOT NULL DEFAULT 'in',
  `qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(10) UNSIGNED NOT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(50) DEFAULT 'وحدة',
  `cost_price` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `sale_price` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `vat_rate` decimal(6,4) NOT NULL DEFAULT 0.1600,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` int(10) UNSIGNED NOT NULL,
  `entry_no` varchar(50) NOT NULL,
  `entry_date` date NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `status` enum('draft','posted','canceled') NOT NULL DEFAULT 'draft',
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` int(10) UNSIGNED DEFAULT NULL,
  `branch_id` int(10) UNSIGNED DEFAULT NULL,
  `currency_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `journal_entries`
--

INSERT INTO `journal_entries` (`id`, `entry_no`, `entry_date`, `description`, `status`, `source_type`, `source_id`, `branch_id`, `currency_code`, `created_at`, `updated_at`) VALUES
(1, '2026-00001', '2026-04-07', 'قيد تجريبي تلقائي', 'posted', NULL, NULL, NULL, NULL, '2026-04-07 06:11:27', '2026-04-07 06:11:27'),
(2, '2026-00002', '2026-04-07', 'قيد تجريبي تلقائي', 'posted', NULL, NULL, NULL, NULL, '2026-04-07 07:11:02', '2026-04-07 07:11:02'),
(3, '2026-00003', '2026-04-08', 'ترحيل فاتورة مشتريات PI-2026-00001', 'posted', 'purchase_invoice', 1, NULL, NULL, '2026-04-08 07:21:52', '2026-04-08 07:21:52');

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry_lines`
--

CREATE TABLE `journal_entry_lines` (
  `id` int(10) UNSIGNED NOT NULL,
  `journal_entry_id` int(10) UNSIGNED NOT NULL,
  `account_id` int(10) UNSIGNED NOT NULL,
  `line_no` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `debit` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `credit` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `journal_entry_lines`
--

INSERT INTO `journal_entry_lines` (`id`, `journal_entry_id`, `account_id`, `line_no`, `debit`, `credit`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 100.0000, 0.0000, 'مدين تجريبي', '2026-04-07 06:11:27', '2026-04-07 06:11:27'),
(2, 1, 4, 2, 0.0000, 100.0000, 'دائن تجريبي', '2026-04-07 06:11:27', '2026-04-07 06:11:27'),
(3, 2, 1, 1, 100.0000, 0.0000, 'مدين تجريبي', '2026-04-07 07:11:02', '2026-04-07 07:11:02'),
(4, 2, 4, 2, 0.0000, 100.0000, 'دائن تجريبي', '2026-04-07 07:11:02', '2026-04-07 07:11:02'),
(5, 3, 18, 1, 550.0000, 0.0000, 'مدين - مشتريات/مخزون', '2026-04-08 07:21:52', '2026-04-08 07:21:52'),
(6, 3, 17, 2, 88.0000, 0.0000, 'مدين - ضريبة مشتريات مدخلات', '2026-04-08 07:21:52', '2026-04-08 07:21:52'),
(7, 3, 12, 3, 0.0000, 638.0000, 'دائن - ذمم موردين', '2026-04-08 07:21:52', '2026-04-08 07:21:52');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `payment_no` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `supplier_id` int(10) UNSIGNED DEFAULT NULL,
  `amount` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `method` enum('cash','bank') NOT NULL DEFAULT 'cash',
  `status` enum('draft','posted','void') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoices`
--

CREATE TABLE `purchase_invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `supplier_id` int(10) UNSIGNED NOT NULL,
  `status` enum('draft','posted','void') NOT NULL DEFAULT 'draft',
  `subtotal` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `vat_total` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `total` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_invoices`
--

INSERT INTO `purchase_invoices` (`id`, `invoice_no`, `invoice_date`, `supplier_id`, `status`, `subtotal`, `vat_total`, `total`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'PI-2026-00001', '2026-04-08', 1, 'posted', 550.0000, 88.0000, 638.0000, NULL, '2026-04-08 07:21:47', '2026-04-08 07:21:52');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoice_lines`
--

CREATE TABLE `purchase_invoice_lines` (
  `id` int(10) UNSIGNED NOT NULL,
  `purchase_invoice_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(500) NOT NULL DEFAULT '',
  `qty` decimal(18,4) NOT NULL DEFAULT 1.0000,
  `unit_cost` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `vat_rate` decimal(6,4) NOT NULL DEFAULT 0.1600,
  `vat_amount` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `line_total` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `line_no` smallint(6) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_invoice_lines`
--

INSERT INTO `purchase_invoice_lines` (`id`, `purchase_invoice_id`, `item_id`, `description`, `qty`, `unit_cost`, `vat_rate`, `vat_amount`, `line_total`, `line_no`) VALUES
(1, 1, NULL, 'شاشة', 10.0000, 55.0000, 0.1600, 88.0000, 638.0000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `id` int(10) UNSIGNED NOT NULL,
  `receipt_no` varchar(50) NOT NULL,
  `receipt_date` date NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `amount` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `method` enum('cash','bank') NOT NULL DEFAULT 'cash',
  `status` enum('draft','posted','void') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoices`
--

CREATE TABLE `sales_invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `status` enum('draft','posted','void') NOT NULL DEFAULT 'draft',
  `uuid` varchar(64) DEFAULT NULL,
  `qr_payload` text DEFAULT NULL,
  `subtotal` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `vat_total` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `total` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_invoices`
--

INSERT INTO `sales_invoices` (`id`, `invoice_no`, `invoice_date`, `customer_id`, `status`, `uuid`, `qr_payload`, `subtotal`, `vat_total`, `total`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'SI-2026-00001', '2026-04-08', 1, 'draft', 'f241c9f1-7929-487c-9484-605469317896', '{\"seller_name\":\"اسم المنشأة لديك هنا\",\"seller_tax_no\":\"رقم الضريبة لديك هنا\",\"invoice_no\":\"SI-2026-00001\",\"invoice_date\":\"2026-04-08\",\"total\":232,\"vat_total\":32,\"uuid\":\"f241c9f1-7929-487c-9484-605469317896\"}', 200.0000, 32.0000, 232.0000, 'راتب', '2026-04-08 07:21:11', '2026-04-08 07:33:31');

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoice_lines`
--

CREATE TABLE `sales_invoice_lines` (
  `id` int(10) UNSIGNED NOT NULL,
  `sales_invoice_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(500) NOT NULL DEFAULT '',
  `qty` decimal(18,4) NOT NULL DEFAULT 1.0000,
  `unit_price` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `vat_rate` decimal(6,4) NOT NULL DEFAULT 0.1600,
  `vat_amount` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `line_total` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `line_no` smallint(6) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_invoice_lines`
--

INSERT INTO `sales_invoice_lines` (`id`, `sales_invoice_id`, `item_id`, `description`, `qty`, `unit_price`, `vat_rate`, `vat_amount`, `line_total`, `line_no`) VALUES
(1, 1, NULL, 'شاشة', 1.0000, 200.0000, 0.1600, 32.0000, 232.0000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_val` varchar(500) NOT NULL DEFAULT '',
  `note` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_val`, `note`, `updated_at`) VALUES
(1, 'vat_rate', '0.16', 'معدل ضريبة القيمة المضافة الافتراضي', '2026-04-07 12:14:05'),
(2, 'account_ar', '11', 'حساب ذمم العملاء (Accounts Receivable)', '2026-04-07 12:14:05'),
(3, 'account_ap', '12', 'حساب ذمم الموردين (Accounts Payable)', '2026-04-07 12:14:05'),
(4, 'account_sales', '7', 'حساب إيرادات المبيعات', '2026-04-07 12:14:05'),
(5, 'account_inventory', '14', 'حساب المخزون', '2026-04-07 12:14:05'),
(6, 'account_cogs', '15', 'حساب تكلفة البضاعة المباعة', '2026-04-07 12:14:05'),
(7, 'account_vat_output', '16', 'حساب ضريبة القيمة المضافة - مخرجات', '2026-04-07 12:14:05'),
(8, 'account_vat_input', '17', 'حساب ضريبة القيمة المضافة - مدخلات', '2026-04-07 12:14:05'),
(9, 'account_purchases', '18', 'حساب المشتريات', '2026-04-07 12:14:05'),
(10, 'account_cash', '2', 'حساب الصندوق (نقد)', '2026-04-07 12:14:05'),
(11, 'account_bank', '3', 'حساب البنك', '2026-04-07 12:14:05');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `tax_no` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `code`, `name`, `phone`, `email`, `address`, `tax_no`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'a1', 'محمد 1', '0799186062', NULL, NULL, NULL, 1, '2026-04-08 07:17:52', '2026-04-08 07:17:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `fk_accounts_parent` (`parent_id`),
  ADD KEY `idx_accounts_type` (`account_type`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_customer_code` (`code`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_im_item` (`item_id`),
  ADD KEY `idx_im_source` (`source_type`,`source_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_item_sku` (`sku`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entry_no` (`entry_no`),
  ADD KEY `idx_journal_entries_date` (`entry_date`),
  ADD KEY `idx_je_source` (`source_type`,`source_id`);

--
-- Indexes for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_journal_lines_entry` (`journal_entry_id`),
  ADD KEY `idx_journal_lines_account` (`account_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payment_no` (`payment_no`),
  ADD KEY `idx_payment_supplier` (`supplier_id`);

--
-- Indexes for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_purchase_invoice_no` (`invoice_no`),
  ADD KEY `idx_pi_supplier` (`supplier_id`);

--
-- Indexes for table `purchase_invoice_lines`
--
ALTER TABLE `purchase_invoice_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pil_invoice` (`purchase_invoice_id`),
  ADD KEY `idx_pil_item` (`item_id`);

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_receipt_no` (`receipt_no`),
  ADD KEY `idx_receipt_customer` (`customer_id`);

--
-- Indexes for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sales_invoice_no` (`invoice_no`),
  ADD UNIQUE KEY `idx_sales_invoices_uuid` (`uuid`),
  ADD KEY `idx_si_customer` (`customer_id`);

--
-- Indexes for table `sales_invoice_lines`
--
ALTER TABLE `sales_invoice_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sil_invoice` (`sales_invoice_id`),
  ADD KEY `idx_sil_item` (`item_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_setting_key` (`setting_key`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_supplier_code` (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_invoice_lines`
--
ALTER TABLE `purchase_invoice_lines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales_invoice_lines`
--
ALTER TABLE `sales_invoice_lines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD CONSTRAINT `fk_accounts_parent` FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `fk_im_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD CONSTRAINT `fk_journal_lines_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_journal_lines_entry` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD CONSTRAINT `fk_pi_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_invoice_lines`
--
ALTER TABLE `purchase_invoice_lines`
  ADD CONSTRAINT `fk_pil_invoice` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pil_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `receipts`
--
ALTER TABLE `receipts`
  ADD CONSTRAINT `fk_receipt_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD CONSTRAINT `fk_si_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `sales_invoice_lines`
--
ALTER TABLE `sales_invoice_lines`
  ADD CONSTRAINT `fk_sil_invoice` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sil_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
