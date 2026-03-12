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
-- Database: `db_product`
--

-- --------------------------------------------------------

--
-- Table structure for table `tblexpense`
--

CREATE TABLE `tblexpense` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `expense_category` varchar(100) NOT NULL,
  `expense_description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `notes` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblexpense`
--

INSERT INTO `tblexpense` (`id`, `user_id`, `date`, `expense_category`, `expense_description`, `amount`, `notes`) VALUES
(1, 6, '2026-03-11 15:26:22', 'Inventory Restock', 'Soft drinks & chips (Coke, Piattos, Nova)', 1000.00, 'Plete'),
(2, 3, '2026-03-12 16:03:51', 'Inventory Restock', 'Soft drinks & chips (Coke, Piattos, Nova)', 100.00, 'Plete');

-- --------------------------------------------------------

--
-- Table structure for table `tblproduct`
--

CREATE TABLE `tblproduct` (
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblproduct`
--

INSERT INTO `tblproduct` (`product_id`, `user_id`, `product_name`, `category`, `cost_price`, `selling_price`, `stock`, `created_at`) VALUES
(3, 0, 'Gas', 'yawa', 60.00, 100.00, 979, '2026-03-11 14:45:29'),
(4, 3, 'Piatos', 'Snacks', 10.00, 25.00, 2000, '2026-03-11 15:14:39'),
(6, 6, 'Piatos', 'Snacks', 10.00, 20.00, 150, '2026-03-11 15:45:44'),
(7, 3, 'Tanduay', 'yawa', 100.00, 125.00, 90, '2026-03-12 16:10:08');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_quantity` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `received` decimal(10,2) DEFAULT NULL,
  `status` enum('completed','insufficient') DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `date`, `total_quantity`, `total_price`, `received`, `status`) VALUES
(80, 1, '2025-04-07 12:53:17', 1, 10.00, 15.00, 'completed'),
(81, 1, '2025-04-07 12:53:39', 2, 20.00, 33.00, 'completed'),
(82, 0, '2026-03-11 14:48:29', 20, 2000.00, 3023.00, 'completed'),
(83, 3, '2026-03-11 15:18:45', 4, 100.00, 300.00, 'completed'),
(84, 3, '2026-03-11 15:20:36', 1, 25.00, 30.00, 'completed'),
(85, 6, '2026-03-11 15:22:43', 1, 75.00, 100.00, 'completed'),
(86, 6, '2026-03-11 15:27:31', 4, 340.00, 500.00, 'completed'),
(87, 6, '2026-03-11 15:28:24', 7, 595.00, 600.00, 'completed'),
(88, 3, '2026-03-12 16:10:28', 2, 250.00, 300.00, 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_details`
--

CREATE TABLE `transaction_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_details`
--

INSERT INTO `transaction_details` (`id`, `user_id`, `transaction_id`, `product_id`, `product_name`, `category`, `price`, `quantity`, `subtotal`) VALUES
(2, 1, 80, 1, 'fish cracker', 'Snacks', 10.00, 1, 10.00),
(3, 1, 81, 1, 'fish cracker', 'Snacks', 10.00, 2, 20.00),
(4, 0, 82, 3, 'Gas', 'yawa', 100.00, 20, 2000.00),
(5, 3, 83, 4, 'Piatos', 'Snacks', 25.00, 4, 100.00),
(6, 3, 84, 4, 'Piatos', 'Snacks', 25.00, 1, 25.00),
(7, 6, 85, NULL, NULL, 'sh8', 75.00, 1, 75.00),
(8, 6, 86, NULL, NULL, 'sh8', 85.00, 4, 340.00),
(9, 6, 87, NULL, NULL, 'sh8', 85.00, 7, 595.00),
(10, 3, 88, 7, 'Tanduay', 'yawa', 125.00, 2, 250.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblexpense`
--
ALTER TABLE `tblexpense`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_expense` (`user_id`,`id`);

--
-- Indexes for table `tblproduct`
--
ALTER TABLE `tblproduct`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_user_product` (`user_id`,`product_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_transaction` (`user_id`,`id`);

--
-- Indexes for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_user_details` (`user_id`,`transaction_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblexpense`
--
ALTER TABLE `tblexpense`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tblproduct`
--
ALTER TABLE `tblproduct`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `transaction_details`
--
ALTER TABLE `transaction_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD CONSTRAINT `transaction_details_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
