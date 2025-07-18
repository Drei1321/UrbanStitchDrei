-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 18, 2025 at 04:44 PM
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
-- Database: `urbanstitch_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `selected_size` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `user_id`, `product_id`, `quantity`, `selected_size`, `created_at`, `updated_at`) VALUES
(27, 1, 42, 1, 'XS', '2025-07-16 05:45:35', '2025-07-16 05:45:35');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `product_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `color`, `description`, `product_count`, `created_at`, `updated_at`) VALUES
(1, 'Urban Tees', 'urban-tees', 'fas fa-tshirt', 'text-neon-green', 'Premium urban t-shirts and hoodies for street fashion enthusiasts', 2, '2025-06-20 16:11:27', NULL),
(2, 'Winter Wear', 'winter-wear', 'fas fa-snowflake', 'text-urban-orange', 'Stay warm and stylish with our winter collection', 1, '2025-06-20 16:11:27', NULL),
(4, 'Shorts & Jeans', 'shorts-jeans', 'fas fa-user-tie', 'text-urban-orange', 'Comfortable and stylish bottoms for everyday wear', 1, '2025-06-20 16:11:27', NULL),
(5, 'Streetwear', 'streetwear', 'fas fa-tshirt', 'text-neon-green', 'Authentic street fashion pieces that define urban culture', 1, '2025-06-20 16:11:27', NULL),
(6, 'Footwear', 'footwear', 'fas fa-shoe-prints', 'text-urban-orange', 'Urban sneakers and shoes for every street style', 1, '2025-06-20 16:11:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscriptions`
--

CREATE TABLE `newsletter_subscriptions` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter_subscriptions`
--

INSERT INTO `newsletter_subscriptions` (`id`, `email`, `subscribed_at`) VALUES
(1, 'subscriber1@example.com', '2025-06-26 07:33:34'),
(2, 'subscriber2@example.com', '2025-06-26 07:33:34'),
(3, 'subscriber3@example.com', '2025-06-26 07:33:34');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','completed','cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'paypal',
  `billing_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`billing_info`)),
  `gcash_number` varchar(15) DEFAULT NULL,
  `gcash_reference` varchar(50) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `status_updated_at` timestamp NULL DEFAULT NULL,
  `updated_by_admin` int(11) DEFAULT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `total_amount`, `status`, `shipping_address`, `billing_address`, `payment_method`, `billing_info`, `gcash_number`, `gcash_reference`, `admin_notes`, `status_updated_at`, `updated_by_admin`, `payment_id`, `created_at`, `updated_at`) VALUES
(4, 5, 'US2025503454', 7200.00, 'completed', NULL, NULL, 'gcash', '{\"first_name\":\"Andrei\",\"last_name\":\"Alba\",\"email\":\"albaandrei0904@gmail.com\",\"phone\":\"+639171425250\",\"address\":\"a.c reyes street poblacion plaridel, bulacan\",\"city\":\"plaridel\",\"postal_code\":\"3004\",\"province\":\"bulacan\"}', '09171425250', '123124', '', '2025-07-14 14:35:29', 1, NULL, '2025-07-14 14:18:07', '2025-07-14 14:35:29'),
(5, 5, 'US2025693481', 8500.00, 'cancelled', NULL, NULL, 'gcash', '{\"first_name\":\"Andrei\",\"last_name\":\"Alba\",\"email\":\"albaandrei0904@gmail.com\",\"phone\":\"+639171425250\",\"address\":\"a.c reyes street poblacion plaridel, bulacan\",\"city\":\"plaridel\",\"postal_code\":\"3004\",\"province\":\"bulacan\"}', '09171425250', '123124', '', '2025-07-15 14:57:08', 1, NULL, '2025-07-14 14:41:59', '2025-07-15 14:57:08'),
(6, 5, 'US2025283599', 800.00, 'completed', NULL, NULL, 'gcash', '{\"first_name\":\"Andrei\",\"last_name\":\"Alba\",\"email\":\"albaandrei0904@gmail.com\",\"phone\":\"+639171425250\",\"address\":\"a.c reyes street poblacion plaridel, bulacan\",\"city\":\"plaridel\",\"postal_code\":\"3004\",\"province\":\"bulacan\"}', '09171425250', '123124', '', '2025-07-15 14:54:02', 1, NULL, '2025-07-15 14:53:16', '2025-07-15 14:54:02'),
(7, 5, 'US2025420263', 5500.00, 'completed', NULL, NULL, 'gcash', '{\"first_name\":\"Andrei\",\"last_name\":\"Alba\",\"email\":\"albaandrei0904@gmail.com\",\"phone\":\"+639171425250\",\"address\":\"a.c reyes street poblacion plaridel, bulacan\",\"city\":\"plaridel\",\"postal_code\":\"3004\",\"province\":\"bulacan\"}', '09171425250', '123124', '', '2025-07-15 14:58:20', 1, NULL, '2025-07-15 14:57:46', '2025-07-15 14:58:20'),
(8, 5, 'US2025653888', 5500.00, 'completed', NULL, NULL, 'gcash', '{\"first_name\":\"Andrei\",\"last_name\":\"Alba\",\"email\":\"albaandrei0904@gmail.com\",\"phone\":\"+639171425250\",\"address\":\"a.c reyes street poblacion plaridel, bulacan\",\"city\":\"plaridel\",\"postal_code\":\"3004\",\"province\":\"bulacan\"}', '09171425250', '1234', '', '2025-07-16 02:58:00', 1, NULL, '2025-07-15 15:31:27', '2025-07-16 02:58:00'),
(9, 5, 'US2025902824', 3000.00, 'completed', NULL, NULL, 'gcash', '{\"first_name\":\"Andrei\",\"last_name\":\"Alba\",\"email\":\"albaandrei0904@gmail.com\",\"phone\":\"+639171425250\",\"address\":\"a.c reyes street poblacion plaridel, bulacan\",\"city\":\"plaridel\",\"postal_code\":\"3004\",\"province\":\"bulacan\"}', '09171425250', '123124', '', '2025-07-15 16:16:39', 1, NULL, '2025-07-15 16:14:19', '2025-07-15 16:16:39'),
(10, 5, 'US2025379935', 5500.00, 'delivered', NULL, NULL, 'gcash', '{\"first_name\":\"Andrei\",\"last_name\":\"Alba\",\"email\":\"albaandrei0904@gmail.com\",\"phone\":\"+639171425250\",\"address\":\"a.c reyes street poblacion plaridel, bulacan\",\"city\":\"plaridel\",\"postal_code\":\"3004\",\"province\":\"bulacan\"}', '09171425250', '123124', '', '2025-07-16 15:58:11', 1, NULL, '2025-07-16 03:20:18', '2025-07-16 15:58:11'),
(11, 5, 'US2025732566', 11898.00, 'completed', NULL, NULL, 'gcash', '{\"first_name\":\"Andrei\",\"last_name\":\"Alba\",\"email\":\"albaandrei0904@gmail.com\",\"phone\":\"+639171425250\",\"address\":\"a.c reyes street poblacion plaridel, bulacan\",\"city\":\"plaridel\",\"postal_code\":\"3004\",\"province\":\"bulacan\"}', '09171425250', '123124', '', '2025-07-16 06:08:45', 1, NULL, '2025-07-16 06:07:08', '2025-07-16 06:08:45'),
(12, 12, 'US2025551389', 8000.00, 'completed', NULL, NULL, 'gcash', '{\"first_name\":\"andrei\",\"last_name\":\"Alba\",\"email\":\"albaandrei87@gmail.com\",\"phone\":\"+639171425250\",\"address\":\"a.c reyes street poblacion plaridel, bulacan\",\"city\":\"plaridel\",\"postal_code\":\"3004\",\"province\":\"bulacan\"}', '09171425250', '123124', '', '2025-07-16 16:02:05', 1, NULL, '2025-07-16 16:01:26', '2025-07-16 16:02:05');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `selected_size` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `selected_size`) VALUES
(7, 4, 25, 2, 1600.00, 'XS'),
(9, 6, 33, 1, 800.00, 'XS'),
(10, 7, 29, 1, 5500.00, 'XS'),
(11, 8, 29, 1, 5500.00, 'S'),
(12, 9, 28, 1, 3000.00, 'XS'),
(13, 10, 29, 1, 5500.00, 'XS'),
(14, 11, 33, 1, 800.00, 'XS'),
(15, 11, 42, 1, 2599.00, 'S'),
(16, 11, 43, 1, 8499.00, '7'),
(17, 12, 44, 1, 8000.00, '7');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` enum('pending','confirmed','processing','shipped','delivered','completed','cancelled') DEFAULT NULL,
  `new_status` enum('pending','confirmed','processing','shipped','delivered','completed','cancelled') NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `old_status`, `new_status`, `admin_id`, `admin_notes`, `created_at`) VALUES
(1, 4, 'pending', 'confirmed', 1, 'wait for the delivery', '2025-07-14 14:19:10'),
(2, 4, 'confirmed', 'shipped', 1, '', '2025-07-14 14:20:42'),
(3, 4, 'shipped', 'completed', 1, '', '2025-07-14 14:35:29'),
(4, 6, 'pending', 'confirmed', 1, '', '2025-07-15 14:53:44'),
(5, 6, 'confirmed', 'completed', 1, '', '2025-07-15 14:54:02'),
(6, 5, 'pending', 'cancelled', 1, '', '2025-07-15 14:57:08'),
(7, 7, 'pending', 'completed', 1, '', '2025-07-15 14:58:20'),
(8, 9, 'pending', 'completed', 1, '', '2025-07-15 16:16:39'),
(9, 8, 'pending', 'shipped', 1, '', '2025-07-16 02:12:06'),
(10, 8, 'shipped', 'delivered', 1, '', '2025-07-16 02:43:54'),
(11, 8, 'delivered', 'completed', 1, '', '2025-07-16 02:58:00'),
(12, 10, 'pending', 'confirmed', 1, '', '2025-07-16 03:20:55'),
(13, 11, 'pending', 'confirmed', 1, '', '2025-07-16 06:07:57'),
(14, 11, 'confirmed', 'shipped', 1, '', '2025-07-16 06:08:22'),
(15, 11, 'shipped', 'completed', 1, '', '2025-07-16 06:08:45'),
(16, 10, 'confirmed', 'delivered', 1, '', '2025-07-16 15:58:11'),
(17, 12, 'pending', 'completed', 1, '', '2025-07-16 16:02:05');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_trending` tinyint(1) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `reviews_count` int(11) DEFAULT 0,
  `tags` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `product_type_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `description`, `price`, `original_price`, `image_url`, `category_id`, `stock_quantity`, `is_featured`, `is_trending`, `rating`, `reviews_count`, `tags`, `created_at`, `updated_at`, `product_type_id`) VALUES
(25, 'coffee gift', 'coffee-gift', 'coffee gift', 1600.00, 1800.00, '/uploads/products/product_url_6873399d6c89c_1752381853.jpg', 2, 35, 0, 0, 0.00, 0, '', '2025-07-13 04:44:13', '2025-07-13 04:44:13', 4),
(27, 'Winter Color Block Lamb Thermal Fleece Jacket', 'winter-color-block-lamb-thermal-fleece-jacket', 'Winter Color Block Lamb Thermal Fleece Jacketet', 5000.00, 5500.00, 'https://i.pinimg.com/1200x/4a/e2/3d/4ae23d869f35b7973cdfc59592c738fb.jpg', 2, 70, 0, 0, 0.00, 0, '', '2025-07-15 06:28:06', '2025-07-15 06:28:06', 2),
(28, '1pc Loose Fit Men\'s Hooded Padded Coat With Zip Pocket, Plain Going Out Basic Warm Jacket', '1pc-loose-fit-men\'s-hooded-padded-coat-with-zip-pocket,-plain-going-out-basic-warm-jacket', '1pc Loose Fit Men\'s Hooded Padded Coat With Zip Pocket, Plain Going Out Basic Warm Jacket Black Sports & Outdoor - Gym Commuting,Casual - Basic,Casual - Modern Casual Long Sleeve Fabric Plain Puffer Non-Stretch Spring/Fall,Winter Men Clothing, size features are:Bust: ,Length: ,Sleeve Length:', 3000.00, 3400.00, 'https://i.pinimg.com/1200x/aa/0a/3b/aa0a3bff18210d3f20f5cdc5ca496952.jpg', 2, 70, 0, 1, 0.00, 0, '', '2025-07-15 06:29:11', '2025-07-15 06:29:11', 2),
(29, 'Sweatshirt Zip Hoodie Pullover Letter Front Pocket For Men\'s', 'zity-men\'s-flannel-hoodie-shirts-casual-button-down-plaid-shirt-jackets-for-men-long-sleeve-stylish-hooded-with-pocket', 'Selling Points 1. Gender: Women\'s Men\'s 2. What\'s in the box: Hoodie Specifications Gender: Women\'s, Men\'s, Unisex, Style: Fashion, Y2K, Types: Sweatshirt, Zip Hoodie, Pullover, Design: Front Pocket, Pattern: Letter, Print Type: Hot Stamping, Fabric: Polyester, Length [Top] (cm):', 5500.00, 6000.00, 'https://i.pinimg.com/736x/f8/8e/83/f88e837dfcb3415d1be6941c333e5aac.jpg', 2, 50, 0, 0, 0.00, 0, '', '2025-07-15 06:30:28', '2025-07-15 08:30:25', 2),
(30, 'Men\'s Colorblock Long Sleeve Jacket & Outerwear, Spring Autumn', 'men\'s-colorblock-long-sleeve-jacket-&-outerwear,-spring-autumn', 'Men\'s Colorblock Long Sleeve Jacket & Outerwear, Spring Autumn Grey Sports & Outdoor - Mountain/Outdoor Long Sleeve Woven Fabric Colorblock,Letter Other Non-Stretch Spring/Fall Men Clothing, size features are:Bust: ,Length: ,Sleeve', 1600.00, 2000.00, 'https://i.pinimg.com/1200x/eb/dd/27/ebdd27e9068c198bd6cbc17d735eb490.jpg', 2, 70, 0, 0, 0.00, 0, '', '2025-07-15 10:34:51', '2025-07-15 10:34:51', 2),
(31, 'Men\'s Sport Jacket, Outdoor Waterproof Multi-zipper Windbreaker Coat', 'men\'s-sport-jacket,-outdoor-waterproof-multi-zipper-windbreaker-coat', 'Free Returns ✓ Free Shipping✓. Men\'s Sport Jacket, Outdoor Waterproof Multi-zipper Windbreaker Coat- undefined', 1800.00, 2000.00, 'https://i.pinimg.com/1200x/58/30/a6/5830a60a59defb228eaf3525e2cb68ca.jpg', 2, 70, 0, 1, 0.00, 0, '', '2025-07-15 10:39:14', '2025-07-15 10:39:14', 2),
(33, 'Men T-Shirts Fit Crew Neck Short Sleeve Graphic Tee Cool Stuff Street Summer Knit Top', 'men-t-shirts-fit-crew-neck-short-sleeve-graphic-tee-cool-stuff-street-summer-knit-top', 'Men T-Shirts Fit Crew Neck Short Sleeve Graphic Tee Cool Stuff Street Summer Knit Top Black Avant-Garde - Street Casual,Casual - Modern Casual Short Sleeve Fabric Letter Medium Stretch Summer Men Clothing, size features are:Bust: ,Length: ,Sleeve Length:', 800.00, 1000.00, 'https://i.pinimg.com/1200x/b2/c1/da/b2c1da9f22bb7db90dd9e29eaec25b8a.jpg', 1, 4, 0, 0, 0.00, 0, '', '2025-07-15 12:43:51', '2025-07-15 15:27:32', 1),
(34, 'Elwood Straight Leg Jeans in Vintage Indigo at Nordstrom', 'elwood-straight-leg-jeans-in-vintage-indigo-at-nordstrom', 'A smart straight-leg silhouette lends polished ease to these casual jeans cut from nonstretch denim. Exclusive retailer 31\" inseam; 19\" leg opening; 12 1/2\" front rise; 16\" back rise (size 32) Zip fly with button closure Five-pocket style 100% cotton Machine wash, tumble dry Imported', 1200.00, 2000.00, 'https://i.pinimg.com/1200x/7d/9d/37/7d9d37b96d56728b2766058a850dd97e.jpg', 4, 35, 0, 0, 0.00, 0, '', '2025-07-16 03:28:44', '2025-07-16 03:28:44', 3),
(37, 'BOWSTONS JEANS - RAW DENIM', 'bowstons-jeans---raw-denim', 'Meet our take on wide leg raw denim in our perfectly fitting unisex Bowstons4 cut. Perfect balance between a heavy denim feel and daily comfort achieved by 13,5oz 100% cotton indigo denim. This might be the only pair of pants you will ever need.', 2000.00, 2000.00, 'https://i.pinimg.com/1200x/ec/4c/76/ec4c76766d6af90f2d9ae2227b8765c0.jpg', 4, 35, 0, 0, 0.00, 0, '', '2025-07-16 03:35:29', '2025-07-16 03:35:29', 3),
(38, 'Cargo Pants Fashion Trend: Styling & Shopping Tips » coco bassey', 'cargo-pants-fashion-trend:-styling-&-shopping-tips-»-coco-bassey', 'Are you into the cargo pants fashion trend? Coco Bassey styles her Hudson Jeans cargo pants & shares a curated selection of stylish options.', 2200.00, 2500.00, 'https://i.pinimg.com/1200x/37/74/1f/37741fc0d2455766c778953aaec64f2c.jpg', 4, 28, 1, 1, 0.00, 0, '', '2025-07-16 03:36:42', '2025-07-16 03:36:42', 3),
(39, 'Parachute Pants | UNIQLO US', 'parachute-pants-|-uniqlo-us', 'Shop Parachute Pants at UNIQLO US. Read customer reviews, explore styling ideas, and more.', 2000.00, 2990.00, 'https://i.pinimg.com/736x/31/bf/44/31bf449bd53714db9bf3583741ccc771.jpg', 4, 20, 1, 1, 0.00, 0, '', '2025-07-16 03:38:15', '2025-07-16 03:38:15', 3),
(40, 'Men\'s Washed Vintage Wide Leg Flare Jeans, Frayed Plain Dark Blue Long Distressed Baggy Jeans, For Husband, Boyfriend Gifts', 'men\'s-washed-vintage-wide-leg-flare-jeans,-frayed-plain-dark-blue-long-distressed-baggy-jeans,-for-husband,-boyfriend-gifts', 'Men\'s Washed Vintage Wide Leg Flare Jeans, Frayed Plain Dark Blue Long Distressed Baggy Jeans, For Husband, Boyfriend Gifts Light Wash Casual - Amekaji Denim Plain,All Over Print Wide Leg Non-Stretch Summer,All Men Clothing, size features are:Bust: ,Length: ,Sleeve Length:', 1600.00, 2000.00, 'https://i.pinimg.com/1200x/29/2e/54/292e547695a834932062b733b37360fd.jpg', 4, 70, 0, 0, 0.00, 0, '', '2025-07-16 03:39:58', '2025-07-16 03:39:58', 3),
(41, 'Brushed Wide Fit Carpenter Denim Pants [Black]', 'brushed-wide-fit-carpenter-denim-pants-[black]', 'These premium black carpenter denim pants feature a relaxed wide-fit silhouette with distinctive snap details at the hem and custom metal hardware. The high-density cotton fabric undergoes an all-brush washing technique to achieve a subtle, graceful finish that elevates the classic workwear style.', 2000.00, 2499.00, 'https://i.pinimg.com/1200x/89/ce/02/89ce02131e827f152805f5a231a7d7fa.jpg', 4, 35, 0, 1, 0.00, 0, '', '2025-07-16 03:41:44', '2025-07-16 03:41:44', 3),
(42, 'Mens Vintage Baggy Coat Zip Outerwear Tops Male Retro Lapel Jacket Casual', 'mens-vintage-baggy-coat-zip-outerwear-tops-male-retro-lapel-jacket-casual', 'Product Description Real Size Infomation Unit:cm/inch 1Inch=2.54cm notes: 1. Due to the change of screen color setting, the actual fabric color may be slightly different from the online color. 2. The label size is Asian size, the parameters of each size are measured by placing the product on a flat table to measure its outer size. Please measure yourself before ordering, if you are not sure, please contact us.', 2599.00, 3099.00, 'https://i.pinimg.com/1200x/10/a2/41/10a241f1b2980b95b0d5a0a3e40630da.jpg', 2, 70, 0, 0, 0.00, 0, '', '2025-07-16 03:47:22', '2025-07-16 03:47:22', 2),
(43, 'ON CLOUDTILT | BrownsShoes', 'on-cloudtilt-|-brownsshoes', 'Details CloudTec Phase® for seamless weight transferPrecision-crafted with Finite Element AnalysisUltra-lightweight, smooth midsoleAlignment of cloud elements delivers perfect cushioningSock construction and speed laces, to pull on and goUpper mesh made with 100% recycled polyesterHigh energy return, thanks to Helion™ superfoamDope dyed for 90% less water usage Shipping & Returns Free shipping on all order.', 8499.00, 9000.00, 'https://i.pinimg.com/1200x/71/24/26/712426dd65edfa9c32277779fb4e82b9.jpg', 6, 100, 1, 1, 0.00, 0, '', '2025-07-16 03:50:25', '2025-07-16 03:50:25', 6),
(44, 'MEXICO 66 | Unisex | Yellow/Black | UNISEX SHOES | Onitsuka Tiger Australia', 'mexico-66-|-unisex-|-yellow/black-|-unisex-shoes-|-onitsuka-tiger-australia', 'MEXICO 66 | Unisex | Yellow/Black | UNISEX SHOES | Onitsuka Tiger Australia', 8000.00, 8500.00, 'https://i.pinimg.com/1200x/82/81/61/82816107ad1a21f08a9a9471ffb8f52d.jpg', 6, 90, 0, 0, 0.00, 0, '', '2025-07-16 16:00:33', '2025-07-16 16:00:33', 6);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_sizes`
--

CREATE TABLE `product_sizes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size_code` varchar(10) NOT NULL,
  `size_name` varchar(50) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `price_adjustment` decimal(10,2) DEFAULT 0.00,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_sizes`
--

INSERT INTO `product_sizes` (`id`, `product_id`, `size_code`, `size_name`, `stock_quantity`, `price_adjustment`, `is_available`, `created_at`, `updated_at`) VALUES
(22, 25, 'XS', 'Extra Small', 3, 0.00, 1, '2025-07-13 04:44:13', '2025-07-14 14:18:07'),
(23, 25, 'S', 'Small', 5, 0.00, 1, '2025-07-13 04:44:13', '2025-07-13 04:44:13'),
(24, 25, 'M', 'Medium', 5, 0.00, 1, '2025-07-13 04:44:13', '2025-07-13 04:44:13'),
(25, 25, 'L', 'Large', 5, 0.00, 1, '2025-07-13 04:44:13', '2025-07-13 04:44:13'),
(26, 25, 'XL', 'Extra Large', 5, 0.00, 1, '2025-07-13 04:44:13', '2025-07-13 04:44:13'),
(27, 25, 'XXL', '2X Large', 5, 0.00, 1, '2025-07-13 04:44:13', '2025-07-13 04:44:13'),
(28, 25, 'XXXL', '3X Large', 5, 0.00, 1, '2025-07-13 04:44:13', '2025-07-13 04:44:13'),
(40, 27, 'XS', 'Extra Small', 10, 0.00, 1, '2025-07-15 06:28:06', '2025-07-15 06:28:06'),
(41, 27, 'S', 'Small', 10, 0.00, 1, '2025-07-15 06:28:06', '2025-07-15 06:28:06'),
(42, 27, 'M', 'Medium', 10, 0.00, 1, '2025-07-15 06:28:06', '2025-07-15 06:28:06'),
(43, 27, 'L', 'Large', 10, 0.00, 1, '2025-07-15 06:28:06', '2025-07-15 06:28:06'),
(44, 27, 'XL', 'Extra Large', 10, 0.00, 1, '2025-07-15 06:28:06', '2025-07-15 06:28:06'),
(45, 27, 'XXL', '2X Large', 10, 0.00, 1, '2025-07-15 06:28:06', '2025-07-15 06:28:06'),
(46, 27, 'XXXL', '3X Large', 10, 0.00, 1, '2025-07-15 06:28:06', '2025-07-15 06:28:06'),
(47, 28, 'XS', 'Extra Small', 9, 0.00, 1, '2025-07-15 06:29:11', '2025-07-15 16:14:19'),
(48, 28, 'S', 'Small', 10, 0.00, 1, '2025-07-15 06:29:11', '2025-07-15 06:29:11'),
(49, 28, 'M', 'Medium', 10, 0.00, 1, '2025-07-15 06:29:11', '2025-07-15 06:29:11'),
(50, 28, 'L', 'Large', 10, 0.00, 1, '2025-07-15 06:29:11', '2025-07-15 06:29:11'),
(51, 28, 'XL', 'Extra Large', 10, 0.00, 1, '2025-07-15 06:29:11', '2025-07-15 06:29:11'),
(52, 28, 'XXL', '2X Large', 10, 0.00, 1, '2025-07-15 06:29:11', '2025-07-15 06:29:11'),
(53, 28, 'XXXL', '3X Large', 10, 0.00, 1, '2025-07-15 06:29:11', '2025-07-15 06:29:11'),
(54, 29, 'XS', 'Extra Small', 8, 0.00, 1, '2025-07-15 06:30:28', '2025-07-16 03:20:18'),
(55, 29, 'S', 'Small', 9, 0.00, 1, '2025-07-15 06:30:28', '2025-07-15 15:31:27'),
(56, 29, 'M', 'Medium', 10, 0.00, 1, '2025-07-15 06:30:28', '2025-07-15 06:30:28'),
(57, 29, 'L', 'Large', 10, 0.00, 1, '2025-07-15 06:30:28', '2025-07-15 06:30:28'),
(58, 29, 'XL', 'Extra Large', 10, 0.00, 1, '2025-07-15 06:30:28', '2025-07-15 06:30:28'),
(59, 30, 'XS', 'Extra Small', 10, 0.00, 1, '2025-07-15 10:34:51', '2025-07-15 10:34:51'),
(60, 30, 'S', 'Small', 10, 0.00, 1, '2025-07-15 10:34:51', '2025-07-15 10:34:51'),
(61, 30, 'M', 'Medium', 10, 0.00, 1, '2025-07-15 10:34:51', '2025-07-15 10:34:51'),
(62, 30, 'L', 'Large', 10, 0.00, 1, '2025-07-15 10:34:51', '2025-07-15 10:34:51'),
(63, 30, 'XL', 'Extra Large', 10, 0.00, 1, '2025-07-15 10:34:51', '2025-07-15 10:34:51'),
(64, 30, 'XXL', '2X Large', 10, 0.00, 1, '2025-07-15 10:34:51', '2025-07-15 10:34:51'),
(65, 30, 'XXXL', '3X Large', 10, 0.00, 1, '2025-07-15 10:34:51', '2025-07-15 10:34:51'),
(66, 31, 'XS', 'Extra Small', 10, 0.00, 1, '2025-07-15 10:39:14', '2025-07-15 10:39:14'),
(67, 31, 'S', 'Small', 10, 0.00, 1, '2025-07-15 10:39:14', '2025-07-15 10:39:14'),
(68, 31, 'M', 'Medium', 10, 0.00, 1, '2025-07-15 10:39:14', '2025-07-15 10:39:14'),
(69, 31, 'L', 'Large', 10, 0.00, 1, '2025-07-15 10:39:14', '2025-07-15 10:39:14'),
(70, 31, 'XL', 'Extra Large', 10, 0.00, 1, '2025-07-15 10:39:14', '2025-07-15 10:39:14'),
(71, 31, 'XXL', '2X Large', 10, 0.00, 1, '2025-07-15 10:39:14', '2025-07-15 10:39:14'),
(72, 31, 'XXXL', '3X Large', 10, 0.00, 1, '2025-07-15 10:39:14', '2025-07-15 10:39:14'),
(74, 33, 'XS', 'Extra Small', 3, 0.00, 1, '2025-07-15 12:43:51', '2025-07-16 06:07:08'),
(75, 34, 'XS', 'Extra Small', 5, 0.00, 1, '2025-07-16 03:28:44', '2025-07-16 03:28:44'),
(76, 34, 'S', 'Small', 5, 0.00, 1, '2025-07-16 03:28:44', '2025-07-16 03:28:44'),
(77, 34, 'M', 'Medium', 5, 0.00, 1, '2025-07-16 03:28:44', '2025-07-16 03:28:44'),
(78, 34, 'L', 'Large', 5, 0.00, 1, '2025-07-16 03:28:44', '2025-07-16 03:28:44'),
(79, 34, 'XL', 'Extra Large', 5, 0.00, 1, '2025-07-16 03:28:44', '2025-07-16 03:28:44'),
(80, 34, 'XXL', '2X Large', 5, 0.00, 1, '2025-07-16 03:28:44', '2025-07-16 03:28:44'),
(81, 34, 'XXXL', '3X Large', 5, 0.00, 1, '2025-07-16 03:28:44', '2025-07-16 03:28:44'),
(82, 37, 'XS', 'Extra Small', 5, 0.00, 1, '2025-07-16 03:35:29', '2025-07-16 03:35:29'),
(83, 37, 'S', 'Small', 5, 0.00, 1, '2025-07-16 03:35:29', '2025-07-16 03:35:29'),
(84, 37, 'M', 'Medium', 5, 0.00, 1, '2025-07-16 03:35:29', '2025-07-16 03:35:29'),
(85, 37, 'L', 'Large', 5, 0.00, 1, '2025-07-16 03:35:29', '2025-07-16 03:35:29'),
(86, 37, 'XL', 'Extra Large', 5, 0.00, 1, '2025-07-16 03:35:29', '2025-07-16 03:35:29'),
(87, 37, 'XXL', '2X Large', 5, 0.00, 1, '2025-07-16 03:35:29', '2025-07-16 03:35:29'),
(88, 37, 'XXXL', '3X Large', 5, 0.00, 1, '2025-07-16 03:35:29', '2025-07-16 03:35:29'),
(89, 38, 'XS', 'Extra Small', 4, 0.00, 1, '2025-07-16 03:36:42', '2025-07-16 03:36:42'),
(90, 38, 'S', 'Small', 4, 0.00, 1, '2025-07-16 03:36:42', '2025-07-16 03:36:42'),
(91, 38, 'M', 'Medium', 4, 0.00, 1, '2025-07-16 03:36:42', '2025-07-16 03:36:42'),
(92, 38, 'L', 'Large', 4, 0.00, 1, '2025-07-16 03:36:42', '2025-07-16 03:36:42'),
(93, 38, 'XL', 'Extra Large', 4, 0.00, 1, '2025-07-16 03:36:42', '2025-07-16 03:36:42'),
(94, 38, 'XXL', '2X Large', 4, 0.00, 1, '2025-07-16 03:36:42', '2025-07-16 03:36:42'),
(95, 38, 'XXXL', '3X Large', 4, 0.00, 1, '2025-07-16 03:36:42', '2025-07-16 03:36:42'),
(96, 39, 'XS', 'Extra Small', 5, 0.00, 1, '2025-07-16 03:38:15', '2025-07-16 03:38:15'),
(97, 39, 'S', 'Small', 5, 0.00, 1, '2025-07-16 03:38:15', '2025-07-16 03:38:15'),
(98, 39, 'M', 'Medium', 5, 0.00, 1, '2025-07-16 03:38:15', '2025-07-16 03:38:15'),
(99, 39, 'L', 'Large', 5, 0.00, 1, '2025-07-16 03:38:15', '2025-07-16 03:38:15'),
(100, 40, 'XS', 'Extra Small', 10, 0.00, 1, '2025-07-16 03:39:58', '2025-07-16 03:39:58'),
(101, 40, 'S', 'Small', 10, 0.00, 1, '2025-07-16 03:39:58', '2025-07-16 03:39:58'),
(102, 40, 'M', 'Medium', 10, 0.00, 1, '2025-07-16 03:39:58', '2025-07-16 03:39:58'),
(103, 40, 'L', 'Large', 10, 0.00, 1, '2025-07-16 03:39:58', '2025-07-16 03:39:58'),
(104, 40, 'XL', 'Extra Large', 10, 0.00, 1, '2025-07-16 03:39:58', '2025-07-16 03:39:58'),
(105, 40, 'XXL', '2X Large', 10, 0.00, 1, '2025-07-16 03:39:58', '2025-07-16 03:39:58'),
(106, 40, 'XXXL', '3X Large', 10, 0.00, 1, '2025-07-16 03:39:58', '2025-07-16 03:39:58'),
(107, 41, 'XS', 'Extra Small', 5, 0.00, 1, '2025-07-16 03:41:44', '2025-07-16 03:41:44'),
(108, 41, 'S', 'Small', 5, 0.00, 1, '2025-07-16 03:41:44', '2025-07-16 03:41:44'),
(109, 41, 'M', 'Medium', 5, 0.00, 1, '2025-07-16 03:41:44', '2025-07-16 03:41:44'),
(110, 41, 'L', 'Large', 5, 0.00, 1, '2025-07-16 03:41:44', '2025-07-16 03:41:44'),
(111, 41, 'XL', 'Extra Large', 5, 0.00, 1, '2025-07-16 03:41:44', '2025-07-16 03:41:44'),
(112, 41, 'XXL', '2X Large', 5, 0.00, 1, '2025-07-16 03:41:44', '2025-07-16 03:41:44'),
(113, 41, 'XXXL', '3X Large', 5, 0.00, 1, '2025-07-16 03:41:44', '2025-07-16 03:41:44'),
(114, 42, 'XS', 'Extra Small', 10, 0.00, 1, '2025-07-16 03:47:22', '2025-07-16 03:47:22'),
(115, 42, 'S', 'Small', 9, 0.00, 1, '2025-07-16 03:47:22', '2025-07-16 06:07:08'),
(116, 42, 'M', 'Medium', 10, 0.00, 1, '2025-07-16 03:47:22', '2025-07-16 03:47:22'),
(117, 42, 'L', 'Large', 10, 0.00, 1, '2025-07-16 03:47:22', '2025-07-16 03:47:22'),
(118, 42, 'XL', 'Extra Large', 10, 0.00, 1, '2025-07-16 03:47:22', '2025-07-16 03:47:22'),
(119, 42, 'XXL', '2X Large', 10, 0.00, 1, '2025-07-16 03:47:22', '2025-07-16 03:47:22'),
(120, 42, 'XXXL', '3X Large', 10, 0.00, 1, '2025-07-16 03:47:22', '2025-07-16 03:47:22'),
(121, 43, '7', 'Size 7', 9, 0.00, 1, '2025-07-16 03:50:25', '2025-07-16 06:07:08'),
(122, 43, '8', 'Size 8', 10, 0.00, 1, '2025-07-16 03:50:25', '2025-07-16 03:50:25'),
(123, 43, '9', 'Size 9', 10, 0.00, 1, '2025-07-16 03:50:25', '2025-07-16 03:50:25'),
(124, 43, '10', 'Size 10', 10, 0.00, 1, '2025-07-16 03:50:25', '2025-07-16 03:50:25'),
(125, 43, '11', 'Size 11', 10, 0.00, 1, '2025-07-16 03:50:25', '2025-07-16 03:50:25'),
(126, 43, '12', 'Size 12', 10, 0.00, 1, '2025-07-16 03:50:25', '2025-07-16 03:50:25'),
(127, 43, '7.5', 'Size 7.5', 10, 0.00, 1, '2025-07-16 03:50:25', '2025-07-16 03:50:25'),
(128, 43, '8.5', 'Size 8.5', 10, 0.00, 1, '2025-07-16 03:50:25', '2025-07-16 03:50:25'),
(129, 43, '9.5', 'Size 9.5', 10, 0.00, 1, '2025-07-16 03:50:25', '2025-07-16 03:50:25'),
(130, 43, '10.5', 'Size 10.5', 10, 0.00, 1, '2025-07-16 03:50:25', '2025-07-16 03:50:25'),
(131, 44, '7', 'Size 7', 9, 0.00, 1, '2025-07-16 16:00:33', '2025-07-16 16:01:26'),
(132, 44, '8', 'Size 8', 10, 0.00, 1, '2025-07-16 16:00:33', '2025-07-16 16:00:33'),
(133, 44, '9', 'Size 9', 10, 0.00, 1, '2025-07-16 16:00:33', '2025-07-16 16:00:33'),
(134, 44, '10', 'Size 10', 10, 0.00, 1, '2025-07-16 16:00:33', '2025-07-16 16:00:33'),
(135, 44, '11', 'Size 11', 10, 0.00, 1, '2025-07-16 16:00:33', '2025-07-16 16:00:33'),
(136, 44, '12', 'Size 12', 10, 0.00, 1, '2025-07-16 16:00:33', '2025-07-16 16:00:33'),
(137, 44, '7.5', 'Size 7.5', 10, 0.00, 1, '2025-07-16 16:00:33', '2025-07-16 16:00:33'),
(138, 44, '8.5', 'Size 8.5', 10, 0.00, 1, '2025-07-16 16:00:33', '2025-07-16 16:00:33'),
(139, 44, '9.5', 'Size 9.5', 10, 0.00, 1, '2025-07-16 16:00:33', '2025-07-16 16:00:33');

-- --------------------------------------------------------

--
-- Stand-in structure for view `product_stats`
-- (See below for the actual view)
--
CREATE TABLE `product_stats` (
`total_products` bigint(21)
,`featured_products` bigint(21)
,`trending_products` bigint(21)
,`in_stock_products` bigint(21)
,`out_of_stock_products` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `product_types`
--

CREATE TABLE `product_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `size_type` enum('apparel','footwear') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_types`
--

INSERT INTO `product_types` (`id`, `name`, `size_type`, `created_at`) VALUES
(1, 'T-Shirts', 'apparel', '2025-07-12 13:58:19'),
(2, 'Hoodies', 'apparel', '2025-07-12 13:58:19'),
(3, 'Jeans', 'apparel', '2025-07-12 13:58:19'),
(4, 'Shirts', 'apparel', '2025-07-12 13:58:19'),
(6, 'Sneakers', 'footwear', '2025-07-12 13:58:19'),
(8, 'Sandals', 'footwear', '2025-07-12 13:58:19');

-- --------------------------------------------------------

--
-- Stand-in structure for view `sales_stats`
-- (See below for the actual view)
--
CREATE TABLE `sales_stats` (
`total_orders` bigint(21)
,`completed_orders` bigint(21)
,`total_revenue` decimal(32,2)
,`avg_order_value` decimal(14,6)
);

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `category` enum('general','order','product','technical','billing') DEFAULT 'general',
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','closed') DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `email_verified` tinyint(1) DEFAULT 0,
  `profile_completed` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `address`, `city`, `postal_code`, `country`, `avatar_url`, `is_admin`, `email_verified`, `profile_completed`, `email_verification_token`, `password_reset_token`, `password_reset_expires`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'andreialba2022@gmail.com', '$2y$10$52xRM/3sgw4aJoasHDy.R.qH/YDWTaTt0105b3VaVAAryV5G.kGGq', 'Admin', 'User', '', '', '', '', '', NULL, 1, 1, 1, NULL, NULL, NULL, '2025-07-17 00:02:00', '2025-06-26 07:33:34', '2025-07-16 16:02:00'),
(5, 'drei1', 'albaandrei0904@gmail.com', '$2y$10$5npptE1T.Q8cYsPb3kC6k.LTDc/hU91H7p0EZc.DSOIi3MMBij1bO', 'Andrei', 'Alba', '', '', '', '', '', 'uploads/avatars/avatar_5_1752473890.jpg', 0, 1, 1, NULL, '188450', '2025-07-15 18:44:42', '2025-07-16 14:05:42', '2025-06-26 13:04:24', '2025-07-16 06:05:42'),
(12, 'drei2', 'albaandrei87@gmail.com', '$2y$10$q4V66idA8JoakcYIVQZDluSxXhOct05jhH.XaoizCpkq6g./l6kR.', 'andrei', 'Alba', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, NULL, '2025-07-17 00:01:09', '2025-07-16 15:57:01', '2025-07-16 16:01:09');

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_stats`
-- (See below for the actual view)
--
CREATE TABLE `user_stats` (
`total_users` bigint(21)
,`verified_users` bigint(21)
,`admin_users` bigint(21)
,`new_users_30_days` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist_items`
--

CREATE TABLE `wishlist_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `product_stats`
--
DROP TABLE IF EXISTS `product_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `product_stats`  AS SELECT count(0) AS `total_products`, count(case when `products`.`is_featured` = 1 then 1 end) AS `featured_products`, count(case when `products`.`is_trending` = 1 then 1 end) AS `trending_products`, count(case when `products`.`stock_quantity` > 0 then 1 end) AS `in_stock_products`, count(case when `products`.`stock_quantity` = 0 then 1 end) AS `out_of_stock_products` FROM `products` ;

-- --------------------------------------------------------

--
-- Structure for view `sales_stats`
--
DROP TABLE IF EXISTS `sales_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sales_stats`  AS SELECT count(0) AS `total_orders`, count(case when `orders`.`status` = 'completed' then 1 end) AS `completed_orders`, coalesce(sum(case when `orders`.`status` = 'completed' then `orders`.`total_amount` end),0) AS `total_revenue`, coalesce(avg(case when `orders`.`status` = 'completed' then `orders`.`total_amount` end),0) AS `avg_order_value` FROM `orders` ;

-- --------------------------------------------------------

--
-- Structure for view `user_stats`
--
DROP TABLE IF EXISTS `user_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_stats`  AS SELECT count(0) AS `total_users`, count(case when `users`.`email_verified` = 1 then 1 end) AS `verified_users`, count(case when `users`.`is_admin` = 1 then 1 end) AS `admin_users`, count(case when `users`.`created_at` >= current_timestamp() - interval 30 day then 1 end) AS `new_users_30_days` FROM `users` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product_size` (`user_id`,`product_id`,`selected_size`),
  ADD KEY `idx_cart_user` (`user_id`),
  ADD KEY `idx_cart_product_size` (`product_id`,`selected_size`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `newsletter_subscriptions`
--
ALTER TABLE `newsletter_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `updated_by_admin` (`updated_by_admin`),
  ADD KEY `idx_orders_created_at` (`created_at`),
  ADD KEY `idx_orders_user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_order_status_history_order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_featured` (`is_featured`),
  ADD KEY `idx_products_trending` (`is_trending`),
  ADD KEY `idx_products_tags` (`tags`(100)),
  ADD KEY `fk_product_type` (`product_type_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product_review` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_size` (`product_id`,`size_code`);

--
-- Indexes for table `product_types`
--
ALTER TABLE `product_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_admin` (`is_admin`),
  ADD KEY `idx_users_verified` (`email_verified`);

--
-- Indexes for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_wishlist_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `newsletter_subscriptions`
--
ALTER TABLE `newsletter_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product_sizes`
--
ALTER TABLE `product_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT for table `product_types`
--
ALTER TABLE `product_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3376;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`updated_by_admin`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_type` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`),
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`);

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD CONSTRAINT `product_sizes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD CONSTRAINT `wishlist_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
