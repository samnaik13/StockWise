-- ==============================================================================
-- GROCERY STORE INVENTORY MANAGEMENT SYSTEM
-- SCRIPT: sample_data.sql
-- PURPOSE: Realistic sample dataset demonstrating all 7 algorithms, low stock,
--          dead stock, weighted average cost, multi-item sales/purchases,
--          fractional units, internal usage, and damaged stock.
-- DBMS: MariaDB / MySQL (phpMyAdmin Compatible)
-- ==============================================================================

USE `grocery_inventory`;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `Stock_Movement`;
TRUNCATE TABLE `Inventory`;
TRUNCATE TABLE `Sale_Item`;
TRUNCATE TABLE `Sales`;
TRUNCATE TABLE `Purchase_Item`;
TRUNCATE TABLE `Purchase`;
TRUNCATE TABLE `Supplier`;
TRUNCATE TABLE `Product`;
TRUNCATE TABLE `Category`;
SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- 1. INSERT CATEGORIES (7 Categories)
-- ==============================================================================
INSERT INTO `Category` (`category_id`, `category_name`, `description`) VALUES
(1, 'Grains & Flours', 'Staple food grains, whole wheat, rice varieties, and milled flours'),
(2, 'Pulses & Lentils', 'High-protein lentils, dals, peas, and organic legumes'),
(3, 'Dairy & Breakfast', 'Fresh milk, butter, cheese, breakfast cereals, and bakery items'),
(4, 'Beverages', 'Black tea, green tea, roasted filter coffee, and health drinks'),
(5, 'Cooking Oils & Spices', 'Edible cooking oils, ground spices, whole aromatic spices, and salt'),
(6, 'Snacks & Confectionery', 'Biscuits, crackers, potato crisps, snacks, and cookies'),
(7, 'Household & Cleaning', 'Laundry detergents, toilet soaps, dishwash liquids, and surface cleaners');

-- ==============================================================================
-- 2. INSERT SUPPLIERS (5 Suppliers)
-- ==============================================================================
INSERT INTO `Supplier` (`supplier_id`, `supplier_name`, `phone`, `email`, `address`) VALUES
(1, 'Royal Agro & Grains Wholesalers', '9822011223', 'orders@royalagro.com', 'Plot 42, Agricultural APMC Yard, Sector 18, City'),
(2, 'Mother Dairy & Farm Fresh Co.', '9822022334', 'distribution@motherdairyfarm.com', 'Survey 108, Milk Chilling Center, Highway Road, City'),
(3, 'Heritage Spices & Oil Millers', '9822033445', 'supply@heritagespices.in', 'B-12 Industrial Estate, Grain Market Road, City'),
(4, 'Metro FMCG & Snacks Distributors', '9822044556', 'sales@metrofmcg.com', 'Warehouse Complex 5, Transport Nagar, City'),
(5, 'National Cleaning & Hygiene Hub', '9822055667', 'contact@nationalhygiene.com', 'Unit 88, Wholesale Consumer Park, City');

-- ==============================================================================
-- 3. INSERT PRODUCTS (18 Grocery Products with diverse units & linked suppliers)
-- ==============================================================================
INSERT INTO `Product` (`product_id`, `product_name`, `category_id`, `supplier_id`, `selling_price`, `reorder_level`, `unit`, `created_at`) VALUES
(1,  'Basmati Rice Royal 1kg',        1, 1,  95.00,  40.000, 'kg',     '2026-05-01 09:00:00'),
(2,  'Whole Wheat Flour (Atta)',     1, 1,  45.00,  50.000, 'kg',     '2026-05-01 09:10:00'),
(3,  'Toor Dal Premium Unpolished',   2, 1, 160.00,  25.000, 'kg',     '2026-05-01 09:15:00'),
(4,  'Moong Dal Yellow Split',        2, 1, 130.00,  20.000, 'kg',     '2026-05-01 09:20:00'),
(5,  'Refined White Sugar',           5, 3,  44.00,  35.000, 'kg',     '2026-05-01 09:25:00'),
(6,  'Iodized Table Salt 1kg',        5, 3,  26.00,  25.000, 'packet', '2026-05-01 09:30:00'),
(7,  'Sunflower Cooking Oil',         5, 3, 145.00,  30.000, 'litre',  '2026-05-01 09:35:00'),
(8,  'Fresh Full Cream Cow Milk',     3, 2,  64.00,  20.000, 'litre',  '2026-05-01 09:40:00'),
(9,  'Assam CTC Tea Powder',          4, 4, 380.00,  15.000, 'kg',     '2026-05-01 09:45:00'),
(10, 'Instant Roasted Coffee 100g',   4, 4, 125.00,  15.000, 'packet', '2026-05-01 09:50:00'),
(11, 'Marie Gold Biscuits 250g',      6, 4,  35.00,  25.000, 'packet', '2026-05-01 09:55:00'),
(12, 'Crunchy Potato Chips 100g',     6, 4,  30.00,  20.000, 'packet', '2026-05-01 10:00:00'),
(13, 'Organic Turmeric Powder',       5, 3, 240.00,  10.000, 'kg',     '2026-05-01 10:05:00'),
(14, 'Kashmiri Red Chilli Powder',    5, 3, 320.00,  10.000, 'kg',     '2026-05-01 10:10:00'),
(15, 'Dishwash Liquid Gel 500ml',     7, 5, 105.00,  12.000, 'bottle', '2026-05-01 10:15:00'),
(16, 'Active Laundry Detergent 1kg',  7, 5, 145.00,  15.000, 'packet', '2026-05-01 10:20:00'),
(17, 'Neem Toilet Soap 125g',         7, 5,  40.00,  30.000, 'piece',  '2026-05-01 10:25:00'),
(18, 'Organic Chia Seeds 250g',       2, 1, 210.00,  10.000, 'packet', '2026-05-01 10:30:00');

-- ==============================================================================
-- 4. INSERT OPENING STOCK MOVEMENTS (Recorded as of 2026-06-01)
-- ==============================================================================
INSERT INTO `Stock_Movement` (`movement_id`, `product_id`, `movement_date`, `movement_type`, `quantity`, `remarks`) VALUES
(1,  1,  '2026-06-01', 'OPENING', 100.000, 'Initial verified inventory opening balance'),
(2,  2,  '2026-06-01', 'OPENING', 120.000, 'Initial verified inventory opening balance'),
(3,  3,  '2026-06-01', 'OPENING',  50.000, 'Initial verified inventory opening balance'),
(4,  4,  '2026-06-01', 'OPENING',  40.000, 'Initial verified inventory opening balance'),
(5,  5,  '2026-06-01', 'OPENING',  80.000, 'Initial verified inventory opening balance'),
(6,  6,  '2026-06-01', 'OPENING',  60.000, 'Initial verified inventory opening balance'),
(7,  7,  '2026-06-01', 'OPENING',  70.000, 'Initial verified inventory opening balance'),
(8,  8,  '2026-06-01', 'OPENING',  30.000, 'Initial verified inventory opening balance'),
(9,  9,  '2026-06-01', 'OPENING',  25.000, 'Initial verified inventory opening balance'),
(10, 10, '2026-06-01', 'OPENING',  30.000, 'Initial verified inventory opening balance'),
(11, 11, '2026-06-01', 'OPENING',  50.000, 'Initial verified inventory opening balance'),
(12, 12, '2026-06-01', 'OPENING',  40.000, 'Initial verified inventory opening balance'),
(13, 13, '2026-06-01', 'OPENING',  20.000, 'Initial verified inventory opening balance'),
(14, 14, '2026-06-01', 'OPENING',  15.000, 'Initial verified inventory opening balance'),
(15, 15, '2026-06-01', 'OPENING',  25.000, 'Initial verified inventory opening balance'),
(16, 16, '2026-06-01', 'OPENING',  30.000, 'Initial verified inventory opening balance'),
(17, 17, '2026-06-01', 'OPENING',  60.000, 'Initial verified inventory opening balance'),
(18, 18, '2026-06-01', 'OPENING',  25.000, 'Initial verified inventory opening balance'); -- Note: Chia seeds never sold, dead stock candidate!

-- ==============================================================================
-- 5. INSERT PURCHASES & PURCHASE ITEMS (Stock-In Batches across months)
-- ==============================================================================

-- Purchase 1: June 2026 - Grains & Pulses from Royal Agro
INSERT INTO `Purchase` (`purchase_id`, `supplier_id`, `purchase_date`, `invoice_number`) VALUES
(1, 1, '2026-06-15', 'INV-RA-2026-0101');
INSERT INTO `Purchase_Item` (`purchase_item_id`, `purchase_id`, `product_id`, `quantity`, `unit_cost`, `expiry_date`) VALUES
(1, 1, 1,  50.000,  78.00, '2027-06-15'),
(2, 1, 2,  80.000,  36.00, '2026-12-15'),
(3, 1, 3,  30.000, 130.00, '2027-03-15');

-- Purchase 2: July 2026 - Spices, Sugar & Oil from Heritage Spices
INSERT INTO `Purchase` (`purchase_id`, `supplier_id`, `purchase_date`, `invoice_number`) VALUES
(2, 3, '2026-07-10', 'INV-HS-2026-0442');
INSERT INTO `Purchase_Item` (`purchase_item_id`, `purchase_id`, `product_id`, `quantity`, `unit_cost`, `expiry_date`) VALUES
(4, 2, 5,  50.000,  37.00, '2027-07-10'),
(5, 2, 7,  40.000, 118.00, '2027-01-10'),
(6, 2, 13, 10.000, 190.00, '2027-07-10');

-- Purchase 3: August 2026 - Dairy from Mother Dairy & FMCG Snacks
INSERT INTO `Purchase` (`purchase_id`, `supplier_id`, `purchase_date`, `invoice_number`) VALUES
(3, 2, '2026-08-05', 'INV-MD-2026-0819');
INSERT INTO `Purchase_Item` (`purchase_item_id`, `purchase_id`, `product_id`, `quantity`, `unit_cost`, `expiry_date`) VALUES
(7, 3, 8,  40.000,  52.00, '2026-08-10');

INSERT INTO `Purchase` (`purchase_id`, `supplier_id`, `purchase_date`, `invoice_number`) VALUES
(4, 4, '2026-08-20', 'INV-MF-2026-1102');
INSERT INTO `Purchase_Item` (`purchase_item_id`, `purchase_id`, `product_id`, `quantity`, `unit_cost`, `expiry_date`) VALUES
(8,  4, 11, 40.000,  27.00, '2027-02-20'),
(9,  4, 12, 30.000,  22.00, '2026-12-20'),
(10, 4, 10, 20.000,  95.00, '2027-08-20');

-- Purchase 5: September 2026 - Cleaning supplies from National Hygiene
INSERT INTO `Purchase` (`purchase_id`, `supplier_id`, `purchase_date`, `invoice_number`) VALUES
(5, 5, '2026-09-12', 'INV-NH-2026-0560');
INSERT INTO `Purchase_Item` (`purchase_item_id`, `purchase_id`, `product_id`, `quantity`, `unit_cost`, `expiry_date`) VALUES
(11, 5, 15, 20.000,  80.00, '2028-09-12'),
(12, 5, 16, 25.000, 115.00, '2028-09-12'),
(13, 5, 17, 50.000,  28.00, '2028-09-12');

-- Purchase 6: October 2026 - Restocking Rice & Oil (demonstrating varying purchase costs)
INSERT INTO `Purchase` (`purchase_id`, `supplier_id`, `purchase_date`, `invoice_number`) VALUES
(6, 1, '2026-10-05', 'INV-RA-2026-0925');
INSERT INTO `Purchase_Item` (`purchase_item_id`, `purchase_id`, `product_id`, `quantity`, `unit_cost`, `expiry_date`) VALUES
(14, 6, 1,  40.000,  82.00, '2027-10-05'), -- Notice higher cost than June (78.00 -> 82.00)
(15, 6, 2,  50.000,  37.50, '2027-04-05');

-- Record corresponding PURCHASE Stock Movements
INSERT INTO `Stock_Movement` (`movement_id`, `product_id`, `movement_date`, `movement_type`, `quantity`, `remarks`) VALUES
(19, 1,  '2026-06-15', 'PURCHASE', 50.000, 'Procurement from Royal Agro INV-RA-2026-0101'),
(20, 2,  '2026-06-15', 'PURCHASE', 80.000, 'Procurement from Royal Agro INV-RA-2026-0101'),
(21, 3,  '2026-06-15', 'PURCHASE', 30.000, 'Procurement from Royal Agro INV-RA-2026-0101'),
(22, 5,  '2026-07-10', 'PURCHASE', 50.000, 'Procurement from Heritage Spices INV-HS-2026-0442'),
(23, 7,  '2026-07-10', 'PURCHASE', 40.000, 'Procurement from Heritage Spices INV-HS-2026-0442'),
(24, 13, '2026-07-10', 'PURCHASE', 10.000, 'Procurement from Heritage Spices INV-HS-2026-0442'),
(25, 8,  '2026-08-05', 'PURCHASE', 40.000, 'Procurement from Mother Dairy INV-MD-2026-0819'),
(26, 11, '2026-08-20', 'PURCHASE', 40.000, 'Procurement from Metro FMCG INV-MF-2026-1102'),
(27, 12, '2026-08-20', 'PURCHASE', 30.000, 'Procurement from Metro FMCG INV-MF-2026-1102'),
(28, 10, '2026-08-20', 'PURCHASE', 20.000, 'Procurement from Metro FMCG INV-MF-2026-1102'),
(29, 15, '2026-09-12', 'PURCHASE', 20.000, 'Procurement from National Hygiene INV-NH-2026-0560'),
(30, 16, '2026-09-12', 'PURCHASE', 25.000, 'Procurement from National Hygiene INV-NH-2026-0560'),
(31, 17, '2026-09-12', 'PURCHASE', 50.000, 'Procurement from National Hygiene INV-NH-2026-0560'),
(32, 1,  '2026-10-05', 'PURCHASE', 40.000, 'Procurement from Royal Agro INV-RA-2026-0925'),
(33, 2,  '2026-10-05', 'PURCHASE', 50.000, 'Procurement from Royal Agro INV-RA-2026-0925');

-- ==============================================================================
-- 6. INSERT SALES & SALE ITEMS (Demonstrating sales across months, frozen unit_cost_at_sale)
-- ==============================================================================

-- Sale 1: June 2026 (Red Chilli sold here on June 10, then never sold again -> Dead stock test!)
INSERT INTO `Sales` (`sale_id`, `sale_date`, `bill_number`) VALUES
(1, '2026-06-10', 'BILL-2026-0601');
INSERT INTO `Sale_Item` (`sale_item_id`, `sale_id`, `product_id`, `quantity`, `unit_selling_price`, `unit_cost_at_sale`) VALUES
(1, 1, 14,  0.500, 320.00, 260.00), -- Red Chilli last sold here (2026-06-10) > 140 days ago!
(2, 1, 1,   5.000,  95.00,  78.00),
(3, 1, 5,   2.000,  44.00,  37.00);

-- Sale 2: July 2026
INSERT INTO `Sales` (`sale_id`, `sale_date`, `bill_number`) VALUES
(2, '2026-07-15', 'BILL-2026-0715');
INSERT INTO `Sale_Item` (`sale_item_id`, `sale_id`, `product_id`, `quantity`, `unit_selling_price`, `unit_cost_at_sale`) VALUES
(4, 2, 1,  10.000,  95.00,  78.00),
(5, 2, 2,  25.000,  45.00,  36.00),
(6, 2, 7,   5.000, 145.00, 118.00),
(7, 2, 6,   3.000,  26.00,  20.00);

-- Sale 3: August 2026
INSERT INTO `Sales` (`sale_id`, `sale_date`, `bill_number`) VALUES
(3, '2026-08-18', 'BILL-2026-0818');
INSERT INTO `Sale_Item` (`sale_item_id`, `sale_id`, `product_id`, `quantity`, `unit_selling_price`, `unit_cost_at_sale`) VALUES
(8,  3, 8,  35.000,  64.00,  52.00),
(9,  3, 11, 20.000,  35.00,  27.00),
(10, 3, 12, 15.000,  30.00,  22.00);

-- Sale 4: September 2026
INSERT INTO `Sales` (`sale_id`, `sale_date`, `bill_number`) VALUES
(4, '2026-09-22', 'BILL-2026-0922');
INSERT INTO `Sale_Item` (`sale_item_id`, `sale_id`, `product_id`, `quantity`, `unit_selling_price`, `unit_cost_at_sale`) VALUES
(11, 4, 1,  20.000,  95.00,  78.00),
(12, 4, 2,  40.000,  45.00,  36.00),
(13, 4, 3,  35.000, 160.00, 130.00),
(14, 4, 15,  5.000, 105.00,  80.00),
(15, 4, 17, 25.000,  40.00,  28.00);

-- Sale 5: October 2026 (Recent Sales - October 15 and October 26)
INSERT INTO `Sales` (`sale_id`, `sale_date`, `bill_number`) VALUES
(5, '2026-10-15', 'BILL-2026-1015');
INSERT INTO `Sale_Item` (`sale_item_id`, `sale_id`, `product_id`, `quantity`, `unit_selling_price`, `unit_cost_at_sale`) VALUES
(16, 5, 1,  15.000,  95.00,  79.03),
(17, 5, 2,  30.000,  45.00,  36.50),
(18, 5, 5,  15.000,  44.00,  37.00),
(19, 5, 7,  10.000, 145.00, 118.00),
(20, 5, 8,  24.500,  64.00,  52.00);

INSERT INTO `Sales` (`sale_id`, `sale_date`, `bill_number`) VALUES
(6, '2026-10-26', 'BILL-2026-1026');
INSERT INTO `Sale_Item` (`sale_item_id`, `sale_id`, `product_id`, `quantity`, `unit_selling_price`, `unit_cost_at_sale`) VALUES
(21, 6, 3,  27.000, 160.00, 130.00), -- Brings Toor Dal down to 18.000 kg (Reorder: 25.000 -> LOW STOCK!)
(22, 6, 10, 43.000, 125.00,  95.00), -- Brings Coffee down to 7.000 packets (Reorder: 15.000 -> LOW STOCK!)
(23, 6, 16, 15.000, 145.00, 115.00);

-- Record corresponding SALE Stock Movements
INSERT INTO `Stock_Movement` (`movement_id`, `product_id`, `movement_date`, `movement_type`, `quantity`, `remarks`) VALUES
(34, 14, '2026-06-10', 'SALE',  0.500, 'Customer sale Bill BILL-2026-0601'),
(35, 1,  '2026-06-10', 'SALE',  5.000, 'Customer sale Bill BILL-2026-0601'),
(36, 5,  '2026-06-10', 'SALE',  2.000, 'Customer sale Bill BILL-2026-0601'),
(37, 1,  '2026-07-15', 'SALE', 10.000, 'Customer sale Bill BILL-2026-0715'),
(38, 2,  '2026-07-15', 'SALE', 25.000, 'Customer sale Bill BILL-2026-0715'),
(39, 7,  '2026-07-15', 'SALE',  5.000, 'Customer sale Bill BILL-2026-0715'),
(40, 6,  '2026-07-15', 'SALE',  3.000, 'Customer sale Bill BILL-2026-0715'),
(41, 8,  '2026-08-18', 'SALE', 35.000, 'Customer sale Bill BILL-2026-0818'),
(42, 11, '2026-08-18', 'SALE', 20.000, 'Customer sale Bill BILL-2026-0818'),
(43, 12, '2026-08-18', 'SALE', 15.000, 'Customer sale Bill BILL-2026-0818'),
(44, 1,  '2026-09-22', 'SALE', 20.000, 'Customer sale Bill BILL-2026-0922'),
(45, 2,  '2026-09-22', 'SALE', 40.000, 'Customer sale Bill BILL-2026-0922'),
(46, 3,  '2026-09-22', 'SALE', 35.000, 'Customer sale Bill BILL-2026-0922'),
(47, 15, '2026-09-22', 'SALE',  5.000, 'Customer sale Bill BILL-2026-0922'),
(48, 17, '2026-09-22', 'SALE', 25.000, 'Customer sale Bill BILL-2026-0922'),
(49, 1,  '2026-10-15', 'SALE', 15.000, 'Customer sale Bill BILL-2026-1015'),
(50, 2,  '2026-10-15', 'SALE', 30.000, 'Customer sale Bill BILL-2026-1015'),
(51, 5,  '2026-10-15', 'SALE', 15.000, 'Customer sale Bill BILL-2026-1015'),
(52, 7,  '2026-10-15', 'SALE', 10.000, 'Customer sale Bill BILL-2026-1015'),
(53, 8,  '2026-10-15', 'SALE', 24.500, 'Customer sale Bill BILL-2026-1015'),
(54, 3,  '2026-10-26', 'SALE', 27.000, 'Customer sale Bill BILL-2026-1026'),
(55, 10, '2026-10-26', 'SALE', 43.000, 'Customer sale Bill BILL-2026-1026'),
(56, 16, '2026-10-26', 'SALE', 15.000, 'Customer sale Bill BILL-2026-1026');

-- ==============================================================================
-- 7. INSERT INTERNAL USAGE, DAMAGE & RETURNS (Demonstrating shrinkage & usage)
-- ==============================================================================
INSERT INTO `Stock_Movement` (`movement_id`, `product_id`, `movement_date`, `movement_type`, `quantity`, `remarks`) VALUES
(57, 15, '2026-08-10', 'INTERNAL_USE', 1.000, 'Store maintenance & floor washing sanitizer use'),
(58, 9,  '2026-09-01', 'INTERNAL_USE', 1.000, 'Staff tea & pantry consumption for September'),
(59, 8,  '2026-10-18', 'DAMAGE',       2.000, 'Leaked dairy pouches spoiled during storage'),
(60, 5,  '2026-10-20', 'DAMAGE',       1.500, 'Torn bag water dampness during shelf restocking'),
(61, 1,  '2026-10-22', 'CUSTOMER_RETURN', 2.000, 'Customer returned unopened bag with bill receipt');

-- ==============================================================================
-- 8. INSERT VERIFIED INVENTORY BALANCES
-- Calculated strictly according to Algorithm 1:
-- Closing = Opening + Purchases + Cust.Returns - Sales - InternalUse - Damage - Supp.Returns
-- ==============================================================================
-- Product 1 (Basmati Rice):  100 (open) + 50 (pur1) + 40 (pur6) + 2 (return) - (5 + 10 + 20 + 15) (sales) = 142.000 kg. WAC = 79.03
-- Product 2 (Wheat Flour):   120 (open) + 80 (pur1) + 50 (pur6) - (25 + 40 + 30) (sales) = 155.000 kg. WAC = 36.50
-- Product 3 (Toor Dal):       50 (open) + 30 (pur1) - 35 (sale4) - 27 (sale6) = 18.000 kg (Reorder 25.000 -> LOW STOCK!) WAC = 130.00
-- Product 4 (Moong Dal):      40 (open) = 40.000 kg. WAC = 105.00
-- Product 5 (Sugar):          80 (open) + 50 (pur2) - 2 (sale1) - 15 (sale5) - 1.5 (damage) = 111.500 kg. WAC = 37.00
-- Product 6 (Salt):           60 (open) - 3 (sale2) = 57.000 packets. WAC = 20.00
-- Product 7 (Cooking Oil):    70 (open) + 40 (pur2) - 5 (sale2) - 10 (sale5) = 95.000 litres. WAC = 118.00
-- Product 8 (Milk):           30 (open) + 40 (pur3) - 35 (sale3) - 24.5 (sale5) - 2 (damage) = 8.500 litres (Reorder 20.000 -> LOW STOCK!) WAC = 52.00
-- Product 9 (Tea):            25 (open) - 1 (internal use) = 24.000 kg. WAC = 310.00
-- Product 10 (Coffee):        30 (open) + 20 (pur4) - 43 (sale6) = 7.000 packets (Reorder 15.000 -> LOW STOCK!) WAC = 95.00
-- Product 11 (Biscuits):      50 (open) + 40 (pur4) - 20 (sale3) = 70.000 packets. WAC = 27.00
-- Product 12 (Potato Chips):  40 (open) + 30 (pur4) - 15 (sale3) = 55.000 packets. WAC = 22.00
-- Product 13 (Turmeric):      20 (open) + 10 (pur2) = 30.000 kg. WAC = 190.00
-- Product 14 (Red Chilli):    15 (open) - 0.5 (sale1 on 2026-06-10) = 14.500 kg (Last sold > 140 days ago -> DEAD STOCK!) WAC = 260.00
-- Product 15 (Dishwash):      25 (open) + 20 (pur5) - 5 (sale4) - 1 (internal use) = 39.000 bottles. WAC = 80.00
-- Product 16 (Detergent):     30 (open) + 25 (pur5) - 15 (sale6) = 40.000 packets. WAC = 115.00
-- Product 17 (Soap):          60 (open) + 50 (pur5) - 25 (sale4) = 85.000 pieces. WAC = 28.00
-- Product 18 (Chia Seeds):    25 (open on 2026-06-01), 0 sales = 25.000 packets (> 150 days unsold -> DEAD STOCK!) WAC = 160.00

INSERT INTO `Inventory` (`product_id`, `quantity_available`, `average_cost`, `last_updated`) VALUES
(1,  142.000,  79.03, '2026-10-26 18:30:00'),
(2,  155.000,  36.50, '2026-10-26 18:30:00'),
(3,   18.000, 130.00, '2026-10-26 18:30:00'), -- LOW STOCK
(4,   40.000, 105.00, '2026-10-26 18:30:00'),
(5,  111.500,  37.00, '2026-10-26 18:30:00'),
(6,   57.000,  20.00, '2026-10-26 18:30:00'),
(7,   95.000, 118.00, '2026-10-26 18:30:00'),
(8,    8.500,  52.00, '2026-10-26 18:30:00'), -- LOW STOCK
(9,   24.000, 310.00, '2026-10-26 18:30:00'),
(10,   7.000,  95.00, '2026-10-26 18:30:00'), -- LOW STOCK
(11,  70.000,  27.00, '2026-10-26 18:30:00'),
(12,  55.000,  22.00, '2026-10-26 18:30:00'),
(13,  30.000, 190.00, '2026-10-26 18:30:00'),
(14,  14.500, 260.00, '2026-10-26 18:30:00'), -- DEAD STOCK (Last sale > 140 days)
(15,  39.000,  80.00, '2026-10-26 18:30:00'),
(16,  40.000, 115.00, '2026-10-26 18:30:00'),
(17,  85.000,  28.00, '2026-10-26 18:30:00'),
(18,  25.000, 160.00, '2026-10-26 18:30:00'); -- DEAD STOCK (Never sold > 150 days)

