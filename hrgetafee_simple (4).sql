-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 28, 2026 at 12:28 PM
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
-- Database: `hrgetafee_simple`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `visible_to_roles` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `clock_in` datetime DEFAULT NULL,
  `clock_out` datetime DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `status` enum('present','absent','late') DEFAULT 'present',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `qr_scanned` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_attendance_employee3`
--

CREATE TABLE `backup_attendance_employee3` (
  `attendance_id` int(11) NOT NULL DEFAULT 0,
  `employee_id` int(11) NOT NULL,
  `clock_in` datetime DEFAULT NULL,
  `clock_out` datetime DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `status` enum('present','absent','late') DEFAULT 'present',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `qr_scanned` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_history`
--

CREATE TABLE `backup_history` (
  `backup_id` int(11) NOT NULL,
  `backup_name` varchar(255) NOT NULL,
  `backup_type` varchar(50) DEFAULT NULL,
  `backup_size` bigint(20) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `backup_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_leave_requests_employee3`
--

CREATE TABLE `backup_leave_requests_employee3` (
  `leave_id` int(11) NOT NULL DEFAULT 0,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `number_of_days` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','denied') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_payroll_all`
--

CREATE TABLE `backup_payroll_all` (
  `payroll_id` int(11) NOT NULL DEFAULT 0,
  `employee_id` int(11) NOT NULL,
  `payroll_month` int(11) DEFAULT NULL,
  `payroll_year` int(11) DEFAULT NULL,
  `gross_salary` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `net_salary` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','processed','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `backup_payroll_all`
--

INSERT INTO `backup_payroll_all` (`payroll_id`, `employee_id`, `payroll_month`, `payroll_year`, `gross_salary`, `deductions`, `net_salary`, `status`, `created_at`) VALUES
(1, 4, 7, 2026, 22000.00, 2640.00, 19360.00, 'paid', '2026-07-07 04:39:02'),
(2, 3, 7, 2026, 25000.00, 3000.00, 22000.00, 'paid', '2026-07-07 04:46:36'),
(3, 5, 7, 2026, 20000.00, 4730.02, 15269.98, 'processed', '2026-07-07 06:18:20');

-- --------------------------------------------------------

--
-- Table structure for table `backup_payroll_details_all`
--

CREATE TABLE `backup_payroll_details_all` (
  `payroll_detail_id` int(11) NOT NULL DEFAULT 0,
  `payroll_id` int(11) NOT NULL,
  `detail_type` enum('earning','deduction') NOT NULL,
  `detail_name` varchar(100) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `backup_payroll_details_all`
--

INSERT INTO `backup_payroll_details_all` (`payroll_detail_id`, `payroll_id`, `detail_type`, `detail_name`, `amount`, `quantity`, `created_at`) VALUES
(1, 3, 'earning', 'Gross Salary', 20000.00, 1, '2026-07-07 06:18:20'),
(2, 3, 'deduction', 'SSS Contribution', 726.00, 1, '2026-07-07 06:18:20'),
(3, 3, 'deduction', 'PhilHealth Premium', 550.00, 1, '2026-07-07 06:18:20'),
(4, 3, 'deduction', 'Pag-IBIG Contribution', 400.00, 1, '2026-07-07 06:18:20'),
(5, 3, 'deduction', 'CPF (Consolidated Police Fund)', 971.75, 1, '2026-07-07 06:18:20'),
(6, 3, 'deduction', 'CBIF (Comprehensive Benefit Insurance)', 0.00, 1, '2026-07-07 06:18:20'),
(7, 3, 'deduction', 'MPL (Magna Carta for Public Employees)', 0.00, 1, '2026-07-07 06:18:20'),
(8, 3, 'deduction', 'Absence Deduction (0 × ₱500)', 0.00, 1, '2026-07-07 06:18:20'),
(9, 3, 'deduction', 'Late Deduction (0 × ₱100)', 0.00, 1, '2026-07-07 06:18:20'),
(10, 3, 'deduction', 'BIR Income Tax (12%)', 2082.27, 1, '2026-07-07 06:18:20');

-- --------------------------------------------------------

--
-- Table structure for table `backup_payroll_details_employee3`
--

CREATE TABLE `backup_payroll_details_employee3` (
  `payroll_detail_id` int(11) NOT NULL DEFAULT 0,
  `payroll_id` int(11) NOT NULL,
  `detail_type` enum('earning','deduction') NOT NULL,
  `detail_name` varchar(100) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_payroll_employee3`
--

CREATE TABLE `backup_payroll_employee3` (
  `payroll_id` int(11) NOT NULL DEFAULT 0,
  `employee_id` int(11) NOT NULL,
  `payroll_month` int(11) DEFAULT NULL,
  `payroll_year` int(11) DEFAULT NULL,
  `gross_salary` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `net_salary` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','processed','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `backup_payroll_employee3`
--

INSERT INTO `backup_payroll_employee3` (`payroll_id`, `employee_id`, `payroll_month`, `payroll_year`, `gross_salary`, `deductions`, `net_salary`, `status`, `created_at`) VALUES
(2, 3, 7, 2026, 25000.00, 3000.00, 22000.00, 'paid', '2026-07-07 04:46:36');

-- --------------------------------------------------------

--
-- Table structure for table `biometric_import_logs`
--

CREATE TABLE `biometric_import_logs` (
  `import_id` int(11) NOT NULL,
  `imported_by` int(11) NOT NULL,
  `import_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `filename` varchar(255) DEFAULT NULL,
  `total_records` int(11) DEFAULT NULL,
  `successful_records` int(11) DEFAULT NULL,
  `failed_records` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deduction_types`
--

CREATE TABLE `deduction_types` (
  `deduction_id` int(11) NOT NULL,
  `deduction_name` varchar(50) NOT NULL,
  `deduction_code` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `calculation_type` enum('fixed','percentage') DEFAULT 'fixed',
  `default_rate` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deduction_types`
--

INSERT INTO `deduction_types` (`deduction_id`, `deduction_name`, `deduction_code`, `description`, `is_mandatory`, `calculation_type`, `default_rate`, `created_at`) VALUES
(1, 'SSS', 'SSS', 'Social Security System', 1, 'percentage', 3.63, '2026-07-07 06:10:39'),
(2, 'PhilHealth', 'PH', 'PhilHealth Insurance', 1, 'percentage', 2.75, '2026-07-07 06:10:39'),
(3, 'Pag-IBIG', 'PAGIBIG', 'Home Development Mutual Fund', 1, 'percentage', 2.00, '2026-07-07 06:10:39'),
(4, 'BIR - Income Tax', 'BIR', 'Bureau of Internal Revenue Tax', 1, 'percentage', 0.00, '2026-07-07 06:10:39'),
(5, 'CPF', 'CPF', 'Consolidated Police Fund', 1, 'fixed', 971.75, '2026-07-07 06:10:39'),
(6, 'CBIF', 'CBIF', 'Comprehensive Benefit Insurance Fund', 1, 'fixed', 0.00, '2026-07-07 06:10:39'),
(7, 'MPL', 'MPL', 'Magna Carta for Public Employees Levy', 1, 'fixed', 0.00, '2026-07-07 06:10:39'),
(8, 'Absence Deduction', 'ABSENCE', 'Deduction for absences', 0, 'fixed', 500.00, '2026-07-07 06:10:39'),
(9, 'Late Deduction', 'LATE', 'Deduction for late arrivals', 0, 'fixed', 100.00, '2026-07-07 06:10:39');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `dept_head` varchar(100) DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `dept_name`, `dept_head`, `budget`, `created_date`, `is_deleted`, `deleted_at`, `deleted_by`) VALUES
(1, 'Human Resources', 'HR', 0.00, '2026-07-11 13:34:05', 0, NULL, NULL),
(2, 'Finance', 'FIN', 0.00, '2026-07-11 13:34:05', 0, NULL, NULL),
(3, 'Operations', 'OPS', 0.00, '2026-07-11 13:34:05', 0, NULL, NULL),
(4, 'IT Support', 'IT', 0.00, '2026-07-11 13:34:05', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `employee_code` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `employment_type` enum('Regular','Casual','Job Order','COS') DEFAULT 'Regular',
  `salary` decimal(10,2) DEFAULT NULL,
  `date_hired` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `department` varchar(100) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `qr_generated_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `employee_code`, `first_name`, `last_name`, `email`, `phone`, `position`, `employment_type`, `salary`, `date_hired`, `status`, `created_at`, `updated_at`, `department`, `qr_code`, `qr_generated_date`) VALUES
(1, 'GETAFE-2026-001', 'Admin', 'User', 'admin@getafe.gov.ph', '09123456789', 'HR Administrator', 'Regular', 50000.01, '2020-01-01', 'active', '2026-07-07 04:29:42', '2026-10-02 11:40:48', NULL, NULL, NULL),
(2, 'GETAFE-2026-002', 'Maria', 'Santos', 'maria@getafe.gov.ph', '09234567890', 'Faculty', 'COS', 35000.00, '2021-03-15', 'inactive', '2026-07-07 04:29:42', '2026-10-02 06:58:50', NULL, NULL, NULL),
(3, 'GETAFE-2026-003', 'John', 'Dela Cruz', 'john@getafe.gov.ph', '09345678901', 'BSIS- Instructor', 'Regular', 25000.00, '2022-01-20', 'active', '2026-07-07 04:29:42', '2026-10-02 12:56:36', NULL, NULL, NULL),
(4, 'GETAFE-2026-004', 'Rosa', 'Garcia', 'rosa@getafe.gov.ph', '09456789012', 'Faculty', 'COS', 22000.00, '2022-05-10', 'inactive', '2026-07-07 04:29:42', '2026-10-02 06:59:47', NULL, NULL, NULL),
(5, 'GETAFE-2026-005', 'Pedro', 'Lopez', 'pedro@getafe.gov.ph', '09567890123', 'Faculty', 'COS', 20000.00, '2023-01-15', 'inactive', '2026-07-07 04:29:42', '2026-10-02 07:00:13', NULL, NULL, NULL),
(6, 'GETAFE-2026-006', 'Janah', 'Breva', 'm.janey@gmail.com', '09701918626', 'Sample', 'COS', 200.00, '2026-07-11', 'inactive', '2026-07-11 12:54:00', '2026-10-02 07:00:51', NULL, NULL, NULL),
(7, 'GETAFE-2026-007', 'cejay', 'cutarra', 'gxsgxhxbs@gmail.com', '09999', 'Sample', 'COS', 1.00, '2026-07-14', 'inactive', '2026-07-14 05:01:42', '2026-10-02 07:01:13', NULL, NULL, NULL),
(8, 'GETAFE-2026-008', 'rose', 'breva', 'brevarose82@gmail.com', '099987875', 'Sample', 'COS', 100000.00, '2026-07-31', 'inactive', '2026-07-31 01:40:08', '2026-10-02 07:01:34', NULL, NULL, NULL),
(9, 'GETAFE-2026-009', 'Grecia Joy', 'Casenas', 'grecia123@gmail.com', '09701918626', 'Program Head-BSIS', 'COS', 10000.00, '2026-07-31', 'active', '2026-07-31 01:58:10', '2026-10-02 11:38:10', NULL, NULL, NULL),
(11, 'GETAFE-2026-010', 'Rose', 'Breva', 'rosebreva@gmail.com', '09345678901', 'Faculty', 'Casual', 400.00, '2026-10-02', 'active', '2026-10-02 08:11:24', '2026-10-02 08:11:24', NULL, NULL, NULL),
(12, 'GETAFE-2026-011', 'Cejay', 'Cuttara', 'CejayCuttara@gmail.com', '09345678901', 'Faculty', 'Job Order', 399.92, '2026-10-02', 'active', '2026-10-02 08:29:15', '2026-10-02 08:29:15', NULL, NULL, NULL),
(13, 'GETAFE-2026-012', 'daryl', 'eman', 'daryljoyceeman@gmail.com', '09052735095', 'Faculty', '', 10000.00, '2026-10-15', 'active', '2026-10-15 01:50:09', '2026-10-15 01:50:09', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_deductions`
--

CREATE TABLE `employee_deductions` (
  `emp_deduction_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `deduction_id` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `document_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_documents`
--

INSERT INTO `employee_documents` (`document_id`, `employee_id`, `document_type`, `document_name`, `file_path`, `file_size`, `uploaded_by`, `uploaded_at`, `is_active`, `notes`, `created_at`) VALUES
(1, 3, 'other', 'sample', 'C:\\xampp\\htdocs\\hrgetafee-simple\\admin/../uploads/employee_documents/3/1790919130_logo.jpg', 33133, 1, '2026-10-02 05:32:10', 1, '', '2026-10-02 05:32:10');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `holiday_id` int(11) NOT NULL,
  `holiday_name` varchar(100) NOT NULL,
  `holiday_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`holiday_id`, `holiday_name`, `holiday_date`, `created_at`, `is_deleted`, `deleted_at`, `deleted_by`) VALUES
(1, 'New Year', '2026-01-01', '2026-07-07 04:29:43', 0, NULL, NULL),
(2, 'EDSA Revolution', '2026-02-25', '2026-07-07 04:29:43', 0, NULL, NULL),
(3, 'Labor Day', '2026-05-01', '2026-07-07 04:29:43', 0, NULL, NULL),
(4, 'Independence Day', '2026-06-12', '2026-07-07 04:29:43', 0, NULL, NULL),
(5, 'Christmas Day', '2026-12-25', '2026-07-07 04:29:43', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `hours_fraction_conversion`
--

CREATE TABLE `hours_fraction_conversion` (
  `conversion_id` int(11) NOT NULL,
  `hours` int(11) DEFAULT NULL,
  `minutes` int(11) DEFAULT NULL,
  `fraction_of_day` decimal(5,4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hours_fraction_conversion`
--

INSERT INTO `hours_fraction_conversion` (`conversion_id`, `hours`, `minutes`, `fraction_of_day`, `created_at`) VALUES
(1, 1, 0, 0.1250, '2026-07-07 06:10:39'),
(2, 2, 0, 0.2500, '2026-07-07 06:10:39'),
(3, 3, 0, 0.3750, '2026-07-07 06:10:39'),
(4, 4, 0, 0.5000, '2026-07-07 06:10:39'),
(5, 5, 0, 0.6250, '2026-07-07 06:10:39'),
(6, 6, 0, 0.7500, '2026-07-07 06:10:39'),
(7, 7, 0, 0.8750, '2026-07-07 06:10:39'),
(8, 8, 0, 1.0000, '2026-07-07 06:10:39'),
(9, 1, 15, 0.1313, '2026-07-07 06:10:39'),
(10, 1, 30, 0.1375, '2026-07-07 06:10:39'),
(11, 1, 45, 0.1438, '2026-07-07 06:10:39'),
(12, 2, 15, 0.2438, '2026-07-07 06:10:39'),
(13, 2, 30, 0.3125, '2026-07-07 06:10:39'),
(14, 2, 45, 0.3438, '2026-07-07 06:10:39'),
(15, 3, 15, 0.4063, '2026-07-07 06:10:39'),
(16, 3, 30, 0.4375, '2026-07-07 06:10:39'),
(17, 3, 45, 0.4688, '2026-07-07 06:10:39'),
(18, 4, 15, 0.5313, '2026-07-07 06:10:39'),
(19, 4, 30, 0.5625, '2026-07-07 06:10:39'),
(20, 4, 45, 0.5938, '2026-07-07 06:10:39'),
(21, 5, 15, 0.6563, '2026-07-07 06:10:39'),
(22, 5, 30, 0.6875, '2026-07-07 06:10:39'),
(23, 5, 45, 0.7188, '2026-07-07 06:10:39'),
(24, 6, 15, 0.7813, '2026-07-07 06:10:39'),
(25, 6, 30, 0.8125, '2026-07-07 06:10:39'),
(26, 6, 45, 0.8438, '2026-07-07 06:10:39'),
(27, 7, 15, 0.9063, '2026-07-07 06:10:39'),
(28, 7, 30, 0.9375, '2026-07-07 06:10:39'),
(29, 7, 45, 0.9688, '2026-07-07 06:10:39');

-- --------------------------------------------------------

--
-- Table structure for table `leave_compensation`
--

CREATE TABLE `leave_compensation` (
  `compensation_id` int(11) NOT NULL,
  `ams_value` decimal(5,1) DEFAULT NULL,
  `leave_earned_days` decimal(5,2) DEFAULT NULL,
  `leave_earned_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_compensation`
--

INSERT INTO `leave_compensation` (`compensation_id`, `ams_value`, `leave_earned_days`, `leave_earned_amount`, `created_at`) VALUES
(1, 0.5, 1.23, 1.23, '2026-07-07 06:10:39'),
(2, 1.0, 1.20, 1.20, '2026-07-07 06:10:39'),
(3, 1.5, 1.19, 1.19, '2026-07-07 06:10:39'),
(4, 2.0, 1.17, 1.17, '2026-07-07 06:10:39'),
(5, 2.5, 1.15, 1.15, '2026-07-07 06:10:39'),
(6, 3.0, 1.13, 1.13, '2026-07-07 06:10:39'),
(7, 3.5, 1.10, 1.10, '2026-07-07 06:10:39'),
(8, 4.0, 1.08, 1.08, '2026-07-07 06:10:39'),
(9, 4.5, 1.06, 1.06, '2026-07-07 06:10:39'),
(10, 5.0, 1.04, 1.04, '2026-07-07 06:10:39'),
(11, 5.5, 1.02, 1.02, '2026-07-07 06:10:39'),
(12, 6.0, 1.00, 1.00, '2026-07-07 06:10:39'),
(13, 6.5, 0.98, 0.98, '2026-07-07 06:10:39'),
(14, 7.0, 0.96, 0.96, '2026-07-07 06:10:39'),
(15, 7.5, 0.94, 0.94, '2026-07-07 06:10:39'),
(16, 8.0, 0.92, 0.92, '2026-07-07 06:10:39'),
(17, 8.5, 0.90, 0.90, '2026-07-07 06:10:39'),
(18, 9.0, 0.88, 0.88, '2026-07-07 06:10:39'),
(19, 9.5, 0.85, 0.85, '2026-07-07 06:10:39'),
(20, 10.0, 0.83, 0.83, '2026-07-07 06:10:39'),
(21, 10.5, 0.81, 0.81, '2026-07-07 06:10:39'),
(22, 11.0, 0.79, 0.79, '2026-07-07 06:10:39'),
(23, 11.5, 0.77, 0.77, '2026-07-07 06:10:39'),
(24, 12.0, 0.75, 0.75, '2026-07-07 06:10:39'),
(25, 12.5, 0.73, 0.73, '2026-07-07 06:10:39'),
(26, 13.0, 0.71, 0.71, '2026-07-07 06:10:39'),
(27, 13.5, 0.69, 0.69, '2026-07-07 06:10:39'),
(28, 14.0, 0.67, 0.67, '2026-07-07 06:10:39'),
(29, 14.5, 0.65, 0.65, '2026-07-07 06:10:39'),
(30, 15.0, 0.63, 0.63, '2026-07-07 06:10:39'),
(31, 15.5, 0.60, 0.60, '2026-07-07 06:10:39'),
(32, 16.0, 0.58, 0.58, '2026-07-07 06:10:39'),
(33, 16.5, 0.56, 0.56, '2026-07-07 06:10:39'),
(34, 17.0, 0.54, 0.54, '2026-07-07 06:10:39'),
(35, 17.5, 0.52, 0.52, '2026-07-07 06:10:39'),
(36, 18.0, 0.50, 0.50, '2026-07-07 06:10:39'),
(37, 18.5, 0.48, 0.48, '2026-07-07 06:10:39'),
(38, 19.0, 0.46, 0.46, '2026-07-07 06:10:39'),
(39, 19.5, 0.44, 0.44, '2026-07-07 06:10:39'),
(40, 20.0, 0.42, 0.42, '2026-07-07 06:10:39');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `leave_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `number_of_days` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','denied') DEFAULT 'pending',
  `staff_note` text DEFAULT NULL,
  `denial_reason` text DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`leave_id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `number_of_days`, `reason`, `status`, `staff_note`, `denial_reason`, `signature_path`, `approved_by`, `approved_date`, `created_at`, `updated_at`) VALUES
(1, 9, 3, '2026-07-31', '2026-07-31', 1, 'b', 'denied', '', 'No supporting documentation provided', NULL, 1, '2026-10-02 20:45:53', '2026-07-31 02:21:30', '2026-10-02 12:45:53'),
(2, 3, 3, '2026-10-02', '2026-10-05', 4, 'aray ko', 'pending', NULL, NULL, 'C:\\xampp\\htdocs\\hrgetafee-simple\\employee/../uploads/leave_signatures/3/1790920189_logo.jpg', NULL, NULL, '2026-10-02 05:49:49', '2026-10-02 05:49:49'),
(3, 3, 1, '2026-10-17', '2026-10-18', 2, 'ksjdshu', 'denied', '', 'this was just a sample', 'C:\\xampp\\htdocs\\hrgetafee-simple\\employee/../uploads/leave_signatures/3/signature_3_1790923806.png', 1, '2026-10-02 16:26:25', '2026-10-02 06:50:06', '2026-10-02 08:26:25'),
(4, 3, 7, '2026-10-22', '2026-10-23', 2, '', 'approved', '', '', 'C:\\xampp\\htdocs\\hrgetafee-simple\\employee/../uploads/leave_supporting_documents/3/1792032687_Data_Management_and_preparation_1_.pdf', 1, '2026-10-15 10:52:55', '2026-10-15 02:51:27', '2026-10-15 02:52:55');

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `leave_type_id` int(11) NOT NULL,
  `leave_type_name` varchar(50) NOT NULL,
  `max_days_per_year` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`leave_type_id`, `leave_type_name`, `max_days_per_year`, `description`, `created_at`, `is_deleted`, `deleted_at`, `deleted_by`) VALUES
(1, 'Vacation Leave', 15, 'Annual vacation leave', '2026-07-07 04:29:43', 0, NULL, NULL),
(2, 'Sick Leave', 10, 'Medical leave', '2026-07-07 04:29:43', 0, NULL, NULL),
(3, 'Emergency Leave', 5, 'Emergency situations', '2026-07-07 04:29:43', 0, NULL, NULL),
(4, 'Study Leave', 3, 'Educational purposes', '2026-07-07 04:29:43', 0, NULL, NULL),
(7, 'Personal Leave', 5, 'Personal matters', '2026-07-07 07:08:38', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `leave_type_categories`
--

CREATE TABLE `leave_type_categories` (
  `category_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `is_paid` tinyint(1) DEFAULT 1,
  `with_pay_percentage` decimal(5,2) DEFAULT 100.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_type_categories`
--

INSERT INTO `leave_type_categories` (`category_id`, `leave_type_id`, `category_name`, `is_paid`, `with_pay_percentage`, `created_at`) VALUES
(1, 1, 'Vacation Leave With Pay', 1, 100.00, '2026-07-07 06:10:39'),
(2, 2, 'Sick Leave', 1, 100.00, '2026-07-07 06:10:39'),
(3, 3, 'Emergency Leave', 1, 100.00, '2026-07-07 06:10:39'),
(4, 4, 'Study Leave', 1, 100.00, '2026-07-07 06:10:39');

-- --------------------------------------------------------

--
-- Table structure for table `payment_records`
--

CREATE TABLE `payment_records` (
  `payment_id` int(11) NOT NULL,
  `payroll_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_method` enum('cash','bank_transfer','check','other') DEFAULT 'cash',
  `payment_reference` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `check_number` varchar(50) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `payment_notes` text DEFAULT NULL,
  `paid_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `payroll_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payroll_month` int(11) DEFAULT NULL,
  `payroll_year` int(11) DEFAULT NULL,
  `gross_salary` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `net_salary` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','processed','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_details`
--

CREATE TABLE `payroll_details` (
  `payroll_detail_id` int(11) NOT NULL,
  `payroll_id` int(11) NOT NULL,
  `detail_type` enum('earning','deduction') NOT NULL,
  `detail_name` varchar(100) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recycle_bin`
--

CREATE TABLE `recycle_bin` (
  `bin_id` int(11) NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `deleted_by` int(11) NOT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_data` longtext DEFAULT NULL COMMENT 'JSON snapshot of deleted record',
  `restored` tinyint(1) DEFAULT 0,
  `restored_by` int(11) DEFAULT NULL,
  `restored_at` timestamp NULL DEFAULT NULL,
  `permanent_delete` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recycle_bin`
--

INSERT INTO `recycle_bin` (`bin_id`, `item_type`, `item_id`, `item_name`, `deleted_by`, `deleted_at`, `deleted_data`, `restored`, `restored_by`, `restored_at`, `permanent_delete`) VALUES
(1, 'permission', 50, 'view_profile', 1, '2026-07-12 02:21:15', '{\"permission_id\":50,\"permission_name\":\"view_profile\",\"role_id\":3}', 1, 1, '2026-07-12 02:21:23', 0),
(2, 'permission', 53, 'apply_leave', 1, '2026-07-12 02:21:34', '{\"permission_id\":53,\"permission_name\":\"apply_leave\",\"role_id\":3}', 1, 1, '2026-07-12 02:21:45', 0),
(3, 'department', 2, 'Finance', 1, '2026-07-12 02:21:57', '{\"dept_id\":2,\"dept_name\":\"Finance\",\"dept_head\":\"FIN\",\"budget\":\"0.00\"}', 1, 1, '2026-07-12 02:22:02', 0);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `created_at`, `is_deleted`, `deleted_at`, `deleted_by`) VALUES
(1, 'HR Administrator', 'Full system control and configuration', '2026-07-07 04:29:42', 0, NULL, NULL),
(3, 'Employee', 'Clock in/out and leave application', '2026-07-07 04:29:42', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `permission_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`permission_id`, `role_id`, `permission_name`, `description`, `created_at`, `is_deleted`, `deleted_at`, `deleted_by`) VALUES
(24, 1, 'manage_users', 'Create, edit, delete user accounts', '2026-07-11 13:20:42', 0, NULL, NULL),
(25, 1, 'manage_roles', 'Manage roles and permissions', '2026-07-11 13:20:42', 0, NULL, NULL),
(26, 1, 'manage_departments', 'Manage departments', '2026-07-11 13:20:42', 0, NULL, NULL),
(27, 1, 'manage_holidays', 'Manage company holidays', '2026-07-11 13:20:43', 0, NULL, NULL),
(28, 1, 'manage_employees', 'Create and edit employee records', '2026-07-11 13:20:43', 0, NULL, NULL),
(29, 1, 'generate_employee_report', 'Generate employee report', '2026-07-11 13:20:43', 0, NULL, NULL),
(30, 1, 'generate_attendance_report', 'Generate attendance report', '2026-07-11 13:20:43', 0, NULL, NULL),
(31, 1, 'generate_leave_report', 'Generate leave report', '2026-07-11 13:20:43', 0, NULL, NULL),
(32, 1, 'generate_payroll_report', 'Generate payroll report', '2026-07-11 13:20:43', 0, NULL, NULL),
(33, 1, 'backup_restore', 'Backup and restore system data', '2026-07-11 13:20:43', 0, NULL, NULL),
(50, 3, 'view_profile', 'View own profile', '2026-07-11 13:20:43', 0, NULL, NULL),
(51, 3, 'clock_in_out', 'Clock in and out', '2026-07-11 13:20:43', 0, NULL, NULL),
(52, 3, 'view_attendance_summary', 'View own attendance summary', '2026-07-11 13:20:43', 0, NULL, NULL),
(53, 3, 'apply_leave', 'Apply for leave', '2026-07-11 13:20:43', 0, NULL, NULL),
(54, 3, 'view_leave_status', 'View own leave status', '2026-07-11 13:20:43', 0, NULL, NULL),
(55, 3, 'view_payslip_employee', 'View own payslip', '2026-07-11 13:20:43', 0, NULL, NULL),
(56, 3, 'view_announcements', 'View announcements', '2026-07-11 13:20:43', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_logs`
--

INSERT INTO `security_logs` (`log_id`, `user_id`, `action`, `description`, `ip_address`, `timestamp`) VALUES
(1, 1, 'DEACTIVATE_USER', 'Deactivated user_id=3', '::1', '2026-07-11 20:22:01'),
(2, 1, 'REACTIVATE_USER', 'Reactivated user_id=3', '::1', '2026-07-11 20:22:18'),
(3, 1, 'ADD_EMPLOYEE', 'Added employee: GETAFE-2026-006', '::1', '2026-07-11 20:54:00'),
(4, 1, 'CREATE_USER', 'Created user \'janah\' for employee_id=6', '::1', '2026-07-11 20:55:09'),
(5, 1, 'DEACTIVATE_USER', 'Deactivated user_id=6', '::1', '2026-07-11 20:55:33'),
(6, 1, 'UPDATE_DEPARTMENT', 'Updated department_id: 2', '::1', '2026-07-11 21:35:56'),
(7, 1, 'DELETE_PERMISSION', 'Moved to bin: \'view_profile\' from role_id 3', '::1', '2026-07-12 10:21:15'),
(8, 1, 'RESTORE_FROM_BIN', 'Restored permission: view_profile', '::1', '2026-07-12 10:21:23'),
(9, 1, 'DELETE_PERMISSION', 'Moved to bin: \'apply_leave\' from role_id 3', '::1', '2026-07-12 10:21:34'),
(10, 1, 'RESTORE_FROM_BIN', 'Restored permission: apply_leave', '::1', '2026-07-12 10:21:45'),
(11, 1, 'RESTORE_FROM_BIN', 'Restored department: Finance', '::1', '2026-07-12 10:22:02'),
(12, 1, 'ADD_EMPLOYEE', 'Added employee: GETAFE-2026-007', '::1', '2026-07-14 13:01:42'),
(13, 1, 'CREATE_USER', 'Created user \'gxsgxhxbs\' for employee_id=7', '::1', '2026-07-14 13:03:14'),
(14, 1, 'DEACTIVATE_USER', 'Deactivated user_id=7', '::1', '2026-07-14 13:03:33'),
(15, 1, 'REACTIVATE_USER', 'Reactivated user_id=7', '::1', '2026-07-14 13:03:38'),
(16, 1, 'ADD_EMPLOYEE', 'Added employee: GETAFE-2026-008', '::1', '2026-07-31 09:40:08'),
(17, 1, 'ADD_EMPLOYEE', 'Added employee: GETAFE-2026-009', '::1', '2026-07-31 09:58:10'),
(18, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 9', '::1', '2026-07-31 09:59:27'),
(19, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 9', '::1', '2026-07-31 10:00:01'),
(20, 1, 'CREATE_USER', 'Created user \'grecia123\' for employee_id=9', '::1', '2026-07-31 10:11:53'),
(21, 8, 'APPLY_LEAVE', 'Applied for leave from 2026-07-31 to 2026-07-31', '::1', '2026-07-31 10:21:30'),
(22, 3, 'APPLY_LEAVE', 'Applied for leave from 2026-10-02 to 2026-10-05', '::1', '2026-10-02 13:49:49'),
(23, 3, 'APPLY_LEAVE', 'Applied for leave from 2026-10-17 to 2026-10-18', '::1', '2026-10-02 14:50:06'),
(24, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 2', '::1', '2026-10-02 14:58:50'),
(25, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 4', '::1', '2026-10-02 14:59:47'),
(26, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 5', '::1', '2026-10-02 15:00:13'),
(27, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 6', '::1', '2026-10-02 15:00:51'),
(28, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 7', '::1', '2026-10-02 15:01:13'),
(29, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 8', '::1', '2026-10-02 15:01:34'),
(30, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 3', '::1', '2026-10-02 15:03:36'),
(31, 1, 'DEACTIVATE_USER', 'Deactivated user_id=7', '::1', '2026-10-02 16:07:27'),
(32, 1, 'DEACTIVATE_USER', 'Deactivated user_id=4', '::1', '2026-10-02 16:07:54'),
(33, 1, 'DEACTIVATE_USER', 'Deactivated user_id=5', '::1', '2026-10-02 16:08:04'),
(34, 1, 'DEACTIVATE_USER', 'Deactivated user_id=2', '::1', '2026-10-02 16:08:12'),
(35, 1, 'ADD_EMPLOYEE', 'Added employee: GETAFE-2026-010', '::1', '2026-10-02 16:11:24'),
(36, 1, 'CREATE_USER', 'Created user \'rosebreva\' for employee_id=11', '::1', '2026-10-02 16:12:14'),
(37, 1, 'DEACTIVATE_USER', 'Deactivated user_id=9', '::1', '2026-10-02 16:13:07'),
(38, 1, 'CREATE_USER', 'Created user \'brevarose82\' for employee_id=8', '::1', '2026-10-02 16:19:42'),
(39, 1, 'DEACTIVATE_USER', 'Deactivated user_id=10', '::1', '2026-10-02 16:20:02'),
(40, 1, 'REACTIVATE_USER', 'Reactivated user_id=9', '::1', '2026-10-02 16:20:17'),
(41, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 3', '::1', '2026-10-02 16:24:50'),
(42, 1, 'DENY_LEAVE', 'Leave ID: 3 - Action: deny', '::1', '2026-10-02 16:26:25'),
(43, 1, 'ADD_EMPLOYEE', 'Added employee: GETAFE-2026-011', '::1', '2026-10-02 16:29:15'),
(44, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 3', '::1', '2026-10-02 17:10:14'),
(45, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 9', '::1', '2026-10-02 19:38:10'),
(46, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 1', '::1', '2026-10-02 19:40:48'),
(47, 1, 'DENY_LEAVE', 'Leave ID: 1 - Action: deny', '::1', '2026-10-02 20:45:53'),
(48, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 3', '::1', '2026-10-02 20:56:30'),
(49, 1, 'EDIT_EMPLOYEE', 'Updated employee_id: 3', '::1', '2026-10-02 20:56:36'),
(50, 3, 'APPLY_LEAVE', 'Applied for leave from 2026-10-22 to 2026-10-23', '::1', '2026-10-15 10:51:27'),
(51, 1, 'APPROVE_LEAVE', 'Leave ID: 4 - Action: approve', '::1', '2026-10-15 10:52:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `deactivation_reason` varchar(255) DEFAULT NULL,
  `deactivated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role_id`, `employee_id`, `status`, `deactivation_reason`, `deactivated_at`, `created_at`, `last_login`) VALUES
(1, 'admin', '$2y$12$4ZZ2tuvDOJv4s1hzJYtHn.z5.fmlMNQVNi1oiNwsFTBdTQFTV8Mqa', 1, 1, 'active', NULL, NULL, '2026-07-07 04:29:42', '2026-10-28 19:23:37'),
(2, 'maria', '$2y$12$z7/C3d0LOc4gn7IXdY1dnO1jXh/r/Ti2kfccRs4EKVPkj7gYOi1Eq', 3, 2, 'inactive', 'this was just a sample', '2026-10-02 16:08:12', '2026-07-07 04:29:42', '2026-10-02 12:49:46'),
(3, 'john', '$2y$12$4QsekufabIgX.ftJJSTWseUhafMAW/K0Ck1X779x8hi.g8eTwha96', 3, 3, 'active', NULL, NULL, '2026-07-07 04:29:42', '2026-10-19 15:34:41'),
(4, 'rosa', '$2y$12$46GPhvcOBznN0C2VhZqUGOfH.XNz86RF/Aj03p05QQbjiJYw2tUZO', 3, 4, 'inactive', 'this was just a sample', '2026-10-02 16:07:54', '2026-07-07 04:29:42', '2026-07-07 20:33:59'),
(5, 'pedro', '$2y$12$ozzMZ13PrHFWh.lcwIbhxOeHHbMmgxrubGdzvIh0NAAEv/QkkEZg6', 3, 5, 'inactive', 'this was just a sample', '2026-10-02 16:08:04', '2026-07-07 04:29:42', '2026-07-07 14:53:03'),
(6, 'janah', '$2y$12$4rvA.4BPpglKIWKq95NEnO85mTZ0jd52sdvQyjlWJcCgX.3gmWEay', 3, 6, 'inactive', 'this was just a sample', '2026-07-11 20:55:33', '2026-07-11 12:55:09', NULL),
(7, 'gxsgxhxbs', '$2y$12$Fl/09/ZMqFArEbPo64GEvO4phDwOTPUF.olP5WpNAYVh17nna5RSe', 3, 7, 'inactive', 'this was just a sample', '2026-10-02 16:07:27', '2026-07-14 05:03:14', NULL),
(8, 'grecia123', '$2y$12$6veAKMXtsYbXji3C/D/uj.NMzTL3DlZ1meYlP6B5hlzgk7J34Orsy', 3, 9, 'active', NULL, NULL, '2026-07-31 02:11:53', '2026-10-01 11:41:47'),
(9, 'rosebreva', '$2y$12$wnPnkqE2aAiz/4jo1YlB5.jTnzJ6hyrOfJnLXrM.cByf45/tGY4CO', 3, 11, 'active', NULL, NULL, '2026-10-02 08:12:14', '2026-10-02 17:50:28'),
(10, 'brevarose82', '$2y$12$RnuTMM/iwTGBb.72dZM4neJEO9t01L0OVdcaxvgbs4CTqFyWlTb/.', 3, 8, 'inactive', 'this was just a sample', '2026-10-02 16:20:02', '2026-10-02 08:19:42', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `idx_employee_date` (`employee_id`,`clock_in`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `backup_history`
--
ALTER TABLE `backup_history`
  ADD PRIMARY KEY (`backup_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `biometric_import_logs`
--
ALTER TABLE `biometric_import_logs`
  ADD PRIMARY KEY (`import_id`),
  ADD KEY `imported_by` (`imported_by`);

--
-- Indexes for table `deduction_types`
--
ALTER TABLE `deduction_types`
  ADD PRIMARY KEY (`deduction_id`),
  ADD UNIQUE KEY `deduction_code` (`deduction_code`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`),
  ADD UNIQUE KEY `unique_dept_name` (`dept_name`),
  ADD KEY `idx_departments_deleted` (`is_deleted`,`deleted_at`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  ADD PRIMARY KEY (`emp_deduction_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `deduction_id` (`deduction_id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_employee_documents_employee` (`employee_id`),
  ADD KEY `idx_employee_documents_type` (`document_type`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`holiday_id`),
  ADD KEY `idx_holidays_deleted` (`is_deleted`,`deleted_at`);

--
-- Indexes for table `hours_fraction_conversion`
--
ALTER TABLE `hours_fraction_conversion`
  ADD PRIMARY KEY (`conversion_id`),
  ADD UNIQUE KEY `hours` (`hours`,`minutes`);

--
-- Indexes for table `leave_compensation`
--
ALTER TABLE `leave_compensation`
  ADD PRIMARY KEY (`compensation_id`),
  ADD UNIQUE KEY `ams_value` (`ams_value`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `leave_type_id` (`leave_type_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`leave_type_id`),
  ADD KEY `idx_leave_types_deleted` (`is_deleted`,`deleted_at`);

--
-- Indexes for table `leave_type_categories`
--
ALTER TABLE `leave_type_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `leave_type_id` (`leave_type_id`);

--
-- Indexes for table `payment_records`
--
ALTER TABLE `payment_records`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `payroll_id` (`payroll_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `paid_by` (`paid_by`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `payroll_details`
--
ALTER TABLE `payroll_details`
  ADD PRIMARY KEY (`payroll_detail_id`),
  ADD KEY `payroll_id` (`payroll_id`);

--
-- Indexes for table `recycle_bin`
--
ALTER TABLE `recycle_bin`
  ADD PRIMARY KEY (`bin_id`),
  ADD KEY `deleted_by` (`deleted_by`),
  ADD KEY `restored_by` (`restored_by`),
  ADD KEY `idx_item_type` (`item_type`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`),
  ADD KEY `idx_restored` (`restored`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`),
  ADD KEY `idx_roles_deleted` (`is_deleted`,`deleted_at`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_name`),
  ADD KEY `idx_permissions_deleted` (`is_deleted`,`deleted_at`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `backup_history`
--
ALTER TABLE `backup_history`
  MODIFY `backup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `biometric_import_logs`
--
ALTER TABLE `biometric_import_logs`
  MODIFY `import_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deduction_types`
--
ALTER TABLE `deduction_types`
  MODIFY `deduction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  MODIFY `emp_deduction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `holiday_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hours_fraction_conversion`
--
ALTER TABLE `hours_fraction_conversion`
  MODIFY `conversion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `leave_compensation`
--
ALTER TABLE `leave_compensation`
  MODIFY `compensation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `leave_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `leave_type_categories`
--
ALTER TABLE `leave_type_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_records`
--
ALTER TABLE `payment_records`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payroll_details`
--
ALTER TABLE `payroll_details`
  MODIFY `payroll_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `recycle_bin`
--
ALTER TABLE `recycle_bin`
  MODIFY `bin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `backup_history`
--
ALTER TABLE `backup_history`
  ADD CONSTRAINT `backup_history_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `biometric_import_logs`
--
ALTER TABLE `biometric_import_logs`
  ADD CONSTRAINT `biometric_import_logs_ibfk_1` FOREIGN KEY (`imported_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  ADD CONSTRAINT `employee_deductions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `employee_deductions_ibfk_2` FOREIGN KEY (`deduction_id`) REFERENCES `deduction_types` (`deduction_id`);

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `employee_documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`leave_type_id`),
  ADD CONSTRAINT `leave_requests_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `leave_type_categories`
--
ALTER TABLE `leave_type_categories`
  ADD CONSTRAINT `leave_type_categories_ibfk_1` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`leave_type_id`);

--
-- Constraints for table `payment_records`
--
ALTER TABLE `payment_records`
  ADD CONSTRAINT `payment_records_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`payroll_id`),
  ADD CONSTRAINT `payment_records_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `payment_records_ibfk_3` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `payroll_details`
--
ALTER TABLE `payroll_details`
  ADD CONSTRAINT `payroll_details_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`payroll_id`);

--
-- Constraints for table `recycle_bin`
--
ALTER TABLE `recycle_bin`
  ADD CONSTRAINT `recycle_bin_ibfk_1` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `recycle_bin_ibfk_2` FOREIGN KEY (`restored_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
