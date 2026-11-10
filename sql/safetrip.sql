-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 26, 2026 at 09:25 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `safetrip`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 3, 'logout', 'User logged out', '::1', '2026-04-22 19:17:08'),
(2, 3, 'login', 'User logged in', '::1', '2026-04-22 19:26:47'),
(3, 3, 'logout', 'User logged out', '::1', '2026-04-23 04:52:41'),
(4, 3, 'login', 'User logged in', '::1', '2026-04-23 04:52:50'),
(5, 3, 'logout', 'User logged out', '::1', '2026-04-23 19:20:47'),
(6, 3, 'login', 'User logged in', '::1', '2026-04-23 19:27:48'),
(7, 3, 'logout', 'User logged out', '::1', '2026-04-23 19:29:04'),
(8, 1, 'login', 'User logged in', '::1', '2026-04-25 16:13:12'),
(9, 1, 'logout', 'User logged out', '::1', '2026-04-25 16:22:34'),
(10, 3, 'login', 'User logged in', '::1', '2026-04-25 16:23:26'),
(11, 3, 'login', 'User logged in', '::1', '2026-04-25 16:24:05'),
(12, 3, 'logout', 'User logged out', '::1', '2026-04-26 05:50:02'),
(13, 1, 'login', 'User logged in', '::1', '2026-04-26 05:51:28'),
(14, 1, 'logout', 'User logged out', '::1', '2026-04-26 06:01:19'),
(15, 1, 'login', 'User logged in', '::1', '2026-04-26 06:18:38'),
(16, 1, 'logout', 'User logged out', '::1', '2026-04-26 07:20:55'),
(17, 1, 'login', 'User logged in', '::1', '2026-04-26 07:21:10'),
(18, 3, 'login', 'User logged in', '::1', '2026-04-26 07:21:19');

-- --------------------------------------------------------

--
-- Table structure for table `blocked_users`
--

CREATE TABLE `blocked_users` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `blocked_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `checkin_settings`
--

CREATE TABLE `checkin_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `auto_checkin_enabled` tinyint(1) DEFAULT 1,
  `checkin_interval_minutes` int(11) DEFAULT 30,
  `reminder_before_minutes` int(11) DEFAULT 5,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emergency_contacts`
--

CREATE TABLE `emergency_contacts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contact_name` varchar(100) NOT NULL,
  `contact_phone` varchar(15) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `escort_sessions`
--

CREATE TABLE `escort_sessions` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `escort_code` varchar(32) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `started_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `last_location_lat` decimal(10,8) DEFAULT NULL,
  `last_location_lng` decimal(11,8) DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL,
  `view_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_splits`
--

CREATE TABLE `expense_splits` (
  `id` int(11) NOT NULL,
  `expense_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `settled` tinyint(1) DEFAULT 0,
  `settled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorite_routes`
--

CREATE TABLE `favorite_routes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `from_location` varchar(255) NOT NULL,
  `to_location` varchar(255) NOT NULL,
  `transport_mode` varchar(50) DEFAULT 'train',
  `usage_count` int(11) DEFAULT 1,
  `last_used` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `join_requests`
--

CREATE TABLE `join_requests` (
  `id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_locations`
--

CREATE TABLE `live_locations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `trip_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `user_id`, `email`, `ip`, `attempt_time`, `success`) VALUES
(1, NULL, 'preetam0032@gmail.com', '::1', '2026-04-22 18:17:33', 0),
(2, NULL, 'preetam0032@gmail.com', '::1', '2026-04-22 18:17:47', 0),
(3, NULL, 'preetam0032@gmail.com', '::1', '2026-04-22 18:18:00', 0),
(4, NULL, 'preetam0032@gmail.com', '::1', '2026-04-22 18:18:31', 0),
(5, NULL, 'preetam0032@gmail.com', '::1', '2026-04-22 18:18:54', 0),
(6, NULL, 'preetam0032@gmail.com', '::1', '2026-04-22 18:21:37', 0),
(7, NULL, 'preetam0032@gmail.com', '::1', '2026-04-22 18:21:47', 0),
(8, NULL, 'preetam0032@gmail.com', '::1', '2026-04-22 18:21:55', 0),
(9, 3, NULL, '::1', '2026-04-22 18:24:37', 1),
(10, 3, NULL, '::1', '2026-04-22 19:26:47', 1),
(11, 3, NULL, '::1', '2026-04-23 04:52:50', 1),
(12, 3, NULL, '::1', '2026-04-23 19:27:48', 1),
(13, NULL, 'lionking8052@gmail.com', '::1', '2026-04-25 11:07:34', 0),
(14, 1, NULL, '::1', '2026-04-25 16:13:12', 1),
(15, NULL, 'preetam0032@gmail.com', '::1', '2026-04-25 16:23:14', 0),
(16, 3, NULL, '::1', '2026-04-25 16:23:26', 1),
(17, NULL, 'preetam0032@gmail.com', '::1', '2026-04-25 16:23:39', 0),
(18, 3, NULL, '::1', '2026-04-25 16:24:05', 1),
(19, 1, NULL, '::1', '2026-04-26 05:51:28', 1),
(20, 1, NULL, '::1', '2026-04-26 06:18:38', 1),
(21, NULL, 'preetam0032@gamil.com', '::1', '2026-04-26 07:20:15', 0),
(22, NULL, 'preetam0032@gmail.com', '::1', '2026-04-26 07:20:39', 0),
(23, 1, NULL, '::1', '2026-04-26 07:21:10', 1),
(24, 3, NULL, '::1', '2026-04-26 07:21:19', 1);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('join_request','request_accepted','emergency','system') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `related_id`, `created_at`) VALUES
(2, 3, 'Account Verified', 'Your account has been verified! You can now create and join trips.', 'system', 1, NULL, '2026-04-21 18:55:42'),
(3, 801, 'Account Verified', 'Your account has been verified! You can now create and join trips.', 'system', 0, NULL, '2026-04-25 16:13:29');

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `id` int(11) NOT NULL,
  `referrer_id` int(11) NOT NULL,
  `referred_email` varchar(100) NOT NULL,
  `status` enum('pending','registered') DEFAULT 'pending',
  `points_earned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reported_user_id` int(11) NOT NULL,
  `trip_id` int(11) DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','resolved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `safety_alerts`
--

CREATE TABLE `safety_alerts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `alert_type` enum('emergency','route_deviation','missed_checkin','checkin_safe') DEFAULT 'emergency',
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_groups`
--

CREATE TABLE `travel_groups` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `group_code` varchar(10) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

CREATE TABLE `trips` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `from_location` varchar(255) NOT NULL,
  `to_location` varchar(255) NOT NULL,
  `travel_date` date NOT NULL,
  `travel_time` time NOT NULL,
  `seats_available` int(11) DEFAULT 1,
  `transport_mode` enum('train','bus','flight','car','bike','other') DEFAULT 'other',
  `distance_km` decimal(10,2) DEFAULT NULL,
  `duration_hours` decimal(5,2) DEFAULT NULL,
  `route_info` text DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `share_code` varchar(10) DEFAULT NULL,
  `is_shared` tinyint(1) NOT NULL DEFAULT 0,
  `share_view_count` int(11) DEFAULT 0,
  `escort_enabled` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trips`
--

INSERT INTO `trips` (`id`, `user_id`, `from_location`, `to_location`, `travel_date`, `travel_time`, `seats_available`, `transport_mode`, `distance_km`, `duration_hours`, `route_info`, `status`, `created_at`, `share_code`, `is_shared`, `share_view_count`, `escort_enabled`) VALUES
(167, 596, 'Delhi', 'Mumbai', '2026-05-01', '08:00:00', 3, 'flight', 1152.50, 2.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP001', 0, 0, 0),
(168, 597, 'Mumbai', 'Bangalore', '2026-05-02', '06:30:00', 2, 'flight', 980.00, 1.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP002', 0, 0, 0),
(169, 598, 'Delhi', 'Jaipur', '2026-05-03', '09:00:00', 4, 'car', 280.00, 5.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP003', 0, 0, 0),
(170, 599, 'Bangalore', 'Chennai', '2026-05-04', '07:00:00', 2, 'train', 350.00, 4.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP004', 0, 0, 0),
(171, 600, 'Kolkata', 'Patna', '2026-05-05', '10:00:00', 3, 'bus', 320.00, 6.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP005', 0, 0, 0),
(172, 601, 'Hyderabad', 'Goa', '2026-05-06', '05:00:00', 2, 'flight', 450.00, 1.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP006', 0, 0, 0),
(173, 602, 'Pune', 'Mumbai', '2026-05-01', '07:30:00', 4, 'bus', 150.00, 2.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP007', 0, 0, 0),
(174, 603, 'Chennai', 'Coimbatore', '2026-05-02', '06:00:00', 3, 'train', 420.00, 5.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP008', 0, 0, 0),
(175, 604, 'Delhi', 'Agra', '2026-05-03', '08:00:00', 2, 'car', 210.00, 3.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP009', 0, 0, 0),
(176, 605, 'Ahmedabad', 'Surat', '2026-05-04', '09:30:00', 3, 'bus', 265.00, 4.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP010', 0, 0, 0),
(177, 606, 'Bangalore', 'Mysore', '2026-05-05', '07:00:00', 4, 'car', 140.00, 2.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP011', 0, 0, 0),
(178, 607, 'Kolkata', 'Durgapur', '2026-05-06', '06:00:00', 2, 'train', 150.00, 2.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP012', 0, 0, 0),
(179, 608, 'Mumbai', 'Pune', '2026-05-01', '05:30:00', 3, 'bus', 150.00, 2.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP013', 0, 0, 0),
(180, 609, 'Delhi', 'Chandigarh', '2026-05-02', '08:00:00', 2, 'flight', 250.00, 1.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP014', 0, 0, 0),
(181, 610, 'Hyderabad', 'Bangalore', '2026-05-03', '07:00:00', 3, 'flight', 570.00, 1.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP015', 0, 0, 0),
(182, 611, 'Jaipur', 'Udaipur', '2026-05-04', '09:00:00', 4, 'car', 250.00, 4.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP016', 0, 0, 0),
(183, 612, 'Chennai', 'Pondicherry', '2026-05-05', '10:00:00', 2, 'car', 120.00, 2.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP017', 0, 0, 0),
(184, 613, 'Kolkata', 'Siliguri', '2026-05-06', '05:00:00', 3, 'train', 400.00, 6.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP018', 0, 0, 0),
(185, 614, 'Pune', 'Nashik', '2026-05-01', '06:30:00', 2, 'car', 210.00, 3.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP019', 0, 0, 0),
(186, 615, 'Bangalore', 'Coorg', '2026-05-02', '07:30:00', 4, 'car', 270.00, 4.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP020', 0, 0, 0),
(187, 616, 'Delhi', 'Lucknow', '2026-05-03', '08:00:00', 3, 'flight', 420.00, 1.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP021', 0, 0, 0),
(188, 617, 'Mumbai', 'Ahmedabad', '2026-05-04', '06:00:00', 2, 'flight', 530.00, 1.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP022', 0, 0, 0),
(189, 618, 'Ahmedabad', 'Jaipur', '2026-05-05', '09:00:00', 3, 'bus', 520.00, 8.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP023', 0, 0, 0),
(190, 619, 'Kolkata', 'Bhubaneswar', '2026-05-06', '07:00:00', 2, 'train', 440.00, 5.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP024', 0, 0, 0),
(191, 620, 'Hyderabad', 'Vijayawada', '2026-05-01', '05:30:00', 3, 'bus', 280.00, 5.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP025', 0, 0, 0),
(192, 621, 'Pune', 'Nagpur', '2026-05-02', '08:00:00', 2, 'bus', 450.00, 7.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP026', 0, 0, 0),
(193, 622, 'Bangalore', 'Chennai', '2026-05-03', '06:30:00', 4, 'flight', 350.00, 1.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP027', 0, 0, 0),
(194, 623, 'Delhi', 'Dehradun', '2026-05-04', '07:00:00', 2, 'car', 210.00, 5.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP028', 0, 0, 0),
(195, 624, 'Chennai', 'Thiruvananthapuram', '2026-05-05', '09:30:00', 3, 'train', 655.00, 8.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP029', 0, 0, 0),
(196, 625, 'Jaipur', 'Jodhpur', '2026-05-06', '06:00:00', 2, 'car', 350.00, 5.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP030', 0, 0, 0),
(197, 626, 'Mumbai', 'Surat', '2026-05-01', '05:00:00', 4, 'bus', 300.00, 4.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP031', 0, 0, 0),
(198, 627, 'Kolkata', 'Ranchi', '2026-05-02', '08:30:00', 3, 'train', 380.00, 5.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP032', 0, 0, 0),
(199, 628, 'Hyderabad', 'Tirupati', '2026-05-03', '06:00:00', 2, 'bus', 550.00, 8.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP033', 0, 0, 0),
(200, 629, 'Pune', 'Mumbai', '2026-05-04', '07:30:00', 3, 'train', 150.00, 2.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP034', 0, 0, 0),
(201, 630, 'Bangalore', 'Hampi', '2026-05-05', '05:30:00', 2, 'car', 350.00, 5.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP035', 0, 0, 0),
(202, 631, 'Delhi', 'Shimla', '2026-05-06', '08:00:00', 3, 'car', 360.00, 8.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP036', 0, 0, 0),
(203, 632, 'Ahmedabad', 'Udaipur', '2026-05-01', '09:00:00', 4, 'car', 260.00, 4.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP037', 0, 0, 0),
(204, 633, 'Chennai', 'Madurai', '2026-05-02', '06:30:00', 2, 'train', 440.00, 5.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP038', 0, 0, 0),
(205, 634, 'Mumbai', 'Nashik', '2026-05-03', '07:00:00', 3, 'bus', 170.00, 2.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP039', 0, 0, 0),
(206, 635, 'Kolkata', 'Shantiniketan', '2026-05-04', '08:00:00', 2, 'train', 160.00, 2.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP040', 0, 0, 0),
(207, 636, 'Jaipur', 'Ajmer', '2026-05-05', '10:00:00', 4, 'car', 130.00, 2.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP041', 0, 0, 0),
(208, 637, 'Hyderabad', 'Warangal', '2026-05-06', '06:00:00', 2, 'bus', 250.00, 5.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP042', 0, 0, 0),
(209, 638, 'Pune', 'Kolhapur', '2026-05-01', '09:30:00', 3, 'car', 230.00, 3.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP043', 0, 0, 0),
(210, 639, 'Bangalore', 'Mangalore', '2026-05-02', '07:00:00', 2, 'bus', 350.00, 6.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP044', 0, 0, 0),
(211, 640, 'Delhi', 'Varanasi', '2026-05-03', '05:30:00', 3, 'flight', 680.00, 1.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP045', 0, 0, 0),
(212, 641, 'Chennai', 'Bangalore', '2026-05-04', '08:00:00', 4, 'flight', 350.00, 1.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP046', 0, 0, 0),
(213, 642, 'Ahmedabad', 'Rajkot', '2026-05-05', '06:30:00', 2, 'car', 220.00, 3.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP047', 0, 0, 0),
(214, 643, 'Kolkata', 'Puri', '2026-05-06', '07:30:00', 3, 'train', 500.00, 7.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP048', 0, 0, 0),
(215, 644, 'Mumbai', 'Goa', '2026-05-01', '05:00:00', 2, 'flight', 420.00, 1.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP049', 0, 0, 0),
(216, 645, 'Hyderabad', 'Secunderabad', '2026-05-02', '09:00:00', 3, 'bus', 15.00, 0.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP050', 0, 0, 0),
(217, 646, 'Pune', 'Shirdi', '2026-05-03', '06:00:00', 4, 'car', 180.00, 3.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP051', 0, 0, 0),
(218, 647, 'Bangalore', 'Ooty', '2026-05-04', '07:30:00', 2, 'car', 280.00, 5.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP052', 0, 0, 0),
(219, 648, 'Delhi', 'Kolkata', '2026-05-05', '08:00:00', 3, 'flight', 1480.00, 2.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP053', 0, 0, 0),
(220, 649, 'Chennai', 'Kanyakumari', '2026-05-06', '05:30:00', 2, 'train', 680.00, 10.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP054', 0, 0, 0),
(221, 650, 'Jaipur', 'Bikaner', '2026-05-01', '09:00:00', 3, 'car', 330.00, 5.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP055', 0, 0, 0),
(222, 651, 'Mumbai', 'Ahmedabad', '2026-05-02', '06:30:00', 4, 'flight', 530.00, 1.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP056', 0, 0, 0),
(223, 652, 'Kolkata', 'Bardhaman', '2026-05-03', '08:00:00', 2, 'train', 100.00, 1.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP057', 0, 0, 0),
(224, 653, 'Hyderabad', 'Guntur', '2026-05-04', '07:00:00', 3, 'bus', 320.00, 6.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP058', 0, 0, 0),
(225, 654, 'Pune', 'Aurangabad', '2026-05-05', '05:00:00', 2, 'car', 250.00, 4.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP059', 0, 0, 0),
(226, 655, 'Bangalore', 'Tumkur', '2026-05-06', '09:30:00', 3, 'bus', 400.00, 7.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP060', 0, 0, 0),
(227, 656, 'Delhi', 'Gurgaon', '2026-05-01', '07:30:00', 4, 'car', 60.00, 1.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP061', 0, 0, 0),
(228, 657, 'Chennai', 'Tiruchirappalli', '2026-05-02', '06:00:00', 2, 'train', 320.00, 4.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP062', 0, 0, 0),
(229, 658, 'Ahmedabad', 'Vadodara', '2026-05-03', '08:00:00', 3, 'car', 110.00, 2.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP063', 0, 0, 0),
(230, 659, 'Kolkata', 'Hooghly', '2026-05-04', '07:00:00', 2, 'train', 40.00, 1.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP064', 0, 0, 0),
(231, 660, 'Mumbai', 'Lonavala', '2026-05-05', '06:30:00', 3, 'car', 120.00, 2.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP065', 0, 0, 0),
(232, 661, 'Hyderabad', 'Kochi', '2026-05-06', '09:00:00', 2, 'flight', 650.00, 1.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP066', 0, 0, 0),
(233, 662, 'Pune', 'Mahabaleshwar', '2026-05-01', '08:00:00', 4, 'car', 120.00, 3.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP067', 0, 0, 0),
(234, 663, 'Delhi', 'Noida', '2026-05-02', '07:30:00', 2, 'car', 40.00, 1.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP068', 0, 0, 0),
(235, 664, 'Chennai', 'Vellore', '2026-05-03', '06:00:00', 3, 'train', 135.00, 2.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP069', 0, 0, 0),
(236, 665, 'Jaipur', 'Pushkar', '2026-05-04', '09:30:00', 2, 'car', 150.00, 2.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP070', 0, 0, 0),
(237, 666, 'Mumbai', 'Thane', '2026-05-05', '08:00:00', 4, 'bus', 280.00, 5.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP071', 0, 0, 0),
(238, 667, 'Kolkata', 'Howrah', '2026-05-06', '07:00:00', 2, 'train', 10.00, 0.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP072', 0, 0, 0),
(239, 668, 'Hyderabad', 'Nagpur', '2026-05-01', '06:30:00', 3, 'bus', 180.00, 4.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP073', 0, 0, 0),
(240, 669, 'Pune', 'Satara', '2026-05-02', '05:00:00', 2, 'car', 320.00, 5.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP074', 0, 0, 0),
(241, 670, 'Bangalore', 'Shimoga', '2026-05-03', '09:00:00', 3, 'bus', 270.00, 5.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP075', 0, 0, 0),
(242, 671, 'Delhi', 'Faridabad', '2026-05-04', '08:00:00', 2, 'car', 30.00, 1.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP076', 0, 0, 0),
(243, 672, 'Chennai', 'Salem', '2026-05-05', '07:30:00', 3, 'train', 340.00, 4.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP077', 0, 0, 0),
(244, 673, 'Ahmedabad', 'Bharuch', '2026-05-06', '06:00:00', 4, 'car', 150.00, 2.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP078', 0, 0, 0),
(245, 674, 'Kolkata', 'Midnapur', '2026-05-01', '09:30:00', 2, 'train', 130.00, 2.00, NULL, 'active', '2026-04-23 18:26:01', 'TRIP079', 0, 0, 0),
(246, 675, 'Mumbai', 'Ulhasnagar', '2026-05-02', '08:00:00', 3, 'car', 55.00, 1.50, NULL, 'active', '2026-04-23 18:26:01', 'TRIP080', 0, 0, 0),
(247, 596, 'Delhi', 'Mumbai', '2026-05-01', '08:00:00', 3, 'flight', 1152.50, 2.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP001', 0, 0, 0),
(250, 599, 'Bangalore', 'Chennai', '2026-05-04', '07:00:00', 2, 'train', 350.00, 4.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP004', 0, 0, 0),
(251, 600, 'Kolkata', 'Patna', '2026-05-05', '10:00:00', 3, 'bus', 320.00, 6.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP005', 0, 0, 0),
(252, 601, 'Hyderabad', 'Goa', '2026-05-06', '05:00:00', 2, 'flight', 450.00, 1.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP006', 0, 0, 0),
(253, 602, 'Pune', 'Mumbai', '2026-05-01', '07:30:00', 4, 'bus', 150.00, 2.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP007', 0, 0, 0),
(254, 603, 'Chennai', 'Coimbatore', '2026-05-02', '06:00:00', 3, 'train', 420.00, 5.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP008', 0, 0, 0),
(255, 604, 'Delhi', 'Agra', '2026-05-03', '08:00:00', 2, 'car', 210.00, 3.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP009', 0, 0, 0),
(256, 605, 'Ahmedabad', 'Surat', '2026-05-04', '09:30:00', 3, 'bus', 265.00, 4.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP010', 0, 0, 0),
(257, 606, 'Bangalore', 'Mysore', '2026-05-05', '07:00:00', 4, 'car', 140.00, 2.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP011', 0, 0, 0),
(258, 607, 'Kolkata', 'Durgapur', '2026-05-06', '06:00:00', 2, 'train', 150.00, 2.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP012', 0, 0, 0),
(259, 608, 'Mumbai', 'Pune', '2026-05-01', '05:30:00', 3, 'bus', 150.00, 2.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP013', 0, 0, 0),
(260, 609, 'Delhi', 'Chandigarh', '2026-05-02', '08:00:00', 2, 'flight', 250.00, 1.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP014', 0, 0, 0),
(261, 610, 'Hyderabad', 'Bangalore', '2026-05-03', '07:00:00', 3, 'flight', 570.00, 1.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP015', 0, 0, 0),
(262, 611, 'Jaipur', 'Udaipur', '2026-05-04', '09:00:00', 4, 'car', 250.00, 4.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP016', 0, 0, 0),
(263, 612, 'Chennai', 'Pondicherry', '2026-05-05', '10:00:00', 2, 'car', 120.00, 2.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP017', 0, 0, 0),
(264, 613, 'Kolkata', 'Siliguri', '2026-05-06', '05:00:00', 3, 'train', 400.00, 6.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP018', 0, 0, 0),
(265, 614, 'Pune', 'Nashik', '2026-05-01', '06:30:00', 2, 'car', 210.00, 3.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP019', 0, 0, 0),
(266, 615, 'Bangalore', 'Coorg', '2026-05-02', '07:30:00', 4, 'car', 270.00, 4.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP020', 0, 0, 0),
(267, 616, 'Delhi', 'Lucknow', '2026-05-03', '08:00:00', 3, 'flight', 420.00, 1.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP021', 0, 0, 0),
(268, 617, 'Mumbai', 'Ahmedabad', '2026-05-04', '06:00:00', 2, 'flight', 530.00, 1.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP022', 0, 0, 0),
(269, 618, 'Ahmedabad', 'Jaipur', '2026-05-05', '09:00:00', 3, 'bus', 520.00, 8.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP023', 0, 0, 0),
(270, 619, 'Kolkata', 'Bhubaneswar', '2026-05-06', '07:00:00', 2, 'train', 440.00, 5.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP024', 0, 0, 0),
(271, 620, 'Hyderabad', 'Vijayawada', '2026-05-01', '05:30:00', 3, 'bus', 280.00, 5.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP025', 0, 0, 0),
(272, 621, 'Pune', 'Nagpur', '2026-05-02', '08:00:00', 2, 'bus', 450.00, 7.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP026', 0, 0, 0),
(273, 622, 'Bangalore', 'Chennai', '2026-05-03', '06:30:00', 4, 'flight', 350.00, 1.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP027', 0, 0, 0),
(274, 623, 'Delhi', 'Dehradun', '2026-05-04', '07:00:00', 2, 'car', 210.00, 5.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP028', 0, 0, 0),
(275, 624, 'Chennai', 'Thiruvananthapuram', '2026-05-05', '09:30:00', 3, 'train', 655.00, 8.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP029', 0, 0, 0),
(276, 625, 'Jaipur', 'Jodhpur', '2026-05-06', '06:00:00', 2, 'car', 350.00, 5.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP030', 0, 0, 0),
(277, 626, 'Mumbai', 'Surat', '2026-05-01', '05:00:00', 4, 'bus', 300.00, 4.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP031', 0, 0, 0),
(278, 627, 'Kolkata', 'Ranchi', '2026-05-02', '08:30:00', 3, 'train', 380.00, 5.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP032', 0, 0, 0),
(279, 628, 'Hyderabad', 'Tirupati', '2026-05-03', '06:00:00', 2, 'bus', 550.00, 8.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP033', 0, 0, 0),
(280, 629, 'Pune', 'Mumbai', '2026-05-04', '07:30:00', 3, 'train', 150.00, 2.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP034', 0, 0, 0),
(281, 630, 'Bangalore', 'Hampi', '2026-05-05', '05:30:00', 2, 'car', 350.00, 5.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP035', 0, 0, 0),
(282, 631, 'Delhi', 'Shimla', '2026-05-06', '08:00:00', 3, 'car', 360.00, 8.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP036', 0, 0, 0),
(283, 632, 'Ahmedabad', 'Udaipur', '2026-05-01', '09:00:00', 4, 'car', 260.00, 4.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP037', 0, 0, 0),
(284, 633, 'Chennai', 'Madurai', '2026-05-02', '06:30:00', 2, 'train', 440.00, 5.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP038', 0, 0, 0),
(285, 634, 'Mumbai', 'Nashik', '2026-05-03', '07:00:00', 3, 'bus', 170.00, 2.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP039', 0, 0, 0),
(286, 635, 'Kolkata', 'Shantiniketan', '2026-05-04', '08:00:00', 2, 'train', 160.00, 2.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP040', 0, 0, 0),
(287, 636, 'Jaipur', 'Ajmer', '2026-05-05', '10:00:00', 4, 'car', 130.00, 2.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP041', 0, 0, 0),
(288, 637, 'Hyderabad', 'Warangal', '2026-05-06', '06:00:00', 2, 'bus', 250.00, 5.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP042', 0, 0, 0),
(289, 638, 'Pune', 'Kolhapur', '2026-05-01', '09:30:00', 3, 'car', 230.00, 3.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP043', 0, 0, 0),
(290, 639, 'Bangalore', 'Mangalore', '2026-05-02', '07:00:00', 2, 'bus', 350.00, 6.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP044', 0, 0, 0),
(291, 640, 'Delhi', 'Varanasi', '2026-05-03', '05:30:00', 3, 'flight', 680.00, 1.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP045', 0, 0, 0),
(292, 641, 'Chennai', 'Bangalore', '2026-05-04', '08:00:00', 4, 'flight', 350.00, 1.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP046', 0, 0, 0),
(293, 642, 'Ahmedabad', 'Rajkot', '2026-05-05', '06:30:00', 2, 'car', 220.00, 3.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP047', 0, 0, 0),
(294, 643, 'Kolkata', 'Puri', '2026-05-06', '07:30:00', 3, 'train', 500.00, 7.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP048', 0, 0, 0),
(295, 644, 'Mumbai', 'Goa', '2026-05-01', '05:00:00', 2, 'flight', 420.00, 1.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP049', 0, 0, 0),
(296, 645, 'Hyderabad', 'Secunderabad', '2026-05-02', '09:00:00', 3, 'bus', 15.00, 0.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP050', 0, 0, 0),
(297, 646, 'Pune', 'Shirdi', '2026-05-03', '06:00:00', 4, 'car', 180.00, 3.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP051', 0, 0, 0),
(298, 647, 'Bangalore', 'Ooty', '2026-05-04', '07:30:00', 2, 'car', 280.00, 5.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP052', 0, 0, 0),
(299, 648, 'Delhi', 'Kolkata', '2026-05-05', '08:00:00', 3, 'flight', 1480.00, 2.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP053', 0, 0, 0),
(300, 649, 'Chennai', 'Kanyakumari', '2026-05-06', '05:30:00', 2, 'train', 680.00, 10.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP054', 0, 0, 0),
(301, 650, 'Jaipur', 'Bikaner', '2026-05-01', '09:00:00', 3, 'car', 330.00, 5.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP055', 0, 0, 0),
(302, 651, 'Mumbai', 'Ahmedabad', '2026-05-02', '06:30:00', 4, 'flight', 530.00, 1.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP056', 0, 0, 0),
(303, 652, 'Kolkata', 'Bardhaman', '2026-05-03', '08:00:00', 2, 'train', 100.00, 1.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP057', 0, 0, 0),
(304, 653, 'Hyderabad', 'Guntur', '2026-05-04', '07:00:00', 3, 'bus', 320.00, 6.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP058', 0, 0, 0),
(305, 654, 'Pune', 'Aurangabad', '2026-05-05', '05:00:00', 2, 'car', 250.00, 4.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP059', 0, 0, 0),
(306, 655, 'Bangalore', 'Tumkur', '2026-05-06', '09:30:00', 3, 'bus', 400.00, 7.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP060', 0, 0, 0),
(307, 656, 'Delhi', 'Gurgaon', '2026-05-01', '07:30:00', 4, 'car', 60.00, 1.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP061', 0, 0, 0),
(308, 657, 'Chennai', 'Tiruchirappalli', '2026-05-02', '06:00:00', 2, 'train', 320.00, 4.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP062', 0, 0, 0),
(309, 658, 'Ahmedabad', 'Vadodara', '2026-05-03', '08:00:00', 3, 'car', 110.00, 2.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP063', 0, 0, 0),
(310, 659, 'Kolkata', 'Hooghly', '2026-05-04', '07:00:00', 2, 'train', 40.00, 1.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP064', 0, 0, 0),
(311, 660, 'Mumbai', 'Lonavala', '2026-05-05', '06:30:00', 3, 'car', 120.00, 2.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP065', 0, 0, 0),
(312, 661, 'Hyderabad', 'Kochi', '2026-05-06', '09:00:00', 2, 'flight', 650.00, 1.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP066', 0, 0, 0),
(313, 662, 'Pune', 'Mahabaleshwar', '2026-05-01', '08:00:00', 4, 'car', 120.00, 3.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP067', 0, 0, 0),
(314, 663, 'Delhi', 'Noida', '2026-05-02', '07:30:00', 2, 'car', 40.00, 1.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP068', 0, 0, 0),
(315, 664, 'Chennai', 'Vellore', '2026-05-03', '06:00:00', 3, 'train', 135.00, 2.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP069', 0, 0, 0),
(316, 665, 'Jaipur', 'Pushkar', '2026-05-04', '09:30:00', 2, 'car', 150.00, 2.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP070', 0, 0, 0),
(317, 666, 'Mumbai', 'Thane', '2026-05-05', '08:00:00', 4, 'bus', 280.00, 5.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP071', 0, 0, 0),
(318, 667, 'Kolkata', 'Howrah', '2026-05-06', '07:00:00', 2, 'train', 10.00, 0.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP072', 0, 0, 0),
(319, 668, 'Hyderabad', 'Nagpur', '2026-05-01', '06:30:00', 3, 'bus', 180.00, 4.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP073', 0, 0, 0),
(320, 669, 'Pune', 'Satara', '2026-05-02', '05:00:00', 2, 'car', 320.00, 5.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP074', 0, 0, 0),
(321, 670, 'Bangalore', 'Shimoga', '2026-05-03', '09:00:00', 3, 'bus', 270.00, 5.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP075', 0, 0, 0),
(322, 671, 'Delhi', 'Faridabad', '2026-05-04', '08:00:00', 2, 'car', 30.00, 1.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP076', 0, 0, 0),
(323, 672, 'Chennai', 'Salem', '2026-05-05', '07:30:00', 3, 'train', 340.00, 4.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP077', 0, 0, 0),
(324, 673, 'Ahmedabad', 'Bharuch', '2026-05-06', '06:00:00', 4, 'car', 150.00, 2.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP078', 0, 0, 0),
(325, 674, 'Kolkata', 'Midnapur', '2026-05-01', '09:30:00', 2, 'train', 130.00, 2.00, NULL, 'active', '2026-04-23 18:26:46', 'TRIP079', 0, 0, 0),
(326, 675, 'Mumbai', 'Ulhasnagar', '2026-05-02', '08:00:00', 3, 'car', 55.00, 1.50, NULL, 'active', '2026-04-23 18:26:46', 'TRIP080', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `trip_checkins`
--

CREATE TABLE `trip_checkins` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `checkin_type` enum('start','periodic','manual','sos') DEFAULT 'manual',
  `status` enum('pending','checked_in','missed','escalated') DEFAULT 'pending',
  `scheduled_at` datetime DEFAULT NULL,
  `checked_in_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_expenses`
--

CREATE TABLE `trip_expenses` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `added_by` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` enum('fuel','toll','food','ticket','accommodation','other') DEFAULT 'other',
  `receipt_url` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_flags`
--

CREATE TABLE `trip_flags` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `flag_type` enum('suspicious_price','vague_details','multiple_flags','reported') DEFAULT 'suspicious_price',
  `severity` enum('low','medium','high','critical') DEFAULT 'low',
  `reason` text DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `status` enum('pending','reviewed','cleared','confirmed') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_ratings`
--

CREATE TABLE `trip_ratings` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `rater_id` int(11) NOT NULL,
  `rated_user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `age` int(11) DEFAULT NULL,
  `id_type` enum('aadhaar','pan','driving_license') NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `id_number_hash` char(64) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `trust_score` int(11) DEFAULT 50,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `last_seen` datetime DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_blocked` tinyint(1) DEFAULT 0,
  `otp` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `login_fail_count` int(11) NOT NULL DEFAULT 0,
  `login_locked_until` datetime DEFAULT NULL,
  `otp_fail_count` int(11) NOT NULL DEFAULT 0,
  `otp_locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `registration_ip` varchar(45) DEFAULT NULL,
  `is_profile_private` tinyint(1) DEFAULT 0,
  `delete_requested_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `session_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `gender`, `age`, `id_type`, `id_number`, `id_number_hash`, `verification_status`, `trust_score`, `is_online`, `last_seen`, `is_admin`, `is_blocked`, `otp`, `otp_expiry`, `reset_token`, `login_fail_count`, `login_locked_until`, `otp_fail_count`, `otp_locked_until`, `created_at`, `registration_ip`, `is_profile_private`, `delete_requested_at`, `last_login_at`, `session_token`) VALUES
(1, 'Admin', 'admin@safetrip.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9999999999', 'male', 30, 'aadhaar', '****N123', '5b40171489659251097e7790fc2f1892e2183a72546fe1df283d07865db9149c', 'verified', 100, 0, NULL, 1, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-15 07:13:11', NULL, 0, NULL, '2026-04-26 12:51:10', NULL),
(3, 'preetam kumar', 'preetam0032@gmail.com', '$2y$10$iRZohAZDgX79YWefibYV4O2/ysc8NIvC9rMcYJ.We7kqSlt9W7OQS', '8299769731', 'male', 23, 'aadhaar', '********4999', 'c17def9250cd87b8429cf43e4c0fc8e8184aa51789628282ffe612c2164758c7', 'verified', 70, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-21 18:54:24', NULL, 0, NULL, '2026-04-26 12:51:19', NULL),
(4, 'adya kumar', 'kharwarfamily89@gmail.com', '$2y$10$6IjHWxtnzmnount1L6CKSuX8xMvcckHds1brl5/yi23a0rYC37POS', '8299769733', 'female', 24, 'pan', '********4923', '270a9b47be795bbf24aed31acbce95b954edf8e09a89ace36e673e1652e96f1c', 'pending', 50, 0, NULL, 0, 0, '446655', '2026-04-22 23:17:58', NULL, 0, NULL, 0, NULL, '2026-04-22 17:37:58', NULL, 0, NULL, NULL, NULL),
(596, 'Rahul Sharma', 'rahul.sharma@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543210', 'male', 28, 'aadhaar', '********9012', '73220ea94379d74d86d94751d0189d5f', 'verified', 85, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(597, 'Priya Patel', 'priya.patel@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543211', 'female', 25, 'aadhaar', '********9013', '8e880ee27d486536201ec634c8407fcd', 'verified', 90, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(598, 'Amit Kumar', 'amit.kumar@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543212', 'male', 32, 'pan', '******234F', 'e781611acf3681dd03209a636a785bf6', 'verified', 78, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(599, 'Sneha Gupta', 'sneha.gupta@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543213', 'female', 24, 'aadhaar', '********9014', 'fcf46424949fc1bcef1b11856e3db489', 'verified', 92, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(600, 'Vikram Singh', 'vikram.singh@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543214', 'male', 35, 'driving_license', '********7890', 'a3f621a53e3b7573f0a745e091bdf71b', 'verified', 88, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(601, 'Anjali Reddy', 'anjali.reddy@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543215', 'female', 27, 'aadhaar', '********9015', 'b0b38d801ad93f9e8608837eaaa12fc9', 'verified', 82, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(602, 'Raj Malhotra', 'raj.malhotra@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543216', 'male', 30, 'pan', '******235F', '3ddd51cacc1297c6ec6bc0282adc8c71', 'verified', 75, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(603, 'Divya Krishnan', 'divya.krishnan@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543217', 'female', 26, 'aadhaar', '********9016', 'a3cc074d40cedb5f470200d9ba3bb93d', 'verified', 95, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(604, 'Sanjay Verma', 'sanjay.verma@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543218', 'male', 33, 'driving_license', '********7891', 'e3b6ae79e9f298bf01d2e017e6c1bd28', 'verified', 80, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(605, 'Meera Joshi', 'meera.joshi@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543219', 'female', 29, 'aadhaar', '********9017', 'a9a36d25c50ed8f67efd367c2044d2e9', 'verified', 87, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(606, 'Karan Shah', 'karan.shah@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543220', 'male', 31, 'pan', '******236F', 'eaca46a8e4c555e604274180cd3076d7', 'verified', 72, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(607, 'Pooja Iyer', 'pooja.iyer@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543221', 'female', 23, 'aadhaar', '********9018', 'd09fed239b1bd6439ae4ac30cdb3cdec', 'verified', 91, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(608, 'Arjun Nair', 'arjun.nair@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543222', 'male', 29, 'driving_license', '********7892', 'abcd332451c649a5f1f583335e0b4ece', 'verified', 84, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(609, 'Kavita Desai', 'kavita.desai@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543223', 'female', 28, 'aadhaar', '********9019', '372d1595e5e2930b40aaf6441efedef1', 'verified', 89, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(610, 'Deepak Choudhary', 'deepak.choudhary@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543224', 'male', 34, 'pan', '******237F', '3032c3a69a1f947620fbbafdc578d728', 'verified', 76, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(611, 'Richa Mishra', 'richa.mishra@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543225', 'female', 25, 'aadhaar', '********9020', '7b03d6bb5ccd527ebabcbdccefe3fb8d', 'verified', 93, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(612, 'Omar Khan', 'omar.khan@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543226', 'male', 27, 'driving_license', '********7893', '3c6e77b5555a376dc2941e1ef5d60590', 'verified', 81, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(613, 'Tara Singh', 'tara.singh@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543227', 'female', 26, 'aadhaar', '********9021', 'cc591952696c70fdba90ffed8edd880d', 'verified', 86, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(614, 'Aditya Rao', 'aditya.rao@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543228', 'male', 32, 'pan', '******238F', 'd03216d05033657b9ea8b42a341819f8', 'verified', 79, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(615, 'Nisha Bhatia', 'nisha.bhatia@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543229', 'female', 24, 'aadhaar', '********9022', 'f6e1e502e99fad383d20d46eaa8e06c4', 'verified', 94, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(616, 'Varun Tyagi', 'varun.tyagi@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543230', 'male', 28, 'driving_license', '********7894', '25476dcef7ffb14218029c583ec6b879', 'verified', 77, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(617, 'Riya Kapoor', 'riya.kapoor@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543231', 'female', 27, 'aadhaar', '********9023', '7534f951fc595c8c2ef1013ed47268a1', 'verified', 88, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(618, 'Manish Bansal', 'manish.bansal@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543232', 'male', 35, 'pan', '******239F', '4c543a9bb9dc922da2f8a859398ad0f2', 'verified', 73, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(619, 'Ananya Das', 'ananya.das@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543233', 'female', 23, 'aadhaar', '********9024', '80b5900b02fe71a29a745beecb13baf8', 'verified', 90, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(620, 'Soham Dutta', 'soham.dutta@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543234', 'male', 26, 'driving_license', '********7895', '7bde2c8aa719fbbcac2838d26593f4d6', 'verified', 83, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(621, 'Kiara Shah', 'kiara.shah@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543235', 'female', 25, 'aadhaar', '********9025', '126bd5b994c2afe27bd67ea52af2f8f6', 'verified', 91, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(622, 'Vineet Mehra', 'vineet.mehra@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543236', 'male', 31, 'pan', '******240F', '24fac207fa9e0ab5cef71f6eb2911a2f', 'verified', 74, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(623, 'Sanya Jain', 'sanya.jain@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543237', 'female', 26, 'aadhaar', '********9026', '9f532eb32135e74bfd854a0db7785d3b', 'verified', 87, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(624, 'Kabir Ahmed', 'kabir.ahmed@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543238', 'male', 29, 'driving_license', '********7896', '3cbf443878c7fa6df600eea52980819a', 'verified', 80, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(625, 'Aisha Qureshi', 'aisha.qureshi@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543239', 'female', 24, 'aadhaar', '********9027', 'c10c35912a146e244bfded5b18ab2e4f', 'verified', 92, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(626, 'Yashowardhan Pokhriyal', 'yashowardhan.pokhriyal@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543240', 'male', 33, 'pan', '******241F', 'bb513426c2d9ac77b212895421002dc5', 'verified', 71, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(627, 'Ira Trivedi', 'ira.trivedi@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543241', 'female', 25, 'aadhaar', '********9028', '11a345174db0385c3d9313f2c213fcac', 'verified', 89, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(628, 'Rohan Ghosh', 'rohan.ghosh@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543242', 'male', 27, 'driving_license', '********7897', '2113732591cecbbd9d07a8582d93d97e', 'verified', 82, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(629, 'Tanya Sen', 'tanya.sen@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543243', 'female', 28, 'aadhaar', '********9029', '5248c3c3e3d9606b7884c4e76cb62d36', 'verified', 85, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(630, 'Ishaan Saxena', 'ishaan.saxena@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543244', 'male', 30, 'pan', '******242F', '8a9759579570b56d2a0d5d5cd621fa18', 'verified', 78, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(631, 'Diya Khanna', 'diya.khanna@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543245', 'female', 23, 'aadhaar', '********9030', '724e07093e401ff36af21c657c61cad4', 'verified', 93, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(632, 'Angad Kumar', 'angad.kumar@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543246', 'male', 28, 'driving_license', '********7898', '38ad434969a33ea28b06c266613ea52d', 'verified', 81, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(633, 'Myra Reddy', 'myra.reddy@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543247', 'female', 26, 'aadhaar', '********9031', '4e3c712d89ffe87744db876bf8a06483', 'verified', 88, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(634, 'Arnav Chatterjee', 'arnav.chatterjee@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543248', 'male', 32, 'pan', '******243F', '199e284abc7a578fbda0f3d3c9ea8d74', 'verified', 75, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(635, 'Aditi Mukherjee', 'aditi.mukherjee@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543249', 'female', 25, 'aadhaar', '********9032', 'f36b3f7438e07079944fda0a1f84bdd5', 'verified', 90, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(636, 'Dev Malhotra', 'dev.malhotra@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543250', 'male', 27, 'driving_license', '********7899', '6ac24f373b41ed5a9f3065af5875222c', 'verified', 83, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(637, 'Rhea Kapoor', 'rhea.kapoor@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543251', 'female', 24, 'aadhaar', '********9033', 'cec681b0e41d5a9713b2b19f50714a07', 'verified', 86, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(638, 'Parth Patel', 'parth.patel@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543252', 'male', 31, 'pan', '******244F', '6a079a3f2f9016fbfa611d278e7badbd', 'verified', 72, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(639, 'Sara Ali', 'sara.ali@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543253', 'female', 27, 'aadhaar', '********9034', '04b174c6ec6b337f6122926aa96d0ff2', 'verified', 91, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(640, 'Akash Jain', 'akash.jain@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543254', 'male', 26, 'driving_license', '********7900', '1b0270d4f8d70d72b6ec0998be1b06b4', 'verified', 84, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(641, 'Maya Singh', 'maya.singh@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543255', 'female', 25, 'aadhaar', '********9035', '36d1ef3c542ffeab3c30dafcba972d7a', 'verified', 89, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(642, 'Veer Sharma', 'veer.sharma@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543256', 'male', 29, 'pan', '******245F', 'ff35cb1b93e16af4c27c795683f4c7f9', 'verified', 79, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(643, 'Radha Iyer', 'radha.iyer@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543257', 'female', 26, 'aadhaar', '********9036', 'bd4cc7eb86159bce494cac599a5fc665', 'verified', 87, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(644, 'Umang Joshi', 'umang.joshi@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543258', 'male', 28, 'driving_license', '********7901', '3ec255660488f0c5a50f7ca8ce1cf364', 'verified', 80, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(645, 'Anika Desai', 'anika.desai@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543259', 'female', 24, 'aadhaar', '********9037', '4419d41a82771cdcf320cb5386a274e7', 'verified', 94, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(646, 'Dhruv Shah', 'dhruv.shah@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543260', 'male', 30, 'pan', '******246F', '2e19523be1bbd79fe8d4fff3cc6dfe0a', 'verified', 73, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(647, 'Ishita Rao', 'ishita.rao@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543261', 'female', 25, 'aadhaar', '********9038', '9d2d113f44ab82669d690e254bd924f6', 'verified', 88, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(648, 'Jai Verma', 'jai.verma@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543262', 'male', 27, 'driving_license', '********7902', '6f1528eeadcc0d52dbf3be1d789f1b9f', 'verified', 81, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(649, 'Tanvi Mishra', 'tanvi.mishra@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543263', 'female', 26, 'aadhaar', '********9039', '5f3d238ab391ec971366c5cf8cc9621f', 'verified', 90, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(650, 'Vihaan Khan', 'vihaan.khan@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543264', 'male', 28, 'pan', '******247F', 'd28d0483e2c825be2e120132adcf2b7f', 'verified', 75, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(651, 'Zara Bhatia', 'zara.bhatia@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543265', 'female', 23, 'aadhaar', '********9040', 'c35e64d99810c05174c2d3f948407c04', 'verified', 92, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(652, 'Arin Choudhary', 'arin.choudhary@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543266', 'male', 26, 'driving_license', '********7903', '5af4aee6a3c47bb83c91f90e6e616e70', 'verified', 82, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(653, 'Safa Khan', 'safa.khan@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543267', 'female', 25, 'aadhaar', '********9041', '0c8098cf2d315495177203064017ae65', 'verified', 85, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(654, 'Reyansh Agarwal', 'reyansh.agarwal@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543268', 'male', 31, 'pan', '******248F', 'b7019c86c7668322042b6d0234a0ecb6', 'verified', 77, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(655, 'Navya Das', 'navya.das@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543269', 'female', 27, 'aadhaar', '********9042', '63254e647be77575153dca3e82c339cd', 'verified', 89, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(656, 'Tushar Reddy', 'tushar.reddy@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543270', 'male', 29, 'driving_license', '********7904', 'a5dc38e89f2566dce2b031cc610566b8', 'verified', 80, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(657, 'Riya Agarwal', 'riya.agarwal@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543271', 'female', 24, 'aadhaar', '********9043', '26a67d8227679f4ed222adca9799a53c', 'verified', 93, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(658, 'Atharv Mishra', 'atharv.mishra@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543272', 'male', 27, 'pan', '******249F', 'c3267dd62cfe1aee968d8b6b97eccbaa', 'verified', 74, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(659, 'Sia Nair', 'sia.nair@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543273', 'female', 26, 'aadhaar', '********9044', 'c752ed954a3981e6130131a840863c7e', 'verified', 86, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(660, 'Lakshya Grover', 'lakshya.grover@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543274', 'male', 30, 'driving_license', '********7905', '5be4f728722a808701c2396e9c65a404', 'verified', 78, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(661, 'Amaira Fatima', 'amaira.fatima@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543275', 'female', 25, 'aadhaar', '********9045', '9aaaddecd8bf57c1be5142683dfcc922', 'verified', 88, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(662, 'Adheer Joshi', 'adheer.joshi@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543276', 'male', 28, 'pan', '******250F', '2400e4a30c1bffbd3e5526c4c54f8aed', 'verified', 76, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(663, 'Anika Bose', 'anika.bose@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543277', 'female', 23, 'aadhaar', '********9046', '3a66175064e4afb1dcfe5f82b355b255', 'verified', 91, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(664, 'Keshav Narayanan', 'keshav.narayanan@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543278', 'male', 32, 'driving_license', '********7906', '761492f085b1f600d7b317b5fc29fce7', 'verified', 79, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(665, 'Ira Garg', 'ira.garg@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543279', 'female', 26, 'aadhaar', '********9047', '874f02b3331ce3467a4a3a9c7d66a91d', 'verified', 87, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(666, 'Raghav Sharma', 'raghav.sharma@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543280', 'male', 29, 'pan', '******251F', 'f5f23e3fc6a65a61383677630d7a1d26', 'verified', 81, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(667, 'Niyati Shah', 'niyati.shah@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543281', 'female', 25, 'aadhaar', '********9048', 'c07eb3d9433e130d1a81383c1a492785', 'verified', 90, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(668, 'Sahil Khan', 'sahil.khan@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543282', 'male', 27, 'driving_license', '********7907', '7dae975660cf889b323f29fa0dedeac1', 'verified', 83, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(669, 'Tiya Das', 'tiya.das@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543283', 'female', 24, 'aadhaar', '********9049', '29f3a0013619e7510e07f88e018f0b92', 'verified', 92, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(670, 'Uthhav Reddy', 'uthhav.reddy@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543284', 'male', 31, 'pan', '******252F', '65a423eb0bc9fee2bd44d2d8b1cdeecd', 'verified', 75, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(671, 'Avni Patel', 'avni.patel@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543285', 'female', 26, 'aadhaar', '********9050', 'bf245a75221c023fa01ba0e1f3a12820', 'verified', 85, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(672, 'Nirav Choudhary', 'nirav.choudhary@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543286', 'male', 28, 'driving_license', '********7908', '71f1e3ee79c1026b7d18262f4da4ec76', 'verified', 80, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(673, 'Mitali Jain', 'mitali.jain@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543287', 'female', 23, 'aadhaar', '********9051', '7687c2103cdd3310ea267969c14a55b5', 'verified', 93, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(674, 'Shaurya Singh', 'shaurya.singh@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543288', 'male', 26, 'pan', '******253F', 'df2466d203a7abba0ed3c36379d59685', 'verified', 77, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(675, 'Sakshi Gupta', 'sakshi.gupta@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543289', 'female', 27, 'aadhaar', '********9052', '0498652c411964779c5ce8fd12c8f5e4', 'verified', 86, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(676, 'Tarun Malhotra', 'tarun.malhotra@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543290', 'male', 30, 'driving_license', '********7909', '8052edf734d554154babfa4936d005a6', 'verified', 78, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(677, 'Khushi Sharma', 'khushi.sharma@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543291', 'female', 25, 'aadhaar', '********9053', '3474da4916cf74c35afa323b4d565678', 'verified', 89, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(678, 'Ayaan Bhat', 'ayaan.bhat@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543292', 'male', 28, 'pan', '******254F', '5490998e6e2205103ee1448ceaf77c48', 'verified', 82, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(679, 'Ayesha Begum', 'ayesha.begum@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543293', 'female', 24, 'aadhaar', '********9054', 'c0598904305508cfc05b9d641749d5f2', 'verified', 94, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(680, 'Maanav Kumar', 'maanav.kumar@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543294', 'male', 27, 'driving_license', '********7910', '9957f12c9b3092a3d15bbdaa44c5494e', 'verified', 79, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(681, 'Anchal Rao', 'anchal.rao@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543295', 'female', 26, 'aadhaar', '********9055', '5c363efbb53df5a1f952667ac871ee1d', 'verified', 88, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(682, 'Purvi Shah', 'purvi.shah@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543296', 'female', 23, 'aadhaar', '********9056', 'f3599e791975abf9af9e22b7c6fd1c53', 'verified', 90, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(683, 'Ujwal Desai', 'ujwal.desai@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543297', 'male', 29, 'pan', '******255F', '31030e7e50e1423b381d6bd30bae0672', 'verified', 74, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(684, 'Riddhi Yadav', 'riddhi.yadav@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543298', 'female', 25, 'aadhaar', '********9057', 'cfae4fdcd42286a486e029a4734fcabe', 'verified', 85, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(685, 'Arhaan Khan', 'arhaan.khan@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543299', 'male', 26, 'driving_license', '********7911', '8dc268048ca0af507f6603d5903a40be', 'verified', 81, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(686, 'Shriya Kapoor', 'shriya.kapoor@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543300', 'female', 24, 'aadhaar', '********9058', '26cc0d7615965f5a260c019ad94915cd', 'verified', 91, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(687, 'Vedant Rao', 'vedant.rao@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543301', 'male', 27, 'pan', '******256F', '60f4cc29490942fbcb14da5add6998f2', 'verified', 76, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(688, 'Manya Gupta', 'manya.gupta@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543302', 'female', 26, 'aadhaar', '********9059', 'eccd4a75a17592f8dd6e00835089bd7c', 'verified', 87, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(689, 'Kabir Malhotra', 'kabir.malhotra@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543303', 'male', 28, 'driving_license', '********7912', '09d13ba0afdbdc82efb0aafce762664d', 'verified', 78, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(690, 'Samara Fatima', 'samara.fatima@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543304', 'female', 25, 'aadhaar', '********9060', 'cba96d712b33380429b422ed501ea2a3', 'verified', 89, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(691, 'Vivaan Shah', 'vivaan.shah@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543305', 'male', 30, 'pan', '******257F', 'd97e5b2263754a7d5ba9d8a2b7b8cf23', 'verified', 75, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(692, 'Siya Agarwal', 'siya.agarwal@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543306', 'female', 23, 'aadhaar', '********9061', 'ef8b7ca75b4e1e17223a304c38e62660', 'verified', 92, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(693, 'Aaryan Singh', 'aaryan.singh@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543307', 'male', 27, 'driving_license', '********7913', 'abc9dcd92200d7818a5c49b93babe8c2', 'verified', 80, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(694, 'Adah Sharma', 'adah.sharma@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFG', '9876543308', 'female', 26, 'aadhaar', '********9062', '0f6efa6bf01f647e5425cb8cc57e6684', 'verified', 86, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-23 18:10:54', NULL, 0, NULL, NULL, NULL),
(796, 'Test User', 'testuser@example.com', '$2y$10$iRZohAZDgX79YWefibYV4O2/ysc8NIvC9rMcYJ.We7kqSlt9W7OQS', '8888888888', 'male', 25, 'aadhaar', '****1234', 'HASH123', 'verified', 50, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-25 11:59:34', NULL, 0, NULL, NULL, NULL),
(801, 'bhanu yadav', 'yadavbhanu800@gmail.com', '$2y$10$GIfaH44XYt6zYo.6wG.fUu/QpP3rK0fOfqhw5zaevP3tRMOM3GFum', '8318567139', 'male', 25, 'aadhaar', '********5672', 'c3a68da0694de19b2ac171d0607decc97234287820afc40ea9dd52770e32a6d7', 'verified', 70, 0, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, NULL, '2026-04-25 16:09:54', '::1', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_name` varchar(100) NOT NULL,
  `badge_icon` varchar(50) DEFAULT '?',
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_block` (`user_id`,`blocked_user_id`),
  ADD KEY `blocked_user_id` (`blocked_user_id`);

--
-- Indexes for table `checkin_settings`
--
ALTER TABLE `checkin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `escort_sessions`
--
ALTER TABLE `escort_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `escort_code` (`escort_code`),
  ADD KEY `idx_escort_code` (`escort_code`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_active` (`is_active`,`expires_at`);

--
-- Indexes for table `expense_splits`
--
ALTER TABLE `expense_splits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expense` (`expense_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `favorite_routes`
--
ALTER TABLE `favorite_routes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_route` (`user_id`,`from_location`,`to_location`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_member` (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `join_requests`
--
ALTER TABLE `join_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_request` (`from_user_id`,`to_user_id`,`trip_id`),
  ADD KEY `trip_id` (`trip_id`),
  ADD KEY `idx_join_requests_status` (`to_user_id`,`status`);

--
-- Indexes for table `live_locations`
--
ALTER TABLE `live_locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_location` (`user_id`),
  ADD KEY `trip_id` (`trip_id`),
  ADD KEY `idx_live_locations_user` (`user_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_messages_group` (`group_id`),
  ADD KEY `idx_messages_created` (`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `referrer_id` (`referrer_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `reported_user_id` (`reported_user_id`),
  ADD KEY `trip_id` (`trip_id`);

--
-- Indexes for table `safety_alerts`
--
ALTER TABLE `safety_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_safety_alerts_user` (`user_id`,`created_at`);

--
-- Indexes for table `travel_groups`
--
ALTER TABLE `travel_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_code` (`group_code`),
  ADD KEY `trip_id` (`trip_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trips_user` (`user_id`),
  ADD KEY `idx_trips_date` (`travel_date`),
  ADD KEY `idx_trips_location` (`from_location`,`to_location`),
  ADD KEY `idx_trips_status` (`status`);

--
-- Indexes for table `trip_checkins`
--
ALTER TABLE `trip_checkins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trip_user` (`trip_id`,`user_id`),
  ADD KEY `idx_scheduled` (`scheduled_at`,`status`);

--
-- Indexes for table `trip_expenses`
--
ALTER TABLE `trip_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trip` (`trip_id`),
  ADD KEY `idx_group` (`group_id`);

--
-- Indexes for table `trip_flags`
--
ALTER TABLE `trip_flags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trip` (`trip_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_severity` (`severity`);

--
-- Indexes for table `trip_ratings`
--
ALTER TABLE `trip_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rating` (`trip_id`,`rater_id`,`rated_user_id`),
  ADD KEY `rater_id` (`rater_id`),
  ADD KEY `rated_user_id` (`rated_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `unique_id_number_hash` (`id_number_hash`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `blocked_users`
--
ALTER TABLE `blocked_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `checkin_settings`
--
ALTER TABLE `checkin_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `escort_sessions`
--
ALTER TABLE `escort_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_splits`
--
ALTER TABLE `expense_splits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorite_routes`
--
ALTER TABLE `favorite_routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `join_requests`
--
ALTER TABLE `join_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `live_locations`
--
ALTER TABLE `live_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `safety_alerts`
--
ALTER TABLE `safety_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `travel_groups`
--
ALTER TABLE `travel_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=327;

--
-- AUTO_INCREMENT for table `trip_checkins`
--
ALTER TABLE `trip_checkins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trip_expenses`
--
ALTER TABLE `trip_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trip_flags`
--
ALTER TABLE `trip_flags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trip_ratings`
--
ALTER TABLE `trip_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=802;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD CONSTRAINT `blocked_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blocked_users_ibfk_2` FOREIGN KEY (`blocked_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD CONSTRAINT `emergency_contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `travel_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `join_requests`
--
ALTER TABLE `join_requests`
  ADD CONSTRAINT `join_requests_ibfk_1` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `join_requests_ibfk_2` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `join_requests_ibfk_3` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `live_locations`
--
ALTER TABLE `live_locations`
  ADD CONSTRAINT `live_locations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `live_locations_ibfk_2` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD CONSTRAINT `login_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `travel_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `safety_alerts`
--
ALTER TABLE `safety_alerts`
  ADD CONSTRAINT `safety_alerts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `travel_groups`
--
ALTER TABLE `travel_groups`
  ADD CONSTRAINT `travel_groups_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `travel_groups_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trips`
--
ALTER TABLE `trips`
  ADD CONSTRAINT `trips_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trip_ratings`
--
ALTER TABLE `trip_ratings`
  ADD CONSTRAINT `trip_ratings_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trip_ratings_ibfk_2` FOREIGN KEY (`rater_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trip_ratings_ibfk_3` FOREIGN KEY (`rated_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
