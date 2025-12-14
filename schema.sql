-- Database Schema for Cango Woodfired Pizza (Variants Support)

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `size_definitions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `measurement` VARCHAR(50) NOT NULL,
  UNIQUE KEY `name` (`name`)
);

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
);

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('menu','special') NOT NULL DEFAULT 'menu',
  `display_order` INT DEFAULT 0,
  `active_days` VARCHAR(255) DEFAULT NULL,
  `allowed_variants` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `show_measurements` BOOLEAN DEFAULT 1
);

CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `is_special` BOOLEAN DEFAULT 0,
  `day_of_week` VARCHAR(20) DEFAULT NULL,
  `is_active` BOOLEAN DEFAULT 1,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `item_variants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `menu_item_id` INT NOT NULL,
  `size_id` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`size_id`) REFERENCES `size_definitions`(`id`) ON DELETE CASCADE
);

INSERT IGNORE INTO `size_definitions` (`id`, `name`, `measurement`) VALUES
  (1, 'Small', '19cm'),
  (2, 'Medium', '23cm'),
  (3, 'Large', '30cm'),
  (4, 'Standard', '');

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('pdf_page_width_mm', '148'),
  ('pdf_page_height_mm', '230');

-- Admin User (Password: password)
INSERT IGNORE INTO `admin_users` (`username`, `password_hash`) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
