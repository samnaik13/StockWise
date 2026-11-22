-- ==============================================================================
-- GROCERY STORE INVENTORY MANAGEMENT SYSTEM
-- SCRIPT: views.sql
-- PURPOSE: Create database views for Low Stock, Dead Stock, Product Status,
--          and Monthly Summaries.
-- DBMS: MariaDB / MySQL (phpMyAdmin Compatible)
-- ==============================================================================

USE `grocery_inventory`;

DROP VIEW IF EXISTS `Low_Stock_Products`;
DROP VIEW IF EXISTS `Dead_Stock_Products`;
DROP VIEW IF EXISTS `Product_Inventory_Status`;
DROP VIEW IF EXISTS `Monthly_Transaction_Summary`;

-- ==============================================================================
-- VIEW 1: Low_Stock_Products (Algorithm 4)
-- PURPOSE: Identify grocery items whose available quantity has fallen below or
--          equal to their designated reorder threshold.
-- ==============================================================================
CREATE VIEW `Low_Stock_Products` AS
SELECT 
    p.product_id,
    p.product_name,
    c.category_name,
    i.quantity_available,
    p.reorder_level,
    p.unit,
    ROUND(p.reorder_level - i.quantity_available, 3) AS shortage_quantity,
    CASE 
        WHEN i.quantity_available = 0 THEN 'OUT OF STOCK'
        ELSE 'REORDER NEEDED'
    END AS stock_condition
FROM `Product` p
JOIN `Category` c ON p.category_id = c.category_id
JOIN `Inventory` i ON p.product_id = i.product_id
WHERE i.quantity_available <= p.reorder_level
ORDER BY shortage_quantity DESC, i.quantity_available ASC;

-- ==============================================================================
-- VIEW 2: Dead_Stock_Products (Algorithm 7)
-- PURPOSE: Identify items with positive stock (quantity > 0) that have not been
--          sold for 90 days or more, or items that have never been sold since receipt.
-- ==============================================================================
CREATE VIEW `Dead_Stock_Products` AS
SELECT 
    p.product_id,
    p.product_name,
    c.category_name,
    i.quantity_available,
    p.unit,
    i.average_cost,
    ROUND(i.quantity_available * i.average_cost, 2) AS tied_up_capital,
    sales_info.last_sale_date,
    stock_info.first_stock_date,
    COALESCE(sales_info.last_sale_date, stock_info.first_stock_date) AS reference_date,
    DATEDIFF(CURRENT_DATE(), COALESCE(sales_info.last_sale_date, stock_info.first_stock_date)) AS days_without_sale,
    CASE 
        WHEN sales_info.last_sale_date IS NULL THEN 'Never Sold Since Initial Receipt'
        ELSE 'No Customer Sales in 90+ Days'
    END AS classification_reason
FROM `Product` p
JOIN `Category` c ON p.category_id = c.category_id
JOIN `Inventory` i ON p.product_id = i.product_id
-- Retrieve the latest customer sale date for each product
LEFT JOIN (
    SELECT 
        si.product_id, 
        MAX(s.sale_date) AS last_sale_date
    FROM `Sale_Item` si
    JOIN `Sales` s ON si.sale_id = s.sale_id
    GROUP BY si.product_id
) sales_info ON p.product_id = sales_info.product_id
-- Retrieve the earliest stock receipt date (OPENING or PURCHASE)
LEFT JOIN (
    SELECT 
        product_id, 
        MIN(movement_date) AS first_stock_date
    FROM `Stock_Movement`
    WHERE movement_type IN ('OPENING', 'PURCHASE')
    GROUP BY product_id
) stock_info ON p.product_id = stock_info.product_id
WHERE i.quantity_available > 0
  AND (
      (sales_info.last_sale_date IS NOT NULL AND DATEDIFF(CURRENT_DATE(), sales_info.last_sale_date) >= 90)
      OR 
      (sales_info.last_sale_date IS NULL AND stock_info.first_stock_date IS NOT NULL AND DATEDIFF(CURRENT_DATE(), stock_info.first_stock_date) >= 90)
  )
ORDER BY days_without_sale DESC;

-- ==============================================================================
-- VIEW 3: Product_Inventory_Status
-- PURPOSE: Master consolidated inventory overview showing product details, category,
--          current stock, unit, average cost, total inventory valuation, and status.
-- ==============================================================================
CREATE VIEW `Product_Inventory_Status` AS
SELECT 
    p.product_id,
    p.product_name,
    c.category_name,
    p.unit,
    p.selling_price,
    i.average_cost,
    i.quantity_available,
    p.reorder_level,
    ROUND(i.quantity_available * i.average_cost, 2) AS current_inventory_value,
    CASE 
        WHEN i.quantity_available = 0 THEN 'Out of Stock'
        WHEN i.quantity_available <= p.reorder_level THEN 'Low Stock'
        ELSE 'Adequate'
    END AS stock_status,
    i.last_updated
FROM `Product` p
JOIN `Category` c ON p.category_id = c.category_id
JOIN `Inventory` i ON p.product_id = i.product_id;

-- ==============================================================================
-- VIEW 4: Monthly_Transaction_Summary
-- PURPOSE: Monthly aggregated breakdown of purchase orders and customer sales bills.
-- ==============================================================================
CREATE VIEW `Monthly_Transaction_Summary` AS
SELECT 
    DATE_FORMAT(s.sale_date, '%Y-%m') AS transaction_month,
    'SALES' AS transaction_type,
    COUNT(DISTINCT s.sale_id) AS total_transactions,
    ROUND(SUM(si.quantity * si.unit_selling_price), 2) AS total_financial_value
FROM `Sales` s
JOIN `Sale_Item` si ON s.sale_id = si.sale_id
GROUP BY DATE_FORMAT(s.sale_date, '%Y-%m')
UNION ALL
SELECT 
    DATE_FORMAT(p.purchase_date, '%Y-%m') AS transaction_month,
    'PURCHASES' AS transaction_type,
    COUNT(DISTINCT p.purchase_id) AS total_transactions,
    ROUND(SUM(pi.quantity * pi.unit_cost), 2) AS total_financial_value
FROM `Purchase` p
JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id
GROUP BY DATE_FORMAT(p.purchase_date, '%Y-%m')
ORDER BY transaction_month DESC, transaction_type ASC;

