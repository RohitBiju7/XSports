-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 03, 2025 at 06:22 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `xsports`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
CREATE TABLE IF NOT EXISTS `addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `address_line` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pincode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `selected` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `address_line`, `city`, `state`, `pincode`, `selected`) VALUES
(1, 1, 'A 10, Ganga Nagar, Kudappanakunnu PO', 'Trivandrum', 'Kerala', '695043', 0),
(2, 1, 'Kalluzhathil House, Njaliyakuzhy', 'Kottayam', 'Kerala', '686538', 1),
(3, 2, 'Pathilchirayil House, Manaarkunnu', 'Kottayam', 'Kerala', '686562', 1);

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password`) VALUES
(1, 'admin@xsports.com', '$2y$10$.qzMEH5Z/mQQL5RCV1BgM.ga6QxOBK/PtdWHq19O/FCx0tjbhicKq');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE IF NOT EXISTS `cart_items` (
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_id`,`product_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `address_id` int DEFAULT NULL,
  `payment_method` enum('cod','card','upi') DEFAULT 'cod',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `shipping` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('placed','processing','shipped','delivered','cancelled') DEFAULT 'placed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `address_id`, `payment_method`, `subtotal`, `shipping`, `tax`, `total`, `status`, `created_at`) VALUES
(6, 1, 2, 'cod', 1099.00, 50.00, 197.82, 1346.82, 'placed', '2025-09-17 08:15:48'),
(5, 1, 1, 'cod', 52396.00, 50.00, 9431.28, 61877.28, 'placed', '2025-09-17 08:15:18');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `brand`, `price`, `quantity`, `image_path`) VALUES
(6, 5, 2, 'Shoes', 'Nike', 2400.00, 20, 'images/products/68bd89dc8f5852.92689394.jpg'),
(7, 5, 3, 'Football Ball Size 5 F550 - White', 'KIPSTA', 1099.00, 4, 'images/products/68ca54fa1331b8.86109408.jpg'),
(8, 6, 3, 'Football Ball Size 5 F550 - White', 'KIPSTA', 1099.00, 1, 'images/products/68ca54fa1331b8.86109408.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `quantity` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `brand`, `price`, `image_path`, `category`, `description`, `quantity`, `created_at`) VALUES
(2, 'Shoes', 'Nike', 2400.00, 'images/products/68bd89dc8f5852.92689394.jpg', 'Running', 'asfasfasf', 100, '2025-08-21 08:43:11'),
(3, 'Football Ball Size 5 F550 - White', 'KIPSTA', 1099.00, 'images/products/68ca54fa1331b8.86109408.jpg', 'Football', 'The F550 hybrid has been approved by FIFA for your training sessions and matches. We\'ve designed it to give the perfect balance between durability and feel', 95, '2025-09-17 06:28:10');

-- --------------------------------------------------------

--
-- Table structure for table `support_messages`
--

DROP TABLE IF EXISTS `support_messages`;
CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `sender_type` enum('user','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `support_messages`
--

INSERT INTO `support_messages` (`id`, `ticket_id`, `sender_type`, `message`, `created_at`) VALUES
(19, 7, 'admin', 'asd', '2025-07-20 20:03:35'),
(18, 8, 'user', 'afs', '2025-07-20 20:02:28'),
(17, 8, 'user', 'faf', '2025-07-20 20:02:20'),
(16, 7, 'user', 'asfa', '2025-07-20 20:02:17'),
(15, 6, 'user', 'asd', '2025-07-20 20:02:15'),
(14, 5, 'user', 'asdasda', '2025-07-20 20:02:10'),
(20, 9, 'user', 'boobs', '2025-09-17 08:18:18'),
(21, 9, 'user', 'asfasfasf', '2025-09-17 08:18:25'),
(22, 9, 'admin', 'afasf2', '2025-09-17 08:18:50'),
(23, 9, 'admin', 'as', '2025-09-17 08:18:53');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','active','resolved') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_id`, `subject`, `description`, `status`, `created_at`, `updated_at`) VALUES
(8, 1, '123', 'faf', 'active', '2025-07-20 20:02:20', '2025-07-20 20:13:34'),
(7, 1, '2131', 'asfa', 'resolved', '2025-07-20 20:02:17', '2025-07-20 20:09:57'),
(6, 1, 'testse', 'asd', 'active', '2025-07-20 20:02:15', '2025-07-20 20:18:45'),
(5, 1, 'Testing 1', 'asdasda', 'pending', '2025-07-20 20:02:10', '2025-07-20 20:02:10'),
(9, 2, 'Help ME plz', 'boobs', 'resolved', '2025-09-17 08:18:18', '2025-09-17 08:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `phone`, `created_at`) VALUES
(1, 'rohitbiju2001@gmail.com', '$2y$10$SR5Rid1rC1hoo/dlTMoNCO4u.GgFNfh7lUbYlgUb44Ca1w9IpgzgW', 'Rohit Biju', '9447892551', '2025-07-20 16:31:39'),
(2, 'alwinarun@gmail.com', '$2y$10$Woq3.ol/fnfIL1U8MKWAUOgVn7oXrbp006FjTb7zk8gyx0ExSULne', 'Alwin Arun', NULL, '2025-09-17 07:18:41');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE IF NOT EXISTS `wishlist` (
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`product_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`user_id`, `product_id`) VALUES
(1, 2);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
