-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 01, 2025 at 11:57 PM
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
  `address_line` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pincode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `selected` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `address_line`, `city`, `state`, `pincode`, `selected`) VALUES
(1, 1, 'A 10, Ganga Nagar, Kudappanakunnu PO', 'Trivandrum', 'Kerala', '695043', 0),
(2, 1, 'Kalluzhathil House, Njaliyakuzhy', 'Kottayam', 'Kerala', '686538', 1),
(3, 2, 'Pathilchirayil House, Manaarkunnu', 'Kottayam', 'Kerala', '686562', 1),
(4, 1, 'test', 'kottayam', 'kerala', '38383', 0);

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `size` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`user_id`,`product_id`,`size`),
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
  `admin_confirmed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `size` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `quantity` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `has_sizes` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `brand`, `price`, `image_path`, `category`, `description`, `quantity`, `created_at`, `has_sizes`) VALUES
(2, 'Men Water Resistant Mid Ankle Hiking Shoes Beige - NH150', 'QUECHUA', 3599.00, 'images/products/68e5912b76d4e4.43466646.avif', 'Running', 'Waterproof, comfortable shoes that provide good grip even on slightly uneven, hiking trails. Affordably priced and stylish in color. Tested to work in mild rain', 176, '2025-08-21 08:43:11', 1),
(3, 'Football Ball Size 5 F550 - White', 'KIPSTA', 1099.00, 'images/products/68ca54fa1331b8.86109408.jpg', 'Football', 'The F550 hybrid has been approved by FIFA for your training sessions and matches. We\'ve designed it to give the perfect balance between durability and feel', 127, '2025-09-17 06:28:10', 0),
(5, 'Women\'s Fitted Leggings with a side pocket 120 - Comet Blue', 'DOMYOS', 1299.00, 'images/products/69046025047dc8.84105489.jpg', 'Fitness & Clothing', 'High-waisted sports legging with breathable and stretchy fabric for all your fitness activities. Available in plain and print options along with a phone pocket.', 156, '2025-10-31 07:07:17', 1),
(6, 'Men Puffer Jacket for Trekking - MT50 Black', 'FORCLAZ', 1699.00, 'images/products/69046278793fc4.39922476.jpg', 'Fitness & Clothing', 'your outings in cold weather', 126, '2025-10-31 07:17:12', 1),
(7, 'Road Bike Triban RC100 Drop Bar - Integrated Shifters, 7 Speed, Aluminium Frame', 'TRIBAN', 39999.00, 'images/products/690661f62b5f21.26387107.jpg', 'Cycling', 'We\'ve designed this bike specially for and with beginners: reassuring tyres, simple speed changes, and drop handelbars.\r\n\r\n\r\nBENEFITS\r\n\r\nversatility: Hybrid tyres, can be fitted with a 900 pannier rack\r\n\r\nefficiency: 6061 aluminium frame. Comfort road geometry\r\n\r\nease of use: Single-chainring drivetrain and curved handlebars with integrated shifter\r\n\r\nlifetime warranty: Lifetime warranty on: frame, stem, handlebars and fork. Details on btwin.com\r\n\r\ncycling comfort: Comfort geometry for a more upright position, 700 x 32 tyres', 40, '2025-11-01 19:39:34', 0),
(8, 'Cycle Hybrid Riverside 120 8 Speed Trigger Shifter Unisex Frame 28 In wheels', 'RIVERSIDE', 19999.00, 'images/products/69066264e32062.18316715.jpg', 'Cycling', 'Perfect bike for daily exercise and leisure riding.\r\n\r\nBENEFITS\r\n\r\nlifetime warranty: Lifetime warranty on the frame & fork and 2 years warranty on non-wearing parts\r\n\r\nversatility: Unisex frame (sizes S, M, L); 700C wheels; B\'Twin hybrid road/trail tyres.\r\n\r\nease of use: Single-chainring 8-speed drivetrain. Welded handlebar/stem. Quick-release.\r\n\r\ncycling comfort: Optimized frame geometry. Height-adjustable stem. 700X35 tyres.\r\n\r\n', 50, '2025-11-01 19:41:24', 0),
(9, 'Mountain Bike Rockrider ST100 Grey - Aluminium Frame, 21 Speed, 80mm Suspension', 'BTWIN', 27999.00, 'images/products/690662c4c5d0a8.84313062.jpg', 'Cycling', 'This 27.5\" mountain bike has been designed and developed to best suit getting started with mountain-bike touring in all weather, for rides upto 90 minutes\r\n\r\nBENEFITS\r\n\r\nrobustness: An ultra-strong MTB: double-walled rims, welded saddle, derailleur guard.\r\n\r\nlifetime warranty: ROCKRIDER offers a lifetime warranty on the frame, handlebars and stem\r\n\r\ncycling comfort: Enjoy touring: raised position, 80 mm suspension, hammock saddle, FLEX seat post\r\n\r\ndirectional control: Control your trajectory: V-Brake pads, tyres with side knobs\r\n\r\nefficiency: Light frame, 21 speeds, 27.5\" wheels, tyres with low rolling resistance\r\n', 62, '2025-11-01 19:43:00', 0),
(10, 'Lifelong Trike Cycle for Kids Cycle 2-5 Years - Tricycles for Boy & Girl - Baby Cycle - Bicycle for Kids - Bike for Kids with 3 EVA Wheels, Bell & Basket for Toys -Durable Tricycle 30kg Capacity', 'LIFELONG', 1499.00, 'images/products/690664a931ed54.04761353.jpg', 'Cycling', 'Lifelong Trike Cycle for Kids Cycle 2-5 Years - Tricycles for Boy & Girl - Baby Cycle - Bicycle for Kids - Bike for Kids with 3 EVA Wheels, Bell & Basket for Toys -Durable Tricycle 30kg Capacity\r\n\r\nLifelong tricycles for kids, known for their exceptional design and utility, ensure that your child can enjoy a safe and comfortable ride as they grow. Suitable for kids of 2 to 5 years, these innovative tricycles boast adaptable seats and handlebars that can be adjusted according to your child\'s development stage.\r\n\r\nOur kids’ bikes ensuring the utmost safety for infants and toddlers. With Lifelong Tricycles for Kids 2-5 years, we take pride in crafting products that not only provide countless hours of fun but also prioritize your child\'s comfort besides helping the parents with the easy storage baskets, and back support and the handle fitting knob for the kids.\r\n\r\n', 40, '2025-11-01 19:51:05', 0),
(18, 'Jogflow 190.1 Men Running Shoes– Cushioned, Lightweight, Flexible–Black, 20km/wk', 'KIPRUN', 2799.00, 'images/products/69066fbab91ec2.02050037.jpg', 'Running', 'Our design teams developed these lightweight men\'s running shoes with cushioning for running up to 20 km per week.\r\n\r\n', 136, '2025-11-01 20:38:18', 1),
(19, 'Kiprun Jogflow 190.1 Women\'s Running Shoes - Blue/Purple', 'KIPRUN', 3249.00, 'images/products/69067005ecd671.96654935.jpg', 'Running', 'Our design teams developed these lightweight and cushioned women\'s running shoes for running up to 20 km per week.\r\n\r\n', 280, '2025-11-01 20:39:24', 1),
(20, 'Run One Men\'s Running Shoes - Blue', 'DECATHLON', 999.00, 'images/products/69067044869b73.05498743.jpg', 'Running', 'Your versatile everyday shoe: fitness, walking, jogging. Comfortable and lightweight, it is ideal for all your sports adventures.\r\n\r\n', 191, '2025-11-01 20:40:36', 1),
(21, 'Running Socks Run100 Pack of 3 - White', 'KIPRUN', 399.00, 'images/products/6906708e1008f5.89819809.jpg', 'Running', 'These running socks protect your feet from blisters when you are out running.\r\n\r\n', 122, '2025-11-01 20:41:50', 0),
(23, 'Women\'s Running Tight Shorts - black', 'KALENJI', 899.00, 'images/products/69067134247f98.74268059.jpg', 'Running', 'Our design teams created these tight shorts for women running in hot weather.\r\n\r\n', 85, '2025-11-01 20:44:36', 1),
(24, 'Women\'s Running Short Leggings - Kiprun Run 100 Black', 'KALENJI', 1199.00, 'images/products/6906715973d902.08980533.jpg', 'Running', 'Our design teams created these cropped bottoms for women running in hot or cool weather.\r\n\r\n', 95, '2025-11-01 20:45:13', 1),
(25, 'Light, Quick dry, 3 Zip Pockets, Jog Fit-Women Running Trackpant Black', 'KALENJI', 1699.00, 'images/products/690671846bd4c2.56573966.jpg', 'Running', 'Lightweight, breathable and fluid running trousers. Perfect for running in all seasons.\r\n\r\n', 74, '2025-11-01 20:45:56', 1),
(32, 'Light Support Fitness Sports Bra 140 - Mauve', 'DOMYOS', 1699.00, 'images/products/6906755a023a50.18599670.jpg', 'Fitness & Clothing', 'This light support sports bra is perfect for doing gentle and low-impact activities such as toning, Pilates and strength training.\r\n\r\n', 161, '2025-11-01 21:02:18', 0),
(33, 'Women\'s Sports Bra with Thin Cross-Over Straps - Indigo Blue - XS', 'DECATHLON', 799.00, 'images/products/6906759c4971e2.32271494.jpg', 'Fitness & Clothing', 'Sporty, stylish sports bra ideal for fitness, offering lightweight support and optimum comfort.\r\n\r\n', 425, '2025-11-01 21:03:24', 0),
(34, 'Women Gym Leggings High-Waist - Print', 'DOMYOS', 2099.00, 'images/products/690675cb35c747.29155256.jpg', 'Fitness & Clothing', 'Ultra-trendy printed leggings so you can go to the gym in comfort!\r\n\r\n', 269, '2025-11-01 21:04:11', 1),
(35, 'Men Running T-shirt Run Dry - Prussian Blue', 'DECATHLON', 799.00, 'images/products/690675ebce66f0.31081829.jpg', 'Fitness & Clothing', 'Men\'s breathable running T-shirt keeps you dry when running in hot weather.\r\n\r\n', 594, '2025-11-01 21:04:43', 1),
(36, 'Men\'s Warm Long-Sleeved Zip Running T-Shirt - KIPRUN Run 100 Warm - Black', 'KALENJI', 1499.00, 'images/products/69067620b37295.48398022.jpg', 'Fitness & Clothing', 'Our team of designers developed this men\'s breathable long-sleeved running T-shirt to keep you warm when running in the winter.\r\n\r\n', 137, '2025-11-01 21:05:36', 1),
(37, 'Cross Training and Weight Training 7.5 kg Recycled Cast Iron Dumbbell', 'CORENGTH', 4999.00, 'images/products/69067638e59cb7.22074657.jpg', 'Fitness & Clothing', 'The wide variety of exercises you can do with this 7.5 kg hexagonal dumbbell will help you tone and firm your body.\r\n\r\n', 0, '2025-11-01 21:06:00', 0),
(38, 'Treadmill T900D Foldable, Upto 18 kmph, 10% Incl, Smart, Low-Noise, Max 130 kg', 'DOMYOS', 99999.00, 'images/products/6906765aacc784.55270827.jpg', 'Fitness & Clothing', 'An ideal treadmill designed for running or walking up to 5 hours per week with a max speed of 18 km/hr and 10% incline. It has 32 in-built training programmes.\r\n\r\n', 525, '2025-11-01 21:06:34', 0),
(39, 'Men\'s Cardio Fitness Tracksuit Jacket FJA 100 - Black', 'DOMYOS', 999.00, 'images/products/690676797d5ac1.94745741.jpg', 'Fitness & Clothing', 'This lightweight, breathable jacket is perfect for indoor and outdoor workouts, making sure you are comfortable while working out! It consists of two pockets\r\n\r\n', 991, '2025-11-01 21:07:05', 1),
(40, 'Women’s waterproof hiking anorak -8°C, NH500 - Green', 'QUECHUA', 7799.00, 'images/products/6906769e5e33f7.32076199.jpg', 'Fitness & Clothing', 'Our female hikers designed this short jacket to keep you warm even in the city, thanks to its dynamic cut that moves with you everywhere you go.\r\n\r\n', 534, '2025-11-01 21:07:42', 1),
(41, 'Women\'s Zip-Up Fitness Fleece Sweatshirt - Aged Pink', 'DOMYOS', 2299.00, 'images/products/690676c3cf6496.82529704.jpg', 'Fitness & Clothing', 'This zip-up sweatshirt has been designed with 270 g/m² fleece for warmth and softness. It\'s a timeless piece you\'ll wear for\r\n\r\n', 180, '2025-11-01 21:08:19', 1),
(42, 'Ligue 1 McDonald\'s Official Replica Ball 2024-2025 Size 5', 'KIPSTA', 4999.00, 'images/products/690678b5491464.37037418.jpg', 'Football', 'Our design teams bring you this official replica of the Ligue 1 McDonald\'s or Ligue 2 BKT ball.\r\n\r\n', 444, '2025-11-01 21:16:37', 0),
(12, 'Mountain Bike Helmet EXPL 50 - Black', 'ROCKRIDER', 1499.00, 'images/products/69066552829f36.39534375.jpg', 'Cycling', 'Designed for your first rides.\r\n\r\nBENEFITS\r\n\r\nventilation: 15 ventilation channels\r\n\r\nweight: Size M: 320 g Size L: 360 g\r\n\r\nstability: Adjust the helmet using the 2 buckles under the ears and one under the chin\r\n\r\nimpact protection: Complies with EN 1078.\r\n\r\nuser comfort: Removable foam padding\r\n\r\nadjustable: Tightening knob', 54, '2025-11-01 19:53:54', 0),
(13, 'Road Cycling Helmet RoadR 500 MIPS - Black', 'VAN RYSEL', 7999.00, 'images/products/69066593e08a34.12409519.jpg', 'Cycling', 'A helmet designed for cyclists looking for a compact, well-ventilated helmet that is very comfortable. Integration of the MIPS anti-rotational system\r\n\r\nBENEFITS\r\n\r\nventilation: The helmet\'s grille and inner channels facilitate continuous air flow.\r\n\r\ncompatibility: compatible with our ViooClip lights.\r\n\r\nanatomic design: Ponytail-compatible turn ring\r\n\r\nease of use: new turn-ring system developed by our teams of engineers.\r\n\r\naerodynamics: Benefits from ventilation studies carried out on the Aerofit 900 helmet\r\n\r\nlightweight: In-Mold helmet that is light but less compact than its big brother the 900\r\n', 38, '2025-11-01 19:54:59', 0),
(14, 'Ski and Snowboard Helmet Bag - Black', 'WEDZE', 1999.00, 'images/products/690665c5474b63.72961750.jpg', 'Cycling', 'Want to keep your helmet intact when not in use? This handy cover makes it easy.\r\n\r\nBENEFITS\r\n\r\ncompatibility: This cover is multi-sized and suitable for helmets with a visor.\r\n\r\neasy maintenance: The cover is machine-washable at 30°C.\r\n\r\nease of use: Wide opening and drawstring adjustment.\r\n\r\n', 66, '2025-11-01 19:55:49', 0),
(15, 'Cycling Helmet Liner - Black', 'VAN RYSEL', 699.00, 'images/products/690666126945a6.11898149.jpg', 'Cycling', 'Our engineers have designed this helmet liner for cyclists wanting to wear a lighter product in exchange for a thinner and stretchier product\r\n\r\nBENEFITS\r\n\r\nanatomic design: Very stretchy fabric for good coverage of most head sizes and shapes.\r\n\r\nmoisture management: Micro-knit material lets perspiration escape very effectively. Quick-drying\r\n\r\nwarmth: Provides extra warmth when needed while cycling in chilly weather.\r\n', 64, '2025-11-01 19:57:06', 0),
(17, 'Mountain Bike Rockrider ST20 Low Frame - Steel Frame, Single Speed, MTB Tyres', 'ROCKRIDER', 11999.00, 'images/products/690666974f4bd9.23806970.jpg', 'Cycling', 'Designed for leisure cycling on off-road trails and urban conditions.\r\n\r\nBENEFITS\r\n\r\nease of use: Easy to adjust the seat height and remove the front wheel with a quick-release.\r\n\r\ncycling comfort: Soft grips on the handlebar and ergonomic foam saddle for seating comfort.\r\n\r\nefficiency: Steel bottom bracket near the pedals for smooth rolling and longer life.\r\n\r\nlifetime warranty: Lifetime warranty on the frame & fork and 2 years warranty on non-wearing parts\r\n', 67, '2025-11-01 19:59:19', 0),
(26, '500 Ml Flexible Trail Running Water Bottle-Blue', 'KIPRUN', 1399.00, 'images/products/6906719d3d9545.57826733.jpg', 'Running', 'Our team of trail running enthusiasts designed this 500 mL flexible flask for comfortably transporting your drinks during your trail runs.\r\n\r\n', 244, '2025-11-01 20:46:21', 0),
(27, 'Unisex Running Belt - KIPRUN Basic 2 Mauve', 'KIPRUN', 559.00, 'images/products/690671bdf34613.17375044.jpg', 'Running', 'Running belt for carrying the essentials (phone, keys, card) without feeling hindered so that your hands are free while you jog or walk.\r\n\r\n', 420, '2025-11-01 20:46:53', 0),
(28, 'Running Multi-Purpose Headband- Grey', 'KIPRUN', 699.00, 'images/products/690671da050511.96339291.jpg', 'Running', 'Versatile, multi-sport running neck warmer to protect your head and neck from the wind/cold during your runs over all distances (training and competitions)\r\n\r\n', 240, '2025-11-01 20:47:22', 0),
(29, 'Trail Running Bottle Holder Belt 500 ml - Sold with 500ml bottle', 'KIPRUN', 1399.00, 'images/products/690671f8bf81b0.96938258.jpg', 'Running', 'Our team of trail running enthusiasts designed this belt for carrying your essentials during short runs and trail running sessions.\r\n\r\n', 267, '2025-11-01 20:47:52', 0),
(30, 'KIPRUN KD900 Women\'s Running Shoes -Coral', 'KIPRUN', 15599.00, 'images/products/690672288d0da5.14470586.jpg', 'Running', 'Our teams developed these dynamic, shock-absorbing and lightweight women\'s running shoes for runners seeking propulsion with every stride (from 10 to 42km)\r\n\r\n', 75, '2025-11-01 20:48:40', 1),
(31, 'WOMEN\'S KIPRUN KS 500 2 RUNNING SHOES - GREY AND CORAL', 'KIPRUN', 9099.00, 'images/products/6906724e5f37e7.02687666.jpg', 'Running', 'The KIPRUN KS500 2 model is your entry into the world of high-performance sports.\r\n\r\n', 147, '2025-11-01 20:49:18', 1),
(43, 'Size 5 Machine-Stitched Football Training Ball - White', 'KIPSTA', 1099.00, 'images/products/690678dd234501.88213597.jpg', 'Football', 'We\'ve designed and developed the Training Ball for young footballers who have just joined a club or who play occasionally.\r\n\r\n', 522, '2025-11-01 21:17:17', 0),
(44, 'Adult Short-Sleeved Football Shirt Viralto Topo - Black & Neon Pink', 'KIPSTA', 1049.00, 'images/products/690678fddb3dd0.24475800.jpg', 'Football', 'Our football designers have developed this Viralto football shirt to wear for training sessions and matches up to 3 times a week.\r\n\r\n', 527, '2025-11-01 21:17:49', 1),
(45, 'Football Jacket Training Essential - Black/Grey', 'KIPSTA', 1499.00, 'images/products/6906791dd2da51.20355598.jpg', 'Football', 'Our football designers have developed the Essential football jacket to wear for training sessions and warm-ups up to twice a week.\r\n\r\n', 330, '2025-11-01 21:18:21', 1),
(46, 'Women\'s Football Shorts Viralto - Black', 'KIPSTA', 1299.00, 'images/products/690679445c0b52.76028092.jpg', 'Football', 'Our team of football enthusiasts has developed these Viralto women\'s football shorts for your matches and training, up to 3 times per week.\r\n\r\n', 101, '2025-11-01 21:19:00', 1),
(47, 'Men Football Shorts F500 Black', 'KIPSTA', 999.00, 'images/products/6906796a44db82.10548908.jpg', 'Football', 'Our football designers have developed these Viralto football shorts to wear for training sessions and matches up to 3 times a week!\r\n\r\n', 178, '2025-11-01 21:19:38', 1),
(48, 'Football Ball Training Size 3 Below 8 years First Kick Blue', 'KIPSTA', 699.00, 'images/products/69067989949e66.10546095.jpg', 'Football', 'We designed and developed this ball for young beginner footballers who play occasionally.\r\n\r\n', 520, '2025-11-01 21:20:09', 0),
(49, 'Adult Short-Sleeved Football Shirt Viralto Topo - Neon Pink & Black', 'KIPSTA', 1049.00, 'images/products/690679cee3a262.78924505.jpg', 'Football', 'Our football designers have developed this Viralto football shirt to wear for training sessions and matches up to 3 times a week.\r\n\r\n', 384, '2025-11-01 21:21:18', 1),
(50, 'Women\'s Football Jersey Shirt - Dark Brown', 'KIPSTA', 1499.00, 'images/products/690679eb8866d7.71818555.jpg', 'Football', 'Our passionate football team designed this women\'s Viralto football shirt for matches and training sessions.\r\n\r\n', 214, '2025-11-01 21:21:47', 1),
(51, 'Head Move Skirt', 'HEAD', 1299.00, 'images/products/69068325e071a4.57442294.avif', 'Badminton', 'The Head Move skirt combines a sporty and feminine design, ideal for the court.\r\n\r\n', 94, '2025-11-01 22:01:09', 1),
(52, 'Kids Badminton Racket BR 100 Pink', 'KUIKMA', 499.00, 'images/products/6906833d1ac545.15362918.jpg', 'Badminton', 'Kid beginner player who looks for racket whcih is easy to handle with great value price ratio.\r\n\r\n', 444, '2025-11-01 22:01:33', 0),
(53, 'Kids Badminton Racket 85g Aluminium BR 100 Green', 'KUIKMA', 599.00, 'images/products/6906835f8b9f00.55240964.jpg', 'Badminton', 'Kid beginner player who looks for racket which is easy to handle with great value price ratio.\r\n\r\n', 555, '2025-11-01 22:02:07', 0),
(54, 'Adult Badminton Racket BR 100 Blue', 'DECATHLON', 499.00, 'images/products/6906837fe86468.01995827.jpg', 'Badminton', 'occasional and/or beginner badminton players looking for a sturdy and powerful racket at an affordable price.\r\n\r\n', 542, '2025-11-01 22:02:39', 0),
(55, 'ADULT BADMINTON RACKET BR 190 SET PARTNER GREEN BEIGE', 'PERFLY', 2899.00, 'images/products/690683974ce5a3.74501513.jpg', 'Badminton', 'Beginner badminton player who looking for a tolerant racket with power!\r\n\r\n', 222, '2025-11-01 22:03:03', 0),
(56, 'Adult badminton racket br 100 set Starter White', 'DECATHLON', 1499.00, 'images/products/690683b90acb38.56017695.jpg', 'Badminton', 'Beginner badminton player looking for a solid and powerful racquet at an affordable price.\r\n\r\n', 222, '2025-11-01 22:03:37', 0),
(57, 'Badminton unstrung racket adult, BR Sensation 990 Pro Navy', 'KUIKMA', 12999.00, 'images/products/690683d2a092f2.86758582.jpg', 'Badminton', 'Pro badminton player who looking for a versatile racket with great control & Precision to enhancing overall performance!\r\n\r\n', 525, '2025-11-01 22:04:02', 0),
(58, 'Women Heva One-Piece Swimsuit with Skirt Supportive & Quick-Dry Purple', 'NABAIJI', 1299.00, 'images/products/69068447732c29.07204218.jpg', 'Swimming', 'This 1-piece swimsuit with skirt was designed for swimmers who want to enjoy the water with a covering and original swimsuit.\r\n\r\n', 203, '2025-11-01 22:05:59', 1),
(59, 'Badminton skirt lite 560 women Storm Blue', 'PERFLY', 1849.00, 'images/products/690684c2628600.07476747.jpg', 'Badminton', 'The intermediate players who are looking for a technical and nice-looking skirt for her regular practice and games.\r\n\r\n', 422, '2025-11-01 22:07:40', 0),
(60, 'Men Tennis Shorts Quick Dry Regular Fit Black', 'KUIKMA', 399.00, 'images/products/690686f5770bd3.37902104.jpg', 'Tennis', 'low intensity sporting activity or for casual wear. The TSH100 is an above knee length shorts. It is lightweight and can carry upto 3 tennis balls per pocket.\r\n\r\n', 221, '2025-11-01 22:17:25', 1),
(61, 'Men Tennis Jacket - TJA500 Black/Grey', 'KUIKMA', 2199.00, 'images/products/6906871a77a603.47653925.jpg', 'Tennis', 'Our design teams created this light jacket for tennis players.\r\n\r\n', 340, '2025-11-01 22:18:02', 1),
(62, 'Men Tennis Shorts Quick Dry Regular Fit Navy', 'KUIKMA', 599.00, 'images/products/6906874e4aa158.53114037.jpg', 'Tennis', 'low intensity sporting activity or for casual wear. The TSH100 is an above knee length shorts. It is lightweight and can carry upto 3 tennis balls per pocket.\r\n\r\n', 286, '2025-11-01 22:18:54', 1),
(63, 'Tennis Racket Aluminium TR100', 'ARTENGO', 1799.00, 'images/products/6906876cba7540.84246100.jpg', 'Tennis', 'Our design team developed this product so you can try out tennis at an affordable price.\r\n\r\n', 242, '2025-11-01 22:19:24', 0),
(64, 'TR160 Graph Adult Tennis Racket 270 g - White', 'ARTENGO', 5999.00, 'images/products/69068783572869.13301354.jpg', 'Tennis', 'providing best handling for first practice sessions. It is a pure graphite racket with excellent manoeuvrability.\r\n\r\n', 444, '2025-11-01 22:19:47', 0),
(65, '58 cm Tennis Cap TC 500 - Dark Pink', 'KUIKMA', 549.00, 'images/products/690687b340f4c6.96769716.jpg', 'Tennis', 'TENNIS players looking for a lightweight, comfortable cap adapted for tennis or other sports. This cap protects your face from sun and absorbs perspiration.\r\n\r\n', 222, '2025-11-01 22:20:35', 0),
(66, 'Tennis Cap Size 58 TC 900 - Blue', 'KUIKMA', 799.00, 'images/products/690687cea77325.55627603.jpg', 'Tennis', 'tennis players looking for a lightweight, comfortable, highly technical cap to absorb and block perspiration during play.\r\n\r\n', 524, '2025-11-01 22:21:02', 0),
(67, 'TP 100 Tennis Wristband - Navy', 'KUIKMA', 299.00, 'images/products/690687e8bcd6b2.96994517.jpg', 'Tennis', 'playing tennis, or other racket sports, in HOT WEATHER.\r\n\r\n', 241, '2025-11-01 22:21:28', 0),
(68, 'Women Tennis Skirt - Dry 500 Navy', 'KUIKMA', 999.00, 'images/products/6906889a1a5ca5.62751715.jpg', 'Tennis', 'Our team has designed this tennis skirt for matches and training sessions.Wear it all year round, whatever the temperature.\r\n\r\n', 278, '2025-11-01 22:22:12', 1),
(69, 'Women\'s Tennis Crew-Neck T-Shirt TTS Dry - Celadon Green', 'KUIKMA', 1499.00, 'images/products/69068857a85cc6.00328023.jpg', 'Tennis', 'We designed this tennis T-shirt for training and matches. Wear it all year round, whatever the temperature.\r\n\r\n', 238, '2025-11-01 22:23:19', 1),
(70, 'Women\'s Swimming Una Top Stretchable & Comfortable Dark Blue', 'NABAIJI', 1999.00, 'images/products/69068b01d31672.57340892.jpg', 'Swimming', 'Our design team created this swimming top for female beginners who swim regularly.\r\n\r\n', 243, '2025-11-01 22:34:41', 1),
(71, 'Women Swimming Top Una Pyva Comfortable & Lightweight Navy', 'NABAIJI', 1899.00, 'images/products/69068b50498c73.34057293.jpg', 'Swimming', 'female beginners who swim regularly and want their full body to be covered.\r\n\r\n', 191, '2025-11-01 22:36:00', 1),
(72, 'Women Swimming Top UNA Leo Stylish & Supportive Black', 'NABAIJI', 1999.00, 'images/products/69068b6f7b8682.03235455.jpg', 'Swimming', 'female beginners who swim regularly and want their full body to be covered.\r\n\r\n', 0, '2025-11-01 22:36:31', 0),
(73, 'Women\'s One Piece Long Sleeve Surf Swimsuit- CN JANE -GLORY BLK', 'OLAIAN', 2299.00, 'images/products/69068b88643425.37186714.jpg', 'Swimming', 'As female surfers of all levels, we designed this swimsuit for intermediate surfers surfing waves under one meter.\r\n\r\n', 0, '2025-11-01 22:36:56', 1),
(74, 'Adult Swimming Cap Silicone 56-60 Cm Quick-Dry & Lightweight White', 'NABAIJI', 499.00, 'images/products/69068ba228f931.18834884.jpg', 'Swimming', 'Need a cap that stays in place during laps? Then the silicone bathing cap is for you.\r\n\r\n', 1, '2025-11-01 22:37:22', 1),
(75, 'Adult Swimming Goggles Men Women UV Protection Mirror Lenses Fast 900 Blue', 'NABAIJI', 2699.00, 'images/products/69068bb68c56d5.68265034.jpg', 'Swimming', 'Swimming goggles for advanced swimmers and pool specialists looking for performance in both training and competition.\r\n\r\n', 2, '2025-11-01 22:37:42', 0),
(76, 'Women’s surfing bikini crop top - Andrea black and white - UK:36C EU:80C', 'OLAIAN', 2299.00, 'images/products/69068c11a94c53.24894184.jpg', 'Swimming', 'As female surfers of all levels, we designed this swimsuit for intermediate surfers surfing waves under one meter.\r\n\r\n', 0, '2025-11-01 22:39:13', 0),
(77, 'Women\'s boardshorts - Tini black and white', 'OLAIAN', 1949.00, 'images/products/69068c3a261369.61499884.jpg', 'Swimming', 'The perfect swim shorts for surfers who want to combine comfort and style. To be worn over swimwear.\r\n\r\n', 131, '2025-11-01 22:39:54', 1),
(78, 'Men’s UPF50+ short-sleeved surf top - smoked black with chequered pattern', 'OLAIAN', 1199.00, 'images/products/69068c5b50ade6.32387935.jpg', 'Swimming', 'This UV-protection t-shirt is ideal for surfing in warm water, for sessions lasting up to 2 hours\r\n\r\n', 208, '2025-11-01 22:40:27', 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_sizes`
--

DROP TABLE IF EXISTS `product_sizes`;
CREATE TABLE IF NOT EXISTS `product_sizes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `size` varchar(10) NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=198 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_sizes`
--

INSERT INTO `product_sizes` (`id`, `product_id`, `size`, `stock`) VALUES
(4, 2, '8', 80),
(3, 2, '6', 96),
(45, 5, '3XL', 12),
(44, 5, '2XL', 50),
(43, 5, 'XL', 33),
(42, 5, 'L', 9),
(41, 5, 'M', 24),
(40, 5, 'S', 28),
(50, 6, '2XL', 19),
(49, 6, 'XL', 18),
(48, 6, 'L', 31),
(47, 6, 'M', 42),
(46, 6, 'S', 16),
(51, 18, '6', 12),
(52, 18, '6.5', 24),
(53, 18, '7', 54),
(54, 18, '8', 46),
(62, 19, '7.5', 87),
(61, 19, '7', 45),
(60, 19, '6.5', 24),
(59, 19, '6', 124),
(63, 20, '6', 12),
(64, 20, '6.5', 24),
(65, 20, '7', 57),
(66, 20, '7.5', 55),
(67, 20, '8', 43),
(68, 23, 'S', 44),
(69, 23, 'M', 29),
(70, 23, 'L', 12),
(71, 24, 'S', 42),
(72, 24, 'M', 12),
(73, 24, 'L', 24),
(74, 24, 'XL', 17),
(75, 25, 'S', 24),
(76, 25, 'M', 18),
(77, 25, 'L', 20),
(78, 25, 'XL', 12),
(79, 30, '6', 24),
(80, 30, '6.5', 12),
(81, 30, '7', 27),
(82, 30, '8', 12),
(83, 31, '6', 12),
(84, 31, '6.5', 25),
(85, 31, '7', 25),
(86, 31, '7.5', 43),
(87, 31, '8', 3),
(88, 31, '8.5', 34),
(89, 31, '9', 5),
(101, 35, 'M', 22),
(100, 35, 'S', 125),
(99, 34, 'XL', 52),
(98, 34, 'L', 41),
(97, 34, 'M', 152),
(96, 34, 'S', 24),
(102, 35, 'L', 422),
(103, 35, 'XL', 25),
(104, 36, 'S', 52),
(105, 36, 'M', 21),
(106, 36, 'L', 52),
(107, 36, 'XL', 12),
(108, 39, 'S', 125),
(109, 39, 'M', 521),
(110, 39, 'L', 121),
(111, 39, 'XL', 224),
(112, 40, 'S', 12),
(113, 40, 'M', 52),
(114, 40, 'L', 255),
(115, 40, 'XL', 215),
(116, 41, 'S', 52),
(117, 41, 'M', 21),
(118, 41, 'L', 55),
(119, 41, 'XL', 52),
(120, 44, 'S', 52),
(121, 44, 'M', 251),
(122, 44, 'L', 212),
(123, 44, 'XL', 12),
(124, 45, 'S', 12),
(125, 45, 'M', 52),
(126, 45, 'L', 254),
(127, 45, 'XL', 12),
(128, 46, 'S', 12),
(129, 46, 'M', 21),
(130, 46, 'L', 44),
(131, 46, 'XL', 24),
(132, 47, 'S', 12),
(133, 47, 'M', 34),
(134, 47, 'L', 66),
(135, 47, 'XL', 66),
(136, 49, 'S', 125),
(137, 49, 'M', 25),
(138, 49, 'L', 12),
(139, 49, 'XL', 222),
(140, 50, 'S', 25),
(141, 50, 'M', 122),
(142, 50, 'L', 42),
(143, 50, 'XL', 25),
(144, 51, 'S', 24),
(145, 51, 'M', 15),
(146, 51, 'L', 55),
(147, 58, 'S', 12),
(148, 58, 'M', 124),
(149, 58, 'L', 42),
(150, 58, 'XL', 25),
(151, 60, 'S', 24),
(152, 60, 'M', 52),
(153, 60, 'L', 121),
(154, 60, 'XL', 24),
(155, 61, 'S', 55),
(156, 61, 'M', 52),
(157, 61, 'L', 34),
(158, 61, 'XL', 56),
(159, 61, '2XL', 77),
(160, 61, '3XL', 66),
(161, 62, 'S', 55),
(162, 62, 'M', 66),
(163, 62, 'L', 77),
(164, 62, 'XL', 88),
(176, 68, 'XL', 57),
(175, 68, 'L', 67),
(174, 68, 'M', 77),
(173, 68, 'S', 77),
(169, 69, 'S', 52),
(170, 69, 'M', 44),
(171, 69, 'L', 66),
(172, 69, 'XL', 76),
(177, 70, 'S', 24),
(178, 70, 'M', 52),
(179, 70, 'L', 25),
(180, 70, 'XL', 55),
(181, 70, '2XL', 54),
(182, 70, '3XL', 33),
(183, 71, 'S', 14),
(184, 71, 'M', 22),
(185, 71, 'L', 55),
(186, 71, 'XL', 67),
(187, 71, '2XL', 22),
(188, 71, '3XL', 11),
(189, 74, 'M', 1),
(190, 77, 'S', 25),
(191, 77, 'M', 52),
(192, 77, 'L', 42),
(193, 77, 'XL', 12),
(194, 78, 'S', 52),
(195, 78, 'M', 52),
(196, 78, 'L', 52),
(197, 78, 'XL', 52);

-- --------------------------------------------------------

--
-- Table structure for table `support_messages`
--

DROP TABLE IF EXISTS `support_messages`;
CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `sender_type` enum('user','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`)
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','active','resolved') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
(2, 5);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
