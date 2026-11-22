USE grocery_inventory;

-- Demonstration A: show nine main tables and keys
SHOW TABLES;
SHOW CREATE TABLE purchase_items;
SHOW CREATE TABLE sale_items;
SHOW CREATE TABLE stock_movements;

-- Demonstration B: purchase-related records
SELECT * FROM purchases ORDER BY purchase_id DESC;
SELECT * FROM purchase_items ORDER BY purchase_item_id DESC;
SELECT * FROM inventory WHERE product_id=1;
SELECT * FROM stock_movements WHERE reference_code='PUR-2026-001';

-- Demonstration C: sale-related records and the saved cost at the time of sale
SELECT * FROM sales ORDER BY sale_id DESC;
SELECT * FROM sale_items ORDER BY sale_item_id DESC;
SELECT * FROM inventory WHERE product_id=1;
SELECT * FROM stock_movements WHERE reference_code='BILL-2026-002';

-- Demonstration D: views
SELECT * FROM Low_Stock_Products;
SELECT * FROM Dead_Stock_Products;

-- Demonstration E: safe transaction rollback. The +1 is undone.
START TRANSACTION;
UPDATE inventory SET quantity_available=quantity_available+1 WHERE product_id=1;
SELECT product_id,quantity_available FROM inventory WHERE product_id=1;
ROLLBACK;
SELECT product_id,quantity_available FROM inventory WHERE product_id=1;

-- Demonstration F: verify that current stock agrees with movement history.
-- Expected difference is zero for correctly posted stock transactions.
SELECT i.product_id,p.product_name,i.quantity_available,
 SUM(CASE WHEN sm.movement_type IN ('OPENING','PURCHASE','CUSTOMER_RETURN') THEN sm.quantity ELSE -sm.quantity END) AS movement_calculated_stock,
 i.quantity_available-SUM(CASE WHEN sm.movement_type IN ('OPENING','PURCHASE','CUSTOMER_RETURN') THEN sm.quantity ELSE -sm.quantity END) AS difference
FROM inventory i JOIN products p ON p.product_id=i.product_id
JOIN stock_movements sm ON sm.product_id=i.product_id
GROUP BY i.product_id,p.product_name,i.quantity_available;
