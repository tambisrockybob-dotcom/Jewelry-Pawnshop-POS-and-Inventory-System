-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql309.infinityfree.com
-- Generation Time: Nov 23, 2025 at 04:05 AM
-- Server version: 11.4.7-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_40321175_jewelry_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `created_at`) VALUES
(1, 'John Doe', 'john@example.com', '09171234567', '2025-11-23 02:34:45'),
(2, 'Jane Smith', 'jane@example.com', '09179876543', '2025-11-23 02:34:45'),
(6, 'jay', 'asdasd@gmail.com', '09090909', '2025-11-23 07:32:49');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `weight` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `code`, `name`, `price`, `stock`, `weight`, `description`, `image`, `created_at`) VALUES
(1, 'P001', 'Gold Ring', '1500.00', 9, '5.50', '18K Gold Ring', NULL, '2025-11-23 02:34:45'),
(2, 'P002', 'Silver Necklace', '1200.00', 15, '10.00', 'Sterling Silver Necklace', NULL, '2025-11-23 02:34:45'),
(3, 'P003', 'Diamond Earrings', '5000.00', 9, '2.00', 'Diamond Stud Earrings', NULL, '2025-11-23 02:34:45'),
(4, 'P004', 'Platinum Bracelet', '7000.00', 5, '12.00', 'Platinum Bracelet', NULL, '2025-11-23 02:34:45'),
(5, 'P005', 'Pearl Pendant', '2500.00', 8, '3.00', 'Pearl Pendant Necklace', NULL, '2025-11-23 02:34:45'),
(6, 'P006', 'Gold Chain', '3000.00', 12, '20.00', '18K Gold Chain', NULL, '2025-11-23 02:34:45'),
(7, 'P007', 'Silver Ring', '900.00', 20, '4.00', 'Sterling Silver Ring', NULL, '2025-11-23 02:34:45'),
(8, 'P008', 'Gemstone Brooch', '3500.00', 10, '6.50', 'Sapphire Brooch', NULL, '2025-11-23 02:34:45'),
(9, 'P009', 'Diamond Bracelet', '8000.00', 9, '15.00', 'Diamond Bracelet', NULL, '2025-11-23 02:34:45'),
(10, 'P010', 'Gold Earrings', '2000.00', 8, '3.50', '18K Gold Earrings', NULL, '2025-11-23 02:34:45'),
(12, 'P011', 'Diamond Tennis Bracelet', '12500.00', 12, '18.50', '14K white gold tennis bracelet with brilliant cut diamonds', NULL, '2025-11-23 08:31:06'),
(13, 'P012', 'Emerald Pendant Necklace', '8500.00', 12, '8.00', 'Stunning emerald pendant set in 18K yellow gold', NULL, '2025-11-23 08:31:06'),
(14, 'P013', 'Rose Gold Wedding Band', '3500.00', 15, '6.20', 'Classic 14K rose gold wedding band, 4mm width', NULL, '2025-11-23 08:31:06'),
(15, 'P014', 'Sapphire Stud Earrings', '6500.00', 8, '3.50', 'Natural blue sapphire studs in white gold setting', NULL, '2025-11-23 08:31:06'),
(16, 'P015', 'Pearl Drop Earrings', '4200.00', 12, '4.80', 'Freshwater pearl drop earrings with diamond accents', NULL, '2025-11-23 08:31:06'),
(17, 'P016', 'Infinity Love Ring', '2800.00', 20, '4.50', 'Sterling silver infinity symbol ring with CZ stones', NULL, '2025-11-23 08:31:06'),
(18, 'P017', 'Charm Bracelet', '5500.00', 10, '15.00', '18K gold charm bracelet with detachable charms', NULL, '2025-11-23 08:31:06'),
(19, 'P018', 'Men\'s Signet Ring', '4500.00', 7, '12.00', 'Classic men\'s signet ring in 14K yellow gold', NULL, '2025-11-23 08:31:06'),
(20, 'P019', 'Ruby Heart Pendant', '7800.00', 6, '5.50', 'Heart-shaped ruby pendant with white gold chain', NULL, '2025-11-23 08:31:06'),
(21, 'P020', 'Anniversary Band Ring', '5200.00', 9, '7.00', 'Diamond anniversary band in platinum setting', NULL, '2025-11-23 08:31:06');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_no`, `customer_id`, `total`, `created_at`) VALUES
(1, 'INV1763870821', 5, '8000.00', '2025-11-23 04:07:01'),
(2, 'INV1763873498', 2, '8000.00', '2025-11-23 04:51:38'),
(3, 'INV20251123000439', NULL, '14000.00', '2025-11-23 05:04:38'),
(4, 'INV20251123000448', NULL, '14000.00', '2025-11-23 05:04:48'),
(5, 'INV20251123000459', NULL, '14000.00', '2025-11-23 05:04:59'),
(6, 'INV20251123000636', NULL, '14000.00', '2025-11-23 05:06:36'),
(7, 'INV20251123000750', NULL, '8000.00', '2025-11-23 05:07:50'),
(8, 'INV20251123000959', NULL, '3000.00', '2025-11-23 05:09:59'),
(9, 'INV20251123014847', NULL, '24000.00', '2025-11-23 06:48:47'),
(10, 'INV20251123014904', NULL, '2000.00', '2025-11-23 06:49:04'),
(11, 'INV20251123014921', 5, '4000.00', '2025-11-23 06:49:21'),
(12, 'INV20251123015033', NULL, '7000.00', '2025-11-23 06:50:33'),
(13, 'INV20251123023553', 6, '13000.00', '2025-11-23 07:35:53'),
(14, 'INV20251123031355', NULL, '1500.00', '2025-11-23 08:13:55'),
(15, 'INV20251123032243', NULL, '7000.00', '2025-11-23 08:22:43');

-- --------------------------------------------------------

--
-- Table structure for table `sales_items`
--

CREATE TABLE `sales_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sales_items`
--

INSERT INTO `sales_items` (`id`, `sale_id`, `product_id`, `quantity`, `price`) VALUES
(1, 9, 3, 1, '5000.00'),
(2, 9, 8, 2, '3500.00'),
(3, 9, 6, 4, '3000.00'),
(4, 10, 10, 1, '2000.00'),
(5, 11, 10, 2, '2000.00'),
(6, 12, 4, 1, '7000.00');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `price`, `created_at`) VALUES
(1, 1, 9, 1, '8000.00', '2025-11-23 04:07:01'),
(2, 2, 9, 1, '8000.00', '2025-11-23 04:51:38'),
(3, 13, 9, 1, '8000.00', '2025-11-23 07:35:53'),
(4, 13, 3, 1, '5000.00', '2025-11-23 07:35:53'),
(5, 14, 1, 1, '1500.00', '2025-11-23 08:13:55'),
(6, 15, 4, 1, '7000.00', '2025-11-23 08:22:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'admin'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`) VALUES
(1, 'admin', '$2y$10$69/erDOfDG9q6NFMAJSHPukFE85QejVWQvIgXrebXlsGPqKC8Ern6', 'Administrator', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

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
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `sales_items`
--
ALTER TABLE `sales_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
