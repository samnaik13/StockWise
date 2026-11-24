-- ==============================================================================
-- STOCKWISE: Multi-Tenant Authentication Migration
-- PURPOSE: Add users table, add user_id foreign keys to all data tables,
--          assign existing data to primary account (Samarth Naik, user_id=1)
-- ==============================================================================

USE `grocery_inventory`;

-- 1. Create Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT AUTO_INCREMENT,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `mobile` VARCHAR(15) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `is_setup_complete` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Insert primary account placeholder (setup NOT complete - user must provide email/mobile/password)
INSERT IGNORE INTO `users` (`user_id`, `full_name`, `email`, `mobile`, `password_hash`, `is_primary`, `is_setup_complete`)
VALUES (1, 'Samarth Naik', 'pending_setup@stockwise.local', '', '', 1, 0);

-- 3. Add user_id column to all data tables (default 1 = Samarth Naik for existing data)
ALTER TABLE `Category`      ADD COLUMN `user_id` INT NOT NULL DEFAULT 1 AFTER `description`;
ALTER TABLE `Supplier`       ADD COLUMN `user_id` INT NOT NULL DEFAULT 1 AFTER `address`;
ALTER TABLE `Product`        ADD COLUMN `user_id` INT NOT NULL DEFAULT 1 AFTER `created_at`;
ALTER TABLE `Purchase`       ADD COLUMN `user_id` INT NOT NULL DEFAULT 1 AFTER `invoice_number`;
ALTER TABLE `Sales`          ADD COLUMN `user_id` INT NOT NULL DEFAULT 1 AFTER `bill_number`;
ALTER TABLE `Inventory`      ADD COLUMN `user_id` INT NOT NULL DEFAULT 1 AFTER `last_updated`;
ALTER TABLE `Stock_Movement` ADD COLUMN `user_id` INT NOT NULL DEFAULT 1 AFTER `remarks`;

-- 4. Add indexes on user_id for query performance
ALTER TABLE `Category`      ADD INDEX `idx_category_user` (`user_id`);
ALTER TABLE `Supplier`       ADD INDEX `idx_supplier_user` (`user_id`);
ALTER TABLE `Product`        ADD INDEX `idx_product_user` (`user_id`);
ALTER TABLE `Purchase`       ADD INDEX `idx_purchase_user` (`user_id`);
ALTER TABLE `Sales`          ADD INDEX `idx_sales_user` (`user_id`);
ALTER TABLE `Inventory`      ADD INDEX `idx_inventory_user` (`user_id`);
ALTER TABLE `Stock_Movement` ADD INDEX `idx_stock_movement_user` (`user_id`);

-- 5. Add foreign key constraints
ALTER TABLE `Category`      ADD CONSTRAINT `fk_category_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `Supplier`       ADD CONSTRAINT `fk_supplier_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `Product`        ADD CONSTRAINT `fk_product_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `Purchase`       ADD CONSTRAINT `fk_purchase_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `Sales`          ADD CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `Inventory`      ADD CONSTRAINT `fk_inventory_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `Stock_Movement` ADD CONSTRAINT `fk_stock_movement_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE RESTRICT;

-- 6. Update existing database views to include user_id awareness
-- (Views will continue to work but we filter at query level in PHP)
