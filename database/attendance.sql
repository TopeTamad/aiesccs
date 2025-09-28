-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 28, 2025 at 12:36 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attendance`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `scan_time` datetime DEFAULT NULL,
  `status` enum('Present','Late','Absent','Signed Out') DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `subject_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `scan_time`, `status`, `subject_id`, `subject_name`) VALUES
(4, '220002405', '2025-07-15 18:13:07', 'Present', NULL, NULL),
(5, '220002405', '2025-07-15 18:13:07', 'Signed Out', NULL, NULL),
(6, '220002405', '2025-07-15 18:13:07', 'Signed Out', NULL, NULL),
(7, '220002405', '2025-07-15 18:13:10', 'Present', NULL, NULL),
(8, '220002405', '2025-07-15 18:13:12', 'Signed Out', NULL, NULL),
(9, '220002405', '2025-07-15 18:13:12', 'Present', NULL, NULL),
(10, '220002405', '2025-07-15 18:13:15', 'Signed Out', NULL, NULL),
(11, '220002405', '2025-07-15 12:20:21', 'Present', 1, 'Enterprize System'),
(12, '220002403', '2025-07-15 12:24:36', 'Present', 1, 'Enterprize System'),
(13, '220002403', '2025-07-15 12:24:36', 'Signed Out', 1, 'Enterprize System'),
(14, '220002403', '2025-07-15 12:24:51', 'Signed Out', 1, 'Enterprize System'),
(15, '220002403', '2025-07-15 12:24:54', 'Present', 1, 'Enterprize System'),
(16, '220002405', '2025-07-15 18:25:12', 'Present', NULL, NULL),
(17, '220002405', '2025-07-15 12:28:26', 'Signed Out', 1, 'Enterprize System'),
(18, '220002405', '2025-07-15 12:28:26', 'Present', 1, 'Enterprize System'),
(19, '220002403', '2025-07-15 12:29:00', 'Signed Out', 1, 'Enterprize System'),
(20, '220002403', '2025-07-15 12:29:00', 'Present', 1, 'Enterprize System'),
(21, '220002403', '2025-07-15 12:39:24', 'Present', 1, 'Enterprize System'),
(22, '220002403', '2025-07-15 18:39:46', 'Signed Out', NULL, NULL),
(23, '220002403', '2025-07-15 12:42:20', 'Signed Out', 1, 'Enterprize System'),
(24, '220002403', '2025-07-15 12:42:31', 'Present', 1, 'Enterprize System'),
(25, '220002403', '2025-07-15 18:42:53', 'Present', NULL, NULL),
(26, '220002403', '2025-07-15 12:48:26', 'Signed Out', 1, 'Enterprize System'),
(27, '220002403', '2025-07-15 12:58:06', 'Present', 1, 'Enterprize System'),
(28, '220002403', '2025-07-15 13:00:04', 'Signed Out', 1, 'Enterprize System'),
(29, '220002403', '2025-07-15 13:00:23', 'Present', 1, 'Enterprize System'),
(30, '220002403', '2025-07-15 13:05:09', 'Signed Out', 1, 'Enterprize System'),
(31, '220002403', '2025-07-15 19:05:29', 'Signed Out', NULL, NULL),
(32, '220002403', '2025-07-15 19:05:40', 'Present', NULL, NULL),
(33, '220002405', '2025-07-15 13:08:43', 'Signed Out', 1, 'Enterprize System'),
(35, '220002405', '2025-07-15 13:09:18', 'Present', 1, 'Enterprize System'),
(36, '220002405', '2025-07-15 13:10:18', 'Signed Out', 1, 'Enterprize System'),
(37, '220002405', '2025-07-15 13:10:22', 'Present', 1, 'Enterprize System'),
(38, '220002405', '2025-07-15 13:13:49', 'Signed Out', 1, 'Enterprize System'),
(39, '220002405', '2025-07-15 13:13:55', 'Present', 1, 'Enterprize System'),
(45, '220002403', '2025-07-15 14:04:30', 'Present', 1, 'Enterprize System'),
(46, '220002403', '2025-07-15 14:04:46', 'Signed Out', 1, 'Enterprize System'),
(47, '220002403', '2025-07-15 20:05:01', 'Signed Out', NULL, NULL),
(48, '220002403', '2025-07-15 14:19:03', 'Present', 1, 'Enterprize System'),
(49, '220002403', '2025-07-15 14:19:22', 'Signed Out', 1, 'Enterprize System'),
(50, '220002403', '2025-07-15 14:20:38', 'Present', 1, 'Enterprize System'),
(51, '220002405', '2025-07-15 14:21:57', 'Signed Out', 1, 'Enterprize System'),
(53, '220002405', '2025-07-15 14:41:47', 'Present', 1, 'Enterprize System'),
(54, '220002403', '2025-07-15 14:54:48', 'Signed Out', 1, 'Enterprize System'),
(55, '220002403', '2025-07-15 14:54:55', 'Present', 1, 'Enterprize System'),
(59, '220002403', '2025-07-15 14:58:14', 'Signed Out', 1, 'Enterprize System'),
(60, '220002403', '2025-07-15 15:00:42', 'Present', 1, 'Enterprize System'),
(61, '220002403', '2025-07-15 15:00:47', 'Signed Out', 1, 'Enterprize System'),
(62, '220002403', '2025-07-15 21:08:28', 'Present', NULL, NULL),
(63, '220002403', '2025-07-15 21:08:41', 'Signed Out', NULL, NULL),
(64, '220002403', '2025-07-15 21:08:48', 'Present', NULL, NULL),
(65, '220002403', '2025-07-15 21:08:48', 'Signed Out', NULL, NULL),
(66, '220002403', '2025-07-15 21:11:16', 'Present', NULL, NULL),
(68, '220002403', '2025-07-15 21:44:21', 'Present', 1, 'Enterprize System'),
(69, '220002403', '2025-07-15 21:47:45', 'Signed Out', 1, 'Enterprize System'),
(70, '220002403', '2025-07-15 21:47:56', 'Present', 1, 'Enterprize System'),
(71, '220002403', '2025-07-15 21:48:17', 'Signed Out', 1, 'Enterprize System'),
(72, '220002403', '2025-07-15 21:48:24', 'Present', 1, 'Enterprize System'),
(73, '220002403', '2025-07-15 21:48:56', 'Signed Out', 1, 'Enterprize System'),
(74, '220002403', '2025-07-15 21:51:02', 'Present', 1, 'Enterprize System'),
(83, '220002405', '2025-07-15 22:12:16', 'Signed Out', 1, 'Enterprize System'),
(84, '220002405', '2025-07-15 22:12:19', 'Present', 1, 'Enterprize System'),
(85, '220002405', '2025-07-15 22:12:19', 'Signed Out', 1, 'Enterprize System'),
(86, '220002405', '2025-07-15 22:13:20', 'Present', 1, 'Enterprize System'),
(87, '220002405', '2025-07-15 22:13:53', 'Signed Out', 1, 'Enterprize System'),
(88, '220002405', '2025-07-15 22:13:57', 'Present', 1, 'Enterprize System'),
(89, '220002405', '2025-07-15 22:30:38', 'Signed Out', 1, 'Enterprize System'),
(100, '220002405', '2025-07-15 23:24:28', 'Present', 1, 'Enterprize System'),
(101, '220002405', '2025-07-15 23:24:28', 'Signed Out', 1, 'Enterprize System'),
(102, '220002405', '2025-07-15 23:27:17', 'Signed Out', 1, 'Enterprize System'),
(103, '220002405', '2025-07-15 23:28:28', 'Present', 1, 'Enterprize System'),
(104, '220002405', '2025-07-15 23:28:40', 'Signed Out', 1, 'Enterprize System'),
(108, '220002403', '2025-07-15 23:32:00', 'Signed Out', 1, 'Enterprize System'),
(109, '220002403', '2025-07-15 23:32:04', 'Present', 1, 'Enterprize System'),
(110, '220002403', '2025-07-15 23:33:15', 'Signed Out', 1, 'Enterprize System'),
(116, '220002405', '2025-07-15 23:52:23', 'Present', 3, 'Programming'),
(117, '220002403', '2025-07-15 23:52:40', 'Present', 3, 'Programming'),
(118, '220002403', '2025-07-15 23:56:15', 'Signed Out', 3, 'Programming'),
(119, '220002403', '2025-07-15 23:59:11', 'Present', 3, 'Programming'),
(120, '220002405', '2025-07-15 23:59:25', 'Signed Out', 3, 'Programming'),
(121, '220002405', '2025-07-15 23:59:39', 'Present', 3, 'Programming'),
(122, '220002405', '2025-07-16 00:01:33', 'Present', 3, 'Programming'),
(123, '220002405', '2025-07-16 00:03:55', 'Signed Out', 3, 'Programming'),
(124, '220002403', '2025-07-16 00:05:41', 'Present', 3, 'Programming'),
(125, '220002405', '2025-07-16 00:14:15', 'Present', 3, 'Programming'),
(126, '220002405', '2025-07-16 00:14:34', 'Signed Out', NULL, NULL),
(127, '220002403', '2025-07-16 00:20:36', 'Signed Out', 3, 'Programming'),
(130, '220002405', '2025-07-16 00:21:45', 'Signed Out', 3, 'Programming'),
(131, '220002403', '2025-07-16 00:22:03', 'Present', 3, 'Programming'),
(132, '220002403', '2025-07-16 00:22:10', 'Signed Out', 3, 'Programming'),
(133, '220002405', '2025-07-16 00:22:59', 'Present', 3, 'Programming'),
(134, '220002403', '2025-07-16 00:23:07', 'Present', 3, 'Programming'),
(135, '220002405', '2025-07-16 00:23:26', 'Signed Out', 3, 'Programming'),
(138, '220002403', '2025-07-16 00:23:37', 'Signed Out', 3, 'Programming'),
(139, '220002403', '2025-07-16 00:23:45', 'Present', 3, 'Programming'),
(140, '220002403', '2025-07-16 00:26:08', 'Signed Out', 3, 'Programming'),
(141, '220002403', '2025-07-16 00:26:18', 'Present', 3, 'Programming'),
(142, '220002403', '2025-07-16 00:27:08', 'Signed Out', 3, 'Programming'),
(143, '220002403', '2025-07-16 00:28:31', 'Present', 3, 'Programming'),
(144, '220002403', '2025-07-16 00:28:49', 'Signed Out', 3, 'Programming'),
(145, '220002403', '2025-07-16 00:29:37', 'Present', 3, 'Programming'),
(146, '220002403', '2025-07-16 00:29:37', 'Signed Out', 3, 'Programming'),
(147, '220002405', '2025-07-16 00:29:48', 'Present', 3, 'Programming'),
(150, '220002405', '2025-07-16 00:31:27', 'Signed Out', 3, 'Programming'),
(151, '220002403', '2025-07-16 00:31:30', 'Signed Out', 3, 'Programming'),
(152, '220002403', '2025-07-16 00:32:29', 'Present', 3, 'Programming'),
(153, '220002405', '2025-07-16 00:32:38', 'Present', 3, 'Programming'),
(154, '220002403', '2025-07-16 00:33:20', 'Signed Out', 3, 'Programming'),
(155, '220002405', '2025-07-16 00:33:26', 'Signed Out', 3, 'Programming'),
(156, '220002405', '2025-07-16 00:35:27', 'Present', 3, 'Programming'),
(157, '220002403', '2025-07-16 00:35:38', 'Present', 3, 'Programming'),
(158, '220002405', '2025-07-16 00:35:45', 'Signed Out', 3, 'Programming'),
(159, '220002403', '2025-07-16 00:36:16', 'Signed Out', 3, 'Programming'),
(161, '220002405', '2025-07-16 00:37:02', 'Present', 3, 'Programming'),
(162, '220002405', '2025-07-16 00:37:40', 'Signed Out', 3, 'Programming'),
(165, '220002405', '2025-07-16 00:38:53', 'Present', 3, 'Programming'),
(166, '220002403', '2025-07-16 00:39:02', 'Present', 3, 'Programming'),
(168, '220002405', '2025-07-16 00:42:38', 'Signed Out', 3, 'Programming'),
(169, '220002403', '2025-07-16 00:42:41', 'Signed Out', 3, 'Programming'),
(172, '220002405', '2025-07-16 00:46:09', 'Present', 3, 'Programming'),
(174, '220002405', '2025-07-16 00:46:25', 'Signed Out', 3, 'Programming'),
(175, '220002403', '2025-07-16 00:46:39', 'Present', 3, 'Programming'),
(176, '220002403', '2025-07-16 00:46:49', 'Signed Out', 3, 'Programming'),
(177, '220002405', '2025-07-16 00:46:53', 'Present', 3, 'Programming'),
(179, '220002405', '2025-07-16 00:47:02', 'Signed Out', 3, 'Programming'),
(180, '220002403', '2025-07-16 00:47:05', 'Present', 3, 'Programming'),
(181, '220002403', '2025-07-16 00:47:09', 'Signed Out', 3, 'Programming'),
(183, '220002405', '2025-07-16 00:48:17', 'Present', 3, 'Programming'),
(184, '220002403', '2025-07-16 00:48:28', 'Present', 3, 'Programming'),
(185, '220002403', '2025-07-21 18:52:50', 'Present', 1, 'Enterprize System'),
(186, '220002404', '2025-07-21 18:52:56', 'Present', 1, 'Enterprize System'),
(187, '220002405', '2025-07-21 18:53:02', 'Present', 1, 'Enterprize System'),
(188, '220002403', '2025-07-21 19:29:30', 'Signed Out', NULL, NULL),
(189, '220002405', '2025-07-21 19:29:35', 'Signed Out', NULL, NULL),
(190, '220002404', '2025-07-21 19:29:38', 'Signed Out', NULL, NULL),
(191, '220002403', '2025-07-21 21:33:53', 'Present', 2, 'ESP'),
(192, '220002405', '2025-07-21 21:33:57', 'Present', 2, 'ESP'),
(193, '220002404', '2025-07-21 21:34:00', 'Present', 2, 'ESP'),
(194, '220002405', '2025-08-10 11:17:53', 'Present', 3, 'Programming'),
(195, '220002404', '2025-08-10 11:18:50', 'Present', 3, 'Programming'),
(196, '220002405', '2025-08-10 12:45:01', 'Signed Out', 3, 'Programming'),
(197, '220002405', '2025-08-10 12:46:10', 'Present', 3, 'Programming'),
(198, '220002404', '2025-08-10 12:46:15', 'Signed Out', 3, 'Programming'),
(199, '220002403', '2025-08-10 13:03:21', 'Present', 3, 'Programming'),
(200, '220002403', '2025-08-10 13:04:11', 'Signed Out', NULL, NULL),
(201, '220002403', '2025-08-10 13:04:13', 'Present', NULL, NULL),
(202, '220002403', '2025-08-10 13:04:41', 'Signed Out', NULL, NULL),
(203, '220002403', '2025-08-10 13:04:42', 'Present', NULL, NULL),
(204, '220002403', '2025-08-10 13:04:43', 'Signed Out', NULL, NULL),
(205, '220002403', '2025-08-10 13:04:44', 'Present', NULL, NULL),
(206, '220002403', '2025-08-11 15:00:19', 'Present', NULL, NULL),
(207, '220002403', '2025-08-11 15:00:25', 'Signed Out', NULL, NULL),
(208, '220002376', '2025-08-11 15:11:01', 'Present', 4, 'Capstone 2'),
(209, '220002403', '2025-08-11 16:31:10', 'Present', 4, 'Capstone 2'),
(210, '220002403', '2025-08-12 21:28:55', 'Present', 4, 'Capstone 2'),
(211, '220002403', '2025-08-12 21:44:50', 'Signed Out', 4, 'Capstone 2'),
(212, '220002403', '2025-08-12 21:45:44', 'Present', 4, 'Capstone 2'),
(213, '220002403', '2025-08-12 21:52:11', 'Signed Out', 4, 'Capstone 2'),
(214, '220002403', '2025-08-14 14:34:41', 'Present', 4, 'Capstone 2'),
(215, '220002403', '2025-08-14 14:34:46', 'Signed Out', 4, 'Capstone 2'),
(216, '220002148', '2025-08-14 14:38:08', 'Present', 4, 'Capstone 2'),
(217, '220002148', '2025-08-14 14:38:09', 'Signed Out', 4, 'Capstone 2'),
(218, '220002148', '2025-08-14 14:38:16', 'Present', 4, 'Capstone 2'),
(219, '220002148', '2025-08-14 14:38:16', 'Signed Out', 4, 'Capstone 2'),
(220, '220002403', '2025-08-14 15:01:19', 'Present', 3, 'Programming'),
(221, '220002403', '2025-08-14 15:01:19', 'Signed Out', 3, 'Programming'),
(222, '220002403', '2025-08-14 15:27:53', 'Signed Out', NULL, NULL),
(223, '220002403', '2025-08-14 15:28:05', 'Present', 4, 'Capstone 2'),
(224, '220002405', '2025-08-17 00:01:10', 'Present', 4, 'Capstone 2'),
(225, '220002405', '2025-08-17 00:01:24', 'Signed Out', 4, 'Capstone 2'),
(226, '220002405', '2025-08-17 00:02:17', 'Present', NULL, NULL),
(227, '220002405', '2025-08-17 00:02:17', 'Signed Out', NULL, NULL),
(228, '220002405', '2025-08-17 00:02:21', 'Signed Out', NULL, NULL),
(229, '220002405', '2025-08-17 00:02:23', 'Present', NULL, NULL),
(230, '220002405', '2025-08-17 00:02:23', 'Signed Out', NULL, NULL),
(231, '220002405', '2025-08-17 00:02:25', 'Signed Out', NULL, NULL),
(232, '220002405', '2025-08-17 00:02:25', 'Present', NULL, NULL),
(233, '220002403', '2025-08-17 23:46:34', 'Present', 4, 'Capstone 2'),
(234, '220002403', '2025-08-17 23:46:34', 'Signed Out', 4, 'Capstone 2'),
(235, '220002403', '2025-08-17 23:46:40', 'Signed Out', 4, 'Capstone 2'),
(236, '220002403', '2025-08-17 23:46:47', 'Present', 4, 'Capstone 2'),
(237, '220002403', '2025-08-18 00:08:00', 'Present', NULL, NULL),
(238, '220002403', '2025-08-18 00:08:25', 'Present', 3, 'Programming'),
(239, '220002403', '2025-08-18 00:15:38', 'Signed Out', 3, 'Programming'),
(240, '220002403', '2025-08-18 00:24:56', 'Present', NULL, NULL),
(241, '220002403', '2025-08-18 00:32:05', 'Present', 1, 'Enterprize System'),
(242, '220002403', '2025-08-18 00:32:58', 'Signed Out', 1, 'Enterprize System'),
(243, '220002403', '2025-08-18 00:54:29', 'Present', 2, 'ESP'),
(244, '220002403', '2025-08-18 00:55:25', 'Signed Out', 2, 'ESP'),
(245, '220002403', '2025-08-18 21:39:31', 'Present', NULL, NULL),
(246, '220002403', '2025-08-18 21:41:35', 'Signed Out', NULL, NULL),
(247, '220002403', '2025-08-18 21:42:22', 'Present', NULL, NULL),
(248, '220002403', '2025-08-18 21:42:22', 'Signed Out', NULL, NULL),
(249, '220002403', '2025-08-18 21:43:54', 'Signed Out', NULL, NULL),
(250, '220002403', '2025-08-18 21:44:13', 'Present', NULL, NULL),
(251, '220002403', '2025-08-18 21:44:43', 'Present', 4, 'Capstone 2'),
(252, '220002403', '2025-08-20 23:53:06', 'Present', 5, 'CAPSTONE1'),
(253, '220002403', '2025-08-20 23:56:24', 'Present', 1, 'Enterprize System'),
(254, '220002403', '2025-08-20 23:57:41', 'Signed Out', 1, 'Enterprize System'),
(255, '220002403', '2025-08-20 23:58:57', 'Present', 2, 'ESP'),
(256, '220002403', '2025-08-21 00:07:12', 'Present', 5, 'CAPSTONE1'),
(257, '220002403', '2025-08-21 00:07:32', 'Signed Out', NULL, NULL),
(258, '220002403', '2025-08-21 00:07:57', 'Present', 1, 'Enterprize System'),
(259, '220002403', '2025-08-21 00:08:31', 'Signed Out', 1, 'Enterprize System'),
(260, '220002403', '2025-08-21 00:08:42', 'Present', 3, 'Programming'),
(261, '220002403', '2025-08-21 00:20:37', 'Signed Out', 3, 'Programming'),
(262, '220002403', '2025-08-21 00:20:52', 'Present', 3, 'Programming'),
(263, '220002376', '2025-08-22 13:54:45', 'Present', 3, 'Programming'),
(264, '220002403', '2025-08-22 13:55:31', 'Present', 3, 'Programming'),
(265, '220002405', '2025-08-24 00:51:02', 'Present', 5, 'CAPSTONE1'),
(266, '220002405', '2025-08-24 00:52:19', 'Present', 2, 'ESP'),
(267, '220002405', '2025-08-24 00:52:47', 'Present', 1, 'Enterprize System'),
(268, '220002405', '2025-08-24 01:01:28', 'Signed Out', 5, 'CAPSTONE1'),
(269, '220002403', '2025-08-25 21:46:45', 'Present', 5, 'CAPSTONE1'),
(270, '220002403', '2025-08-25 21:46:45', 'Signed Out', 5, 'CAPSTONE1'),
(271, '220002403', '2025-08-25 21:46:51', 'Signed Out', 5, 'CAPSTONE1'),
(272, '220002403', '2025-08-25 21:46:51', 'Present', 5, 'CAPSTONE1'),
(273, '220002403', '2025-08-25 21:47:57', 'Present', 5, 'CAPSTONE1'),
(274, '220002405', '2025-08-25 21:52:51', 'Present', 5, 'CAPSTONE1'),
(275, '220002405', '2025-08-25 21:54:22', 'Signed Out', 5, 'CAPSTONE1'),
(276, '220002403', '2025-08-25 22:03:15', 'Signed Out', 5, 'CAPSTONE1'),
(277, '220002403', '2025-08-25 22:03:15', 'Present', 5, 'CAPSTONE1'),
(278, '220002403', '2025-08-25 22:03:21', 'Present', 5, 'CAPSTONE1'),
(279, '220002403', '2025-08-25 22:03:21', 'Signed Out', 5, 'CAPSTONE1'),
(280, '220002403', '2025-08-25 22:35:21', 'Present', NULL, NULL),
(281, '220002404', '2025-08-25 22:37:30', 'Present', 5, 'CAPSTONE1'),
(282, '220002403', '2025-08-25 22:50:45', 'Present', 1, 'Enterprize System'),
(283, '220002403', '2025-08-25 22:50:45', 'Signed Out', 1, 'Enterprize System'),
(284, '220002403', '2025-08-25 22:50:49', 'Signed Out', 1, 'Enterprize System'),
(285, '220002403', '2025-08-25 22:50:50', 'Present', 1, 'Enterprize System'),
(286, '220002403', '2025-08-25 22:50:50', 'Signed Out', 1, 'Enterprize System'),
(287, '220002403', '2025-08-25 22:50:51', 'Present', 1, 'Enterprize System'),
(288, '220002403', '2025-08-25 22:50:51', 'Signed Out', 1, 'Enterprize System'),
(289, '220002403', '2025-08-25 22:50:52', 'Signed Out', 1, 'Enterprize System'),
(290, '220002403', '2025-08-25 22:50:54', 'Present', 1, 'Enterprize System'),
(291, '220002403', '2025-08-26 01:04:17', 'Present', NULL, NULL),
(292, '220002403', '2025-08-26 01:04:17', 'Signed Out', NULL, NULL),
(293, '220002403', '2025-08-26 01:04:29', 'Signed Out', NULL, NULL),
(294, '220002403', '2025-08-26 01:04:34', 'Present', NULL, NULL),
(295, '220002403', '2025-08-26 01:04:34', 'Signed Out', NULL, NULL),
(296, '220002403', '2025-08-26 01:04:36', 'Present', NULL, NULL),
(297, '220002403', '2025-08-26 01:04:36', 'Signed Out', NULL, NULL),
(298, '220002403', '2025-08-27 13:34:13', 'Present', 6, 'ISO 25010'),
(299, '220002405', '2025-08-28 10:10:23', 'Present', 3, 'Programming'),
(300, '220002405', '2025-08-28 10:10:42', 'Signed Out', 3, 'Programming'),
(301, '220002403', '2025-08-28 16:59:21', 'Present', 5, 'CAPSTONE1'),
(302, '220002403', '2025-08-28 17:00:43', 'Signed Out', 5, 'CAPSTONE1'),
(303, '220002403', '2025-08-28 17:04:45', 'Present', 5, 'CAPSTONE1');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `course` varchar(50) DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `pc_number` varchar(50) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `name`, `section`, `course`, `year_level`, `barcode`, `profile_pic`, `pc_number`, `gender`) VALUES
(1, '220002403', 'Christopher Panoy', 'A', 'BSIS', 3, '220002403', 'student_6876291e63cfc4.98136848.jpg', '99', 'Male'),
(3, '220002405', 'Leonel Popatco', 'A', 'BSIS', 3, '220002405', 'student_68762987e84ad1.48628763.jpg', '7', 'Male'),
(5, '0002', 'Tope', 'A', 'BSIS', 2, '0002', 'student_68764ef3832aa0.84376158.jpg', '12', NULL),
(6, '009', 'Abdul', 'A', 'BSIS', 4, '009', 'student_68764f15d837b7.72079010.jpg', '21', NULL),
(7, '220002404', 'Roman Mercado', 'A', 'BSIS', 3, '220002404', 'student_687e1bc4048093.46913500.jpg', '100', 'Male'),
(8, '220002376', 'Cristine Maambong', 'A', 'BSIS', 3, '220002376', 'student_68995705662053.13220675.jpg', '12', 'Female'),
(9, '220002148', 'Mark Glen Guevarra', 'A', 'BSIS', 3, '220002148', 'student_689d841b991590.71695209.jpg', '1', 'Male'),
(10, '13123133131', 'Abdul Patikol', 'A', 'BSIS', 3, '13123133131', 'student_68a200a1123f96.41094008.jpg', '78', 'Male'),
(11, '220002409', 'Rambo Rhat', 'A', 'BSIS', 1, '220002409', 'student_68ad5fe7aea6e8.88253050.jpg', '1', 'Male');

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `student_subjects`
--

INSERT INTO `student_subjects` (`id`, `student_id`, `subject_id`, `created_at`) VALUES
(1, '220002403', 1, '2025-07-15 10:14:34'),
(2, '220002405', 1, '2025-07-15 10:14:34'),
(4, '220002403', 2, '2025-07-15 15:44:03'),
(5, '220002405', 2, '2025-07-15 15:44:03'),
(7, '220002403', 3, '2025-07-15 15:44:38'),
(8, '220002405', 3, '2025-07-15 15:44:38'),
(10, '220002404', 1, '2025-07-21 10:52:16'),
(11, '220002376', 4, '2025-08-11 07:09:47'),
(12, '220002403', 4, '2025-08-11 07:09:47'),
(13, '220002404', 4, '2025-08-11 07:09:47'),
(14, '220002405', 4, '2025-08-11 07:09:47'),
(15, '220002148', 4, '2025-08-14 06:37:56'),
(16, '13123133131', 4, '2025-08-17 16:29:17'),
(17, '13123133131', 5, '2025-08-25 13:47:44'),
(18, '220002148', 5, '2025-08-25 13:47:44'),
(19, '220002376', 5, '2025-08-25 13:47:44'),
(20, '220002403', 5, '2025-08-25 13:47:44'),
(21, '220002404', 5, '2025-08-25 13:47:44'),
(22, '220002405', 5, '2025-08-25 13:47:44'),
(23, '13123133131', 6, '2025-08-27 05:18:09'),
(24, '220002148', 6, '2025-08-27 05:18:09'),
(25, '220002376', 6, '2025-08-27 05:18:09'),
(26, '220002403', 6, '2025-08-27 05:18:09'),
(27, '220002404', 6, '2025-08-27 05:18:09'),
(28, '220002405', 6, '2025-08-27 05:18:09');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `subject_name` varchar(100) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `schedule_days` varchar(50) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `teacher_id`, `schedule_days`, `start_time`, `end_time`) VALUES
(1, '001', 'Enterprize System', 1, 'Mon', '22:50:00', '23:00:00'),
(2, '102', 'ESP', 1, 'Wed', '23:45:00', '00:45:00'),
(3, '103', 'Programming', 1, 'Thu', '00:46:00', '13:45:00'),
(4, '105', 'Capstone 2', 1, 'Mon', '16:29:00', '17:01:00'),
(5, '999', 'CAPSTONE1', 1, 'Mon,Thu', '16:33:00', '17:57:00'),
(6, 'IS06', 'ISO 25010', 1, 'Wed', '13:31:00', '14:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `teacher_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `teacher_id`, `name`, `email`, `phone`, `department`, `profile_pic`, `created_at`) VALUES
(1, '001', 'Joshua Tiongco', 'joshuationgco@gmail.com', '09309971418', 'College of Computing Studies', 'teacher_68a495af045ea6.85352413.jpg', '2025-07-15 10:14:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','teacher','student') DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `name`, `verified`) VALUES
(1, NULL, 'christophermadeja7@gmail.com', '$2y$10$SNXuGq75DHCZoQ028oyfiOJMfRQAVf3qWhXCEZuhW0Snh3QdKs7Me', 'admin', 'Christopher', 1),
(2, NULL, 'joshuationgco@gmail.com', '$2y$10$69mEv4OrZRH28UGcaRNB6.npMuW6hizsAn1TenXBynJdUMb6wOlma', 'teacher', 'Joshua Tiongco', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_subject` (`student_id`,`subject_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=304;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `student_subjects`
--
ALTER TABLE `student_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD CONSTRAINT `fk_student_subjects_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_student_subjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
