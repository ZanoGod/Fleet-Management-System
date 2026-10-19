-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2026 at 07:20 AM
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
-- Database: `fleet-beta`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(80) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `is_active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$ClMVTxaI6Flqm1Oew9qAzOkAnpztCHwjt0IfXrToPP1Jb5lfMlslu', 1, '2026-10-19 09:45:35', '2026-07-16 05:22:33', '2026-10-19 03:15:35'),
(7, 'test', 'test@gmail.com', '$2y$10$GOw4rSqHQJKCTuIf0aHc2e2l313M9K3cvxm6jO2J88q6mkMQpqqAO', 1, NULL, '2026-07-28 09:03:20', '2026-07-28 09:03:20'),
(8, 'zano', 'zano@gmail.com', '$2y$10$R.7CthB1A/DTwfjVJgfSrOAkqPVhde5QlxWEg8wQ7m/NbeYuzglp.', 1, '2026-08-07 06:55:42', '2026-07-28 09:04:34', '2026-08-07 00:25:42');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `guest_company_name` varchar(255) NOT NULL,
  `car_id` int(10) UNSIGNED DEFAULT NULL,
  `custom_car_name` varchar(255) DEFAULT NULL,
  `secondary_car_id` int(10) UNSIGNED DEFAULT NULL,
  `driver_id` int(10) UNSIGNED DEFAULT NULL,
  `custom_driver_name` varchar(255) DEFAULT NULL,
  `operator_id` int(10) UNSIGNED DEFAULT NULL,
  `operator_name` varchar(150) NOT NULL,
  `even_odd` enum('Even','Odd') DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Pending','Confirm','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `guest_company_name`, `car_id`, `custom_car_name`, `secondary_car_id`, `driver_id`, `custom_driver_name`, `operator_id`, `operator_name`, `even_odd`, `start_date`, `end_date`, `status`, `remark`, `created_at`, `updated_at`) VALUES
(46, 'Mr.Fukuzawa Mr.okamoto Mr.Nakamura', 4, '', NULL, 5, '', 8, 'Hein Htet Zaw', '', '2026-06-03', '2026-06-04', 'Confirm', '', '2026-06-16 03:37:29', '2026-06-16 03:41:09'),
(47, 'Mr.Fukuzawa Mr.okamoto Mr.Nakamura', 4, '', NULL, 5, '', 8, 'Hein Htet Zaw', '', '2026-06-05', '2026-06-05', 'Confirm', '', '2026-06-16 03:40:57', '2026-06-16 03:40:57'),
(49, 'Sumitomo Electric /  Mr. Asai', 17, '', NULL, 16, '', 7, 'Thae Oo Min Eain', '', '2026-06-04', '2026-06-05', 'Confirm', '4L-5868(Alphard)   Ko Aung Kyaw Moe(09443079246)\r\n7H-5898(Alphard)   Ko Thar 09253387208', '2026-06-16 03:56:34', '2026-06-16 07:19:52'),
(50, 'Toyota Myanmar', 6, '', NULL, 1, '', 2, 'Nan Yee Mon Theint', '', '2026-06-02', '2026-06-03', 'Cancelled', '', '2026-06-16 03:58:08', '2026-06-16 03:58:08'),
(51, 'Toyota Myanmar', 6, '', NULL, 1, '', 2, 'Nan Yee Mon Theint', '', '2026-06-04', '2026-06-05', 'Cancelled', '', '2026-06-16 03:58:57', '2026-06-16 03:58:57'),
(52, 'Mr. Sato , Mr. Umesh', 1, '', NULL, 2, '', 8, 'Hein Htet Zaw', '', '2026-06-02', '2026-06-03', 'Confirm', '', '2026-06-16 04:00:23', '2026-06-16 04:00:23'),
(53, 'Mr. Sato , Mr. Umesh', 1, '', NULL, 2, '', 8, 'Hein Htet Zaw', '', '2026-06-04', '2026-06-05', 'Confirm', '', '2026-06-16 04:01:03', '2026-06-16 04:01:03'),
(54, 'Golden Dowa', 6, '', NULL, 14, '', 2, 'Nan Yee Mon Theint', '', '2026-06-07', '2026-06-07', 'Confirm', '', '2026-06-16 04:02:24', '2026-06-16 04:02:24'),
(55, 'Mr. Hidetoshi Fukui', 1, '', NULL, 2, '', 9, 'Ngwe Sin Thar', '', '2026-06-12', '2026-06-13', 'Confirm', '', '2026-06-16 04:04:02', '2026-06-16 04:04:02'),
(56, 'Mr. Hidetoshi Fukui', 1, '', NULL, 2, '', 9, 'Ngwe Sin Thar', '', '2026-06-14', '2026-06-15', 'Confirm', '', '2026-06-16 04:04:29', '2026-06-16 04:04:29'),
(57, 'Mr. Hidetoshi Fukui', 1, '', NULL, 2, '', 9, 'Ngwe Sin Thar', '', '2026-06-16', '2026-06-17', 'Confirm', '', '2026-06-16 04:04:53', '2026-06-16 04:04:53'),
(58, 'Mr. Hidetoshi Fukui', 1, '', NULL, 2, '', 9, 'Ngwe Sin Thar', '', '2026-06-18', '2026-06-19', 'Confirm', '', '2026-06-16 04:05:48', '2026-06-16 04:05:48'),
(59, 'Mr. Hidetoshi Fukui', 1, '', NULL, 2, '', 9, 'Ngwe Sin Thar', '', '2026-06-20', '2026-06-21', 'Confirm', '', '2026-06-16 04:06:18', '2026-06-16 04:06:18'),
(60, 'Mr. Hidetoshi Fukui', 1, '', NULL, 2, '', 8, 'Hein Htet Zaw', '', '2026-06-22', '2026-06-22', 'Confirm', '', '2026-06-16 04:07:00', '2026-06-16 04:07:00'),
(61, 'Mo Mo Finance', 2, '', NULL, 10, '', 2, 'Nan Yee Mon Theint', '', '2026-06-04', '2026-06-05', 'Cancelled', '', '2026-06-16 04:18:35', '2026-06-16 04:18:35'),
(62, 'Itsumiya', 6, '', NULL, 14, '', 16, 'Kaung Min Thant', '', '2026-06-05', '2026-06-05', 'Confirm', '', '2026-06-16 07:04:37', '2026-06-16 07:04:37'),
(63, 'SCSK Corporation Guest Name     : Mr. Yoichiro Iida', 6, '', NULL, 2, '', 11, 'Chu Saung Eain', '', '2026-06-08', '2026-06-09', 'Confirm', '', '2026-06-16 07:06:22', '2026-06-16 07:06:22'),
(64, 'SCSK Corporation Guest Name     : Mr. Yoichiro Iida', 6, '', NULL, 2, '', 11, 'Chu Saung Eain', '', '2026-06-10', '2026-06-11', 'Confirm', '', '2026-06-16 07:06:55', '2026-06-16 07:06:55'),
(65, 'Myanmar Legal', 18, '', NULL, 17, '', 2, 'Nan Yee Mon Theint', '', '2026-06-02', '2026-06-12', 'Confirm', '6E-7556(Alphard)\r\nU Thein Myint\r\n(09-5101632)', '2026-06-16 07:08:16', '2026-06-16 07:19:11'),
(66, 'Mother Brain Agency', 8, '', 9, 15, '', 4, 'Chaw Su May', '', '2026-06-01', '2026-06-02', 'Confirm', '', '2026-06-16 07:09:13', '2026-06-16 07:09:13'),
(67, 'Mother Brain Agency', 8, '', 9, 15, '', 4, 'Chaw Su May', '', '2026-06-03', '2026-06-04', 'Confirm', '', '2026-06-16 07:09:51', '2026-06-16 07:09:51'),
(68, 'Mother Brain Agency', 8, '', 9, 15, '', 4, 'Chaw Su May', '', '2026-06-05', '2026-06-05', 'Confirm', '', '2026-06-16 07:10:18', '2026-06-16 07:10:18'),
(69, 'Mother Brain Agency', 10, '', NULL, 8, '', 4, 'Chaw Su May', '', '2026-06-04', '2026-06-04', 'Cancelled', '', '2026-06-16 07:11:13', '2026-06-16 07:11:13'),
(70, 'JFE Steel/ Chiyoda San', NULL, '', NULL, NULL, '', 12, 'Khine Zar', '', '2026-06-23', '2026-06-23', 'Pending', '', '2026-06-16 07:11:44', '2026-06-16 07:11:44'),
(71, 'Mr. Shuji MIWA', 19, '', NULL, 16, '', 1, 'Ko Kaung Min Thant', 'Even', '2026-06-06', '2026-06-06', 'Confirm', 'Outside Car & Driver', '2026-06-16 07:12:56', '2026-06-16 07:23:04'),
(72, 'Mr. Masaki Horaguchi / Mitsubishi', 20, '', NULL, 20, '', 10, 'Htet Htet Hlaing', '', '2026-06-01', '2026-06-05', 'Confirm', '', '2026-06-16 07:31:07', '2026-06-16 07:31:07'),
(73, 'U Aung (Sayarsan REN)', 4, '', NULL, 8, '', 1, 'Ko Kaung Min Thant', '', '2026-06-07', '2026-06-07', 'Confirm', '', '2026-06-16 07:32:13', '2026-06-16 07:32:13'),
(74, 'Mr. Hiromi Deguchi + 4 local', 17, '', NULL, 17, '', 17, 'Win Kyaw Aung', '', '2026-06-16', '2026-06-18', 'Confirm', 'June 16  (Yangon - Hpa-An)\r\nJune 17  (Hpa-An Area Usage)\r\nJune 18  (Hpa-An - Yangon)    / 5F/7337 (Alphard) / Ko Than Naing Soe', '2026-06-16 07:34:57', '2026-06-16 07:45:31'),
(76, 'Mr. Naka & Mr. Kubota (Tenbells) 2 Pax', 3, '', NULL, 1, '', 4, 'Chaw Su May', '', '2026-06-16', '2026-06-17', 'Confirm', '', '2026-06-16 07:40:14', '2026-06-16 07:42:04'),
(77, 'Mr. Naka & Mr. Kubota (Tenbells) 2 Pax', 3, '', NULL, 1, '', 4, 'Chaw Su May', '', '2026-06-18', '2026-06-19', 'Confirm', '', '2026-06-16 07:41:30', '2026-06-16 07:41:55'),
(78, 'Satoshi Watanabe', 3, '', NULL, 3, '', 8, 'Hein Htet Zaw', '', '2026-06-14', '2026-06-14', 'Confirm', '', '2026-06-16 07:44:18', '2026-06-16 07:44:18'),
(79, 'Konoike Construction', NULL, '', NULL, NULL, '', 8, 'Hein Htet Zaw', '', '2026-06-25', '2026-06-25', 'Pending', '', '2026-06-16 07:46:39', '2026-06-16 07:46:39'),
(80, 'Toyota Myanmar', 5, '', NULL, 15, '', 7, 'Thae Oo Min Eain', '', '2026-06-15', '2026-06-16', 'Confirm', '', '2026-06-16 07:47:34', '2026-06-16 07:47:34'),
(81, 'Toyota Myanmar', 5, '', NULL, 15, '', 7, 'Thae Oo Min Eain', '', '2026-06-17', '2026-06-17', 'Confirm', '', '2026-06-16 07:48:04', '2026-06-16 07:48:04'),
(82, 'Mr. Marumo', 4, '', NULL, 2, '', 8, 'Hein Htet Zaw', '', '2026-06-23', '2026-06-23', 'Confirm', '', '2026-06-16 07:49:12', '2026-06-16 07:49:12'),
(83, 'Mr. Marumo', 4, '', NULL, 2, '', 8, 'Hein Htet Zaw', '', '2026-06-25', '2026-06-25', 'Confirm', '', '2026-06-16 07:50:06', '2026-06-16 07:50:06'),
(84, 'Mr. Yuji Kanashiki (License Academy)', NULL, '', NULL, NULL, '', 4, 'Chaw Su May', '', '2026-06-25', '2026-06-25', 'Pending', '', '2026-06-16 07:50:34', '2026-06-16 08:00:36'),
(85, 'Mr. Kita', 21, '', NULL, 8, '', 9, 'Ngwe Sin Thar', '', '2026-06-13', '2026-06-14', 'Confirm', '', '2026-06-16 07:52:41', '2026-06-16 07:52:41'),
(86, 'Mr. Kanemura (Tenbells) 1 Pax', 6, '', NULL, 1, '', 4, 'Chaw Su May', '', '2026-06-25', '2026-06-26', 'Confirm', '', '2026-06-16 07:53:29', '2026-06-16 08:03:00'),
(87, 'Mr. Kanemura (Tenbells) 1 Pax', 6, '', NULL, 1, '', 4, 'Chaw Su May', '', '2026-06-27', '2026-06-27', 'Confirm', '', '2026-06-16 07:53:54', '2026-06-16 08:03:29'),
(88, 'Shonan’s Japanese customer', NULL, '', NULL, NULL, '', 12, 'Khine Zar', '', '2026-06-25', '2026-06-26', 'Pending', '', '2026-06-16 07:54:28', '2026-06-16 07:54:28'),
(89, 'Shonan’s Japanese customer', NULL, '', NULL, NULL, '', 12, 'Khine Zar', NULL, '2026-06-27', '2026-06-28', 'Pending', '', '2026-06-16 07:54:52', '2026-06-19 04:38:05'),
(90, 'Hitachi Limited  Mr. Yanagida & Mr. Andi', 22, '', NULL, 1, '', 10, 'Htet Htet Hlaing', '', '2026-06-20', '2026-06-20', 'Confirm', '', '2026-06-16 07:55:47', '2026-06-17 07:09:13'),
(91, 'zin waiyan', NULL, '', NULL, NULL, '', 13, 'Zin Me Yu Lay', NULL, '2026-06-20', '2026-06-22', 'Pending', '', '2026-06-19 04:39:06', '2026-06-19 04:39:06'),
(92, 'Sumitomo Electric /  Mr. Asai', 5, '', NULL, 2, '', 1, 'Ko Kaung Min Thant', NULL, '2026-08-12', '2026-08-15', 'Confirm', '', '2026-08-12 08:30:17', '2026-08-12 08:30:17');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(10) UNSIGNED NOT NULL,
  `car_type` varchar(100) NOT NULL,
  `plate_no` varchar(50) NOT NULL,
  `model_name` varchar(100) NOT NULL,
  `seat_capacity` tinyint(3) UNSIGNED NOT NULL DEFAULT 4,
  `availability_status` enum('Available','Assigned','Maintenance') NOT NULL DEFAULT 'Available',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `car_type`, `plate_no`, `model_name`, `seat_capacity`, `availability_status`, `note`, `created_at`, `updated_at`) VALUES
(1, 'Alphard', '2G/4165', '2002', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(2, 'Alphard', '2G/5649', '2002', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(3, 'Alphard', '4H/2590', '2003', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(4, 'Alphard', '3J/4760', '2005', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(5, 'Prado', '3M/8296', '2006', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(6, 'Prado', '7E/7850', '1999', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(7, 'Hi Ace 9 Seater', '1N/5660', '2007', 9, 'Available', 'Naypyitaw Office', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(8, 'Hi Ace 9 Seater', '6N/4680', '2008', 9, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(9, 'Hi Ace 14 Seater', '7K/5572', '2005', 14, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(10, 'Ertiga Type II', '4N/4401', '2017', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(11, 'Ertiga Type II', '7P/8951', '2017', 7, 'Available', 'Thanlyin Refinery 01 Jun', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(12, 'Wish', '5I/2273', '2003', 7, 'Available', 'JFE Engineering 05-Jun / Nikken 08-Jun', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(13, 'Alphard', '9L/1268', '2006', 7, 'Available', 'available on 21.06.26', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(14, 'Zinger', '6E/4489', '2012', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(15, 'Mirage', '9N/3501', '2016', 4, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(16, 'Outside Car (Ko Than Naing Soe)', '5F/7337', '-', 4, 'Available', '', '2026-06-16 07:13:56', '2026-06-16 07:15:46'),
(17, 'Outside Car ( Myo Myanmar)', '1', '-', 4, 'Available', '', '2026-06-16 07:17:16', '2026-06-16 07:28:00'),
(18, 'Outside Car (Myo Myanmar)', '2', '-', 4, 'Available', '', '2026-06-16 07:18:54', '2026-06-16 07:27:46'),
(19, 'Outside Car', 'Nay Pyi Taw', '-', 4, 'Available', '', '2026-06-16 07:22:52', '2026-06-16 07:22:52'),
(20, 'Outside Car (Myo Myanmar)', '3', '-', 4, 'Available', '', '2026-06-16 07:29:09', '2026-06-16 07:29:35'),
(21, 'Toyota Allion', '2K-6398', '2010', 4, 'Available', '', '2026-06-16 07:52:13', '2026-06-16 07:52:13'),
(22, 'HiAce 9 seaters', '3P/1031', '2008', 9, 'Available', '', '2026-06-17 07:08:50', '2026-06-17 07:08:50');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `off_day` varchar(50) DEFAULT NULL,
  `service_hour` varchar(100) DEFAULT NULL,
  `customer_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `company_name`, `guest_name`, `contact_person`, `contact_phone`, `contact_email`, `customer_address`, `off_day`, `service_hour`, `customer_status`, `remark`, `created_at`, `updated_at`) VALUES
(1, 'SCSK Myanmar', NULL, NULL, '09-700-111-222', 'admin@scsk.example', NULL, 'Sunday', '09:00 AM - 06:00 PM', 'Active', 'Yangon', '2026-07-22 10:03:46', '2026-07-24 05:26:13'),
(2, 'Mitsubishi Corporation', 'user', 'Ishiki Ishiki', '09-700-333-444', 'fleet@mitsubishi.example', NULL, 'Sat, Sun & Public Holidays', '10 hour', 'Active', 'Bahan, Yangon', '2026-07-22 10:03:46', '2026-07-24 08:24:01'),
(3, 'Nikken International Myanmar Co., Ltd.', NULL, NULL, '09-700-555-666', '', NULL, 'Sat, Sun & Public Holidays', '09:00 AM - 06:00 PM', 'Active', 'Hlaing, Yangon', '2026-07-22 10:03:46', '2026-07-24 05:26:40'),
(4, 'test', '', '', '0921341441', 'test@gmail.com', '', 'Sat, Sun & Public Holidays', '', 'Active', 'No.123445 street', '2026-07-22 10:03:46', '2026-07-28 03:54:29'),
(8, 'Google', 'Company Use', 'google', '0923141244', 'admin@gmail.com', NULL, 'Once a week', '12 hours', 'Active', '', '2026-07-22 10:48:44', '2026-07-27 04:02:21'),
(9, 'Kubota Corporation (Yangon Branch)', 'Company Use', '', '', 'kaythwe.soe@kubota.com', NULL, 'Sunday & Public Holidays', '10 hour', 'Active', '', '2026-07-24 08:15:43', '2026-07-24 08:23:46'),
(10, 'JFE Myanmar Coated Steel Co., Ltd', '\"Mr.Tsuchiya Kenji (+95) 9441 579 585\"', 'Ko Lin Zayar', '09-787993304', 'linzayarkyaw@jfemyanmarcoat.com', NULL, 'Sunday & Public Holidays', '7:00 ~ 22:00 | 12 Hour', 'Active', '', '2026-07-24 08:25:47', '2026-08-11 09:41:17'),
(11, 'SMBC', 'User', 'Ma Kyi Sin Win Pa (09-5052144)', '', 'kyi_sin_win_pa@mm.smbc.co.jp', NULL, 'Public Holidays', '12 hour', 'Active', '', '2026-07-24 08:40:46', '2026-07-27 04:02:06'),
(12, 'Konoike Construction Co.,Ltd', 'Mr. Johtsuka', 'Mr.Johtsuka - 09953311304  Mr.Iwai - 09964344103  iwai_yk@konoike.co.jp | johtsuka_ty@konoike.co.jp', '', 'iwai_yk@konoike.co.jp', NULL, 'Sunday & Public Holidays', '12 hour', 'Active', '', '2026-07-24 08:47:05', '2026-07-24 08:47:05'),
(13, 'TEST 1', 'test 1', 'test', '0923141244', 'admin@gmail.com', NULL, 'Sat, Sun & Public Holidays', '09:00 AM - 06:00 PM', 'Active', '', '2026-07-27 05:07:58', '2026-07-27 05:07:58'),
(14, 'Sumitomo Corporation  Asia & Oceania Pte. Ltd', 'user', 'Nang San', '09-405149973', 'nang.san@sumitomocorp.com', '', 'Sat or Sun & Public Holidays', '10.5 Hour', 'Active', '', '2026-07-27 06:52:12', '2026-07-27 07:08:58'),
(15, 'TEST 3', 'User 3', 'User 3', '09231413312', 'user@gmail.com', '', 'Sat, Sun & Public Holidays', '09:00 AM - 06:00 PM', 'Active', '', '2026-07-28 08:05:27', '2026-07-28 08:07:29'),
(17, 'ZANO Game Development', 'Zano', 'Zano', '09238340211', 'zano@admn.com', '', 'Sat, Sun & Public Holidays', '09:00 AM - 04:30 PM', 'Active', '', '2026-07-28 10:08:57', '2026-08-11 10:06:57');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `license_no` varchar(100) DEFAULT NULL,
  `driver_status` enum('Available','Assigned','Leave','Inactive') NOT NULL DEFAULT 'Available',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `full_name`, `phone_number`, `license_no`, `driver_status`, `note`, `created_at`, `updated_at`) VALUES
(1, 'Ko Myo Thet Tun', '09-777714566', NULL, 'Available', NULL, '2026-06-04 10:25:14', '2026-06-04 10:25:14'),
(2, 'Ko Han Win Aung', '09-420177161', NULL, 'Available', NULL, '2026-06-04 10:25:14', '2026-06-04 10:25:14'),
(3, 'Ko Pyae Phyo Aung', '09-424633929', NULL, 'Available', 'Available on 06 Jun', '2026-06-04 10:25:14', '2026-06-04 10:25:14'),
(4, 'Ko Hlaing Min Oo', '09-263992711', NULL, 'Available', 'Oncall Driver', '2026-06-04 10:25:14', '2026-06-04 10:25:14'),
(5, 'Ko Ye Win Tun', '09-790379710', NULL, 'Available', 'Oncall Driver', '2026-06-04 10:25:14', '2026-06-04 10:25:14'),
(6, 'Ko Myint Zaw Hlaing', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(7, 'U Zaw Khin', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(8, 'Ko Aye Min Tun', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(9, 'Ko Kyaw Htoo', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(10, 'Ko Aung Kyaw Moe', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(11, 'Ko Zaw Zaw Aung', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(12, 'Ko Than Naing Soe', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(13, 'Ko Khine Win Aung', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(14, 'Ko Kyaw Swar Lwin', '-', NULL, 'Available', '', '2026-06-08 09:13:16', '2026-06-08 09:13:16'),
(15, 'Ko Soe Kyaw Kyaw', '-', NULL, 'Available', '', '2026-06-08 09:17:39', '2026-06-08 09:17:39'),
(16, 'Outside Driver', 'Naypyitaw', NULL, 'Available', '', '2026-06-16 07:14:14', '2026-06-16 07:22:12'),
(17, 'Outside Driver', 'Myo Myanmar 1', NULL, 'Available', '', '2026-06-16 07:16:26', '2026-06-16 07:24:10'),
(18, 'Outside Driver', 'Myo  Myanmar 2', NULL, 'Available', '', '2026-06-16 07:25:41', '2026-06-16 07:25:41'),
(19, 'Outside Driver', 'Ko Than Naing Soe', NULL, 'Available', '', '2026-06-16 07:26:02', '2026-06-16 07:26:02'),
(20, 'Outside Driver', 'Myo Myanmar  3', NULL, 'Available', '', '2026-06-16 07:30:22', '2026-06-16 07:30:22');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_cars`
--

CREATE TABLE `monthly_cars` (
  `id` int(10) UNSIGNED NOT NULL,
  `car_type` varchar(100) NOT NULL,
  `plate_no` varchar(50) NOT NULL,
  `model_name` varchar(100) NOT NULL,
  `seat_capacity` tinyint(3) UNSIGNED NOT NULL DEFAULT 4,
  `availability_status` enum('Available','Assigned','Maintenance','Inactive') NOT NULL DEFAULT 'Available',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monthly_cars`
--

INSERT INTO `monthly_cars` (`id`, `car_type`, `plate_no`, `model_name`, `seat_capacity`, `availability_status`, `note`, `created_at`, `updated_at`) VALUES
(1, 'Alphard', '2G/4165', '2002', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(2, 'Alphard', '2G/5649', '2002', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(3, 'Alphard', '4H/2590', '2003', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(4, 'Alphard', '3J/4760', '2005', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(5, 'Prado', '3M/8296', '2006', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(6, 'Prado', '7E/7850', '1999', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(7, 'Hi Ace 9 Seater', '1N/5660', '2007', 9, 'Available', 'Naypyitaw Office', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(8, 'Hi Ace 9 Seater', '6N/4680', '2008', 9, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(9, 'Hi Ace 14 Seater', '7K/5572', '2005', 14, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(10, 'Ertiga Type II', '4N/4401', '2017', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(11, 'Ertiga Type II', '7P/8951', '2017', 7, 'Available', 'Thanlyin Refinery 01 Jun', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(12, 'Wish', '5I/2273', '2003', 7, 'Available', 'JFE Engineering 05-Jun / Nikken 08-Jun', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(13, 'Alphard', '9L/1268', '2006', 7, 'Available', 'available on 21.06.26', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(14, 'Zinger', '6E/4489', '2012', 7, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(15, 'Mirage', '9N/3501', '2016', 4, 'Available', '', '2026-06-04 10:17:59', '2026-06-04 10:17:59'),
(16, 'Outside Car (Ko Than Naing Soe)', '5F/7337', '-', 4, 'Available', '', '2026-06-16 07:13:56', '2026-06-16 07:15:46'),
(17, 'Outside Car ( Myo Myanmar)', '1', '-', 4, 'Available', '', '2026-06-16 07:17:16', '2026-06-16 07:28:00'),
(18, 'Outside Car (Myo Myanmar)', '2', '-', 4, 'Available', '', '2026-06-16 07:18:54', '2026-06-16 07:27:46'),
(19, 'Outside Car', 'Nay Pyi Taw', '-', 4, 'Available', '', '2026-06-16 07:22:52', '2026-06-16 07:22:52'),
(21, 'Toyota Allion', '2K-6398', '2010', 4, 'Available', '', '2026-06-16 07:52:13', '2026-06-16 07:52:13'),
(22, 'HiAce 9 seaters', '3P/1031', '2008', 9, 'Available', '', '2026-06-17 07:08:50', '2026-06-17 07:08:50'),
(23, 'Myo Myanmar', '3', '2002', 4, 'Available', '', '2026-07-22 07:37:34', '2026-07-22 07:37:34'),
(24, 'Vellfire', '5P/7474', '-', 4, 'Available', '', '2026-07-27 06:53:10', '2026-07-27 06:53:10'),
(28, 'Alphard', '7H/8341', '2002', 7, 'Available', '', '2026-07-27 06:57:40', '2026-07-27 06:57:40'),
(29, 'BMW M3', '9S/1234', '2020', 2, 'Available', '', '2026-07-28 08:06:25', '2026-07-28 08:06:25'),
(30, 'BYD', '3K/1234', '2025', 4, 'Available', '', '2026-08-11 10:09:27', '2026-08-11 10:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_contracts`
--

CREATE TABLE `monthly_contracts` (
  `id` int(10) UNSIGNED NOT NULL,
  `contract_no` varchar(50) NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `assignment_type` enum('Driver Only','Vehicle Only','Vehicle and Driver') NOT NULL DEFAULT 'Vehicle Only',
  `car_id` int(10) UNSIGNED DEFAULT NULL,
  `driver_id` int(10) UNSIGNED DEFAULT NULL,
  `operator_id` int(10) UNSIGNED DEFAULT NULL,
  `operator_name` varchar(150) DEFAULT NULL,
  `even_odd` enum('Even','Odd') DEFAULT NULL,
  `operation_sheet` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `monthly_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `rental_currency` enum('MMK','USD') NOT NULL DEFAULT 'MMK',
  `rental_status` varchar(50) NOT NULL,
  `status` enum('Draft','Active','Expired','Renewed','Cancelled') NOT NULL DEFAULT 'Active',
  `renewal_parent_id` int(10) UNSIGNED DEFAULT NULL,
  `note` text DEFAULT NULL,
  `prepared_by` varchar(150) DEFAULT NULL,
  `invoice_sender` varchar(150) DEFAULT NULL,
  `checked_by` varchar(150) DEFAULT NULL,
  `off_day` varchar(100) DEFAULT NULL,
  `service_hour` varchar(100) DEFAULT NULL,
  `insurance_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `insurance_company` varchar(255) DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monthly_contracts`
--

INSERT INTO `monthly_contracts` (`id`, `contract_no`, `customer_id`, `customer_name`, `guest_name`, `contact_person`, `customer_phone`, `customer_email`, `customer_address`, `assignment_type`, `car_id`, `driver_id`, `operator_id`, `operator_name`, `even_odd`, `operation_sheet`, `start_date`, `end_date`, `monthly_rate`, `rental_currency`, `rental_status`, `status`, `renewal_parent_id`, `note`, `prepared_by`, `invoice_sender`, `checked_by`, `off_day`, `service_hour`, `insurance_price`, `insurance_company`, `terms_conditions`, `created_at`, `updated_at`) VALUES
(2, 'MC-202607-0001', 1, 'SCSK Myanmar', NULL, NULL, '09-700-111-222', 'admin@scsk.example', 'Yangon', 'Vehicle and Driver', 1, 1, NULL, NULL, NULL, NULL, '2026-07-01', '2026-07-31', 2500000.00, 'MMK', 'Paid', 'Active', NULL, 'Monthly executive vehicle with assigned driver.', NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, '2026-07-16 06:51:42', '2026-07-22 10:03:59'),
(3, 'MC-202607-0002', 2, 'Mitsubishi Corporation', NULL, NULL, '09-700-333-444', 'fleet@mitsubishi.example', 'Bahan, Yangon', 'Vehicle Only', 2, NULL, NULL, NULL, NULL, NULL, '2026-07-05', '2026-08-04', 1800000.00, 'MMK', 'Pending', 'Active', NULL, 'Vehicle-only monthly contract.', NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, '2026-07-16 06:51:42', '2026-07-22 10:03:59'),
(4, 'MC-202606-0003', 3, 'Nikken International Myanmar Co., Ltd.', NULL, NULL, '09-700-555-666', '', 'Hlaing, Yangon', '', NULL, NULL, 13, 'Zin Me Yu Lay', NULL, NULL, '2026-06-01', '2026-06-30', 0.00, 'MMK', 'Overdue', 'Renewed', NULL, 'Customer contract record kept without assigned resources.', NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, '2026-07-16 06:51:42', '2026-07-27 04:56:01'),
(5, 'MC-202607-0003', 4, 'test', '', '', '0921341441', 'test@gmail.com', 'No.123445 street', 'Vehicle Only', 8, NULL, 21, 'Testingsdf', '', '0', '2026-07-26', '2026-08-26', 750000.00, '', 'Including fuel', 'Renewed', NULL, '', '', '', '', '', '', 0.00, '', '', '2026-07-22 06:58:20', '2026-07-28 05:34:35'),
(6, 'MC-202607-0004', 8, 'Google', '', '', '', '', '', 'Vehicle Only', 17, NULL, 1, 'Ko Kaung Min Thant', '', '', '2026-07-22', '2026-08-21', 1200000.00, 'MMK', 'Pending', 'Renewed', NULL, '', '', '', '', '', '', 1000000.00, 'GGI', '', '2026-07-22 10:51:51', '2026-07-28 04:11:34'),
(7, 'MC-202607-0005', 3, 'Nikken International Myanmar Co., Ltd.', '', '', '09-700-555-666', '', '', 'Vehicle and Driver', 5, 9, 16, 'Kaung Min Thant', 'Even', '01/08/2026 - 25/08/2026', '2026-08-01', '2026-08-25', 850000.00, 'MMK', 'Pending', 'Active', NULL, '', '', '', '', 'Sat, Sun & Public Holidays', '09:00 AM - 06:00 PM', 1000000.00, 'AYA', '', '2026-07-24 11:10:55', '2026-07-27 04:49:37'),
(8, 'MC-202607-0006', 3, 'Nikken International Myanmar Co., Ltd.', 'Nikken', 'Company use', '09-700-555-666', '', 'Hlaing, Yangon', 'Vehicle Only', 14, NULL, 13, 'Zin Me Yu Lay', 'Even', '01/08/2026 - 31/08/2026', '2026-08-01', '2026-08-31', 20000000.00, 'MMK', 'Pending', 'Active', 4, 'Renewed from MC-202606-0003. Customer contract record kept without assigned resources.', 'Zin Me Yu Lay', 'Zin Me Yu Lay', 'Kaung Min Thant', 'Sat, Sun & Public Holidays', '09:00 AM - 06:00 PM', 1000000.00, 'AYA', '', '2026-07-27 04:56:01', '2026-07-27 04:56:01'),
(9, 'MC-202607-0007', 3, 'Nikken International Myanmar Co., Ltd.', '', '', '09-700-555-666', '', 'Hlaing, Yangon', 'Vehicle Only', 14, NULL, 13, 'Zin Me Yu Lay', 'Even', '', '2026-07-01', '2026-07-31', 2000000.00, 'MMK', 'Pending', 'Renewed', 4, 'Renewed from MC-202606-0003. Customer contract record kept without assigned resources.', 'Zin Me Yu Lay', 'Zin Me Yu Lay', 'Kaung Min Thant', '', '', 0.00, '', '', '2026-07-27 05:05:48', '2026-07-28 04:01:11'),
(10, 'MC-202607-0008', 13, 'TEST 1', 'test 1', 'test', '0923141244', 'admin@gmail.com', '', 'Vehicle Only', 13, NULL, 13, 'Zin Me Yu Lay', '', '', '2026-07-01', '2026-08-25', 2000000.00, 'MMK', 'Paid', 'Renewed', NULL, '', 'Zin Me Yu Lay', 'Zin Me Yu Lay', 'Kaung Min Thant', 'Sat, Sun & Public Holidays', '09:00 AM - 06:00 PM', 0.00, '', '', '2026-07-27 05:09:11', '2026-07-27 05:10:22'),
(11, 'MC-202608-0001', 13, 'TEST 1', 'test 1', 'test', '0923141244', 'admin@gmail.com', '', 'Vehicle Only', 3, NULL, 13, 'Zin Me Yu Lay', '', '', '2026-08-01', '2026-09-25', 2000000.00, 'MMK', 'Pending', 'Active', 10, 'Renewed from MC-202607-0008.', 'Zin Me Yu Lay', 'Zin Me Yu Lay', 'Kaung Min Thant', 'Sat, Sun & Public Holidays', '09:00 AM - 06:00 PM', 0.00, '', '', '2026-07-27 05:10:22', '2026-07-27 05:10:22'),
(12, 'MC-202607-0009', 14, 'Sumitomo Corporation  Asia & Oceania Pte. Ltd', 'user', 'Nang San', '09-405149973', 'nang.san@sumitomocorp.com', '', 'Vehicle and Driver', 24, 12, 2, 'Nan Yee Mon Theint', 'Odd', '1.07', '2026-07-27', '2026-08-26', 7192500.00, '', 'Including fuel', 'Active', NULL, '', 'Nan Yee Mon Theint', 'test', 'test', 'Sat or Sun & Public Holidays', '10.5 Hour', 11326440.00, 'GGI', '', '2026-07-27 07:08:58', '2026-07-28 05:32:47'),
(13, 'MC-202607-0010', 3, 'Nikken International Myanmar Co., Ltd.', '', '', '09-700-555-666', '', 'Hlaing, Yangon', 'Vehicle Only', 23, NULL, 13, 'Zin Me Yu Lay', 'Even', '0', '2026-08-01', '2026-09-30', 2000000.00, '', 'Pending', 'Active', 9, 'Renewed from Nikken International Myanmar Co., Ltd.', 'Zin Me Yu Lay', 'Zin Me Yu Lay', 'Kaung Min Thant', 'Sat, Sun & Public Holidays', '10 hr', 0.00, '', '', '2026-07-28 04:01:11', '2026-07-28 04:01:11'),
(14, 'MC-202607-0011', 8, 'Google', '', '', '', '', '', 'Vehicle and Driver', 9, 11, 1, 'Ko Kaung Min Thant', '', '0', '2026-08-01', '2026-09-30', 1200000.00, '', 'Pending', 'Active', 6, 'Renewed from Google', '', '', '', '', '', 1000000.00, 'GGI', '', '2026-07-28 04:11:34', '2026-07-28 04:11:34'),
(15, 'MC-202607-0012', 4, 'test', '', '', '0921341441', 'test@gmail.com', 'No.123445 street', 'Driver Only', NULL, NULL, 21, 'Testingsdf', '', '0', '2026-08-01', '2026-09-30', 750000.00, '', 'Driver only', 'Active', 5, 'Renewed from test', '', '', '', 'Public Holidays', '', 0.00, '', '', '2026-07-28 05:34:35', '2026-07-28 05:34:35'),
(16, 'MC-202607-0013', 15, 'TEST 3', 'User 3', 'User 3', '09231413312', 'user@gmail.com', '', 'Vehicle Only', 29, NULL, 1, 'Ko Kaung Min Thant', 'Odd', '0', '2026-06-30', '2026-07-28', 2000000.00, '', 'Including fuel', 'Renewed', NULL, '', 'Nan Yee Mon Theint', 'Zin Me Yu Lay', 'Chaw Su May', 'Sat, Sun & Public Holidays', '09:00 AM - 06:00 PM', 9000000.00, 'AYA', '', '2026-07-28 08:07:29', '2026-07-28 08:10:25'),
(17, 'MC-202607-0014', 15, 'TEST 3', 'User 3', 'User 3', '09231413312', 'user@gmail.com', '', 'Vehicle Only', 29, NULL, 1, 'Ko Kaung Min Thant', 'Odd', '0', '2026-07-30', '2026-08-31', 2000000.00, '', 'Including fuel', 'Active', 16, 'Renewed from TEST 3', 'Nan Yee Mon Theint', 'Zin Me Yu Lay', 'Chaw Su May', 'Sat, Sun & Public Holidays', '09:00 AM - 06:00 PM', 9000000.00, 'AYA', '', '2026-07-28 08:10:25', '2026-07-28 08:10:25'),
(18, 'MC-202608-0002', 10, 'JFE Myanmar Coated Steel Co., Ltd', '\"Mr.Tsuchiya Kenji (+95) 9441 579 585\"', 'Ko Lin Zayar', '09-787993304', 'linzayarkyaw@jfemyanmarcoat.com', '', 'Vehicle and Driver', 13, 4, 2, 'Nan Yee Mon Theint', 'Even', '0', '2026-08-11', '2026-10-10', 750000.00, '', 'Including fuel', 'Active', NULL, '', 'Thae Oo Min Eain', 'Thae Oo Min Eain', 'Nan Yee Mon Theint', 'Sunday & Public Holidays', '7:00 ~ 22:00 | 12 Hour', 0.00, '', '', '2026-08-11 09:41:17', '2026-08-11 10:00:13'),
(19, 'MC-202608-0003', 17, 'ZANO Game Development', 'Zano', 'Zin', '09238340211', 'zano@admn.com', '', 'Vehicle Only', 21, NULL, 16, 'Kaung Min Thant', '', '0', '2026-08-11', '2026-09-10', 0.00, '', 'Including fuel', 'Active', NULL, '', 'Ko Kaung Min Thant', 'Testingsdf', 'Testingsdf', 'Sat, Sun & Public Holidays', '09:00 AM - 04:30 PM', 0.00, '', '', '2026-08-11 10:06:57', '2026-08-11 10:06:57'),
(20, 'MC-202608-0004', 17, 'ZANO Game Development', 'Zano', 'ZIN', '09238340211', 'zano@admn.com', '', 'Vehicle Only', 30, NULL, 2, 'Nan Yee Mon Theint', '', '0', '2026-08-11', '2026-09-10', 0.00, '', 'Including fuel', 'Active', NULL, '', 'Nan Yee Mon Theint', 'Nan Yee Mon Theint', 'Nan Yee Mon Theint', 'Sat, Sun & Public Holidays', '09:00 AM - 04:30 PM', 0.00, '', '', '2026-08-11 10:10:12', '2026-08-11 10:10:12');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_drivers`
--

CREATE TABLE `monthly_drivers` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `license_no` varchar(100) DEFAULT NULL,
  `driver_status` enum('Available','Assigned','Leave','Inactive') NOT NULL DEFAULT 'Available',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monthly_drivers`
--

INSERT INTO `monthly_drivers` (`id`, `full_name`, `phone_number`, `license_no`, `driver_status`, `note`, `created_at`, `updated_at`) VALUES
(1, 'Ko Myo Thet Tun', '09-777714566', NULL, 'Available', NULL, '2026-06-04 10:25:14', '2026-06-04 10:25:14'),
(2, 'Ko Han Win Aung', '09-420177161', NULL, 'Available', NULL, '2026-06-04 10:25:14', '2026-06-04 10:25:14'),
(3, 'Ko Pyae Phyo Aung', '09-424633929', NULL, 'Available', 'Available on 06 Jun', '2026-06-04 10:25:14', '2026-06-04 10:25:14'),
(4, 'Ko Hlaing Min Oo', '09-263992711', NULL, 'Available', 'Oncall Driver', '2026-06-04 10:25:14', '2026-06-04 10:25:14'),
(5, 'Ko Ye Win Tun', '09-790379710', NULL, 'Available', 'Oncall Driver', '2026-06-04 10:25:14', '2026-06-04 10:25:14'),
(6, 'Ko Myint Zaw Hlaing', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(7, 'U Zaw Khin', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(8, 'Ko Aye Min Tun', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(9, 'Ko Kyaw Htoo', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(11, 'Ko Zaw Zaw Aung', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(12, 'Ko Than Naing Soe', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(13, 'Ko Khine Win Aung', '', NULL, 'Available', NULL, '2026-06-08 06:46:20', '2026-06-08 06:46:20'),
(14, 'Ko Kyaw Swar Lwin', '-', NULL, 'Available', '', '2026-06-08 09:13:16', '2026-06-08 09:13:16'),
(15, 'Ko Soe Kyaw Kyaw', '-', NULL, 'Available', '', '2026-06-08 09:17:39', '2026-06-08 09:17:39'),
(16, 'Outside Driver', 'Naypyitaw', NULL, 'Available', '', '2026-06-16 07:14:14', '2026-06-16 07:22:12'),
(17, 'Outside Driver', 'Myo Myanmar 1', NULL, 'Available', '', '2026-06-16 07:16:26', '2026-06-16 07:24:10'),
(18, 'Outside Driver', 'Myo  Myanmar 2', NULL, 'Available', '', '2026-06-16 07:25:41', '2026-06-16 07:25:41'),
(19, 'Outside Driver', 'Ko Than Naing Soe', NULL, 'Available', '', '2026-06-16 07:26:02', '2026-06-16 07:26:02'),
(20, 'Outside Driver', 'Myo Myanmar  3', NULL, 'Available', '', '2026-06-16 07:30:22', '2026-06-16 07:30:22'),
(22, 'Ko Aung Kyaw Moe', '-', NULL, 'Available', '', '2026-07-22 09:05:59', '2026-07-22 09:05:59'),
(23, 'Ko Aung Kyaw Moe', '=', NULL, 'Available', '', '2026-07-22 09:06:11', '2026-07-22 09:06:11'),
(24, 'Myo Myanmar 1', '08921893', NULL, 'Available', '', '2026-07-23 08:32:05', '2026-07-23 08:32:05'),
(25, 'Ko Arkar Kyaw Lin', '09-448017560', NULL, 'Available', '', '2026-07-27 06:52:40', '2026-07-27 06:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `operators`
--

CREATE TABLE `operators` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone_number` varchar(30) DEFAULT NULL,
  `operator_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `operators`
--

INSERT INTO `operators` (`id`, `full_name`, `phone_number`, `operator_status`, `note`, `created_at`, `updated_at`) VALUES
(1, 'Ko Kaung Min Thant', '', 'Active', '', '2026-06-08 05:10:26', '2026-06-08 05:10:26'),
(2, 'Nan Yee Mon Theint', NULL, 'Active', NULL, '2026-06-08 05:30:00', '2026-06-08 05:30:00'),
(3, 'Min Thant', NULL, 'Active', NULL, '2026-06-08 05:30:00', '2026-06-08 05:30:00'),
(4, 'Chaw Su May', NULL, 'Active', NULL, '2026-06-08 05:30:00', '2026-06-08 05:30:00'),
(5, 'Nan Toe', NULL, 'Active', NULL, '2026-06-08 05:30:00', '2026-06-08 05:30:00'),
(6, 'Thin Thin Aye', NULL, 'Active', NULL, '2026-06-08 05:30:00', '2026-06-08 05:30:00'),
(7, 'Thae Oo Min Eain', NULL, 'Active', NULL, '2026-06-08 05:30:00', '2026-06-08 05:30:00'),
(8, 'Hein Htet Zaw', NULL, 'Active', NULL, '2026-06-08 05:30:00', '2026-06-08 05:30:00'),
(9, 'Ngwe Sin Thar', NULL, 'Active', NULL, '2026-06-08 05:30:00', '2026-06-08 05:30:00'),
(10, 'Htet Htet Hlaing', NULL, 'Active', NULL, '2026-06-08 05:30:00', '2026-06-08 05:30:00'),
(11, 'Chu Saung Eain', NULL, 'Active', NULL, '2026-06-08 05:30:00', '2026-06-08 05:30:00'),
(12, 'Khine Zar', NULL, 'Active', NULL, '2026-06-08 05:30:00', '2026-06-08 05:30:00'),
(13, 'Zin Me Yu Lay', NULL, 'Active', NULL, '2026-06-08 05:30:00', '2026-06-08 05:30:50'),
(16, 'Kaung Min Thant', NULL, 'Active', NULL, '2026-06-15 09:44:34', '2026-06-15 09:44:34'),
(17, 'Win Kyaw Aung', NULL, 'Active', NULL, '2026-06-15 09:44:34', '2026-06-15 09:44:34'),
(21, 'Testingsdf', '09281314713', 'Active', 'test', '2026-07-21 10:31:05', '2026-07-21 10:31:05'),
(22, 'test', '0012398`', 'Active', '', '2026-07-23 04:38:54', '2026-07-23 04:38:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_admins_username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bookings_car` (`car_id`),
  ADD KEY `fk_bookings_driver` (`driver_id`),
  ADD KEY `idx_bookings_operator_id` (`operator_id`),
  ADD KEY `idx_bookings_secondary_car_id` (`secondary_car_id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_no` (`plate_no`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_no` (`license_no`);

--
-- Indexes for table `monthly_cars`
--
ALTER TABLE `monthly_cars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_no` (`plate_no`);

--
-- Indexes for table `monthly_contracts`
--
ALTER TABLE `monthly_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contract_no` (`contract_no`),
  ADD KEY `idx_monthly_contracts_car_id` (`car_id`),
  ADD KEY `idx_monthly_contracts_driver_id` (`driver_id`),
  ADD KEY `idx_monthly_contracts_status_end_date` (`status`,`end_date`),
  ADD KEY `idx_monthly_contracts_renewal_parent_id` (`renewal_parent_id`),
  ADD KEY `idx_monthly_contracts_operator_id` (`operator_id`),
  ADD KEY `fk_monthly_contract_customer` (`customer_id`);

--
-- Indexes for table `monthly_drivers`
--
ALTER TABLE `monthly_drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_no` (`license_no`);

--
-- Indexes for table `operators`
--
ALTER TABLE `operators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `full_name` (`full_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `monthly_cars`
--
ALTER TABLE `monthly_cars`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `monthly_contracts`
--
ALTER TABLE `monthly_contracts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `monthly_drivers`
--
ALTER TABLE `monthly_drivers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `operators`
--
ALTER TABLE `operators`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_car` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_operator` FOREIGN KEY (`operator_id`) REFERENCES `operators` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_secondary_car` FOREIGN KEY (`secondary_car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `monthly_contracts`
--
ALTER TABLE `monthly_contracts`
  ADD CONSTRAINT `fk_monthly_contract_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monthly_contracts_car` FOREIGN KEY (`car_id`) REFERENCES `monthly_cars` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monthly_contracts_driver` FOREIGN KEY (`driver_id`) REFERENCES `monthly_drivers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monthly_contracts_operator` FOREIGN KEY (`operator_id`) REFERENCES `operators` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monthly_contracts_parent` FOREIGN KEY (`renewal_parent_id`) REFERENCES `monthly_contracts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
