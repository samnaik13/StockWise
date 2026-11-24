# GROCERY STORE INVENTORY MANAGEMENT SYSTEM
## COMPREHENSIVE DBMS VIVA QUESTIONS & ANSWERS (PHASE 12)

This guide prepares students to confidently answer technical database questions during college DBMS project evaluations, practical exams, and viva voce.

---

### Q1: What is a Primary Key and how is it used in this project?
**Answer:**  
A **Primary Key (PK)** is a column or a set of columns that uniquely identifies each tuple (row) in a relation (table). It must contain unique values and cannot contain `NULL`.
- In our project, surrogate primary keys such as `category_id`, `product_id`, `supplier_id`, `purchase_id`, `sale_id`, and `movement_id` are defined using `INT AUTO_INCREMENT PRIMARY KEY`.
- In the `Inventory` table, `product_id` serves as both the Primary Key and Foreign Key, enforcing a strict 1:1 relationship with the `Product` table.

---

### Q2: What is a Foreign Key and what referential actions are configured?
**Answer:**  
A **Foreign Key (FK)** is an attribute in one table that references the primary key of another table, establishing a logical link and enforcing referential integrity.
- In this project, foreign keys like `Product(category_id)` reference `Category(category_id)`.
- We configured `ON UPDATE CASCADE` so that if an ID is updated, child records follow automatically.
- Crucially, we configured `ON DELETE RESTRICT` on products, categories, and suppliers. If an examiner asks why: *"We restrict deletion so that historical purchase invoices and customer sales bills are never corrupted or orphaned."*

---

### Q3: Explain Normalization and how your database achieves 1NF, 2NF, and 3NF.
**Answer:**  
**Normalization** is the systematic process of organizing data in a database to reduce data redundancy and eliminate insert, update, and delete anomalies.
1. **1NF (First Normal Form):** All attribute values are atomic (indivisible) and there are no repeating groups. In our project, an invoice does not store a comma-separated list of items; instead, line items are stored as individual rows in `Purchase_Item` and `Sale_Item`.
2. **2NF (Second Normal Form):** The table is in 1NF and all non-key attributes are fully functionally dependent on the entire primary key (no partial dependencies). In `Purchase_Item`, product catalog fields like `product_name` and `unit` are kept in `Product`, not in `Purchase_Item`.
3. **3NF (Third Normal Form):** The table is in 2NF and there are no transitive dependencies ($X \to Y$ and $Y \to Z$). For example, supplier address is stored in `Supplier`, not inside `Purchase`. The product category description is stored in `Category`, not inside `Product`.

---

### Q4: Why are separate `Purchase_Item` and `Sale_Item` tables necessary?
**Answer:**  
1. **To eliminate repeating groups (1NF):** A single wholesale invoice or counter bill can contain any number of grocery items (e.g., 5 kg rice, 1 litre oil, 2 packets salt). Having separate item tables allows an arbitrary number of items per transaction.
2. **Different entity granularity:** The transaction header (`Purchase` or `Sales`) stores transaction-level facts (bill number, date, supplier ID). The line item table stores product-level facts (quantity, price, unit cost).
3. **Historical snapshot preservation:** `Sale_Item` records `unit_selling_price` and `unit_cost_at_sale` at the exact instant the transaction occurred. If master catalog prices change later, past sales revenue and profit calculations remain frozen and 100% accurate.

---

### Q5: If you have a `Stock_Movement` ledger, why do you need an `Inventory` table?
**Answer:**  
1. **Query Performance ($O(1)$ vs $O(N)$):** When a cashier scans an item at the counter, the system must immediately check if `quantity_available >= requested_qty`. Looking up `Inventory.quantity_available` takes $O(1)$ constant time via an indexed primary key lookup. If we had to aggregate millions of historical ledger records on every POS scan, performance would degrade linearly ($O(N)$).
2. **Current State vs Audit Ledger:** `Inventory` represents current real-time stock; `Stock_Movement` represents the chronological audit trail.
3. **Reconciliation:** We can run an audit query asserting:
   $$\text{Recorded Inventory} = \sum \text{Stock In} - \sum \text{Stock Out}$$
   Our test suite proves that the variance is exactly `0.000` across all items.

---

### Q6: Why are quantities stored as `DECIMAL(10,3)` instead of `INT`?
**Answer:**  
In grocery stores, goods are sold in fractional units of weight and volume:
- $2.500\text{ kg}$ basmati rice
- $0.750\text{ kg}$ sugar
- $1.250\text{ litres}$ cooking oil
Storing quantities as integers would cause loss of precision or rounding errors. `DECIMAL(10,3)` guarantees precision down to 1 gram ($0.001\text{ kg}$) or 1 millilitre ($0.001\text{ litre}$).

---

### Q7: Why are all quantities in `Stock_Movement` stored as positive values?
**Answer:**  
All quantities in `Stock_Movement` have a `CHECK (quantity > 0)` constraint. Storing absolute magnitudes prevents operator entry errors (such as entering negative numbers for sales, which would result in double-negative addition bugs).  
The `movement_type` determines whether the quantity adds to or subtracts from inventory:
- **Additions (+):** `OPENING`, `PURCHASE`, `CUSTOMER_RETURN`
- **Subtractions (-):** `SALE`, `INTERNAL_USE`, `DAMAGE`, `SUPPLIER_RETURN`

---

### Q8: What is the Weighted Average Cost (WAC) Method and how is it implemented?
**Answer:**  
Wholesale grocery commodity prices fluctuate across purchase batches. When new stock arrives, the new unit cost is blended with existing stock:
$$\text{New Average Cost} = \frac{(\text{Current Qty} \times \text{Current Avg Cost}) + (\text{Purchased Qty} \times \text{New Unit Cost})}{\text{Current Qty} + \text{Purchased Qty}}$$
- If previous stock is zero, the new average cost equals the purchase unit cost.
- This weighted average cost is maintained in `Inventory.average_cost` and snapshotted into `Sale_Item.unit_cost_at_sale` whenever a customer sale occurs.

---

### Q9: What is the difference between Purchase Expenditure, Cost of Goods Sold (COGS), and Gross Profit?
**Answer:**  
- **Sales Revenue:** The total gross money collected from customers ($\sum \text{Quantity Sold} \times \text{Selling Price}$).
- **Purchase Expenditure:** Total cash spent buying wholesale inventory from suppliers during a month.
- **Cost of Goods Sold (COGS):** The purchase cost of **only the items that were actually sold** ($\sum \text{Quantity Sold} \times \text{Historical Unit Cost at Sale}$). Unsold inventory remains an asset in the warehouse.
- **Gross Profit:**
  $$\text{Gross Profit} = \text{Sales Revenue} - \text{COGS}$$
  *Note:* You cannot calculate profit simply by subtracting total monthly purchases from total monthly sales, because purchased stock may remain unsold on store shelves.

---

### Q10: How does Algorithm 4 (Low Stock Detection) work in your database?
**Answer:**  
Algorithm 4 identifies items that need immediate restocking:
$$\text{Condition: } \text{Inventory.quantity\_available} \le \text{Product.reorder\_level}$$
We implemented this directly as a SQL View named `Low_Stock_Products`. When queried, it computes the replenishment shortage:
$$\text{shortage\_quantity} = \text{reorder\_level} - \text{quantity\_available}$$
and categorizes each product as `REORDER NEEDED` or `OUT OF STOCK`.

---

### Q11: How does Algorithm 7 (Dead Stock Detection) work?
**Answer:**  
Algorithm 7 identifies slow-moving or stagnant inventory tying up shop capital:
$$\text{Dead Stock: } \text{quantity\_available} > 0 \text{ AND } \text{Days Without Sale} \ge 90$$
- If a product has past sales, days without sale = $\text{DATEDIFF}(\text{CURRENT\_DATE}, \text{MAX}(\text{sale\_date}))$.
- If a product was never sold, days without sale = $\text{DATEDIFF}(\text{CURRENT\_DATE}, \text{MIN}(\text{movement\_date}))$.
- Products with zero available stock are **never** classified as dead stock, because zero stock represents an out-of-stock item, not stagnant capital.
- Implemented as the `Dead_Stock_Products` view.

---

### Q12: Explain how ACID Transactions are used in Purchases and Sales.
**Answer:**  
An **ACID transaction** ensures that multiple related SQL operations either all succeed together or all fail together without partial state changes:
- **Atomicity:** `START TRANSACTION` ... `COMMIT` / `ROLLBACK`. If inserting a sale item succeeds but deducting inventory fails, everything rolls back.
- **Consistency:** Stock availability is validated (`SELECT ... FOR UPDATE`); stock cannot become negative.
- **Isolation:** Concurrent counter billing operations cannot read uncommitted stock deductions.
- **Durability:** Once committed, records are permanently written to MariaDB storage.

---

### Q13: What is the role of Database Triggers in this application?
**Answer:**  
In our design, business transactions are managed by PHP PDO with ACID transactions, while database triggers act as **defensive storage-layer constraints** (total of 4 triggers):
1. `trg_validate_inventory_update`: Fired `BEFORE UPDATE ON Inventory`. If any query attempts to set `quantity_available < 0` or `average_cost < 0`, it rejects the transaction with:
   ```sql
   SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'DBMS Transaction Rejected: Insufficient stock. Available inventory cannot become negative.';
   ```
2. `trg_validate_inventory_insert`: Fired `BEFORE INSERT ON Inventory`. Prevents negative initial stock quantity or negative average cost.
3. `trg_validate_product_insert`: Fired `BEFORE INSERT ON Product`. Guarantees catalog integrity by rejecting products with `selling_price <= 0` or negative `reorder_level < 0`.
4. `trg_validate_stock_movement_insert`: Fired `BEFORE INSERT ON Stock_Movement`. Rejects quantities $\le 0$ and verifies valid `movement_type` ENUM values.

---

### Q14: Explain the difference between INNER JOIN and LEFT JOIN with examples from your project.
**Answer:**  
- **INNER JOIN:** Returns only rows where there is a match in both tables.
  *Example:* `Product INNER JOIN Category ON Product.category_id = Category.category_id` returns only products that have a valid category.
- **LEFT JOIN:** Returns all rows from the left table and matched rows from the right table (or `NULL` if no match exists).
  *Example:* Finding products that have **never been sold**:
  ```sql
  SELECT p.product_name 
  FROM Product p 
  LEFT JOIN Sale_Item si ON p.product_id = si.product_id 
  WHERE si.sale_item_id IS NULL;
  ```

---

### Q15: What is the difference between WHERE and HAVING in SQL?
**Answer:**  
- **WHERE clause:** Filters individual rows **before** any aggregation takes place.
  *Example:* `WHERE selling_price >= 100.00`
- **HAVING clause:** Filters aggregated groups **after** the `GROUP BY` clause executes.
  *Example:* `GROUP BY category_id HAVING SUM(quantity_available * average_cost) > 5000.00` filters only categories whose total stock value exceeds ₹5,000.

