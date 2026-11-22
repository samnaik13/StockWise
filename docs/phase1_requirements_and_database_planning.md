# PHASE 1: REQUIREMENTS ANALYSIS AND DATABASE PLANNING
**Project Title:** Grocery Store Inventory Management System  
**Course:** Database Management Systems (DBMS)  
**Target Database:** `grocery_inventory`  
**Execution Environment:** XAMPP (Apache + MariaDB 10.4.32 + PHP 8.2.12 + phpMyAdmin)  
**Workspace:** `C:\xampp\htdocs\grocery_inventory`

---

## 1. ENVIRONMENT INSPECTION & DIAGNOSTICS

Before proceeding with the database design, a complete environment verification was conducted on the host machine:

| Component | Detected Specification | Verification Status |
| :--- | :--- | :--- |
| **Operating System** | Windows 11 / 64-bit | Verified |
| **XAMPP Root** | `C:\xampp` | Confirmed Present |
| **PHP Engine** | PHP 8.2.12 (cli / apache) | Active |
| **Database Server** | MariaDB 10.4.32 (XAMPP Bundled) | Active via default port 3306 |
| **Database Admin** | phpMyAdmin (`http://localhost/phpmyadmin/`) | Bundled |
| **Project Location** | `C:\xampp\htdocs\grocery_inventory` | Initialized |
| **Local Web Access** | `http://localhost/grocery_inventory/` | Target URL |

---

## 2. PROBLEM STATEMENT & BUSINESS DOMAIN ANALYSIS

A grocery store operates under distinctive retail and supply chain constraints that differentiate it from generic retail stores:

1. **Fractional Quantities & Unit Heterogeneity:**
   - Groceries are sold across various units of measurement: mass (`kg`, `g`), volume (`litre`, `ml`), and discrete counts (`packet`, `piece`, `bottle`, `box`, `dozen`).
   - Customers frequently purchase fractional quantities (e.g., $1.750\text{ kg}$ sugar, $0.500\text{ litre}$ mustard oil, $2.500\text{ kg}$ basmati rice).
   - Therefore, integer quantity columns are fundamentally inadequate; all quantity fields must use `DECIMAL(10,3)` to ensure milligram and millilitre precision.

2. **Frequent Supplier Price Volatility:**
   - Agricultural commodity prices (pulses, grains, edible oils, spices) fluctuate frequently across purchase batches.
   - The store administrator must record the exact `unit_cost` for every purchase transaction.
   - For accounting and inventory valuation, the system must employ the **Weighted Average Cost (WAC)** method:
     $$\text{Average Cost} = \frac{\text{Previous Stock Value} + \text{New Purchase Value}}{\text{Previous Stock Quantity} + \text{Purchased Quantity}}$$

3. **Multi-Item Transactions (Stock-In and Stock-Out):**
   - A single purchase order from a wholesaler contains multiple grocery commodities.
   - A single sales bill generated at the counter contains multiple items purchased by a customer.
   - To adhere to First Normal Form (1NF) and eliminate repeating groups, transaction headers (`Purchase`, `Sales`) must be separated from transaction line items (`Purchase_Item`, `Sale_Item`).

4. **Diverse Stock Outflows (Shrinkage, Spoilage, Damage, Internal Consumption):**
   - Grocery stores routinely suffer non-sales stock depletion: perishables spoil, packaging tears, bottles leak, or items are consumed internally for store cleaning or maintenance.
   - These outflows must be tracked via distinct movement classifications (`DAMAGE`, `INTERNAL_USE`, `SUPPLIER_RETURN`) rather than falsifying sales figures.

5. **Historical Price and Cost Freezing:**
   - Once a sale is completed, historical records must permanently preserve:
     - `unit_selling_price`: The exact retail price charged at that instant.
     - `unit_cost_at_sale`: The average inventory cost at the exact time of the transaction.
   - Historical sales and profit margins must never recalculate when catalog prices or subsequent purchase costs change.

6. **Scope Boundary:**
   - This application is an **internal back-office inventory and point-of-sale management system**.
   - It deliberately excludes e-commerce shopping carts, customer registration, public storefronts, delivery tracking, and payment gateways.

---

## 3. THE NINE CORE DATABASE ENTITIES & SPECIFICATIONS

The system strictly utilizes nine normalized relational database tables:

```
+-----------------------------------------------------------------------------------+
|                                  DATABASE ENTITIES                                |
+-----------------------+-----------------------------+-----------------------------+
| Master Data           | Transactional Master-Detail | State & Audit Trail         |
+-----------------------+-----------------------------+-----------------------------+
| 1. Category           | 4. Purchase                 | 8. Inventory                |
| 2. Product            | 5. Purchase_Item            | 9. Stock_Movement           |
| 3. Supplier           | 6. Sales                    |                             |
|                       | 7. Sale_Item                |                             |
+-----------------------+-----------------------------+-----------------------------+
```

### Table 1: `Category`
- **Purpose:** Organizes grocery products into logical departments (e.g., Grains, Pulses, Dairy, Beverages, Snacks, Spices, Household Essentials).
- **Attributes:**
  - `category_id` INT AUTO_INCREMENT PRIMARY KEY
  - `category_name` VARCHAR(60) NOT NULL UNIQUE
  - `description` VARCHAR(150) NULL

### Table 2: `Product`
- **Purpose:** Stores the grocery product master catalog, unit of measure, standard retail price, and minimum reorder threshold.
- **Attributes:**
  - `product_id` INT AUTO_INCREMENT PRIMARY KEY
  - `product_name` VARCHAR(100) NOT NULL
  - `category_id` INT NOT NULL FOREIGN KEY
  - `supplier_id` INT NULL FOREIGN KEY
  - `selling_price` DECIMAL(10,2) NOT NULL (CHECK `selling_price >= 0`)
  - `reorder_level` DECIMAL(10,3) NOT NULL (CHECK `reorder_level >= 0`)
  - `unit` VARCHAR(20) NOT NULL (`kg`, `g`, `litre`, `ml`, `packet`, `piece`, `bottle`, `box`, `dozen`)
  - `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP

### Table 3: `Supplier`
- **Purpose:** Maintains wholesale vendor records from whom groceries are procured.
- **Attributes:**
  - `supplier_id` INT AUTO_INCREMENT PRIMARY KEY
  - `supplier_name` VARCHAR(100) NOT NULL
  - `phone` VARCHAR(15) NULL
  - `email` VARCHAR(100) NULL
  - `address` VARCHAR(200) NULL

### Table 4: `Purchase`
- **Purpose:** Transaction header for stock-in procurement batches received from suppliers.
- **Attributes:**
  - `purchase_id` INT AUTO_INCREMENT PRIMARY KEY
  - `supplier_id` INT NOT NULL FOREIGN KEY
  - `purchase_date` DATE NOT NULL
  - `invoice_number` VARCHAR(50) NOT NULL UNIQUE

### Table 5: `Purchase_Item`
- **Purpose:** Detail line items for each grocery product included in a specific purchase invoice.
- **Attributes:**
  - `purchase_item_id` INT AUTO_INCREMENT PRIMARY KEY
  - `purchase_id` INT NOT NULL FOREIGN KEY
  - `product_id` INT NOT NULL FOREIGN KEY
  - `quantity` DECIMAL(10,3) NOT NULL (CHECK `quantity > 0`)
  - `unit_cost` DECIMAL(10,2) NOT NULL (CHECK `unit_cost >= 0`)
  - `expiry_date` DATE NULL (Optional grocery shelf-life tracking)

### Table 6: `Sales`
- **Purpose:** Transaction header for sales transactions made at the grocery counter.
- **Attributes:**
  - `sale_id` INT AUTO_INCREMENT PRIMARY KEY
  - `sale_date` DATE NOT NULL
  - `bill_number` VARCHAR(50) NOT NULL UNIQUE

### Table 7: `Sale_Item`
- **Purpose:** Detail line items for each product sold in a counter bill, capturing historical selling price and cost snapshot.
- **Attributes:**
  - `sale_item_id` INT AUTO_INCREMENT PRIMARY KEY
  - `sale_id` INT NOT NULL FOREIGN KEY
  - `product_id` INT NOT NULL FOREIGN KEY
  - `quantity` DECIMAL(10,3) NOT NULL (CHECK `quantity > 0`)
  - `unit_selling_price` DECIMAL(10,2) NOT NULL (CHECK `unit_selling_price >= 0`)
  - `unit_cost_at_sale` DECIMAL(10,2) NOT NULL (CHECK `unit_cost_at_sale >= 0`)

### Table 8: `Inventory`
- **Purpose:** Maintains the current, real-time physical stock level and weighted average unit cost for every product.
- **Attributes:**
  - `product_id` INT PRIMARY KEY and FOREIGN KEY (1:1 with `Product`)
  - `quantity_available` DECIMAL(10,3) NOT NULL DEFAULT 0.000 (CHECK `quantity_available >= 0`)
  - `average_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00 (CHECK `average_cost >= 0`)
  - `last_updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### Table 9: `Stock_Movement`
- **Purpose:** Immutable, chronological audit ledger of every event that affects inventory quantity.
- **Attributes:**
  - `movement_id` INT AUTO_INCREMENT PRIMARY KEY
  - `product_id` INT NOT NULL FOREIGN KEY
  - `movement_date` DATE NOT NULL
  - `movement_type` VARCHAR(25) NOT NULL (ENUM / CHECK constraint: `OPENING`, `PURCHASE`, `SALE`, `INTERNAL_USE`, `DAMAGE`, `CUSTOMER_RETURN`, `SUPPLIER_RETURN`)
  - `quantity` DECIMAL(10,3) NOT NULL (CHECK `quantity > 0` - always stored as a positive magnitude)
  - `remarks` VARCHAR(150) NULL

---

## 4. ENTITY RELATIONSHIP CARDINALITIES

| Primary Entity | Foreign Entity | Relationship | Cardinality | Business Meaning |
| :--- | :--- | :--- | :--- | :--- |
| `Category` | `Product` | One-to-Many | 1 : N | A category classifies many products; each product belongs to exactly one category. |
| `Supplier` | `Purchase` | One-to-Many | 1 : N | A supplier can supply multiple purchase orders; each purchase order is sourced from one supplier. |
| `Purchase` | `Purchase_Item` | One-to-Many | 1 : N | A purchase invoice contains one or more line items; each line item belongs to one invoice. |
| `Product` | `Purchase_Item` | One-to-Many | 1 : N | A product can appear across multiple purchase invoices over time. |
| `Sales` | `Sale_Item` | One-to-Many | 1 : N | A sales bill contains one or more line items; each line item belongs to one sales bill. |
| `Product` | `Sale_Item` | One-to-Many | 1 : N | A product can be sold across multiple customer bills. |
| `Product` | `Inventory` | One-to-One | 1 : 1 | Each product has exactly one current inventory record; inventory primary key is foreign key to Product. |
| `Product` | `Stock_Movement`| One-to-Many | 1 : N | A product has a continuous timeline of stock movement ledger entries. |

---

## 5. ARCHITECTURAL & DESIGN JUSTIFICATIONS (ACADEMIC RIGOR)

### A. Why Master-Detail Tables are Required (`Purchase`/`Purchase_Item` and `Sales`/`Sale_Item`)
In relational database theory:
1. **First Normal Form (1NF) Compliance:** Storing multiple items in comma-separated fields within `Purchase` or `Sales` violates 1NF (non-atomic values).
2. **Elimination of Repeating Groups:** Having columns like `item1_id`, `item1_qty`, `item2_id`, `item2_qty` creates a fixed, inflexible schema with null values and structural anomalies.
3. **Different Granularities:** An invoice has header-level attributes (`invoice_number`, `purchase_date`, `supplier_id`) that occur once per transaction, whereas item attributes (`quantity`, `unit_cost`) occur per product.
4. **Historical Price Stability:** Separating `Sale_Item` allows capturing `unit_selling_price` and `unit_cost_at_sale` at the exact millisecond of the sale. If the master catalog `Product.selling_price` is updated tomorrow, historical revenues and profits remain uncorrupted.

### B. Why `Inventory` and `Stock_Movement` are Separate
A common beginner question in DBMS viva is: *"If you have `Stock_Movement`, why do you need an `Inventory` table?"*

1. **Performance & Query Scalability ($O(1)$ vs $O(N)$):**
   - High-throughput point-of-sale checkout requires checking whether `quantity_available >= requested_qty` in microseconds.
   - Reading directly from `Inventory.quantity_available` is a simple indexed primary key lookup ($O(1)$ time complexity).
   - If stock were computed dynamically on every POS scan by aggregating the entire `Stock_Movement` history (`SUM(CASE WHEN ... THEN qty ELSE -qty END)`), query time would degrade linearly ($O(N)$) as the transaction volume grows into tens of thousands of records.
2. **Auditability & Integrity Verification:**
   - `Inventory` represents the **current state**.
   - `Stock_Movement` represents the **historical timeline (ledger)**.
   - The database administrator can run an integrity verification query at any time:
     $$\text{Calculated Stock from Movement} = \sum \text{Stock-In} - \sum \text{Stock-Out}$$
     and assert that it equals `Inventory.quantity_available`. Any discrepancy immediately highlights unauthorized tampering.

### C. Positive Quantities in `Stock_Movement`
All quantities in `Stock_Movement` are strictly positive (`CHECK quantity > 0`). The mathematical sign is governed by business rules:
- **Positive Contributors (+):** `OPENING`, `PURCHASE`, `CUSTOMER_RETURN`
- **Negative Contributors (-):** `SALE`, `INTERNAL_USE`, `DAMAGE`, `SUPPLIER_RETURN`

This eliminates ambiguity where developers or operators accidentally input negative numbers for subtractions, causing double negatives.

---

## 6. PHASE 1 DELIVERABLE SUMMARY & READINESS

Phase 1 establishes the rock-solid conceptual and logical foundation for the Grocery Store Inventory Management System. 

With all 9 entities, attributes, units, precision requirements, constraints, relationships, and justifications formally specified, the project is ready to transition to **PHASE 2: ER Diagram, Relational Schema, Primary Keys, Foreign Keys, and Normalization Analysis**.

