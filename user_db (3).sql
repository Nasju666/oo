-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2026 at 07:16 PM
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
-- Database: `user_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`) VALUES
(1, 'nasju', 'asdas@gmail.com', '$2y$10$Qao0Io9soxl0/e8ceFUyp.swRUpxzjBnhK6Cpi5SISWVrb/Q5/84K'),
(2, 'nasju666', '12@gmail.com', '$2y$10$/YlQbFDYVMfvUuXQ.QUtmut3CwvpVYZgHM8ekUJ2mLK63TMISOSMe'),
(3, 'nasju008', 'amanencejunas42@gmail.com', '$2y$10$rHbca3d1qXIUjAFAhDA5hOSvjLnFvaQiBPHEp4KvyJH4cyfcl8a8W'),
(5, 'nasu', 'sadasd@gmail.com', '$2y$10$gNXlcpEEwtoqE02kKaz3t.2Sa0XqyVXNZ7e97GlMaLUXjAvv2bwh6'),
(6, 'nasw', 'eqweq@gmail.com', '$2y$10$w2zdDgFdH07PLDuOX6hG4uu84c/hU5Pvd90fhXHjWV6C3.sWHOEcy');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
