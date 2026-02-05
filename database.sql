-- Smart Inventory System - Full Schema and Seed
-- Usage: Import this file into MySQL to create the database with seed data

DROP DATABASE IF EXISTS `inventory`;
CREATE DATABASE `inventory` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `inventory`;

-- Users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` varchar(20) DEFAULT 'user',
  `security_question` varchar(255) NOT NULL,
  `security_answer` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_users_username` (`username`)
) ENGINE=InnoDB;

-- Categories
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_categories_name` (`name`)
) ENGINE=InnoDB;

-- Subcategories
CREATE TABLE `subcategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_cat_subcat` (`category_id`,`name`),
  CONSTRAINT `fk_subcategories_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Product Types
CREATE TABLE `product_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_product_types_name` (`name`)
) ENGINE=InnoDB;

-- Product Items
CREATE TABLE `product_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_type_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_type_item` (`product_type_id`,`name`),
  CONSTRAINT `fk_product_items_type` FOREIGN KEY (`product_type_id`) REFERENCES `product_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Companies / Brands
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `contact_info` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_companies_name` (`name`)
) ENGINE=InnoDB;

-- Map Type/Item to default Category/Subcategory
CREATE TABLE IF NOT EXISTS `type_item_category_map` (
  `product_type_id` INT NOT NULL,
  `product_item_id` INT NOT NULL,
  `category_id` INT NULL,
  `subcategory_id` INT NULL,
  PRIMARY KEY (`product_item_id`),
  KEY `idx_ticm_type` (`product_type_id`),
  CONSTRAINT `fk_ticm_type` FOREIGN KEY (`product_type_id`) REFERENCES `product_types`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticm_item` FOREIGN KEY (`product_item_id`) REFERENCES `product_items`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticm_cat` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ticm_subcat` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Map Items to Brands
CREATE TABLE `company_item_map` (
  `product_item_id` INT NOT NULL,
  `company_id` INT NOT NULL,
  PRIMARY KEY (`product_item_id`,`company_id`),
  CONSTRAINT `fk_map_item` FOREIGN KEY (`product_item_id`) REFERENCES `product_items`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_map_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Products
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_type_id` int(11) DEFAULT NULL,
  `product_item_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `quality` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `subcategory_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT 5,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_products_name` (`name`),
  CONSTRAINT `fk_products_type` FOREIGN KEY (`product_type_id`) REFERENCES `product_types`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_item` FOREIGN KEY (`product_item_id`) REFERENCES `product_items`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_subcategory` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Customers
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) UNIQUE NOT NULL,
  `phone` varchar(20) NOT NULL,
  `customer_type` enum('regular','wholesale','vip','corporate') DEFAULT 'regular',
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `credit_limit` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `is_active` boolean DEFAULT TRUE,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Transactions
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `type` enum('buy','sell') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `customer_id` int(11) NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tx_product` (`product_id`),
  KEY `idx_tx_user` (`user_id`),
  KEY `idx_tx_customer` (`customer_id`),
  CONSTRAINT `fk_tx_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tx_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- User login tracking
CREATE TABLE `user_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `role` varchar(20) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Seed: Admin user (password: password)
INSERT INTO `users` (`username`, `password`, `email`, `phone`, `full_name`, `company`, `address`, `role`, `security_question`, `security_answer`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@inventory.com', '9999999999', 'System Administrator', 'Inventory System', 'Admin Address', 'admin', 'What is your favorite color?', 'blue');

-- Seed: Product Types
INSERT INTO `product_types` (`name`,`icon`) VALUES
('Electronics','fas fa-laptop'),
('Automotive','fas fa-car'),
('Grocery','fas fa-shopping-basket'),
('Stationery','fas fa-pencil-alt'),
('Home & Kitchen','fas fa-home'),
('Fashion','fas fa-tshirt'),
('Sports & Fitness','fas fa-dumbbell'),
('Beauty & Health','fas fa-heart'),
('Books & Media','fas fa-book'),
('Toys & Games','fas fa-gamepad'),
('Furniture','fas fa-couch'),
('Mobile & Accessories','fas fa-mobile-alt'),
('Computers & Laptops','fas fa-desktop'),
('Appliances','fas fa-blender'),
('Footwear','fas fa-shoe-prints');

-- Seed: Items
-- Electronics
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Television' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Smart TV' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'LED TV' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Refrigerator' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Washing Machine' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Air Conditioner' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Microwave Oven' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Water Purifier' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Vacuum Cleaner' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Iron' FROM product_types WHERE name='Electronics';

-- Mobile & Accessories
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Smartphone' FROM product_types WHERE name='Mobile & Accessories';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Feature Phone' FROM product_types WHERE name='Mobile & Accessories';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Mobile Cover' FROM product_types WHERE name='Mobile & Accessories';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Screen Protector' FROM product_types WHERE name='Mobile & Accessories';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Power Bank' FROM product_types WHERE name='Mobile & Accessories';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Mobile Charger' FROM product_types WHERE name='Mobile & Accessories';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Earphones' FROM product_types WHERE name='Mobile & Accessories';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Bluetooth Headset' FROM product_types WHERE name='Mobile & Accessories';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Wireless Earbuds' FROM product_types WHERE name='Mobile & Accessories';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Selfie Stick' FROM product_types WHERE name='Mobile & Accessories';

-- Computers & Laptops
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Laptop' FROM product_types WHERE name='Computers & Laptops';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Desktop Computer' FROM product_types WHERE name='Computers & Laptops';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Gaming Laptop' FROM product_types WHERE name='Computers & Laptops';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Tablet' FROM product_types WHERE name='Computers & Laptops';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Monitor' FROM product_types WHERE name='Computers & Laptops';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Keyboard' FROM product_types WHERE name='Computers & Laptops';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Mouse' FROM product_types WHERE name='Computers & Laptops';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Webcam' FROM product_types WHERE name='Computers & Laptops';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Printer' FROM product_types WHERE name='Computers & Laptops';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'External Hard Drive' FROM product_types WHERE name='Computers & Laptops';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Pen Drive' FROM product_types WHERE name='Computers & Laptops';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Router' FROM product_types WHERE name='Computers & Laptops';

-- Automotive
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Car' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Motorcycle' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Scooter' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Bicycle' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Engine Oil' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Car Battery' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Tires' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Brake Pads' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Car Accessories' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Helmet' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Car Polish' FROM product_types WHERE name='Automotive';

-- Grocery
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Basmati Rice' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Regular Rice' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Wheat Flour (Atta)' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Maida' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Toor Dal' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Moong Dal' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Chana Dal' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Sugar' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Salt' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Cooking Oil' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Ghee' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Tea' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Coffee' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Milk' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Bread' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Biscuits' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Namkeen' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Chips' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Noodles' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Pasta' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Spices' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Pickles' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Jam' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Honey' FROM product_types WHERE name='Grocery';

-- Stationery
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Ball Pen' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Gel Pen' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Pencil' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Eraser' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Sharpener' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Notebook' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Register' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'A4 Paper' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'File Folder' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Marker' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Highlighter' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Stapler' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Scissors' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Glue Stick' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Tape' FROM product_types WHERE name='Stationery';

-- Home & Kitchen
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Pressure Cooker' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Mixer Grinder' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Gas Stove' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Induction Cooktop' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Non-Stick Cookware' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Dinner Set' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Water Bottle' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Lunch Box' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Kitchen Knife Set' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Chopping Board' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Storage Container' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Bedsheet' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Pillow' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Curtains' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Table Lamp' FROM product_types WHERE name='Home & Kitchen';

-- Fashion
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'T-Shirt' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Shirt' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Jeans' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Trousers' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Kurta' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Saree' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Salwar Suit' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Jacket' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Sweater' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Watch' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Sunglasses' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Belt' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Wallet' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Handbag' FROM product_types WHERE name='Fashion';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Backpack' FROM product_types WHERE name='Fashion';

-- Footwear
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Sports Shoes' FROM product_types WHERE name='Footwear';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Casual Shoes' FROM product_types WHERE name='Footwear';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Formal Shoes' FROM product_types WHERE name='Footwear';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Sandals' FROM product_types WHERE name='Footwear';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Slippers' FROM product_types WHERE name='Footwear';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Flip Flops' FROM product_types WHERE name='Footwear';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Boots' FROM product_types WHERE name='Footwear';

-- Sports & Fitness
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Cricket Bat' FROM product_types WHERE name='Sports & Fitness';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Cricket Ball' FROM product_types WHERE name='Sports & Fitness';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Football' FROM product_types WHERE name='Sports & Fitness';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Badminton Racket' FROM product_types WHERE name='Sports & Fitness';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Shuttlecock' FROM product_types WHERE name='Sports & Fitness';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Tennis Racket' FROM product_types WHERE name='Sports & Fitness';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Gym Dumbbell' FROM product_types WHERE name='Sports & Fitness';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Yoga Mat' FROM product_types WHERE name='Sports & Fitness';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Skipping Rope' FROM product_types WHERE name='Sports & Fitness';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Gym Bag' FROM product_types WHERE name='Sports & Fitness';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Water Bottle (Sports)' FROM product_types WHERE name='Sports & Fitness';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Cycling Helmet' FROM product_types WHERE name='Sports & Fitness';

-- Beauty & Health
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Face Cream' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Body Lotion' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Shampoo' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Conditioner' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Hair Oil' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Face Wash' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Soap' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Toothpaste' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Toothbrush' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Perfume' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Deodorant' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Sunscreen' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Lipstick' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Nail Polish' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Sanitizer' FROM product_types WHERE name='Beauty & Health';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Face Mask' FROM product_types WHERE name='Beauty & Health';

-- Books & Media
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Fiction Book' FROM product_types WHERE name='Books & Media';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Non-Fiction Book' FROM product_types WHERE name='Books & Media';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Educational Book' FROM product_types WHERE name='Books & Media';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Magazine' FROM product_types WHERE name='Books & Media';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Newspaper' FROM product_types WHERE name='Books & Media';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Comic Book' FROM product_types WHERE name='Books & Media';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Music CD' FROM product_types WHERE name='Books & Media';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Movie DVD' FROM product_types WHERE name='Books & Media';

-- Toys & Games
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Action Figure' FROM product_types WHERE name='Toys & Games';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Doll' FROM product_types WHERE name='Toys & Games';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Board Game' FROM product_types WHERE name='Toys & Games';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Puzzle' FROM product_types WHERE name='Toys & Games';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Remote Control Car' FROM product_types WHERE name='Toys & Games';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Soft Toy' FROM product_types WHERE name='Toys & Games';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Building Blocks' FROM product_types WHERE name='Toys & Games';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Video Game' FROM product_types WHERE name='Toys & Games';

-- Furniture
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Sofa' FROM product_types WHERE name='Furniture';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Bed' FROM product_types WHERE name='Furniture';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Mattress' FROM product_types WHERE name='Furniture';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Dining Table' FROM product_types WHERE name='Furniture';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Chair' FROM product_types WHERE name='Furniture';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Office Chair' FROM product_types WHERE name='Furniture';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Study Table' FROM product_types WHERE name='Furniture';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Wardrobe' FROM product_types WHERE name='Furniture';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Bookshelf' FROM product_types WHERE name='Furniture';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'TV Unit' FROM product_types WHERE name='Furniture';

-- Appliances
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Ceiling Fan' FROM product_types WHERE name='Appliances';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Table Fan' FROM product_types WHERE name='Appliances';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Air Cooler' FROM product_types WHERE name='Appliances';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Geyser' FROM product_types WHERE name='Appliances';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Room Heater' FROM product_types WHERE name='Appliances';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Electric Kettle' FROM product_types WHERE name='Appliances';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Toaster' FROM product_types WHERE name='Appliances';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Juicer' FROM product_types WHERE name='Appliances';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Rice Cooker' FROM product_types WHERE name='Appliances';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Air Fryer' FROM product_types WHERE name='Appliances';

-- Seed: Companies / Brands
INSERT INTO `companies` (`name`) VALUES
-- Electronics & Appliances
('Samsung'),('LG'),('Sony'),('Apple'),('Xiaomi'),('OnePlus'),('Realme'),('Vivo'),('Oppo'),('Motorola'),
('Nokia'),('Dell'),('HP'),('Lenovo'),('Asus'),('Acer'),('MSI'),('Panasonic'),('Philips'),('Bose'),
('JBL'),('Boat'),('Whirlpool'),('Bosch'),('IFB'),('Godrej'),('Voltas'),('Blue Star'),('Carrier'),('Daikin'),
-- Automotive
('Toyota'),('Honda'),('Hyundai'),('Maruti Suzuki'),('Tata Motors'),('Mahindra'),('Ford'),('BMW'),('Mercedes-Benz'),('Audi'),
('Hero'),('Bajaj'),('TVS'),('Royal Enfield'),('KTM'),('Yamaha'),('Suzuki'),('Kawasaki'),
('MRF'),('CEAT'),('Apollo'),('JK Tyre'),('Michelin'),('Exide'),('Amaron'),('Castrol'),('Mobil'),('Shell'),
-- Grocery & FMCG
('Amul'),('Nestle'),('Britannia'),('Parle'),('ITC'),('Haldiram'),('Bikaji'),('Coca-Cola'),('Pepsi'),('Bisleri'),
('Tata Salt'),('Tata Tea'),('Brooke Bond'),('Nescafe'),('Bru'),('Fortune'),('Aashirvaad'),('Pillsbury'),('India Gate'),('Daawat'),
('Maggi'),('Yippee'),('Top Ramen'),('Kissan'),('Heinz'),('MDH'),('Everest'),('Catch'),('Patanjali'),('Dabur'),
-- Fashion & Footwear
('Nike'),('Adidas'),('Puma'),('Reebok'),('Bata'),('Woodland'),('Red Tape'),('Liberty'),('Relaxo'),('Paragon'),
('Levi\'s'),('Wrangler'),('Lee'),('Peter England'),('Van Heusen'),('Allen Solly'),('Louis Philippe'),('Raymond'),
('Titan'),('Fastrack'),('Casio'),('Fossil'),('Timex'),('Sonata'),('Ray-Ban'),('Oakley'),
-- Home & Kitchen
('Prestige'),('Hawkins'),('Pigeon'),('Butterfly'),('Milton'),('Cello'),('Tupperware'),('Borosil'),('Corelle'),
('Havells'),('Crompton'),('Orient'),('Usha'),('Bajaj Electricals'),('Philips Lighting'),('Syska'),
-- Stationery
('Reynolds'),('Parker'),('Cello Pens'),('Classmate'),('Navneet'),('Camlin'),('Faber-Castell'),('Staedtler'),('Apsara'),('Nataraj'),
-- Beauty & Health
('Lakme'),('Maybelline'),('L\'Oreal'),('Garnier'),('Nivea'),('Ponds'),('Himalaya'),('Biotique'),('Mamaearth'),
('Dove'),('Lux'),('Lifebuoy'),('Dettol'),('Savlon'),('Colgate'),('Pepsodent'),('Sensodyne'),('Close-Up'),
('Head & Shoulders'),('Pantene'),('Sunsilk'),('Clinic Plus'),('Parachute'),('Bajaj Almond Drops'),('Emami'),
-- Sports & Fitness
('Nivia'),('Cosco'),('Yonex'),('Li-Ning'),('SG'),('SS'),('MRF Genius'),('Kookaburra'),('Decathlon'),
-- Furniture
('Godrej Interio'),('Nilkamal'),('Durian'),('Urban Ladder'),('Pepperfry'),('IKEA'),('Sleepwell'),('Kurlon'),('Kurl-On'),
-- Books & Media
('Penguin'),('HarperCollins'),('Scholastic'),('Oxford'),('Cambridge'),('McGraw Hill'),('Pearson'),
-- Toys
('Funskool'),('Mattel'),('Hasbro'),('LEGO'),('Hot Wheels'),('Barbie'),('Fisher-Price');

-- Seed: company_item_map for Automotive & Electronics and common items
INSERT IGNORE INTO company_item_map (product_item_id, company_id)
SELECT pi.id, c.id FROM product_items pi, companies c
WHERE pi.name='Car' AND c.name IN ('Toyota','Honda','Hyundai','Maruti Suzuki','Tata Motors','Mahindra','Ford','BMW','Mercedes-Benz','Audi');

INSERT IGNORE INTO company_item_map (product_item_id, company_id)
SELECT pi.id, c.id FROM product_items pi, companies c
WHERE pi.name='Motorcycle' AND c.name IN ('Honda','BMW');

INSERT IGNORE INTO company_item_map (product_item_id, company_id)
SELECT pi.id, c.id FROM product_items pi, companies c
WHERE pi.product_type_id = (SELECT id FROM product_types WHERE name='Electronics' LIMIT 1)
  AND pi.name IN ('Television','Mobile Phone','Laptop','Refrigerator','Washing Machine','Air Conditioner','Headphones','Speaker','Tablet','Camera')
  AND c.name IN ('Samsung','LG','Sony','Apple','Xiaomi','OnePlus','Dell','HP','Lenovo','Asus','Panasonic','Philips','Bose');

-- Optional: Basic categories/subcategories for legacy filters
INSERT INTO `categories` (`name`) VALUES
('Electronics'),('Mobile & Accessories'),('Computers & Laptops'),('Appliances'),
('Fashion'),('Footwear'),('Watches & Accessories'),
('Home & Kitchen'),('Furniture'),('Home Decor'),
('Sports & Fitness'),('Outdoor & Adventure'),
('Books & Media'),('Toys & Games'),
('Automotive'),('Bike & Car Accessories'),
('Grocery & Gourmet'),('Health & Personal Care'),('Beauty & Cosmetics'),
('Stationery & Office Supplies'),('Baby Products'),('Pet Supplies');

-- Electronics Subcategories
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Televisions' FROM categories c WHERE c.name='Electronics';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Home Audio' FROM categories c WHERE c.name='Electronics';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Cameras' FROM categories c WHERE c.name='Electronics';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Gaming Consoles' FROM categories c WHERE c.name='Electronics';

-- Mobile & Accessories Subcategories
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Smartphones' FROM categories c WHERE c.name='Mobile & Accessories';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Feature Phones' FROM categories c WHERE c.name='Mobile & Accessories';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Mobile Covers & Cases' FROM categories c WHERE c.name='Mobile & Accessories';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Power Banks' FROM categories c WHERE c.name='Mobile & Accessories';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Headphones & Earphones' FROM categories c WHERE c.name='Mobile & Accessories';

-- Computers & Laptops Subcategories
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Laptops' FROM categories c WHERE c.name='Computers & Laptops';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Desktops' FROM categories c WHERE c.name='Computers & Laptops';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Tablets' FROM categories c WHERE c.name='Computers & Laptops';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Printers & Scanners' FROM categories c WHERE c.name='Computers & Laptops';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Computer Accessories' FROM categories c WHERE c.name='Computers & Laptops';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Storage Devices' FROM categories c WHERE c.name='Computers & Laptops';

-- Appliances Subcategories
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Refrigerators' FROM categories c WHERE c.name='Appliances';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Washing Machines' FROM categories c WHERE c.name='Appliances';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Air Conditioners' FROM categories c WHERE c.name='Appliances';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Kitchen Appliances' FROM categories c WHERE c.name='Appliances';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Fans & Coolers' FROM categories c WHERE c.name='Appliances';

-- Fashion Subcategories
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Men\'s Clothing' FROM categories c WHERE c.name='Fashion';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Women\'s Clothing' FROM categories c WHERE c.name='Fashion';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Kids Clothing' FROM categories c WHERE c.name='Fashion';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Ethnic Wear' FROM categories c WHERE c.name='Fashion';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Western Wear' FROM categories c WHERE c.name='Fashion';

-- Footwear Subcategories
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Men\'s Footwear' FROM categories c WHERE c.name='Footwear';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Women\'s Footwear' FROM categories c WHERE c.name='Footwear';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Kids Footwear' FROM categories c WHERE c.name='Footwear';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Sports Shoes' FROM categories c WHERE c.name='Footwear';

-- Home & Kitchen Subcategories
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Cookware' FROM categories c WHERE c.name='Home & Kitchen';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Kitchen Tools' FROM categories c WHERE c.name='Home & Kitchen';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Dinnerware' FROM categories c WHERE c.name='Home & Kitchen';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Kitchen Storage' FROM categories c WHERE c.name='Home & Kitchen';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Bedding & Linen' FROM categories c WHERE c.name='Home & Kitchen';

-- Grocery Subcategories
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Staples' FROM categories c WHERE c.name='Grocery & Gourmet';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Snacks & Beverages' FROM categories c WHERE c.name='Grocery & Gourmet';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Packaged Food' FROM categories c WHERE c.name='Grocery & Gourmet';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Dairy Products' FROM categories c WHERE c.name='Grocery & Gourmet';

-- Sports & Fitness Subcategories
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Cricket Equipment' FROM categories c WHERE c.name='Sports & Fitness';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Badminton Equipment' FROM categories c WHERE c.name='Sports & Fitness';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Football Equipment' FROM categories c WHERE c.name='Sports & Fitness';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Fitness Equipment' FROM categories c WHERE c.name='Sports & Fitness';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Yoga & Pilates' FROM categories c WHERE c.name='Sports & Fitness';

-- Beauty & Health Subcategories
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Skin Care' FROM categories c WHERE c.name='Beauty & Cosmetics';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Hair Care' FROM categories c WHERE c.name='Beauty & Cosmetics';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Makeup' FROM categories c WHERE c.name='Beauty & Cosmetics';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Fragrances' FROM categories c WHERE c.name='Beauty & Cosmetics';

-- Automotive Subcategories
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Car Care' FROM categories c WHERE c.name='Automotive';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Bike Care' FROM categories c WHERE c.name='Automotive';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Car Accessories' FROM categories c WHERE c.name='Automotive';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Bike Accessories' FROM categories c WHERE c.name='Automotive';

-- Sample products
-- Electronics
INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Samsung 55-inch 4K Smart TV', 10, 55000.00, 42000.00, pt.id, pi.id, co.id, 'SAM-TV-55-4K', '8801643740894', 'Electro Supplier', 2, '55-inch 4K UHD Smart LED TV with HDR support'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Electronics' AND pi.name='Smart TV' AND pi.product_type_id=pt.id AND co.name='Samsung' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'LG 260L Frost Free Refrigerator', 8, 28500.00, 22000.00, pt.id, pi.id, co.id, 'LG-REF-260', '8806098149537', 'Appliance Hub', 3, 'Double door frost-free refrigerator with smart inverter compressor'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Electronics' AND pi.name='Refrigerator' AND pi.product_type_id=pt.id AND co.name='LG' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Whirlpool 7kg Washing Machine', 12, 18999.00, 15000.00, pt.id, pi.id, co.id, 'WP-WM-7KG', '8901268001234', 'Home Appliances Co', 3, 'Fully automatic top load washing machine'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Electronics' AND pi.name='Washing Machine' AND pi.product_type_id=pt.id AND co.name='Whirlpool' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Voltas 1.5 Ton Split AC', 6, 32000.00, 26000.00, pt.id, pi.id, co.id, 'VOL-AC-1.5T', '8901268002345', 'Cool Tech', 2, '1.5 Ton 3 Star Split AC with copper condenser'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Electronics' AND pi.name='Air Conditioner' AND pi.product_type_id=pt.id AND co.name='Voltas' LIMIT 1;

-- Mobile & Accessories
INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Apple iPhone 14 128GB', 25, 79999.00, 65000.00, pt.id, pi.id, co.id, 'IPH14-128', '0194253394563', 'Mobile World', 5, 'iPhone 14 with A15 Bionic chip, 6.1-inch display'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Mobile & Accessories' AND pi.name='Smartphone' AND pi.product_type_id=pt.id AND co.name='Apple' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Samsung Galaxy S23 256GB', 20, 74999.00, 60000.00, pt.id, pi.id, co.id, 'SAM-S23-256', '8806094567890', 'Mobile World', 5, 'Galaxy S23 with Snapdragon 8 Gen 2, 8GB RAM'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Mobile & Accessories' AND pi.name='Smartphone' AND pi.product_type_id=pt.id AND co.name='Samsung' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'OnePlus Nord CE 3 5G', 30, 26999.00, 21000.00, pt.id, pi.id, co.id, 'OP-NORD-CE3', '6921815620123', 'Mobile World', 8, '8GB RAM, 128GB Storage, 5G enabled'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Mobile & Accessories' AND pi.name='Smartphone' AND pi.product_type_id=pt.id AND co.name='OnePlus' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Boat Airdopes 141 TWS', 50, 1299.00, 800.00, pt.id, pi.id, co.id, 'BOAT-AD141', '8904130891234', 'Audio Hub', 15, 'True wireless earbuds with 42H playback'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Mobile & Accessories' AND pi.name='Wireless Earbuds' AND pi.product_type_id=pt.id AND co.name='Boat' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Mi 20000mAh Power Bank', 40, 1799.00, 1200.00, pt.id, pi.id, co.id, 'MI-PB-20K', '6934177712345', 'Mobile Accessories', 12, '20000mAh with 18W fast charging'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Mobile & Accessories' AND pi.name='Power Bank' AND pi.product_type_id=pt.id AND co.name='Xiaomi' LIMIT 1;

-- Computers & Laptops
INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Dell Inspiron 15 Laptop', 15, 62000.00, 48000.00, pt.id, pi.id, co.id, 'DEL-INS15', '5397184512345', 'Tech Supplier', 3, 'Intel i5 12th Gen, 8GB RAM, 512GB SSD'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Computers & Laptops' AND pi.name='Laptop' AND pi.product_type_id=pt.id AND co.name='Dell' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'HP Pavilion Gaming Laptop', 10, 75000.00, 60000.00, pt.id, pi.id, co.id, 'HP-PAV-GAME', '0195161234567', 'Tech Supplier', 2, 'AMD Ryzen 5, GTX 1650, 16GB RAM'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Computers & Laptops' AND pi.name='Gaming Laptop' AND pi.product_type_id=pt.id AND co.name='HP' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Lenovo IdeaPad Slim 3', 18, 45000.00, 36000.00, pt.id, pi.id, co.id, 'LEN-SLIM3', '0195891234567', 'Tech Supplier', 4, 'Intel i5 11th Gen, 8GB RAM, 512GB SSD'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Computers & Laptops' AND pi.name='Laptop' AND pi.product_type_id=pt.id AND co.name='Lenovo' LIMIT 1;

-- Grocery Items
INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'India Gate Basmati Rice 5kg', 100, 550.00, 420.00, pt.id, pi.id, co.id, 'IG-RICE-5KG', '8901491101234', 'Grocery Mart', 20, 'Premium basmati rice, aged for aroma'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Grocery' AND pi.name='Basmati Rice' AND pi.product_type_id=pt.id AND co.name='India Gate' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Aashirvaad Atta 10kg', 80, 420.00, 340.00, pt.id, pi.id, co.id, 'ASH-ATTA-10', '8901491101345', 'Grocery Mart', 15, 'Whole wheat flour, 100% MP Sharbati wheat'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Grocery' AND pi.name='Wheat Flour (Atta)' AND pi.product_type_id=pt.id AND co.name='Aashirvaad' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Fortune Sunflower Oil 5L', 60, 650.00, 520.00, pt.id, pi.id, co.id, 'FOR-OIL-5L', '8901491101456', 'Grocery Mart', 12, 'Refined sunflower oil, rich in vitamins'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Grocery' AND pi.name='Cooking Oil' AND pi.product_type_id=pt.id AND co.name='Fortune' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Tata Tea Premium 1kg', 70, 480.00, 380.00, pt.id, pi.id, co.id, 'TATA-TEA-1KG', '8901491101567', 'Grocery Mart', 15, 'Premium blend tea leaves'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Grocery' AND pi.name='Tea' AND pi.product_type_id=pt.id AND co.name='Tata Tea' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Britannia Good Day Cookies 600g', 120, 80.00, 60.00, pt.id, pi.id, co.id, 'BRI-GD-600', '8901491101678', 'Grocery Mart', 25, 'Butter cookies with cashew'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Grocery' AND pi.name='Biscuits' AND pi.product_type_id=pt.id AND co.name='Britannia' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Maggi 2-Minute Noodles 12 Pack', 150, 144.00, 110.00, pt.id, pi.id, co.id, 'MAG-NOOD-12', '8901491101789', 'Grocery Mart', 30, 'Masala instant noodles pack of 12'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Grocery' AND pi.name='Noodles' AND pi.product_type_id=pt.id AND co.name='Maggi' LIMIT 1;

-- Fashion & Footwear
INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Nike Air Max Running Shoes', 25, 7995.00, 6000.00, pt.id, pi.id, co.id, 'NIKE-AM-RUN', '0193655123456', 'Sports Store', 8, 'Men\'s running shoes with air cushioning'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Footwear' AND pi.name='Sports Shoes' AND pi.product_type_id=pt.id AND co.name='Nike' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Adidas Ultraboost 22', 20, 16999.00, 13000.00, pt.id, pi.id, co.id, 'ADI-UB22', '4064036123456', 'Sports Store', 6, 'Premium running shoes with boost technology'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Footwear' AND pi.name='Sports Shoes' AND pi.product_type_id=pt.id AND co.name='Adidas' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Levi\'s 511 Slim Fit Jeans', 40, 2999.00, 2200.00, pt.id, pi.id, co.id, 'LEV-511-SLIM', '5400537123456', 'Fashion Hub', 10, 'Men\'s slim fit denim jeans'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Fashion' AND pi.name='Jeans' AND pi.product_type_id=pt.id AND co.name='Levi\'s' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Peter England Formal Shirt', 50, 1299.00, 900.00, pt.id, pi.id, co.id, 'PE-SHIRT-F', '8904245123456', 'Fashion Hub', 12, 'Men\'s formal cotton shirt'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Fashion' AND pi.name='Shirt' AND pi.product_type_id=pt.id AND co.name='Peter England' LIMIT 1;

-- Home & Kitchen
INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Prestige Deluxe Plus 5L Cooker', 35, 2499.00, 1900.00, pt.id, pi.id, co.id, 'PRE-COOK-5L', '8901365123456', 'Kitchen Store', 8, 'Aluminium pressure cooker with induction base'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Home & Kitchen' AND pi.name='Pressure Cooker' AND pi.product_type_id=pt.id AND co.name='Prestige' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Philips HL7756 Mixer Grinder', 20, 5999.00, 4500.00, pt.id, pi.id, co.id, 'PHI-MG-7756', '8710103123456', 'Kitchen Store', 5, '750W mixer grinder with 3 jars'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Home & Kitchen' AND pi.name='Mixer Grinder' AND pi.product_type_id=pt.id AND co.name='Philips' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Milton Thermosteel 1L Bottle', 60, 699.00, 500.00, pt.id, pi.id, co.id, 'MIL-THERM-1L', '8904196123456', 'Kitchen Store', 15, 'Insulated water bottle, keeps hot/cold 24hrs'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Home & Kitchen' AND pi.name='Water Bottle' AND pi.product_type_id=pt.id AND co.name='Milton' LIMIT 1;

-- Sports & Fitness
INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'SG Kashmir Willow Cricket Bat', 30, 1899.00, 1400.00, pt.id, pi.id, co.id, 'SG-BAT-KW', '8906010123456', 'Sports World', 8, 'Kashmir willow cricket bat for leather ball'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Sports & Fitness' AND pi.name='Cricket Bat' AND pi.product_type_id=pt.id AND co.name='SG' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Yonex Mavis 350 Shuttlecock', 80, 1299.00, 950.00, pt.id, pi.id, co.id, 'YON-SHUT-M350', '4550086123456', 'Sports World', 20, 'Nylon shuttlecock, pack of 6'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Sports & Fitness' AND pi.name='Shuttlecock' AND pi.product_type_id=pt.id AND co.name='Yonex' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Nivia Storm Football Size 5', 45, 599.00, 420.00, pt.id, pi.id, co.id, 'NIV-FB-STORM', '8906010234567', 'Sports World', 12, 'Synthetic leather football, size 5'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Sports & Fitness' AND pi.name='Football' AND pi.product_type_id=pt.id AND co.name='Nivia' LIMIT 1;

-- Beauty & Health
INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Lakme Absolute Lipstick', 70, 599.00, 420.00, pt.id, pi.id, co.id, 'LAK-LIP-ABS', '8901030123456', 'Beauty Store', 18, 'Matte finish lipstick, long lasting'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Beauty & Health' AND pi.name='Lipstick' AND pi.product_type_id=pt.id AND co.name='Lakme' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Dove Intense Repair Shampoo 650ml', 90, 399.00, 300.00, pt.id, pi.id, co.id, 'DOV-SHAM-650', '8901030234567', 'Beauty Store', 22, 'Nourishing shampoo for damaged hair'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Beauty & Health' AND pi.name='Shampoo' AND pi.product_type_id=pt.id AND co.name='Dove' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Colgate Total Toothpaste 200g', 150, 180.00, 130.00, pt.id, pi.id, co.id, 'COL-TP-200', '8901030345678', 'Beauty Store', 35, 'Advanced toothpaste with germ protection'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Beauty & Health' AND pi.name='Toothpaste' AND pi.product_type_id=pt.id AND co.name='Colgate' LIMIT 1;

-- Stationery
INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Classmate Notebook 172 Pages', 200, 45.00, 32.00, pt.id, pi.id, co.id, 'CM-NB-172', '8901032123456', 'Stationery Hub', 40, 'Single line notebook, 172 pages'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Stationery' AND pi.name='Notebook' AND pi.product_type_id=pt.id AND co.name='Classmate' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Reynolds Trimax Pen Blue', 300, 10.00, 7.00, pt.id, pi.id, co.id, 'REY-PEN-BLUE', '8901032234567', 'Stationery Hub', 60, 'Ball pen with smooth writing'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Stationery' AND pi.name='Ball Pen' AND pi.product_type_id=pt.id AND co.name='Reynolds' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`barcode`,`supplier`,`reorder_level`,`description`)
SELECT 'Apsara Platinum Pencil Pack of 10', 180, 50.00, 35.00, pt.id, pi.id, co.id, 'APS-PENCIL-10', '8901032345678', 'Stationery Hub', 35, 'Extra dark pencils, pack of 10'
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Stationery' AND pi.name='Pencil' AND pi.product_type_id=pt.id AND co.name='Apsara' LIMIT 1;
