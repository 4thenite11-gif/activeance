-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2026 at 10:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `activeance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `make_model` varchar(100) NOT NULL,
  `year` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `waiting_period` varchar(100) DEFAULT NULL,
  `shipment_status` enum('In Stock','In Transit','Arriving Soon') DEFAULT 'In Stock',
  `status` enum('Available','Sold') DEFAULT 'Available',
  `sold_at` datetime DEFAULT NULL,
  `sold_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `make_model`, `year`, `price`, `image_url`, `waiting_period`, `shipment_status`, `status`, `sold_at`, `sold_price`) VALUES
(1, 'Volkswagen Golf', 2020, 250000.00, 'public/car_1793845689_39201c.png', NULL, 'In Stock', 'Available', NULL, NULL),
(6, 'Honda Fit', 2018, 180000.00, 'public/car1.jpg', NULL, 'In Stock', 'Available', NULL, NULL),
(8, 'Mercedes-Benz C-Class', 2019, 450000.00, 'public/car2.jpg', NULL, 'In Stock', 'Available', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text NOT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `business_name` varchar(150) NOT NULL DEFAULT 'Activeance Investment CC',
  `tagline` varchar(255) NOT NULL DEFAULT 'PREMIUM PRE-OWNED VEHICLES',
  `quote_text` varchar(500) NOT NULL DEFAULT '',
  `logo_url` varchar(255) NOT NULL DEFAULT 'public/logo.png',
  `owner_photo_url` varchar(255) DEFAULT 'public/owner.jpg',
  `owner_name` varchar(100) DEFAULT 'Mr. A Muxinga',
  `owner_bio` text DEFAULT NULL,
  `color_primary` varchar(7) NOT NULL DEFAULT '#D62828',
  `color_dark` varchar(7) NOT NULL DEFAULT '#0B132B',
  `color_accent` varchar(7) NOT NULL DEFAULT '#25D366',
  `phone` varchar(50) DEFAULT '+264814540060',
  `stat_years` varchar(20) DEFAULT '10+',
  `stat_cars` varchar(20) DEFAULT '500+',
  `stat_inspected` varchar(20) DEFAULT '100%',
  `stat_support` varchar(20) DEFAULT '24/7',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `value1_title` varchar(100) DEFAULT 'Honest Inspection',
  `value1_desc` text DEFAULT NULL,
  `value2_title` varchar(100) DEFAULT 'Fair Pricing',
  `value2_desc` text DEFAULT NULL,
  `value3_title` varchar(100) DEFAULT 'Direct Communication',
  `value3_desc` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `business_name`, `tagline`, `quote_text`, `logo_url`, `owner_photo_url`, `owner_name`, `owner_bio`, `color_primary`, `color_dark`, `color_accent`, `phone`, `stat_years`, `stat_cars`, `stat_inspected`, `stat_support`, `updated_at`, `value1_title`, `value1_desc`, `value2_title`, `value2_desc`, `value3_title`, `value3_desc`) VALUES
(1, 'Activeance Investment CC', 'PREMIUM PRE-OWNED VEHICLES', 'Driven by trust. Delivered with care.', 'public/logo.png', 'public/owner.jpg', 'Mr. Alberto Muxinga', 'Mr. Muxinga founded Activeance Investment CC with a vision to provide high-quality vehicles to Namibia.', '#d62828', '#0b132b', '#25d366', '+264814540060', '2', '3', '100%', '24/7', '2026-11-05 03:39:32', 'Honest Inspection', '', 'Fair Pricing', '', 'Direct Communication', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`) VALUES
(1, 'admin', '$2y$10$LcJYILUCb.wL38XZPd.Wo.sTW8NToN4A.ZNZGhW5ftNLcoRxW0QRO');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
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
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
