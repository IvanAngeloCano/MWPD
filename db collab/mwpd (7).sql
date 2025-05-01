-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2025 at 02:57 PM
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
-- Database: `mwpd`
--

-- --------------------------------------------------------

--
-- Table structure for table `bm`
--

CREATE TABLE `bm` (
  `bmid` int(11) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `given_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `sex` char(1) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `eval_counter_no` varchar(10) DEFAULT NULL,
  `eval_type` varchar(50) DEFAULT NULL,
  `eval_time_in` time DEFAULT NULL,
  `eval_time_out` time DEFAULT NULL,
  `eval_total_pct` decimal(5,2) DEFAULT NULL,
  `pay_counter_no` varchar(10) DEFAULT NULL,
  `pay_time_in` time DEFAULT NULL,
  `pay_time_out` time DEFAULT NULL,
  `pay_total_pct` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT 'Pending',
  `position` varchar(255) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `nameofthenewprincipal` varchar(255) DEFAULT NULL,
  `employmentduration` varchar(255) DEFAULT NULL,
  `datearrival` date DEFAULT NULL,
  `datedeparture` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bm`
--

INSERT INTO `bm` (`bmid`, `last_name`, `given_name`, `middle_name`, `sex`, `address`, `destination`, `eval_counter_no`, `eval_type`, `eval_time_in`, `eval_time_out`, `eval_total_pct`, `pay_counter_no`, `pay_time_in`, `pay_time_out`, `pay_total_pct`, `remarks`, `position`, `salary`, `nameofthenewprincipal`, `employmentduration`, `datearrival`, `datedeparture`) VALUES
(1, 'Macatuno', 'Allana', 'Carmona', 'F', 'Calamba, Laguna', 'Tokyo, Japan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', 'Crew', 1000.00, 'Jupiter', '2026', '2025-04-30', '2025-05-07');

-- --------------------------------------------------------

--
-- Table structure for table `bm_sp_files`
--

CREATE TABLE `bm_sp_files` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bmid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bm_sp_files`
--

INSERT INTO `bm_sp_files` (`id`, `filename`, `filepath`, `created_at`, `bmid`) VALUES
(1, 'SEAFERER_Macatuno_20250430_220236.docx', 'C:\\Xampp\\htdocs\\MWPD/generated_files/SEAFERER_Macatuno_20250430_220236.docx', '2025-04-30 12:00:05', 1);

-- --------------------------------------------------------

--
-- Table structure for table `clearance_approvals`
--

CREATE TABLE `clearance_approvals` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `record_type` varchar(50) NOT NULL,
  `status` enum('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `direct_hire`
--

CREATE TABLE `direct_hire` (
  `id` int(11) NOT NULL,
  `control_no` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `jobsite` varchar(255) NOT NULL,
  `evaluated` date DEFAULT NULL,
  `for_confirmation` date DEFAULT NULL,
  `emailed_to_dhad` date DEFAULT NULL,
  `received_from_dhad` date DEFAULT NULL,
  `evaluator` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `type` enum('professional','household') DEFAULT 'professional',
  `status` enum('pending','approved','denied') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `submitted_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `direct_hire`
--

INSERT INTO `direct_hire` (`id`, `control_no`, `name`, `jobsite`, `evaluated`, `for_confirmation`, `emailed_to_dhad`, `received_from_dhad`, `evaluator`, `note`, `type`, `status`, `created_at`, `updated_at`, `submitted_by`, `approved_by`, `approved_at`) VALUES
(1, 'DH-001', 'Justinesb', 'Allianz', '2025-08-09', '2025-04-04', '2025-09-09', '2025-09-09', 'Ivan Cano', '', 'professional', 'approved', '2025-04-26 06:45:51', '2025-04-26 15:34:33', NULL, 1, '2025-04-26 23:34:33'),
(2, 'DH-0040', 'tester justinte', 'Allienz', '2024-04-22', '2025-02-23', '2025-02-23', '2025-02-20', 'justine ', '', 'household', 'approved', '2025-04-26 07:20:54', '2025-04-26 07:45:48', NULL, 1, '2025-04-26 15:45:48'),
(3, 'DH-009', 'dsadas', 'dsadasd', '2020-08-09', '2020-02-22', '2020-02-20', '2020-02-20', 'dsda', '', 'household', 'approved', '2025-04-26 12:06:49', '2025-04-28 08:06:31', NULL, 1, '2025-04-28 16:06:31'),
(4, 'DH-0013ds', 'dsads', 'dsadsad', '2020-09-09', '2020-09-09', '2020-09-09', '2002-09-09', 'sadsa', '', 'household', 'denied', '2025-04-26 13:15:27', '2025-04-30 05:26:51', NULL, 1, '2025-04-30 13:26:51'),
(5, 'sdasd', 'dsadsa', 'dsdasn', '2020-09-09', '2020-02-02', '2020-02-20', '2020-02-20', 'ds', '', 'professional', 'approved', '2025-04-26 15:05:47', '2025-04-30 03:54:07', NULL, 1, '2025-04-30 11:54:07'),
(6, 'DH-00401', 'jamiwjap', 'joblist', '2025-04-09', '2025-04-14', '2025-04-17', '2025-04-18', 'Ivan Cano', '[APPROVAL NOTE - April 29, 2025 by Regional Director]\r\nRecord approved without additional comments.\n\n[APPROVAL NOTE - April 29, 2025 by Regional Director]\nRecord approved without additional comments.', 'professional', 'approved', '2025-04-28 08:09:33', '2025-04-29 09:57:27', NULL, 1, '2025-04-29 17:57:27'),
(7, 'DH-0099', 'dsadsal', 'gjhfjgdgghshgs', '2025-04-10', '2025-04-10', '2025-04-20', '2025-04-08', 'ghdytd', '[APPROVAL NOTE - April 29, 2025 by Regional Director]\r\nRecord approved without additional comments.\r\n\r\n[APPROVAL NOTE - April 29, 2025 by Regional Director]\r\nRecord approved without additional comments.', 'professional', 'approved', '2025-04-28 08:10:09', '2025-04-30 05:15:48', NULL, 1, '2025-04-30 13:15:48'),
(8, 'DH-004088', 'JADEERD', 'GFSGF', NULL, NULL, NULL, NULL, '', '[APPROVAL NOTE - April 29, 2025 by Regional Director]\r\nRecord approved without additional comments.\r\n\r\n[APPROVAL NOTE - April 29, 2025 by Regional Director]\r\njdsad', 'professional', 'approved', '2025-04-28 08:11:18', '2025-04-30 05:19:10', NULL, 1, '2025-04-30 13:19:10'),
(9, 'DH-001090', 'JOLY', 'PORK', NULL, NULL, NULL, NULL, '', '[DENIAL NOTE - April 29, 2025 by Regional Director]\r\ndsadsdas\r\n\r\n[DENIAL NOTE - April 29, 2025 by Regional Director]\r\ndsadsa\r\n\r\n[DENIAL NOTE - April 29, 2025 by Regional Director]\r\ndsadsa\r\n\r\n[DENIAL NOTE - April 29, 2025 by Regional Director]\r\ndsadsa\r\n\r\n[DENIAL NOTE - April 29, 2025 by Regional Director]\r\ndsadsa\r\n\r\n[APPROVAL NOTE - April 29, 2025 by Regional Director]\r\nRecord approved without additional comments.[APPROVAL NOTE - 2025-04-30 05:32:50 by Regional Director]\r\nApproved without additional comments.\r\n\r\n', 'professional', 'approved', '2025-04-28 08:11:42', '2025-05-01 07:03:39', NULL, 1, '2025-05-01 15:03:39'),
(10, 'DH-001965', 'KALIX', 'KOLIV', NULL, NULL, NULL, NULL, '', '[DENIAL NOTE - April 29, 2025 by Regional Director]\r\ncdcsa\r\n\r\n[APPROVAL NOTE - April 29, 2025 by Regional Director]\r\nRecord approved without additional comments.\r\n\r\n[APPROVAL NOTE - April 29, 2025 by Regional Director]\r\nRecord approved without additional comments.\r\n\r\n[APPROVAL NOTE - April 29, 2025 by Regional Director]\r\nRecord approved without additional comments.[DENIAL NOTE - 2025-04-29 17:01:14 by Regional Director]\r\ndf\r\n\r\n[DENIAL NOTE - 2025-04-29 17:02:28 by Regional Director]\r\nhhh\r\n\r\n[DENIAL NOTE - 2025-04-29 17:02:44 by Regional Director]\r\nhhh\r\n\r\n[APPROVAL NOTE - 2025-04-30 05:26:54 by Regional Director]\r\nApproved without additional comments.\r\n\r\n[APPROVAL NOTE - 2025-04-30 05:29:42 by Regional Director]\r\nApproved without additional comments.\r\n\r\n', 'professional', 'pending', '2025-04-28 08:12:20', '2025-05-01 07:11:50', NULL, 1, '2025-04-30 13:25:15');

-- --------------------------------------------------------

--
-- Table structure for table `direct_hire_approvals`
--

CREATE TABLE `direct_hire_approvals` (
  `id` int(11) NOT NULL,
  `direct_hire_id` int(11) NOT NULL,
  `status` enum('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `submitted_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `direct_hire_clearance_approvals`
--

CREATE TABLE `direct_hire_clearance_approvals` (
  `id` int(11) NOT NULL,
  `direct_hire_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `record_type` enum('direct_hire','clearance','gov_to_gov','balik_manggagawa') NOT NULL DEFAULT 'clearance',
  `status` enum('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `submitted_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `direct_hire_clearance_approvals`
--

INSERT INTO `direct_hire_clearance_approvals` (`id`, `direct_hire_id`, `document_id`, `record_type`, `status`, `submitted_by`, `approved_by`, `comments`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'clearance', 'approved', 1, 1, 'okay', '2025-04-26 06:45:58', '2025-04-26 07:18:15'),
(2, 2, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 07:45:41', '2025-04-26 07:45:48'),
(3, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 11:52:09', '2025-04-26 11:52:17'),
(4, 3, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 12:06:52', '2025-04-26 12:06:58'),
(5, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 12:20:50', '2025-04-26 12:30:06'),
(6, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 12:32:58', '2025-04-26 12:54:14'),
(7, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 13:06:28', '2025-04-26 13:06:34'),
(8, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 13:10:53', '2025-04-26 13:10:59'),
(9, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 13:14:30', '2025-04-26 13:14:46'),
(10, 4, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 13:15:33', '2025-04-26 13:15:39'),
(11, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 13:20:03', '2025-04-26 13:20:08'),
(12, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 13:25:52', '2025-04-26 13:26:00'),
(13, 1, NULL, 'clearance', 'approved', 2, 1, '', '2025-04-26 13:27:19', '2025-04-26 13:27:38'),
(14, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 13:56:08', '2025-04-26 13:56:14'),
(15, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:01:05', '2025-04-26 14:01:26'),
(16, 4, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:02:04', '2025-04-26 14:12:52'),
(17, 3, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:02:18', '2025-04-26 14:12:22'),
(18, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:13:07', '2025-04-26 14:17:03'),
(19, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:14:21', '2025-04-26 14:14:27'),
(20, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:25:10', '2025-04-26 14:25:37'),
(21, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:25:54', '2025-04-26 14:26:05'),
(22, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:26:34', '2025-04-26 14:26:41'),
(23, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:30:35', '2025-04-26 14:30:40'),
(24, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:42:57', '2025-04-26 14:43:03'),
(25, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:44:12', '2025-04-26 14:44:16'),
(26, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:52:05', '2025-04-26 14:52:16'),
(27, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:52:29', '2025-04-26 14:59:20'),
(28, 4, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 14:55:57', '2025-04-26 14:56:04'),
(29, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:00:55', '2025-04-26 15:01:00'),
(30, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:04:10', '2025-04-26 15:04:20'),
(31, 5, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:05:51', '2025-04-26 15:05:58'),
(32, 5, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:24:47', '2025-04-26 15:24:53'),
(33, 5, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:25:49', '2025-04-26 15:25:54'),
(34, 5, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:30:49', '2025-04-26 15:30:54'),
(35, 5, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:33:24', '2025-04-26 15:33:37'),
(36, 1, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:34:23', '2025-04-26 15:34:33'),
(37, 5, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:36:05', '2025-04-26 15:36:10'),
(38, 5, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:37:44', '2025-04-26 15:37:52'),
(39, 5, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:39:15', '2025-04-26 15:39:19'),
(40, 5, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:42:22', '2025-04-26 15:42:25'),
(41, 5, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-26 15:44:36', '2025-04-26 15:44:39'),
(42, 5, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-28 02:28:59', '2025-04-28 02:29:03'),
(43, 5, NULL, 'clearance', 'denied', 1, 1, 'kualng', '2025-04-28 02:31:40', '2025-04-28 02:34:23'),
(44, 5, NULL, 'clearance', 'denied', 1, 1, 'falpak', '2025-04-28 07:14:51', '2025-04-29 02:55:58'),
(45, 3, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-28 07:26:20', '2025-04-28 08:06:31'),
(46, 4, NULL, 'clearance', 'denied', 1, 1, 'ju', '2025-04-29 02:48:40', '2025-04-29 02:48:47'),
(51, 10, NULL, 'clearance', 'denied', 1, 1, 'cdcsa', '2025-04-29 03:35:25', '2025-04-29 03:35:32'),
(52, 9, NULL, 'clearance', 'denied', 1, 1, 'dsadsdas', '2025-04-29 03:53:17', '2025-04-29 03:54:26'),
(53, 8, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-29 03:53:21', '2025-04-29 03:53:31'),
(54, 7, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-29 03:56:40', '2025-04-29 03:56:45'),
(55, 6, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-29 03:58:11', '2025-04-29 03:58:16'),
(58, 10, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-29 03:59:09', '2025-04-29 04:11:38'),
(59, 9, NULL, 'clearance', 'denied', 1, 1, 'dsadsa', '2025-04-29 03:59:13', '2025-04-29 04:10:34'),
(60, 8, NULL, 'clearance', 'approved', 1, 1, 'jdsad', '2025-04-29 03:59:24', '2025-04-29 04:09:38'),
(61, 7, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-29 03:59:34', '2025-04-29 03:59:42'),
(64, 10, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-29 04:25:53', '2025-04-29 04:47:38'),
(66, 9, NULL, 'clearance', 'approved', 2, 1, '', '2025-04-29 09:23:58', '2025-04-29 09:24:25'),
(67, 6, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-29 09:25:42', '2025-04-29 09:57:27'),
(68, 10, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-29 10:04:03', '2025-04-29 10:04:49'),
(73, 10, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-29 15:01:04', '2025-04-30 04:12:36'),
(74, 9, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-30 03:31:23', '2025-04-30 04:10:33'),
(75, 5, NULL, 'clearance', 'approved', 1, 1, '', '2025-04-30 03:34:44', '2025-04-30 03:54:07'),
(76, 10, NULL, 'clearance', 'approved', 1, 1, 'test', '2025-04-30 04:19:24', '2025-04-30 04:19:32'),
(77, 9, NULL, 'clearance', 'approved', 2, 1, '', '2025-04-30 04:20:29', '2025-04-30 04:20:53'),
(78, 9, NULL, 'clearance', 'approved', 2, 1, '', '2025-04-30 04:35:22', '2025-05-01 07:03:39'),
(79, 10, NULL, 'clearance', 'approved', 2, 1, '', '2025-04-30 04:58:58', '2025-04-30 05:00:47'),
(80, 4, NULL, 'clearance', 'denied', 2, 1, 'dsdsa', '2025-04-30 05:04:38', '2025-04-30 05:26:51'),
(81, 8, NULL, 'direct_hire', 'approved', 2, 1, '', '2025-04-30 05:07:32', '2025-04-30 05:19:10'),
(82, 7, NULL, 'direct_hire', 'approved', 1, 1, '', '2025-04-30 05:15:37', '2025-04-30 05:15:48'),
(83, 10, NULL, 'direct_hire', 'denied', 1, 1, 'fsafasdsds', '2025-04-30 05:16:37', '2025-04-30 05:16:51'),
(84, 10, NULL, 'direct_hire', 'denied', 1, 1, 'dsadaaaa', '2025-04-30 05:24:58', '2025-04-30 05:25:15'),
(85, 10, NULL, 'direct_hire', 'pending', 1, NULL, NULL, '2025-05-01 07:11:50', '2025-05-01 07:11:50');

-- --------------------------------------------------------

--
-- Table structure for table `direct_hire_documents`
--

CREATE TABLE `direct_hire_documents` (
  `id` int(11) NOT NULL,
  `direct_hire_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_approved` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `direct_hire_documents`
--

INSERT INTO `direct_hire_documents` (`id`, `direct_hire_id`, `filename`, `original_filename`, `file_type`, `file_size`, `uploaded_at`, `is_approved`) VALUES
(3, 2, 'DirectHireClearance_DH-0040_20250426_092054.docx', 'DirectHireClearance_DH-0040_20250426_092054.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 457354, '2025-04-26 07:20:54', 0),
(10, 2, 'Clearance_DH-0040_20250426_094554.pdf', 'Clearance - tester justinte - 2025-04-26.pdf', 'application/pdf', 567766, '2025-04-26 07:45:57', 1),
(11, 2, 'Clearance_DH-0040_20250426_100344.pdf', 'Clearance - tester justinte - 2025-04-26.pdf', 'application/pdf', 567766, '2025-04-26 08:03:49', 1),
(14, 3, 'DirectHireClearance_DH-009_20250426_140649.docx', 'DirectHireClearance_DH-009_20250426_140649.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 457330, '2025-04-26 12:06:49', 0),
(15, 1, 'Approved_Clearance_DH-001_20250426_143006.pdf', 'Approved Clearance - Justine.pdf', 'application/pdf', 567664, '2025-04-26 12:30:10', 1),
(16, 4, 'DirectHireClearance_DH-0013ds_20250426_151527.docx', 'DirectHireClearance_DH-0013ds_20250426_151527.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 457330, '2025-04-26 13:15:27', 0),
(17, 1, 'Approved_Clearance_DH-001_20250426_160118.pdf', 'Approved Clearance - Justine.pdf', 'application/pdf', 567664, '2025-04-26 14:01:25', 1),
(18, 1, 'Approved_Clearance_DH-001_20250426_160126.pdf', 'Approved Clearance - Justine.pdf', 'application/pdf', 567664, '2025-04-26 14:01:32', 1),
(19, 5, 'DirectHireClearance_sdasd_20250426_170547.docx', 'DirectHireClearance_sdasd_20250426_170547.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 457333, '2025-04-26 15:05:47', 0),
(20, 6, 'DirectHireClearance_DH-00401_20250428_100933.docx', 'DirectHireClearance_DH-00401_20250428_100933.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 457344, '2025-04-28 08:09:33', 0),
(21, 7, 'DirectHireClearance_DH-0099_20250428_101009.docx', 'DirectHireClearance_DH-0099_20250428_101009.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 457353, '2025-04-28 08:10:09', 0),
(22, 8, 'DirectHireClearance_DH-004088_20250428_101118.docx', 'DirectHireClearance_DH-004088_20250428_101118.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 457341, '2025-04-28 08:11:18', 0),
(23, 9, 'DirectHireClearance_DH-001090_20250428_101142.docx', 'DirectHireClearance_DH-001090_20250428_101142.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 457336, '2025-04-28 08:11:42', 0),
(24, 10, 'DirectHireClearance_DH-001965_20250428_101220.docx', 'DirectHireClearance_DH-001965_20250428_101220.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 457336, '2025-04-28 08:12:20', 0),
(28, 8, 'Approved_Clearance_DH-004088_20250429_055331.pdf', 'Approved Clearance - JADEERD.pdf', 'application/pdf', 561815, '2025-04-29 03:53:35', 1),
(29, 7, 'Approved_Clearance_DH-0099_20250429_055645.pdf', 'Approved Clearance - dsadsal.pdf', 'application/pdf', 561940, '2025-04-29 03:56:48', 1),
(30, 6, 'Approved_Clearance_DH-00401_20250429_055816.pdf', 'Approved Clearance - jamiwjap.pdf', 'application/pdf', 561893, '2025-04-29 03:58:20', 1),
(31, 7, 'Approved_Clearance_DH-0099_20250429_055942.pdf', 'Approved Clearance - dsadsal.pdf', 'application/pdf', 561940, '2025-04-29 03:59:49', 1),
(32, 8, 'Approved_Clearance_DH-004088_20250429_060938.pdf', 'Approved Clearance - JADEERD.pdf', 'application/pdf', 561815, '2025-04-29 04:09:42', 1),
(33, 10, 'Approved_Clearance_DH-001965_20250429_061138.pdf', 'Approved Clearance - KALIX.pdf', 'application/pdf', 562602, '2025-04-29 04:11:42', 1),
(35, 10, 'Approved_Clearance_DH-001965_20250429_064738.pdf', 'Approved Clearance - KALIX.pdf', 'application/pdf', 562602, '2025-04-29 04:47:43', 1),
(39, 9, 'Approved_Clearance_DH-001090_20250429_112425.pdf', 'Approved Clearance - JOLY.pdf', 'application/pdf', 562625, '2025-04-29 09:24:29', 1),
(40, 6, 'Approved_Clearance_DH-00401_20250429_115727.pdf', 'Approved Clearance - jamiwjap.pdf', 'application/pdf', 561893, '2025-04-29 09:57:31', 1),
(41, 10, 'Approved_Clearance_DH-001965_20250429_120449.pdf', 'Approved Clearance - KALIX.pdf', 'application/pdf', 562602, '2025-04-29 10:04:52', 1);

-- --------------------------------------------------------

--
-- Table structure for table `direct_hire_household`
--

CREATE TABLE `direct_hire_household` (
  `id` int(11) NOT NULL,
  `control_no` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `jobsite` varchar(255) DEFAULT NULL,
  `evaluated` date DEFAULT NULL,
  `for_confirmation` varchar(255) DEFAULT NULL,
  `emailed_to_dhad` date DEFAULT NULL,
  `received_from_dhad` date DEFAULT NULL,
  `evaluator` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `direct_hire_professional`
--

CREATE TABLE `direct_hire_professional` (
  `id` int(11) NOT NULL,
  `control_no` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `jobsite` varchar(255) DEFAULT NULL,
  `evaluated` date DEFAULT NULL,
  `for_confirmation` varchar(255) DEFAULT NULL,
  `emailed_to_dhad` date DEFAULT NULL,
  `received_from_dhad` date DEFAULT NULL,
  `evaluator` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `endorsed_gov_to_gov`
--

CREATE TABLE `endorsed_gov_to_gov` (
  `endorsed_id` int(11) NOT NULL,
  `g2g_id` int(11) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `age` int(3) DEFAULT NULL,
  `height` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `educational_attainment` varchar(255) DEFAULT NULL,
  `present_address` text DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `passport_validity` date DEFAULT NULL,
  `id_presented` varchar(255) DEFAULT NULL,
  `id_number` varchar(255) DEFAULT NULL,
  `with_job_experience` varchar(5) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `job_description` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `endorsement_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `memo_reference` varchar(255) DEFAULT NULL,
  `employer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gov_to_gov`
--

CREATE TABLE `gov_to_gov` (
  `g2g` int(11) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(10) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `educational_attainment` varchar(255) DEFAULT NULL,
  `present_address` varchar(255) DEFAULT NULL,
  `email_address` varchar(100) DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `passport_validity` date DEFAULT NULL,
  `id_presented` varchar(100) DEFAULT NULL,
  `id_number` varchar(100) DEFAULT NULL,
  `with_job_experience` varchar(10) DEFAULT NULL,
  `company_name_year_started_ended` varchar(255) DEFAULT NULL,
  `with_job_experience_aside_from` varchar(10) DEFAULT NULL,
  `name_company_year_started_ended` varchar(255) DEFAULT NULL,
  `remarks` enum('Pending','Good','Endorsed') NOT NULL DEFAULT 'Pending',
  `date_received_by_region` date DEFAULT NULL,
  `endorsement_date` timestamp NULL DEFAULT NULL,
  `employer` varchar(255) DEFAULT NULL,
  `memo_reference` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gov_to_gov`
--

INSERT INTO `gov_to_gov` (`g2g`, `last_name`, `first_name`, `middle_name`, `sex`, `birth_date`, `age`, `height`, `weight`, `educational_attainment`, `present_address`, `email_address`, `contact_number`, `passport_number`, `passport_validity`, `id_presented`, `id_number`, `with_job_experience`, `company_name_year_started_ended`, `with_job_experience_aside_from`, `name_company_year_started_ended`, `remarks`, `date_received_by_region`, `endorsement_date`, `employer`, `memo_reference`) VALUES
(1, 'Leclerc', 'Charles', 'K', 'Male', '2025-04-22', 0, 180, 56, 'Highschool Graduate', 'Calamba, Lguna', 'charlehadu@dfa.com', '3743817510', '72035', '2027-08-22', 'passport', '72035', 'No', 'n/a', 'No', 'n/a', 'Good', '2025-04-22', NULL, NULL, NULL),
(2, 'Sainz', 'Carlos', 'P', 'Male', '2025-04-22', 23, 456, 58, 'vbhjnkm.,h', 'cfhghkj', 'rfghkj@gmail.com', '3456789', '45678', '2000-09-17', 'vbnks', '45678', 'No', 'n/a', 'No', 'n/a', 'Good', '2025-04-22', NULL, NULL, NULL),
(3, 'Verstappen', 'Max', 'H', 'Male', '1997-06-26', 27, 182, 62, 'College', 'drcfghbjk', 'vgjabdkja@fnaj.com', '765678', '456789', '2027-08-09', 'hbkj877t8', 'bkhb', 'No', 'n/a', 'No', 'n/a', 'Pending', '2025-03-31', NULL, NULL, NULL),
(4, 'Macatuno', 'Allana', 'C', 'Female', '2000-09-17', 24, 150, 47, 'College', 'rdcvgbhkj', 'fvghj@vs.com', '4567', '57687', '2025-12-31', 'edfvgbhj', '456789', 'No', 'n/a', 'No', 'n/a', 'Endorsed', '2025-01-31', NULL, NULL, NULL),
(5, 'Carmona', 'Aya', 'C', 'Female', '2001-04-05', 23, 456, 51, 'dcfvghb', 'fgvbh', 'fgh@bgh.com', '456', '45678', '2025-12-31', 'dfg', '4567', 'No', '', 'No', '', 'Pending', '2025-12-31', NULL, NULL, NULL),
(6, 'rtghj', 'vghb', 'g', 'Female', '2025-04-23', 0, 100, 30, 'cgvbhn', 'bnm,', 'jbkj@gda.com', '5678', '567', '2000-06-05', '456', '46578', 'Yes', 'Canda', 'Yes', 'iran', 'Endorsed', '2025-12-31', '2025-05-01 06:05:39', 'dsa', 'dsa'),
(7, 'SYSTEM', 'OPTION', 'ENDORSED', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, NULL),
(8, 'SYSTEM', 'OPTION', 'ENDORSED', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_fairs`
--

CREATE TABLE `job_fairs` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `venue` varchar(255) NOT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` enum('planned','confirmed','completed','cancelled') NOT NULL DEFAULT 'planned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_fairs`
--

INSERT INTO `job_fairs` (`id`, `date`, `venue`, `contact_info`, `note`, `status`, `created_at`, `updated_at`) VALUES
(1, '2025-04-15', 'SM City Calamba', '0962 671 9976', 'Laguna Job Fair', 'completed', '2025-04-29 00:49:02', '2025-04-29 00:49:02'),
(2, '2025-04-30', 'System Presentation', '(049) 548 1375', 'LSPU-LB ', 'confirmed', '2025-04-29 00:49:02', '2025-04-29 01:28:13'),
(3, '2025-05-10', 'Ayala Mall Legazpi', 'dmw4a.processing@dmw.gov.ph', 'Bicol Region Job Fair', 'planned', '2025-04-29 00:49:02', '2025-04-29 00:49:02'),
(4, '2025-05-22', 'SM City Cebu', '0962 671 9976', 'Cebu Job Fair', 'planned', '2025-04-29 00:49:02', '2025-04-29 00:49:02'),
(5, '2025-06-05', 'SM Mall of Asia', '(049) 548 1375', 'National Job Fair', 'planned', '2025-04-29 00:49:02', '2025-04-29 00:49:02');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `record_type` varchar(50) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_seen` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `record_id`, `record_type`, `link`, `is_read`, `is_seen`, `created_at`) VALUES
(18, 1, 'Test from debug script at 2025-04-29 12:44:56', 1, 'test', 'dashboard.php', 1, 0, '2025-04-29 10:44:56'),
(19, 1, 'Your request for KALIX has been approved by Justine castanar. Comment: test', 10, 'direct_hire', 'direct_hire_view.php?id=10', 1, 0, '2025-04-30 04:19:32'),
(20, 2, 'Your request for JOLY has been approved by Justine castanar', 9, 'direct_hire', 'direct_hire_view.php?id=9', 1, 0, '2025-04-30 04:20:53'),
(21, 2, 'Your request for KALIX has been approved by Justine castanar', 10, 'direct_hire', 'direct_hire_view.php?id=10', 0, 0, '2025-04-30 05:00:47'),
(22, 1, 'Your request for dsadsal has been approved by Justine castanar', 7, 'direct_hire', 'direct_hire_view.php?id=7', 1, 0, '2025-04-30 05:15:48'),
(23, 1, 'Your request for KALIX has been denied by Justine castanar. Comment: fsafasdsds', 10, 'direct_hire', 'direct_hire_view.php?id=10', 1, 0, '2025-04-30 05:16:51'),
(24, 2, 'Your request for JADEERD has been approved by Justine castanar', 8, 'direct_hire', 'direct_hire_view.php?id=8', 0, 0, '2025-04-30 05:19:10'),
(25, 1, 'Your request for KALIX has been denied by Justine castanar. Comment: dsadaaaa', 10, 'direct_hire', 'direct_hire_view.php?id=10', 1, 0, '2025-04-30 05:25:15'),
(26, 2, 'Your request for dsads has been denied by Justine castanar. Comment: dsdsa', 4, 'direct_hire', 'direct_hire_view.php?id=4', 0, 0, '2025-04-30 05:26:51'),
(27, 1, 'Gov-to-Gov record for rtghj, vghb has been approved', 6, 'gov_to_gov', 'gov_to_gov.php?tab=endorsed', 0, 0, '2025-05-01 06:05:39'),
(28, 2, 'Your request for JOLY has been approved by Justine castanar', 9, 'direct_hire', 'direct_hire_view.php?id=9', 0, 0, '2025-05-01 07:03:39');

-- --------------------------------------------------------

--
-- Table structure for table `pending_g2g_approvals`
--

CREATE TABLE `pending_g2g_approvals` (
  `approval_id` int(11) NOT NULL,
  `g2g_id` int(11) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `submitted_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `employer` varchar(255) DEFAULT NULL,
  `memo_reference` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `approval_date` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_g2g_approvals`
--

INSERT INTO `pending_g2g_approvals` (`approval_id`, `g2g_id`, `submitted_by`, `submitted_date`, `employer`, `memo_reference`, `status`, `approval_date`, `approved_by`, `remarks`) VALUES
(15, 2, 1, '2025-05-01 01:54:58', 'fdsfsd', 'dfds', 'Pending', NULL, NULL, NULL),
(16, 7, 1, '2025-05-01 02:50:55', 'xzczx', 'cxzcx', 'Pending', NULL, NULL, NULL),
(17, 6, 1, '2025-05-01 02:51:21', 'dsa', 'dsa', 'Approved', '2025-05-01 06:05:39', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `signatories`
--

CREATE TABLE `signatories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `position_order` int(11) NOT NULL DEFAULT 0,
  `signature_file` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `signatories`
--

INSERT INTO `signatories` (`id`, `name`, `position`, `position_order`, `signature_file`, `active`, `created_at`, `updated_at`) VALUES
(1, 'IVAN ANGELO M. CANO', 'MWPD', 1, NULL, 1, '2025-04-26 07:20:54', '2025-04-26 07:20:54'),
(2, 'JOHN DOE', 'Department Head', 2, NULL, 1, '2025-04-26 07:20:54', '2025-04-26 07:20:54'),
(3, 'JANE SMITH', 'Director', 3, NULL, 1, '2025-04-26 07:20:54', '2025-04-26 07:20:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'User',
  `profile_picture` longtext DEFAULT NULL,
  `privacy_consent` tinyint(1) DEFAULT 0,
  `first_login` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `profile_picture`, `privacy_consent`, `first_login`) VALUES
(1, 'test', '$2y$10$uivhh9tfHw7ERzkmmshH3.w0EA25Bcn80PzKd/AeEeuL.Ra3qLU8O', 'Justine castanar', 'Regional Director', NULL, 1, 0),
(2, 'tester', '$2y$10$bGjccEYgV53CFn4TqBIT2O4ScQX9jq5AGFsJ6zHzxYQhl0TWmRuTO', 'submitter', 'User', NULL, 1, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clearance_approvals`
--
ALTER TABLE `clearance_approvals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `direct_hire`
--
ALTER TABLE `direct_hire`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `direct_hire_approvals`
--
ALTER TABLE `direct_hire_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `direct_hire_id` (`direct_hire_id`);

--
-- Indexes for table `direct_hire_clearance_approvals`
--
ALTER TABLE `direct_hire_clearance_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `direct_hire_id` (`direct_hire_id`);

--
-- Indexes for table `direct_hire_documents`
--
ALTER TABLE `direct_hire_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `direct_hire_id` (`direct_hire_id`);

--
-- Indexes for table `direct_hire_household`
--
ALTER TABLE `direct_hire_household`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `direct_hire_professional`
--
ALTER TABLE `direct_hire_professional`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `endorsed_gov_to_gov`
--
ALTER TABLE `endorsed_gov_to_gov`
  ADD PRIMARY KEY (`endorsed_id`);

--
-- Indexes for table `gov_to_gov`
--
ALTER TABLE `gov_to_gov`
  ADD PRIMARY KEY (`g2g`);

--
-- Indexes for table `job_fairs`
--
ALTER TABLE `job_fairs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pending_g2g_approvals`
--
ALTER TABLE `pending_g2g_approvals`
  ADD PRIMARY KEY (`approval_id`);

--
-- Indexes for table `signatories`
--
ALTER TABLE `signatories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clearance_approvals`
--
ALTER TABLE `clearance_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `direct_hire`
--
ALTER TABLE `direct_hire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `direct_hire_approvals`
--
ALTER TABLE `direct_hire_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `direct_hire_clearance_approvals`
--
ALTER TABLE `direct_hire_clearance_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `direct_hire_documents`
--
ALTER TABLE `direct_hire_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `direct_hire_household`
--
ALTER TABLE `direct_hire_household`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `direct_hire_professional`
--
ALTER TABLE `direct_hire_professional`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `endorsed_gov_to_gov`
--
ALTER TABLE `endorsed_gov_to_gov`
  MODIFY `endorsed_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gov_to_gov`
--
ALTER TABLE `gov_to_gov`
  MODIFY `g2g` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `job_fairs`
--
ALTER TABLE `job_fairs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `pending_g2g_approvals`
--
ALTER TABLE `pending_g2g_approvals`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `signatories`
--
ALTER TABLE `signatories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `direct_hire_approvals`
--
ALTER TABLE `direct_hire_approvals`
  ADD CONSTRAINT `direct_hire_approvals_ibfk_1` FOREIGN KEY (`direct_hire_id`) REFERENCES `direct_hire` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `direct_hire_clearance_approvals`
--
ALTER TABLE `direct_hire_clearance_approvals`
  ADD CONSTRAINT `direct_hire_clearance_approvals_ibfk_1` FOREIGN KEY (`direct_hire_id`) REFERENCES `direct_hire` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `direct_hire_documents`
--
ALTER TABLE `direct_hire_documents`
  ADD CONSTRAINT `direct_hire_documents_ibfk_1` FOREIGN KEY (`direct_hire_id`) REFERENCES `direct_hire` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
