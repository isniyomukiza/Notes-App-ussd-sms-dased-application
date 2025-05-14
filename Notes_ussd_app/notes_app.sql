-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2025 at 10:35 AM
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
-- Database: `notes_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `user_id`, `title`, `content`, `created_at`) VALUES
(9, 11, 'Gatete', 'Patrick', '2025-05-12 22:56:29'),
(11, 14, 'asdfg', '123', '2025-05-13 00:29:27'),
(12, 14, 'asdfg', '123', '2025-05-13 00:29:38'),
(13, 14, 'abcd', 'efghijklmn', '2025-05-13 09:31:44'),
(14, 14, 'rtt', 'poiuyttr', '2025-05-13 09:36:31'),
(15, 14, 'rtt', 'reetegbnh1', '2025-05-13 09:38:28'),
(16, 14, 'rtt', 'reetegbnh1', '2025-05-13 10:01:43'),
(17, 14, 'az', 'qwertyuiopl', '2025-05-13 11:27:50'),
(18, 14, 'az', 'qwertyuiopl', '2025-05-13 11:29:09'),
(19, 14, 'az', 'qwertyuioplnnnmm', '2025-05-13 11:29:15'),
(21, 17, 'My papa', 'klmn', '2025-05-13 16:42:30'),
(22, 17, 'my father', 'all of me', '2025-05-14 10:02:50');

-- --------------------------------------------------------

--
-- Table structure for table `shared_notes`
--

CREATE TABLE `shared_notes` (
  `id` int(11) NOT NULL,
  `note_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `shared_with_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shared_notes`
--

INSERT INTO `shared_notes` (`id`, `note_id`, `owner_id`, `shared_with_id`, `created_at`) VALUES
(2, 13, 14, 13, '2025-05-13 09:32:42'),
(4, 21, 17, 13, '2025-05-13 16:42:30'),
(5, 22, 17, 13, '2025-05-14 10:02:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `phone`, `password`, `created_at`) VALUES
(11, 'pp', '+250789105167', '$2y$10$mLpgfL3cd6sJ78/0WJPNe.vLsSoQPCtXb8rP9aRTxR6ZKpg8SE1oe', '2025-05-12 22:52:06'),
(13, 'Ismael', '+250784844687', '$2y$10$yJCQFufGSBkcebf6fP3cMeJqajrqxwNCfy5vQF1crxGMrkh71FREa', '2025-05-12 23:02:39'),
(14, 'asdf', '+250783033359', '$2y$10$73mZuSobIF1DXENBwZ/1T.ovjTtKQTvq5WG046BRJ/P4vkc3tj/m2', '2025-05-13 00:24:22'),
(17, 'Papa', '+250786540830', '$2y$10$XBZUQcZzjoXjlUMybja0t.Nl30kjbPkXZbHNzZGFE3CZGph71aadm', '2025-05-13 16:08:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `shared_notes`
--
ALTER TABLE `shared_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_share` (`note_id`,`shared_with_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `shared_with_id` (`shared_with_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `shared_notes`
--
ALTER TABLE `shared_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shared_notes`
--
ALTER TABLE `shared_notes`
  ADD CONSTRAINT `shared_notes_ibfk_1` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shared_notes_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shared_notes_ibfk_3` FOREIGN KEY (`shared_with_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
