-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 21, 2025 at 04:57 PM
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
-- Database: `ban_dien_thoai`
--

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `description`, `logo`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Apple', 'apple', 'Apple Inc. - iPhone series', NULL, 'active', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(2, 'Samsung', 'samsung', 'Samsung Electronics - Galaxy series', NULL, 'active', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(3, 'Xiaomi', 'xiaomi', 'Xiaomi Corporation - Mi series', NULL, 'active', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(4, 'OPPO', 'oppo', 'OPPO Electronics - Find, Reno, A series', NULL, 'active', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(5, 'Vivo', 'vivo', 'Vivo Mobile - X, V, Y series', NULL, 'active', '2025-05-21 12:26:50', '2025-05-21 12:26:50');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Điện thoại', 'dien-thoai', 'Các loại điện thoại di động', NULL, NULL, 'active', '2025-05-21 12:26:50', '2025-05-21 12:26:50');

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `question`, `answer`, `is_active`, `updated_at`, `sort_order`, `status`, `created_at`) VALUES
(1, 'Làm thế nào để đặt hàng?', 'Bạn có thể đặt hàng trực tuyến thông qua website hoặc gọi điện thoại đến hotline của chúng tôi.', 1, '2025-06-18 00:10:11', 1, 'active', '2025-05-21 12:26:50'),
(3, 'Có chính sách đổi trả không?', 'Chúng tôi có chính sách đổi trả trong vòng 7 ngày nếu sản phẩm có lỗi từ nhà sản xuất.', 1, '2025-05-25 14:07:06', 3, 'active', '2025-05-21 12:26:50'),
(4, 'Có bảo hành không?', 'Tất cả sản phẩm đều được bảo hành chính hãng từ 12-24 tháng tùy theo sản phẩm.', 1, '2025-05-25 14:07:17', 4, 'active', '2025-05-21 12:26:50');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order','system','promotion') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','completed','cancelled') DEFAULT 'pending',
  `user_confirmed_at` datetime DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `shipping_phone` varchar(20) NOT NULL,
  `shipping_name` varchar(100) NOT NULL,
  `shipping_email` varchar(100) DEFAULT NULL,
  `payment_method` enum('cod','banking','momo','zalopay') NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `order_status` varchar(50) DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_price`, `status`, `user_confirmed_at`, `shipping_address`, `shipping_phone`, `shipping_name`, `shipping_email`, `payment_method`, `payment_status`, `order_status`, `note`, `created_at`, `updated_at`, `notes`) VALUES
(5, 8, 99999999.99, 'shipped', NULL, 'ap 6 go dau tay ninh', '0384171558', 'thanhh', NULL, 'banking', 'paid', 'pending', NULL, '2025-05-24 13:37:13', '2025-06-18 00:20:40', ''),
(9, 6, 29990000.00, '', NULL, 'ap 6 go dau tay ninh', '0384171558', 'thanh', NULL, 'banking', 'pending', 'pending', NULL, '2025-05-26 13:44:07', '2025-06-21 14:45:28', ''),
(12, 8, 24990000.00, 'pending', NULL, 'ap 6 go dau tay ninh', '0384171558', 'thanhh', NULL, 'banking', 'pending', 'pending', NULL, '2025-05-29 03:30:11', '2025-06-18 00:20:35', NULL),
(13, 6, 64980000.00, 'completed', '2025-06-21 20:31:34', 'ap 6 go dau tay ninh', '0384171558', 'thanh', NULL, 'cod', 'paid', 'pending', NULL, '2025-06-21 13:24:15', '2025-06-21 13:31:34', ''),
(14, 6, 24990000.00, 'pending', NULL, 'ap 6 go dau tay ninh', '0384171558', 'thanh', NULL, 'banking', 'pending', 'pending', NULL, '2025-06-21 14:43:17', '2025-06-21 14:45:26', '');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `quantity`, `price`, `created_at`) VALUES
(7, 5, 6, 2, 32990000.00, '2025-05-24 13:37:13'),
(8, 5, 1, 3, 34990000.00, '2025-05-24 13:37:13'),
(12, 9, 2, 1, 29990000.00, '2025-05-26 13:44:07'),
(15, 12, 11, 1, 24990000.00, '2025-05-29 03:30:11'),
(16, 13, 1, 1, 34990000.00, '2025-06-21 13:24:15'),
(17, 13, 2, 1, 29990000.00, '2025-06-21 13:24:15'),
(18, 14, 7, 1, 24990000.00, '2025-06-21 14:43:17');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` int(10) UNSIGNED NOT NULL,
  `sale_price` int(10) UNSIGNED DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `specifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specifications`)),
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `description`, `price`, `sale_price`, `stock`, `image`, `brand_id`, `category_id`, `sku`, `specifications`, `status`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'iPhone 15 Pro Max với chip A17 Pro, camera 48MP', 34990000, 34000000, 42, 'products/iphone-15-pro-max.jpg', 1, 1, 'APP-15PM-256', '{\"screen\": \"6.7 inch Super Retina XDR\", \"ram\": \"8GB\", \"storage\": \"256GB\", \"camera\": \"48MP + 12MP + 12MP\", \"battery\": \"4422mAh\"}', 'active', '2025-05-21 12:26:50', '2025-06-21 13:24:15', 1),
(2, 'iPhone 15 Pro', 'iphone-15-pro', 'iPhone 15 Pro với chip A17 Pro, camera 48MP', 29990000, 28990000, 43, 'products/iphone-15-pro.jpg', 1, 1, 'APP-15P-256', '{\"screen\": \"6.1 inch Super Retina XDR\", \"ram\": \"8GB\", \"storage\": \"256GB\", \"camera\": \"48MP + 12MP + 12MP\", \"battery\": \"3650mAh\"}', 'active', '2025-05-21 12:26:50', '2025-06-21 13:24:15', 1),
(3, 'iPhone 15 Plus', 'iphone-15-plus', 'iPhone 15 Plus với chip A16, camera 48MP', 24990000, 23990000, 40, 'products/iphone-15-plus.jpg', 1, 1, 'APP-15P-128', '{\"screen\": \"6.7 inch Super Retina XDR\", \"ram\": \"6GB\", \"storage\": \"128GB\", \"camera\": \"48MP + 12MP\", \"battery\": \"4383mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 14:56:58', 1),
(4, 'iPhone 15', 'iphone-15', 'iPhone 15 với chip A16, camera 48MP', 19990000, 18990000, 35, 'products/iphone-15.jpg', 1, 1, 'APP-15-128', '{\"screen\": \"6.1 inch Super Retina XDR\", \"ram\": \"6GB\", \"storage\": \"128GB\", \"camera\": \"48MP + 12MP\", \"battery\": \"3349mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 14:57:05', 1),
(5, 'iPhone SE (2022)', 'iphone-se-2022', 'iPhone SE (2022) với chip A15 Bionic', 12990000, 11990000, 30, 'products/iphone-se-2022.jpg', 1, 1, 'APP-SE3-128', '{\"screen\": \"4.7 inch Retina HD\", \"ram\": \"4GB\", \"storage\": \"128GB\", \"camera\": \"12MP\", \"battery\": \"2018mAh\"}', 'active', '2025-05-21 12:26:50', '2025-06-17 05:44:31', 1),
(6, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Galaxy S24 Ultra với chip Snapdragon 8 Gen 3', 32990000, 31990000, 43, 'products/samsung-s24-ultra.jpg', 2, 1, 'SAM-S24U-256', '{\"screen\": \"6.8 inch Dynamic AMOLED 2X\", \"ram\": \"12GB\", \"storage\": \"512GB\", \"camera\": \"200MP + 12MP + 10MP + 50MP\", \"battery\": \"5000mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-24 13:37:13', 1),
(7, 'Samsung Galaxy S24+', 'samsung-galaxy-s24-plus', 'Galaxy S24+ với chip Snapdragon 8 Gen 3', 24990000, 23990000, 39, 'products/samsung-s24-plus.jpg', 2, 1, 'SAM-S24P-256', '{\"screen\": \"6.7 inch Dynamic AMOLED 2X\", \"ram\": \"12GB\", \"storage\": \"256GB\", \"camera\": \"50MP + 12MP + 10MP\", \"battery\": \"4900mAh\"}', 'active', '2025-05-21 12:26:50', '2025-06-21 14:43:17', 1),
(8, 'Samsung Galaxy S24', 'samsung-galaxy-s24', 'Galaxy S24 với chip Snapdragon 8 Gen 3', 19990000, 18990000, 35, 'products/samsung-s24.jpg', 2, 1, 'SAM-S24-128', '{\"screen\": \"6.2 inch Dynamic AMOLED 2X\", \"ram\": \"8GB\", \"storage\": \"128GB\", \"camera\": \"50MP + 12MP + 10MP\", \"battery\": \"4000mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 14:57:38', 1),
(9, 'Samsung Galaxy Z Fold5', 'samsung-galaxy-z-fold5', 'Galaxy Z Fold5 màn hình gập', 34990000, 33990000, 29, 'products/samsung-z-fold5.jpg', 2, 1, 'SAM-ZF5-512', '{\"screen\": \"7.6 inch Dynamic AMOLED 2X\", \"ram\": \"12GB\", \"storage\": \"512GB\", \"camera\": \"50MP + 12MP + 10MP\", \"battery\": \"4400mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-25 14:05:58', 1),
(10, 'Samsung Galaxy Z Flip5', 'samsung-galaxy-z-flip5', 'Galaxy Z Flip5 màn hình gập', 25990000, 24990000, 25, 'products/samsung-z-flip5.jpg', 2, 1, 'SAM-ZF5-256', '{\"screen\": \"6.7 inch Dynamic AMOLED 2X (main)\", \"ram\": \"8GB\", \"storage\": \"256GB\", \"camera\": \"12MP + 12MP\", \"battery\": \"3700mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 14:57:56', 1),
(11, 'Xiaomi 14 Ultra', 'xiaomi-14-ultra', 'Xiaomi 14 Ultra với chip Snapdragon 8 Gen 3', 24990000, 23990000, 24, 'products/xiaomi-14-ultra.jpg', 3, 1, 'XIA-14U-512', '{\"screen\": \"6.73 inch AMOLED\", \"ram\": \"16GB\", \"storage\": \"512GB\", \"camera\": \"50MP + 50MP + 50MP\", \"battery\": \"5000mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-29 03:30:11', 1),
(12, 'Xiaomi 14 Pro', 'xiaomi-14-pro', 'Xiaomi 14 Pro với chip Snapdragon 8 Gen 3', 19990000, 18990000, 30, 'products/xiaomi-14-pro.jpg', 3, 1, 'XIA-14P-256', '{\"screen\": \"6.73 inch AMOLED\", \"ram\": \"12GB\", \"storage\": \"256GB\", \"camera\": \"50MP + 50MP + 50MP\", \"battery\": \"4880mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 14:56:21', 1),
(13, 'Xiaomi 14', 'xiaomi-14', 'Xiaomi 14 với chip Snapdragon 8 Gen 3', 15990000, 14990000, 35, 'products/xiaomi-14.jpg', 3, 1, 'XIA-14-256', '{\"screen\": \"6.36 inch AMOLED\", \"ram\": \"12GB\", \"storage\": \"256GB\", \"camera\": \"50MP + 50MP + 50MP\", \"battery\": \"4610mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 14:58:13', 1),
(14, 'Xiaomi 13T Pro', 'xiaomi-13t-pro', 'Xiaomi 13T Pro với chip Dimensity 9200+', 14990000, 13990000, 40, 'products/xiaomi-13t-pro.jpg', 3, 1, 'XIA-13TP-256', '{\"screen\": \"6.67 inch AMOLED\", \"ram\": \"12GB\", \"storage\": \"256GB\", \"camera\": \"50MP + 50MP + 12MP\", \"battery\": \"5000mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 14:58:45', 1),
(15, 'Xiaomi 13T', 'xiaomi-13t', 'Xiaomi 13T với chip Dimensity 8200 Ultra', 11990000, 10990000, 45, 'products/xiaomi-13t.jpg', 3, 1, 'XIA-13T-256', '{\"screen\": \"6.67 inch AMOLED\", \"ram\": \"12GB\", \"storage\": \"256GB\", \"camera\": \"50MP + 50MP + 12MP\", \"battery\": \"5000mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 14:59:16', 1),
(16, 'OPPO Find X7 Ultra', 'oppo-find-x7-ultra', 'OPPO Find X7 Ultra với camera Hasselblad thế hệ mới', 29990000, 28990000, 28, 'products/oppo-find-x7-ultra.jpg', 4, 1, 'OPP-FX7U-512', '{\"screen\": \"6.82 inch AMOLED\", \"ram\": \"12GB\", \"storage\": \"512GB\", \"camera\": \"50MP + 50MP + 50MP + 50MP\", \"battery\": \"5000mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 14:59:46', 1),
(17, 'OPPO Reno11 5G', 'oppo-reno11-5g', 'OPPO Reno11 5G thiết kế sang trọng', 11990000, 10990000, 35, 'products/oppo-reno11-5g.jpg', 4, 1, 'OPP-R11-256', '{\"screen\": \"6.7 inch AMOLED\", \"ram\": \"8GB\", \"storage\": \"256GB\", \"camera\": \"50MP + 32MP + 8MP\", \"battery\": \"4800mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 15:00:10', 1),
(18, 'OPPO A98 5G', 'oppo-a98-5g', 'OPPO A98 5G hiệu năng ổn định', 8490000, 7990000, 40, 'products/oppo-a98-5g.jpg', 4, 1, 'OPP-A98-256', '{\"screen\": \"6.72 inch LCD\", \"ram\": \"8GB\", \"storage\": \"256GB\", \"camera\": \"64MP + 2MP + 2MP\", \"battery\": \"5000mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 15:00:31', 1),
(19, 'OPPO A78 4G', 'oppo-a78-4g', 'OPPO A78 4G sạc nhanh SuperVOOC', 6490000, 5990000, 50, 'products/oppo-a78-4g.jpg', 4, 1, 'OPP-A78-128', '{\"screen\": \"6.43 inch AMOLED\", \"ram\": \"8GB\", \"storage\": \"128GB\", \"camera\": \"50MP + 2MP\", \"battery\": \"5000mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 15:01:28', 1),
(20, 'OPPO A58 4G', 'oppo-a58-4g', 'OPPO A58 4G pin trâu', 4990000, 4590000, 55, 'products/oppo-a58-4g.jpg', 4, 1, 'OPP-A58-128', '{\"screen\": \"6.72 inch LCD\", \"ram\": \"6GB\", \"storage\": \"128GB\", \"camera\": \"50MP + 2MP\", \"battery\": \"5000mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 15:02:00', 1),
(21, 'Vivo X100 Pro', 'vivo-x100-pro', 'Vivo X100 Pro với camera Zeiss', 26990000, 25990000, 28, 'products/vivo-x100-pro.jpg', 5, 1, 'VIV-X100P-512', '{\"screen\": \"6.78 inch AMOLED\", \"ram\": \"16GB\", \"storage\": \"512GB\", \"camera\": \"50MP + 50MP + 50MP\", \"battery\": \"5400mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 15:02:39', 1),
(22, 'Vivo V29 5G', 'vivo-v29-5g', 'Vivo V29 5G camera chân dung 50MP', 11990000, 10990000, 38, 'products/vivo-v29-5g.jpg', 5, 1, 'VIV-V29-256', '{\"screen\": \"6.78 inch AMOLED\", \"ram\": \"12GB\", \"storage\": \"256GB\", \"camera\": \"50MP + 8MP + 2MP\", \"battery\": \"4600mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 15:03:10', 1),
(23, 'Vivo Y100 5G', 'vivo-y100-5g', 'Vivo Y100 5G pin trâu, sạc nhanh', 7990000, 7490000, 55, 'products/vivo-y100-5g.jpg', 5, 1, 'VIV-Y100-128', '{\"screen\": \"6.67 inch AMOLED\", \"ram\": \"8GB\", \"storage\": \"128GB\", \"camera\": \"50MP + 2MP + 2MP\", \"battery\": \"5000mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 15:03:44', 1),
(24, 'Vivo Y36 5G', 'vivo-y36-5g', 'Vivo Y36 5G thiết kế đẹp', 7490000, 6990000, 48, 'products/vivo-y36-5g.jpg', 5, 1, 'VIV-Y36-128', '{\"screen\": \"6.64 inch LCD\", \"ram\": \"8GB\", \"storage\": \"128GB\", \"camera\": \"50MP + 2MP\", \"battery\": \"5000mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 15:04:14', 1),
(25, 'Vivo Y03', 'vivo-y03', 'Vivo Y03 giá rẻ', 2990000, 2790000, 60, 'products/vivo-y03.jpg', 5, 1, 'VIV-Y03-64', '{\"screen\": \"6.56 inch LCD\", \"ram\": \"4GB\", \"storage\": \"64GB\", \"camera\": \"13MP\", \"battery\": \"5000mAh\"}', 'active', '2025-05-21 12:26:50', '2025-05-21 15:04:34', 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_attributes`
--

CREATE TABLE `product_attributes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_attributes`
--

INSERT INTO `product_attributes` (`id`, `product_id`, `name`, `value`, `created_at`) VALUES
(1, 1, 'Màu sắc', 'Titanium Black', '2025-05-21 12:26:50'),
(2, 1, 'Bộ nhớ', '256GB', '2025-05-21 12:26:50'),
(3, 6, 'Màu sắc', 'Titanium Black', '2025-05-21 12:26:50'),
(4, 6, 'Bộ nhớ', '512GB', '2025-05-21 12:26:50'),
(5, 11, 'Màu sắc', 'Black', '2025-05-21 12:26:50'),
(6, 11, 'Bộ nhớ', '256GB', '2025-05-21 12:26:50'),
(7, 16, 'Màu sắc', 'Black', '2025-05-21 12:26:50'),
(8, 16, 'Bộ nhớ', '256GB', '2025-05-21 12:26:50'),
(9, 21, 'Màu sắc', 'Black', '2025-05-21 12:26:50'),
(10, 21, 'Bộ nhớ', '256GB', '2025-05-21 12:26:50');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `created_at`) VALUES
(50, 1, '682de8052601c0.69613019.jpg', 0, '2025-05-21 14:49:41'),
(51, 2, '682de8191dace7.31892354.jpg', 0, '2025-05-21 14:50:01'),
(52, 3, '682de851c1f353.17824864.jpg', 0, '2025-05-21 14:50:57'),
(53, 4, '682de86de1b0f6.85489914.jpg', 0, '2025-05-21 14:51:25'),
(54, 5, '682de886f2c0b1.97893345.jpg', 0, '2025-05-21 14:51:50'),
(55, 6, '682de8afeea2e1.14458430.jpg', 0, '2025-05-21 14:52:31'),
(56, 7, '682de8c82b2588.86305990.jpg', 0, '2025-05-21 14:52:56'),
(57, 8, '682de8dd2ed360.18018207.jpg', 0, '2025-05-21 14:53:17'),
(58, 9, '682de94334dfb6.65404805.jpg', 0, '2025-05-21 14:54:59'),
(59, 10, '682de958da5fa9.60092919.jpg', 0, '2025-05-21 14:55:20'),
(60, 11, '682de9783b7e53.88826384.jpg', 0, '2025-05-21 14:55:52'),
(61, 12, '682de9956a9e29.56736374.jpg', 0, '2025-05-21 14:56:21'),
(62, 13, '682dea0583a436.09264123.jpg', 0, '2025-05-21 14:58:13'),
(63, 14, '682dea250d9509.08234258.jpg', 0, '2025-05-21 14:58:45'),
(64, 15, '682dea440c04f3.31496285.jpg', 0, '2025-05-21 14:59:16'),
(65, 16, '682dea62cc86d3.42927041.jpg', 0, '2025-05-21 14:59:46'),
(66, 17, '682dea7a9c56e5.20111724.jpg', 0, '2025-05-21 15:00:10'),
(67, 18, '682dea8f7e7ca0.51586840.jpg', 0, '2025-05-21 15:00:31'),
(68, 19, '682deac8ddc1c3.80051770.jpg', 0, '2025-05-21 15:01:28'),
(69, 20, '682deae89a6632.30484306.jpg', 0, '2025-05-21 15:02:00'),
(70, 21, '682deb0fa95704.56168028.jpg', 0, '2025-05-21 15:02:39'),
(71, 22, '682deb2ed4ee23.52576213.jpg', 0, '2025-05-21 15:03:10'),
(72, 23, '682deb5074a2c8.22788314.jpg', 0, '2025-05-21 15:03:44'),
(73, 24, '682deb6eaa26a0.81219037.jpg', 0, '2025-05-21 15:04:14'),
(74, 25, '682deb8254aa72.58899086.jpg', 0, '2025-05-21 15:04:34');

-- --------------------------------------------------------

--
-- Table structure for table `product_views`
--

CREATE TABLE `product_views` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `rating`, `comment`, `status`, `created_at`, `updated_at`) VALUES
(12, 6, 1, 5, 'good', 'approved', '2025-06-21 06:01:07', '2025-06-21 14:46:38');

-- --------------------------------------------------------

--
-- Table structure for table `search_queries`
--

CREATE TABLE `search_queries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `query` varchar(255) NOT NULL,
  `searched_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `results_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key_name`, `value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Bán Điện Thoại', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(2, 'site_description', 'Cửa hàng điện thoại di động uy tín', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(3, 'site_keywords', 'điện thoại, smartphone, iphone, samsung, xiaomi', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(4, 'site_logo', 'images/logo.png', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(5, 'site_favicon', 'images/favicon.ico', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(6, 'site_email', 'contact@bandienthoai.com', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(7, 'site_phone', '0384171558', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(8, 'site_address', '17 Đường Tô ký, Quận 12, TP.HCM', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(9, 'facebook_url', 'https://facebook.com/bandienthoai', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(10, 'youtube_url', 'https://youtube.com/bandienthoai', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(11, 'instagram_url', 'https://instagram.com/bandienthoai', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(12, 'twitter_url', 'https://twitter.com/bandienthoai', '2025-05-21 12:26:50', '2025-05-21 12:26:50'),
(13, 'payment_qr', 'images/payment-qr.jpg', '2025-05-21 12:26:50', '2025-05-21 12:26:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `name`, `email`, `password`, `phone`, `address`, `role`, `status`, `created_at`, `updated_at`, `reset_token`, `reset_token_expires`) VALUES
(6, 'thanh', 'thanh', 'thanhnc5187@ut.edu.vn', '$2y$10$48PRPiUreWMci5XEVqig/uPFoM5QjnvHAL1HVbWcqe8A8rizZQH0m', '0384171558', 'ap 6 go dau tay ninh', 'admin', 'active', '2025-05-21 12:35:16', '2025-06-18 00:07:05', NULL, NULL),
(8, 'thanhh', 'thanhh', 'nguyenchithanh2005.nvt@gmail.com', '$2y$10$FZ9cMf.uuM/IuJzYKlEg8OSKMSWZr7idpXq2dZvHCeiHRrPn7mwkG', '0384171558', 'ap 6 go dau tay ninh', 'user', 'active', '2025-05-24 07:15:17', '2025-05-24 07:15:17', NULL, NULL),
(10, 'aaa', 'Nguyễn Văn A', 'localhost@gmail.com', '$2y$10$3EV8NsO8dZqvmpbwokc6ied0yay9fAoWwjEggL4U7tnuKV95LyAhC', '0364151345', 'tp hcm', 'user', 'active', '2025-06-18 00:20:08', '2025-06-18 00:20:08', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_views`
--
ALTER TABLE `product_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_viewed_at` (`viewed_at`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `search_queries`
--
ALTER TABLE `search_queries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_searched_at` (`searched_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `product_views`
--
ALTER TABLE `product_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `search_queries`
--
ALTER TABLE `search_queries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD CONSTRAINT `product_attributes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_views`
--
ALTER TABLE `product_views`
  ADD CONSTRAINT `product_views_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_views_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `search_queries`
--
ALTER TABLE `search_queries`
  ADD CONSTRAINT `search_queries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
