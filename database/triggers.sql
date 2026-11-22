-- ==============================================================================
-- GROCERY STORE INVENTORY MANAGEMENT SYSTEM
-- SCRIPT: triggers.sql
-- PURPOSE: Defensive database triggers to enforce business integrity rules
--          and prevent negative inventory at the storage engine level.
-- DBMS: MariaDB / MySQL (phpMyAdmin Compatible)
-- ==============================================================================

USE `grocery_inventory`;

DROP TRIGGER IF EXISTS `trg_validate_inventory_insert`;
DROP TRIGGER IF EXISTS `trg_validate_inventory_update`;
DROP TRIGGER IF EXISTS `trg_validate_stock_movement_insert`;

DELIMITER $$

-- ==============================================================================
-- TRIGGER 1: trg_validate_inventory_insert
-- PURPOSE: Prevent negative inventory or negative cost upon product creation.
-- ==============================================================================
CREATE TRIGGER `trg_validate_inventory_insert`
BEFORE INSERT ON `Inventory`
FOR EACH ROW
BEGIN
    IF NEW.quantity_available < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'DBMS Integrity Error: quantity_available cannot be negative.';
    END IF;

    IF NEW.average_cost < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'DBMS Integrity Error: average_cost cannot be negative.';
    END IF;
END$$

-- ==============================================================================
-- TRIGGER 2: trg_validate_inventory_update
-- PURPOSE: Crucial defensive trigger preventing any race condition, rogue query,
--          or billing error from reducing stock below zero.
-- ==============================================================================
CREATE TRIGGER `trg_validate_inventory_update`
BEFORE UPDATE ON `Inventory`
FOR EACH ROW
BEGIN
    IF NEW.quantity_available < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'DBMS Transaction Rejected: Insufficient stock. Available inventory cannot become negative.';
    END IF;

    IF NEW.average_cost < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'DBMS Transaction Rejected: Weighted average cost cannot be negative.';
    END IF;
END$$

-- ==============================================================================
-- TRIGGER 3: trg_validate_stock_movement_insert
-- PURPOSE: Guarantee that all quantities entered into Stock_Movement are strictly
--          positive magnitudes, and validate movement classifications.
-- ==============================================================================
CREATE TRIGGER `trg_validate_stock_movement_insert`
BEFORE INSERT ON `Stock_Movement`
FOR EACH ROW
BEGIN
    IF NEW.quantity <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'DBMS Validation Error: Stock_Movement quantity must be strictly greater than zero.';
    END IF;

    IF NEW.movement_type NOT IN (
        'OPENING', 
        'PURCHASE', 
        'SALE', 
        'INTERNAL_USE', 
        'DAMAGE', 
        'CUSTOMER_RETURN', 
        'SUPPLIER_RETURN'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'DBMS Validation Error: Invalid movement_type specified in Stock_Movement.';
    END IF;
END$$

DELIMITER ;

