SET FOREIGN_KEY_CHECKS=0;
USE lorinims_db;
DROP TABLE IF EXISTS accounting_settings;
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS batch_details;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS delivery_assignments;
DROP TABLE IF EXISTS delivery_receipts;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS finished_goods;
DROP TABLE IF EXISTS goods_receiving_notes;
DROP TABLE IF EXISTS gps_tracking;
DROP TABLE IF EXISTS grn_items;
DROP TABLE IF EXISTS id_sequences;
DROP TABLE IF EXISTS inventory_transactions;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS invoice_items;
DROP TABLE IF EXISTS migrations;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS pagination_settings;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS payroll;
DROP TABLE IF EXISTS payroll_breakdown;
DROP TABLE IF EXISTS payroll_deductions;
DROP TABLE IF EXISTS payroll_settings;
DROP TABLE IF EXISTS po_items;
DROP TABLE IF EXISTS production_batches;
DROP TABLE IF EXISTS production_requests;
DROP TABLE IF EXISTS production_settings;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS raw_materials;
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 08, 2026 at 10:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lorinims_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounting_settings`
--

CREATE TABLE `accounting_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounting_settings`
--

INSERT INTO `accounting_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'vat_rate', '12', 'VAT rate percentage', '2026-03-01 15:17:27'),
(2, 'invoice_prefix', 'INV-2026-', 'Invoice prefix', '2026-03-01 15:17:27'),
(3, 'default_revenue_account', 'Sales Revenue', 'Default revenue account', '2026-03-01 15:17:27'),
(4, 'cutoff_day', '30', 'Monthly cut-off day', '2026-03-01 15:17:27');

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `created_at`) VALUES
(1, 3, 'create', 'supplier_delivery', 1, 'Delivery from supplier #2', '2026-02-05 06:04:12'),
(2, 6, 'cancel', 'order', 1, 'Order ORD-20260203-7029 cancelled', '2026-02-05 06:05:15'),
(3, 6, 'create', 'order', 4, 'Order ORD-20260205-0003 with 1 item(s)', '2026-02-05 06:16:16'),
(4, 6, 'create', 'order', 5, 'Order ORD-20260205-0004 with 1 item(s)', '2026-02-05 06:17:10'),
(5, 1, 'create', 'order', 6, 'Order ORD-20260205-0005 with 1 item(s)', '2026-02-05 06:26:52'),
(6, 6, 'create', 'order', 7, 'Order ORD-20260205-0006 with 1 item(s)', '2026-02-05 07:20:38'),
(7, 6, 'create', 'order', 8, 'Order ORD-20260205-0007 with 1 item(s)', '2026-02-05 07:23:23'),
(8, 1, 'create', 'production_batch', 2, 'Batch BAT-20260206-0001', '2026-02-06 11:56:28'),
(9, 1, 'create', 'production_batch', 3, 'Batch BAT-20260206-0002', '2026-02-06 12:23:48'),
(10, 1, 'create', 'production_batch', 4, 'Batch BAT-20260206-0003', '2026-02-06 12:27:37'),
(11, 1, 'create', 'production_batch', 5, 'Batch BAT-20260206-0004', '2026-02-06 12:28:12'),
(12, 1, 'create', 'production_batch', 6, 'Batch BAT-20260206-0005', '2026-02-06 12:43:22'),
(13, 1, 'create', 'production_batch', 7, 'Batch BAT-20260206-0006', '2026-02-06 13:12:32'),
(14, 1, 'create', 'production_batch', 8, 'Batch BAT-20260206-0007', '2026-02-06 14:39:00'),
(15, 1, 'create', 'production_batch', 9, 'Batch BAT-20260206-0008', '2026-02-06 14:40:08'),
(16, 1, 'create', 'order', 9, 'Order ORD-20260206-0001 with 1 item(s)', '2026-02-06 15:02:21'),
(17, 1, 'create', 'order', 10, 'Order ORD-20260206-0002 with 2 item(s)', '2026-02-06 15:06:00'),
(18, 1, 'create', 'order', 11, 'Order ORD-20260207-0001 with 1 item(s)', '2026-02-07 03:44:30'),
(19, 1, 'create', 'production_batch', 10, 'Batch BAT-20260215-0001', '2026-02-15 19:15:08'),
(20, 1, 'create', 'order', 12, 'Order ORD-20260215-0001 with 1 item(s)', '2026-02-15 20:04:53'),
(21, 1, 'create', 'order', 13, 'Order ORD-20260215-0002 with 1 item(s)', '2026-02-15 20:07:00'),
(22, 1, 'create', 'order', 14, 'Order ORD-20260215-0003 with 1 item(s)', '2026-02-15 20:07:57'),
(23, 1, 'create', 'order', 15, 'Order ORD-20260215-0004 with 1 item(s)', '2026-02-15 20:19:28'),
(24, 2, 'create', 'production_batch', 11, 'Batch BAT-20260216-0001', '2026-02-16 06:06:46'),
(25, 1, 'create', 'production_batch', 12, 'Batch BAT-20260216-0002', '2026-02-16 08:15:30'),
(26, 1, 'create', 'production_batch', 13, 'Batch BAT-20260216-0003', '2026-02-16 08:57:05'),
(27, 1, 'create', 'production_batch', 14, 'Batch BAT-20260216-0004', '2026-02-16 10:54:45'),
(28, 1, 'create', 'production_batch', 15, 'Batch BAT-20260216-0005', '2026-02-16 10:56:28'),
(29, 1, 'create', 'production_batch', 16, 'Batch BAT-20260216-0006', '2026-02-16 11:20:59'),
(30, 1, 'create', 'production_batch', 17, 'Batch BAT-20260216-0007', '2026-02-16 11:21:32'),
(31, 1, 'create', 'production_batch', 18, 'Batch BAT-20260216-0008', '2026-02-16 11:27:51'),
(32, 1, 'create', 'production_batch', 19, 'Batch BAT-20260216-0009', '2026-02-16 11:55:48'),
(33, 1, 'create', 'production_batch', 20, 'Batch BAT-20260226-0001', '2026-02-26 10:00:20'),
(34, 1, 'create', 'raw_material', 6, 'Material MAT-20260226-0001', '2026-02-26 10:01:55'),
(35, 1, 'create', 'production_batch', 21, 'Batch BAT-20260227-0001', '2026-02-27 10:45:50'),
(36, 1, 'create', 'production_batch', 22, 'Batch BAT-20260227-0002', '2026-02-27 10:56:06'),
(37, 1, 'create', 'production_batch', 23, 'Batch BAT-20260227-0003', '2026-02-27 10:57:20'),
(38, 1, 'create', 'production_batch', 24, 'Batch BAT-20260227-0004', '2026-02-27 11:00:13'),
(39, 1, 'create', 'production_batch', 25, 'Batch BAT-20260227-0005', '2026-02-27 11:01:06'),
(40, 1, 'create', 'production_batch', 26, 'Batch BAT-20260227-0006', '2026-02-27 11:01:29'),
(41, 1, 'create', 'production_batch', 27, 'Batch BAT-20260227-0007', '2026-02-27 11:02:33'),
(42, 1, 'create', 'production_batch', 28, 'Batch BAT-20260227-0008', '2026-02-27 11:06:07'),
(43, 1, 'create', 'production_batch', 29, 'Batch BAT-20260227-0009', '2026-02-27 11:09:52'),
(44, 1, 'create', 'production_batch', 30, 'Batch BAT-20260227-0010', '2026-02-27 11:11:41'),
(45, 1, 'create', 'production_batch', 31, 'Batch BAT-20260227-0011', '2026-02-27 11:12:13'),
(46, 1, 'create', 'production_batch', 32, 'Batch BAT-20260227-0012', '2026-02-27 11:14:16'),
(47, 1, 'create', 'production_batch', 33, 'Batch BAT-20260227-0013', '2026-02-27 11:30:20'),
(48, 1, 'create', 'production_batch', 34, 'Batch BAT-20260227-0014', '2026-02-27 11:30:47'),
(49, 1, 'create', 'production_batch', 35, 'Batch BAT-20260227-0015', '2026-02-27 11:31:16'),
(50, 1, 'create', 'production_batch', 36, 'Batch BAT-20260227-0016', '2026-02-27 11:31:53'),
(51, 1, 'create', 'production_batch', 37, 'Batch BAT-20260227-0017', '2026-02-27 11:34:14'),
(52, 1, 'create', 'production_batch', 38, 'Batch BAT-20260227-0018', '2026-02-27 11:34:14'),
(53, 1, 'create', 'production_batch', 39, 'Batch BAT-20260227-0019', '2026-02-27 11:35:32'),
(54, 1, 'create', 'production_batch', 40, 'Batch BAT-20260227-0020', '2026-02-27 12:02:18'),
(55, 1, 'create', 'production_batch', 41, 'Batch BAT-20260227-0021', '2026-02-27 12:02:38'),
(56, 1, 'create', 'production_batch', 42, 'Batch BAT-20260301-0001', '2026-03-01 09:49:41'),
(57, 1, 'create', 'order', 26, 'Order ORD-20260301-0009 with 2 item(s)', '2026-03-01 10:01:16'),
(58, 1, 'create', 'order', 27, 'Order ORD-20260301-0010 with 1 item(s)', '2026-03-01 10:01:49'),
(59, 1, 'create', 'order', 28, 'Order ORD-20260301-0011 with 1 item(s)', '2026-03-01 10:21:27'),
(60, 1, 'create', 'order', 29, 'Order ORD-20260301-0012 with 1 item(s)', '2026-03-01 10:21:36'),
(61, 1, 'create', 'order', 30, 'Order ORD-20260301-0013 with 1 item(s)', '2026-03-01 10:23:59'),
(62, 1, 'create', 'order', 31, 'Order ORD-20260301-0014 with 1 item(s)', '2026-03-01 10:30:06'),
(63, 1, 'create', 'order', 32, 'Order ORD-20260301-0015 with 1 item(s)', '2026-03-01 10:41:23'),
(64, 1, 'create', 'order', 33, 'Order ORD-20260301-0016 with 1 items', '2026-03-01 10:46:09'),
(65, 1, 'create', 'order', 34, 'Order ORD-20260301-0017 with 1 item(s)', '2026-03-01 10:47:42'),
(66, 1, 'create', 'order', 35, 'Order ORD-20260301-0018 with 1 item(s)', '2026-03-01 10:48:30'),
(67, 1, 'create', 'order', 36, 'Order ORD-20260301-0019 with 1 item(s)', '2026-03-01 10:48:40'),
(68, 1, 'create', 'order', 37, 'Order ORD-20260301-0020 with 1 item(s)', '2026-03-01 10:49:58'),
(69, 1, 'create', 'order', 38, 'Order ORD-20260301-0021 with 1 item(s)', '2026-03-01 10:51:57'),
(70, 1, 'create', 'order', 39, 'Order ORD-20260301-0022 with 1 item(s)', '2026-03-01 11:05:51'),
(71, 1, 'create', 'order', 40, 'Order ORD-20260301-0023 with 1 item(s)', '2026-03-01 11:06:09'),
(72, 1, 'create', 'order', 41, 'Order ORD-20260301-0024 with 1 item(s)', '2026-03-01 11:10:30'),
(73, 1, 'create', 'order', 42, 'Order ORD-20260301-0025 with 1 item(s)', '2026-03-01 11:14:06'),
(74, 1, 'create', 'order', 43, 'Order ORD-20260301-0026 with 1 item(s)', '2026-03-01 11:16:55'),
(75, 1, 'create', 'order', 44, 'Order ORD-20260301-0027 with 1 item(s)', '2026-03-01 11:18:07'),
(76, 1, 'create', 'order', 45, 'Order ORD-20260301-0028 with 1 item(s)', '2026-03-01 11:18:52'),
(77, 1, 'create', 'order', 46, 'Order ORD-20260301-0029 with 1 item(s)', '2026-03-01 11:20:42'),
(78, 1, 'create', 'order', 47, 'Order ORD-20260301-0030 with 1 item(s)', '2026-03-01 11:22:21'),
(79, 1, 'create', 'order', 48, 'Order PUP-20260301-0001 with 1 item(s)', '2026-03-01 11:26:17'),
(80, 1, 'create', 'order', 49, 'Order PUP-20260301-0002 with 1 item(s)', '2026-03-01 11:29:00'),
(81, 1, 'create', 'order', 50, 'Order PUP-20260301-0003 with 1 item(s)', '2026-03-01 11:29:17'),
(82, 1, 'create', 'order', 51, 'Order ORD-20260301-0031 with 1 item(s)', '2026-03-01 11:29:55'),
(83, 1, 'create', 'order', 52, 'Order PUP-20260301-0004 with 1 item(s)', '2026-03-01 11:37:25'),
(84, 1, 'create', 'order', 53, 'Order PUP-20260301-0005 with 1 item(s)', '2026-03-01 11:41:55'),
(85, 1, 'create', 'order', 54, 'Order PUP-20260301-0006 with 1 item(s)', '2026-03-01 11:48:16'),
(86, 1, 'create', 'order', 55, 'Order PUP-20260301-0007 with 1 item(s)', '2026-03-01 11:53:49'),
(87, 1, 'create', 'order', 56, 'Order PUP-20260301-0008 with 1 item(s)', '2026-03-01 12:04:34'),
(88, 1, 'create', 'order', 57, 'Order PUP-20260301-0009 with 1 item(s)', '2026-03-01 12:18:39'),
(89, 1, 'create', 'order', 58, 'Order PUP-20260301-0010 with 1 item(s)', '2026-03-01 12:20:10'),
(90, 1, 'create', 'order', 59, 'Order PUP-20260301-0011 with 1 item(s)', '2026-03-01 12:22:34'),
(91, 1, 'create', 'order', 60, 'Order ORD-20260301-0032 with 1 item(s)', '2026-03-01 12:56:39'),
(92, 1, 'create', 'order', 61, 'Order ORD-20260301-0033 with 1 item(s)', '2026-03-01 13:09:27'),
(93, 1, 'create', 'production_batch', 43, 'Batch BAT-20260302-0001', '2026-03-02 04:46:56'),
(94, 1, 'create', 'raw_material', 8, 'Material MAT-20260302-0001', '2026-03-02 04:51:56'),
(95, 1, 'create', 'production_batch', 44, 'Batch BAT-20260302-0002', '2026-03-02 05:50:38'),
(96, 1, 'create', 'production_batch', 45, 'Batch BAT-20260302-0008', '2026-03-02 06:55:25'),
(97, 1, 'create', 'production_batch', 46, 'Batch BAT-20260302-0009', '2026-03-02 06:57:59'),
(98, 1, 'create', 'production_batch', 47, 'Batch BAT-20260302-0010', '2026-03-02 07:05:01'),
(99, 1, 'create', 'production_batch', 48, 'Batch BAT-20260302-0011', '2026-03-02 07:32:12'),
(100, 1, 'create', 'production_batch', 49, 'Batch BAT-20260302-0012', '2026-03-02 07:36:34'),
(101, 1, 'create', 'production_batch', 50, 'Batch BAT-20260302-0013', '2026-03-02 08:01:44'),
(102, 1, 'create', 'production_batch', 51, 'Batch BAT-20260302-0014', '2026-03-02 08:05:41'),
(103, 1, 'create', 'order', 65, 'Order ORD-20260304-0001 with 1 item(s)', '2026-03-04 09:32:43'),
(104, 1, 'create', 'production_batch', 52, 'Batch BAT-20260304-0001', '2026-03-04 11:10:35'),
(105, 1, 'create', 'production_batch', 53, 'Batch BAT-20260304-0002', '2026-03-04 12:47:41'),
(106, 1, 'create', 'production_batch', 54, 'Batch BAT-20260304-0003', '2026-03-04 12:48:15'),
(107, 1, 'create', 'production_batch', 55, 'Batch BAT-20260304-0004', '2026-03-04 13:05:24'),
(108, 1, 'create', 'production_batch', 56, 'Batch BAT-20260304-0005', '2026-03-04 13:26:52'),
(109, 1, 'create', 'production_batch', 57, 'Batch BAT-20260304-0006', '2026-03-04 13:26:52'),
(110, 1, 'create', 'order', 70, 'Order ORD-20260304-0006 with 1 item(s)', '2026-03-04 14:25:42'),
(111, 1, 'create', 'order', 71, 'Order ORD-20260304-0007 with 1 item(s)', '2026-03-04 14:44:47'),
(112, 1, 'create', 'production_batch', 58, 'Batch BAT-20260307-0001', '2026-03-07 02:39:27'),
(113, 1, 'create', 'production_batch', 59, 'Batch BAT-20260308-0001', '2026-03-08 06:37:19'),
(114, 1, 'create', 'production_batch', 60, 'Batch BAT-20260308-0002', '2026-03-08 06:56:33'),
(115, 1, 'create', 'production_batch', 61, 'Batch BAT-20260308-0003', '2026-03-08 06:58:58');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `hours_worked` decimal(4,2) DEFAULT 0.00,
  `status` enum('Present','Absent','Late','Half Day','Leave') DEFAULT 'Present',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attendance_ref` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batch_details`
--

CREATE TABLE `batch_details` (
  `detail_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `material_id` int(11) DEFAULT NULL,
  `quantity_used` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch_details`
--

INSERT INTO `batch_details` (`detail_id`, `batch_id`, `material_id`, `quantity_used`, `created_at`) VALUES
(1, 25, 2, 10.00, '2026-02-27 11:01:06'),
(2, 26, 2, 10.00, '2026-02-27 11:01:29'),
(3, 27, 3, 12.00, '2026-02-27 11:02:33'),
(4, 28, 2, 12.00, '2026-02-27 11:06:07'),
(5, 29, 3, 34.00, '2026-02-27 11:09:52'),
(6, 30, 3, 34.00, '2026-02-27 11:11:41'),
(7, 31, 3, 12.00, '2026-02-27 11:12:13'),
(8, 32, 3, 12.00, '2026-02-27 11:14:16'),
(9, 33, 1, 12.00, '2026-02-27 11:30:20'),
(10, 34, 1, 12.00, '2026-02-27 11:30:47'),
(11, 35, 3, 121.00, '2026-02-27 11:31:16'),
(12, 36, 1, 432.00, '2026-02-27 11:31:53'),
(13, 37, 6, 12.00, '2026-02-27 11:34:14'),
(14, 39, 3, 12.00, '2026-02-27 11:35:32'),
(15, 40, 1, 12.00, '2026-02-27 12:02:18'),
(16, 41, 2, 12.00, '2026-02-27 12:02:38'),
(17, 42, 2, 100.00, '2026-03-01 09:49:41'),
(18, 42, 1, 4.00, '2026-03-01 09:49:41'),
(19, 43, 3, 1.00, '2026-03-02 04:46:56'),
(20, 44, 2, 1.00, '2026-03-02 05:50:38'),
(21, 44, 1, 1.00, '2026-03-02 05:50:38'),
(22, 44, 3, 1.00, '2026-03-02 05:50:38'),
(23, 45, 6, 1.00, '2026-03-02 06:55:25'),
(24, 46, 1, 1.00, '2026-03-02 06:57:59'),
(25, 47, 6, 1.00, '2026-03-02 07:05:01'),
(26, 49, 32, 1.00, '2026-03-02 07:36:34'),
(27, 50, 27, 1.00, '2026-03-02 08:01:44'),
(28, 51, 30, 1.00, '2026-03-02 08:05:41'),
(29, 52, 15, 12.00, '2026-03-04 11:10:35'),
(30, 53, 26, 1.00, '2026-03-04 12:47:41'),
(31, 54, 30, 1.00, '2026-03-04 12:48:15'),
(32, 55, 12, 1.00, '2026-03-04 13:05:24'),
(33, 56, 30, 1.00, '2026-03-04 13:26:52'),
(34, 58, 6, 1.00, '2026-03-07 02:39:27'),
(35, 59, 27, 1.00, '2026-03-08 06:37:19'),
(36, 60, 27, 1.00, '2026-03-08 06:56:33'),
(37, 61, 30, 1.00, '2026-03-08 06:58:58');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`, `contact_person`, `contact_number`, `email`, `address`, `created_at`, `updated_at`, `customer_code`) VALUES
(12, 'test customer1', NULL, '09234234234', NULL, 'test address', '2026-02-26 10:42:52', '2026-02-26 10:42:52', NULL),
(13, 'test2', NULL, '0934343443', NULL, 'test add', '2026-03-01 11:16:28', '2026-03-01 11:16:28', NULL),
(14, 'test reserve', NULL, '09121212121212', NULL, 'testinbg', '2026-03-04 11:08:07', '2026-03-04 11:08:07', NULL),
(15, 'maam c', NULL, '06767676', NULL, 'taszrrd', '2026-03-07 02:35:56', '2026-03-07 02:35:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `delivery_assignments`
--

CREATE TABLE `delivery_assignments` (
  `assignment_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `vehicle_info` varchar(100) DEFAULT NULL,
  `dispatch_time` datetime DEFAULT NULL,
  `status` enum('Pending','Dispatched','On the Way','Arrived','Delivered','Failed') DEFAULT 'Pending',
  `proof_of_delivery` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assignment_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_assignments`
--

INSERT INTO `delivery_assignments` (`assignment_id`, `order_id`, `driver_id`, `vehicle_info`, `dispatch_time`, `status`, `proof_of_delivery`, `created_at`, `assignment_number`) VALUES
(14, 17, 9, 'sdfg', '2026-02-27 12:36:00', 'Delivered', NULL, '2026-02-27 11:36:31', 'DEL-20260227-0001'),
(15, 26, 9, NULL, '2026-03-01 18:01:16', 'Delivered', NULL, '2026-03-01 10:01:16', NULL),
(16, 28, 9, NULL, '2026-03-01 18:21:27', 'Delivered', NULL, '2026-03-01 10:21:27', NULL),
(17, 34, 9, '', '2026-03-04 06:08:00', 'Delivered', NULL, '2026-03-01 10:47:42', NULL),
(18, 35, 9, NULL, '2026-03-01 18:48:30', 'Delivered', NULL, '2026-03-01 10:48:30', NULL),
(19, 36, 9, NULL, '2026-03-01 18:48:40', 'Delivered', NULL, '2026-03-01 10:48:40', NULL),
(20, 37, 9, NULL, '2026-03-01 18:49:58', 'Failed', NULL, '2026-03-01 10:49:58', NULL),
(21, 38, 7, NULL, '2026-03-01 18:51:57', 'Failed', NULL, '2026-03-01 10:51:57', NULL),
(22, 46, 9, NULL, '2026-03-01 19:20:42', 'Delivered', NULL, '2026-03-01 11:20:42', NULL),
(23, 51, 7, NULL, '2026-03-01 19:29:55', 'Failed', NULL, '2026-03-01 11:29:55', NULL),
(24, 60, 7, '', '2026-03-01 14:09:00', 'Delivered', NULL, '2026-03-01 12:56:39', NULL),
(25, 61, 7, NULL, '2026-03-01 21:09:27', 'Delivered', NULL, '2026-03-01 13:09:27', NULL),
(26, 65, 9, '', '2026-03-04 10:32:00', 'Delivered', NULL, '2026-03-04 09:32:43', NULL),
(27, 67, 9, '', '2026-03-04 14:08:00', 'Delivered', NULL, '2026-03-04 13:08:27', 'DEL-20260304-0001'),
(28, 66, 9, 'motor', '2026-03-04 15:15:00', 'Delivered', NULL, '2026-03-04 14:15:27', 'DEL-20260304-0002'),
(29, 70, 7, '', '2026-03-04 15:25:00', 'Failed', NULL, '2026-03-04 14:25:42', NULL),
(30, 71, 9, '', '2026-03-04 15:45:00', 'Delivered', NULL, '2026-03-04 14:44:47', NULL),
(31, 72, 9, '', '2026-03-07 03:41:00', 'Delivered', NULL, '2026-03-07 02:43:17', 'DEL-20260307-0001'),
(32, 68, 9, '', '2026-03-08 08:03:00', 'Delivered', NULL, '2026-03-08 07:05:08', 'DEL-20260308-0001'),
(33, 20, 7, '', '2026-03-08 08:41:00', 'Delivered', NULL, '2026-03-08 07:41:05', 'DEL-20260308-0002'),
(34, 23, 7, '', '2026-03-08 08:41:00', 'Delivered', NULL, '2026-03-08 07:41:19', 'DEL-20260308-0003'),
(35, 19, 9, '', '2026-03-08 08:44:00', 'Delivered', NULL, '2026-03-08 07:43:08', 'DEL-20260308-0004'),
(36, 22, 7, '', '2026-03-08 08:43:00', 'Pending', NULL, '2026-03-08 07:43:17', 'DEL-20260308-0005'),
(37, 39, 9, '', '2026-03-08 08:44:00', 'Delivered', NULL, '2026-03-08 07:43:27', 'DEL-20260308-0006'),
(38, 25, 9, '', '2026-03-08 08:44:00', 'Delivered', NULL, '2026-03-08 07:45:04', 'DEL-20260308-0007'),
(39, 63, 7, 'Truck', '2026-03-08 08:47:00', 'Pending', NULL, '2026-03-08 07:47:33', 'DEL-20260308-0008'),
(40, 21, 7, 'Truck', '2026-03-08 08:47:00', 'Pending', NULL, '2026-03-08 07:47:44', 'DEL-20260308-0009'),
(41, 69, 7, 'Truck', '2026-03-08 08:47:00', 'Pending', NULL, '2026-03-08 07:47:55', 'DEL-20260308-0010');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_receipts`
--

CREATE TABLE `delivery_receipts` (
  `dr_id` int(11) NOT NULL,
  `dr_number` varchar(50) NOT NULL,
  `order_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `delivery_date` date NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `vehicle_info` varchar(100) DEFAULT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_receipts`
--

INSERT INTO `delivery_receipts` (`dr_id`, `dr_number`, `order_id`, `invoice_id`, `delivery_date`, `driver_id`, `vehicle_info`, `received_by`, `notes`, `created_by`, `created_at`) VALUES
(1, 'DR-20260227-0001', 17, 5, '2026-02-27', NULL, NULL, NULL, NULL, 1, '2026-02-27 11:38:22');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `employee_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT 0.00,
  `status` enum('Active','Inactive','Terminated') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sss_enabled` tinyint(1) DEFAULT 1,
  `philhealth_enabled` tinyint(1) DEFAULT 1,
  `pagibig_enabled` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `user_id`, `employee_number`, `first_name`, `last_name`, `middle_name`, `position`, `department`, `hire_date`, `salary`, `status`, `created_at`, `updated_at`, `sss_enabled`, `philhealth_enabled`, `pagibig_enabled`) VALUES
(1, NULL, 'EMP-20260205-0001', 'wear', 'waerasfs', 'sdfg', 'production operator', 'Production', '2026-02-05', 500.00, 'Terminated', '2026-02-05 06:20:52', '2026-03-07 08:34:57', 1, 1, 1),
(2, NULL, 'EMP-20260205-0002', 'lasd', 'me', 'ASFD', 'motor rider', 'Logistics', '2026-02-05', 600.00, 'Active', '2026-02-05 06:24:41', '2026-03-07 08:35:17', 1, 1, 1),
(3, NULL, 'EMP-20260307-0001', 'sdffd', 'fgfg', 'ghfg', 'production operator', 'Production', '2026-03-07', 500.00, 'Active', '2026-03-07 02:48:14', '2026-03-07 08:35:18', 1, 1, 1),
(4, NULL, 'EMP-20260307-0002', 'sdf', 'sdfgsr', 'fdgdf', 'production operator', '', '2026-03-07', 1000.00, 'Active', '2026-03-07 02:58:00', '2026-03-07 08:35:15', 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `category` enum('Raw Materials','Labor','Utilities','Transportation','Other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expense_ref` varchar(50) DEFAULT NULL,
  `supplier_invoice_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `category`, `amount`, `description`, `reference_type`, `reference_id`, `department`, `expense_date`, `created_by`, `created_at`, `expense_ref`, `supplier_invoice_id`) VALUES
(1, 'Transportation', 12729.00, 'for transfering goods', NULL, NULL, NULL, '2026-02-06', 1, '2026-02-06 15:14:45', 'EXP-20260206-0001', NULL),
(2, 'Raw Materials', 1000000.00, 'Auto: Procurement - Supplier Invoice #SI-                                        PO-20260226-0001-883410 - john', 'supplier_invoice', 1, 'Production / Operations', '2026-02-26', 1, '2026-02-26 10:15:57', 'EXP-20260226-0001', NULL),
(3, 'Raw Materials', 1010.00, 'Auto: Procurement - Supplier Invoice #SI-                                        PO-20260227-0001-839641 - test supplier', 'supplier_invoice', 3, NULL, '2026-02-28', 1, '2026-02-28 14:04:50', 'EXP-20260228-0001', NULL),
(4, 'Raw Materials', 529.00, 'Auto: Procurement - Supplier Invoice #SI-                                        PO-20260227-0001-839641 - test supplier', 'supplier_invoice', 2, NULL, '2026-02-28', 1, '2026-02-28 14:06:19', 'EXP-20260228-0002', NULL),
(5, 'Raw Materials', 40000.00, 'Auto: Procurement - Supplier Invoice #SI-20260304-0001 - mike', 'supplier_invoice', 5, 'Production / Operations', '2026-03-04', 1, '2026-03-04 11:57:27', 'EXP-20260304-0001', NULL),
(6, 'Other', 10000.00, 'for fuel cost', NULL, NULL, NULL, '2026-03-04', 1, '2026-03-04 14:09:54', 'EXP-20260304-0002', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `finished_goods`
--

CREATE TABLE `finished_goods` (
  `fg_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `expiry_date` date DEFAULT NULL,
  `warehouse_location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reserved_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `qc_approved` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finished_goods`
--

INSERT INTO `finished_goods` (`fg_id`, `product_id`, `quantity`, `expiry_date`, `warehouse_location`, `created_at`, `updated_at`, `reserved_quantity`, `qc_approved`) VALUES
(1, 11, 53.00, NULL, NULL, '2026-02-01 06:54:01', '2026-02-07 03:44:30', 43.00, 0),
(2, 17, 114.00, '2027-02-27', NULL, '2026-02-06 11:56:28', '2026-02-27 10:30:20', 38.00, 1),
(3, 14, 72.00, '2027-02-27', NULL, '2026-02-06 12:23:48', '2026-02-27 10:30:19', 35.00, 1),
(4, 34, 90.00, '2027-02-27', NULL, '2026-02-06 12:28:12', '2026-02-27 10:30:22', 12.00, 1),
(5, 36, 112.00, '2027-02-27', NULL, '2026-02-06 12:43:22', '2026-02-27 10:30:24', 0.00, 1),
(6, 39, 68.00, '2027-02-27', NULL, '2026-02-06 13:12:32', '2026-02-27 10:30:25', 21.00, 1),
(7, 35, 90.00, '2027-02-27', NULL, '2026-02-06 14:39:00', '2026-02-27 10:30:27', 0.00, 1),
(8, 37, 67.00, NULL, NULL, '2026-02-06 14:40:08', '2026-02-06 14:40:08', 0.00, 0),
(9, 37, 67.00, '2027-02-06', NULL, '2026-02-06 14:58:02', '2026-02-06 14:58:02', 0.00, 0),
(10, 39, 34.00, '2027-02-06', NULL, '2026-02-06 14:58:26', '2026-02-06 15:06:00', 21.00, 0),
(11, 36, 56.00, '2027-02-06', NULL, '2026-02-06 14:59:03', '2026-02-06 14:59:03', 0.00, 0),
(12, 37, 67.00, '2027-02-07', NULL, '2026-02-07 03:39:32', '2026-02-07 03:39:32', 0.00, 0),
(13, 16, 324.00, '2027-03-02', NULL, '2026-02-16 08:15:30', '2026-03-02 04:49:58', 0.00, 1),
(14, 16, 234.00, '2027-02-16', NULL, '2026-02-16 08:50:37', '2026-02-16 08:50:37', 0.00, 0),
(15, 35, 45.00, '2027-02-16', NULL, '2026-02-16 08:50:48', '2026-02-16 08:50:48', 0.00, 0),
(16, 39, 34.00, '2027-02-06', NULL, '2026-02-16 08:51:21', '2026-02-16 08:51:21', 0.00, 0),
(17, 34, 45.00, '2027-02-06', NULL, '2026-02-16 08:56:22', '2026-02-16 08:56:22', 0.00, 0),
(18, 12, 46.00, '2027-02-26', NULL, '2026-02-16 08:57:05', '2026-02-26 10:01:11', 0.00, 1),
(19, 12, 34.00, '2027-02-16', NULL, '2026-02-16 08:57:48', '2026-02-16 08:57:48', 0.00, 0),
(20, 12, 34.00, '2027-02-16', NULL, '2026-02-16 09:18:04', '2026-02-16 09:18:04', 0.00, 0),
(21, 17, 23.00, '2027-02-06', NULL, '2026-02-16 09:18:24', '2026-02-16 09:18:24', 0.00, 0),
(22, 16, 234.00, '2027-02-16', NULL, '2026-02-16 09:57:00', '2026-02-16 09:57:00', 0.00, 0),
(23, 11, 65.00, '2027-02-01', NULL, '2026-02-16 09:57:27', '2026-02-16 09:57:27', 0.00, 0),
(24, 23, 78.00, NULL, NULL, '2026-02-16 10:54:45', '2026-02-16 10:54:45', 0.00, 0),
(25, 23, 78.00, '2027-02-16', NULL, '2026-02-16 10:55:24', '2026-02-16 10:55:24', 0.00, 0),
(26, 18, 20.00, NULL, NULL, '2026-02-16 10:56:28', '2026-02-16 10:56:28', 0.00, 0),
(27, 15, 113.00, NULL, NULL, '2026-02-16 11:20:59', '2026-02-16 11:20:59', 0.00, 0),
(28, 15, 113.00, '2027-02-16', NULL, '2026-02-16 11:21:09', '2026-02-16 11:21:09', 0.00, 0),
(29, 26, 2763.00, '2027-03-02', NULL, '2026-02-16 11:21:32', '2026-03-04 14:44:47', 763.00, 1),
(30, 19, 80.00, '2027-02-27', NULL, '2026-02-16 11:27:51', '2026-02-27 10:32:16', 0.00, 1),
(31, 21, 160.00, '2027-02-27', NULL, '2026-02-16 11:55:47', '2026-02-27 10:30:40', 0.00, 1),
(32, 42, 112.00, '2027-03-02', NULL, '2026-03-02 07:05:28', '2026-03-02 08:03:53', 0.00, 1),
(33, 20, 12334.00, '2027-03-02', NULL, '2026-03-02 08:05:55', '2026-03-02 08:05:55', 0.00, 1),
(34, 32, 100.00, '2028-03-04', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '2026-03-04 11:10:55', '2026-03-04 11:10:55', 0.00, 1),
(35, 41, 200.00, '2027-03-04', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '2026-03-04 13:07:16', '2026-03-04 13:28:34', 200.00, 1),
(36, 24, 100.00, '2027-03-04', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '2026-03-04 13:28:34', '2026-03-04 13:28:34', 100.00, 1),
(37, 1, 200.00, '2028-03-07', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '2026-03-07 02:40:55', '2026-03-07 02:40:55', 200.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `goods_receiving_notes`
--

CREATE TABLE `goods_receiving_notes` (
  `grn_id` int(11) NOT NULL,
  `grn_number` varchar(50) NOT NULL,
  `po_id` int(11) NOT NULL,
  `received_date` date NOT NULL,
  `received_by` int(11) NOT NULL,
  `qc_status` enum('Pending','Passed','Failed','Partial') DEFAULT 'Pending',
  `qc_checked_by` int(11) DEFAULT NULL,
  `qc_checked_at` timestamp NULL DEFAULT NULL,
  `qc_remarks` text DEFAULT NULL,
  `total_items_received` int(11) DEFAULT 0,
  `status` enum('Draft','Received','QC Passed','QC Failed','Partially Received') DEFAULT 'Draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `invoice_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goods_receiving_notes`
--

INSERT INTO `goods_receiving_notes` (`grn_id`, `grn_number`, `po_id`, `received_date`, `received_by`, `qc_status`, `qc_checked_by`, `qc_checked_at`, `qc_remarks`, `total_items_received`, `status`, `notes`, `created_at`, `updated_at`, `invoice_id`) VALUES
(1, 'GRN-20260226-0001', 1, '2026-02-26', 1, 'Pending', NULL, '2026-02-26 10:13:34', '', 1, 'Draft', '', '2026-02-26 10:13:34', '2026-02-26 10:13:34', NULL),
(2, 'GRN-20260227-0001', 2, '2026-02-27', 1, 'Pending', NULL, '2026-02-27 09:25:53', '', 1, 'Draft', '', '2026-02-27 09:25:53', '2026-03-04 11:57:51', 6),
(3, 'GRN-20260301-0001', 3, '2026-03-01', 1, 'Pending', NULL, '2026-03-01 13:30:14', '', 1, 'Draft', '', '2026-03-01 13:30:14', '2026-03-01 13:30:14', NULL),
(4, 'GRN-20260301-0002', 4, '2026-03-01', 1, 'Pending', NULL, '2026-03-01 13:41:40', '', 1, 'Draft', '', '2026-03-01 13:41:40', '2026-03-04 11:56:44', 5),
(5, 'GRN-20260304-0001', 5, '2026-03-04', 10, 'Pending', NULL, '2026-03-04 11:39:50', '', 1, 'Draft', '', '2026-03-04 11:39:50', '2026-03-04 11:39:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gps_tracking`
--

CREATE TABLE `gps_tracking` (
  `tracking_id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gps_tracking`
--

INSERT INTO `gps_tracking` (`tracking_id`, `assignment_id`, `latitude`, `longitude`, `timestamp`) VALUES
(30, 14, 14.04609125, 121.20186975, '2026-02-27 11:36:53'),
(31, 14, 14.04609125, 121.20186975, '2026-02-27 11:36:53'),
(32, 14, 14.04609125, 121.20186975, '2026-02-27 11:37:19'),
(33, 14, 14.04609125, 121.20186975, '2026-02-27 11:37:19'),
(34, 25, 14.04623420, 121.20193180, '2026-03-03 12:31:57'),
(35, 25, 14.04623060, 121.20193370, '2026-03-03 12:32:33'),
(36, 25, 14.04626755, 121.20182110, '2026-03-04 03:55:35'),
(37, 25, 14.04626755, 121.20182110, '2026-03-04 03:55:35'),
(38, 25, 14.04622171, 121.20183204, '2026-03-04 03:56:24'),
(39, 25, 14.04626755, 121.20182110, '2026-03-04 03:58:38'),
(40, 25, 14.04626755, 121.20182110, '2026-03-04 03:58:57'),
(41, 25, 14.04626755, 121.20182110, '2026-03-04 03:58:57'),
(42, 25, 14.04625156, 121.20182165, '2026-03-04 03:59:46'),
(43, 25, 14.04626002, 121.20182154, '2026-03-04 04:05:23'),
(44, 25, 14.04626002, 121.20182154, '2026-03-04 04:05:23'),
(45, 25, 14.04627030, 121.20182049, '2026-03-04 04:06:50'),
(46, 25, 14.04627030, 121.20182049, '2026-03-04 04:06:50'),
(47, 25, 14.04626610, 121.20182126, '2026-03-04 04:07:48'),
(48, 25, 14.04626610, 121.20182126, '2026-03-04 04:08:20'),
(49, 25, 14.04622950, 121.20193490, '2026-03-04 04:09:36'),
(50, 25, 14.04622950, 121.20193490, '2026-03-04 04:09:36'),
(51, 25, 14.04622950, 121.20193490, '2026-03-04 04:09:36'),
(52, 25, 14.04626755, 121.20182110, '2026-03-04 04:10:03'),
(53, 25, 14.04627000, 121.20195830, '2026-03-04 04:10:07'),
(54, 25, 14.04621000, 121.20193830, '2026-03-04 04:10:37'),
(55, 25, 14.04627204, 121.20182110, '2026-03-04 04:10:50'),
(56, 25, 14.04621000, 121.20193830, '2026-03-04 04:11:07'),
(57, 25, 14.04621570, 121.20192850, '2026-03-04 04:12:06'),
(58, 25, 14.04621570, 121.20192850, '2026-03-04 04:12:06'),
(59, 25, 14.04621570, 121.20192850, '2026-03-04 04:12:06'),
(60, 25, 14.04621670, 121.20192830, '2026-03-04 04:12:37'),
(61, 25, 14.04620500, 121.20197330, '2026-03-04 04:13:07'),
(62, 25, 14.04626746, 121.20182338, '2026-03-04 04:13:32'),
(63, 25, 14.04620500, 121.20197330, '2026-03-04 04:13:38'),
(64, 25, 14.04620500, 121.20197330, '2026-03-04 04:14:09'),
(65, 25, 14.04626636, 121.20182076, '2026-03-04 04:14:13'),
(66, 25, 14.04624500, 121.20194170, '2026-03-04 04:14:40'),
(67, 25, 14.04626755, 121.20182110, '2026-03-04 04:14:44'),
(68, 25, 14.04624500, 121.20194170, '2026-03-04 04:15:10'),
(69, 25, 14.04624500, 121.20194170, '2026-03-04 04:15:10'),
(70, 25, 14.04624500, 121.20194170, '2026-03-04 04:15:41'),
(71, 25, 14.04624500, 121.20194170, '2026-03-04 04:16:12'),
(72, 25, 14.04624500, 121.20194170, '2026-03-04 04:16:42'),
(73, 25, 14.04624500, 121.20194170, '2026-03-04 04:17:12'),
(74, 25, 14.04624500, 121.20194170, '2026-03-04 04:17:43'),
(75, 25, 14.04624670, 121.20195330, '2026-03-04 04:18:14'),
(76, 25, 14.04624670, 121.20195330, '2026-03-04 04:18:45'),
(77, 25, 14.04627805, 121.20181783, '2026-03-04 04:19:21'),
(78, 25, 14.04627805, 121.20181783, '2026-03-04 04:19:23'),
(79, 25, 14.04627805, 121.20181783, '2026-03-04 04:19:23'),
(80, 25, 14.04624670, 121.20195330, '2026-03-04 04:19:42'),
(81, 25, 14.04624670, 121.20195330, '2026-03-04 04:19:42'),
(82, 25, 14.04624670, 121.20195330, '2026-03-04 04:19:42'),
(83, 25, 14.04616467, 121.20184703, '2026-03-04 04:19:57'),
(84, 25, 14.04624670, 121.20193170, '2026-03-04 04:20:12'),
(85, 25, 14.04621170, 121.20189830, '2026-03-04 04:20:28'),
(86, 25, 14.04621170, 121.20189830, '2026-03-04 04:20:28'),
(87, 25, 14.04626000, 121.20190540, '2026-03-04 04:20:34'),
(88, 25, 14.04626000, 121.20190540, '2026-03-04 04:20:34'),
(89, 25, 14.04623672, 121.20182969, '2026-03-04 04:20:41'),
(90, 25, 14.04623672, 121.20182969, '2026-03-04 04:20:41'),
(91, 25, 14.04623672, 121.20182969, '2026-03-04 04:20:43'),
(92, 25, 14.04623672, 121.20182969, '2026-03-04 04:20:43'),
(93, 25, 14.04626500, 121.20190500, '2026-03-04 04:21:04'),
(94, 25, 14.04622330, 121.20192670, '2026-03-04 04:21:34'),
(95, 25, 14.04622330, 121.20192670, '2026-03-04 04:22:04'),
(96, 25, 14.04622330, 121.20192670, '2026-03-04 04:22:16'),
(97, 25, 14.04622330, 121.20192670, '2026-03-04 04:22:16'),
(98, 25, 14.04622330, 121.20192670, '2026-03-04 04:22:16'),
(99, 25, 14.04622330, 121.20192670, '2026-03-04 04:22:18'),
(100, 25, 14.04622330, 121.20192670, '2026-03-04 04:22:18'),
(101, 25, 14.04622330, 121.20192670, '2026-03-04 04:22:18'),
(102, 25, 14.04620700, 121.20194020, '2026-03-04 04:22:30'),
(103, 25, 14.04620700, 121.20194020, '2026-03-04 04:22:30'),
(104, 25, 14.04626330, 121.20193830, '2026-03-04 04:23:01'),
(105, 25, 14.04622290, 121.20194200, '2026-03-04 04:25:56'),
(106, 25, 14.04622290, 121.20194200, '2026-03-04 04:25:56'),
(107, 25, 14.04622290, 121.20194200, '2026-03-04 04:25:56'),
(108, 25, 14.04623170, 121.20193670, '2026-03-04 04:26:27'),
(109, 25, 14.04620330, 121.20193000, '2026-03-04 04:26:57'),
(110, 25, 14.04623120, 121.20194360, '2026-03-04 04:34:00'),
(111, 25, 14.04623120, 121.20194360, '2026-03-04 04:34:00'),
(112, 25, 14.04621170, 121.20187840, '2026-03-04 04:34:30'),
(113, 25, 14.04621170, 121.20187840, '2026-03-04 04:34:30'),
(114, 25, 14.04621170, 121.20187840, '2026-03-04 04:34:30'),
(115, 25, 14.04622710, 121.20194230, '2026-03-04 04:38:13'),
(116, 25, 14.04622710, 121.20194230, '2026-03-04 04:38:13'),
(117, 25, 14.04622710, 121.20194230, '2026-03-04 04:38:13'),
(118, 25, 14.04623300, 121.20189480, '2026-03-04 04:38:43'),
(119, 25, 14.04624150, 121.20188320, '2026-03-04 04:38:57'),
(120, 25, 14.04624150, 121.20188320, '2026-03-04 04:38:57'),
(121, 25, 14.04624120, 121.20188380, '2026-03-04 04:38:58'),
(122, 25, 14.04622340, 121.20193910, '2026-03-04 04:49:13'),
(123, 25, 14.04622340, 121.20193910, '2026-03-04 04:49:13'),
(124, 25, 14.04622340, 121.20193910, '2026-03-04 04:49:13'),
(125, 25, 14.04621340, 121.20191190, '2026-03-04 04:49:44'),
(126, 25, 14.04620000, 121.20190330, '2026-03-04 04:50:14'),
(127, 25, 14.04619650, 121.20188870, '2026-03-04 04:50:44'),
(128, 25, 14.04619670, 121.20186670, '2026-03-04 04:51:14'),
(129, 25, 14.04622560, 121.20193680, '2026-03-04 04:54:32'),
(130, 24, 14.04622560, 121.20193680, '2026-03-04 04:55:02'),
(131, 24, 14.04622560, 121.20193680, '2026-03-04 04:55:02'),
(132, 24, 14.04622560, 121.20193680, '2026-03-04 04:55:02'),
(133, 23, 14.04622520, 121.20193910, '2026-03-04 04:55:09'),
(134, 23, 14.04622520, 121.20193910, '2026-03-04 04:55:09'),
(135, 22, 14.04619000, 121.20192170, '2026-03-04 05:08:45'),
(136, 22, 14.04619000, 121.20192170, '2026-03-04 05:08:45'),
(137, 22, 14.04619000, 121.20192170, '2026-03-04 05:08:45'),
(138, 22, 14.04623870, 121.20194830, '2026-03-04 05:09:25'),
(139, 22, 14.04636420, 121.20184470, '2026-03-04 05:09:25'),
(140, 22, 14.04636330, 121.20185180, '2026-03-04 05:09:27'),
(141, 22, 14.04636330, 121.20185180, '2026-03-04 05:09:27'),
(142, 22, 14.04629500, 121.20188870, '2026-03-04 05:13:01'),
(143, 22, 14.04629500, 121.20188870, '2026-03-04 05:13:01'),
(144, 22, 14.04629500, 121.20188870, '2026-03-04 05:13:01'),
(145, 22, 14.04622500, 121.20194250, '2026-03-04 05:15:24'),
(146, 22, 14.04622500, 121.20194250, '2026-03-04 05:15:24'),
(147, 22, 14.04622500, 121.20194250, '2026-03-04 05:15:24'),
(148, 22, 14.04621700, 121.20193710, '2026-03-04 05:16:30'),
(149, 22, 14.02781340, 121.20524680, '2026-03-04 05:17:27'),
(150, 22, 14.02781340, 121.20524680, '2026-03-04 05:17:27'),
(151, 22, 14.02781340, 121.20524680, '2026-03-04 05:17:27'),
(152, 22, 14.04626746, 121.20182338, '2026-03-04 05:36:01'),
(153, 22, 14.04626746, 121.20182338, '2026-03-04 05:36:02'),
(154, 22, 14.04623168, 121.20183490, '2026-03-04 05:36:29'),
(155, 22, 14.04623168, 121.20183490, '2026-03-04 05:36:29'),
(156, 22, 14.04627380, 121.20201510, '2026-03-04 05:37:50'),
(157, 22, 14.04627380, 121.20201510, '2026-03-04 05:37:50'),
(158, 22, 14.04627380, 121.20201510, '2026-03-04 05:37:50'),
(159, 26, 14.04626755, 121.20182110, '2026-03-04 09:33:06'),
(160, 26, 14.04626755, 121.20182110, '2026-03-04 09:33:06'),
(161, 22, 14.04626755, 121.20182110, '2026-03-04 09:33:31'),
(162, 22, 14.04626755, 121.20182110, '2026-03-04 09:33:32'),
(163, 20, 14.04626755, 121.20182110, '2026-03-04 09:33:47'),
(164, 20, 14.04626755, 121.20182110, '2026-03-04 09:33:47'),
(165, 19, 14.04626755, 121.20182110, '2026-03-04 09:33:59'),
(166, 19, 14.04626755, 121.20182110, '2026-03-04 09:33:59'),
(167, 18, 14.04626755, 121.20182110, '2026-03-04 09:34:07'),
(168, 18, 14.04626755, 121.20182110, '2026-03-04 09:34:07'),
(169, 17, 14.04627257, 121.20182252, '2026-03-04 09:34:14'),
(170, 17, 14.04627257, 121.20182252, '2026-03-04 09:34:14'),
(171, 16, 14.04626636, 121.20182076, '2026-03-04 09:34:21'),
(172, 16, 14.04626636, 121.20182076, '2026-03-04 09:34:21'),
(173, 15, 14.04626636, 121.20182076, '2026-03-04 09:34:26'),
(174, 15, 14.04626636, 121.20182076, '2026-03-04 09:34:26'),
(175, 27, 14.04628643, 121.20181575, '2026-03-04 13:08:43'),
(176, 27, 14.04628643, 121.20181575, '2026-03-04 13:08:43'),
(177, 28, 14.04626293, 121.20181979, '2026-03-04 14:15:38'),
(178, 28, 14.04626293, 121.20181979, '2026-03-04 14:15:38'),
(179, 28, 14.04626245, 121.20181724, '2026-03-04 14:16:16'),
(180, 28, 14.04626903, 121.20182019, '2026-03-04 14:23:46'),
(181, 28, 14.04626903, 121.20182019, '2026-03-04 14:23:46'),
(182, 29, 14.04626575, 121.20181862, '2026-03-04 14:26:23'),
(183, 29, 14.04626575, 121.20181862, '2026-03-04 14:26:23'),
(184, 29, 14.04633021, 121.20180612, '2026-03-04 14:27:00'),
(185, 29, 14.04633021, 121.20180612, '2026-03-04 14:27:00'),
(186, 29, 14.04633021, 121.20180612, '2026-03-04 14:27:13'),
(187, 29, 14.04633021, 121.20180612, '2026-03-04 14:27:14'),
(188, 29, 14.04626755, 121.20182110, '2026-03-04 14:28:15'),
(189, 29, 14.04626755, 121.20182110, '2026-03-04 14:28:15'),
(190, 29, 14.04626774, 121.20182005, '2026-03-04 14:43:19'),
(191, 29, 14.04626774, 121.20182005, '2026-03-04 14:43:19'),
(192, 29, 14.04626774, 121.20182005, '2026-03-04 14:43:22'),
(193, 29, 14.04626774, 121.20182005, '2026-03-04 14:43:22'),
(194, 29, 14.04625424, 121.20181922, '2026-03-04 14:45:35'),
(195, 29, 14.04625424, 121.20181922, '2026-03-04 14:45:35'),
(196, 30, 14.04625424, 121.20181922, '2026-03-04 14:45:55'),
(197, 30, 14.04625424, 121.20181922, '2026-03-04 14:45:55'),
(198, 30, 14.04626774, 121.20182005, '2026-03-04 14:49:44'),
(199, 30, 14.04626774, 121.20182005, '2026-03-04 14:49:44'),
(200, 30, 14.04626774, 121.20182005, '2026-03-04 14:49:51'),
(201, 30, 14.04626774, 121.20182005, '2026-03-04 14:49:51'),
(202, 30, 14.04626774, 121.20182005, '2026-03-04 14:50:57'),
(203, 30, 14.04626774, 121.20182005, '2026-03-04 14:50:57'),
(204, 29, 14.04622990, 121.20193560, '2026-03-05 15:31:37'),
(205, 29, 14.04622990, 121.20193560, '2026-03-05 15:31:37'),
(206, 29, 14.04622990, 121.20193560, '2026-03-05 15:31:37'),
(207, 30, 14.04630820, 121.20187620, '2026-03-05 15:32:16'),
(208, 30, 14.04630820, 121.20187620, '2026-03-05 15:32:16'),
(209, 30, 14.04630820, 121.20187620, '2026-03-05 15:32:16'),
(210, 30, 14.04626170, 121.20198170, '2026-03-05 15:32:47'),
(211, 30, 14.04626170, 121.20198170, '2026-03-05 15:32:47'),
(212, 30, 14.04625170, 121.20197330, '2026-03-05 15:33:18'),
(213, 30, 14.04625963, 121.20182112, '2026-03-05 15:34:32'),
(214, 30, 14.04625963, 121.20182112, '2026-03-05 15:34:32'),
(215, 30, 14.08410280, 121.19085180, '2026-03-05 15:40:54'),
(216, 30, 14.08410280, 121.19085180, '2026-03-05 15:40:54'),
(217, 30, 14.08409600, 121.19085160, '2026-03-05 15:41:34'),
(218, 30, 14.08409600, 121.19085160, '2026-03-05 15:41:34'),
(219, 30, 14.08409600, 121.19085160, '2026-03-05 15:41:35'),
(220, 30, 14.08409280, 121.19085180, '2026-03-05 15:42:05'),
(221, 30, 14.04625170, 121.20197330, '2026-03-05 15:42:11'),
(222, 30, 14.04625170, 121.20197330, '2026-03-05 15:42:11'),
(223, 30, 14.04625170, 121.20197330, '2026-03-05 15:42:11'),
(224, 30, 14.08405410, 121.19083360, '2026-03-05 15:42:36'),
(225, 30, 14.08404710, 121.19083940, '2026-03-05 15:42:37'),
(226, 30, 14.08397730, 121.19089680, '2026-03-05 15:43:07'),
(227, 30, 14.08413030, 121.19083160, '2026-03-05 15:43:37'),
(228, 30, 14.08401360, 121.19087410, '2026-03-05 15:44:07'),
(229, 30, 14.08401200, 121.19098960, '2026-03-05 15:44:37'),
(230, 30, 14.08413290, 121.19090160, '2026-03-05 15:45:08'),
(231, 30, 14.08410400, 121.19085020, '2026-03-05 15:46:21'),
(232, 30, 14.09103140, 121.14436920, '2026-03-07 02:28:38'),
(233, 30, 14.09103140, 121.14436920, '2026-03-07 02:28:38'),
(234, 30, 14.09103140, 121.14436920, '2026-03-07 02:28:38'),
(235, 30, 14.09155820, 121.14339200, '2026-03-07 02:29:29'),
(236, 30, 14.09158500, 121.14332670, '2026-03-07 02:30:00'),
(237, 30, 14.09156470, 121.14333250, '2026-03-07 02:30:31'),
(238, 30, 14.09156630, 121.14336780, '2026-03-07 02:31:01'),
(239, 30, 14.09146350, 121.14353150, '2026-03-07 02:42:11'),
(240, 30, 14.09146350, 121.14353150, '2026-03-07 02:42:11'),
(241, 30, 14.09146350, 121.14353150, '2026-03-07 02:42:11'),
(242, 30, 14.09152790, 121.14367500, '2026-03-07 02:42:39'),
(243, 30, 14.09152790, 121.14367500, '2026-03-07 02:42:39'),
(244, 28, 14.09145630, 121.14354310, '2026-03-07 02:42:51'),
(245, 28, 14.09145630, 121.14354310, '2026-03-07 02:42:51'),
(246, 31, 14.09156650, 121.14366160, '2026-03-07 02:43:25'),
(247, 31, 14.09156650, 121.14366160, '2026-03-07 02:43:25'),
(248, 31, 14.09159670, 121.14364010, '2026-03-07 02:43:59'),
(249, 31, 14.09159610, 121.14364080, '2026-03-07 02:44:05'),
(250, 31, 14.09159690, 121.14364170, '2026-03-07 02:44:05'),
(251, 32, 14.04636751, 121.20180135, '2026-03-08 07:05:22'),
(252, 32, 14.04636751, 121.20180135, '2026-03-08 07:05:22'),
(253, 29, 14.04623509, 121.20183043, '2026-03-08 07:34:57'),
(254, 29, 14.04623509, 121.20183043, '2026-03-08 07:34:57'),
(255, 34, 14.04623604, 121.20182618, '2026-03-08 07:41:40'),
(256, 34, 14.04623604, 121.20182618, '2026-03-08 07:41:40'),
(257, 34, 14.04614402, 121.20185174, '2026-03-08 07:42:13'),
(258, 33, 14.04614402, 121.20185174, '2026-03-08 07:42:17'),
(259, 33, 14.04614402, 121.20185174, '2026-03-08 07:42:17'),
(260, 38, 14.04637690, 121.20179845, '2026-03-08 07:45:14'),
(261, 38, 14.04637690, 121.20179845, '2026-03-08 07:45:14'),
(262, 37, 14.04637240, 121.20179671, '2026-03-08 07:45:30'),
(263, 37, 14.04637240, 121.20179671, '2026-03-08 07:45:30'),
(264, 35, 14.04637240, 121.20179671, '2026-03-08 07:45:59'),
(265, 35, 14.04637240, 121.20179671, '2026-03-08 07:45:59'),
(266, 41, 14.04624551, 121.20182738, '2026-03-08 07:48:12'),
(267, 41, 14.04624551, 121.20182738, '2026-03-08 07:48:12'),
(268, 41, 14.04623509, 121.20183043, '2026-03-08 07:49:17'),
(269, 41, 14.04623604, 121.20182618, '2026-03-08 07:49:58'),
(270, 41, 14.04607879, 121.20186895, '2026-03-08 08:09:41'),
(271, 41, 14.04607879, 121.20186895, '2026-03-08 08:09:42'),
(272, 41, 14.04607879, 121.20186895, '2026-03-08 08:09:42'),
(273, 40, 14.04607879, 121.20186895, '2026-03-08 08:09:43'),
(274, 40, 14.04607879, 121.20186895, '2026-03-08 08:09:43'),
(275, 39, 14.04607879, 121.20186895, '2026-03-08 08:09:46'),
(276, 39, 14.04607879, 121.20186895, '2026-03-08 08:09:46'),
(277, 36, 14.04607879, 121.20186895, '2026-03-08 08:09:49'),
(278, 36, 14.04607879, 121.20186895, '2026-03-08 08:09:49'),
(279, 40, 14.04619067, 121.20184306, '2026-03-08 08:09:55'),
(280, 40, 14.04619067, 121.20184306, '2026-03-08 08:09:55'),
(281, 41, 14.04619067, 121.20184306, '2026-03-08 08:09:58'),
(282, 41, 14.04619067, 121.20184306, '2026-03-08 08:09:58'),
(283, 41, 14.04624484, 121.20183195, '2026-03-08 08:10:41'),
(284, 41, 14.04629009, 121.20181993, '2026-03-08 08:11:43'),
(285, 41, 14.04629009, 121.20181993, '2026-03-08 08:11:55'),
(286, 41, 14.04629009, 121.20181993, '2026-03-08 08:11:55'),
(287, 41, 14.04623483, 121.20183304, '2026-03-08 08:13:26'),
(288, 41, 14.04623483, 121.20183304, '2026-03-08 08:13:26'),
(289, 40, 14.04628642, 121.20181873, '2026-03-08 08:14:12'),
(290, 40, 14.04628642, 121.20181873, '2026-03-08 08:14:13'),
(291, 39, 14.04628642, 121.20181873, '2026-03-08 08:14:17'),
(292, 39, 14.04628642, 121.20181873, '2026-03-08 08:14:17'),
(293, 36, 14.04628642, 121.20181873, '2026-03-08 08:14:22'),
(294, 36, 14.04628642, 121.20181873, '2026-03-08 08:14:22');

-- --------------------------------------------------------

--
-- Table structure for table `grn_items`
--

CREATE TABLE `grn_items` (
  `grn_item_id` int(11) NOT NULL,
  `grn_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `quantity_received` decimal(10,2) NOT NULL,
  `quantity_accepted` decimal(10,2) DEFAULT 0.00,
  `quantity_rejected` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(20) DEFAULT 'kg',
  `lot_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `warehouse_location` varchar(100) DEFAULT NULL,
  `qc_status` enum('Pending','Passed','Failed') DEFAULT 'Pending',
  `qc_remarks` text DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  `qc_record_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grn_items`
--

INSERT INTO `grn_items` (`grn_item_id`, `grn_id`, `po_item_id`, `material_id`, `product_id`, `item_name`, `quantity_received`, `quantity_accepted`, `quantity_rejected`, `unit`, `lot_number`, `expiry_date`, `warehouse_location`, `qc_status`, `qc_remarks`, `unit_price`, `subtotal`, `qc_record_id`) VALUES
(1, 1, 1, NULL, NULL, '0', 123.00, 123.00, 0.00, '0', '', NULL, '', 'Passed', '', 34.00, 3400.00, NULL),
(2, 2, 2, NULL, NULL, '2', 23.00, 23.00, 0.00, '23', '', NULL, '', 'Passed', '', 23.00, 529.00, 1),
(3, 3, 3, NULL, NULL, '0', 200.00, 0.00, 0.00, '0', '', NULL, '', 'Passed', '', 20.00, 4000.00, 4),
(4, 4, 4, NULL, NULL, '0', 200.00, 0.00, 0.00, '0', '', NULL, '', 'Passed', '', 200.00, 40000.00, 5),
(5, 5, 5, NULL, NULL, '0', 23.00, 0.00, 0.00, '0', '', NULL, '', 'Passed', '', 121.00, 2783.00, 6);

-- --------------------------------------------------------

--
-- Table structure for table `id_sequences`
--

CREATE TABLE `id_sequences` (
  `prefix` varchar(10) NOT NULL,
  `seq_date` date NOT NULL,
  `last_seq` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `id_sequences`
--

INSERT INTO `id_sequences` (`prefix`, `seq_date`, `last_seq`) VALUES
('BAT', '2026-02-06', 8),
('BAT', '2026-02-15', 1),
('BAT', '2026-02-16', 9),
('BAT', '2026-02-26', 1),
('BAT', '2026-02-27', 21),
('BAT', '2026-03-01', 1),
('BAT', '2026-03-02', 14),
('BAT', '2026-03-04', 6),
('BAT', '2026-03-07', 1),
('BAT', '2026-03-08', 3),
('CUST', '2026-02-05', 6),
('CUST', '2026-02-07', 1),
('DEL', '2026-02-05', 2),
('DEL', '2026-02-26', 1),
('DEL', '2026-02-27', 1),
('DEL', '2026-03-04', 2),
('DEL', '2026-03-07', 1),
('DEL', '2026-03-08', 10),
('DR', '2026-02-27', 1),
('EMP', '2026-02-05', 2),
('EMP', '2026-03-07', 2),
('EXP', '2026-02-06', 1),
('EXP', '2026-02-26', 1),
('EXP', '2026-02-28', 2),
('EXP', '2026-03-04', 2),
('GRN', '2026-02-26', 1),
('GRN', '2026-02-27', 1),
('GRN', '2026-03-01', 2),
('GRN', '2026-03-04', 1),
('INV', '2026-02-06', 3),
('INV', '2026-02-07', 1),
('INV', '2026-02-27', 1),
('INV', '2026-03-01', 9),
('INV', '2026-03-07', 1),
('MAT', '2026-02-05', 1),
('MAT', '2026-02-26', 1),
('MAT', '2026-02-27', 1),
('MAT', '2026-03-02', 1),
('MAT', '2026-03-04', 1),
('ORD', '2026-02-05', 7),
('ORD', '2026-02-06', 2),
('ORD', '2026-02-07', 1),
('ORD', '2026-02-15', 4),
('ORD', '2026-02-26', 1),
('ORD', '2026-02-27', 1),
('ORD', '2026-03-01', 34),
('ORD', '2026-03-02', 2),
('ORD', '2026-03-04', 7),
('ORD', '2026-03-07', 1),
('PAY', '2026-02-05', 1),
('PAY', '2026-02-17', 1),
('PAY', '2026-02-26', 1),
('PAY', '2026-02-27', 1),
('PAY', '2026-03-01', 3),
('PAY', '2026-03-07', 4),
('PO', '2026-02-26', 1),
('PO', '2026-02-27', 1),
('PO', '2026-03-01', 2),
('PO', '2026-03-04', 1),
('PR', '2026-02-06', 2),
('PR', '2026-02-07', 1),
('PR', '2026-02-26', 1),
('PR', '2026-02-27', 1),
('PR', '2026-03-01', 1),
('PR', '2026-03-04', 2),
('PUP', '2026-03-01', 11),
('QC', '2026-02-06', 32),
('QC', '2026-02-07', 1),
('QC', '2026-02-16', 43),
('QC', '2026-02-26', 1),
('QC', '2026-02-27', 8),
('QC', '2026-03-01', 7),
('QC', '2026-03-02', 5),
('QC', '2026-03-04', 5),
('QC', '2026-03-07', 1),
('RET', '2026-02-27', 1),
('RET', '2026-02-28', 2),
('SI', '2026-03-04', 2),
('SUP', '2026-02-05', 1),
('SUP', '2026-02-06', 2),
('SUP', '2026-02-26', 1),
('USR', '2026-02-05', 1),
('USR', '2026-03-04', 1);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `transaction_id` int(11) NOT NULL,
  `item_type` enum('Raw Material','Finished Product') NOT NULL,
  `item_id` int(11) NOT NULL,
  `transaction_type` enum('In','Out','Adjustment') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `grn_id` int(11) DEFAULT NULL,
  `return_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`transaction_id`, `item_type`, `item_id`, `transaction_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`, `grn_id`, `return_id`) VALUES
(1, 'Finished Product', 1, 'In', 65.00, 'Production', 1, 'Production batch created', 1, '2026-02-01 06:54:01', NULL, NULL),
(2, 'Raw Material', 4, 'In', 12.00, NULL, NULL, 'Initial stock entry', 3, '2026-02-05 05:16:10', NULL, NULL),
(3, 'Raw Material', 5, 'In', 32.00, NULL, NULL, 'Initial stock entry', 3, '2026-02-05 05:55:01', NULL, NULL),
(4, 'Finished Product', 2, 'In', 23.00, 'Production', 2, 'Production batch created', 1, '2026-02-06 11:56:28', NULL, NULL),
(5, 'Finished Product', 3, 'In', 34.00, 'Production', 3, 'Production batch created', 1, '2026-02-06 12:23:48', NULL, NULL),
(6, 'Finished Product', 2, 'In', 34.00, 'Production', 4, 'Production batch created', 1, '2026-02-06 12:27:37', NULL, NULL),
(7, 'Finished Product', 4, 'In', 45.00, 'Production', 5, 'Production batch created', 1, '2026-02-06 12:28:12', NULL, NULL),
(8, 'Finished Product', 5, 'In', 56.00, 'Production', 6, 'Production batch created', 1, '2026-02-06 12:43:22', NULL, NULL),
(9, 'Finished Product', 6, 'In', 34.00, 'Production', 7, 'Production batch created', 1, '2026-02-06 13:12:32', NULL, NULL),
(10, 'Finished Product', 7, 'In', 45.00, 'Production', 8, 'Production batch created', 1, '2026-02-06 14:39:00', NULL, NULL),
(11, 'Finished Product', 8, 'In', 67.00, 'Production', 9, 'Production batch created', 1, '2026-02-06 14:40:08', NULL, NULL),
(12, 'Finished Product', 3, 'In', 2.00, 'Production', 10, 'Production batch created', 1, '2026-02-15 19:15:08', NULL, NULL),
(13, 'Finished Product', 2, 'In', 23.00, 'Production', 11, 'Production batch created', 2, '2026-02-16 06:06:46', NULL, NULL),
(14, 'Finished Product', 13, 'In', 234.00, 'Production', 12, 'Production batch created', 1, '2026-02-16 08:15:30', NULL, NULL),
(15, 'Finished Product', 18, 'In', 34.00, 'Production', 13, 'Production batch created', 1, '2026-02-16 08:57:05', NULL, NULL),
(16, 'Finished Product', 24, 'In', 78.00, 'Production', 14, 'Production batch created', 1, '2026-02-16 10:54:45', NULL, NULL),
(17, 'Finished Product', 26, 'In', 20.00, 'Production', 15, 'Production batch created', 1, '2026-02-16 10:56:28', NULL, NULL),
(18, 'Finished Product', 27, 'In', 113.00, 'Production', 16, 'Production batch created', 1, '2026-02-16 11:20:59', NULL, NULL),
(19, 'Finished Product', 29, 'In', 34.00, 'Production', 17, 'Production batch created', 1, '2026-02-16 11:21:32', NULL, NULL),
(20, 'Finished Product', 30, 'In', 40.00, 'Production', 18, 'Production batch created', 1, '2026-02-16 11:27:51', NULL, NULL),
(21, 'Finished Product', 31, 'In', 80.00, 'Production', 19, 'Production batch created', 1, '2026-02-16 11:55:48', NULL, NULL),
(22, 'Finished Product', 18, 'In', 12.00, 'Production', 20, 'Production output QC approved', 1, '2026-02-26 10:01:11', NULL, NULL),
(23, 'Finished Product', 3, 'In', 2.00, 'Production', 10, 'Production output QC approved', 1, '2026-02-27 10:24:12', NULL, NULL),
(24, 'Raw Material', 7, 'In', 123.00, 'QC', 3, 'QC approved - Added to inventory', 1, '2026-02-27 10:27:58', NULL, NULL),
(25, 'Finished Product', 3, 'In', 34.00, 'Production', 3, 'Production output QC approved', 1, '2026-02-27 10:30:19', NULL, NULL),
(26, 'Finished Product', 2, 'In', 34.00, 'Production', 4, 'Production output QC approved', 1, '2026-02-27 10:30:20', NULL, NULL),
(27, 'Finished Product', 4, 'In', 45.00, 'Production', 5, 'Production output QC approved', 1, '2026-02-27 10:30:22', NULL, NULL),
(28, 'Finished Product', 5, 'In', 56.00, 'Production', 6, 'Production output QC approved', 1, '2026-02-27 10:30:24', NULL, NULL),
(29, 'Finished Product', 6, 'In', 34.00, 'Production', 7, 'Production output QC approved', 1, '2026-02-27 10:30:25', NULL, NULL),
(30, 'Finished Product', 7, 'In', 45.00, 'Production', 8, 'Production output QC approved', 1, '2026-02-27 10:30:27', NULL, NULL),
(31, 'Finished Product', 31, 'In', 80.00, 'Production', 19, 'Production output QC approved', 1, '2026-02-27 10:30:40', NULL, NULL),
(32, 'Finished Product', 30, 'In', 40.00, 'Production', 18, 'Production output QC approved', 1, '2026-02-27 10:32:16', NULL, NULL),
(33, 'Raw Material', 3, 'Out', 12.00, 'Production', 32, 'Used in production batch', 1, '2026-02-27 11:14:16', NULL, NULL),
(34, 'Raw Material', 1, 'Out', 12.00, 'Production', 33, 'Used in production batch', 1, '2026-02-27 11:30:20', NULL, NULL),
(35, 'Raw Material', 3, 'Out', 121.00, 'Production', 35, 'Used in production batch', 1, '2026-02-27 11:31:16', NULL, NULL),
(36, 'Raw Material', 1, 'Out', 432.00, 'Production', 36, 'Used in production batch', 1, '2026-02-27 11:31:53', NULL, NULL),
(37, 'Raw Material', 6, 'Out', 12.00, 'Production', 37, 'Used in production batch', 1, '2026-02-27 11:34:14', NULL, NULL),
(38, 'Raw Material', 3, 'Out', 12.00, 'Production', 39, 'Used in production batch', 1, '2026-02-27 11:35:32', NULL, NULL),
(39, 'Finished Product', 29, 'In', 123.00, 'Production', 39, 'Production output QC approved', 1, '2026-02-27 11:36:06', NULL, NULL),
(40, 'Raw Material', 1, 'Out', 12.00, 'Production', 40, 'Used in production batch', 1, '2026-02-27 12:02:18', NULL, NULL),
(41, 'Raw Material', 2, 'Out', 12.00, 'Production', 41, 'Used in production batch', 1, '2026-02-27 12:02:38', NULL, NULL),
(42, 'Raw Material', 2, 'Out', 100.00, 'Production', 42, 'Used in production batch', 1, '2026-03-01 09:49:41', NULL, NULL),
(43, 'Raw Material', 1, 'Out', 4.00, 'Production', 42, 'Used in production batch', 1, '2026-03-01 09:49:41', NULL, NULL),
(44, 'Finished Product', 29, 'In', 1300.00, 'Production', 42, 'Production output QC approved', 1, '2026-03-01 09:54:05', NULL, NULL),
(45, 'Finished Product', 29, 'Out', 6.00, 'Sales', 56, 'Sales fulfillment', 1, '2026-03-01 12:04:34', NULL, NULL),
(46, 'Raw Material', 7, 'In', 123.00, '0', 3, 'QC approved - Added to inventory', 1, '2026-03-01 13:21:53', NULL, NULL),
(47, 'Raw Material', 2, 'In', 100.00, NULL, NULL, 'Stock added from inventory form', 3, '2026-03-01 14:41:44', NULL, NULL),
(48, 'Finished Product', 29, 'In', 12.00, 'Production', 33, 'Production output QC approved', 1, '2026-03-01 15:31:54', NULL, NULL),
(49, 'Raw Material', 3, 'Out', 1.00, 'Production', 43, 'Used in production batch', 1, '2026-03-02 04:46:56', NULL, NULL),
(50, 'Finished Product', 13, 'In', 90.00, 'Production', 43, 'Production output QC approved', 1, '2026-03-02 04:49:58', NULL, NULL),
(51, 'Raw Material', 2, 'Out', 1.00, 'Production', 44, 'Used in production batch', 1, '2026-03-02 05:50:38', NULL, NULL),
(52, 'Raw Material', 1, 'Out', 1.00, 'Production', 44, 'Used in production batch', 1, '2026-03-02 05:50:38', NULL, NULL),
(53, 'Raw Material', 3, 'Out', 1.00, 'Production', 44, 'Used in production batch', 1, '2026-03-02 05:50:38', NULL, NULL),
(54, 'Raw Material', 6, 'Out', 1.00, 'Production', 45, 'Used in production batch', 1, '2026-03-02 06:55:25', NULL, NULL),
(55, 'Raw Material', 1, 'Out', 1.00, 'Production', 46, 'Used in production batch', 1, '2026-03-02 06:57:59', NULL, NULL),
(56, 'Raw Material', 6, 'Out', 1.00, 'Production', 47, 'Used in production batch', 1, '2026-03-02 07:05:01', NULL, NULL),
(57, 'Finished Product', 32, 'In', 100.00, 'Production', 44, 'Production output QC approved', 1, '2026-03-02 07:05:28', NULL, NULL),
(58, 'Raw Material', 32, 'Out', 1.00, 'Production', 49, 'Used in production batch', 1, '2026-03-02 07:36:34', NULL, NULL),
(59, 'Finished Product', 29, 'In', 1300.00, 'Production', 49, 'Production output QC approved', 1, '2026-03-02 07:37:59', NULL, NULL),
(60, 'Raw Material', 27, 'Out', 1.00, 'Production', 50, 'Used in production batch', 1, '2026-03-02 08:01:44', NULL, NULL),
(61, 'Finished Product', 32, 'In', 12.00, 'Production', 50, 'Production output QC approved', 1, '2026-03-02 08:03:53', NULL, NULL),
(62, 'Raw Material', 30, 'Out', 1.00, 'Production', 51, 'Used in production batch', 1, '2026-03-02 08:05:41', NULL, NULL),
(63, 'Finished Product', 33, 'In', 12334.00, 'Production', 51, 'Production output QC approved', 1, '2026-03-02 08:05:55', NULL, NULL),
(64, 'Raw Material', 15, 'Out', 12.00, 'Production', 52, 'Used in production batch', 1, '2026-03-04 11:10:35', NULL, NULL),
(65, 'Finished Product', 34, 'In', 100.00, 'Production', 52, 'Production output QC approved', 1, '2026-03-04 11:10:55', NULL, NULL),
(66, 'Raw Material', 36, 'In', 123.00, '0', 3, 'QC approved - Added to inventory', 1, '2026-03-04 12:00:21', NULL, NULL),
(67, 'Raw Material', 26, 'Out', 1.00, 'Production', 53, 'Used in production batch', 1, '2026-03-04 12:47:41', NULL, NULL),
(68, 'Raw Material', 30, 'Out', 1.00, 'Production', 54, 'Used in production batch', 1, '2026-03-04 12:48:15', NULL, NULL),
(69, 'Raw Material', 12, 'Out', 1.00, 'Production', 55, 'Used in production batch', 1, '2026-03-04 13:05:24', NULL, NULL),
(70, 'Finished Product', 35, 'In', 100.00, 'Production', 55, 'Production output QC approved', 1, '2026-03-04 13:07:16', NULL, NULL),
(71, 'Raw Material', 30, 'Out', 1.00, 'Production', 56, 'Used in production batch', 1, '2026-03-04 13:26:52', NULL, NULL),
(72, 'Finished Product', 35, 'In', 100.00, 'Production', 56, 'Production output QC approved', 1, '2026-03-04 13:28:16', NULL, NULL),
(73, 'Finished Product', 36, 'In', 100.00, 'Production', 57, 'Production output QC approved', 1, '2026-03-04 13:28:34', NULL, NULL),
(74, 'Raw Material', 6, 'Out', 1.00, 'Production', 58, 'Used in production batch', 1, '2026-03-07 02:39:27', NULL, NULL),
(75, 'Finished Product', 37, 'In', 200.00, 'Production', 58, 'Production output QC approved', 1, '2026-03-07 02:40:55', NULL, NULL),
(76, 'Raw Material', 27, 'Out', 1.00, 'Production', 59, 'Used in production batch', 1, '2026-03-08 06:37:19', NULL, NULL),
(77, 'Raw Material', 27, 'Out', 1.00, 'Production', 60, 'Used in production batch', 1, '2026-03-08 06:56:33', NULL, NULL),
(78, 'Raw Material', 30, 'Out', 1.00, 'Production', 61, 'Used in production batch', 1, '2026-03-08 06:58:58', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `invoice_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Pending','Partially Paid','Paid','Overdue') DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `vat_rate` decimal(5,2) DEFAULT 0.00,
  `vat_amount` decimal(12,2) DEFAULT 0.00,
  `payment_terms` varchar(50) DEFAULT 'Cash',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `delivery_receipt_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`invoice_id`, `invoice_number`, `customer_id`, `order_id`, `amount`, `invoice_date`, `due_date`, `status`, `created_by`, `created_at`, `subtotal`, `discount_amount`, `vat_rate`, `vat_amount`, `payment_terms`, `approved_by`, `approved_at`, `approval_status`, `notes`, `delivery_receipt_number`) VALUES
(5, 'INV-20260227-0001', 12, 17, 11709.60, '2026-02-27', '2026-02-27', 'Paid', 1, '2026-02-27 11:38:22', 10455.00, 0.00, 12.00, 1254.60, 'Cash', 1, '2026-02-27 11:38:38', 'Approved', '', 'DR-20260227-0001'),
(6, 'INV-20260301-0001', 12, NULL, 1000.00, '2026-03-01', '2026-04-11', 'Paid', 1, '2026-03-01 09:43:30', 0.00, 0.00, 0.00, 0.00, 'Cash', 1, '2026-03-01 09:43:35', 'Approved', NULL, NULL),
(7, 'INV-20260301-0002', 12, NULL, 1000.00, '2026-03-01', '2026-03-31', 'Paid', 1, '2026-03-01 09:45:05', 0.00, 0.00, 0.00, 0.00, 'Cash', 1, '2026-03-01 09:45:13', 'Approved', NULL, NULL),
(8, 'INV-20260301-0003', 13, 48, 1020.00, '2026-03-01', '2026-03-31', 'Paid', 1, '2026-03-01 12:53:06', 0.00, 0.00, 0.00, 0.00, 'Cash', 1, '2026-03-01 12:53:11', 'Approved', NULL, NULL),
(9, 'INV-20260301-0004', 12, 59, 340.00, '2026-03-01', '2026-03-31', 'Pending', 1, '2026-03-01 12:55:39', 0.00, 0.00, 0.00, 0.00, 'Cash', NULL, NULL, 'Pending', NULL, NULL),
(10, 'INV-20260301-0005', 12, 61, 100.00, '2026-03-01', '2026-03-31', 'Pending', 1, '2026-03-01 13:16:01', 0.00, 0.00, 0.00, 0.00, 'Cash', NULL, NULL, 'Pending', NULL, NULL),
(11, 'INV-20260301-0006', 12, 60, 100.00, '2026-03-01', '2026-03-31', 'Pending', 1, '2026-03-01 13:16:15', 0.00, 0.00, 0.00, 0.00, 'Cash', NULL, NULL, 'Pending', NULL, NULL),
(12, 'INV-20260301-0007', 12, 58, 8.00, '2026-03-01', '2026-03-31', 'Pending', 1, '2026-03-01 13:16:28', 0.00, 0.00, 0.00, 0.00, 'Cash', 1, '2026-03-01 15:40:06', 'Approved', NULL, NULL),
(13, 'INV-20260301-0008', 12, 18, 100.00, '2026-03-01', '2026-03-31', 'Pending', 1, '2026-03-01 15:40:34', 0.00, 0.00, 0.00, 0.00, 'Cash', NULL, NULL, 'Pending', NULL, NULL),
(14, 'INV-20260301-0009', 12, 27, 2890.00, '2026-03-01', '2026-03-31', 'Pending', 1, '2026-03-01 15:40:42', 0.00, 0.00, 0.00, 0.00, 'Cash', NULL, NULL, 'Pending', NULL, NULL),
(15, 'INV-20260307-0001', 15, 72, 2254.00, '2026-03-07', '2026-04-06', 'Paid', 1, '2026-03-07 02:44:36', 0.00, 0.00, 0.00, 0.00, 'Cash', 1, '2026-03-07 02:44:57', 'Approved', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `item_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`item_id`, `invoice_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`, `created_at`) VALUES
(1, 5, 26, 'Filtaste Kaong 12 oz', 123.00, 85.00, 10455.00, '2026-02-27 11:38:22');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `checksum` varchar(64) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `applied_by` int(11) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reserved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`, `reserved`, `created_at`) VALUES
(18, 17, 26, 123.00, 85.00, 10455.00, 0, '2026-02-27 11:36:06'),
(19, 21, 26, 1300.00, 85.00, 110500.00, 0, '2026-03-01 09:54:05'),
(20, 26, 26, 23.00, 85.00, 1955.00, 1, '2026-03-01 10:01:16'),
(21, 26, 17, 23.00, 38.79, 892.17, 1, '2026-03-01 10:01:16'),
(22, 27, 26, 34.00, 85.00, 2890.00, 1, '2026-03-01 10:01:49'),
(23, 28, 26, 12.00, 85.00, 1020.00, 1, '2026-03-01 10:21:27'),
(24, 29, 26, 12.00, 85.00, 1020.00, 1, '2026-03-01 10:21:36'),
(25, 30, 26, 7.00, 85.00, 595.00, 1, '2026-03-01 10:23:59'),
(26, 31, 26, 7.00, 85.00, 595.00, 1, '2026-03-01 10:30:06'),
(27, 32, 26, 12.00, 85.00, 1020.00, 1, '2026-03-01 10:41:23'),
(28, 33, 26, 7.00, 85.00, 595.00, 1, '2026-03-01 10:46:09'),
(29, 34, 26, 7.00, 85.00, 595.00, 1, '2026-03-01 10:47:42'),
(30, 35, 17, 6.00, 38.79, 232.74, 1, '2026-03-01 10:48:30'),
(31, 36, 26, 23.00, 85.00, 1955.00, 1, '2026-03-01 10:48:40'),
(32, 37, 26, 7.00, 85.00, 595.00, 1, '2026-03-01 10:49:58'),
(33, 38, 26, 7.00, 85.00, 595.00, 1, '2026-03-01 10:51:57'),
(34, 39, 26, 12.00, 85.00, 1020.00, 1, '2026-03-01 11:05:51'),
(35, 40, 26, 12.00, 85.00, 1020.00, 1, '2026-03-01 11:06:09'),
(36, 41, 26, 3.00, 85.00, 255.00, 1, '2026-03-01 11:10:30'),
(37, 42, 26, 54.00, 85.00, 4590.00, 1, '2026-03-01 11:14:06'),
(38, 43, 26, 6.00, 85.00, 510.00, 1, '2026-03-01 11:16:55'),
(39, 44, 26, 5.00, 85.00, 425.00, 1, '2026-03-01 11:18:07'),
(40, 45, 26, 5.00, 85.00, 425.00, 1, '2026-03-01 11:18:52'),
(41, 46, 26, 4.00, 85.00, 340.00, 1, '2026-03-01 11:20:42'),
(42, 47, 26, 6.00, 85.00, 510.00, 1, '2026-03-01 11:22:21'),
(43, 48, 26, 12.00, 85.00, 1020.00, 1, '2026-03-01 11:26:17'),
(44, 49, 26, 8.00, 85.00, 680.00, 1, '2026-03-01 11:29:00'),
(45, 50, 26, 4.00, 85.00, 340.00, 1, '2026-03-01 11:29:17'),
(46, 51, 26, 6.00, 85.00, 510.00, 1, '2026-03-01 11:29:55'),
(47, 52, 26, 5.00, 85.00, 425.00, 1, '2026-03-01 11:37:25'),
(48, 53, 26, 23.00, 85.00, 1955.00, 1, '2026-03-01 11:41:55'),
(49, 54, 26, 2.00, 85.00, 170.00, 1, '2026-03-01 11:48:16'),
(50, 55, 26, 4.00, 85.00, 340.00, 1, '2026-03-01 11:53:49'),
(51, 56, 26, 6.00, 85.00, 510.00, 0, '2026-03-01 12:04:34'),
(52, 57, 26, 12.00, 85.00, 1020.00, 1, '2026-03-01 12:18:39'),
(53, 58, 26, 12.00, 85.00, 1020.00, 1, '2026-03-01 12:20:10'),
(54, 59, 26, 4.00, 85.00, 340.00, 1, '2026-03-01 12:22:34'),
(55, 60, 26, 23.00, 85.00, 1955.00, 1, '2026-03-01 12:56:39'),
(56, 61, 26, 9.00, 85.00, 765.00, 1, '2026-03-01 13:09:27'),
(57, 62, 26, 123.00, 85.00, 10455.00, 0, '2026-03-01 15:31:54'),
(58, 63, 42, 100.00, 163.77, 16377.00, 0, '2026-03-02 07:05:28'),
(59, 64, 26, 1300.00, 85.00, 110500.00, 0, '2026-03-02 07:37:59'),
(60, 65, 26, 2.00, 85.00, 170.00, 1, '2026-03-04 09:32:43'),
(61, 66, 32, 100.00, 36.33, 3633.00, 0, '2026-03-04 11:10:55'),
(62, 67, 41, 100.00, 124.53, 12453.00, 1, '2026-03-04 13:07:16'),
(63, 68, 41, 100.00, 124.53, 12453.00, 1, '2026-03-04 13:28:16'),
(64, 68, 24, 100.00, 60.00, 6000.00, 1, '2026-03-04 13:28:16'),
(65, 69, 41, 100.00, 124.53, 12453.00, 1, '2026-03-04 13:28:34'),
(66, 69, 24, 100.00, 60.00, 6000.00, 1, '2026-03-04 13:28:34'),
(67, 70, 26, 500.00, 85.00, 42500.00, 1, '2026-03-04 14:25:42'),
(68, 71, 26, 30.00, 85.00, 2550.00, 1, '2026-03-04 14:44:47'),
(69, 72, 1, 200.00, 11.27, 2254.00, 1, '2026-03-07 02:40:55');

-- --------------------------------------------------------

--
-- Table structure for table `pagination_settings`
--

CREATE TABLE `pagination_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pagination_settings`
--

INSERT INTO `pagination_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'items_per_page', '25', 'Default rows per page', '2026-03-08 06:58:14'),
(2, 'per_page_options', '10,25,50,100,200', 'Dropdown options', '2026-03-08 06:58:14');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `payment_number` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Check','Credit Card','Other') DEFAULT 'Cash',
  `amount` decimal(12,2) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `invoice_id`, `payment_number`, `payment_date`, `payment_method`, `amount`, `reference_number`, `notes`, `created_by`, `created_at`) VALUES
(1, 5, 'PAY-20260227-0001', '2026-02-27', '', 11709.60, '', '', 1, '2026-02-27 11:38:53'),
(2, 6, 'PAY-20260301-0001', '2026-03-01', '', 1000.00, '', '', 1, '2026-03-01 09:43:45'),
(3, 7, 'PAY-20260301-0002', '2026-03-01', '', 1000.00, '', '', 1, '2026-03-01 09:45:20'),
(4, 8, 'PAY-20260301-0003', '2026-03-01', '', 1020.00, '', '', 1, '2026-03-01 12:53:16'),
(5, 15, 'PAY-20260307-0001', '2026-03-07', '', 2254.00, '', '', 1, '2026-03-07 02:45:16');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `payroll_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payroll_period_start` date NOT NULL,
  `payroll_period_end` date NOT NULL,
  `basic_salary` decimal(10,2) DEFAULT 0.00,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `gross_pay` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `net_pay` decimal(10,2) DEFAULT 0.00,
  `status` enum('Draft','Processed','Paid') DEFAULT 'Draft',
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payroll_ref` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`payroll_id`, `employee_id`, `payroll_period_start`, `payroll_period_end`, `basic_salary`, `overtime_pay`, `allowances`, `gross_pay`, `deductions`, `net_pay`, `status`, `processed_by`, `created_at`, `payroll_ref`) VALUES
(1, 1, '2026-02-05', '2026-02-05', 500.00, 100.00, 50.00, 650.00, 300.00, 350.00, 'Processed', 5, '2026-02-05 06:21:30', 'PAY-20260205-0001'),
(2, 1, '2026-02-01', '2026-02-28', 500.00, 2323.00, 323.00, 3146.00, 157.50, 2988.50, 'Processed', 5, '2026-02-17 20:59:53', 'PAY-20260217-0001'),
(3, 2, '2026-02-26', '2026-03-06', 600.00, 0.00, 0.00, 600.00, 162.00, 438.00, 'Processed', 1, '2026-02-26 09:56:14', 'PAY-20260226-0001'),
(4, 3, '2026-03-01', '2026-03-31', 500.00, 0.00, 0.00, 500.00, 157.50, 342.50, 'Processed', 1, '2026-03-07 02:48:57', 'PAY-20260307-0002'),
(5, 3, '2026-03-01', '2026-03-15', 500.00, 0.00, 0.00, 500.00, 157.50, 342.50, 'Processed', 1, '2026-03-07 02:58:50', 'PAY-20260307-0003'),
(6, 3, '2026-03-01', '2026-03-30', 500.00, 0.00, 0.00, 0.00, 157.50, -157.50, 'Processed', 1, '2026-03-07 07:17:35', 'PAY-20260307-0004');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_breakdown`
--

CREATE TABLE `payroll_breakdown` (
  `id` int(11) NOT NULL,
  `payroll_id` int(11) NOT NULL,
  `type` enum('earning','deduction') NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_breakdown`
--

INSERT INTO `payroll_breakdown` (`id`, `payroll_id`, `type`, `code`, `description`, `amount`, `created_at`) VALUES
(1, 2, 'deduction', 'SSS', 'SSS Contribution', 135.00, '2026-02-17 20:59:53'),
(2, 2, 'deduction', 'PHILHEALTH', 'PhilHealth Contribution', 12.50, '2026-02-17 20:59:53'),
(3, 2, 'deduction', 'PAGIBIG', 'Pag-IBIG Contribution', 10.00, '2026-02-17 20:59:53'),
(4, 3, 'deduction', 'SSS', 'SSS Contribution', 135.00, '2026-02-26 09:56:14'),
(5, 3, 'deduction', 'PHILHEALTH', 'PhilHealth Contribution', 15.00, '2026-02-26 09:56:14'),
(6, 3, 'deduction', 'PAGIBIG', 'Pag-IBIG Contribution', 12.00, '2026-02-26 09:56:14'),
(7, 4, 'deduction', 'SSS', 'SSS Contribution', 135.00, '2026-03-07 02:48:57'),
(8, 4, 'deduction', 'PHILHEALTH', 'PhilHealth Contribution', 12.50, '2026-03-07 02:48:57'),
(9, 4, 'deduction', 'PAGIBIG', 'Pag-IBIG Contribution', 10.00, '2026-03-07 02:48:57'),
(10, 5, 'deduction', 'SSS', 'SSS Contribution', 135.00, '2026-03-07 02:58:50'),
(11, 5, 'deduction', 'PHILHEALTH', 'PhilHealth Contribution', 12.50, '2026-03-07 02:58:50'),
(12, 5, 'deduction', 'PAGIBIG', 'Pag-IBIG Contribution', 10.00, '2026-03-07 02:58:50'),
(13, 6, 'deduction', 'SSS', 'SSS Contribution', 135.00, '2026-03-07 07:17:35'),
(14, 6, 'deduction', 'PHILHEALTH', 'PhilHealth Contribution', 12.50, '2026-03-07 07:17:35'),
(15, 6, 'deduction', 'PAGIBIG', 'Pag-IBIG Contribution', 10.00, '2026-03-07 07:17:35');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_deductions`
--

CREATE TABLE `payroll_deductions` (
  `deduction_id` int(11) NOT NULL,
  `payroll_id` int(11) NOT NULL,
  `deduction_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_settings`
--

CREATE TABLE `payroll_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` decimal(10,4) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_settings`
--

INSERT INTO `payroll_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'late_penalty_fraction', 0.1250, 'Late penalty fraction of daily rate', '2026-02-17 19:25:41'),
(2, 'working_days', 26.0000, 'Monthly working days', '2026-02-17 19:25:41'),
(3, 'sss_rate', 0.0450, 'SSS employee contribution rate', '2026-02-17 19:25:41'),
(4, 'philhealth_rate', 0.0250, 'PhilHealth employee contribution rate', '2026-02-17 19:25:41'),
(5, 'pagibig_rate', 0.0200, 'Pag-IBIG employee contribution rate', '2026-02-17 19:25:41');

-- --------------------------------------------------------

--
-- Table structure for table `po_items`
--

CREATE TABLE `po_items` (
  `po_item_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_type` enum('Raw Material','Product','Other') DEFAULT 'Raw Material',
  `quantity_ordered` decimal(10,2) NOT NULL,
  `quantity_received` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(20) DEFAULT 'kg',
  `unit_price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `po_items`
--

INSERT INTO `po_items` (`po_item_id`, `po_id`, `material_id`, `product_id`, `item_name`, `item_type`, `quantity_ordered`, `quantity_received`, `unit`, `unit_price`, `subtotal`, `notes`) VALUES
(1, 1, NULL, NULL, 'bulb lightS', '', 123.00, 100.00, '0', 34.00, 0.00, ''),
(2, 2, NULL, NULL, '2we', '', 23.00, 23.00, '23', 23.00, 5.00, ''),
(3, 3, NULL, NULL, 'plastic bags', '', 200.00, 200.00, '0', 20.00, 0.00, 'a'),
(4, 4, NULL, NULL, 'plastic bags', '', 200.00, 200.00, '0', 200.00, 4.00, ''),
(5, 5, NULL, NULL, 'plastic bags', '', 23.00, 23.00, '0', 121.00, 0.00, '');

-- --------------------------------------------------------

--
-- Table structure for table `production_batches`
--

CREATE TABLE `production_batches` (
  `batch_id` int(11) NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `batch_date` date NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `expiry_date` date NOT NULL,
  `warehouse_location` varchar(255) DEFAULT NULL,
  `fermentation_status` enum('Not Started','Ongoing','Completed') DEFAULT 'Not Started',
  `packaging_status` enum('Pending','In Progress','Finished') DEFAULT 'Pending',
  `status` varchar(50) DEFAULT 'Processing',
  `created_by` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phase` varchar(50) DEFAULT 'Planned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_batches`
--

INSERT INTO `production_batches` (`batch_id`, `batch_number`, `product_id`, `batch_date`, `quantity`, `expiry_date`, `warehouse_location`, `fermentation_status`, `packaging_status`, `status`, `created_by`, `request_id`, `created_at`, `phase`) VALUES
(1, '98', 11, '2026-02-01', 65.00, '2027-02-01', NULL, 'Ongoing', 'In Progress', 'Completed', 1, NULL, '2026-02-01 06:54:01', 'Planned'),
(2, 'BAT-20260206-0001', 17, '2026-02-06', 23.00, '2027-02-06', NULL, 'Not Started', 'Finished', 'Completed', 1, NULL, '2026-02-06 11:56:28', 'Planned'),
(3, 'BAT-20260206-0002', 14, '2026-02-06', 34.00, '2027-02-06', NULL, 'Completed', 'Finished', 'Completed', 1, NULL, '2026-02-06 12:23:48', 'Completed'),
(4, 'BAT-20260206-0003', 17, '2026-02-06', 34.00, '2027-02-06', NULL, 'Ongoing', 'In Progress', 'Completed', 1, NULL, '2026-02-06 12:27:37', 'Completed'),
(5, 'BAT-20260206-0004', 34, '2026-02-06', 45.00, '2027-02-06', NULL, 'Not Started', 'Finished', 'Completed', 1, NULL, '2026-02-06 12:28:12', 'Completed'),
(6, 'BAT-20260206-0005', 36, '2026-02-06', 56.00, '2027-02-06', NULL, 'Not Started', 'Finished', 'Completed', 1, NULL, '2026-02-06 12:43:22', 'Completed'),
(7, 'BAT-20260206-0006', 39, '2026-02-06', 34.00, '2027-02-06', NULL, 'Completed', 'Finished', 'Completed', 1, NULL, '2026-02-06 13:12:32', 'Completed'),
(8, 'BAT-20260206-0007', 35, '2026-02-06', 45.00, '2026-02-07', NULL, 'Ongoing', 'Finished', 'Completed', 1, NULL, '2026-02-06 14:39:00', 'Completed'),
(9, 'BAT-20260206-0008', 37, '2026-02-06', 67.00, '2026-02-07', NULL, 'Not Started', 'In Progress', 'Ready', 1, NULL, '2026-02-06 14:40:08', 'Planned'),
(10, 'BAT-20260215-0001', 14, '2026-02-15', 2.00, '2026-02-16', NULL, 'Not Started', 'Pending', 'Completed', 1, NULL, '2026-02-15 19:15:08', 'Completed'),
(11, 'BAT-20260216-0001', 17, '2026-02-16', 23.00, '2026-02-17', NULL, 'Ongoing', 'Pending', 'Ready', 2, NULL, '2026-02-16 06:06:46', 'Planned'),
(12, 'BAT-20260216-0002', 16, '2026-02-16', 234.00, '2026-02-17', NULL, 'Completed', 'Finished', 'Completed', 1, NULL, '2026-02-16 08:15:30', 'Planned'),
(13, 'BAT-20260216-0003', 12, '2026-02-16', 34.00, '2026-02-17', NULL, 'Ongoing', 'Pending', 'Completed', 1, NULL, '2026-02-16 08:57:05', 'Planned'),
(14, 'BAT-20260216-0004', 23, '2026-02-16', 78.00, '2026-02-17', NULL, 'Not Started', 'Pending', 'Completed', 1, NULL, '2026-02-16 10:54:45', 'Planned'),
(15, 'BAT-20260216-0005', 18, '2026-02-16', 20.00, '2026-02-17', NULL, 'Not Started', 'Pending', 'Processing', 1, NULL, '2026-02-16 10:56:28', 'In Progress'),
(16, 'BAT-20260216-0006', 15, '2026-02-16', 113.00, '2026-02-17', NULL, 'Ongoing', 'Pending', 'Completed', 1, NULL, '2026-02-16 11:20:59', 'Planned'),
(17, 'BAT-20260216-0007', 26, '2026-02-16', 34.00, '2026-02-17', NULL, 'Not Started', 'Pending', 'Processing', 1, NULL, '2026-02-16 11:21:32', 'In Progress'),
(18, 'BAT-20260216-0008', 19, '2026-02-16', 40.00, '2026-02-17', NULL, 'Not Started', 'Pending', 'Completed', 1, NULL, '2026-02-16 11:27:51', 'Completed'),
(19, 'BAT-20260216-0009', 21, '2026-02-16', 80.00, '2026-02-17', NULL, 'Not Started', 'Pending', 'Completed', 1, NULL, '2026-02-16 11:55:47', 'Completed'),
(20, 'BAT-20260226-0001', 12, '2026-02-26', 12.00, '2026-02-27', NULL, '', 'Pending', 'Completed', 1, 26, '2026-02-26 10:00:20', 'Completed'),
(21, 'BAT-20260227-0001', 26, '2026-02-27', 900.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 27, '2026-02-27 10:45:50', 'In Progress'),
(22, 'BAT-20260227-0002', 23, '2026-02-27', 23.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 0, '2026-02-27 10:56:06', 'In Progress'),
(23, 'BAT-20260227-0003', 26, '2026-02-27', 1300.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 28, '2026-02-27 10:57:20', 'In Progress'),
(24, 'BAT-20260227-0004', 21, '2026-02-27', 12.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 0, '2026-02-27 11:00:13', 'In Progress'),
(25, 'BAT-20260227-0005', 10, '2026-02-27', 12.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 0, '2026-02-27 11:01:06', 'In Progress'),
(26, 'BAT-20260227-0006', 10, '2026-02-27', 12.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 0, '2026-02-27 11:01:29', 'In Progress'),
(27, 'BAT-20260227-0007', 26, '2026-02-27', 12.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 0, '2026-02-27 11:02:33', 'In Progress'),
(28, 'BAT-20260227-0008', 12, '2026-02-27', 12.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 0, '2026-02-27 11:06:07', 'In Progress'),
(29, 'BAT-20260227-0009', 26, '2026-02-27', 12.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 0, '2026-02-27 11:09:52', 'In Progress'),
(30, 'BAT-20260227-0010', 26, '2026-02-27', 12.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 0, '2026-02-27 11:11:41', 'In Progress'),
(31, 'BAT-20260227-0011', 26, '2026-02-27', 12.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 0, '2026-02-27 11:12:13', 'In Progress'),
(32, 'BAT-20260227-0012', 26, '2026-02-27', 12.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 0, '2026-02-27 11:14:16', 'In Progress'),
(33, 'BAT-20260227-0013', 26, '2026-02-27', 12.00, '2026-02-28', NULL, 'Ongoing', 'Pending', 'Completed', 1, 31, '2026-02-27 11:30:20', 'Completed'),
(34, 'BAT-20260227-0014', 13, '2026-02-27', 221.00, '2026-02-28', NULL, 'Not Started', 'Pending', 'Rejected', 1, 0, '2026-02-27 11:30:47', 'Output Pending QC'),
(35, 'BAT-20260227-0015', 17, '2026-02-27', 12.00, '2026-02-28', NULL, 'Not Started', 'Pending', 'Processing', 1, 0, '2026-02-27 11:31:16', 'In Progress'),
(36, 'BAT-20260227-0016', 24, '2026-02-27', 122.00, '2026-02-28', NULL, 'Not Started', 'Pending', 'Ready', 1, 0, '2026-02-27 11:31:53', 'Output Pending QC'),
(37, 'BAT-20260227-0017', 26, '2026-02-27', 12.00, '2026-02-28', NULL, '', 'Pending', 'Rejected', 1, 29, '2026-02-27 11:34:14', 'Output Pending QC'),
(38, 'BAT-20260227-0018', 24, '2026-02-27', 12.00, '2026-02-28', NULL, '', 'Pending', 'Ready', 1, 30, '2026-02-27 11:34:14', 'Output Pending QC'),
(39, 'BAT-20260227-0019', 26, '2026-02-27', 123.00, '2026-02-28', NULL, '', 'Pending', 'Completed', 1, 32, '2026-02-27 11:35:32', 'Completed'),
(40, 'BAT-20260227-0020', 26, '2026-02-27', 1000.00, '2026-02-28', NULL, '', 'Pending', 'Processing', 1, 27, '2026-02-27 12:02:18', 'In Progress'),
(41, 'BAT-20260227-0021', 1, '2026-02-27', 12.00, '2026-02-28', NULL, '', 'Pending', 'Rejected', 1, 0, '2026-02-27 12:02:38', 'Output Pending QC'),
(42, 'BAT-20260301-0001', 26, '2026-03-01', 1300.00, '2026-03-02', NULL, '', 'Pending', 'Completed', 1, 28, '2026-03-01 09:49:41', 'Completed'),
(43, 'BAT-20260302-0001', 16, '2026-03-02', 90.00, '2026-03-03', NULL, '', 'Pending', 'Completed', 1, 0, '2026-03-02 04:46:56', 'Completed'),
(44, 'BAT-20260302-0002', 42, '2026-03-02', 100.00, '2026-03-03', NULL, '', 'Pending', 'Completed', 1, 33, '2026-03-02 05:50:38', 'Completed'),
(45, 'BAT-20260302-0008', 42, '2026-03-02', 100.00, '2027-03-02', NULL, '', 'Pending', 'Processing', 1, 33, '2026-03-02 06:55:25', 'In Progress'),
(46, 'BAT-20260302-0009', 26, '2026-03-02', 1000.00, '2027-03-02', NULL, '', 'Pending', 'Processing', 1, 27, '2026-03-02 06:57:59', 'In Progress'),
(47, 'BAT-20260302-0010', 10, '2026-03-02', 23.00, '2029-03-02', NULL, '', 'Pending', 'Processing', 1, 13, '2026-03-02 07:05:01', 'In Progress'),
(48, 'BAT-20260302-0011', 26, '2026-03-02', 1300.00, '2027-03-02', NULL, '', 'Pending', 'Processing', 1, 28, '2026-03-02 07:32:12', 'In Progress'),
(49, 'BAT-20260302-0012', 26, '2026-03-02', 1300.00, '2027-03-02', NULL, '', 'Pending', 'Completed', 1, 28, '2026-03-02 07:36:34', 'Completed'),
(50, 'BAT-20260302-0013', 42, '2026-03-02', 12.00, '2027-03-02', NULL, 'Completed', 'Pending', 'Completed', 1, 0, '2026-03-02 08:01:44', 'Completed'),
(51, 'BAT-20260302-0014', 20, '2026-03-02', 12334.00, '2027-03-02', NULL, '', 'Pending', 'Completed', 1, 0, '2026-03-02 08:05:41', 'Completed'),
(52, 'BAT-20260304-0001', 32, '2026-03-04', 100.00, '2028-03-04', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '', 'Pending', 'Completed', 1, 34, '2026-03-04 11:10:35', 'Completed'),
(53, 'BAT-20260304-0002', 10, '2026-03-04', 23.00, '2029-03-04', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '', 'Pending', 'Processing', 1, 13, '2026-03-04 12:47:41', 'In Progress'),
(54, 'BAT-20260304-0003', 10, '2026-03-04', 23.00, '2029-03-04', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '', 'Pending', 'Processing', 1, 13, '2026-03-04 12:48:15', 'In Progress'),
(55, 'BAT-20260304-0004', 41, '2026-03-04', 100.00, '2027-03-04', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '', 'Pending', 'Completed', 1, 36, '2026-03-04 13:05:24', 'Completed'),
(56, 'BAT-20260304-0005', 41, '2026-03-04', 100.00, '2027-03-04', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '', 'Pending', 'Completed', 1, 37, '2026-03-04 13:26:52', 'Completed'),
(57, 'BAT-20260304-0006', 24, '2026-03-04', 100.00, '2027-03-04', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '', 'Pending', 'Completed', 1, 37, '2026-03-04 13:26:52', 'Completed'),
(58, 'BAT-20260307-0001', 1, '2026-03-07', 200.00, '2028-03-07', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '', 'Pending', 'Completed', 1, 40, '2026-03-07 02:39:27', 'Completed'),
(59, 'BAT-20260308-0001', 10, '2026-03-08', 23.00, '2029-03-08', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '', 'Pending', 'Processing', 1, 13, '2026-03-08 06:37:19', 'In Progress'),
(60, 'BAT-20260308-0002', 26, '2026-03-08', 12.00, '2027-03-08', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '', 'Pending', 'Processing', 1, 35, '2026-03-08 06:56:33', 'In Progress'),
(61, 'BAT-20260308-0003', 24, '2026-03-08', 100.00, '2027-03-08', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '', 'Pending', 'Processing', 1, 39, '2026-03-08 06:58:58', 'In Progress');

-- --------------------------------------------------------

--
-- Table structure for table `production_requests`
--

CREATE TABLE `production_requests` (
  `request_id` int(11) NOT NULL,
  `sales_order_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `requested_qty` decimal(10,2) NOT NULL,
  `reason` enum('Customer Order','Low Stock','Custom Order') DEFAULT 'Customer Order',
  `status` enum('Pending','In Progress','For Inspection','Completed','Cancelled') DEFAULT 'Pending',
  `requested_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `priority` enum('Normal','High') NOT NULL DEFAULT 'Normal',
  `due_date` date DEFAULT NULL,
  `request_group_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_requests`
--

INSERT INTO `production_requests` (`request_id`, `sales_order_id`, `customer_name`, `product_id`, `requested_qty`, `reason`, `status`, `requested_by`, `created_at`, `updated_at`, `priority`, `due_date`, `request_group_id`) VALUES
(1, 1, 'TEST CUSTOMER', 1, 100.00, 'Customer Order', 'Completed', 1, '2026-02-15 17:00:37', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(2, NULL, 'sfggsd', 17, 34.00, 'Customer Order', 'Completed', 1, '2026-02-15 17:31:04', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(3, NULL, 'sfggsd', 17, 23.00, 'Customer Order', 'Completed', 1, '2026-02-15 17:31:51', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(4, NULL, 'dsfg', 17, 23.00, 'Customer Order', 'Completed', 1, '2026-02-15 17:35:09', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(5, NULL, 'dsfg', 15, 34.00, 'Customer Order', 'Completed', 1, '2026-02-15 17:44:51', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(6, NULL, 'dsfg', 15, 34.00, 'Customer Order', 'Completed', 1, '2026-02-15 17:51:32', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(7, NULL, 'test', 17, 23.00, 'Customer Order', 'Completed', 1, '2026-02-15 17:53:05', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(8, NULL, 'test2', 17, 23.00, 'Customer Order', 'Completed', 1, '2026-02-15 17:54:44', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(9, NULL, 'test2', 17, 23.00, 'Customer Order', 'Completed', 1, '2026-02-15 17:58:24', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(10, NULL, '2', 15, 3.00, 'Customer Order', 'Completed', 1, '2026-02-15 17:59:15', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(11, NULL, 'w', 17, 3.00, 'Customer Order', 'Completed', 1, '2026-02-15 18:09:00', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(12, NULL, 'sfggsd', 10, 23.00, 'Customer Order', 'Completed', 1, '2026-02-15 18:09:39', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(13, NULL, 'sfggsd', 10, 23.00, 'Customer Order', 'For Inspection', 1, '2026-02-15 18:16:25', '2026-03-08 06:37:19', 'Normal', NULL, NULL),
(14, NULL, '12', 17, 12.00, 'Customer Order', 'Completed', 1, '2026-02-15 18:18:37', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(15, NULL, 'sfggsd', 17, 2.00, 'Customer Order', 'Completed', 1, '2026-02-15 18:19:45', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(16, NULL, '34', 14, 34.00, 'Customer Order', 'Completed', 1, '2026-02-15 18:20:51', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(17, NULL, '34', 14, 34.00, 'Customer Order', 'Completed', 1, '2026-02-15 18:35:56', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(18, NULL, 'dsfg', 17, 23.00, 'Customer Order', 'Completed', 1, '2026-02-15 18:36:03', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(19, NULL, 'dsfg', 1, 1.00, '', 'Completed', 1, '2026-02-15 18:39:52', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(20, NULL, 'test24', 1, 1.00, '', 'Completed', 1, '2026-02-15 18:51:55', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(21, NULL, '3asd', 14, 34.00, 'Customer Order', 'Completed', 1, '2026-02-15 19:01:24', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(22, NULL, '3asd', 10, 56.00, 'Customer Order', 'Completed', 1, '2026-02-15 19:01:24', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(23, NULL, '4', 14, 7.00, 'Customer Order', 'Completed', 1, '2026-02-15 19:04:28', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(24, NULL, '4', 26, 45.00, 'Customer Order', 'Completed', 1, '2026-02-15 19:04:28', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(25, NULL, '3asd', 17, 343.00, 'Customer Order', 'Completed', 1, '2026-02-15 20:34:33', '2026-03-02 07:01:43', 'Normal', NULL, NULL),
(26, 0, 'asfsk', 12, 12.00, '', 'Completed', 1, '2026-02-26 09:58:02', '2026-03-02 07:01:43', 'Normal', NULL, 'PRG-20260226105802-ef36'),
(27, 0, 'test customer1', 26, 1000.00, '', 'Completed', 1, '2026-02-27 10:33:41', '2026-03-02 07:01:43', 'Normal', NULL, 'PRG-20260227113341-1123'),
(28, 0, 'test customer1', 26, 1300.00, '', 'Completed', 1, '2026-02-27 10:50:11', '2026-03-02 07:37:59', 'Normal', NULL, 'PRG-20260227115011-bd30'),
(29, 0, 'test customer1', 26, 12.00, '', 'Completed', 1, '2026-02-27 11:15:44', '2026-03-02 07:01:43', 'Normal', NULL, 'PRG-20260227121544-8c51'),
(30, 0, 'test customer1', 24, 12.00, '', 'Completed', 1, '2026-02-27 11:15:44', '2026-03-02 07:01:43', 'Normal', NULL, 'PRG-20260227121544-8c51'),
(31, 0, 'test customer1', 26, 123.00, '', 'Completed', 1, '2026-02-27 11:19:11', '2026-03-02 07:01:43', 'Normal', NULL, 'PRG-20260227121911-2da8'),
(32, 0, 'test customer1', 26, 123.00, '', 'Completed', 1, '2026-02-27 11:35:08', '2026-03-02 07:01:43', 'Normal', NULL, 'PRG-20260227123508-8d19'),
(33, 0, 'test2', 42, 100.00, '', 'Completed', 1, '2026-03-02 05:49:51', '2026-03-02 07:05:28', 'Normal', NULL, 'PRG-20260302064951-d4db'),
(34, 0, 'test reserve', 32, 100.00, '', 'Completed', 1, '2026-03-04 11:08:31', '2026-03-04 11:10:55', 'Normal', NULL, 'PRG-20260304120831-830b'),
(35, 0, 'test reserve', 26, 12.00, '', 'For Inspection', 1, '2026-03-04 12:54:45', '2026-03-08 06:56:33', 'Normal', NULL, 'PRG-20260304135445-1c90'),
(36, 0, 'test reserve', 41, 100.00, '', 'Completed', 1, '2026-03-04 13:04:53', '2026-03-04 13:07:16', 'Normal', NULL, 'PRG-20260304140453-7c51'),
(37, 0, 'test reserve', 41, 100.00, '', 'Completed', 1, '2026-03-04 13:26:29', '2026-03-04 13:28:34', 'Normal', NULL, 'PRG-20260304142629-b958'),
(38, 0, 'test reserve', 24, 100.00, '', 'Completed', 1, '2026-03-04 13:26:29', '2026-03-04 13:28:34', 'Normal', NULL, 'PRG-20260304142629-b958'),
(39, 0, 'test customer1', 24, 100.00, '', 'For Inspection', 1, '2026-03-07 02:33:18', '2026-03-08 06:58:58', 'Normal', NULL, 'PRG-20260307033318-b24f'),
(40, 0, 'maam c', 1, 200.00, '', 'Completed', 1, '2026-03-07 02:37:04', '2026-03-07 02:40:55', 'Normal', NULL, 'PRG-20260307033704-3318');

-- --------------------------------------------------------

--
-- Table structure for table `production_settings`
--

CREATE TABLE `production_settings` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_settings`
--

INSERT INTO `production_settings` (`id`, `product_id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(47, 1, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(48, 2, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(49, 3, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(50, 4, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(51, 5, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(52, 6, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(53, 21, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(54, 28, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(55, 29, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(56, 30, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(57, 31, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(58, 32, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(59, 33, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(60, 34, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(61, 35, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(62, 39, 'expiry_value', '36', 'Vinegar - 36 months shelf life', '2026-03-02 06:55:02'),
(63, 40, 'expiry_value', '24', 'Fish Sauce - 24 months shelf life', '2026-03-02 06:55:02'),
(78, 1, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(79, 2, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(80, 3, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(81, 4, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(82, 5, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(83, 6, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(84, 21, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(85, 28, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(86, 29, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(87, 30, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(88, 31, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(89, 32, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(90, 33, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(91, 34, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(92, 35, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(93, 39, 'expiry_unit', 'months', 'Vinegar - Unit', '2026-03-02 06:55:02'),
(94, 40, 'expiry_unit', 'months', 'Fish Sauce - Unit', '2026-03-02 06:55:02'),
(109, 7, 'expiry_value', '36', 'Soy Sauce - 36 months shelf life', '2026-03-02 06:55:02'),
(110, 8, 'expiry_value', '36', 'Soy Sauce - 36 months shelf life', '2026-03-02 06:55:02'),
(111, 9, 'expiry_value', '36', 'Soy Sauce - 36 months shelf life', '2026-03-02 06:55:02'),
(116, 7, 'expiry_unit', 'months', 'Soy Sauce - Unit', '2026-03-02 06:55:02'),
(117, 8, 'expiry_unit', 'months', 'Soy Sauce - Unit', '2026-03-02 06:55:02'),
(118, 9, 'expiry_unit', 'months', 'Soy Sauce - Unit', '2026-03-02 06:55:02'),
(123, 10, 'expiry_value', '36', 'Vinegar - 36 months shelf life', '2026-03-02 06:55:02'),
(124, 11, 'expiry_value', '36', 'Vinegar - 36 months shelf life', '2026-03-02 06:55:02'),
(125, 12, 'expiry_value', '36', 'Vinegar - 36 months shelf life', '2026-03-02 06:55:02'),
(126, 23, 'expiry_value', '36', 'Vinegar - 36 months shelf life', '2026-03-02 06:55:02'),
(127, 36, 'expiry_value', '36', 'Vinegar - 36 months shelf life', '2026-03-02 06:55:02'),
(128, 37, 'expiry_value', '36', 'Vinegar - 36 months shelf life', '2026-03-02 06:55:02'),
(129, 38, 'expiry_value', '36', 'Vinegar - 36 months shelf life', '2026-03-02 06:55:02'),
(138, 10, 'expiry_unit', 'months', 'Vinegar - Unit', '2026-03-02 06:55:02'),
(139, 11, 'expiry_unit', 'months', 'Vinegar - Unit', '2026-03-02 06:55:02'),
(140, 12, 'expiry_unit', 'months', 'Vinegar - Unit', '2026-03-02 06:55:02'),
(141, 23, 'expiry_unit', 'months', 'Vinegar - Unit', '2026-03-02 06:55:02'),
(142, 36, 'expiry_unit', 'months', 'Vinegar - Unit', '2026-03-02 06:55:02'),
(143, 37, 'expiry_unit', 'months', 'Vinegar - Unit', '2026-03-02 06:55:02'),
(144, 38, 'expiry_unit', 'months', 'Vinegar - Unit', '2026-03-02 06:55:02'),
(153, 17, 'expiry_value', '24', 'Bagoong - 24 months shelf life', '2026-03-02 06:55:02'),
(154, 17, 'expiry_unit', 'months', 'Bagoong - Unit', '2026-03-02 06:55:03'),
(155, 13, 'expiry_value', '365', 'Default: Lorins Budget / Value Pack', '2026-03-02 06:55:03'),
(156, 14, 'expiry_value', '365', 'Default: Lorins Alamang Guisado Original 8 oz / 250 g', '2026-03-02 06:55:03'),
(157, 15, 'expiry_value', '365', 'Default: Lorins Alamang Guisado Sweet', '2026-03-02 06:55:03'),
(158, 16, 'expiry_value', '365', 'Default: Lorins Alamang Guisado Spicy', '2026-03-02 06:55:03'),
(159, 18, 'expiry_value', '365', 'Default: Lorins Crab Paste 8 oz', '2026-03-02 06:55:03'),
(160, 19, 'expiry_value', '365', 'Default: Lorins Coconut Milk 400 mL', '2026-03-02 06:55:03'),
(161, 20, 'expiry_value', '365', 'Default: Lorins Premium Extra-Virgin Anchovy Extract 200 mL', '2026-03-02 06:55:03'),
(162, 24, 'expiry_value', '365', 'Default: Filtaste Nata de Coco 12 oz', '2026-03-02 06:55:03'),
(163, 26, 'expiry_value', '365', 'Default: Filtaste Kaong 12 oz', '2026-03-02 06:55:03'),
(164, 41, 'expiry_value', '365', 'Default: Filtaste Nata de Coco 32 oz', '2026-03-02 06:55:03'),
(165, 42, 'expiry_value', '365', 'Default: Filtaste Kaong 32 oz', '2026-03-02 06:55:03'),
(170, 13, 'expiry_unit', 'days', 'Default unit', '2026-03-02 06:55:03'),
(171, 14, 'expiry_unit', 'days', 'Default unit', '2026-03-02 06:55:03'),
(172, 15, 'expiry_unit', 'days', 'Default unit', '2026-03-02 06:55:03'),
(173, 16, 'expiry_unit', 'days', 'Default unit', '2026-03-02 06:55:03'),
(174, 18, 'expiry_unit', 'days', 'Default unit', '2026-03-02 06:55:03'),
(175, 19, 'expiry_unit', 'days', 'Default unit', '2026-03-02 06:55:03'),
(176, 20, 'expiry_unit', 'days', 'Default unit', '2026-03-02 06:55:03'),
(177, 24, 'expiry_unit', 'days', 'Default unit', '2026-03-02 06:55:03'),
(178, 26, 'expiry_unit', 'days', 'Default unit', '2026-03-02 06:55:03'),
(179, 41, 'expiry_unit', 'days', 'Default unit', '2026-03-02 06:55:03'),
(180, 42, 'expiry_unit', 'days', 'Default unit', '2026-03-02 06:55:03');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `unit` varchar(20) DEFAULT 'pcs',
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fermentation_eligible` tinyint(1) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shelf_life_days` int(11) NOT NULL DEFAULT 365 COMMENT 'Number of days product remains shelf-stable after production. Used for automatic expiry date calculation.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `description`, `image_path`, `unit`, `category_id`, `created_at`, `fermentation_eligible`, `unit_price`, `shelf_life_days`) VALUES
(1, 'Lorins Patis Flavor 150 mL pouch', 'Lorins Patis Flavor 150 mL pouch', 'patis-POUCH-150ML.webp', 'pcs', 1, '2026-02-01 05:25:06', 1, 11.27, 730),
(2, 'Lorins Patis Flavor 350 mL PET bottle', 'Lorins Patis Flavor 350 mL PET bottle', 'PATIS-350ML-PETbottle.webp', 'pcs', 1, '2026-02-01 05:25:06', 1, 22.67, 730),
(3, 'Lorins Patis Flavor with Chili 350 mL PET bottle', 'Lorins Patis Flavor with Chili 350 mL PET bottle', 'CHILI-PATIS-350ML-PETbottle.webp', 'pcs', 8, '2026-02-01 05:25:06', 1, 28.31, 730),
(4, 'Lorins Patis Flavor 1 L', 'Lorins Patis Flavor 1 Liter', 'PATIS-1LITER.webp', 'pcs', 1, '2026-02-01 05:25:06', 1, 68.91, 730),
(5, 'Lorins Patis Flavor 1893 mL (Half Gallon)', 'Lorins Patis Flavor 1893 mL (Half Gallon)', 'PATIS-HALF-GALLON.webp', 'pcs', 1, '2026-02-01 05:25:06', 1, 126.60, 730),
(6, 'Lorins Patis Flavor 3785 mL (Gallon)', 'Lorins Patis Flavor 3785 mL (Gallon)', 'PATIS-1GALLON.webp', 'pcs', 1, '2026-02-01 05:25:06', 1, 221.60, 730),
(7, 'Lorins Soy Sauce 350 mL PET bottle', 'Lorins Soy Sauce 350 mL PET bottle', 'SOY-SAUCE-350ML.webp', 'pcs', 2, '2026-02-01 05:25:06', 1, 19.89, 1095),
(8, 'Lorins Soy Sauce 1 L', 'Lorins Soy Sauce 1 Liter', 'SOY-SAUCE-1LITER.webp', 'pcs', 2, '2026-02-01 05:25:06', 1, 51.63, 1095),
(9, 'Lorins Soy Sauce 3785 mL (Gallon)', 'Lorins Soy Sauce 3785 mL (Gallon)', 'SOY-SAUCE-1GALLON.webp', 'pcs', 2, '2026-02-01 05:25:06', 1, 176.76, 1095),
(10, 'Lorins Coco Suka 150 mL', 'Lorins Coco Suka 150 mL', 'COCO-SUKA-150ML.webp', 'pcs', 3, '2026-02-01 05:25:06', 1, 44.89, 365),
(11, 'Lorins Coco Suka 310 mL', 'Lorins Coco Suka 310 mL', 'COCO-SUKA-310ML.webp', 'pcs', 3, '2026-02-01 05:25:06', 1, 68.25, 365),
(12, 'Lorins Coco Suka 800 mL', 'Lorins Coco Suka 800 mL', 'COCO-SUKA-800ML.webp', 'pcs', 3, '2026-02-01 05:25:06', 1, 156.80, 365),
(13, 'Lorins Budget / Value Pack', 'Lorins Budget / Value Pack (Vinegar + Fish Sauce + Soy Sauce)', 'BUDGET-PACK(vinegar, fishsauce-and-soysauce).webp', 'pcs', 3, '2026-02-01 05:25:06', 1, 56.20, 730),
(14, 'Lorins Alamang Guisado Original 8 oz / 250 g', 'Lorins Alamang Guisado Original 8 oz / 250 g', 'ALAMANG-GUISADO-ORIGINAL-8oz.webp', 'pcs', 4, '2026-02-01 05:25:06', 1, 95.08, 365),
(15, 'Lorins Alamang Guisado Sweet', 'Lorins Alamang Guisado Sweet', 'ALAMANG-GUISADO-SWEET-8oz.webp', 'pcs', 4, '2026-02-01 05:25:06', 1, 95.08, 365),
(16, 'Lorins Alamang Guisado Spicy', 'Lorins Alamang Guisado Spicy', 'ALAMANG-GUISADO-SPICY-8oz.webp', 'pcs', 4, '2026-02-01 05:25:06', 1, 95.08, 365),
(17, 'Lorenzana Bagoong Isda Original 310 mL', 'Lorenzana Bagoong Isda Original 310 mL', 'BAGOONG-ISDA-original-310ML.webp', 'pcs', 5, '2026-02-01 05:25:06', 1, 38.79, 730),
(18, 'Lorins Crab Paste 8 oz', 'Lorins Crab Paste 8 oz', 'CRAB-PASTE-8oz.webp', 'pcs', 6, '2026-02-01 05:25:06', 1, 169.22, 730),
(19, 'Lorins Coconut Milk 400 mL', 'Lorins Coconut Milk 400 mL tin', 'COCONUT-MILK.webp', 'pcs', 6, '2026-02-01 05:25:06', 1, 67.81, 365),
(20, 'Lorins Premium Extra-Virgin Anchovy Extract 200 mL', 'Lorins Premium Extra-Virgin Anchovy Extract 200 mL', 'PREMIUM-extra-virgin-anchovy-ectract-200ML.webp', 'pcs', 7, '2026-02-01 05:25:06', 1, 57.49, 365),
(21, 'Lorins Fish Sauce 800 mL glass bottle', 'Lorins Fish Sauce 800 mL glass bottle', 'patis-800ML.webp', 'pcs', 8, '2026-02-01 05:25:06', 1, 92.42, 730),
(23, 'Lorins Coco Suka Spicy-Sweet 310 mL', 'Lorins Coco Suka Spicy-Sweet 310 mL', 'COCO-SUKA-310ML.webp', 'pcs', 8, '2026-02-01 05:25:06', 1, 68.25, 365),
(24, 'Filtaste Nata de Coco 12 oz', 'Filtaste Nata de Coco 12 oz', 'NATA-DE-COCO-12OZ.webp', 'pcs', 9, '2026-02-01 06:38:24', 1, 60.00, 365),
(26, 'Filtaste Kaong 12 oz', 'Filtaste Kaong 12 oz', 'KAONG-12OZ.webp', 'pcs', 9, '2026-02-01 06:38:24', 1, 85.00, 365),
(28, 'Lorins Patis Puro 150 mL', 'Lorins Patis Puro 150 mL', 'patis-PURO-150ML.webp', 'pcs', 1, '2026-02-01 06:38:24', 1, 33.60, 730),
(29, 'Lorins Patis Puro 310 mL', 'Lorins Patis Puro 310 mL', 'patis-PURO-310ML.webp', 'pcs', 1, '2026-02-01 06:38:24', 1, 48.83, 730),
(30, 'Lorins Patis Puro Chili Mansi 150 mL', 'Lorins Patis Puro Chili Mansi 150 mL', 'patis-puro-CHILIMANSI-150ML.webp', 'pcs', 8, '2026-02-01 06:38:24', 1, 37.70, 730),
(31, 'Lorins Patis Puro Chili Mansi 310 mL', 'Lorins Patis Puro Chili Mansi 310 mL', 'patis-puro-CHILIMANSI-310ML.webp', 'pcs', 8, '2026-02-01 06:38:24', 1, 57.09, 730),
(32, 'Lorins Patis Puro Mansi 150 mL', 'Lorins Patis Puro Mansi 150 mL', 'patis-PURO-MANSI-150ML.webp', 'pcs', 8, '2026-02-01 06:38:24', 1, 36.33, 730),
(33, 'Lorins Patis Flavor 7+1 Tipid Pouch', 'Lorins Patis Flavor 7+1 Tipid Pouch', 'Lorins-patis-7+1(patis-flavor-tipidpouch).webp', 'pcs', 1, '2026-02-01 06:38:24', 1, 78.79, 730),
(34, 'Lorins Patis Twin Pack 1L x 2', 'Lorins Patis Twin Pack 1 Liter x 2', 'patis-TWINPACK(1litterx2).webp', 'pcs', 1, '2026-02-01 06:38:24', 1, 131.01, 730),
(35, 'Lorins Patis Pouch 350 mL', 'Lorins Patis Pouch 350 mL', 'patis-POUCH-350ML.webp', 'pcs', 1, '2026-02-01 06:38:24', 1, 20.66, 730),
(36, 'Lorins Vinegar 350 mL', 'Lorins Vinegar 350 mL', 'VINEGAR-350ML.webp', 'pcs', 3, '2026-02-01 06:38:24', 1, 17.24, 1095),
(37, 'Lorins Vinegar 1 L', 'Lorins Vinegar 1 Liter', 'VINEGAR-1LITER.webp', 'pcs', 3, '2026-02-01 06:38:24', 1, 40.94, 1095),
(38, 'Lorins Vinegar 3785 mL (Gallon)', 'Lorins Vinegar Gallon', 'VINEGAR-1GALLON.webp', 'pcs', 3, '2026-02-01 06:38:24', 1, 151.03, 1095),
(39, 'Lorins Value Pack (Soy Sauce + Vinegar + Free Patis Pouch)', 'Lorins Value Pack with free patis pouch', 'BUDGET-PACK(vinegar, fishsauce-and-soysauce).webp', 'pcs', 3, '2026-02-01 06:38:24', 1, 91.68, 730),
(40, 'Lorins Patis Puro 800 mL', 'Lorins Patis Puro 800 mL', 'Lorins_Patis_Puro_800_mL_1772103212.webp', 'pcs', 1, '2026-02-26 09:42:57', 1, 92.42, 730),
(41, 'Filtaste Nata de Coco 32 oz', 'Filtaste Nata de Coco 32 oz', 'NATA-DE-COCO-32OZ.webp', 'pcs', 9, '2026-02-26 09:42:57', 1, 124.53, 365),
(42, 'Filtaste Kaong 32 oz', 'Filtaste Kaong 32 oz', 'KAONG-32OZ.webp', 'pcs', 9, '2026-02-26 09:42:57', 1, 163.77, 365);

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`category_id`, `category_name`, `description`, `created_at`) VALUES
(1, 'Patis (Fish Sauce)', 'Fish sauce products under Lorins Patis Flavor brand', '2026-02-01 05:25:06'),
(2, 'Soy Sauce', 'Soy sauce products under Lorins brand', '2026-02-01 05:25:06'),
(3, 'Vinegar', 'Vinegar products including Coco Suka and value packs', '2026-02-01 05:25:06'),
(4, 'Alamang (Shrimp Paste)', 'Sauteed shrimp paste products', '2026-02-01 05:25:06'),
(5, 'Bagoong', 'Fermented fish products', '2026-02-01 05:25:06'),
(6, 'Specialty Products', 'Specialty items like crab paste and coconut milk', '2026-02-01 05:25:06'),
(7, 'Premium Products', 'Premium and extra-virgin products', '2026-02-01 05:25:06'),
(8, 'Variants', 'Special variants and limited editions', '2026-02-01 05:25:06'),
(9, 'Nata de Coco & Kaong', 'Nata de coco and kaong products', '2026-02-01 06:38:24');

-- --------------------------------------------------------

--
-- Table structure for table `product_category_materials`
--

CREATE TABLE `product_category_materials` (
  `category_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_category_materials`
--

INSERT INTO `product_category_materials` (`category_id`, `material_id`) VALUES
(1, 9),
(1, 10),
(1, 14),
(1, 20),
(1, 23),
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(1, 28),
(1, 29),
(1, 30),
(1, 31),
(1, 33),
(1, 35),
(2, 14),
(2, 16),
(2, 20),
(2, 23),
(2, 24),
(2, 25),
(2, 26),
(2, 27),
(2, 28),
(2, 30),
(2, 31),
(2, 33),
(2, 35),
(3, 14),
(3, 20),
(3, 27),
(3, 28),
(3, 30),
(3, 31),
(4, 12),
(4, 13),
(4, 14),
(4, 20),
(4, 23),
(4, 24),
(4, 25),
(4, 26),
(4, 27),
(4, 28),
(4, 29),
(4, 30),
(4, 31),
(4, 33),
(4, 35),
(5, 9),
(5, 10),
(5, 14),
(5, 20),
(5, 23),
(5, 24),
(5, 25),
(5, 26),
(5, 27),
(5, 28),
(5, 29),
(5, 30),
(5, 31),
(5, 33),
(5, 35),
(6, 14),
(6, 20),
(6, 23),
(6, 24),
(6, 25),
(6, 27),
(6, 28),
(6, 29),
(6, 30),
(6, 31),
(6, 33),
(6, 35),
(7, 9),
(7, 10),
(7, 14),
(7, 20),
(7, 23),
(7, 24),
(7, 25),
(7, 26),
(7, 27),
(7, 28),
(7, 30),
(7, 31),
(7, 33),
(7, 35),
(8, 9),
(8, 10),
(8, 14),
(8, 17),
(8, 18),
(8, 20),
(8, 23),
(8, 24),
(8, 25),
(8, 26),
(8, 27),
(8, 28),
(8, 29),
(8, 30),
(8, 31),
(8, 33),
(8, 35),
(9, 14),
(9, 20),
(9, 23),
(9, 24),
(9, 27),
(9, 28),
(9, 30),
(9, 31),
(9, 33),
(9, 35);

-- --------------------------------------------------------

--
-- Table structure for table `pr_items`
--

CREATE TABLE `pr_items` (
  `pr_item_id` int(11) NOT NULL,
  `pr_id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_type` enum('Raw Material','Product','Other') DEFAULT 'Raw Material',
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) DEFAULT 'kg',
  `estimated_unit_price` decimal(12,2) DEFAULT NULL,
  `estimated_total` decimal(12,2) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_items`
--

INSERT INTO `pr_items` (`pr_item_id`, `pr_id`, `material_id`, `product_id`, `item_name`, `item_type`, `quantity`, `unit`, `estimated_unit_price`, `estimated_total`, `notes`) VALUES
(1, 1, NULL, NULL, 'bulb lights', '', 123.00, '0', 34.00, 4182.00, NULL),
(2, 2, NULL, NULL, '2we', '', 23.00, '0', 23.00, 529.00, NULL),
(3, 3, NULL, NULL, 'plastic bags', '', 200.00, '0', 20.00, 4000.00, NULL),
(4, 4, NULL, NULL, 'plastic bags', '', 23.00, '0', 121.00, 2783.00, NULL),
(5, 5, NULL, NULL, 'plastic bags', '', 12.00, '0', 12.00, 144.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `po_id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `pr_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `status` enum('Open','Partially Received','Received','Closed','Cancelled') DEFAULT 'Open',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`po_id`, `po_number`, `pr_id`, `supplier_id`, `order_date`, `expected_delivery_date`, `delivery_address`, `payment_terms`, `status`, `subtotal`, `tax_amount`, `total_amount`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PO-20260226-0001', 1, 2, '2026-02-26', '2026-03-12', '', 'Net 30', 'Partially Received', 0.00, 0.00, 0.00, '', 1, '2026-02-26 10:12:26', '2026-02-26 10:13:34'),
(2, 'PO-20260227-0001', 2, 3, '2026-02-27', '2026-03-12', '', 'Net 30', 'Received', 529.00, 0.00, 529.00, '', 1, '2026-02-27 09:25:39', '2026-02-27 09:25:53'),
(3, 'PO-20260301-0001', 3, 4, '2026-03-01', '2026-04-09', '', 'Net 30', 'Received', 0.00, 0.00, 0.00, 'asdf', 1, '2026-03-01 13:29:50', '2026-03-01 13:30:14'),
(4, 'PO-20260301-0002', 3, 4, '2026-03-01', '2026-04-09', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', '', 'Received', 40000.00, 0.00, 40000.00, '', 1, '2026-03-01 13:41:32', '2026-03-01 13:41:40'),
(5, 'PO-20260304-0001', 4, 5, '2026-03-04', '2026-03-12', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 'Cash on Delivery', 'Received', 0.00, 0.00, 0.00, '', 10, '2026-03-04 11:39:45', '2026-03-04 11:39:50');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

CREATE TABLE `purchase_requests` (
  `pr_id` int(11) NOT NULL,
  `pr_number` varchar(50) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) DEFAULT 'kg',
  `expected_delivery_date` date DEFAULT NULL,
  `status` enum('Pending','Approved','Ordered','Delivered','Cancelled') DEFAULT 'Pending',
  `requested_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_requests`
--

INSERT INTO `purchase_requests` (`pr_id`, `pr_number`, `supplier_id`, `item_name`, `quantity`, `unit`, `expected_delivery_date`, `status`, `requested_by`, `created_at`) VALUES
(1, 'PR-20260206-0001', 2, 'suka', 12.00, 'liters', '0000-00-00', 'Pending', 1, '2026-02-06 10:56:27'),
(2, 'PR-20260206-0002', 3, 'toyo', 13.00, 'pcs', '2026-02-06', 'Pending', 1, '2026-02-06 10:57:17'),
(3, 'PR-20260207-0001', 2, 'suka', 1.00, 'liters', '2026-02-07', 'Pending', 1, '2026-02-07 03:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requisitions`
--

CREATE TABLE `purchase_requisitions` (
  `pr_id` int(11) NOT NULL,
  `pr_number` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `requested_by` int(11) NOT NULL,
  `request_date` date NOT NULL,
  `required_date` date DEFAULT NULL,
  `justification` text DEFAULT NULL,
  `status` enum('Draft','Submitted','Approved','Rejected','Cancelled') DEFAULT 'Draft',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `total_estimated_cost` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_requisitions`
--

INSERT INTO `purchase_requisitions` (`pr_id`, `pr_number`, `department`, `requested_by`, `request_date`, `required_date`, `justification`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `total_estimated_cost`, `created_at`, `updated_at`) VALUES
(1, 'PR-20260226-0001', 'Production / Operations', 1, '2026-02-26', '2026-03-12', 'asdfas', 'Approved', 1, '2026-02-26 10:04:46', NULL, 4182.00, '2026-02-26 10:04:24', '2026-02-26 10:04:46'),
(2, 'PR-20260227-0001', 'Marketing', 1, '2026-02-27', '2026-03-12', 'aasd', 'Approved', 1, '2026-02-27 09:25:23', NULL, 529.00, '2026-02-27 09:25:22', '2026-02-27 09:25:23'),
(3, 'PR-20260301-0001', 'Production / Operations', 1, '2026-04-11', '2026-04-09', 'for production', 'Approved', 1, '2026-03-01 13:24:59', NULL, 4000.00, '2026-03-01 13:24:57', '2026-03-01 13:24:59'),
(4, 'PR-20260304-0001', 'Inventory / Warehouse', 10, '2026-03-04', '2026-03-12', 'qased', 'Approved', 10, '2026-03-04 11:39:25', NULL, 2783.00, '2026-03-04 11:39:24', '2026-03-04 11:39:25'),
(5, 'PR-20260304-0002', 'Inventory / Warehouse', 1, '2026-03-04', '2026-04-11', '12', 'Submitted', NULL, NULL, NULL, 144.00, '2026-03-04 11:47:40', '2026-03-04 11:47:40');

-- --------------------------------------------------------

--
-- Table structure for table `qc_records`
--

CREATE TABLE `qc_records` (
  `qc_id` int(11) NOT NULL,
  `qc_number` varchar(50) NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `inspector_name` varchar(100) NOT NULL,
  `inspection_date` date NOT NULL,
  `test_result` enum('Pending','Passed','Failed') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `non_conformance_details` text DEFAULT NULL,
  `corrective_action` text DEFAULT NULL,
  `approval_status` enum('For Re-inspection','Approved','Rejected') DEFAULT 'For Re-inspection',
  `inspected_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qc_records`
--

INSERT INTO `qc_records` (`qc_id`, `qc_number`, `batch_number`, `inspector_name`, `inspection_date`, `test_result`, `remarks`, `non_conformance_details`, `corrective_action`, `approval_status`, `inspected_by`, `created_at`) VALUES
(1, 'QC-20260216-0022', 'BAT-20260206-0004', 'system admin', '2026-02-16', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-02-16 08:56:22'),
(2, 'QC-20260216-0023', 'BAT-20260216-0003', 'test qc', '2026-02-16', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-02-16 08:57:48'),
(3, 'QC-20260216-0024', 'BAT-20260206-0002', 'test qc', '2026-02-16', 'Failed', NULL, 'Contamination', 'Reprocess', 'Rejected', 1, '2026-02-16 08:58:04'),
(8, 'QC-20260216-0032', '', 'test qc', '2026-02-16', 'Passed', '', 'None', 'None', 'Approved', 1, '2026-02-16 09:18:04'),
(9, 'QC-20260216-0033', '', 'me', '2026-02-16', 'Passed', '', 'None', 'None', 'Approved', 1, '2026-02-16 09:18:24'),
(10, 'QC-20260216-0034', '', 'test qc', '2026-02-16', 'Failed', '', 'Wrong Label', 'Discard', 'Rejected', 1, '2026-02-16 09:21:02'),
(11, 'QC-20260216-0035', '', 'test qc', '2026-02-16', 'Pending', '', 'Wrong Label', 'Discard', 'For Re-inspection', 1, '2026-02-16 09:25:13'),
(12, 'QC-20260216-0036', 'BAT-20260216-0002', 'test qc', '2026-02-16', 'Pending', NULL, 'Contamination', 'Reprocess', 'For Re-inspection', 1, '2026-02-16 09:49:57'),
(13, 'QC-20260216-0037', 'BAT-20260216-0002', 'test qc', '2026-02-16', 'Pending', NULL, 'Contamination', 'Reprocess', 'For Re-inspection', 1, '2026-02-16 09:56:45'),
(14, 'QC-20260216-0038', 'BAT-20260216-0002', 'test qc', '2026-02-16', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-02-16 09:57:00'),
(15, 'QC-20260216-0039', '98', 'test qc', '2026-02-16', 'Pending', NULL, 'Contamination', 'Reprocess', 'For Re-inspection', 1, '2026-02-16 09:57:16'),
(16, 'QC-20260216-0040', '98', 'test qc', '2026-02-16', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-02-16 09:57:27'),
(17, 'QC-20260216-0041', 'BAT-20260216-0001', 'test qc', '2026-02-16', 'Pending', NULL, 'Contamination', 'Reprocess', 'For Re-inspection', 1, '2026-02-16 10:09:20'),
(18, 'QC-20260216-0042', 'BAT-20260216-0004', 'test qc', '2026-02-16', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-02-16 10:55:24'),
(19, 'QC-20260216-0043', 'BAT-20260216-0006', 'test qc', '2026-02-16', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-02-16 11:21:09'),
(20, 'QC-20260226-0001', 'BAT-20260226-0001', 'test qc', '2026-02-26', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-02-26 10:01:11'),
(21, 'QC-20260227-0003', 'BAT-20260215-0001', 'test qc', '2026-02-27', 'Passed', NULL, 'Minor Deviation (Acceptable)', 'Release for Distribution', 'Approved', 1, '2026-02-27 10:24:12'),
(22, 'QC-20260227-0005', 'BAT-20260216-0008', 'test qc', '2026-02-27', 'Passed', NULL, 'None', 'Release for Distribution', 'For Re-inspection', 1, '2026-02-27 10:31:48'),
(23, 'QC-20260227-0006', 'BAT-20260216-0008', 'test qc', '2026-02-27', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-02-27 10:32:16'),
(24, 'QC-20260227-0007', 'BAT-20260227-0019', 'test qc', '2026-02-27', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-02-27 11:36:06'),
(25, 'QC-20260227-0008', 'BAT-20260227-0014', 'test qc', '2026-02-27', 'Failed', NULL, 'Contamination', 'Reprocess', 'Rejected', 1, '2026-02-27 11:48:48'),
(26, 'QC-20260301-0001', 'BAT-20260227-0021', 'test qc', '2026-03-01', 'Failed', NULL, 'Contamination', 'Reprocess', 'Rejected', 1, '2026-03-01 09:50:54'),
(27, 'QC-20260301-0002', 'BAT-20260227-0017', 'test qc', '2026-03-01', 'Failed', NULL, 'Contamination', 'Reprocess', 'Rejected', 1, '2026-03-01 09:51:30'),
(28, 'QC-20260301-0003', 'BAT-20260301-0001', 'test qc', '2026-03-01', 'Failed', NULL, 'Contamination', 'Reprocess', 'Rejected', 1, '2026-03-01 09:53:02'),
(29, 'QC-20260301-0004', 'BAT-20260301-0001', 'test qc', '2026-03-01', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-03-01 09:54:04'),
(30, 'QC-20260301-0007', 'BAT-20260227-0013', 'test qc', '2026-03-01', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-03-01 15:31:54'),
(31, 'QC-20260302-0001', 'BAT-20260302-0001', 'test qc', '2026-03-02', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-03-02 04:49:58'),
(32, 'QC-20260302-0002', 'BAT-20260302-0002', 'test qc', '2026-03-02', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-03-02 07:05:28'),
(33, 'QC-20260302-0003', 'BAT-20260302-0012', 'test qc', '2026-03-02', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-03-02 07:37:59'),
(34, 'QC-20260302-0004', 'BAT-20260302-0013', 'test qc', '2026-03-02', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-03-02 08:03:53'),
(35, 'QC-20260302-0005', 'BAT-20260302-0014', 'test qc', '2026-03-02', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-03-02 08:05:55'),
(36, 'QC-20260304-0001', 'BAT-20260304-0001', 'test qc', '2026-03-04', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-03-04 11:10:55'),
(37, 'QC-20260304-0003', 'BAT-20260304-0004', 'test qc', '2026-03-04', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-03-04 13:07:16'),
(38, 'QC-20260304-0004', 'BAT-20260304-0005', 'test qc', '2026-03-04', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-03-04 13:28:16'),
(39, 'QC-20260304-0005', 'BAT-20260304-0006', 'test qc', '2026-03-04', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-03-04 13:28:34'),
(40, 'QC-20260307-0001', 'BAT-20260307-0001', 'test qc', '2026-03-07', 'Passed', NULL, 'None', 'None', 'Approved', 1, '2026-03-07 02:40:55');

-- --------------------------------------------------------

--
-- Table structure for table `qc_rules`
--

CREATE TABLE `qc_rules` (
  `rule_id` int(11) NOT NULL,
  `rule_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `rule_type` enum('Quantity','Expiry','Custom') DEFAULT 'Quantity',
  `condition_field` varchar(50) DEFAULT NULL,
  `operator` enum('>','<','>=','<=','==','!=') DEFAULT '>=',
  `threshold_value` decimal(10,2) DEFAULT NULL,
  `action` enum('Auto Pass','Auto Fail','Flag Conditional') DEFAULT 'Flag Conditional',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qc_rules`
--

INSERT INTO `qc_rules` (`rule_id`, `rule_name`, `category`, `rule_type`, `condition_field`, `operator`, `threshold_value`, `action`, `is_active`, `created_at`) VALUES
(1, 'Quantity Check - 90%', 'All', 'Quantity', 'quantity_received', '>=', 90.00, 'Auto Pass', 1, '2026-02-26 09:53:42'),
(2, 'Quantity Check - Below 90%', 'All', 'Quantity', 'quantity_received', '<', 90.00, 'Flag Conditional', 1, '2026-02-26 09:53:42'),
(3, 'Expiry Check - Less than 1 month', 'All', 'Expiry', 'days_to_expiry', '<', 30.00, 'Flag Conditional', 1, '2026-02-26 09:53:42'),
(4, 'Expiry Check - Expired', 'All', 'Expiry', 'days_to_expiry', '<', 0.00, 'Auto Fail', 1, '2026-02-26 09:53:42');

-- --------------------------------------------------------

--
-- Table structure for table `qc_settings`
--

CREATE TABLE `qc_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qc_settings`
--

INSERT INTO `qc_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'min_pass_score', '85', 'Minimum pass score percentage', '2026-03-01 15:17:27'),
(2, 'mandatory_fields', 'Appearance,Weight,Seal', 'Mandatory inspection fields', '2026-03-01 15:17:27'),
(3, 'auto_reject', '1', 'Auto-reject below pass score (1=enabled, 0=disabled)', '2026-03-01 15:17:27');

-- --------------------------------------------------------

--
-- Table structure for table `raw_materials`
--

CREATE TABLE `raw_materials` (
  `material_id` int(11) NOT NULL,
  `material_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(20) DEFAULT 'kg',
  `expiry_date` date DEFAULT NULL,
  `warehouse_location` varchar(100) DEFAULT NULL,
  `min_stock_level` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `material_code` varchar(50) DEFAULT NULL,
  `preferred_supplier_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `raw_materials`
--

INSERT INTO `raw_materials` (`material_id`, `material_name`, `category`, `quantity`, `unit`, `expiry_date`, `warehouse_location`, `min_stock_level`, `created_at`, `updated_at`, `material_code`, `preferred_supplier_id`) VALUES
(1, 'Soybeans', 'Raw Material', 100.00, 'kg', '2026-04-02', '', 100.00, '2026-02-01 05:25:06', '2026-03-02 07:35:57', NULL, NULL),
(2, 'Salt', 'Label Materials', 187.00, 'kg', NULL, '', 500.00, '2026-02-01 05:25:06', '2026-03-08 07:02:14', NULL, NULL),
(3, 'Sugar', 'Raw Material', 100.00, 'kg', NULL, NULL, 50.00, '2026-02-01 05:25:06', '2026-03-02 07:35:57', NULL, NULL),
(6, 'Tape', 'Packaging', 99.00, 'pcs', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 100.00, '2026-02-26 10:01:55', '2026-03-07 02:39:27', 'MAT-20260226-0001', NULL),
(9, 'Fermented fish', 'Seafood & Protein Sources', 100.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 10.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(10, 'Fish extract', 'Seafood & Protein Sources', 100.00, 'liters', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 5.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(11, 'Whole fish (Mackerel)', 'Seafood & Protein Sources', 100.00, 'kg', '2026-03-19', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 8.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(12, 'Shrimp paste (alam??ng)', 'Seafood & Protein Sources', 99.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 5.00, '2026-03-02 05:10:13', '2026-03-04 13:05:24', NULL, NULL),
(13, 'Dried shrimp', 'Seafood & Protein Sources', 100.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 5.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(14, 'Iodized salt', 'Minerals & Salts', 100.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 20.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(15, 'Salt (sea salt)', 'Minerals & Salts', 88.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 20.00, '2026-03-02 05:10:13', '2026-03-04 11:10:35', NULL, NULL),
(16, 'Soya beans', 'Plant-Derived Ingredients', 100.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 15.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(17, 'Red hot chili / chili slices', 'Plant-Derived Ingredients', 100.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 5.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(18, 'Calamansi flavor / blend', 'Plant-Derived Ingredients', 100.00, 'liters', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 3.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(19, 'Caramel (coloring/flavor)', 'Plant-Derived Ingredients', 100.00, 'liters', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 5.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(20, 'Water', 'Plant-Derived Ingredients', 100.00, 'liters', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 50.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(21, 'Garlic', 'Plant-Derived Ingredients', 100.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 10.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(22, 'Onion', 'Plant-Derived Ingredients', 100.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 10.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(23, 'Potassium sorbate (preservative)', 'Additives & Preservatives', 100.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 2.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(24, 'Sodium benzoate (preservative)', 'Additives & Preservatives', 100.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 2.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(25, 'Monosodium glutamate (MSG)', 'Additives & Preservatives', 100.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 3.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(26, 'Disodium inosinate (flavor enhancer)', 'Additives & Preservatives', 99.00, 'kg', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 1.00, '2026-03-02 05:10:13', '2026-03-04 12:47:41', NULL, NULL),
(27, 'Glass bottles', 'Packaging Materials', 97.00, 'pcs', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 100.00, '2026-03-02 05:10:13', '2026-03-08 06:56:33', NULL, NULL),
(28, 'PET plastic bottles', 'Packaging Materials', 100.00, 'pcs', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 150.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(29, 'Plastic sachets (PET/AL/PE laminate)', 'Packaging Materials', 100.00, 'pcs', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 500.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(30, 'Bottle caps / Lids', 'Packaging Materials', 96.00, 'pcs', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 200.00, '2026-03-02 05:10:13', '2026-03-08 06:58:58', NULL, NULL),
(31, 'Coated paper label stock', 'Label Materials', 100.00, 'pcs', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 100.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(32, 'Uncoated paper label', 'Label Materials', 99.00, 'pcs', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 100.00, '2026-03-02 05:10:13', '2026-03-02 07:36:34', NULL, NULL),
(33, 'BOPP film labels', 'Label Materials', 100.00, 'pcs', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 150.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(34, 'Vinyl film labels', 'Label Materials', 100.00, 'pcs', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 100.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(35, 'Polyester (PET) film labels', 'Label Materials', 100.00, 'pcs', NULL, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas', 100.00, '2026-03-02 05:10:13', '2026-03-02 07:35:57', NULL, NULL),
(36, '0', 'Procurement', 123.00, '0', NULL, '', 0.00, '2026-03-04 12:00:21', '2026-03-04 12:00:21', 'MAT-20260304-0001', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `raw_material_qc`
--

CREATE TABLE `raw_material_qc` (
  `qc_id` int(11) NOT NULL,
  `qc_number` varchar(50) NOT NULL,
  `grn_id` int(11) NOT NULL,
  `grn_item_id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `quantity_received` decimal(10,2) NOT NULL,
  `quantity_accepted` decimal(10,2) DEFAULT 0.00,
  `quantity_rejected` decimal(10,2) DEFAULT 0.00,
  `packaging_status` enum('Intact','Damaged','Partial') DEFAULT 'Intact',
  `label_accuracy` enum('Correct','Incorrect','Missing') DEFAULT 'Correct',
  `quantity_check` enum('Pass','Fail','Conditional') DEFAULT 'Pass',
  `expiry_check` enum('Pass','Fail','Conditional') DEFAULT 'Pass',
  `expiry_date` date DEFAULT NULL,
  `ph_level` decimal(5,2) DEFAULT NULL,
  `salt_percentage` decimal(5,2) DEFAULT NULL,
  `odor_test` enum('Pass','Fail','Conditional') DEFAULT 'Pass',
  `color_check` enum('Pass','Fail','Conditional') DEFAULT 'Pass',
  `texture_check` enum('Pass','Fail','Conditional') DEFAULT 'Pass',
  `qc_status` enum('Pending','Passed','Failed','Conditional') DEFAULT 'Pending',
  `qc_remarks` text DEFAULT NULL,
  `approval_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `supervisor_remarks` text DEFAULT NULL,
  `inspected_by` int(11) NOT NULL,
  `inspection_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `raw_material_qc`
--

INSERT INTO `raw_material_qc` (`qc_id`, `qc_number`, `grn_id`, `grn_item_id`, `material_id`, `item_name`, `lot_number`, `quantity_received`, `quantity_accepted`, `quantity_rejected`, `packaging_status`, `label_accuracy`, `quantity_check`, `expiry_check`, `expiry_date`, `ph_level`, `salt_percentage`, `odor_test`, `color_check`, `texture_check`, `qc_status`, `qc_remarks`, `approval_status`, `approved_by`, `approved_at`, `supervisor_remarks`, `inspected_by`, `inspection_date`, `created_at`, `updated_at`) VALUES
(1, 'QC-20260227-0001', 2, 2, NULL, '2we', '0', 23.00, 23.00, 0.00, 'Intact', 'Correct', 'Pass', 'Pass', NULL, NULL, NULL, 'Pass', 'Pass', 'Pass', 'Passed', '', 'Pending', NULL, NULL, NULL, 1, '0000-00-00', '2026-02-27 09:25:53', '2026-02-27 09:25:53'),
(3, 'QC-20260227-0004', 1, 1, 36, '0', NULL, 123.00, 123.00, 0.00, 'Intact', 'Correct', 'Pass', 'Pass', NULL, NULL, NULL, '', 'Pass', 'Pass', 'Passed', '', 'Approved', NULL, NULL, NULL, 1, '2026-03-04', '2026-02-27 10:26:37', '2026-03-04 12:00:21'),
(4, 'QC-20260301-0005', 3, 3, NULL, 'plastic bags', '0', 200.00, 0.00, 0.00, 'Intact', 'Correct', 'Pass', 'Pass', NULL, NULL, NULL, '', 'Pass', 'Pass', 'Passed', '', 'Approved', NULL, NULL, NULL, 1, '2026-03-04', '2026-03-01 13:30:14', '2026-03-04 12:00:02'),
(5, 'QC-20260301-0006', 4, 4, NULL, 'plastic bags', '0', 200.00, 0.00, 0.00, 'Intact', 'Correct', 'Pass', 'Pass', NULL, NULL, NULL, '', 'Pass', 'Pass', 'Passed', '', 'Approved', NULL, NULL, NULL, 1, '2026-03-04', '2026-03-01 13:41:40', '2026-03-04 11:59:49'),
(6, 'QC-20260304-0002', 5, 5, NULL, 'plastic bags', '0', 23.00, 0.00, 0.00, 'Intact', 'Correct', 'Pass', 'Pass', NULL, NULL, NULL, '', 'Pass', 'Pass', 'Passed', '', 'Approved', NULL, NULL, NULL, 4, '2026-03-04', '2026-03-04 11:39:50', '2026-03-04 11:40:50');

-- --------------------------------------------------------

--
-- Table structure for table `return_items`
--

CREATE TABLE `return_items` (
  `return_item_id` int(11) NOT NULL,
  `return_id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) DEFAULT 'kg',
  `unit_price` decimal(12,2) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_items`
--

INSERT INTO `return_items` (`return_item_id`, `return_id`, `material_id`, `product_id`, `item_name`, `quantity`, `unit`, `unit_price`, `subtotal`, `reason`) VALUES
(1, 1, NULL, NULL, '0', 12.00, '0', 12.00, 144.00, '12'),
(2, 2, NULL, NULL, '0', 100.00, '0', 500.00, 50000.00, 'bad'),
(3, 3, NULL, NULL, '0', 12.00, '0', 12.00, 144.00, '12');

-- --------------------------------------------------------

--
-- Table structure for table `sales_orders`
--

CREATE TABLE `sales_orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `order_date` date NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `fulfillment_type` enum('Delivery','Pickup') DEFAULT 'Delivery',
  `delivery_person_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Confirmed','Dispatched','Delivered','Cancelled','Ready for Pickup','Picked Up') DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `invoice_id` int(11) DEFAULT NULL,
  `invoice_generated` tinyint(1) DEFAULT 0,
  `from_production_request` tinyint(1) NOT NULL DEFAULT 0,
  `request_group_id` varchar(50) DEFAULT NULL,
  `reservation_expires_at` datetime DEFAULT NULL,
  `delivery_lat` decimal(10,8) DEFAULT NULL,
  `delivery_lng` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_orders`
--

INSERT INTO `sales_orders` (`order_id`, `order_number`, `customer_id`, `product_id`, `quantity`, `order_date`, `delivery_address`, `delivery_date`, `fulfillment_type`, `delivery_person_id`, `status`, `created_by`, `created_at`, `total_amount`, `invoice_id`, `invoice_generated`, `from_production_request`, `request_group_id`, `reservation_expires_at`, `delivery_lat`, `delivery_lng`) VALUES
(17, 'ORD-20260227-0001', 12, NULL, NULL, '2026-02-27', '', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-02-27 11:36:06', 10455.00, 5, 1, 1, 'PRG-20260227123508-8d19', NULL, NULL, NULL),
(18, 'ORD-20260301-0001', 12, NULL, NULL, '2026-03-01', 'test address', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-01 09:42:16', 0.00, 13, 1, 0, NULL, '2026-03-03 10:42:16', NULL, NULL),
(19, 'ORD-20260301-0002', 12, NULL, NULL, '2026-03-01', 'test address', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-01 09:42:34', 0.00, NULL, 0, 0, NULL, '2026-03-03 10:42:34', 14.59958900, 120.99888100),
(20, 'ORD-20260301-0003', 12, NULL, NULL, '2026-03-01', 'test address', '2026-03-01', 'Delivery', NULL, 'Delivered', 1, '2026-03-01 09:47:46', 0.00, NULL, 0, 0, NULL, '2026-03-03 10:47:46', NULL, NULL),
(21, 'ORD-20260301-0004', 12, NULL, NULL, '2026-03-01', '', NULL, 'Delivery', NULL, 'Confirmed', 1, '2026-03-01 09:54:05', 110500.00, NULL, 0, 1, 'PRG-20260227115011-bd30', NULL, NULL, NULL),
(22, 'ORD-20260301-0005', 12, NULL, NULL, '2026-03-01', 'test address', '2026-03-01', 'Delivery', NULL, 'Confirmed', 1, '2026-03-01 09:54:46', 0.00, NULL, 0, 0, NULL, '2026-03-03 10:54:46', 14.79901300, 121.13473200),
(23, 'ORD-20260301-0006', 12, NULL, NULL, '2026-03-01', 'test address', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-01 09:55:29', 0.00, NULL, 0, 0, NULL, '2026-03-03 10:55:29', NULL, NULL),
(25, 'ORD-20260301-0008', 12, NULL, NULL, '2026-03-01', 'test address', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-01 09:56:56', 0.00, NULL, 0, 0, NULL, '2026-03-03 10:56:56', 14.58563400, 121.02357100),
(26, 'ORD-20260301-0009', 12, NULL, NULL, '2026-03-01', 'test address', '2026-03-01', 'Delivery', NULL, 'Delivered', 1, '2026-03-01 10:01:16', 2847.17, NULL, 0, 0, NULL, '2026-03-03 11:01:16', NULL, NULL),
(27, 'ORD-20260301-0010', 12, NULL, NULL, '2026-03-01', 'test address', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-01 10:01:49', 2890.00, 14, 1, 0, NULL, '2026-03-03 11:01:49', NULL, NULL),
(28, 'ORD-20260301-0011', 12, NULL, NULL, '2026-03-01', 'test address', '2026-03-01', 'Delivery', NULL, 'Delivered', 1, '2026-03-01 10:21:27', 1020.00, NULL, 0, 0, NULL, '2026-03-03 11:21:27', NULL, NULL),
(29, 'ORD-20260301-0012', 12, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, 'Pickup', NULL, '', 1, '2026-03-01 10:21:36', 1020.00, NULL, 0, 0, NULL, '2026-03-03 11:21:36', NULL, NULL),
(30, 'ORD-20260301-0013', 12, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, 'Pickup', NULL, '', 1, '2026-03-01 10:23:59', 595.00, NULL, 0, 0, NULL, '2026-03-03 11:23:59', NULL, NULL),
(31, 'ORD-20260301-0014', 12, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, 'Pickup', NULL, '', 1, '2026-03-01 10:30:06', 595.00, NULL, 0, 0, NULL, '2026-03-03 11:30:06', NULL, NULL),
(32, 'ORD-20260301-0015', 12, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, 'Pickup', NULL, '', 1, '2026-03-01 10:41:23', 1020.00, NULL, 0, 0, NULL, '2026-03-03 11:41:23', NULL, NULL),
(33, 'ORD-20260301-0016', 12, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, 'Pickup', NULL, '', 1, '2026-03-01 10:46:09', 595.00, NULL, 0, 0, NULL, '2026-03-03 11:46:09', NULL, NULL),
(34, 'ORD-20260301-0017', 12, NULL, NULL, '2026-03-01', 'test address', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-01 10:47:42', 595.00, NULL, 0, 0, NULL, '2026-03-03 11:47:42', NULL, NULL),
(35, 'ORD-20260301-0018', 12, NULL, NULL, '2026-03-01', 'test address', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-01 10:48:30', 232.74, NULL, 0, 0, NULL, '2026-03-03 11:48:30', NULL, NULL),
(36, 'ORD-20260301-0019', 12, NULL, NULL, '2026-03-01', 'test address', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-01 10:48:40', 1955.00, NULL, 0, 0, NULL, '2026-03-03 11:48:40', NULL, NULL),
(37, 'ORD-20260301-0020', 12, NULL, NULL, '2026-03-01', 'test address', NULL, 'Delivery', NULL, 'Confirmed', 1, '2026-03-01 10:49:58', 595.00, NULL, 0, 0, NULL, '2026-03-03 11:49:58', NULL, NULL),
(38, 'ORD-20260301-0021', 12, NULL, NULL, '2026-03-01', 'test address', '2026-04-09', 'Delivery', NULL, 'Confirmed', 1, '2026-03-01 10:51:57', 595.00, NULL, 0, 0, NULL, '2026-03-03 11:51:57', NULL, NULL),
(39, 'ORD-20260301-0022', 12, NULL, NULL, '2026-03-01', 'test address', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-01 11:05:51', 1020.00, NULL, 0, 0, NULL, '2026-03-03 12:05:51', 14.54609200, 121.08357700),
(40, 'ORD-20260301-0023', 12, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, 'Pickup', NULL, 'Confirmed', 1, '2026-03-01 11:06:09', 1020.00, NULL, 0, 0, NULL, '2026-03-03 12:06:09', NULL, NULL),
(41, 'ORD-20260301-0024', 12, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, 'Confirmed', 1, '2026-03-01 11:10:30', 255.00, NULL, 0, 0, NULL, '2026-03-03 12:10:30', NULL, NULL),
(42, 'ORD-20260301-0025', 12, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 11:14:06', 4590.00, NULL, 0, 0, NULL, '2026-03-03 12:14:06', NULL, NULL),
(43, 'ORD-20260301-0026', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 11:16:54', 510.00, NULL, 0, 0, NULL, '2026-03-03 12:16:54', NULL, NULL),
(44, 'ORD-20260301-0027', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 11:18:07', 425.00, NULL, 0, 0, NULL, '2026-03-03 12:18:07', NULL, NULL),
(45, 'ORD-20260301-0028', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, 'Confirmed', 1, '2026-03-01 11:18:52', 425.00, NULL, 0, 0, NULL, '2026-03-03 12:18:52', NULL, NULL),
(46, 'ORD-20260301-0029', 13, NULL, NULL, '2026-03-01', 'test add', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-01 11:20:42', 340.00, NULL, 0, 0, NULL, '2026-03-03 12:20:42', NULL, NULL),
(47, 'ORD-20260301-0030', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 11:22:21', 510.00, NULL, 0, 0, NULL, '2026-03-03 12:22:21', NULL, NULL),
(48, 'PUP-20260301-0001', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 11:26:17', 1020.00, 8, 1, 0, NULL, '2026-03-03 12:26:17', NULL, NULL),
(49, 'PUP-20260301-0002', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 11:29:00', 680.00, NULL, 0, 0, NULL, '2026-03-03 12:29:00', NULL, NULL),
(50, 'PUP-20260301-0003', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 11:29:17', 340.00, NULL, 0, 0, NULL, '2026-03-03 12:29:17', NULL, NULL),
(51, 'ORD-20260301-0031', 13, NULL, NULL, '2026-03-01', 'test add', '2026-03-01', 'Delivery', NULL, 'Confirmed', 1, '2026-03-01 11:29:55', 510.00, NULL, 0, 0, NULL, '2026-03-03 12:29:55', NULL, NULL),
(52, 'PUP-20260301-0004', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 11:37:25', 425.00, NULL, 0, 0, NULL, '2026-03-03 12:37:25', NULL, NULL),
(53, 'PUP-20260301-0005', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 11:41:55', 1955.00, NULL, 0, 0, NULL, '2026-03-03 12:41:55', NULL, NULL),
(54, 'PUP-20260301-0006', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 11:48:16', 170.00, NULL, 0, 0, NULL, '2026-03-03 12:48:16', NULL, NULL),
(55, 'PUP-20260301-0007', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 11:53:49', 340.00, NULL, 0, 0, NULL, '2026-03-03 12:53:49', NULL, NULL),
(56, 'PUP-20260301-0008', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 12:04:34', 510.00, NULL, 0, 0, NULL, '2026-03-03 13:04:34', NULL, NULL),
(57, 'PUP-20260301-0009', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 12:18:39', 1020.00, NULL, 0, 0, NULL, '2026-03-03 13:18:39', NULL, NULL),
(58, 'PUP-20260301-0010', 13, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 12:20:10', 1020.00, 12, 1, 0, NULL, '2026-03-03 13:20:10', NULL, NULL),
(59, 'PUP-20260301-0011', 12, NULL, NULL, '2026-03-01', 'Warehouse Pickup', NULL, '', NULL, '', 1, '2026-03-01 12:22:34', 340.00, 9, 1, 0, NULL, '2026-03-03 13:22:34', NULL, NULL),
(60, 'ORD-20260301-0032', 13, NULL, NULL, '2026-03-01', 'test add', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-01 12:56:39', 1955.00, 11, 1, 0, NULL, '2026-03-03 13:56:39', NULL, NULL),
(61, 'ORD-20260301-0033', 13, NULL, NULL, '2026-03-01', 'test add', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-01 13:09:27', 765.00, 10, 1, 0, NULL, '2026-03-03 14:09:27', NULL, NULL),
(62, 'ORD-20260301-0034', 12, NULL, NULL, '2026-03-01', '', NULL, 'Delivery', NULL, 'Confirmed', 1, '2026-03-01 15:31:54', 10455.00, NULL, 0, 1, 'PRG-20260227121911-2da8', NULL, NULL, NULL),
(63, 'ORD-20260302-0001', 13, NULL, NULL, '2026-03-02', '', NULL, 'Delivery', NULL, 'Confirmed', 1, '2026-03-02 07:05:28', 16377.00, NULL, 0, 1, 'PRG-20260302064951-d4db', NULL, NULL, NULL),
(64, 'ORD-20260302-0002', 12, NULL, NULL, '2026-03-02', '', NULL, 'Delivery', NULL, 'Confirmed', 1, '2026-03-02 07:37:59', 110500.00, NULL, 0, 1, 'PRG-20260227115011-bd30', NULL, NULL, NULL),
(65, 'ORD-20260304-0001', 12, NULL, NULL, '2026-03-04', 'test address', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-04 09:32:43', 170.00, NULL, 0, 0, NULL, '2026-03-06 10:32:43', NULL, NULL),
(66, 'ORD-20260304-0002', 14, NULL, NULL, '2026-03-04', '', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-04 11:10:55', 3633.00, NULL, 0, 1, 'PRG-20260304120831-830b', NULL, NULL, NULL),
(67, 'ORD-20260304-0003', 14, NULL, NULL, '2026-03-04', '', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-04 13:07:16', 12453.00, NULL, 0, 1, 'PRG-20260304140453-7c51', NULL, NULL, NULL),
(68, 'ORD-20260304-0004', 14, NULL, NULL, '2026-03-04', '', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-04 13:28:16', 18453.00, NULL, 0, 1, 'PRG-20260304142629-b958', NULL, NULL, NULL),
(69, 'ORD-20260304-0005', 14, NULL, NULL, '2026-03-04', '', NULL, 'Delivery', NULL, 'Confirmed', 1, '2026-03-04 13:28:34', 18453.00, NULL, 0, 1, 'PRG-20260304142629-b958', NULL, NULL, NULL),
(70, 'ORD-20260304-0006', 14, NULL, NULL, '2026-03-04', 'sta clara sto tomas batangas', '2026-03-04', 'Delivery', NULL, 'Confirmed', 1, '2026-03-04 14:25:42', 42500.00, NULL, 0, 0, NULL, '2026-03-06 15:25:42', NULL, NULL),
(71, 'ORD-20260304-0007', 14, NULL, NULL, '2026-03-04', 'Sta clara sto tomas batangas', '2026-03-04', 'Delivery', NULL, 'Delivered', 1, '2026-03-04 14:44:47', 2550.00, NULL, 0, 0, NULL, '2026-03-06 15:44:47', 14.02599700, 121.20400400),
(72, 'ORD-20260307-0001', 15, NULL, NULL, '2026-03-07', '', NULL, 'Delivery', NULL, 'Delivered', 1, '2026-03-07 02:40:55', 2254.00, 15, 1, 1, 'PRG-20260307033704-3318', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales_settings`
--

CREATE TABLE `sales_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_settings`
--

INSERT INTO `sales_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'default_price', '100.00', 'Default product price in PHP', '2026-03-01 15:17:27'),
(2, 'max_discount', '10', 'Maximum discount percentage', '2026-03-01 15:17:27'),
(3, 'vat_rate', '12', 'VAT rate percentage', '2026-03-01 15:17:27'),
(4, 'payment_terms', 'Cash,30 Days', 'Allowed payment terms', '2026-03-01 15:17:27');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `supplier_code` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `payment_terms`, `contact_person`, `contact_number`, `email`, `address`, `created_at`, `updated_at`, `supplier_code`, `status`) VALUES
(2, 'test supp1', 'Cash on Delivery', 'testcontact', '09234233423', 'mike@gmail.com', '40434', '2026-02-05 06:03:15', '2026-03-01 09:39:46', 'SUP-20260205-0001', 'active'),
(3, 'test supplier', 'Cash on Delivery', 'testcontact', '09234233423', 'mike@gmail.com', '40434', '2026-02-06 10:31:27', '2026-02-27 11:58:25', 'SUP-20260206-0001', 'inactive'),
(4, 'mike', NULL, 'y', '091234112314', 'mike@gmail.com', '404', '2026-02-06 10:44:41', '2026-02-06 10:44:41', 'SUP-20260206-0002', 'active'),
(5, 'test supplier2', 'Cash on Delivery', 'testcontact', '09234233423', 'mike@gmail.com', '40434', '2026-02-26 10:23:42', '2026-03-01 09:39:57', 'SUP-20260226-0001', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_deliveries`
--

CREATE TABLE `supplier_deliveries` (
  `delivery_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `delivery_date` date NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_deliveries`
--

INSERT INTO `supplier_deliveries` (`delivery_id`, `supplier_id`, `delivery_date`, `reference`, `notes`, `received_by`, `created_at`) VALUES
(1, 2, '2026-02-05', '3434', '4afasfd', 3, '2026-02-05 06:04:12');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_delivery_items`
--

CREATE TABLE `supplier_delivery_items` (
  `id` int(11) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  `raw_material_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) DEFAULT 'kg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_delivery_items`
--

INSERT INTO `supplier_delivery_items` (`id`, `delivery_id`, `raw_material_id`, `product_id`, `item_name`, `quantity`, `unit`, `created_at`) VALUES
(1, 1, NULL, NULL, 'asfd', 342.00, 'kg', '2026-02-05 06:04:12');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_invoices`
--

CREATE TABLE `supplier_invoices` (
  `invoice_id` int(11) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `po_id` int(11) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL,
  `payment_status` enum('Unpaid','Partially Paid','Paid') DEFAULT 'Unpaid',
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_invoices`
--

INSERT INTO `supplier_invoices` (`invoice_id`, `invoice_number`, `supplier_id`, `po_id`, `invoice_date`, `due_date`, `subtotal`, `tax_amount`, `total_amount`, `payment_status`, `paid_amount`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'SI-                                        PO-20260226-0001-883410', 2, 1, '2026-02-26', '2026-03-14', 1000000.00, 0.00, 1000000.00, 'Paid', 1000000.00, '', 1, '2026-02-26 10:15:11', '2026-02-26 10:15:57'),
(2, 'SI-                                        PO-20260227-0001-839641', 3, NULL, '2026-02-27', NULL, 529.00, 0.00, 529.00, 'Paid', 529.00, '', 1, '2026-02-27 09:50:45', '2026-02-28 14:06:19'),
(3, 'SI-                                        PO-20260227-0001-839641', 5, NULL, '2026-02-28', '2026-02-28', 1000.00, 10.00, 1010.00, 'Paid', 1010.00, '', 1, '2026-02-28 14:04:42', '2026-02-28 14:04:50'),
(4, 'SI-                                        PO-20260304-0001-476932', 2, NULL, '2026-03-04', '2026-03-04', 4000.00, 0.00, 4000.00, 'Unpaid', 0.00, '', 10, '2026-03-04 11:41:32', '2026-03-04 11:41:32'),
(5, 'SI-20260304-0001', 4, 4, '2026-03-04', NULL, 40000.00, 0.00, 40000.00, 'Paid', 40000.00, '', 1, '2026-03-04 11:56:44', '2026-03-04 11:57:27'),
(6, 'SI-20260304-0002', 3, 2, '2026-03-04', NULL, 529.00, 0.00, 529.00, 'Unpaid', 0.00, '', 1, '2026-03-04 11:57:51', '2026-03-04 11:57:51');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(64) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `reference_number` varchar(128) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_payments`
--

INSERT INTO `supplier_payments` (`id`, `invoice_id`, `payment_date`, `payment_method`, `amount`, `reference_number`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, '2026-02-26', 'Bank Transfer', 1000000.00, '', '', 1, '2026-02-26 10:15:57'),
(2, 3, '2026-02-28', 'Cash', 1010.00, '', '', 1, '2026-02-28 14:04:50'),
(3, 2, '2026-02-28', 'Bank Transfer', 529.00, '', '', 1, '2026-02-28 14:06:19'),
(4, 5, '2026-03-04', 'Check', 40000.00, '', '', 1, '2026-03-04 11:57:27');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_products`
--

CREATE TABLE `supplier_products` (
  `sp_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_type` enum('Raw Material','Product','Other') DEFAULT 'Raw Material',
  `unit_price` decimal(12,2) DEFAULT NULL,
  `unit` varchar(20) DEFAULT 'kg',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_returns`
--

CREATE TABLE `supplier_returns` (
  `return_id` int(11) NOT NULL,
  `return_number` varchar(50) NOT NULL,
  `po_id` int(11) DEFAULT NULL,
  `grn_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) NOT NULL,
  `return_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Returned','Cancelled') DEFAULT 'Pending',
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_returns`
--

INSERT INTO `supplier_returns` (`return_id`, `return_number`, `po_id`, `grn_id`, `supplier_id`, `return_date`, `reason`, `status`, `total_amount`, `approved_by`, `approved_at`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'RET-20260227-0001', NULL, 2, 4, '2026-02-27', '0', 'Approved', 144.00, 1, '2026-02-28 14:07:27', '0', 1, '2026-02-27 11:58:53', '2026-02-28 14:07:27'),
(2, 'RET-20260228-0001', NULL, 2, 5, '2026-02-28', '0', 'Approved', 50000.00, 1, '2026-03-01 15:37:03', '0', 1, '2026-02-28 14:08:16', '2026-03-01 15:37:03'),
(3, 'RET-20260228-0002', NULL, 2, 5, '2026-02-28', '12', 'Pending', 144.00, NULL, NULL, '0', 1, '2026-02-28 14:15:19', '2026-02-28 14:15:19');

-- --------------------------------------------------------

--
-- Table structure for table `system_events`
--

CREATE TABLE `system_events` (
  `id` int(11) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_events`
--

INSERT INTO `system_events` (`id`, `entity_type`, `entity_id`, `event_type`, `payload`, `processed`, `processed_at`, `created_at`) VALUES
(1, 'qc_record', 20, 'QC_APPROVED_FG', '{\"product_id\":12,\"quantity\":\"12.00\",\"batch_id\":20,\"expiry_date\":\"2027-02-26\",\"warehouse_location\":null,\"created_by\":\"1\"}', 1, '2026-02-26 18:01:11', '2026-02-26 10:01:11'),
(2, 'sales_order', 13, 'SALES_ORDER_DELIVERED', '{\"order_id\":13,\"assignment_id\":11,\"order_items\":[{\"product_id\":17,\"quantity\":2}]}', 0, NULL, '2026-02-26 10:18:35'),
(3, 'sales_order', 9, 'SALES_ORDER_DELIVERED', '{\"order_id\":9,\"assignment_id\":7,\"order_items\":[{\"product_id\":34,\"quantity\":12}]}', 0, NULL, '2026-02-26 10:18:47'),
(4, 'qc_record', 21, 'QC_APPROVED_FG', '{\"product_id\":14,\"quantity\":\"2.00\",\"batch_id\":10,\"expiry_date\":\"2027-02-27\",\"warehouse_location\":null,\"created_by\":\"1\"}', 1, '2026-02-27 18:24:12', '2026-02-27 10:24:12'),
(5, 'raw_material_qc', 3, 'QC_APPROVED_RAW', '{\"items\":[{\"material_id\":0,\"quantity\":123,\"expiry_date\":null,\"warehouse_location\":\"\",\"grn_item_id\":1,\"qc_id\":3,\"item_name\":\"0\",\"unit\":\"0\",\"created_by\":\"1\"}]}', 1, '2026-03-04 20:00:21', '2026-02-27 10:27:57'),
(6, 'production_batch', 3, 'PRODUCTION_OUTPUT', '{\"product_id\":14,\"quantity\":34,\"batch_id\":3,\"expiry_date\":\"2027-02-27\",\"created_by\":1}', 1, '2026-02-27 18:30:19', '2026-02-27 10:30:19'),
(7, 'production_batch', 4, 'PRODUCTION_OUTPUT', '{\"product_id\":17,\"quantity\":34,\"batch_id\":4,\"expiry_date\":\"2027-02-27\",\"created_by\":1}', 1, '2026-02-27 18:30:20', '2026-02-27 10:30:20'),
(8, 'production_batch', 5, 'PRODUCTION_OUTPUT', '{\"product_id\":34,\"quantity\":45,\"batch_id\":5,\"expiry_date\":\"2027-02-27\",\"created_by\":1}', 1, '2026-02-27 18:30:22', '2026-02-27 10:30:22'),
(9, 'production_batch', 6, 'PRODUCTION_OUTPUT', '{\"product_id\":36,\"quantity\":56,\"batch_id\":6,\"expiry_date\":\"2027-02-27\",\"created_by\":1}', 1, '2026-02-27 18:30:24', '2026-02-27 10:30:24'),
(10, 'production_batch', 7, 'PRODUCTION_OUTPUT', '{\"product_id\":39,\"quantity\":34,\"batch_id\":7,\"expiry_date\":\"2027-02-27\",\"created_by\":1}', 1, '2026-02-27 18:30:25', '2026-02-27 10:30:25'),
(11, 'production_batch', 8, 'PRODUCTION_OUTPUT', '{\"product_id\":35,\"quantity\":45,\"batch_id\":8,\"expiry_date\":\"2027-02-27\",\"created_by\":1}', 1, '2026-02-27 18:30:27', '2026-02-27 10:30:27'),
(12, 'production_batch', 19, 'PRODUCTION_OUTPUT', '{\"product_id\":21,\"quantity\":80,\"batch_id\":19,\"expiry_date\":\"2027-02-27\",\"created_by\":1}', 1, '2026-02-27 18:30:40', '2026-02-27 10:30:40'),
(13, 'qc_record', 23, 'QC_APPROVED_FG', '{\"product_id\":19,\"quantity\":\"40.00\",\"batch_id\":18,\"expiry_date\":\"2027-02-27\",\"warehouse_location\":null,\"created_by\":\"1\"}', 1, '2026-02-27 18:32:16', '2026-02-27 10:32:16'),
(14, 'production_batch', 25, 'PRODUCTION_CONSUME', '{\"batch_id\":25,\"materials\":[{\"material_id\":2,\"quantity\":10}],\"created_by\":1}', 0, NULL, '2026-02-27 11:01:06'),
(15, 'production_batch', 26, 'PRODUCTION_CONSUME', '{\"batch_id\":26,\"materials\":[{\"material_id\":2,\"quantity\":10}],\"created_by\":1}', 0, NULL, '2026-02-27 11:01:29'),
(16, 'production_batch', 27, 'PRODUCTION_CONSUME', '{\"batch_id\":27,\"materials\":[{\"material_id\":3,\"quantity\":12}],\"created_by\":1}', 0, NULL, '2026-02-27 11:02:33'),
(17, 'production_batch', 28, 'PRODUCTION_CONSUME', '{\"batch_id\":28,\"materials\":[{\"material_id\":2,\"quantity\":12}],\"created_by\":1}', 0, NULL, '2026-02-27 11:06:07'),
(18, 'production_batch', 29, 'PRODUCTION_CONSUME', '{\"batch_id\":29,\"materials\":[{\"material_id\":3,\"quantity\":34}],\"created_by\":1}', 0, NULL, '2026-02-27 11:09:52'),
(19, 'production_batch', 30, 'PRODUCTION_CONSUME', '{\"batch_id\":30,\"materials\":[{\"material_id\":3,\"quantity\":34}],\"created_by\":1}', 0, NULL, '2026-02-27 11:11:41'),
(20, 'production_batch', 31, 'PRODUCTION_CONSUME', '{\"batch_id\":31,\"materials\":[{\"material_id\":3,\"quantity\":12}],\"created_by\":1}', 0, NULL, '2026-02-27 11:12:13'),
(21, 'production_batch', 32, 'PRODUCTION_CONSUME', '{\"batch_id\":32,\"materials\":[{\"material_id\":3,\"quantity\":12}],\"created_by\":1}', 1, '2026-02-27 19:14:16', '2026-02-27 11:14:16'),
(22, 'production_batch', 33, 'PRODUCTION_CONSUME', '{\"batch_id\":33,\"materials\":[{\"material_id\":1,\"quantity\":12}],\"created_by\":1}', 1, '2026-02-27 19:30:20', '2026-02-27 11:30:20'),
(23, 'production_batch', 35, 'PRODUCTION_CONSUME', '{\"batch_id\":35,\"materials\":[{\"material_id\":3,\"quantity\":121}],\"created_by\":1}', 1, '2026-02-27 19:31:16', '2026-02-27 11:31:16'),
(24, 'production_batch', 36, 'PRODUCTION_CONSUME', '{\"batch_id\":36,\"materials\":[{\"material_id\":1,\"quantity\":432}],\"created_by\":1}', 1, '2026-02-27 19:31:53', '2026-02-27 11:31:53'),
(25, 'production_batch', 37, 'PRODUCTION_CONSUME', '{\"batch_id\":37,\"materials\":[{\"material_id\":6,\"quantity\":12}],\"created_by\":1}', 1, '2026-02-27 19:34:14', '2026-02-27 11:34:14'),
(26, 'production_batch', 39, 'PRODUCTION_CONSUME', '{\"batch_id\":39,\"materials\":[{\"material_id\":3,\"quantity\":12}],\"created_by\":1}', 1, '2026-02-27 19:35:32', '2026-02-27 11:35:32'),
(27, 'qc_record', 24, 'QC_APPROVED_FG', '{\"product_id\":26,\"quantity\":\"123.00\",\"batch_id\":39,\"expiry_date\":\"2027-02-27\",\"warehouse_location\":null,\"created_by\":\"1\"}', 1, '2026-02-27 19:36:06', '2026-02-27 11:36:06'),
(28, 'sales_order', 17, 'SALES_ORDER_DELIVERED', '{\"order_id\":17,\"assignment_id\":14,\"order_items\":[{\"product_id\":26,\"quantity\":123}]}', 0, NULL, '2026-02-27 11:37:34'),
(29, 'supplier_return', 1, 'RETURN_PROCESSED', '{\"return_id\":1,\"items\":[{\"material_id\":7,\"quantity\":12,\"created_by\":\"1\"}]}', 0, NULL, '2026-02-27 11:58:53'),
(30, 'production_batch', 40, 'PRODUCTION_CONSUME', '{\"batch_id\":40,\"materials\":[{\"material_id\":1,\"quantity\":12}],\"created_by\":1}', 1, '2026-02-27 20:02:18', '2026-02-27 12:02:18'),
(31, 'production_batch', 41, 'PRODUCTION_CONSUME', '{\"batch_id\":41,\"materials\":[{\"material_id\":2,\"quantity\":12}],\"created_by\":1}', 1, '2026-02-27 20:02:38', '2026-02-27 12:02:38'),
(32, 'supplier_return', 2, 'RETURN_PROCESSED', '{\"return_id\":2,\"items\":[{\"material_id\":7,\"quantity\":100,\"created_by\":\"1\"}]}', 0, NULL, '2026-02-28 14:08:16'),
(33, 'supplier_return', 3, 'RETURN_PROCESSED', '{\"return_id\":3,\"items\":[{\"material_id\":7,\"quantity\":12,\"created_by\":\"1\"}]}', 0, NULL, '2026-02-28 14:15:19'),
(34, 'production_batch', 42, 'PRODUCTION_CONSUME', '{\"batch_id\":42,\"materials\":[{\"material_id\":2,\"quantity\":100},{\"material_id\":1,\"quantity\":4}],\"created_by\":1}', 1, '2026-03-01 17:49:41', '2026-03-01 09:49:41'),
(35, 'qc_record', 29, 'QC_APPROVED_FG', '{\"product_id\":26,\"quantity\":\"1300.00\",\"batch_id\":42,\"expiry_date\":\"2027-03-01\",\"warehouse_location\":null,\"created_by\":\"1\"}', 1, '2026-03-01 17:54:05', '2026-03-01 09:54:04'),
(37, 'qc_record', 30, 'QC_APPROVED_FG', '{\"product_id\":26,\"quantity\":\"12.00\",\"batch_id\":33,\"expiry_date\":\"2027-03-01\",\"warehouse_location\":null,\"created_by\":\"1\"}', 1, '2026-03-01 23:31:54', '2026-03-01 15:31:54'),
(38, 'production_batch', 43, 'PRODUCTION_CONSUME', '{\"batch_id\":43,\"materials\":[{\"material_id\":3,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-02 12:46:56', '2026-03-02 04:46:56'),
(39, 'qc_record', 31, 'QC_APPROVED_FG', '{\"product_id\":16,\"quantity\":\"90.00\",\"batch_id\":43,\"expiry_date\":\"2027-03-02\",\"warehouse_location\":null,\"created_by\":\"1\"}', 1, '2026-03-02 12:49:58', '2026-03-02 04:49:58'),
(40, 'production_batch', 44, 'PRODUCTION_CONSUME', '{\"batch_id\":44,\"materials\":[{\"material_id\":2,\"quantity\":1},{\"material_id\":1,\"quantity\":1},{\"material_id\":3,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-02 13:50:38', '2026-03-02 05:50:38'),
(41, 'production_batch', 45, 'PRODUCTION_CONSUME', '{\"batch_id\":45,\"materials\":[{\"material_id\":6,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-02 14:55:25', '2026-03-02 06:55:25'),
(42, 'production_batch', 46, 'PRODUCTION_CONSUME', '{\"batch_id\":46,\"materials\":[{\"material_id\":1,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-02 14:57:59', '2026-03-02 06:57:59'),
(43, 'production_batch', 47, 'PRODUCTION_CONSUME', '{\"batch_id\":47,\"materials\":[{\"material_id\":6,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-02 15:05:01', '2026-03-02 07:05:01'),
(44, 'qc_record', 32, 'QC_APPROVED_FG', '{\"product_id\":42,\"quantity\":\"100.00\",\"batch_id\":44,\"expiry_date\":\"2026-03-03\",\"warehouse_location\":null,\"created_by\":\"1\"}', 1, '2026-03-02 15:05:28', '2026-03-02 07:05:28'),
(45, 'production_batch', 49, 'PRODUCTION_CONSUME', '{\"batch_id\":49,\"materials\":[{\"material_id\":32,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-02 15:36:34', '2026-03-02 07:36:34'),
(46, 'qc_record', 33, 'QC_APPROVED_FG', '{\"product_id\":26,\"quantity\":\"1300.00\",\"batch_id\":49,\"expiry_date\":\"2027-03-02\",\"warehouse_location\":null,\"created_by\":\"1\"}', 1, '2026-03-02 15:37:59', '2026-03-02 07:37:59'),
(47, 'production_batch', 50, 'PRODUCTION_CONSUME', '{\"batch_id\":50,\"materials\":[{\"material_id\":27,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-02 16:01:44', '2026-03-02 08:01:44'),
(48, 'qc_record', 34, 'QC_APPROVED_FG', '{\"product_id\":42,\"quantity\":\"12.00\",\"batch_id\":50,\"expiry_date\":\"2027-03-02\",\"warehouse_location\":null,\"created_by\":\"1\"}', 1, '2026-03-02 16:03:53', '2026-03-02 08:03:53'),
(49, 'production_batch', 51, 'PRODUCTION_CONSUME', '{\"batch_id\":51,\"materials\":[{\"material_id\":30,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-02 16:05:41', '2026-03-02 08:05:41'),
(50, 'qc_record', 35, 'QC_APPROVED_FG', '{\"product_id\":20,\"quantity\":\"12334.00\",\"batch_id\":51,\"expiry_date\":\"2027-03-02\",\"warehouse_location\":null,\"created_by\":\"1\"}', 1, '2026-03-02 16:05:55', '2026-03-02 08:05:55'),
(51, 'sales_order', 61, 'SALES_ORDER_DELIVERED', '{\"order_id\":61,\"assignment_id\":25,\"order_items\":[{\"product_id\":26,\"quantity\":9}]}', 0, NULL, '2026-03-04 04:54:52'),
(52, 'sales_order', 60, 'SALES_ORDER_DELIVERED', '{\"order_id\":60,\"assignment_id\":24,\"order_items\":[{\"product_id\":26,\"quantity\":23}]}', 0, NULL, '2026-03-04 04:55:05'),
(53, 'sales_order', 65, 'SALES_ORDER_DELIVERED', '{\"order_id\":65,\"assignment_id\":26,\"order_items\":[{\"product_id\":26,\"quantity\":2}]}', 0, NULL, '2026-03-04 09:33:28'),
(54, 'sales_order', 46, 'SALES_ORDER_DELIVERED', '{\"order_id\":46,\"assignment_id\":22,\"order_items\":[{\"product_id\":26,\"quantity\":4}]}', 0, NULL, '2026-03-04 09:33:44'),
(55, 'sales_order', 36, 'SALES_ORDER_DELIVERED', '{\"order_id\":36,\"assignment_id\":19,\"order_items\":[{\"product_id\":26,\"quantity\":23}]}', 0, NULL, '2026-03-04 09:34:04'),
(56, 'sales_order', 35, 'SALES_ORDER_DELIVERED', '{\"order_id\":35,\"assignment_id\":18,\"order_items\":[{\"product_id\":17,\"quantity\":6}]}', 0, NULL, '2026-03-04 09:34:11'),
(57, 'sales_order', 34, 'SALES_ORDER_DELIVERED', '{\"order_id\":34,\"assignment_id\":17,\"order_items\":[{\"product_id\":26,\"quantity\":7}]}', 0, NULL, '2026-03-04 09:34:18'),
(58, 'sales_order', 28, 'SALES_ORDER_DELIVERED', '{\"order_id\":28,\"assignment_id\":16,\"order_items\":[{\"product_id\":26,\"quantity\":12}]}', 0, NULL, '2026-03-04 09:34:23'),
(59, 'sales_order', 26, 'SALES_ORDER_DELIVERED', '{\"order_id\":26,\"assignment_id\":15,\"order_items\":[{\"product_id\":26,\"quantity\":23},{\"product_id\":17,\"quantity\":23}]}', 0, NULL, '2026-03-04 09:34:33'),
(60, 'production_batch', 52, 'PRODUCTION_CONSUME', '{\"batch_id\":52,\"materials\":[{\"material_id\":15,\"quantity\":12}],\"created_by\":1}', 1, '2026-03-04 19:10:35', '2026-03-04 11:10:35'),
(61, 'qc_record', 36, 'QC_APPROVED_FG', '{\"product_id\":32,\"quantity\":\"100.00\",\"batch_id\":52,\"expiry_date\":\"2028-03-04\",\"warehouse_location\":\"Lot 6720 Brgy San Joaquin Sto Tomas Batangas\",\"created_by\":\"1\"}', 1, '2026-03-04 19:10:55', '2026-03-04 11:10:55'),
(63, 'production_batch', 53, 'PRODUCTION_CONSUME', '{\"batch_id\":53,\"materials\":[{\"material_id\":26,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-04 20:47:41', '2026-03-04 12:47:41'),
(64, 'production_batch', 54, 'PRODUCTION_CONSUME', '{\"batch_id\":54,\"materials\":[{\"material_id\":30,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-04 20:48:15', '2026-03-04 12:48:15'),
(65, 'production_batch', 55, 'PRODUCTION_CONSUME', '{\"batch_id\":55,\"materials\":[{\"material_id\":12,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-04 21:05:24', '2026-03-04 13:05:24'),
(66, 'qc_record', 37, 'QC_APPROVED_FG', '{\"product_id\":41,\"quantity\":\"100.00\",\"batch_id\":55,\"expiry_date\":\"2027-03-04\",\"warehouse_location\":\"Lot 6720 Brgy San Joaquin Sto Tomas Batangas\",\"created_by\":\"1\"}', 1, '2026-03-04 21:07:16', '2026-03-04 13:07:16'),
(67, 'sales_order', 67, 'SALES_ORDER_DELIVERED', '{\"order_id\":67,\"assignment_id\":27,\"order_items\":[{\"product_id\":41,\"quantity\":100}]}', 0, NULL, '2026-03-04 13:08:47'),
(68, 'production_batch', 56, 'PRODUCTION_CONSUME', '{\"batch_id\":56,\"materials\":[{\"material_id\":30,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-04 21:26:52', '2026-03-04 13:26:52'),
(69, 'qc_record', 38, 'QC_APPROVED_FG', '{\"product_id\":41,\"quantity\":\"100.00\",\"batch_id\":56,\"expiry_date\":\"2027-03-04\",\"warehouse_location\":\"Lot 6720 Brgy San Joaquin Sto Tomas Batangas\",\"created_by\":\"1\"}', 1, '2026-03-04 21:28:16', '2026-03-04 13:28:16'),
(70, 'qc_record', 39, 'QC_APPROVED_FG', '{\"product_id\":24,\"quantity\":\"100.00\",\"batch_id\":57,\"expiry_date\":\"2027-03-04\",\"warehouse_location\":\"Lot 6720 Brgy San Joaquin Sto Tomas Batangas\",\"created_by\":\"1\"}', 1, '2026-03-04 21:28:34', '2026-03-04 13:28:34'),
(71, 'production_batch', 58, 'PRODUCTION_CONSUME', '{\"batch_id\":58,\"materials\":[{\"material_id\":6,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-07 10:39:27', '2026-03-07 02:39:27'),
(72, 'qc_record', 40, 'QC_APPROVED_FG', '{\"product_id\":1,\"quantity\":\"200.00\",\"batch_id\":58,\"expiry_date\":\"2028-03-07\",\"warehouse_location\":\"Lot 6720 Brgy San Joaquin Sto Tomas Batangas\",\"created_by\":\"1\"}', 1, '2026-03-07 10:40:55', '2026-03-07 02:40:55'),
(73, 'sales_order', 71, 'SALES_ORDER_DELIVERED', '{\"order_id\":71,\"assignment_id\":30,\"order_items\":[{\"product_id\":26,\"quantity\":30}]}', 0, NULL, '2026-03-07 02:42:41'),
(74, 'sales_order', 66, 'SALES_ORDER_DELIVERED', '{\"order_id\":66,\"assignment_id\":28,\"order_items\":[{\"product_id\":32,\"quantity\":100}]}', 0, NULL, '2026-03-07 02:43:06'),
(75, 'sales_order', 72, 'SALES_ORDER_DELIVERED', '{\"order_id\":72,\"assignment_id\":31,\"order_items\":[{\"product_id\":1,\"quantity\":200}]}', 0, NULL, '2026-03-07 02:44:08'),
(76, 'production_batch', 59, 'PRODUCTION_CONSUME', '{\"batch_id\":59,\"materials\":[{\"material_id\":27,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-08 14:37:19', '2026-03-08 06:37:19'),
(77, 'production_batch', 60, 'PRODUCTION_CONSUME', '{\"batch_id\":60,\"materials\":[{\"material_id\":27,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-08 14:56:33', '2026-03-08 06:56:33'),
(78, 'production_batch', 61, 'PRODUCTION_CONSUME', '{\"batch_id\":61,\"materials\":[{\"material_id\":30,\"quantity\":1}],\"created_by\":1}', 1, '2026-03-08 14:58:58', '2026-03-08 06:58:58'),
(79, 'sales_order', 68, 'SALES_ORDER_DELIVERED', '{\"order_id\":68,\"assignment_id\":32,\"order_items\":[{\"product_id\":41,\"quantity\":100},{\"product_id\":24,\"quantity\":100}]}', 0, NULL, '2026-03-08 07:05:33'),
(80, 'sales_order', 23, 'SALES_ORDER_DELIVERED', '{\"order_id\":23,\"assignment_id\":34,\"order_items\":[]}', 0, NULL, '2026-03-08 07:42:14'),
(81, 'sales_order', 20, 'SALES_ORDER_DELIVERED', '{\"order_id\":20,\"assignment_id\":33,\"order_items\":[]}', 0, NULL, '2026-03-08 07:42:38'),
(82, 'sales_order', 25, 'SALES_ORDER_DELIVERED', '{\"order_id\":25,\"assignment_id\":38,\"order_items\":[]}', 0, NULL, '2026-03-08 07:45:27'),
(83, 'sales_order', 39, 'SALES_ORDER_DELIVERED', '{\"order_id\":39,\"assignment_id\":37,\"order_items\":[{\"product_id\":26,\"quantity\":12}]}', 0, NULL, '2026-03-08 07:45:57'),
(84, 'sales_order', 19, 'SALES_ORDER_DELIVERED', '{\"order_id\":19,\"assignment_id\":35,\"order_items\":[]}', 0, NULL, '2026-03-08 07:46:17');

-- --------------------------------------------------------

--
-- Table structure for table `thirteenth_month`
--

CREATE TABLE `thirteenth_month` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_basic` decimal(10,2) DEFAULT 0.00,
  `computed_amount` decimal(10,2) DEFAULT 0.00,
  `released` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','production','warehouse','qc','accounting','sales','delivery','procurement') NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_code` varchar(50) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `full_name`, `email`, `phone_number`, `address`, `birth_date`, `profile_picture`, `last_login`, `created_at`, `updated_at`, `user_code`, `vehicle_type`) VALUES
(1, 'admin', 'admin123', 'admin', 'System Administrator', NULL, NULL, NULL, NULL, NULL, '2026-03-08 08:50:17', '2026-02-01 05:25:06', '2026-03-08 08:50:17', NULL, NULL),
(2, 'production', 'prod123', 'production', 'Production Manager', NULL, NULL, NULL, NULL, NULL, '2026-03-08 08:12:40', '2026-02-01 05:25:06', '2026-03-08 08:12:40', NULL, NULL),
(3, 'warehouse', 'ware123', 'warehouse', 'Warehouse Staff', NULL, NULL, NULL, NULL, NULL, '2026-03-07 02:51:59', '2026-02-01 05:25:06', '2026-03-07 02:51:59', NULL, NULL),
(4, 'qc', 'qc123', 'qc', 'Quality Control Inspector', NULL, NULL, NULL, NULL, NULL, '2026-03-04 11:40:30', '2026-02-01 05:25:06', '2026-03-04 11:40:30', NULL, NULL),
(5, 'accounting', 'acc123', 'accounting', 'Accountant', NULL, NULL, NULL, NULL, NULL, '2026-03-08 08:12:56', '2026-02-01 05:25:06', '2026-03-08 08:12:56', NULL, NULL),
(6, 'sales', 'sales123', 'sales', 'Sales Representative', NULL, NULL, NULL, NULL, NULL, '2026-03-02 11:29:10', '2026-02-01 05:25:06', '2026-03-02 11:29:10', NULL, NULL),
(7, 'delivery', 'del123', 'delivery', 'Delivery Driver', '', NULL, NULL, NULL, NULL, '2026-03-08 08:13:24', '2026-02-01 05:25:06', '2026-03-08 08:13:24', NULL, 'Truck'),
(8, 'f', 'francis', 'admin', 'fnm', 'fasfdd@gmail.com', NULL, NULL, NULL, NULL, NULL, '2026-02-01 05:52:55', '2026-02-06 15:12:59', NULL, NULL),
(9, 'delivery2', '123', 'delivery', 'deliver', 'fsfg@fajsfs.cofg', NULL, NULL, NULL, NULL, '2026-03-08 07:46:31', '2026-02-05 06:25:57', '2026-03-08 07:46:31', 'USR-20260205-0001', NULL),
(10, 'procurement', 'proc123', 'procurement', 'procurement manager', 'procurement@example.com', NULL, NULL, NULL, NULL, '2026-03-04 11:48:21', '2026-03-04 11:32:39', '2026-03-04 11:48:21', 'USR-20260304-0001', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_settings`
--

CREATE TABLE `warehouse_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouse_settings`
--

INSERT INTO `warehouse_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'low_stock_threshold', '50', 'Alert when stock below this number', '2026-03-01 15:17:27'),
(2, 'expiry_warning_days', '30', 'Days before expiry to warn', '2026-03-01 15:17:27'),
(3, 'default_location', 'Main Warehouse', 'Default storage location', '2026-03-01 15:17:27'),
(4, 'stock_method', 'FEFO', 'Stock handling method (FIFO or FEFO)', '2026-03-01 15:20:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounting_settings`
--
ALTER TABLE `accounting_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_user` (`user_id`),
  ADD KEY `idx_activity_created` (`created_at`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_attendance` (`employee_id`,`attendance_date`),
  ADD UNIQUE KEY `attendance_ref` (`attendance_ref`);

--
-- Indexes for table `batch_details`
--
ALTER TABLE `batch_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`);

--
-- Indexes for table `delivery_assignments`
--
ALTER TABLE `delivery_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `assignment_number` (`assignment_number`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `delivery_receipts`
--
ALTER TABLE `delivery_receipts`
  ADD PRIMARY KEY (`dr_id`),
  ADD UNIQUE KEY `dr_number` (`dr_number`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_dr_order` (`order_id`),
  ADD KEY `idx_dr_invoice` (`invoice_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD UNIQUE KEY `expense_ref` (`expense_ref`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `finished_goods`
--
ALTER TABLE `finished_goods`
  ADD PRIMARY KEY (`fg_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `goods_receiving_notes`
--
ALTER TABLE `goods_receiving_notes`
  ADD PRIMARY KEY (`grn_id`),
  ADD UNIQUE KEY `grn_number` (`grn_number`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `qc_checked_by` (`qc_checked_by`),
  ADD KEY `idx_grn_po` (`po_id`),
  ADD KEY `idx_grn_status` (`status`),
  ADD KEY `fk_grn_invoice` (`invoice_id`);

--
-- Indexes for table `gps_tracking`
--
ALTER TABLE `gps_tracking`
  ADD PRIMARY KEY (`tracking_id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Indexes for table `grn_items`
--
ALTER TABLE `grn_items`
  ADD PRIMARY KEY (`grn_item_id`),
  ADD KEY `grn_id` (`grn_id`),
  ADD KEY `po_item_id` (`po_item_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `qc_record_id` (`qc_record_id`);

--
-- Indexes for table `id_sequences`
--
ALTER TABLE `id_sequences`
  ADD PRIMARY KEY (`prefix`,`seq_date`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `grn_id` (`grn_id`),
  ADD KEY `return_id` (`return_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_invoice_items_invoice` (`invoice_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_order_items_order` (`order_id`),
  ADD KEY `idx_order_items_product` (`product_id`);

--
-- Indexes for table `pagination_settings`
--
ALTER TABLE `pagination_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `payment_number` (`payment_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_payments_invoice` (`invoice_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD UNIQUE KEY `payroll_ref` (`payroll_ref`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `payroll_breakdown`
--
ALTER TABLE `payroll_breakdown`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_id` (`payroll_id`);

--
-- Indexes for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  ADD PRIMARY KEY (`deduction_id`),
  ADD KEY `payroll_id` (`payroll_id`);

--
-- Indexes for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `po_items`
--
ALTER TABLE `po_items`
  ADD PRIMARY KEY (`po_item_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `production_batches`
--
ALTER TABLE `production_batches`
  ADD PRIMARY KEY (`batch_id`),
  ADD UNIQUE KEY `batch_number` (`batch_number`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `production_requests`
--
ALTER TABLE `production_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `request_group_id` (`request_group_id`);

--
-- Indexes for table `production_settings`
--
ALTER TABLE `production_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_setting` (`product_id`,`setting_key`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `product_category_materials`
--
ALTER TABLE `product_category_materials`
  ADD PRIMARY KEY (`category_id`,`material_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `pr_items`
--
ALTER TABLE `pr_items`
  ADD PRIMARY KEY (`pr_item_id`),
  ADD KEY `pr_id` (`pr_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `pr_id` (`pr_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_po_status` (`status`),
  ADD KEY `idx_po_supplier` (`supplier_id`);

--
-- Indexes for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD PRIMARY KEY (`pr_id`),
  ADD UNIQUE KEY `pr_number` (`pr_number`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `requested_by` (`requested_by`);

--
-- Indexes for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  ADD PRIMARY KEY (`pr_id`),
  ADD UNIQUE KEY `pr_number` (`pr_number`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_pr_status` (`status`);

--
-- Indexes for table `qc_records`
--
ALTER TABLE `qc_records`
  ADD PRIMARY KEY (`qc_id`);

--
-- Indexes for table `qc_rules`
--
ALTER TABLE `qc_rules`
  ADD PRIMARY KEY (`rule_id`);

--
-- Indexes for table `qc_settings`
--
ALTER TABLE `qc_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD PRIMARY KEY (`material_id`),
  ADD UNIQUE KEY `material_code` (`material_code`),
  ADD KEY `preferred_supplier_id` (`preferred_supplier_id`);

--
-- Indexes for table `raw_material_qc`
--
ALTER TABLE `raw_material_qc`
  ADD PRIMARY KEY (`qc_id`),
  ADD UNIQUE KEY `qc_number` (`qc_number`),
  ADD KEY `grn_item_id` (`grn_item_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `inspected_by` (`inspected_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_qc_status` (`qc_status`),
  ADD KEY `idx_qc_approval` (`approval_status`),
  ADD KEY `idx_qc_grn` (`grn_id`),
  ADD KEY `idx_qc_pending` (`qc_status`,`approval_status`);

--
-- Indexes for table `return_items`
--
ALTER TABLE `return_items`
  ADD PRIMARY KEY (`return_item_id`),
  ADD KEY `return_id` (`return_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_sales_invoice` (`invoice_id`),
  ADD KEY `idx_sales_invoice_generated` (`invoice_generated`,`status`),
  ADD KEY `fk_sales_orders_delivery_person` (`delivery_person_id`),
  ADD KEY `idx_fulfillment_type_status` (`fulfillment_type`,`status`);

--
-- Indexes for table `sales_settings`
--
ALTER TABLE `sales_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD UNIQUE KEY `supplier_code` (`supplier_code`),
  ADD KEY `idx_suppliers_status` (`status`);

--
-- Indexes for table `supplier_deliveries`
--
ALTER TABLE `supplier_deliveries`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `idx_supplier_delivery_date` (`supplier_id`,`delivery_date`);

--
-- Indexes for table `supplier_delivery_items`
--
ALTER TABLE `supplier_delivery_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `raw_material_id` (`raw_material_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_delivery_items` (`delivery_id`);

--
-- Indexes for table `supplier_invoices`
--
ALTER TABLE `supplier_invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_supplier_invoice_status` (`payment_status`);

--
-- Indexes for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `supplier_products`
--
ALTER TABLE `supplier_products`
  ADD PRIMARY KEY (`sp_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `supplier_returns`
--
ALTER TABLE `supplier_returns`
  ADD PRIMARY KEY (`return_id`),
  ADD UNIQUE KEY `return_number` (`return_number`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `grn_id` (`grn_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_returns_status` (`status`);

--
-- Indexes for table `system_events`
--
ALTER TABLE `system_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_entity_event` (`entity_type`,`entity_id`,`event_type`),
  ADD KEY `idx_processed` (`processed`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `thirteenth_month`
--
ALTER TABLE `thirteenth_month`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `user_code` (`user_code`);

--
-- Indexes for table `warehouse_settings`
--
ALTER TABLE `warehouse_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounting_settings`
--
ALTER TABLE `accounting_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batch_details`
--
ALTER TABLE `batch_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `delivery_assignments`
--
ALTER TABLE `delivery_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `delivery_receipts`
--
ALTER TABLE `delivery_receipts`
  MODIFY `dr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `finished_goods`
--
ALTER TABLE `finished_goods`
  MODIFY `fg_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `goods_receiving_notes`
--
ALTER TABLE `goods_receiving_notes`
  MODIFY `grn_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gps_tracking`
--
ALTER TABLE `gps_tracking`
  MODIFY `tracking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=295;

--
-- AUTO_INCREMENT for table `grn_items`
--
ALTER TABLE `grn_items`
  MODIFY `grn_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `pagination_settings`
--
ALTER TABLE `pagination_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payroll_breakdown`
--
ALTER TABLE `payroll_breakdown`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  MODIFY `deduction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `po_items`
--
ALTER TABLE `po_items`
  MODIFY `po_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `production_batches`
--
ALTER TABLE `production_batches`
  MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `production_requests`
--
ALTER TABLE `production_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `production_settings`
--
ALTER TABLE `production_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `pr_items`
--
ALTER TABLE `pr_items`
  MODIFY `pr_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  MODIFY `pr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  MODIFY `pr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `qc_records`
--
ALTER TABLE `qc_records`
  MODIFY `qc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `qc_rules`
--
ALTER TABLE `qc_rules`
  MODIFY `rule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `qc_settings`
--
ALTER TABLE `qc_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `raw_materials`
--
ALTER TABLE `raw_materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `raw_material_qc`
--
ALTER TABLE `raw_material_qc`
  MODIFY `qc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `return_items`
--
ALTER TABLE `return_items`
  MODIFY `return_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales_orders`
--
ALTER TABLE `sales_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `sales_settings`
--
ALTER TABLE `sales_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `supplier_deliveries`
--
ALTER TABLE `supplier_deliveries`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supplier_delivery_items`
--
ALTER TABLE `supplier_delivery_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supplier_invoices`
--
ALTER TABLE `supplier_invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `supplier_products`
--
ALTER TABLE `supplier_products`
  MODIFY `sp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_returns`
--
ALTER TABLE `supplier_returns`
  MODIFY `return_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_events`
--
ALTER TABLE `system_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `thirteenth_month`
--
ALTER TABLE `thirteenth_month`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `warehouse_settings`
--
ALTER TABLE `warehouse_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `batch_details`
--
ALTER TABLE `batch_details`
  ADD CONSTRAINT `batch_details_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `production_batches` (`batch_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `batch_details_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`material_id`);

--
-- Constraints for table `delivery_assignments`
--
ALTER TABLE `delivery_assignments`
  ADD CONSTRAINT `delivery_assignments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`order_id`),
  ADD CONSTRAINT `delivery_assignments_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `delivery_receipts`
--
ALTER TABLE `delivery_receipts`
  ADD CONSTRAINT `delivery_receipts_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`order_id`),
  ADD CONSTRAINT `delivery_receipts_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `delivery_receipts_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `delivery_receipts_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `finished_goods`
--
ALTER TABLE `finished_goods`
  ADD CONSTRAINT `finished_goods_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `goods_receiving_notes`
--
ALTER TABLE `goods_receiving_notes`
  ADD CONSTRAINT `fk_grn_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `supplier_invoices` (`invoice_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `goods_receiving_notes_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`),
  ADD CONSTRAINT `goods_receiving_notes_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `goods_receiving_notes_ibfk_3` FOREIGN KEY (`qc_checked_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `gps_tracking`
--
ALTER TABLE `gps_tracking`
  ADD CONSTRAINT `gps_tracking_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `delivery_assignments` (`assignment_id`);

--
-- Constraints for table `grn_items`
--
ALTER TABLE `grn_items`
  ADD CONSTRAINT `grn_items_ibfk_1` FOREIGN KEY (`grn_id`) REFERENCES `goods_receiving_notes` (`grn_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grn_items_ibfk_2` FOREIGN KEY (`po_item_id`) REFERENCES `po_items` (`po_item_id`),
  ADD CONSTRAINT `grn_items_ibfk_3` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`material_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `grn_items_ibfk_4` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `grn_items_ibfk_5` FOREIGN KEY (`qc_record_id`) REFERENCES `raw_material_qc` (`qc_id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`grn_id`) REFERENCES `goods_receiving_notes` (`grn_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_transactions_ibfk_3` FOREIGN KEY (`return_id`) REFERENCES `supplier_returns` (`return_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_transactions_ibfk_4` FOREIGN KEY (`grn_id`) REFERENCES `goods_receiving_notes` (`grn_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_transactions_ibfk_5` FOREIGN KEY (`return_id`) REFERENCES `supplier_returns` (`return_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_transactions_ibfk_6` FOREIGN KEY (`grn_id`) REFERENCES `goods_receiving_notes` (`grn_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_transactions_ibfk_7` FOREIGN KEY (`return_id`) REFERENCES `supplier_returns` (`return_id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`order_id`),
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `payroll_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payroll_breakdown`
--
ALTER TABLE `payroll_breakdown`
  ADD CONSTRAINT `payroll_breakdown_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`payroll_id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  ADD CONSTRAINT `payroll_deductions_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`payroll_id`) ON DELETE CASCADE;

--
-- Constraints for table `po_items`
--
ALTER TABLE `po_items`
  ADD CONSTRAINT `po_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `po_items_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`material_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `po_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- Constraints for table `production_batches`
--
ALTER TABLE `production_batches`
  ADD CONSTRAINT `production_batches_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `production_batches_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `product_category_materials`
--
ALTER TABLE `product_category_materials`
  ADD CONSTRAINT `product_category_materials_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_category_materials_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`material_id`) ON DELETE CASCADE;

--
-- Constraints for table `pr_items`
--
ALTER TABLE `pr_items`
  ADD CONSTRAINT `pr_items_ibfk_1` FOREIGN KEY (`pr_id`) REFERENCES `purchase_requisitions` (`pr_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pr_items_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`material_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pr_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`pr_id`) REFERENCES `purchase_requisitions` (`pr_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD CONSTRAINT `purchase_requests_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `purchase_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  ADD CONSTRAINT `purchase_requisitions_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_requisitions_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD CONSTRAINT `raw_materials_ibfk_1` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `raw_materials_ibfk_2` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `raw_materials_ibfk_3` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `raw_material_qc`
--
ALTER TABLE `raw_material_qc`
  ADD CONSTRAINT `raw_material_qc_ibfk_1` FOREIGN KEY (`grn_id`) REFERENCES `goods_receiving_notes` (`grn_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `raw_material_qc_ibfk_2` FOREIGN KEY (`grn_item_id`) REFERENCES `grn_items` (`grn_item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `raw_material_qc_ibfk_3` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`material_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `raw_material_qc_ibfk_4` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `raw_material_qc_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `return_items`
--
ALTER TABLE `return_items`
  ADD CONSTRAINT `return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `supplier_returns` (`return_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_items_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`material_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `return_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD CONSTRAINT `fk_sales_orders_delivery_person` FOREIGN KEY (`delivery_person_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `sales_orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `sales_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `sales_orders_ibfk_4` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_orders_ibfk_5` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_orders_ibfk_6` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_orders_ibfk_7` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_orders_ibfk_8` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE SET NULL;

--
-- Constraints for table `supplier_deliveries`
--
ALTER TABLE `supplier_deliveries`
  ADD CONSTRAINT `supplier_deliveries_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `supplier_deliveries_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `supplier_delivery_items`
--
ALTER TABLE `supplier_delivery_items`
  ADD CONSTRAINT `supplier_delivery_items_ibfk_1` FOREIGN KEY (`delivery_id`) REFERENCES `supplier_deliveries` (`delivery_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_delivery_items_ibfk_2` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`material_id`),
  ADD CONSTRAINT `supplier_delivery_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `supplier_invoices`
--
ALTER TABLE `supplier_invoices`
  ADD CONSTRAINT `supplier_invoices_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `supplier_invoices_ibfk_2` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `supplier_invoices_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `supplier_products`
--
ALTER TABLE `supplier_products`
  ADD CONSTRAINT `supplier_products_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_products_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`material_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `supplier_products_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- Constraints for table `supplier_returns`
--
ALTER TABLE `supplier_returns`
  ADD CONSTRAINT `supplier_returns_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `supplier_returns_ibfk_2` FOREIGN KEY (`grn_id`) REFERENCES `goods_receiving_notes` (`grn_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `supplier_returns_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `supplier_returns_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `supplier_returns_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: lorinims_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `unit` varchar(20) DEFAULT 'pcs',
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fermentation_eligible` tinyint(1) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shelf_life_days` int(11) NOT NULL DEFAULT 365 COMMENT 'Number of days product remains shelf-stable after production. Used for automatic expiry date calculation.',
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Lorins Patis Flavor 150 mL pouch','Lorins Patis Flavor 150 mL pouch','patis-POUCH-150ML.webp','pcs',1,'2026-02-01 05:25:06',1,11.27,730),(2,'Lorins Patis Flavor 350 mL PET bottle','Lorins Patis Flavor 350 mL PET bottle','PATIS-350ML-PETbottle.webp','pcs',1,'2026-02-01 05:25:06',1,22.67,730),(3,'Lorins Patis Flavor with Chili 350 mL PET bottle','Lorins Patis Flavor with Chili 350 mL PET bottle','CHILI-PATIS-350ML-PETbottle.webp','pcs',8,'2026-02-01 05:25:06',1,28.31,730),(4,'Lorins Patis Flavor 1 L','Lorins Patis Flavor 1 Liter','PATIS-1LITER.webp','pcs',1,'2026-02-01 05:25:06',1,68.91,730),(5,'Lorins Patis Flavor 1893 mL (Half Gallon)','Lorins Patis Flavor 1893 mL (Half Gallon)','PATIS-HALF-GALLON.webp','pcs',1,'2026-02-01 05:25:06',1,126.60,730),(6,'Lorins Patis Flavor 3785 mL (Gallon)','Lorins Patis Flavor 3785 mL (Gallon)','PATIS-1GALLON.webp','pcs',1,'2026-02-01 05:25:06',1,221.60,730),(7,'Lorins Soy Sauce 350 mL PET bottle','Lorins Soy Sauce 350 mL PET bottle','SOY-SAUCE-350ML.webp','pcs',2,'2026-02-01 05:25:06',1,19.89,1095),(8,'Lorins Soy Sauce 1 L','Lorins Soy Sauce 1 Liter','SOY-SAUCE-1LITER.webp','pcs',2,'2026-02-01 05:25:06',1,51.63,1095),(9,'Lorins Soy Sauce 3785 mL (Gallon)','Lorins Soy Sauce 3785 mL (Gallon)','SOY-SAUCE-1GALLON.webp','pcs',2,'2026-02-01 05:25:06',1,176.76,1095),(10,'Lorins Coco Suka 150 mL','Lorins Coco Suka 150 mL','COCO-SUKA-150ML.webp','pcs',3,'2026-02-01 05:25:06',1,44.89,365),(11,'Lorins Coco Suka 310 mL','Lorins Coco Suka 310 mL','COCO-SUKA-310ML.webp','pcs',3,'2026-02-01 05:25:06',1,68.25,365),(12,'Lorins Coco Suka 800 mL','Lorins Coco Suka 800 mL','COCO-SUKA-800ML.webp','pcs',3,'2026-02-01 05:25:06',1,156.80,365),(13,'Lorins Budget / Value Pack','Lorins Budget / Value Pack (Vinegar + Fish Sauce + Soy Sauce)','BUDGET-PACK(vinegar, fishsauce-and-soysauce).webp','pcs',3,'2026-02-01 05:25:06',1,56.20,730),(14,'Lorins Alamang Guisado Original 8 oz / 250 g','Lorins Alamang Guisado Original 8 oz / 250 g','ALAMANG-GUISADO-ORIGINAL-8oz.webp','pcs',4,'2026-02-01 05:25:06',1,95.08,365),(15,'Lorins Alamang Guisado Sweet','Lorins Alamang Guisado Sweet','ALAMANG-GUISADO-SWEET-8oz.webp','pcs',4,'2026-02-01 05:25:06',1,95.08,365),(16,'Lorins Alamang Guisado Spicy','Lorins Alamang Guisado Spicy','ALAMANG-GUISADO-SPICY-8oz.webp','pcs',4,'2026-02-01 05:25:06',1,95.08,365),(17,'Lorenzana Bagoong Isda Original 310 mL','Lorenzana Bagoong Isda Original 310 mL','BAGOONG-ISDA-original-310ML.webp','pcs',5,'2026-02-01 05:25:06',1,38.79,730),(18,'Lorins Crab Paste 8 oz','Lorins Crab Paste 8 oz','CRAB-PASTE-8oz.webp','pcs',6,'2026-02-01 05:25:06',1,169.22,730),(19,'Lorins Coconut Milk 400 mL','Lorins Coconut Milk 400 mL tin','COCONUT-MILK.webp','pcs',6,'2026-02-01 05:25:06',1,67.81,365),(20,'Lorins Premium Extra-Virgin Anchovy Extract 200 mL','Lorins Premium Extra-Virgin Anchovy Extract 200 mL','PREMIUM-extra-virgin-anchovy-ectract-200ML.webp','pcs',7,'2026-02-01 05:25:06',1,57.49,365),(21,'Lorins Fish Sauce 800 mL glass bottle','Lorins Fish Sauce 800 mL glass bottle','patis-800ML.webp','pcs',8,'2026-02-01 05:25:06',1,92.42,730),(23,'Lorins Coco Suka Spicy-Sweet 310 mL','Lorins Coco Suka Spicy-Sweet 310 mL','COCO-SUKA-310ML.webp','pcs',8,'2026-02-01 05:25:06',1,68.25,365),(24,'Filtaste Nata de Coco 12 oz','Filtaste Nata de Coco 12 oz','NATA-DE-COCO-12OZ.webp','pcs',9,'2026-02-01 06:38:24',1,60.00,365),(26,'Filtaste Kaong 12 oz','Filtaste Kaong 12 oz','KAONG-12OZ.webp','pcs',9,'2026-02-01 06:38:24',1,85.00,365),(28,'Lorins Patis Puro 150 mL','Lorins Patis Puro 150 mL','patis-PURO-150ML.webp','pcs',1,'2026-02-01 06:38:24',1,33.60,730),(29,'Lorins Patis Puro 310 mL','Lorins Patis Puro 310 mL','patis-PURO-310ML.webp','pcs',1,'2026-02-01 06:38:24',1,48.83,730),(30,'Lorins Patis Puro Chili Mansi 150 mL','Lorins Patis Puro Chili Mansi 150 mL','patis-puro-CHILIMANSI-150ML.webp','pcs',8,'2026-02-01 06:38:24',1,37.70,730),(31,'Lorins Patis Puro Chili Mansi 310 mL','Lorins Patis Puro Chili Mansi 310 mL','patis-puro-CHILIMANSI-310ML.webp','pcs',8,'2026-02-01 06:38:24',1,57.09,730),(32,'Lorins Patis Puro Mansi 150 mL','Lorins Patis Puro Mansi 150 mL','patis-PURO-MANSI-150ML.webp','pcs',8,'2026-02-01 06:38:24',1,36.33,730),(33,'Lorins Patis Flavor 7+1 Tipid Pouch','Lorins Patis Flavor 7+1 Tipid Pouch','Lorins-patis-7+1(patis-flavor-tipidpouch).webp','pcs',1,'2026-02-01 06:38:24',1,78.79,730),(34,'Lorins Patis Twin Pack 1L x 2','Lorins Patis Twin Pack 1 Liter x 2','patis-TWINPACK(1litterx2).webp','pcs',1,'2026-02-01 06:38:24',1,131.01,730),(35,'Lorins Patis Pouch 350 mL','Lorins Patis Pouch 350 mL','patis-POUCH-350ML.webp','pcs',1,'2026-02-01 06:38:24',1,20.66,730),(36,'Lorins Vinegar 350 mL','Lorins Vinegar 350 mL','VINEGAR-350ML.webp','pcs',3,'2026-02-01 06:38:24',1,17.24,1095),(37,'Lorins Vinegar 1 L','Lorins Vinegar 1 Liter','VINEGAR-1LITER.webp','pcs',3,'2026-02-01 06:38:24',1,40.94,1095),(38,'Lorins Vinegar 3785 mL (Gallon)','Lorins Vinegar Gallon','VINEGAR-1GALLON.webp','pcs',3,'2026-02-01 06:38:24',1,151.03,1095),(39,'Lorins Value Pack (Soy Sauce + Vinegar + Free Patis Pouch)','Lorins Value Pack with free patis pouch','BUDGET-PACK(vinegar, fishsauce-and-soysauce).webp','pcs',3,'2026-02-01 06:38:24',1,91.68,730),(40,'Lorins Patis Puro 800 mL','Lorins Patis Puro 800 mL','Lorins_Patis_Puro_800_mL_1772103212.webp','pcs',1,'2026-02-26 09:42:57',1,92.42,730),(41,'Filtaste Nata de Coco 32 oz','Filtaste Nata de Coco 32 oz','NATA-DE-COCO-32OZ.webp','pcs',9,'2026-02-26 09:42:57',1,124.53,365),(42,'Filtaste Kaong 32 oz','Filtaste Kaong 32 oz','KAONG-32OZ.webp','pcs',9,'2026-02-26 09:42:57',1,163.77,365);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `raw_materials`
--

DROP TABLE IF EXISTS `raw_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `raw_materials` (
  `material_id` int(11) NOT NULL AUTO_INCREMENT,
  `material_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(20) DEFAULT 'kg',
  `expiry_date` date DEFAULT NULL,
  `warehouse_location` varchar(100) DEFAULT NULL,
  `min_stock_level` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `material_code` varchar(50) DEFAULT NULL,
  `preferred_supplier_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`material_id`),
  UNIQUE KEY `material_code` (`material_code`),
  KEY `preferred_supplier_id` (`preferred_supplier_id`),
  CONSTRAINT `raw_materials_ibfk_1` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL,
  CONSTRAINT `raw_materials_ibfk_2` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL,
  CONSTRAINT `raw_materials_ibfk_3` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `raw_materials`
--

LOCK TABLES `raw_materials` WRITE;
/*!40000 ALTER TABLE `raw_materials` DISABLE KEYS */;
INSERT INTO `raw_materials` VALUES (1,'Soybeans','Raw Material',100.00,'kg','2026-04-02','',100.00,'2026-02-01 05:25:06','2026-03-02 07:35:57',NULL,NULL),(2,'Salt','Label Materials',187.00,'kg',NULL,'',500.00,'2026-02-01 05:25:06','2026-03-08 07:02:14',NULL,NULL),(3,'Sugar','Raw Material',100.00,'kg',NULL,NULL,50.00,'2026-02-01 05:25:06','2026-03-02 07:35:57',NULL,NULL),(6,'Tape','Packaging',99.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',100.00,'2026-02-26 10:01:55','2026-03-07 02:39:27','MAT-20260226-0001',NULL),(9,'Fermented fish','Seafood & Protein Sources',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',10.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(10,'Fish extract','Seafood & Protein Sources',100.00,'liters',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',5.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(11,'Whole fish (Mackerel)','Seafood & Protein Sources',100.00,'kg','2026-03-19','Lot 6720 Brgy San Joaquin Sto Tomas Batangas',8.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(12,'Shrimp paste (alam??ng)','Seafood & Protein Sources',99.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',5.00,'2026-03-02 05:10:13','2026-03-04 13:05:24',NULL,NULL),(13,'Dried shrimp','Seafood & Protein Sources',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',5.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(14,'Iodized salt','Minerals & Salts',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',20.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(15,'Salt (sea salt)','Minerals & Salts',88.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',20.00,'2026-03-02 05:10:13','2026-03-04 11:10:35',NULL,NULL),(16,'Soya beans','Plant-Derived Ingredients',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',15.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(17,'Red hot chili / chili slices','Plant-Derived Ingredients',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',5.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(18,'Calamansi flavor / blend','Plant-Derived Ingredients',100.00,'liters',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',3.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(19,'Caramel (coloring/flavor)','Plant-Derived Ingredients',100.00,'liters',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',5.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(20,'Water','Plant-Derived Ingredients',100.00,'liters',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',50.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(21,'Garlic','Plant-Derived Ingredients',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',10.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(22,'Onion','Plant-Derived Ingredients',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',10.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(23,'Potassium sorbate (preservative)','Additives & Preservatives',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',2.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(24,'Sodium benzoate (preservative)','Additives & Preservatives',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',2.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(25,'Monosodium glutamate (MSG)','Additives & Preservatives',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',3.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(26,'Disodium inosinate (flavor enhancer)','Additives & Preservatives',99.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',1.00,'2026-03-02 05:10:13','2026-03-04 12:47:41',NULL,NULL),(27,'Glass bottles','Packaging Materials',97.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',100.00,'2026-03-02 05:10:13','2026-03-08 06:56:33',NULL,NULL),(28,'PET plastic bottles','Packaging Materials',100.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',150.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(29,'Plastic sachets (PET/AL/PE laminate)','Packaging Materials',100.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',500.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(30,'Bottle caps / Lids','Packaging Materials',96.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',200.00,'2026-03-02 05:10:13','2026-03-08 06:58:58',NULL,NULL),(31,'Coated paper label stock','Label Materials',100.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',100.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(32,'Uncoated paper label','Label Materials',99.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',100.00,'2026-03-02 05:10:13','2026-03-02 07:36:34',NULL,NULL),(33,'BOPP film labels','Label Materials',100.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',150.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(34,'Vinyl film labels','Label Materials',100.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',100.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(35,'Polyester (PET) film labels','Label Materials',100.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',100.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(36,'0','Procurement',123.00,'0',NULL,'',0.00,'2026-03-04 12:00:21','2026-03-04 12:00:21','MAT-20260304-0001',NULL);
/*!40000 ALTER TABLE `raw_materials` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-09 18:44:13
SET FOREIGN_KEY_CHECKS=1;
