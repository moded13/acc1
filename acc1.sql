-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 07, 2026 at 11:32 AM
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
(10, 'a1', 'محمد', NULL, 'asset', NULL, NULL, 0, 0.0000, 0.0000, 1, NULL, '2026-04-07 09:19:38', '2026-04-07 09:19:38');

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
  `branch_id` int(10) UNSIGNED DEFAULT NULL,
  `currency_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `journal_entries`
--

INSERT INTO `journal_entries` (`id`, `entry_no`, `entry_date`, `description`, `status`, `branch_id`, `currency_code`, `created_at`, `updated_at`) VALUES
(1, '2026-00001', '2026-04-07', 'قيد تجريبي تلقائي', 'posted', NULL, NULL, '2026-04-07 06:11:27', '2026-04-07 06:11:27'),
(2, '2026-00002', '2026-04-07', 'قيد تجريبي تلقائي', 'posted', NULL, NULL, '2026-04-07 07:11:02', '2026-04-07 07:11:02');

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
(4, 2, 4, 2, 0.0000, 100.0000, 'دائن تجريبي', '2026-04-07 07:11:02', '2026-04-07 07:11:02');

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
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entry_no` (`entry_no`),
  ADD KEY `idx_journal_entries_date` (`entry_date`);

--
-- Indexes for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_journal_lines_entry` (`journal_entry_id`),
  ADD KEY `idx_journal_lines_account` (`account_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD CONSTRAINT `fk_accounts_parent` FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD CONSTRAINT `fk_journal_lines_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_journal_lines_entry` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
