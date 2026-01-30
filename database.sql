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
('Beauty & Health','fas fa-heart');

-- Seed: Items
-- Electronics
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Television' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Mobile Phone' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Laptop' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Refrigerator' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Washing Machine' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Air Conditioner' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Headphones' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Speaker' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Tablet' FROM product_types WHERE name='Electronics';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Camera' FROM product_types WHERE name='Electronics';

-- Automotive
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Car' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Motorcycle' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Truck' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Engine Parts' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Tires' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Battery' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Brake Pads' FROM product_types WHERE name='Automotive';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Car Accessories' FROM product_types WHERE name='Automotive';

-- Grocery
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Rice' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Wheat Flour' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Pulses' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Sugar' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Salt' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Cooking Oil' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Tea' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Coffee' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Spices' FROM product_types WHERE name='Grocery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Snacks' FROM product_types WHERE name='Grocery';

-- Stationery
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Pen' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Pencil' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Notebook' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'A4 Paper' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'File Folder' FROM product_types WHERE name='Stationery';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Marker' FROM product_types WHERE name='Stationery';

-- Home & Kitchen
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Microwave' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Mixer Grinder' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Cookware' FROM product_types WHERE name='Home & Kitchen';
INSERT INTO `product_items` (`product_type_id`,`name`) SELECT id,'Lighting' FROM product_types WHERE name='Home & Kitchen';

-- Seed: Companies / Brands
INSERT INTO `companies` (`name`) VALUES
('Samsung'),('LG'),('Sony'),('Apple'),('Xiaomi'),('OnePlus'),('Dell'),('HP'),('Lenovo'),('Asus'),('Panasonic'),('Philips'),('Bose'),
('Toyota'),('Honda'),('Hyundai'),('Maruti Suzuki'),('Tata Motors'),('Mahindra'),('Ford'),('BMW'),('Mercedes-Benz'),('Audi'),
('Amul'),('Nestle'),('Britannia'),('Parle'),('Haldiram'),('Coca-Cola'),('Pepsi'),('Tata Salt'),('Fortune'),('Aashirvaad'),
('Cello'),('Reynolds'),('Parker'),('Classmate'),('Navneet'),('Camlin'),('Faber-Castell'),('Staedtler'),
('Whirlpool'),('Bosch'),('IFB'),('Prestige'),('Bajaj'),('Havells'),('Crompton'),('Orient');

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
('Electronics'),('Sports'),('Clothing'),('Books'),('Home & Garden'),('Automotive'),('Health & Beauty'),('Toys & Games');

INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Laptops' FROM categories c WHERE c.name='Electronics';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Smartphones' FROM categories c WHERE c.name='Electronics';
INSERT INTO `subcategories` (`category_id`,`name`)
SELECT c.id,'Accessories' FROM categories c WHERE c.name='Electronics';

-- Sample products
INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`supplier`,`reorder_level`)
SELECT 'Samsung 55-inch 4K TV', 10, 55000.00, 42000.00, pt.id, pi.id, co.id, 'TV-4K-55-SAM', 'Electro Supplier', 2
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Electronics' AND pi.name='Television' AND pi.product_type_id=pt.id AND co.name='Samsung' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`supplier`,`reorder_level`)
SELECT 'Apple iPhone 14', 25, 79999.00, 65000.00, pt.id, pi.id, co.id, 'IPH14-128', 'Mobile Supplier', 5
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Electronics' AND pi.name='Mobile Phone' AND pi.product_type_id=pt.id AND co.name='Apple' LIMIT 1;

INSERT INTO `products` (`name`,`quantity`,`price`,`cost_price`,`product_type_id`,`product_item_id`,`company_id`,`sku`,`supplier`,`reorder_level`)
SELECT 'Dell Inspiron 15', 15, 62000.00, 48000.00, pt.id, pi.id, co.id, 'DEL-INS15', 'Tech Supplier', 3
FROM product_types pt, product_items pi, companies co
WHERE pt.name='Electronics' AND pi.name='Laptop' AND pi.product_type_id=pt.id AND co.name='Dell' LIMIT 1;
