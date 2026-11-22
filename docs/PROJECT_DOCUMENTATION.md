# Project Documentation
## Grocery Store Inventory Management System

### 1. Introduction
The Grocery Store Inventory Management System is a local database application that helps a small grocery store maintain product details, categories, supplier information, purchase entries, sales entries and current stock. It records every stock change and generates monthly operational and financial summaries.

### 2. Problem Statement
Manual grocery stock records can become inconsistent when purchases, sales, damage, internal usage and returns are recorded separately. The proposed system centralizes these records, updates inventory within database transactions, detects low/dead stock and calculates monthly sales and gross profit from stored data.

### 3. Objectives
- Maintain a structured catalogue of grocery products and categories.
- Store supplier and purchase information.
- Record internal sales with multiple products per bill.
- Keep current inventory consistent with a historical stock movement ledger.
- Detect products at/below reorder levels and products not sold for 90 days.
- Calculate purchase expenditure, sales revenue, COGS, gross profit and closing stock value.
- Demonstrate relational database concepts using SQL and phpMyAdmin.

### 4. Scope
The application is for one store and its administrator. It covers catalogue management, supplier records, purchases, sales, stock adjustments and reports. It is not a customer-facing shopping website and does not include online payments, cart, delivery or marketplace functions.

### 5. Functional Requirements
1. Add, edit, search and delete eligible products.
2. Add and edit categories and suppliers.
3. Record a supplier purchase with several product lines.
4. Increase inventory and recompute weighted average cost after purchases.
5. Record a sale containing multiple products.
6. Reject sales when the requested quantity exceeds available stock.
7. Record internal use, damage, supplier returns and customer returns.
8. Show stock movement history.
9. Display low-stock and dead-stock products.
10. Generate month-based inventory and financial reports.

### 6. Non-Functional Requirements
- Simple browser-based interface.
- Local XAMPP deployment.
- Prepared statements for database operations.
- Server-side input validation.
- InnoDB transactions for atomic purchases, sales and adjustments.
- Foreign keys and unique constraints for referential/data integrity.
- Decimal quantity and money fields for accurate grocery values.

### 7. Technology Stack
- XAMPP local server environment.
- phpMyAdmin for database administration and SQL demonstrations.
- PHP 8+ with PDO.
- MySQL-compatible database service bundled with XAMPP.
- HTML5, CSS3 and vanilla JavaScript.

### 8. System Architecture
Browser → PHP pages and shared helper functions → PDO connection → relational database. Purchase, sale and adjustment pages use transactions. Each stock-changing event updates the current inventory row and inserts a costed stock movement. SQL views provide low-stock and dead-stock lists.

### 9. Entity Relationship Description
- One category can contain many products.
- One supplier can have many purchases.
- One purchase contains many purchase items; each purchase item refers to one product.
- One sale contains many sale items; each sale item refers to one product.
- Each product has one inventory summary row.
- Each product can have many stock movement rows.

### 10. Relational Schema
- categories(**category_id**, category_name, description, created_at)
- products(**product_id**, product_name, category_id FK, selling_price, reorder_level, unit, created_at)
- suppliers(**supplier_id**, supplier_name, phone, email, address, created_at)
- purchases(**purchase_id**, supplier_id FK, purchase_date, invoice_number, created_at)
- purchase_items(**purchase_item_id**, purchase_id FK, product_id FK, quantity, unit_cost, expiry_date)
- sales(**sale_id**, sale_date, bill_number, created_at)
- sale_items(**sale_item_id**, sale_id FK, product_id FK, quantity, unit_selling_price, unit_cost_at_sale)
- inventory(**product_id** PK/FK, quantity_available, average_cost, last_updated)
- stock_movements(**movement_id**, product_id FK, movement_date, movement_type, quantity, unit_cost, remarks, reference_code)

### 11. Normalization
**1NF:** Each table has a primary key and each field stores one atomic value. Purchase and sale line items are stored as separate rows rather than comma-separated product lists.

**2NF:** Non-key attributes in line-item tables depend on their complete primary key. Transaction header data (supplier/date/invoice or sale date/bill) is stored separately from item data.

**3NF:** Category descriptions are stored in `categories`, supplier contact details in `suppliers`, and product category references are represented by `category_id`. Non-key attributes are not used to store unrelated transitive facts. `inventory` is intentionally a current operational summary; the movement ledger is the history used for audit and historical calculations.

### 12. Algorithms and Formulas
**Stock balance:** Closing quantity = Opening quantity + Purchases + Customer returns − Sales − Internal use − Damage − Supplier returns.

**Weighted average cost after purchase:** New average cost = (Old stock quantity × Old average cost + Purchased quantity × Purchase unit cost) / (Old stock quantity + Purchased quantity).

**Stock-out validation:** Lock the product inventory row; compare requested quantity with available quantity; reject and roll back if requested quantity is greater; otherwise decrement stock and write a movement row.

**Low stock:** quantity_available <= reorder_level.

**Dead stock:** quantity_available > 0 and last sale/reference receipt date is at least 90 days old. If no sale exists, the first OPENING/PURCHASE movement is used.

**Sales revenue:** SUM(quantity × unit_selling_price).

**COGS:** SUM(quantity × unit_cost_at_sale).

**Gross profit:** Sales revenue − COGS.

**Closing inventory value:** Costed stock inflows to period end − costed stock outflows to period end. The movement ledger stores unit cost for both inflows and outflows.

### 13. SQL Concepts Demonstrated
CREATE DATABASE, CREATE TABLE, PRIMARY KEY, FOREIGN KEY, UNIQUE, NOT NULL, CHECK, INSERT, UPDATE, DELETE, SELECT, WHERE, ORDER BY, DISTINCT, LIKE, BETWEEN, INNER JOIN, LEFT JOIN, SUM, COUNT, AVG, MAX, GROUP BY, HAVING, subqueries, views, triggers and transactions.

### 14. Testing Plan
Run the checklist in `docs/TEST_CASES.md`. Record actual results after importing and running the project on the target XAMPP computer. Do not mark any local database test as passed until it has been performed.

### 15. Limitations and Future Enhancements
- Single-store and local-only use.
- No user authentication or role-based permissions; restrict access to the local machine/network.
- Expiry date is recorded per purchase line but batch-level FEFO deduction is not implemented.
- No barcode reader, tax invoice, supplier payment ledger or customer account module.
- Future work can include role login, barcode scanning, batch/expiry tracking and multi-branch support.

### 16. Conclusion
The system demonstrates practical relational database design for a grocery store. It separates transaction headers from line items, maintains referential integrity, applies transaction-safe stock changes and uses a stock movement ledger for reporting and audit. The PHP interface is intentionally simple so the database operations remain the main focus.
