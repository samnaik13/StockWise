-- ==============================================================================
-- GROCERY STORE INVENTORY MANAGEMENT SYSTEM
-- SCRIPT: create_tables.sql
-- PURPOSE: DDL Script to create database and all 9 normalized relational tables
-- DBMS: MariaDB / MySQL (XAMPP Compatible, phpMyAdmin Importable)
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `grocery_inventory` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `grocery_inventory`;

-- Disable foreign key checks during table drop/creation
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `Stock_Movement`;
DROP TABLE IF EXISTS `Inventory`;
DROP TABLE IF EXISTS `Sale_Item`;
DROP TABLE IF EXISTS `Sales`;
DROP TABLE IF EXISTS `Purchase_Item`;
DROP TABLE IF EXISTS `Purchase`;
DROP TABLE IF EXISTS `Supplier`;
DROP TABLE IF EXISTS `Product`;
DROP TABLE IF EXISTS `Category`;

-- Also drop any legacy plural table names if present
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `purchases`;
DROP TABLE IF EXISTS `purchase_items`;
DROP TABLE IF EXISTS `sale_items`;
DROP TABLE IF EXISTS `suppliers`;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- TABLE 1: Category
-- PURPOSE: Store grocery categories (Grains, Pulses, Dairy, Beverages, etc.)
-- ==============================================================================
CREATE TABLE `Category` (
    `category_id` INT AUTO_INCREMENT,
    `category_name` VARCHAR(60) NOT NULL,
    `description` VARCHAR(150) DEFAULT NULL,
    PRIMARY KEY (`category_id`),
    UNIQUE KEY `uk_category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- TABLE 2: Supplier
-- PURPOSE: Store wholesale supplier records and contact information
-- ==============================================================================
CREATE TABLE `Supplier` (
    `supplier_id` INT AUTO_INCREMENT,
    `supplier_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(15) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `address` VARCHAR(200) DEFAULT NULL,
    PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- TABLE 3: Product
-- PURPOSE: Store grocery product catalog, unit of measurement, selling price, vendor
-- ==============================================================================
CREATE TABLE `Product` (
    `product_id` INT AUTO_INCREMENT,
    `product_name` VARCHAR(100) NOT NULL,
    `category_id` INT NOT NULL,
    `supplier_id` INT DEFAULT NULL,
    `selling_price` DECIMAL(10,2) NOT NULL,
    `reorder_level` DECIMAL(10,3) NOT NULL,
    `unit` VARCHAR(20) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`product_id`),
    KEY `idx_product_category` (`category_id`),
    KEY `idx_product_supplier` (`supplier_id`),
    CONSTRAINT `fk_product_category` 
        FOREIGN KEY (`category_id`) REFERENCES `Category` (`category_id`) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_product_supplier` 
        FOREIGN KEY (`supplier_id`) REFERENCES `Supplier` (`supplier_id`) 
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `chk_product_selling_price` CHECK (`selling_price` >= 0),
    CONSTRAINT `chk_product_reorder_level` CHECK (`reorder_level` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- TABLE 4: Purchase
-- PURPOSE: Master header for stock-in purchase transactions from suppliers
-- ==============================================================================
CREATE TABLE `Purchase` (
    `purchase_id` INT AUTO_INCREMENT,
    `supplier_id` INT NOT NULL,
    `purchase_date` DATE NOT NULL,
    `invoice_number` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`purchase_id`),
    UNIQUE KEY `uk_purchase_invoice` (`invoice_number`),
    KEY `idx_purchase_supplier` (`supplier_id`),
    KEY `idx_purchase_date` (`purchase_date`),
    CONSTRAINT `fk_purchase_supplier` 
        FOREIGN KEY (`supplier_id`) REFERENCES `Supplier` (`supplier_id`) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- TABLE 5: Purchase_Item
-- PURPOSE: Detail line items for products received in each purchase invoice
-- ==============================================================================
CREATE TABLE `Purchase_Item` (
    `purchase_item_id` INT AUTO_INCREMENT,
    `purchase_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` DECIMAL(10,3) NOT NULL,
    `unit_cost` DECIMAL(10,2) NOT NULL,
    `expiry_date` DATE DEFAULT NULL,
    PRIMARY KEY (`purchase_item_id`),
    KEY `idx_purchase_item_purchase` (`purchase_id`),
    KEY `idx_purchase_item_product` (`product_id`),
    CONSTRAINT `fk_purchase_item_purchase` 
        FOREIGN KEY (`purchase_id`) REFERENCES `Purchase` (`purchase_id`) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_purchase_item_product` 
        FOREIGN KEY (`product_id`) REFERENCES `Product` (`product_id`) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_purchase_item_qty` CHECK (`quantity` > 0),
    CONSTRAINT `chk_purchase_item_cost` CHECK (`unit_cost` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- TABLE 6: Sales
-- PURPOSE: Master header for counter sales billing transactions
-- ==============================================================================
CREATE TABLE `Sales` (
    `sale_id` INT AUTO_INCREMENT,
    `sale_date` DATE NOT NULL,
    `bill_number` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`sale_id`),
    UNIQUE KEY `uk_sales_bill` (`bill_number`),
    KEY `idx_sales_date` (`sale_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- TABLE 7: Sale_Item
-- PURPOSE: Detail line items for products sold, capturing historical price and cost
-- ==============================================================================
CREATE TABLE `Sale_Item` (
    `sale_item_id` INT AUTO_INCREMENT,
    `sale_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` DECIMAL(10,3) NOT NULL,
    `unit_selling_price` DECIMAL(10,2) NOT NULL,
    `unit_cost_at_sale` DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (`sale_item_id`),
    KEY `idx_sale_item_sale` (`sale_id`),
    KEY `idx_sale_item_product` (`product_id`),
    CONSTRAINT `fk_sale_item_sale` 
        FOREIGN KEY (`sale_id`) REFERENCES `Sales` (`sale_id`) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_sale_item_product` 
        FOREIGN KEY (`product_id`) REFERENCES `Product` (`product_id`) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_sale_item_qty` CHECK (`quantity` > 0),
    CONSTRAINT `chk_sale_item_price` CHECK (`unit_selling_price` >= 0),
    CONSTRAINT `chk_sale_item_cost` CHECK (`unit_cost_at_sale` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- TABLE 8: Inventory
-- PURPOSE: 1:1 table maintaining current available stock and weighted average cost
-- ==============================================================================
CREATE TABLE `Inventory` (
    `product_id` INT NOT NULL,
    `quantity_available` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    `average_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `last_updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`product_id`),
    CONSTRAINT `fk_inventory_product` 
        FOREIGN KEY (`product_id`) REFERENCES `Product` (`product_id`) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `chk_inventory_qty` CHECK (`quantity_available` >= 0),
    CONSTRAINT `chk_inventory_cost` CHECK (`average_cost` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- TABLE 9: Stock_Movement
-- PURPOSE: Immutable audit ledger recording every change in inventory
-- ==============================================================================
CREATE TABLE `Stock_Movement` (
    `movement_id` INT AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `movement_date` DATE NOT NULL,
    `movement_type` VARCHAR(25) NOT NULL,
    `quantity` DECIMAL(10,3) NOT NULL,
    `remarks` VARCHAR(150) DEFAULT NULL,
    PRIMARY KEY (`movement_id`),
    KEY `idx_stock_movement_product` (`product_id`),
    KEY `idx_stock_movement_date` (`movement_date`),
    KEY `idx_stock_movement_type` (`movement_type`),
    CONSTRAINT `fk_stock_movement_product` 
        FOREIGN KEY (`product_id`) REFERENCES `Product` (`product_id`) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_stock_movement_qty` CHECK (`quantity` > 0),
    CONSTRAINT `chk_stock_movement_type` CHECK (`movement_type` IN (
        'OPENING', 
        'PURCHASE', 
        'SALE', 
        'INTERNAL_USE', 
        'DAMAGE', 
        'CUSTOMER_RETURN', 
        'SUPPLIER_RETURN'
    ))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

