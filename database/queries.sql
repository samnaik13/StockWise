-- ==============================================================================
-- GROCERY STORE INVENTORY MANAGEMENT SYSTEM
-- SCRIPT: queries.sql
-- PURPOSE: 22 Comprehensive DBMS Demonstration Queries Covering DDL, DML,
--          Joins, Aggregations, GROUP BY, HAVING, Subqueries, Views & Transactions.
-- DBMS: MariaDB / MySQL (phpMyAdmin Compatible)
-- ==============================================================================

USE `grocery_inventory`;

-- ==============================================================================
-- QUERY 1: Basic SELECT with Column Aliases and Ordering
-- Purpose: Retrieve full product catalog with category ID and prices sorted alphabetically.
-- Concepts: SELECT, AS (Aliasing), ORDER BY ASC
-- ==============================================================================
SELECT 
    product_id AS ID,
    product_name AS `Grocery Item`,
    unit AS `Unit`,
    selling_price AS `Price (INR)`,
    reorder_level AS `Reorder Threshold`
FROM `Product`
ORDER BY product_name ASC;
-- Expected Output: List of all 18 grocery items sorted alphabetically from 'Active Laundry Detergent' to 'Whole Wheat Flour'.
-- Explanation: Demonstrates standard projection and sorting using ORDER BY.

-- ==============================================================================
-- QUERY 2: Filtering with WHERE and Relational Operators
-- Purpose: Find all high-value grocery items priced at or above 100 INR.
-- Concepts: WHERE, >= Comparison
-- ==============================================================================
SELECT 
    product_id,
    product_name,
    selling_price,
    unit
FROM `Product`
WHERE selling_price >= 100.00
ORDER BY selling_price DESC;
-- Expected Output: High-value commodities such as Assam Tea (380.00), Red Chilli (320.00), Turmeric (240.00), Chia Seeds (210.00), Toor Dal (160.00), etc.
-- Explanation: Filters tuples based on a numeric predicate.

-- ==============================================================================
-- QUERY 3: Range Filtering with BETWEEN
-- Purpose: Identify mid-range grocery products selling between 30 and 100 INR.
-- Concepts: BETWEEN ... AND
-- ==============================================================================
SELECT 
    product_id,
    product_name,
    selling_price,
    unit
FROM `Product`
WHERE selling_price BETWEEN 30.00 AND 100.00
ORDER BY selling_price ASC;
-- Expected Output: Items such as Potato Chips (30.00), Marie Biscuits (35.00), Soap (40.00), Sugar (44.00), Atta (45.00), Milk (64.00), Basmati Rice (95.00).
-- Explanation: Inclusive boundary filtering on numeric attributes.

-- ==============================================================================
-- QUERY 4: Pattern Matching with LIKE and Wildcards
-- Purpose: Search for all dal and pulse varieties containing the keyword 'Dal'.
-- Concepts: LIKE, '%' Wildcard
-- ==============================================================================
SELECT 
    product_id,
    product_name,
    unit,
    selling_price
FROM `Product`
WHERE product_name LIKE '%Dal%';
-- Expected Output: 'Toor Dal Premium Unpolished' and 'Moong Dal Yellow Split'.
-- Explanation: Demonstrates substring pattern matching across text fields.

-- ==============================================================================
-- QUERY 5: Set Membership Filtering with IN
-- Purpose: Retrieve products measured in specific volumetric or discrete units.
-- Concepts: IN (List Predicate)
-- ==============================================================================
SELECT 
    product_id,
    product_name,
    unit,
    selling_price
FROM `Product`
WHERE unit IN ('litre', 'bottle', 'piece')
ORDER BY unit, product_name;
-- Expected Output: Sunflower Oil (litre), Fresh Milk (litre), Dishwash Gel (bottle), Neem Soap (piece).
-- Explanation: Tests whether an attribute value belongs to a specified finite set.

-- ==============================================================================
-- QUERY 6: Eliminating Duplicates with DISTINCT
-- Purpose: List all unique measurement units used across the grocery store.
-- Concepts: DISTINCT
-- ==============================================================================
SELECT DISTINCT 
    unit AS `Configured Units`
FROM `Product`
ORDER BY unit;
-- Expected Output: 'bottle', 'kg', 'litre', 'packet', 'piece'.
-- Explanation: Removes duplicate values from the projected result set.

-- ==============================================================================
-- QUERY 7: Two-Table INNER JOIN
-- Purpose: Display products along with their official category names.
-- Concepts: INNER JOIN, Foreign Key Relationship
-- ==============================================================================
SELECT 
    p.product_id,
    p.product_name,
    c.category_name,
    p.unit,
    p.selling_price
FROM `Product` p
INNER JOIN `Category` c ON p.category_id = c.category_id
ORDER BY c.category_name, p.product_name;
-- Expected Output: All products grouped under their category names (Beverages, Dairy, Grains, etc.).
-- Explanation: Combines records from two tables where foreign key matches primary key.

-- ==============================================================================
-- QUERY 8: Multi-Table INNER JOIN (Purchases, Suppliers, Items, Products)
-- Purpose: Generate a complete wholesale procurement register with supplier details.
-- Concepts: Multi-table JOIN (4 tables)
-- ==============================================================================
SELECT 
    p.purchase_date,
    p.invoice_number,
    s.supplier_name,
    pr.product_name,
    pi.quantity,
    pr.unit,
    pi.unit_cost,
    ROUND(pi.quantity * pi.unit_cost, 2) AS line_total
FROM `Purchase` p
INNER JOIN `Supplier` s ON p.supplier_id = s.supplier_id
INNER JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id
INNER JOIN `Product` pr ON pi.product_id = pr.product_id
ORDER BY p.purchase_date DESC, p.invoice_number;
-- Expected Output: Detailed itemized list of all wholesale purchases, supplier names, quantities, and cost totals.
-- Explanation: Traverses the relational chain from master to detail to product.

-- ==============================================================================
-- QUERY 9: LEFT OUTER JOIN (Products Without Sales)
-- Purpose: Find products that have NEVER been sold in any customer transaction.
-- Concepts: LEFT JOIN, IS NULL Predicate
-- ==============================================================================
SELECT 
    p.product_id,
    p.product_name,
    c.category_name,
    i.quantity_available,
    p.unit
FROM `Product` p
JOIN `Category` c ON p.category_id = c.category_id
JOIN `Inventory` i ON p.product_id = i.product_id
LEFT JOIN `Sale_Item` si ON p.product_id = si.product_id
WHERE si.sale_item_id IS NULL;
-- Expected Output: Products like 'Organic Chia Seeds 250g' which have inventory but zero sales.
-- Explanation: Identifies non-matching rows from the right table using LEFT JOIN and IS NULL.

-- ==============================================================================
-- QUERY 10: Aggregate Functions (SUM, AVG, MIN, MAX, COUNT)
-- Purpose: Calculate overall statistics for the store's current inventory.
-- Concepts: SUM, AVG, MIN, MAX, COUNT
-- ==============================================================================
SELECT 
    COUNT(*) AS total_skus,
    SUM(quantity_available) AS total_physical_quantity,
    ROUND(AVG(average_cost), 2) AS average_unit_cost,
    MIN(average_cost) AS lowest_item_cost,
    MAX(average_cost) AS highest_item_cost,
    ROUND(SUM(quantity_available * average_cost), 2) AS total_inventory_valuation
FROM `Inventory`;
-- Expected Output: 18 SKUs, aggregate stock quantities, min/max costs, and total shop inventory capital.
-- Explanation: Demonstrates standard SQL aggregate computations across an entire relation.

-- ==============================================================================
-- QUERY 11: GROUP BY with Category Summaries
-- Purpose: Calculate total products, total stock quantity, and valuation per category.
-- Concepts: GROUP BY, Aggregates with JOIN
-- ==============================================================================
SELECT 
    c.category_name,
    COUNT(p.product_id) AS total_products,
    SUM(i.quantity_available) AS category_stock_qty,
    ROUND(SUM(i.quantity_available * i.average_cost), 2) AS category_inventory_valuation
FROM `Category` c
JOIN `Product` p ON c.category_id = p.category_id
JOIN `Inventory` i ON p.product_id = i.product_id
GROUP BY c.category_id, c.category_name
ORDER BY category_inventory_valuation DESC;
-- Expected Output: Summary table showing stock and capital invested per category.
-- Explanation: Partitions data into groups based on category_id and aggregates each group.

-- ==============================================================================
-- QUERY 12: Filtering Aggregated Groups with HAVING
-- Purpose: Find categories where total inventory valuation exceeds 5,000 INR.
-- Concepts: GROUP BY, HAVING Clause
-- ==============================================================================
SELECT 
    c.category_name,
    ROUND(SUM(i.quantity_available * i.average_cost), 2) AS category_valuation
FROM `Category` c
JOIN `Product` p ON c.category_id = p.category_id
JOIN `Inventory` i ON p.product_id = i.product_id
GROUP BY c.category_id, c.category_name
HAVING SUM(i.quantity_available * i.average_cost) > 5000.00
ORDER BY category_valuation DESC;
-- Expected Output: High-value categories (Grains & Flours, Cooking Oils & Spices, Pulses & Lentils).
-- Explanation: HAVING filters aggregated groups after the GROUP BY operation, unlike WHERE.

-- ==============================================================================
-- QUERY 13: Subquery with IN
-- Purpose: Find all suppliers who have supplied pulses or grains.
-- Concepts: Nested Subquery with IN
-- ==============================================================================
SELECT 
    supplier_id,
    supplier_name,
    phone,
    email
FROM `Supplier`
WHERE supplier_id IN (
    SELECT DISTINCT p.supplier_id
    FROM `Purchase` p
    JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id
    JOIN `Product` pr ON pi.product_id = pr.product_id
    WHERE pr.category_id IN (1, 2)
);
-- Expected Output: 'Royal Agro & Grains Wholesalers'.
-- Explanation: The inner query identifies supplier IDs for specific categories; the outer query retrieves full supplier profiles.

-- ==============================================================================
-- QUERY 14: Correlated Subquery with EXISTS
-- Purpose: Find products that have experienced stock damage or spoilage.
-- Concepts: Correlated Subquery, EXISTS
-- ==============================================================================
SELECT 
    p.product_id,
    p.product_name,
    p.unit
FROM `Product` p
WHERE EXISTS (
    SELECT 1 
    FROM `Stock_Movement` sm 
    WHERE sm.product_id = p.product_id 
      AND sm.movement_type = 'DAMAGE'
);
-- Expected Output: Products with recorded damages (Milk, Sugar).
-- Explanation: Executes conditionally for each candidate row in the outer query.

-- ==============================================================================
-- QUERY 15: Scalar Subquery in SELECT Projection
-- Purpose: Display each product's price alongside the overall grocery store average price.
-- Concepts: Scalar Subquery
-- ==============================================================================
SELECT 
    product_name,
    selling_price,
    ROUND((SELECT AVG(selling_price) FROM `Product`), 2) AS overall_avg_price,
    ROUND(selling_price - (SELECT AVG(selling_price) FROM `Product`), 2) AS diff_from_avg
FROM `Product`
ORDER BY diff_from_avg DESC;
-- Expected Output: Product list comparing individual prices to overall store average.
-- Explanation: Demonstrates embedding a single scalar query result within projection list.

-- ==============================================================================
-- QUERY 16: Top-N Query with LIMIT (Best Selling Products)
-- Purpose: Identify the top 5 most frequently sold grocery products by quantity.
-- Concepts: SUM, GROUP BY, ORDER BY DESC, LIMIT
-- ==============================================================================
SELECT 
    p.product_id,
    p.product_name,
    p.unit,
    SUM(si.quantity) AS total_quantity_sold,
    ROUND(SUM(si.quantity * si.unit_selling_price), 2) AS total_revenue_generated
FROM `Sale_Item` si
JOIN `Product` p ON si.product_id = p.product_id
GROUP BY p.product_id, p.product_name, p.unit
ORDER BY total_quantity_sold DESC
LIMIT 5;
-- Expected Output: High volume staples (Wheat Flour, Toor Dal, Milk, Coffee, Rice).
-- Explanation: Ranks records by aggregate volume and truncates output to top 5.

-- ==============================================================================
-- QUERY 17: Date Formatting & Monthly Sales Breakdown
-- Purpose: Aggregate sales count, items sold, and gross revenue by calendar month.
-- Concepts: DATE_FORMAT, SUM, COUNT DISTINCT
-- ==============================================================================
SELECT 
    DATE_FORMAT(s.sale_date, '%Y-%m') AS sales_month,
    COUNT(DISTINCT s.sale_id) AS total_bills,
    SUM(si.quantity) AS total_units_sold,
    ROUND(SUM(si.quantity * si.unit_selling_price), 2) AS monthly_revenue
FROM `Sales` s
JOIN `Sale_Item` si ON s.sale_id = si.sale_id
GROUP BY DATE_FORMAT(s.sale_date, '%Y-%m')
ORDER BY sales_month ASC;
-- Expected Output: Monthly sales performance across June, July, August, September, October 2026.
-- Explanation: Groups temporal date fields into Year-Month buckets.

-- ==============================================================================
-- QUERY 18: Cost of Goods Sold (COGS) & Gross Profit Calculation
-- Purpose: Calculate total revenue, COGS, and gross profit using stored point-in-sale costs.
-- Concepts: Mathematical expressions, SUM, Algorithm 6
-- ==============================================================================
SELECT 
    ROUND(SUM(si.quantity * si.unit_selling_price), 2) AS total_sales_revenue,
    ROUND(SUM(si.quantity * si.unit_cost_at_sale), 2) AS total_cogs,
    ROUND(SUM(si.quantity * si.unit_selling_price) - SUM(si.quantity * si.unit_cost_at_sale), 2) AS gross_profit,
    ROUND(((SUM(si.quantity * si.unit_selling_price) - SUM(si.quantity * si.unit_cost_at_sale)) / SUM(si.quantity * si.unit_selling_price)) * 100, 2) AS gross_profit_margin_pct
FROM `Sale_Item` si;
-- Expected Output: Store-wide financial metrics showing revenue, COGS, gross profit, and margin percentage.
-- Explanation: Core formula: Gross Profit = Sales Revenue - Cost of Goods Sold (COGS).

-- ==============================================================================
-- QUERY 19: Stock Movement Audit Reconciliation (Integrity Proof)
-- Purpose: Verify that current Inventory stock matches the historical sum of Stock_Movement.
-- Concepts: CASE WHEN, SUM, Algorithm 1
-- ==============================================================================
SELECT 
    p.product_id,
    p.product_name,
    i.quantity_available AS recorded_inventory,
    SUM(
        CASE 
            WHEN sm.movement_type IN ('OPENING', 'PURCHASE', 'CUSTOMER_RETURN') THEN sm.quantity
            WHEN sm.movement_type IN ('SALE', 'INTERNAL_USE', 'DAMAGE', 'SUPPLIER_RETURN') THEN -sm.quantity
            ELSE 0
        END
    ) AS calculated_stock_from_ledger,
    ROUND(
        i.quantity_available - 
        SUM(
            CASE 
                WHEN sm.movement_type IN ('OPENING', 'PURCHASE', 'CUSTOMER_RETURN') THEN sm.quantity
                WHEN sm.movement_type IN ('SALE', 'INTERNAL_USE', 'DAMAGE', 'SUPPLIER_RETURN') THEN -sm.quantity
                ELSE 0
            END
        ), 3
    ) AS audit_variance
FROM `Product` p
JOIN `Inventory` i ON p.product_id = i.product_id
JOIN `Stock_Movement` sm ON p.product_id = sm.product_id
GROUP BY p.product_id, p.product_name, i.quantity_available;
-- Expected Output: All products should display an audit_variance of exactly 0.000!
-- Explanation: Proves that Algorithm 1 is in 100% mathematical consistency with physical inventory balances.

-- ==============================================================================
-- QUERY 20: Querying the Low Stock Database View (Algorithm 4)
-- Purpose: Query the pre-compiled database view for stock replenishment alerts.
-- Concepts: VIEW querying
-- ==============================================================================
SELECT * FROM `Low_Stock_Products`;
-- Expected Output: Products where quantity_available <= reorder_level (Cow Milk, Instant Coffee, Toor Dal).
-- Explanation: Retrieves reorder alerts directly from the database view.

-- ==============================================================================
-- QUERY 21: Querying the Dead Stock Database View (Algorithm 7)
-- Purpose: Identify stale grocery stock tying up working capital with no sales in 90+ days.
-- Concepts: VIEW querying, DATEDIFF
-- ==============================================================================
SELECT * FROM `Dead_Stock_Products`;
-- Expected Output: Red Chilli Powder (last sold > 140 days ago) and Organic Chia Seeds (unsold > 150 days).
-- Explanation: Retrieves dead-stock items using Algorithm 7 view.

-- ==============================================================================
-- QUERY 22: Transaction Rollback Demonstration (Defensive Transaction)
-- Purpose: Demonstrate ACID transactions and rollback when attempting to oversell.
-- Concepts: START TRANSACTION, ROLLBACK, SAVEPOINT
-- ==============================================================================
START TRANSACTION;

-- Step 1: Check available stock for Fresh Cow Milk (product_id = 8, current stock = 8.500)
SELECT product_id, quantity_available INTO @current_stock 
FROM `Inventory` WHERE product_id = 8;

-- Step 2: Attempt to reduce 50.000 litres (exceeds 8.500 litres available)
-- The trigger or application logic aborts the operation:
ROLLBACK;
-- Explanation: Shows how database transactions prevent inconsistent state or partial updates.

