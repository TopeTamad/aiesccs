-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2025 at 08:03 PM
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
(186, '220002404', '2025-07-21 18:52:56', 'Present', NULL, 'Enterprize System'),
(190, '220002404', '2025-07-21 19:29:38', 'Signed Out', NULL, NULL),
(193, '220002404', '2025-07-21 21:34:00', 'Present', NULL, 'ESP'),
(195, '220002404', '2025-08-10 11:18:50', 'Present', 3, 'Programming'),
(198, '220002404', '2025-08-10 12:46:15', 'Signed Out', 3, 'Programming'),
(208, '220002376', '2025-08-11 15:11:01', 'Present', NULL, 'Capstone 2'),
(263, '220002376', '2025-08-22 13:54:45', 'Present', 3, 'Programming'),
(281, '220002404', '2025-08-25 22:37:30', 'Present', NULL, 'CAPSTONE1'),
(488, '220002403', '2025-08-29 01:16:47', 'Present', NULL, 'ISO 25010'),
(490, '220002403', '2025-08-29 01:20:16', 'Signed Out', NULL, 'ISO 25010'),
(495, '220002403', '2025-08-29 01:26:24', 'Present', 8, 'CAPSTONE2'),
(496, '220002403', '2025-08-29 01:26:24', 'Signed Out', 8, 'CAPSTONE2'),
(497, '220002403', '2025-08-29 01:26:33', 'Signed Out', 8, 'CAPSTONE2'),
(498, '220002403', '2025-08-29 01:26:38', 'Present', 8, 'CAPSTONE2'),
(499, '220002403', '2025-08-29 01:32:22', 'Signed Out', 8, 'CAPSTONE2'),
(500, '220002403', '2025-08-29 01:32:23', 'Present', 8, 'CAPSTONE2'),
(501, '220002403', '2025-08-29 01:32:23', 'Signed Out', 8, 'CAPSTONE2'),
(502, '220002403', '2025-08-29 01:32:27', 'Present', 8, 'CAPSTONE2'),
(503, '220002403', '2025-08-29 01:43:24', '', 8, 'CAPSTONE2'),
(504, '220002403', '2025-08-29 01:43:43', 'Present', 3, 'Programming'),
(505, '220002403', '2025-08-29 01:45:00', 'Signed Out', 3, 'Programming'),
(510, '220002405', '2025-08-29 01:49:50', 'Present', 3, 'Programming'),
(511, '220002405', '2025-08-29 01:49:50', 'Signed Out', 3, 'Programming'),
(512, '220002404', '2025-08-29 01:50:26', 'Present', 3, 'Programming'),
(513, '220002404', '2025-08-29 01:50:41', 'Signed Out', 3, 'Programming'),
(514, '220002403', '2025-08-29 01:55:21', 'Present', 3, 'Programming'),
(515, '220002403', '2025-08-29 01:55:21', 'Signed Out', 3, 'Programming'),
(516, '220002403', '2025-08-29 01:55:34', 'Signed Out', 3, 'Programming'),
(517, '220002403', '2025-08-29 01:55:37', 'Present', 3, 'Programming'),
(518, '220002403', '2025-08-29 01:55:41', 'Signed Out', 3, 'Programming'),
(519, '220002403', '2025-08-29 01:55:41', 'Present', 3, 'Programming'),
(520, '220002403', '2025-08-29 01:55:42', 'Signed Out', 3, 'Programming'),
(521, '220002403', '2025-08-29 01:55:42', 'Present', 3, 'Programming'),
(522, '220002403', '2025-08-29 01:55:48', 'Present', 3, 'Programming'),
(523, '220002403', '2025-08-29 01:55:56', 'Signed Out', 3, 'Programming'),
(524, '220002405', '2025-08-29 01:56:23', 'Signed Out', 3, 'Programming'),
(525, '220002405', '2025-08-29 01:56:23', 'Present', 3, 'Programming'),
(526, '220002405', '2025-08-29 01:56:31', 'Present', 3, 'Programming'),
(527, '220002405', '2025-08-29 01:56:39', 'Signed Out', 3, 'Programming'),
(528, '220002403', '2025-08-29 02:01:55', 'Present', NULL, 'CAPSTONE1'),
(529, '220002403', '2025-08-29 02:02:07', 'Signed Out', NULL, 'CAPSTONE1'),
(530, '220002405', '2025-08-29 02:02:42', 'Present', NULL, 'CAPSTONE1'),
(531, '220002405', '2025-08-29 02:02:59', 'Signed Out', NULL, 'CAPSTONE1'),
(532, '220002403', '2025-08-29 02:19:14', 'Present', NULL, 'CAPSTONE1'),
(533, '220002403', '2025-08-29 02:19:27', 'Signed Out', NULL, 'CAPSTONE1'),
(534, '220002405', '2025-08-29 02:22:52', 'Present', NULL, 'CAPSTONE1'),
(535, '220002405', '2025-08-29 02:22:52', 'Signed Out', NULL, 'CAPSTONE1'),
(536, '220002403', '2025-08-29 02:31:34', 'Present', 8, 'CAPSTONE2'),
(537, '220002403', '2025-08-29 02:32:15', 'Signed Out', 8, 'CAPSTONE2'),
(538, '220002405', '2025-08-29 02:32:24', 'Present', 8, 'CAPSTONE2'),
(539, '220002405', '2025-08-29 02:32:38', 'Signed Out', 8, 'CAPSTONE2'),
(540, '220002403', '2025-08-29 10:39:12', 'Present', NULL, 'CAPSTONE1'),
(541, '220002403', '2025-08-29 10:41:19', 'Signed Out', NULL, 'CAPSTONE1'),
(542, '220002376', '2025-08-29 10:41:57', 'Present', NULL, 'CAPSTONE1'),
(543, '220002376', '2025-08-29 10:46:06', '', NULL, 'CAPSTONE1'),
(544, '220002376', '2025-08-29 10:46:28', 'Present', 8, 'CAPSTONE2'),
(545, '220002376', '2025-08-29 10:46:54', 'Signed Out', 8, 'CAPSTONE2'),
(546, '220002403', '2025-08-29 10:47:44', 'Present', 8, 'CAPSTONE2'),
(547, '220002403', '2025-08-29 10:47:55', 'Signed Out', 8, 'CAPSTONE2'),
(548, '220002405', '2025-08-29 11:06:12', 'Signed Out', NULL, 'CAPSTONE1'),
(549, '220002405', '2025-08-29 11:06:26', 'Present', NULL, 'CAPSTONE1'),
(550, '220002403', '2025-08-29 11:55:24', 'Present', NULL, 'CAPSTONE1'),
(551, '220002403', '2025-08-29 11:55:24', 'Signed Out', NULL, 'CAPSTONE1'),
(552, '220002403', '2025-08-29 11:55:31', 'Signed Out', NULL, 'CAPSTONE1'),
(553, '220002403', '2025-08-29 11:55:31', 'Present', NULL, 'CAPSTONE1'),
(554, '220002403', '2025-08-29 11:55:42', 'Signed Out', NULL, 'CAPSTONE1'),
(555, '220002405', '2025-08-29 11:57:19', 'Signed Out', NULL, 'CAPSTONE1'),
(556, '220002405', '2025-08-29 11:57:47', 'Present', NULL, 'CAPSTONE1'),
(557, '220002405', '2025-08-29 11:57:48', 'Signed Out', NULL, 'CAPSTONE1'),
(558, '220002405', '2025-08-29 11:57:48', 'Present', NULL, 'CAPSTONE1'),
(559, '220002405', '2025-08-29 11:57:51', 'Signed Out', NULL, 'CAPSTONE1'),
(560, '220002405', '2025-08-29 11:57:51', 'Present', NULL, 'CAPSTONE1'),
(561, '220002405', '2025-08-29 11:57:58', 'Present', NULL, 'CAPSTONE1'),
(562, '220002405', '2025-08-29 11:58:01', 'Signed Out', NULL, 'CAPSTONE1'),
(563, '220002376', '2025-08-29 11:58:20', 'Present', NULL, 'CAPSTONE1'),
(564, '220002376', '2025-08-29 11:58:30', 'Signed Out', NULL, 'CAPSTONE1'),
(565, '220002376', '2025-08-29 11:58:30', 'Present', NULL, 'CAPSTONE1'),
(566, '220002376', '2025-08-29 11:58:33', 'Signed Out', NULL, 'CAPSTONE1'),
(567, '220002376', '2025-08-29 11:58:33', 'Present', NULL, 'CAPSTONE1'),
(568, '220002376', '2025-08-29 11:58:40', 'Present', NULL, 'CAPSTONE1'),
(569, '220002376', '2025-08-29 11:58:54', 'Signed Out', NULL, 'CAPSTONE1'),
(570, '220002405', '2025-08-29 12:37:20', 'Present', NULL, NULL),
(571, '220002403', '2025-08-29 12:54:02', 'Present', NULL, 'CAPSTONE1'),
(572, '220002403', '2025-08-29 12:54:02', 'Signed Out', NULL, 'CAPSTONE1'),
(577, '220002376', '2025-08-29 14:43:45', 'Present', 11, 'Esports Education'),
(578, '220002376', '2025-08-29 14:43:45', 'Signed Out', 11, 'Esports Education'),
(579, '220002376', '2025-08-29 14:43:48', 'Signed Out', 11, 'Esports Education'),
(580, '220002403', '2025-08-29 14:43:57', 'Present', 11, 'Esports Education'),
(581, '220002403', '2025-08-29 14:46:12', '', 11, 'Esports Education'),
(582, '220002403', '2025-08-29 14:46:45', 'Present', 12, 'Moblie Legends Bang Bang'),
(583, '220002403', '2025-09-02 21:54:56', 'Present', 8, 'CAPSTONE2'),
(584, '220002403', '2025-09-02 21:55:13', 'Signed Out', 8, 'CAPSTONE2'),
(585, '220002403', '2025-09-02 22:01:11', 'Present', 3, 'Programming'),
(586, '220002403', '2025-09-02 22:01:42', 'Signed Out', 3, 'Programming'),
(587, '220002403', '2025-09-02 22:15:44', 'Present', 3, 'Programming'),
(588, '220002403', '2025-09-02 23:00:38', 'Signed Out', 3, 'Programming'),
(589, '220002403', '2025-09-02 23:00:42', 'Present', 3, 'Programming'),
(590, '220002403', '2025-09-02 23:39:35', '', 3, 'Programming'),
(591, '220002403', '2025-09-02 23:39:43', 'Present', 8, 'CAPSTONE2'),
(592, '220002403', '2025-09-03 00:02:54', 'Present', 3, 'Programming'),
(593, '220002403', '2025-09-03 00:03:34', 'Signed Out', 3, 'Programming'),
(594, '220002403', '2025-09-03 00:05:40', 'Present', 3, 'Programming'),
(595, '220002405', '2025-09-03 00:08:50', 'Present', 3, 'Programming'),
(596, '220002403', '2025-09-03 00:56:51', '', 3, 'Programming'),
(597, '220002403', '2025-09-03 00:56:53', 'Present', 9, 'Enterprize System'),
(598, '220002405', '2025-09-06 21:50:17', 'Present', 3, 'Programming'),
(599, '220002405', '2025-09-06 21:50:17', 'Signed Out', 3, 'Programming'),
(600, '220002405', '2025-09-06 21:50:47', 'Signed Out', 3, 'Programming'),
(601, '220002405', '2025-09-06 22:00:21', 'Present', 8, 'CAPSTONE2'),
(602, '220002405', '2025-09-06 22:02:08', '', 8, 'CAPSTONE2'),
(603, '220002405', '2025-09-06 22:02:13', 'Present', 9, 'Enterprize System'),
(604, '220002405', '2025-09-06 22:07:47', '', 9, 'Enterprize System'),
(605, '220002405', '2025-09-06 22:07:48', 'Present', 11, 'Esports Education'),
(606, '220002405', '2025-09-06 22:07:50', 'Signed Out', 11, 'Esports Education'),
(607, '220002405', '2025-09-06 22:07:50', 'Present', 11, 'Esports Education'),
(608, '220002405', '2025-09-06 22:07:50', 'Signed Out', 11, 'Esports Education'),
(609, '220002405', '2025-09-06 22:07:52', 'Present', 11, 'Esports Education'),
(610, '220002405', '2025-09-07 01:58:53', 'Present', 8, 'CAPSTONE2'),
(611, '220002405', '2025-09-07 03:48:06', '', 8, 'CAPSTONE2'),
(612, '220002405', '2025-09-07 03:48:08', 'Present', 3, 'Programming'),
(613, '220002405', '2025-09-07 03:48:08', 'Signed Out', 3, 'Programming'),
(614, '220002405', '2025-09-07 03:48:11', 'Signed Out', 3, 'Programming'),
(615, '220002405', '2025-09-07 03:48:13', 'Present', 3, 'Programming'),
(616, '220002405', '2025-09-07 03:48:13', 'Signed Out', 3, 'Programming'),
(617, '220002405', '2025-09-07 03:48:19', 'Present', 3, 'Programming'),
(618, '220002403', '2025-09-08 14:53:29', 'Present', 3, 'Programming'),
(619, '220002403', '2025-09-08 15:02:00', 'Signed Out', 3, 'Programming'),
(620, '220002403', '2025-09-08 17:10:49', 'Present', 8, 'CAPSTONE2'),
(621, '220002403', '2025-09-08 17:13:09', '', 8, 'CAPSTONE2'),
(622, '220002403', '2025-09-08 17:13:09', 'Present', 9, 'Enterprize System'),
(623, '220002403', '2025-09-08 17:23:10', '', 9, 'Enterprize System'),
(624, '220002403', '2025-09-08 17:23:13', 'Present', 13, 'Abdul'),
(625, '220002403', '2025-09-08 17:23:26', 'Signed Out', 13, 'Abdul'),
(626, '220002403', '2025-09-08 17:23:26', 'Present', 13, 'Abdul'),
(627, '220002403', '2025-09-08 17:23:28', 'Signed Out', 13, 'Abdul'),
(628, '220002403', '2025-09-08 17:23:28', 'Present', 13, 'Abdul'),
(629, '220002403', '2025-09-08 17:23:33', 'Present', 13, 'Abdul'),
(630, '220002403', '2025-09-08 17:23:33', 'Signed Out', 13, 'Abdul'),
(631, '220002403', '2025-09-08 17:24:01', 'Present', 13, 'Abdul'),
(632, '220002403', '2025-09-08 17:24:26', 'Signed Out', 13, 'Abdul'),
(633, '220002403', '2025-09-08 17:40:41', 'Present', NULL, NULL),
(634, '220002403', '2025-09-08 17:41:16', 'Signed Out', NULL, NULL),
(635, '220002405', '2025-09-08 17:46:39', 'Present', 13, 'Abdul'),
(636, '220002405', '2025-09-08 17:46:39', 'Signed Out', 13, 'Abdul'),
(637, '220002405', '2025-09-08 17:46:43', 'Signed Out', 13, 'Abdul'),
(638, '220002405', '2025-09-08 17:46:45', 'Present', 13, 'Abdul'),
(639, '220002403', '2025-09-12 01:44:37', 'Present', 11, 'Esports Education'),
(640, '220002403', '2025-09-12 01:44:37', 'Signed Out', 11, 'Esports Education'),
(641, '220002403', '2025-09-12 01:44:45', 'Signed Out', 11, 'Esports Education'),
(642, '220002403', '2025-09-12 01:44:45', 'Present', 11, 'Esports Education'),
(643, '220002403', '2025-09-12 02:17:45', 'Present', 11, 'Esports Education'),
(644, '220002403', '2025-09-12 02:17:45', 'Signed Out', 11, 'Esports Education'),
(645, '220002403', '2025-09-12 02:18:24', 'Present', 11, 'Esports Education'),
(646, '220002403', '2025-09-12 03:54:46', '', 11, 'Esports Education'),
(647, '220002403', '2025-09-12 03:54:46', 'Present', 3, 'Programming'),
(648, '220002405', '2025-09-13 22:01:36', 'Present', 3, 'Programming'),
(649, '220002405', '2025-09-13 22:01:36', 'Signed Out', 3, 'Programming'),
(650, '220002405', '2025-09-13 22:01:38', 'Signed Out', 3, 'Programming'),
(651, '220002405', '2025-09-13 22:01:40', 'Present', 3, 'Programming'),
(652, '220002404', '2025-09-13 22:03:20', 'Present', 3, 'Programming'),
(653, '220002404', '2025-09-13 22:03:20', 'Signed Out', 3, 'Programming'),
(654, '220002404', '2025-09-13 22:03:22', 'Signed Out', 3, 'Programming'),
(655, '220002404', '2025-09-13 22:03:25', 'Present', 3, 'Programming'),
(656, '220002405', '2025-09-13 22:04:21', 'Signed Out', 3, 'Programming'),
(657, '220002405', '2025-09-13 22:04:21', 'Present', 3, 'Programming'),
(658, '220002403', '2025-09-13 22:47:57', 'Present', 3, 'Programming'),
(659, '220002405', '2025-09-13 23:07:57', 'Present', 3, 'Programming'),
(660, '220002405', '2025-09-13 23:07:57', 'Signed Out', 3, 'Programming'),
(661, '220002405', '2025-09-14 00:23:57', 'Present', 8, 'CAPSTONE2'),
(662, '220002403', '2025-09-14 23:37:47', 'Present', 3, 'Programming'),
(663, '220002403', '2025-09-14 23:37:47', 'Signed Out', 3, 'Programming'),
(664, '220002403', '2025-09-14 23:37:51', 'Signed Out', 3, 'Programming'),
(665, '220002403', '2025-09-14 23:37:51', 'Present', 3, 'Programming'),
(666, '220002376', '2025-09-14 23:43:26', 'Present', 3, 'Programming'),
(667, '220002376', '2025-09-15 00:10:41', 'Present', 8, 'CAPSTONE2'),
(668, '220002376', '2025-09-15 00:10:41', 'Signed Out', 8, 'CAPSTONE2'),
(669, '220002404', '2025-09-15 00:11:31', 'Present', 8, 'CAPSTONE2'),
(670, '220002404', '2025-09-15 00:11:31', 'Signed Out', 8, 'CAPSTONE2'),
(671, '220002404', '2025-09-15 00:11:33', 'Signed Out', 8, 'CAPSTONE2'),
(672, '220002404', '2025-09-15 00:11:33', 'Present', 8, 'CAPSTONE2'),
(673, '220002405', '2025-09-15 00:12:14', 'Present', 8, 'CAPSTONE2'),
(674, '220002405', '2025-09-15 00:12:14', 'Signed Out', 8, 'CAPSTONE2'),
(675, '220002376', '2025-09-15 00:12:41', 'Signed Out', 8, 'CAPSTONE2'),
(676, '220002376', '2025-09-15 00:12:41', 'Present', 8, 'CAPSTONE2'),
(677, '220002403', '2025-09-15 00:19:09', 'Present', 8, 'CAPSTONE2'),
(678, '220002403', '2025-09-15 00:19:09', 'Signed Out', 8, 'CAPSTONE2'),
(679, '220002403', '2025-09-15 00:19:12', 'Signed Out', 8, 'CAPSTONE2'),
(680, '220002403', '2025-09-15 00:19:12', 'Present', 8, 'CAPSTONE2'),
(681, '220002404', '2025-09-15 00:21:46', 'Present', 8, 'CAPSTONE2'),
(682, '220002404', '2025-09-15 00:21:46', 'Signed Out', 8, 'CAPSTONE2'),
(683, '220002405', '2025-09-15 00:22:17', 'Signed Out', 8, 'CAPSTONE2'),
(684, '220002405', '2025-09-15 00:22:17', 'Present', 8, 'CAPSTONE2'),
(685, '220002403', '2025-09-15 00:37:00', 'Present', 8, 'CAPSTONE2'),
(686, '220002376', '2025-09-15 00:55:46', 'Present', 8, 'CAPSTONE2'),
(687, '220002376', '2025-09-15 00:55:46', 'Signed Out', 8, 'CAPSTONE2'),
(688, '220002403', '2025-09-15 01:01:55', '', 8, 'CAPSTONE2'),
(689, '220002403', '2025-09-15 01:01:55', 'Present', 11, 'Esports Education'),
(690, '220002403', '2025-09-15 01:07:28', 'Present', 12, 'Moblie Legends Bang Bang'),
(691, '220002403', '2025-09-15 01:07:28', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(692, '220002403', '2025-09-15 01:07:49', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(693, '220002403', '2025-09-15 01:07:49', 'Present', 12, 'Moblie Legends Bang Bang'),
(694, '220002403', '2025-09-15 01:10:29', 'Present', 12, 'Moblie Legends Bang Bang'),
(695, '220002403', '2025-09-15 01:10:29', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(696, '220002403', '2025-09-15 01:11:23', 'Present', 12, 'Moblie Legends Bang Bang'),
(697, '220002403', '2025-09-15 01:11:23', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(698, '220002405', '2025-09-15 01:13:02', 'Present', 12, 'Moblie Legends Bang Bang'),
(699, '220002405', '2025-09-15 01:13:02', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(700, '220002403', '2025-09-15 01:13:04', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(701, '220002403', '2025-09-15 01:13:04', 'Present', 12, 'Moblie Legends Bang Bang'),
(702, '220002404', '2025-09-15 01:13:07', 'Present', 12, 'Moblie Legends Bang Bang'),
(703, '220002404', '2025-09-15 01:13:07', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(704, '220002404', '2025-09-15 01:13:09', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(705, '220002404', '2025-09-15 01:13:09', 'Present', 12, 'Moblie Legends Bang Bang'),
(706, '220002404', '2025-09-15 01:13:49', 'Present', 12, 'Moblie Legends Bang Bang'),
(707, '220002404', '2025-09-15 01:13:49', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(708, '220002405', '2025-09-15 01:14:11', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(709, '220002405', '2025-09-15 01:14:11', 'Present', 12, 'Moblie Legends Bang Bang'),
(710, '220002403', '2025-09-15 01:14:18', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(711, '220002403', '2025-09-15 01:14:19', 'Present', 12, 'Moblie Legends Bang Bang'),
(712, '220002405', '2025-09-15 01:14:20', 'Present', 12, 'Moblie Legends Bang Bang'),
(713, '220002405', '2025-09-15 01:14:23', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(714, '220002405', '2025-09-15 01:14:24', 'Present', 12, 'Moblie Legends Bang Bang'),
(715, '220002376', '2025-09-15 01:14:27', 'Present', 12, 'Moblie Legends Bang Bang'),
(716, '220002376', '2025-09-15 01:14:27', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(717, '220002404', '2025-09-15 01:23:12', 'Present', 12, 'Moblie Legends Bang Bang'),
(718, '220002404', '2025-09-15 01:23:14', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(719, '220002404', '2025-09-15 01:23:15', 'Present', 12, 'Moblie Legends Bang Bang'),
(720, '220002405', '2025-09-15 01:23:46', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(721, '220002403', '2025-09-15 01:25:39', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(722, '220002403', '2025-09-15 01:25:39', 'Present', 13, 'Abdul'),
(723, '220002403', '2025-09-15 01:25:39', 'Signed Out', 13, 'Abdul'),
(724, '220002403', '2025-09-15 01:25:53', 'Signed Out', 13, 'Abdul'),
(725, '220002403', '2025-09-15 01:25:59', 'Present', 13, 'Abdul'),
(726, '220002403', '2025-09-15 01:26:25', 'Signed Out', 13, 'Abdul'),
(727, '220002403', '2025-09-15 01:39:49', 'Present', 13, 'Abdul'),
(728, '220002404', '2025-09-15 02:34:30', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(729, '220002404', '2025-09-15 02:34:30', 'Present', 8, 'CAPSTONE2'),
(730, '220002404', '2025-09-15 02:35:04', 'Present', 11, 'Esports Education'),
(731, '220002404', '2025-09-15 02:35:17', 'Signed Out', 11, 'Esports Education'),
(732, '220002404', '2025-09-15 02:35:31', 'Present', 11, 'Esports Education'),
(733, '220002404', '2025-09-15 02:39:22', 'Signed Out', 11, 'Esports Education'),
(734, '220002404', '2025-09-15 02:39:28', 'Present', 11, 'Esports Education'),
(735, '220002404', '2025-09-15 02:39:48', 'Signed Out', 11, 'Esports Education'),
(736, '220002405', '2025-09-15 02:45:22', 'Present', 11, 'Esports Education'),
(737, '220002405', '2025-09-15 02:45:43', 'Signed Out', 11, 'Esports Education'),
(738, '220002405', '2025-09-15 02:45:49', 'Present', 11, 'Esports Education'),
(739, '220002405', '2025-09-15 02:45:56', 'Signed Out', 11, 'Esports Education'),
(740, '220002405', '2025-09-15 02:46:30', 'Present', 11, 'Esports Education'),
(741, '220002376', '2025-09-15 02:47:31', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(742, '220002376', '2025-09-15 02:47:31', 'Present', 11, 'Esports Education'),
(743, '220002376', '2025-09-15 02:47:56', 'Signed Out', 11, 'Esports Education'),
(744, '220002376', '2025-09-15 02:48:07', 'Present', 11, 'Esports Education'),
(745, '220002403', '2025-09-15 02:55:20', 'Signed Out', 13, 'Abdul'),
(746, '220002403', '2025-09-15 02:55:20', 'Present', 11, 'Esports Education'),
(747, '220002404', '2025-09-15 02:56:12', 'Present', 11, 'Esports Education'),
(748, '220002404', '2025-09-15 02:56:23', 'Signed Out', 11, 'Esports Education'),
(749, '220002376', '2025-09-15 02:57:23', 'Signed Out', 11, 'Esports Education'),
(750, '220002405', '2025-09-15 02:57:47', 'Signed Out', 11, 'Esports Education'),
(751, '220002403', '2025-09-15 03:17:22', 'Signed Out', 11, 'Esports Education'),
(752, '220002403', '2025-09-15 03:17:22', 'Present', 12, 'Moblie Legends Bang Bang'),
(753, '220002404', '2025-09-15 03:28:38', 'Present', 12, 'Moblie Legends Bang Bang'),
(754, '220002404', '2025-09-15 03:29:09', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(755, '220002403', '2025-09-15 03:30:41', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(756, '220002403', '2025-09-15 03:31:50', 'Present', 12, 'Moblie Legends Bang Bang'),
(757, '220002403', '2025-09-15 03:46:28', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(758, '220002403', '2025-09-15 03:46:28', 'Present', 14, 'EPP'),
(759, '220002403', '2025-09-15 03:46:49', 'Signed Out', 14, 'EPP'),
(760, '220002405', '2025-09-15 03:47:44', 'Present', 14, 'EPP'),
(761, '220002404', '2025-09-15 03:47:59', 'Present', 14, 'EPP'),
(762, '220002405', '2025-09-15 03:48:21', 'Signed Out', 14, 'EPP'),
(763, '220002405', '2025-09-15 03:48:35', 'Present', 14, 'EPP'),
(764, '220002404', '2025-09-15 03:48:40', 'Signed Out', 14, 'EPP'),
(765, '220002405', '2025-09-15 03:48:55', 'Signed Out', 14, 'EPP'),
(766, '220002376', '2025-09-15 03:49:32', 'Present', 14, 'EPP'),
(767, '220002376', '2025-09-15 03:58:45', 'Signed Out', 14, 'EPP'),
(768, '220002403', '2025-09-15 04:14:03', 'Present', 3, 'Programming'),
(769, '220002405', '2025-09-15 04:14:20', 'Present', 3, 'Programming'),
(770, '220002404', '2025-09-15 04:14:24', 'Present', 3, 'Programming'),
(771, '220002376', '2025-09-15 04:14:27', 'Present', 3, 'Programming'),
(772, '220002403', '2025-09-15 04:26:16', 'Signed Out', 3, 'Programming'),
(773, '220002405', '2025-09-15 04:28:13', 'Signed Out', 3, 'Programming'),
(774, '220002404', '2025-09-15 04:28:50', 'Signed Out', 3, 'Programming'),
(775, '220002376', '2025-09-15 04:28:53', 'Signed Out', 3, 'Programming'),
(776, '220002403', '2025-09-16 18:12:53', 'Present', 3, 'Programming'),
(777, '220002403', '2025-09-16 18:13:35', 'Signed Out', 3, 'Programming'),
(778, '220002405', '2025-09-16 18:14:24', 'Present', 3, 'Programming'),
(779, '220002404', '2025-09-16 18:14:28', 'Present', 3, 'Programming'),
(780, '220002376', '2025-09-16 18:14:31', 'Present', 3, 'Programming'),
(781, '220002376', '2025-09-16 18:23:00', 'Signed Out', 3, 'Programming'),
(782, '220002404', '2025-09-16 18:23:15', 'Signed Out', 3, 'Programming'),
(783, '220002405', '2025-09-16 18:23:46', 'Signed Out', 3, 'Programming'),
(784, '220002376', '2025-09-16 18:30:32', 'Present', 15, 'ISO25010'),
(785, '220002404', '2025-09-16 18:30:34', 'Present', 15, 'ISO25010'),
(786, '220002405', '2025-09-16 18:30:38', 'Present', 15, 'ISO25010'),
(787, '220002403', '2025-09-16 18:31:17', 'Present', 12, 'Moblie Legends Bang Bang'),
(788, '220002403', '2025-09-16 18:31:33', 'Signed Out', 12, 'Moblie Legends Bang Bang'),
(789, '220002405', '2025-09-16 20:29:11', 'Signed Out', 15, 'ISO25010'),
(790, '220002404', '2025-09-16 20:29:19', 'Signed Out', 15, 'ISO25010'),
(791, '220002376', '2025-09-16 20:29:27', 'Signed Out', 15, 'ISO25010'),
(792, '220002405', '2025-09-17 00:04:39', 'Present', 9, 'Enterprize System'),
(793, '220002404', '2025-09-17 00:04:57', 'Present', 9, 'Enterprize System'),
(794, '220002376', '2025-09-17 00:05:30', 'Present', 9, 'Enterprize System'),
(795, '220002405', '2025-09-17 00:09:26', 'Signed Out', 9, 'Enterprize System'),
(796, '220002404', '2025-09-17 00:09:33', 'Signed Out', 9, 'Enterprize System'),
(797, '220002376', '2025-09-17 00:09:43', 'Signed Out', 9, 'Enterprize System'),
(798, '220002376', '2025-09-17 00:17:54', 'Present', 9, 'Enterprize System'),
(799, '220002376', '2025-09-17 00:18:15', 'Signed Out', 9, 'Enterprize System'),
(800, '220002376', '2025-09-17 00:18:22', 'Present', 9, 'Enterprize System'),
(801, '220002405', '2025-09-17 00:25:18', 'Present', 8, 'CAPSTONE2'),
(802, '220002404', '2025-09-17 00:25:26', 'Present', 8, 'CAPSTONE2'),
(803, '220002376', '2025-09-17 00:25:29', 'Signed Out', 9, 'Enterprize System'),
(804, '220002376', '2025-09-17 00:25:29', 'Present', 8, 'CAPSTONE2'),
(805, '220002403', '2025-09-17 00:25:42', 'Present', 8, 'CAPSTONE2'),
(806, '220002405', '2025-09-17 00:28:28', 'Signed Out', 8, 'CAPSTONE2'),
(807, '220002405', '2025-09-17 00:28:28', 'Present', 3, 'Programming'),
(808, '220002405', '2025-09-17 00:28:47', 'Signed Out', 3, 'Programming'),
(809, '220002404', '2025-09-17 00:34:22', 'Signed Out', 8, 'CAPSTONE2'),
(810, '220002404', '2025-09-17 00:34:22', 'Present', 3, 'Programming'),
(811, '220002376', '2025-09-17 00:34:27', 'Signed Out', 8, 'CAPSTONE2'),
(812, '220002376', '2025-09-17 00:34:27', 'Present', 3, 'Programming'),
(813, '220002376', '2025-09-17 00:34:48', 'Signed Out', 3, 'Programming'),
(814, '220002376', '2025-09-17 00:34:56', 'Present', 3, 'Programming'),
(815, '220002404', '2025-09-17 00:35:13', 'Signed Out', 3, 'Programming'),
(816, '220002376', '2025-09-17 00:35:23', 'Signed Out', 3, 'Programming'),
(817, '220002403', '2025-09-17 00:35:46', 'Signed Out', 8, 'CAPSTONE2'),
(818, '220002403', '2025-09-17 00:35:46', 'Present', 3, 'Programming'),
(819, '220002403', '2025-09-17 00:36:20', 'Signed Out', 3, 'Programming'),
(820, '220002404', '2025-09-17 00:36:47', 'Present', 3, 'Programming'),
(821, '220002405', '2025-09-17 00:37:04', 'Present', 3, 'Programming'),
(822, '220002404', '2025-09-17 00:44:46', 'Signed Out', 3, 'Programming'),
(823, '220002376', '2025-09-17 00:44:49', 'Present', 3, 'Programming'),
(824, '220002405', '2025-09-17 00:45:06', 'Signed Out', 3, 'Programming'),
(825, '220002405', '2025-09-17 00:46:18', 'Present', 8, 'CAPSTONE2'),
(826, '220002404', '2025-09-17 00:47:22', 'Present', 8, 'CAPSTONE2'),
(827, '220002405', '2025-09-17 00:47:24', 'Signed Out', 8, 'CAPSTONE2'),
(828, '220002376', '2025-09-17 00:50:53', 'Signed Out', 3, 'Programming'),
(829, '220002404', '2025-09-17 00:51:14', 'Signed Out', 8, 'CAPSTONE2'),
(830, '220002376', '2025-09-17 00:52:48', 'Present', 14, 'EPP'),
(831, '220002404', '2025-09-17 00:53:00', 'Present', 14, 'EPP'),
(832, '220002405', '2025-09-17 00:53:03', 'Present', 14, 'EPP'),
(833, '220002404', '2025-09-17 00:53:10', 'Signed Out', 14, 'EPP'),
(834, '220002376', '2025-09-17 00:53:13', 'Signed Out', 14, 'EPP'),
(835, '220002405', '2025-09-17 00:54:29', 'Signed Out', 14, 'EPP'),
(836, '220002405', '2025-09-17 00:54:41', 'Present', 13, 'Abdul'),
(837, '220002405', '2025-09-17 00:54:48', 'Signed Out', 13, 'Abdul');

-- --------------------------------------------------------

--
-- Table structure for table `attendancerecord`
--

CREATE TABLE `attendancerecord` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent') NOT NULL DEFAULT 'Absent',
  `scan_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendancerecord`
--

INSERT INTO `attendancerecord` (`id`, `student_id`, `subject_id`, `date`, `status`, `scan_time`) VALUES
(1, '220002376', 3, '2025-09-03', 'Absent', NULL),
(2, '220002376', 8, '2025-09-03', 'Absent', NULL),
(3, '220002376', 9, '2025-09-03', 'Absent', NULL),
(4, '220002376', 11, '2025-09-03', 'Absent', NULL),
(5, '220002376', 12, '2025-09-03', 'Absent', NULL),
(6, '220002376', 13, '2025-09-03', 'Absent', NULL),
(7, '220002403', 3, '2025-09-03', 'Absent', NULL),
(8, '220002403', 8, '2025-09-03', 'Absent', NULL),
(9, '220002403', 9, '2025-09-03', 'Absent', NULL),
(10, '220002403', 11, '2025-09-03', 'Absent', NULL),
(11, '220002403', 12, '2025-09-03', 'Absent', NULL),
(12, '220002403', 13, '2025-09-03', 'Absent', NULL),
(13, '220002404', 3, '2025-09-03', 'Absent', NULL),
(14, '220002404', 8, '2025-09-03', 'Absent', NULL),
(15, '220002404', 9, '2025-09-03', 'Absent', NULL),
(16, '220002404', 11, '2025-09-03', 'Absent', NULL),
(17, '220002404', 12, '2025-09-03', 'Absent', NULL),
(18, '220002404', 13, '2025-09-03', 'Absent', NULL),
(19, '220002405', 3, '2025-09-03', 'Absent', NULL),
(20, '220002405', 8, '2025-09-03', 'Absent', NULL),
(21, '220002405', 9, '2025-09-03', 'Absent', NULL),
(22, '220002405', 11, '2025-09-03', 'Absent', NULL),
(23, '220002405', 12, '2025-09-03', 'Absent', NULL),
(24, '220002405', 13, '2025-09-03', 'Absent', NULL);

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
(5, '0002', 'Tope', 'A', 'BSIS', 2, '0002', 'student_68764ef3832aa0.84376158.jpg', '12', NULL),
(6, '009', 'Abdul', 'A', 'BSIS', 4, '009', 'student_68764f15d837b7.72079010.jpg', '21', NULL),
(7, '220002404', 'Mercado Roman', 'A', 'BSIS', 3, '220002404', 'student_68b0966ccea994.82703137.jpg', '199', 'Male'),
(8, '220002376', 'Maambong Cristine', 'A', 'BSIS', 3, '220002376', 'student_68b0965e25aff3.08240002.jpg', '12', 'Female'),
(11, '220002409', 'Rambo Rhat', 'A', 'BSIS', 1, '220002409', 'student_68ad5fe7aea6e8.88253050.jpg', '1', 'Male'),
(12, '220002403', 'Panoy Christopher', 'A', 'BSIS', 3, '220002403', 'student_68bb1a3b5791e6.70972431.jpg', '69', 'Male'),
(13, '220002405', 'Popatco Leonel', 'A', 'BSIS', 3, '220002405', 'student_68bc3c3020f1f9.27254734.jpg', '7', 'Male');

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
(37, '220002376', 8, '2025-08-28 17:13:00'),
(39, '220002404', 8, '2025-08-28 17:13:00'),
(42, '220002403', 8, '2025-08-28 17:15:50'),
(44, '220002405', 8, '2025-08-28 18:30:28'),
(45, '220002376', 9, '2025-08-29 02:51:44'),
(46, '220002403', 9, '2025-08-29 02:51:44'),
(47, '220002404', 9, '2025-08-29 02:51:44'),
(48, '220002405', 9, '2025-08-29 02:51:44'),
(49, '220002376', 11, '2025-08-29 02:53:05'),
(50, '220002403', 11, '2025-08-29 02:53:05'),
(51, '220002404', 11, '2025-08-29 02:53:05'),
(52, '220002405', 11, '2025-08-29 02:53:05'),
(53, '220002376', 12, '2025-08-29 02:58:31'),
(54, '220002403', 12, '2025-08-29 02:58:31'),
(55, '220002404', 12, '2025-08-29 02:58:31'),
(56, '220002405', 12, '2025-08-29 02:58:31'),
(57, '220002376', 3, '2025-08-29 04:00:30'),
(58, '220002403', 3, '2025-08-29 04:00:30'),
(59, '220002404', 3, '2025-08-29 04:00:30'),
(60, '220002405', 3, '2025-08-29 04:00:30'),
(61, '220002376', 13, '2025-09-02 14:34:23'),
(62, '220002403', 13, '2025-09-02 14:34:23'),
(63, '220002404', 13, '2025-09-02 14:34:23'),
(64, '220002405', 13, '2025-09-02 14:34:23'),
(65, '220002376', 14, '2025-09-14 19:45:18'),
(66, '220002403', 14, '2025-09-14 19:45:18'),
(67, '220002404', 14, '2025-09-14 19:45:18'),
(68, '220002405', 14, '2025-09-14 19:45:18'),
(69, '220002376', 15, '2025-09-16 08:52:24'),
(70, '220002403', 15, '2025-09-16 08:52:24'),
(71, '220002404', 15, '2025-09-16 08:52:24'),
(72, '220002405', 15, '2025-09-16 08:52:24'),
(73, '220002376', 16, '2025-09-16 12:31:04'),
(74, '220002403', 16, '2025-09-16 12:31:04'),
(75, '220002404', 16, '2025-09-16 12:31:04'),
(76, '220002405', 16, '2025-09-16 12:31:04');

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
(3, '103', 'Programming', 1, 'Mon,Tue,Wed,Sun,Mon,Tue,Wed,Sun', '00:40:00', '00:46:00'),
(8, '002', 'CAPSTONE2', 1, 'Mon', '00:50:00', '00:55:00'),
(9, '101', 'Enterprize System', 1, 'Mon,Wed,Mon,Wed', '00:00:00', '00:25:00'),
(10, '305', 'Enterprise System', 1, NULL, NULL, NULL),
(11, '69', 'Esports Education', 1, 'Mon,Fri,Sun,Mon,Fri,Sun', '02:40:00', '03:00:00'),
(12, 'ML', 'Moblie Legends Bang Bang', 1, 'Mon,Fri,Mon,Fri', '03:00:00', '03:40:00'),
(13, '001', 'Abdul', 1, 'Mon,Mon,Wed', '00:54:00', '02:00:00'),
(14, '143', 'EPP', 1, 'Mon,Mon,Wed', '00:50:00', '00:54:00'),
(15, '99', 'ISO25010', 2, 'Tue,Tue', '18:30:00', '19:00:00'),
(16, '80', 'Filipino', 2, NULL, NULL, NULL);

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
(1, '001', 'Joshua Tiongco', 'joshuationgco@gmail.com', '09309971418', 'College of Computing Studies', 'teacher_68c95bc59c6380.85353378.jpg', '2025-07-15 10:14:00'),
(2, '002', 'Anthony Rivera', 'antmanrivera@gmail.com', '', 'College of Computing Studies', 'teacher_68c924c2d99577.03245043.jpg', '2025-09-16 08:50:10'),
(3, '003', 'Erickson Salunga', 'ems@gmail.com', '', 'College of Computing Studies', 'teacher_68c925e105ecd8.79151399.jpg', '2025-09-16 08:54:57');

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
(2, NULL, 'joshuationgco@gmail.com', '$2y$10$Hzi3GhiKUse7L6UmbghdxORY29dJOdg.K/VjqSt7bVTaVVfHpOHyq', 'teacher', 'Joshua Tiongco', 1),
(3, NULL, 'antmanrivera@gmail.com', '$2y$10$CEyzWls3IWqZwERllcUCVe7.dLmtab7kbxqrHD6yjmzQc8zjyarqq', 'teacher', 'Anthony Rivera', 1),
(4, NULL, 'ems@gmail.com', '$2y$10$.5xN2CsuUhTj9zIhAalrd.wZZ.1EX0crTohoQZYcee3iSn44ojdfW', 'teacher', 'Erickson Salunga', 1);

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
-- Indexes for table `attendancerecord`
--
ALTER TABLE `attendancerecord`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_student_subject_date` (`student_id`,`subject_id`,`date`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=838;

--
-- AUTO_INCREMENT for table `attendancerecord`
--
ALTER TABLE `attendancerecord`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `student_subjects`
--
ALTER TABLE `student_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
