# GROCERY STORE INVENTORY MANAGEMENT SYSTEM
## PHPMYADMIN SQL DEMONSTRATION & PRACTICAL GUIDE (PHASE 12)

This guide provides a step-by-step script for college students to demonstrate the project during DBMS practical examinations and teacher evaluations using **phpMyAdmin**.

---

## 1. PRE-DEMONSTRATION SETUP

1. Open your browser and navigate to phpMyAdmin:
   ```
   http://localhost/phpmyadmin/
   ```
2. In the left sidebar, click on the database **`grocery_inventory`**.
3. If the database is not yet loaded:
   - Click the **Import** tab at the top.
   - Click **Choose File** and select `c:\Users\naiks\Desktop\MY_INVENTORY\database\grocery_inventory.sql`.
   - Scroll down and click **Import** (or **Go**).
   - You should see green checkmarks confirming all tables, views, triggers, and sample records were created.

---

## 2. DEMONSTRATION SCRIPT FOR EXAMINERS

### STEP 1: Show the Nine Normalized Tables
1. Click on `grocery_inventory` in the sidebar.
2. The main screen displays the **9 Base Tables**:
   - `category`
   - `product`
   - `supplier`
   - `purchase`
   - `purchase_item`
   - `sales`
   - `sale_item`
   - `inventory`
   - `stock_movement`
3. Point out the **4 Views**:
   - `low_stock_products`
   - `dead_stock_products`
   - `product_inventory_status`
   - `monthly_transaction_summary`

---

### STEP 2: Show Primary Keys, Foreign Keys & Constraints
1. Click on the **`product`** table and select the **Structure** tab:
   - Point out `product_id` as the **Primary Key** (Gold Key icon).
   - Point out `category_id` as the **Foreign Key** (Silver Key icon).
   - Point out `selling_price` (`DECIMAL(10,2)`) and `reorder_level` (`DECIMAL(10,3)`).
2. Click **Relation view** (above the structure table):
   - Show the foreign key constraint `fk_product_category` linking `category_id` to `category(category_id)`.
   - Show `ON UPDATE CASCADE` and `ON DELETE RESTRICT`. Explain that `RESTRICT` prevents accidental deletion of categories that contain products.
3. Click on the **`inventory`** table Structure tab:
   - Explain that `product_id` is both the **Primary Key** and a **Foreign Key**, enforcing a strict **1:1 relationship** with the Product master catalog.

---

### STEP 3: Demonstrate Database Views (Algorithms 4 & 7)

#### A. Low Stock Products View (Algorithm 4)
1. Click on the view **`low_stock_products`** in the left sidebar (or run in SQL tab):
   ```sql
   SELECT * FROM Low_Stock_Products;
   ```
2. **What to explain to the examiner:**
   - *"Sir/Madam, this view dynamically queries `Inventory.quantity_available <= Product.reorder_level`."*
   - *"It automatically calculates `shortage_quantity` and flags products needing immediate wholesale replenishment (e.g., Cow Milk with 8.500 litres available against 20.000 litre reorder level)."*

#### B. Dead Stock Products View (Algorithm 7)
1. Click on the view **`dead_stock_products`** (or run in SQL tab):
   ```sql
   SELECT * FROM Dead_Stock_Products;
   ```
2. **What to explain to the examiner:**
   - *"This view implements Algorithm 7 to identify products tying up working capital with no customer sales for 90 days or more."*
   - *"For items never sold, it computes days since their first stock receipt (`OPENING`). For example, Organic Chia Seeds was received 150+ days ago and has 0 sales, correctly flagging it as Dead Stock."*

---

### STEP 4: Demonstrate Key SQL Concepts in the SQL Tab

Click on the **SQL** tab in phpMyAdmin and execute these queries:

#### 1. Multi-Table JOIN (Procurement Register)
```sql
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
JOIN `Supplier` s ON p.supplier_id = s.supplier_id
JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id
JOIN `Product` pr ON pi.product_id = pr.product_id
ORDER BY p.purchase_date DESC;
```
*Explain:* Combines 4 tables (`Purchase`, `Supplier`, `Purchase_Item`, `Product`) to generate an itemized procurement register.

#### 2. LEFT JOIN to Find Products with Zero Sales
```sql
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
```
*Explain:* Uses a `LEFT JOIN` and `IS NULL` to locate products in inventory that have never been purchased by any customer.

#### 3. GROUP BY and HAVING Clause
```sql
SELECT 
    c.category_name,
    COUNT(p.product_id) AS total_products,
    ROUND(SUM(i.quantity_available * i.average_cost), 2) AS category_valuation
FROM `Category` c
JOIN `Product` p ON c.category_id = p.category_id
JOIN `Inventory` i ON p.product_id = i.product_id
GROUP BY c.category_id, c.category_name
HAVING SUM(i.quantity_available * i.average_cost) > 5000.00
ORDER BY category_valuation DESC;
```
*Explain:* Groups by category and filters out aggregated groups whose total inventory capital is below ₹5,000 using `HAVING`.

#### 4. Algorithm 1: Stock Ledger Audit Reconciliation
```sql
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
    ) AS calculated_from_ledger,
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
FROM Product p
JOIN Inventory i ON p.product_id = i.product_id
JOIN Stock_Movement sm ON p.product_id = sm.product_id
GROUP BY p.product_id, p.product_name, i.quantity_available;
```
*Explain:* Demonstrates that physical inventory and the historical stock movements match with a variance of `0.000` for all products.

---

### STEP 5: Demonstrate Defensive Triggers (Negative Stock Prevention)

Execute this query in the SQL tab to prove storage-engine validation:

```sql
-- Attempt to set inventory quantity to a negative number:
UPDATE `Inventory` SET `quantity_available` = -10.000 WHERE `product_id` = 1;
```

**Result:**  
MariaDB immediately blocks the query and displays the custom error:
```
#45000 DBMS Transaction Rejected: Insufficient stock. Available inventory cannot become negative.
```
*Explain:* Even if an external application tries to bypass business checks, our MariaDB Trigger physically prevents negative stock.

---

### STEP 6: Demonstrate Web Application
1. Open `http://localhost/grocery_inventory/` in your browser.
2. Show the **Dashboard**: 8 metric cards, Low Stock alerts, and recent stock movements.
3. Show **Sales Billing** (`add_sale.php`): Add items, select products, and show that attempting to sell more than available stock is rejected.
4. Show **Purchases** (`add_purchase.php`): Show multi-item batch entry and automatic Weighted Average Cost update.
5. Show **Monthly Reports** (`monthly_report.php`): Show the 4-part financial breakdown (Stock Summary, Purchase Expenditure, Sales Revenue, COGS, and Gross Profit).

