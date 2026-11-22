-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: grocery_inventory
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `grocery_inventory`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `grocery_inventory` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `grocery_inventory`;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(60) NOT NULL,
  `description` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uk_category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,'Grains & Flours','Staple food grains, whole wheat, rice varieties, and milled flours'),(2,'Pulses & Lentils','High-protein lentils, dals, peas, and organic legumes'),(3,'Dairy & Breakfast','Fresh milk, butter, cheese, breakfast cereals, and bakery items'),(4,'Beverages','Black tea, green tea, roasted filter coffee, and health drinks'),(5,'Cooking Oils & Spices','Edible cooking oils, ground spices, whole aromatic spices, and salt'),(6,'Snacks & Confectionery','Biscuits, crackers, potato crisps, snacks, and cookies'),(7,'Household & Cleaning','Laundry detergents, toilet soaps, dishwash liquids, and surface cleaners');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `dead_stock_products`
--

DROP TABLE IF EXISTS `dead_stock_products`;
/*!50001 DROP VIEW IF EXISTS `dead_stock_products`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `dead_stock_products` AS SELECT
 1 AS `product_id`,
  1 AS `product_name`,
  1 AS `category_name`,
  1 AS `quantity_available`,
  1 AS `unit`,
  1 AS `average_cost`,
  1 AS `tied_up_capital`,
  1 AS `last_sale_date`,
  1 AS `first_stock_date`,
  1 AS `reference_date`,
  1 AS `days_without_sale`,
  1 AS `classification_reason` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `product_id` int(11) NOT NULL,
  `quantity_available` decimal(10,3) NOT NULL DEFAULT 0.000,
  `average_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_inventory_qty` CHECK (`quantity_available` >= 0),
  CONSTRAINT `chk_inventory_cost` CHECK (`average_cost` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (1,142.000,79.03,'2026-10-26 18:30:00'),(2,155.000,36.50,'2026-10-26 18:30:00'),(3,18.000,130.00,'2026-10-26 18:30:00'),(4,40.000,105.00,'2026-10-26 18:30:00'),(5,111.500,37.00,'2026-10-26 18:30:00'),(6,57.000,20.00,'2026-10-26 18:30:00'),(7,95.000,118.00,'2026-10-26 18:30:00'),(8,8.500,52.00,'2026-10-26 18:30:00'),(9,24.000,310.00,'2026-10-26 18:30:00'),(10,7.000,95.00,'2026-10-26 18:30:00'),(11,70.000,27.00,'2026-10-26 18:30:00'),(12,55.000,22.00,'2026-10-26 18:30:00'),(13,30.000,190.00,'2026-10-26 18:30:00'),(14,14.500,260.00,'2026-10-26 18:30:00'),(15,39.000,80.00,'2026-10-26 18:30:00'),(16,40.000,115.00,'2026-10-26 18:30:00'),(17,85.000,28.00,'2026-10-26 18:30:00'),(18,25.000,160.00,'2026-10-26 18:30:00'),(19,13.500,520.00,'2026-10-31 01:17:28');
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_validate_inventory_insert`
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_validate_inventory_update`
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary table structure for view `low_stock_products`
--

DROP TABLE IF EXISTS `low_stock_products`;
/*!50001 DROP VIEW IF EXISTS `low_stock_products`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `low_stock_products` AS SELECT
 1 AS `product_id`,
  1 AS `product_name`,
  1 AS `category_name`,
  1 AS `quantity_available`,
  1 AS `reorder_level`,
  1 AS `unit`,
  1 AS `shortage_quantity`,
  1 AS `stock_condition` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `monthly_transaction_summary`
--

DROP TABLE IF EXISTS `monthly_transaction_summary`;
/*!50001 DROP VIEW IF EXISTS `monthly_transaction_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `monthly_transaction_summary` AS SELECT
 1 AS `transaction_month`,
  1 AS `transaction_type`,
  1 AS `total_transactions`,
  1 AS `total_financial_value` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `reorder_level` decimal(10,3) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `idx_product_category` (`category_id`),
  KEY `idx_product_supplier` (`supplier_id`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_product_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_product_selling_price` CHECK (`selling_price` >= 0),
  CONSTRAINT `chk_product_reorder_level` CHECK (`reorder_level` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
INSERT INTO `product` VALUES (1,'Basmati Rice Royal 1kg',1,1,95.00,40.000,'kg','2026-05-01 09:00:00'),(2,'Whole Wheat Flour (Atta)',1,1,45.00,50.000,'kg','2026-05-01 09:10:00'),(3,'Toor Dal Premium Unpolished',2,1,160.00,25.000,'kg','2026-05-01 09:15:00'),(4,'Moong Dal Yellow Split',2,1,130.00,20.000,'kg','2026-05-01 09:20:00'),(5,'Refined White Sugar',5,3,44.00,35.000,'kg','2026-05-01 09:25:00'),(6,'Iodized Table Salt 1kg',5,3,26.00,25.000,'packet','2026-05-01 09:30:00'),(7,'Sunflower Cooking Oil',5,3,145.00,30.000,'litre','2026-05-01 09:35:00'),(8,'Fresh Full Cream Cow Milk',3,2,64.00,20.000,'litre','2026-05-01 09:40:00'),(9,'Assam CTC Tea Powder',4,4,380.00,15.000,'kg','2026-05-01 09:45:00'),(10,'Instant Roasted Coffee 100g',4,4,125.00,15.000,'packet','2026-05-01 09:50:00'),(11,'Marie Gold Biscuits 250g',6,4,35.00,25.000,'packet','2026-05-01 09:55:00'),(12,'Crunchy Potato Chips 100g',6,4,30.00,20.000,'packet','2026-05-01 10:00:00'),(13,'Organic Turmeric Powder',5,3,240.00,10.000,'kg','2026-05-01 10:05:00'),(14,'Kashmiri Red Chilli Powder',5,3,320.00,10.000,'kg','2026-05-01 10:10:00'),(15,'Dishwash Liquid Gel 500ml',7,5,105.00,12.000,'bottle','2026-05-01 10:15:00'),(16,'Active Laundry Detergent 1kg',7,5,145.00,15.000,'packet','2026-05-01 10:20:00'),(17,'Neem Toilet Soap 125g',7,5,40.00,30.000,'piece','2026-05-01 10:25:00'),(18,'Organic Chia Seeds 250g',2,1,210.00,10.000,'packet','2026-05-01 10:30:00'),(19,'Organic Almonds 500g',6,NULL,680.00,12.000,'kg','2026-10-31 01:17:27');
/*!40000 ALTER TABLE `product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `product_inventory_status`
--

DROP TABLE IF EXISTS `product_inventory_status`;
/*!50001 DROP VIEW IF EXISTS `product_inventory_status`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `product_inventory_status` AS SELECT
 1 AS `product_id`,
  1 AS `product_name`,
  1 AS `category_name`,
  1 AS `unit`,
  1 AS `selling_price`,
  1 AS `average_cost`,
  1 AS `quantity_available`,
  1 AS `reorder_level`,
  1 AS `current_inventory_value`,
  1 AS `stock_status`,
  1 AS `last_updated` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `purchase`
--

DROP TABLE IF EXISTS `purchase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase` (
  `purchase_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `purchase_date` date NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  PRIMARY KEY (`purchase_id`),
  UNIQUE KEY `uk_purchase_invoice` (`invoice_number`),
  KEY `idx_purchase_supplier` (`supplier_id`),
  KEY `idx_purchase_date` (`purchase_date`),
  CONSTRAINT `fk_purchase_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`supplier_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase`
--

LOCK TABLES `purchase` WRITE;
/*!40000 ALTER TABLE `purchase` DISABLE KEYS */;
INSERT INTO `purchase` VALUES (1,1,'2026-06-15','INV-RA-2026-0101'),(2,3,'2026-07-10','INV-HS-2026-0442'),(3,2,'2026-08-05','INV-MD-2026-0819'),(4,4,'2026-08-20','INV-MF-2026-1102'),(5,5,'2026-09-12','INV-NH-2026-0560'),(6,1,'2026-10-05','INV-RA-2026-0925'),(7,6,'2026-10-30','TEST-PUR-2105');
/*!40000 ALTER TABLE `purchase` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_item`
--

DROP TABLE IF EXISTS `purchase_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_item` (
  `purchase_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`purchase_item_id`),
  KEY `idx_purchase_item_purchase` (`purchase_id`),
  KEY `idx_purchase_item_product` (`product_id`),
  CONSTRAINT `fk_purchase_item_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_item_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchase` (`purchase_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_purchase_item_qty` CHECK (`quantity` > 0),
  CONSTRAINT `chk_purchase_item_cost` CHECK (`unit_cost` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_item`
--

LOCK TABLES `purchase_item` WRITE;
/*!40000 ALTER TABLE `purchase_item` DISABLE KEYS */;
INSERT INTO `purchase_item` VALUES (1,1,1,50.000,78.00,'2027-06-15'),(2,1,2,80.000,36.00,'2026-12-15'),(3,1,3,30.000,130.00,'2027-03-15'),(4,2,5,50.000,37.00,'2027-07-10'),(5,2,7,40.000,118.00,'2027-01-10'),(6,2,13,10.000,190.00,'2027-07-10'),(7,3,8,40.000,52.00,'2026-08-10'),(8,4,11,40.000,27.00,'2027-02-20'),(9,4,12,30.000,22.00,'2026-12-20'),(10,4,10,20.000,95.00,'2027-08-20'),(11,5,15,20.000,80.00,'2028-09-12'),(12,5,16,25.000,115.00,'2028-09-12'),(13,5,17,50.000,28.00,'2028-09-12'),(14,6,1,40.000,82.00,'2027-10-05'),(15,6,2,50.000,37.50,'2027-04-05'),(16,7,19,20.000,520.00,'2027-12-31');
/*!40000 ALTER TABLE `purchase_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_item`
--

DROP TABLE IF EXISTS `sale_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_item` (
  `sale_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `unit_selling_price` decimal(10,2) NOT NULL,
  `unit_cost_at_sale` decimal(10,2) NOT NULL,
  PRIMARY KEY (`sale_item_id`),
  KEY `idx_sale_item_sale` (`sale_id`),
  KEY `idx_sale_item_product` (`product_id`),
  CONSTRAINT `fk_sale_item_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sale_item_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_sale_item_qty` CHECK (`quantity` > 0),
  CONSTRAINT `chk_sale_item_price` CHECK (`unit_selling_price` >= 0),
  CONSTRAINT `chk_sale_item_cost` CHECK (`unit_cost_at_sale` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_item`
--

LOCK TABLES `sale_item` WRITE;
/*!40000 ALTER TABLE `sale_item` DISABLE KEYS */;
INSERT INTO `sale_item` VALUES (1,1,14,0.500,320.00,260.00),(2,1,1,5.000,95.00,78.00),(3,1,5,2.000,44.00,37.00),(4,2,1,10.000,95.00,78.00),(5,2,2,25.000,45.00,36.00),(6,2,7,5.000,145.00,118.00),(7,2,6,3.000,26.00,20.00),(8,3,8,35.000,64.00,52.00),(9,3,11,20.000,35.00,27.00),(10,3,12,15.000,30.00,22.00),(11,4,1,20.000,95.00,78.00),(12,4,2,40.000,45.00,36.00),(13,4,3,35.000,160.00,130.00),(14,4,15,5.000,105.00,80.00),(15,4,17,25.000,40.00,28.00),(16,5,1,15.000,95.00,79.03),(17,5,2,30.000,45.00,36.50),(18,5,5,15.000,44.00,37.00),(19,5,7,10.000,145.00,118.00),(20,5,8,24.500,64.00,52.00),(21,6,3,27.000,160.00,130.00),(22,6,10,43.000,125.00,95.00),(23,6,16,15.000,145.00,115.00),(24,7,19,5.000,680.00,520.00);
/*!40000 ALTER TABLE `sale_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_date` date NOT NULL,
  `bill_number` varchar(50) NOT NULL,
  PRIMARY KEY (`sale_id`),
  UNIQUE KEY `uk_sales_bill` (`bill_number`),
  KEY `idx_sales_date` (`sale_date`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,'2026-06-10','BILL-2026-0601'),(2,'2026-07-15','BILL-2026-0715'),(3,'2026-08-18','BILL-2026-0818'),(4,'2026-09-22','BILL-2026-0922'),(5,'2026-10-15','BILL-2026-1015'),(6,'2026-10-26','BILL-2026-1026'),(7,'2026-10-30','TEST-BILL-9818');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movement`
--

DROP TABLE IF EXISTS `stock_movement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movement` (
  `movement_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `movement_date` date NOT NULL,
  `movement_type` varchar(25) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `remarks` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`movement_id`),
  KEY `idx_stock_movement_product` (`product_id`),
  KEY `idx_stock_movement_date` (`movement_date`),
  KEY `idx_stock_movement_type` (`movement_type`),
  CONSTRAINT `fk_stock_movement_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_stock_movement_qty` CHECK (`quantity` > 0),
  CONSTRAINT `chk_stock_movement_type` CHECK (`movement_type` in ('OPENING','PURCHASE','SALE','INTERNAL_USE','DAMAGE','CUSTOMER_RETURN','SUPPLIER_RETURN'))
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movement`
--

LOCK TABLES `stock_movement` WRITE;
/*!40000 ALTER TABLE `stock_movement` DISABLE KEYS */;
INSERT INTO `stock_movement` VALUES (1,1,'2026-06-01','OPENING',100.000,'Initial verified inventory opening balance'),(2,2,'2026-06-01','OPENING',120.000,'Initial verified inventory opening balance'),(3,3,'2026-06-01','OPENING',50.000,'Initial verified inventory opening balance'),(4,4,'2026-06-01','OPENING',40.000,'Initial verified inventory opening balance'),(5,5,'2026-06-01','OPENING',80.000,'Initial verified inventory opening balance'),(6,6,'2026-06-01','OPENING',60.000,'Initial verified inventory opening balance'),(7,7,'2026-06-01','OPENING',70.000,'Initial verified inventory opening balance'),(8,8,'2026-06-01','OPENING',30.000,'Initial verified inventory opening balance'),(9,9,'2026-06-01','OPENING',25.000,'Initial verified inventory opening balance'),(10,10,'2026-06-01','OPENING',30.000,'Initial verified inventory opening balance'),(11,11,'2026-06-01','OPENING',50.000,'Initial verified inventory opening balance'),(12,12,'2026-06-01','OPENING',40.000,'Initial verified inventory opening balance'),(13,13,'2026-06-01','OPENING',20.000,'Initial verified inventory opening balance'),(14,14,'2026-06-01','OPENING',15.000,'Initial verified inventory opening balance'),(15,15,'2026-06-01','OPENING',25.000,'Initial verified inventory opening balance'),(16,16,'2026-06-01','OPENING',30.000,'Initial verified inventory opening balance'),(17,17,'2026-06-01','OPENING',60.000,'Initial verified inventory opening balance'),(18,18,'2026-06-01','OPENING',25.000,'Initial verified inventory opening balance'),(19,1,'2026-06-15','PURCHASE',50.000,'Procurement from Royal Agro INV-RA-2026-0101'),(20,2,'2026-06-15','PURCHASE',80.000,'Procurement from Royal Agro INV-RA-2026-0101'),(21,3,'2026-06-15','PURCHASE',30.000,'Procurement from Royal Agro INV-RA-2026-0101'),(22,5,'2026-07-10','PURCHASE',50.000,'Procurement from Heritage Spices INV-HS-2026-0442'),(23,7,'2026-07-10','PURCHASE',40.000,'Procurement from Heritage Spices INV-HS-2026-0442'),(24,13,'2026-07-10','PURCHASE',10.000,'Procurement from Heritage Spices INV-HS-2026-0442'),(25,8,'2026-08-05','PURCHASE',40.000,'Procurement from Mother Dairy INV-MD-2026-0819'),(26,11,'2026-08-20','PURCHASE',40.000,'Procurement from Metro FMCG INV-MF-2026-1102'),(27,12,'2026-08-20','PURCHASE',30.000,'Procurement from Metro FMCG INV-MF-2026-1102'),(28,10,'2026-08-20','PURCHASE',20.000,'Procurement from Metro FMCG INV-MF-2026-1102'),(29,15,'2026-09-12','PURCHASE',20.000,'Procurement from National Hygiene INV-NH-2026-0560'),(30,16,'2026-09-12','PURCHASE',25.000,'Procurement from National Hygiene INV-NH-2026-0560'),(31,17,'2026-09-12','PURCHASE',50.000,'Procurement from National Hygiene INV-NH-2026-0560'),(32,1,'2026-10-05','PURCHASE',40.000,'Procurement from Royal Agro INV-RA-2026-0925'),(33,2,'2026-10-05','PURCHASE',50.000,'Procurement from Royal Agro INV-RA-2026-0925'),(34,14,'2026-06-10','SALE',0.500,'Customer sale Bill BILL-2026-0601'),(35,1,'2026-06-10','SALE',5.000,'Customer sale Bill BILL-2026-0601'),(36,5,'2026-06-10','SALE',2.000,'Customer sale Bill BILL-2026-0601'),(37,1,'2026-07-15','SALE',10.000,'Customer sale Bill BILL-2026-0715'),(38,2,'2026-07-15','SALE',25.000,'Customer sale Bill BILL-2026-0715'),(39,7,'2026-07-15','SALE',5.000,'Customer sale Bill BILL-2026-0715'),(40,6,'2026-07-15','SALE',3.000,'Customer sale Bill BILL-2026-0715'),(41,8,'2026-08-18','SALE',35.000,'Customer sale Bill BILL-2026-0818'),(42,11,'2026-08-18','SALE',20.000,'Customer sale Bill BILL-2026-0818'),(43,12,'2026-08-18','SALE',15.000,'Customer sale Bill BILL-2026-0818'),(44,1,'2026-09-22','SALE',20.000,'Customer sale Bill BILL-2026-0922'),(45,2,'2026-09-22','SALE',40.000,'Customer sale Bill BILL-2026-0922'),(46,3,'2026-09-22','SALE',35.000,'Customer sale Bill BILL-2026-0922'),(47,15,'2026-09-22','SALE',5.000,'Customer sale Bill BILL-2026-0922'),(48,17,'2026-09-22','SALE',25.000,'Customer sale Bill BILL-2026-0922'),(49,1,'2026-10-15','SALE',15.000,'Customer sale Bill BILL-2026-1015'),(50,2,'2026-10-15','SALE',30.000,'Customer sale Bill BILL-2026-1015'),(51,5,'2026-10-15','SALE',15.000,'Customer sale Bill BILL-2026-1015'),(52,7,'2026-10-15','SALE',10.000,'Customer sale Bill BILL-2026-1015'),(53,8,'2026-10-15','SALE',24.500,'Customer sale Bill BILL-2026-1015'),(54,3,'2026-10-26','SALE',27.000,'Customer sale Bill BILL-2026-1026'),(55,10,'2026-10-26','SALE',43.000,'Customer sale Bill BILL-2026-1026'),(56,16,'2026-10-26','SALE',15.000,'Customer sale Bill BILL-2026-1026'),(57,15,'2026-08-10','INTERNAL_USE',1.000,'Store maintenance & floor washing sanitizer use'),(58,9,'2026-09-01','INTERNAL_USE',1.000,'Staff tea & pantry consumption for September'),(59,8,'2026-10-18','DAMAGE',2.000,'Leaked dairy pouches spoiled during storage'),(60,5,'2026-10-20','DAMAGE',1.500,'Torn bag water dampness during shelf restocking'),(61,1,'2026-10-22','CUSTOMER_RETURN',2.000,'Customer returned unopened bag with bill receipt'),(62,19,'2026-10-30','PURCHASE',20.000,'Purchase Invoice: TEST-PUR-2105'),(63,19,'2026-10-30','SALE',5.000,'Customer Bill: TEST-BILL-9818'),(64,19,'2026-10-30','INTERNAL_USE',1.000,'Store tasting samples'),(65,19,'2026-10-30','DAMAGE',0.500,'Torn vacuum bag dampness');
/*!40000 ALTER TABLE `stock_movement` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_validate_stock_movement_insert`
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `supplier`
--

DROP TABLE IF EXISTS `supplier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier`
--

LOCK TABLES `supplier` WRITE;
/*!40000 ALTER TABLE `supplier` DISABLE KEYS */;
INSERT INTO `supplier` VALUES (1,'Royal Agro & Grains Wholesalers','9822011223','orders@royalagro.com','Plot 42, Agricultural APMC Yard, Sector 18, City'),(2,'Mother Dairy & Farm Fresh Co.','9822022334','distribution@motherdairyfarm.com','Survey 108, Milk Chilling Center, Highway Road, City'),(3,'Heritage Spices & Oil Millers','9822033445','supply@heritagespices.in','B-12 Industrial Estate, Grain Market Road, City'),(4,'Metro FMCG & Snacks Distributors','9822044556','sales@metrofmcg.com','Warehouse Complex 5, Transport Nagar, City'),(5,'National Cleaning & Hygiene Hub','9822055667','contact@nationalhygiene.com','Unit 88, Wholesale Consumer Park, City'),(6,'Nut & DryFruit Traders','9811002233','nuts@dryfruit.com','Wholesale Market Gate 2');
/*!40000 ALTER TABLE `supplier` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'grocery_inventory'
--

--
-- Current Database: `grocery_inventory`
--

USE `grocery_inventory`;

--
-- Final view structure for view `dead_stock_products`
--

/*!50001 DROP VIEW IF EXISTS `dead_stock_products`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `dead_stock_products` AS select `p`.`product_id` AS `product_id`,`p`.`product_name` AS `product_name`,`c`.`category_name` AS `category_name`,`i`.`quantity_available` AS `quantity_available`,`p`.`unit` AS `unit`,`i`.`average_cost` AS `average_cost`,round(`i`.`quantity_available` * `i`.`average_cost`,2) AS `tied_up_capital`,`sales_info`.`last_sale_date` AS `last_sale_date`,`stock_info`.`first_stock_date` AS `first_stock_date`,coalesce(`sales_info`.`last_sale_date`,`stock_info`.`first_stock_date`) AS `reference_date`,to_days(curdate()) - to_days(coalesce(`sales_info`.`last_sale_date`,`stock_info`.`first_stock_date`)) AS `days_without_sale`,case when `sales_info`.`last_sale_date` is null then 'Never Sold Since Initial Receipt' else 'No Customer Sales in 90+ Days' end AS `classification_reason` from ((((`product` `p` join `category` `c` on(`p`.`category_id` = `c`.`category_id`)) join `inventory` `i` on(`p`.`product_id` = `i`.`product_id`)) left join (select `si`.`product_id` AS `product_id`,max(`s`.`sale_date`) AS `last_sale_date` from (`sale_item` `si` join `sales` `s` on(`si`.`sale_id` = `s`.`sale_id`)) group by `si`.`product_id`) `sales_info` on(`p`.`product_id` = `sales_info`.`product_id`)) left join (select `stock_movement`.`product_id` AS `product_id`,min(`stock_movement`.`movement_date`) AS `first_stock_date` from `stock_movement` where `stock_movement`.`movement_type` in ('OPENING','PURCHASE') group by `stock_movement`.`product_id`) `stock_info` on(`p`.`product_id` = `stock_info`.`product_id`)) where `i`.`quantity_available` > 0 and (`sales_info`.`last_sale_date` is not null and to_days(curdate()) - to_days(`sales_info`.`last_sale_date`) >= 90 or `sales_info`.`last_sale_date` is null and `stock_info`.`first_stock_date` is not null and to_days(curdate()) - to_days(`stock_info`.`first_stock_date`) >= 90) order by to_days(curdate()) - to_days(coalesce(`sales_info`.`last_sale_date`,`stock_info`.`first_stock_date`)) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `low_stock_products`
--

/*!50001 DROP VIEW IF EXISTS `low_stock_products`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `low_stock_products` AS select `p`.`product_id` AS `product_id`,`p`.`product_name` AS `product_name`,`c`.`category_name` AS `category_name`,`i`.`quantity_available` AS `quantity_available`,`p`.`reorder_level` AS `reorder_level`,`p`.`unit` AS `unit`,round(`p`.`reorder_level` - `i`.`quantity_available`,3) AS `shortage_quantity`,case when `i`.`quantity_available` = 0 then 'OUT OF STOCK' else 'REORDER NEEDED' end AS `stock_condition` from ((`product` `p` join `category` `c` on(`p`.`category_id` = `c`.`category_id`)) join `inventory` `i` on(`p`.`product_id` = `i`.`product_id`)) where `i`.`quantity_available` <= `p`.`reorder_level` order by round(`p`.`reorder_level` - `i`.`quantity_available`,3) desc,`i`.`quantity_available` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `monthly_transaction_summary`
--

/*!50001 DROP VIEW IF EXISTS `monthly_transaction_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `monthly_transaction_summary` AS select date_format(`s`.`sale_date`,'%Y-%m') AS `transaction_month`,'SALES' AS `transaction_type`,count(distinct `s`.`sale_id`) AS `total_transactions`,round(sum(`si`.`quantity` * `si`.`unit_selling_price`),2) AS `total_financial_value` from (`sales` `s` join `sale_item` `si` on(`s`.`sale_id` = `si`.`sale_id`)) group by date_format(`s`.`sale_date`,'%Y-%m') union all select date_format(`p`.`purchase_date`,'%Y-%m') AS `transaction_month`,'PURCHASES' AS `transaction_type`,count(distinct `p`.`purchase_id`) AS `total_transactions`,round(sum(`pi`.`quantity` * `pi`.`unit_cost`),2) AS `total_financial_value` from (`purchase` `p` join `purchase_item` `pi` on(`p`.`purchase_id` = `pi`.`purchase_id`)) group by date_format(`p`.`purchase_date`,'%Y-%m') order by `transaction_month` desc,`transaction_type` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `product_inventory_status`
--

/*!50001 DROP VIEW IF EXISTS `product_inventory_status`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `product_inventory_status` AS select `p`.`product_id` AS `product_id`,`p`.`product_name` AS `product_name`,`c`.`category_name` AS `category_name`,`p`.`unit` AS `unit`,`p`.`selling_price` AS `selling_price`,`i`.`average_cost` AS `average_cost`,`i`.`quantity_available` AS `quantity_available`,`p`.`reorder_level` AS `reorder_level`,round(`i`.`quantity_available` * `i`.`average_cost`,2) AS `current_inventory_value`,case when `i`.`quantity_available` = 0 then 'Out of Stock' when `i`.`quantity_available` <= `p`.`reorder_level` then 'Low Stock' else 'Adequate' end AS `stock_status`,`i`.`last_updated` AS `last_updated` from ((`product` `p` join `category` `c` on(`p`.`category_id` = `c`.`category_id`)) join `inventory` `i` on(`p`.`product_id` = `i`.`product_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-31  1:17:37
