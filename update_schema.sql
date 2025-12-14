-- Update Categories Table
ALTER TABLE `categories` 
ADD COLUMN `type` ENUM('menu', 'special') DEFAULT 'menu' AFTER `display_order`,
ADD COLUMN `active_days` TEXT DEFAULT NULL AFTER `type`,
ADD COLUMN `allowed_variants` TEXT DEFAULT NULL AFTER `active_days`,
ADD COLUMN `show_measurements` BOOLEAN DEFAULT 1 AFTER `allowed_variants`;

-- Drop old specials table
DROP TABLE IF EXISTS `specials`;
