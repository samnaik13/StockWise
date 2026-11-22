# StockWise - Grocery Store Inventory Management System
### A Complete, Academic Relational Database Management Systems (DBMS) College Project

Developed with **PHP 8.2**, **MariaDB 10.4 / MySQL**, **Apache (XAMPP)**, and **phpMyAdmin**.

---

## 🌟 PROJECT HIGHLIGHTS

- **Pure Database-Centric Design:** Built strictly around a **9-table relational database schema** normalized up to **3NF / BCNF**.
- **All 7 Core Algorithms Implemented:**
  1. **Algorithm 1:** Inventory Stock Calculation ($\text{Closing} = \text{Opening} + \text{Inflow} - \text{Outflow}$)
  2. **Algorithm 2:** Stock-In (Purchase transaction, inventory update & Weighted Average Costing)
  3. **Algorithm 3:** Stock-Out (Stock availability validation, frozen cost snapshot, overselling rejection)
  4. **Algorithm 4:** Low Stock Detection (`quantity_available <= reorder_level` view)
  5. **Algorithm 5:** Monthly Inventory & Financial Reporting
  6. **Algorithm 6:** Gross Profit Calculation ($\text{Gross Profit} = \text{Sales Revenue} - \text{COGS}$)
  7. **Algorithm 7:** Dead Stock Detection ($\ge 90\text{ days}$ inactive working capital view)
- **Defensive Storage Engine Triggers:** Blocks negative stock and zero-quantity movements at the storage layer.
- **ACID Transaction Management:** Atomic counter sales billing and wholesale purchase receipt.
- **Grocery Specifics:** Full support for fractional quantities (`DECIMAL(10,3)`) across weight (`kg`, `g`), volume (`litre`, `ml`), and counts (`packet`, `piece`, `bottle`).
- **Comprehensive 20-Point Automated Test Suite:** All 20 operational test cases passed with a **100% success rate**.
- **Self-Contained:** Zero external frameworks (no Laravel, Node.js, React, or composer bloat). Runs on standard XAMPP out-of-the-box.

---

## 🚀 QUICK START & INSTALLATION GUIDE

### 1. Prerequisites
- **XAMPP** installed at `C:\xampp` with Apache and MySQL/MariaDB.
- **phpMyAdmin** accessible at `http://localhost/phpmyadmin/`.

### 2. Live Directory Setup
The project files are located in your workspace:
```
c:\Users\naiks\Desktop\MY_INVENTORY
```
and a Windows directory junction has already been created to:
```
C:\xampp\htdocs\grocery_inventory
```
Any file edited in the workspace is immediately live in Apache!

### 3. Database Import via phpMyAdmin
1. Start **Apache** and **MySQL** in your **XAMPP Control Panel**.
2. Open your web browser and navigate to:
   ```
   http://localhost/phpmyadmin/
   ```
3. Click the **Import** tab.
4. Click **Choose File** and select:
   ```
   database/grocery_inventory.sql
   ```
5. Click **Import** (or **Go**).
   *(This creates the `grocery_inventory` database, all 9 tables, 4 views, 3 triggers, and realistic grocery store sample data covering multiple months).*

### 4. Access the Application
Open your browser and navigate to:
```
http://localhost/grocery_inventory/
```

---

## 📁 PROJECT FOLDER STRUCTURE

```
grocery_inventory/
├── config/
│   └── db.php                  # PDO database connection, constants & transaction functions
├── database/
│   ├── grocery_inventory.sql   # Complete all-in-one master SQL export for phpMyAdmin
│   ├── create_tables.sql       # DDL script for 9 normalized tables & constraints
│   ├── sample_data.sql         # Realistic grocery store sample dataset
│   ├── views.sql               # Database views (Low Stock, Dead Stock, Product Status)
│   ├── triggers.sql            # Defensive database triggers (negative inventory rejection)
│   └── queries.sql             # 22 Academic SQL demonstration queries with explanations
├── includes/
│   ├── header.php              # Navigation bar, brand logo, alerts & notification badges
│   └── footer.php              # Standard academic footer
├── products/
│   ├── view_products.php       # Product catalog list, search, category filter & status
│   ├── add_product.php         # Product registration with initial opening stock
│   ├── edit_product.php        # Update product metadata & pricing
│   └── delete_product.php      # Referential integrity protected product deletion
├── categories/
│   └── categories.php          # Category management (add, edit, delete, item count)
├── suppliers/
│   └── suppliers.php           # Wholesale vendor directory & supplier procurement history
├── purchases/
│   ├── purchases.php           # Wholesale procurement register & invoice inspection
│   └── add_purchase.php        # Dynamic multi-item stock-in batch form (Algorithm 2)
├── sales/
│   ├── sales.php               # Customer counter billing history & COGS breakdown
│   └── add_sale.php            # Dynamic POS counter bill with stock check (Algorithm 3)
├── inventory/
│   ├── inventory.php           # Physical inventory valuation & current stock balances
│   ├── stock_adjustment.php    # Non-sale stock adjustments (Internal use, damage, returns)
│   └── stock_history.php       # Filterable stock movement chronological audit log
├── reports/
│   ├── monthly_report.php      # 4-Part monthly inventory & financial statement (Algorithm 5)
│   ├── low_stock.php           # Low stock reorder alert view (Algorithm 4)
│   ├── dead_stock.php          # Stagnant working capital dead stock report (Algorithm 7)
│   ├── sales_report.php        # Period sales performance & product ranking
│   └── purchase_report.php     # Period wholesale spend & supplier volume report
├── tests/
│   └── run_all_tests.php       # Automated 20-point test runner (PHP CLI)
├── docs/
│   ├── phase1_requirements_and_database_planning.md
│   ├── phase2_er_schema_normalization.md
│   ├── test_cases.md           # 20-Point operational test matrix with inputs & results
│   ├── viva_questions.md       # Comprehensive DBMS viva questions & answers
│   └── sql_demonstration_guide.md # phpMyAdmin practical exam demonstration script
├── css/
│   └── style.css               # Clean, professional grocery store UI styling
├── js/
│   └── script.js               # Dynamic multi-item forms, live subtotals & stock pre-check
├── index.php                   # Executive dashboard with 8 metrics & reorder alerts
└── README.md                   # Complete documentation
```

---

## 🧪 RUNNING THE AUTOMATED TEST SUITE

You can run all 20 operational test cases anytime from PowerShell or Command Prompt:

```powershell
C:\xampp\php\php.exe "c:\Users\naiks\Desktop\MY_INVENTORY\tests\run_all_tests.php"
```

**Verified Test Summary:**
- TC-01: Add a new grocery product & initialize inventory (**PASS**)
- TC-02: Update product attributes (**PASS**)
- TC-03: Record wholesale supplier (**PASS**)
- TC-04 & 05: Record purchase & verify stock increase (**PASS**)
- TC-06 & 07: Record sale & verify stock decrease (**PASS**)
- TC-08: Attempt to oversell (Stock-Out rejected, stock preserved) (**PASS**)
- TC-09: Record internal grocery usage (**PASS**)
- TC-10: Record damaged / spoiled stock (**PASS**)
- TC-11: Detect low-stock items via `Low_Stock_Products` view (**PASS**)
- TC-12: Detect dead-stock items via `Dead_Stock_Products` view (**PASS**)
- TC-13: Generate monthly purchase report (**PASS**)
- TC-14: Generate monthly sales report (**PASS**)
- TC-15: Calculate Cost of Goods Sold (COGS) (**PASS**)
- TC-16: Calculate Gross Profit using Weighted Average Cost (**PASS**)
- TC-17: Verify Inventory Capital Valuation (**PASS**)
- TC-18: Stock Movement Reconciliation Audit (0.000 variance across all SKUs) (**PASS**)
- TC-19: Foreign key constraint enforcement (**PASS**)
- TC-20: ACID Transaction rollback verification (**PASS**)

---

## 🎓 ACADEMIC SUBMISSION DOCUMENTS

For project submissions, report generation, and viva preparation:
- [Phase 1 Planning](file:///c:/Users/naiks/Desktop/MY_INVENTORY/docs/phase1_requirements_and_database_planning.md)
- [Phase 2 ER Diagram, Schema & 3NF Normalization](file:///c:/Users/naiks/Desktop/MY_INVENTORY/docs/phase2_er_schema_normalization.md)
- [20-Point Test Suite Matrix](file:///c:/Users/naiks/Desktop/MY_INVENTORY/docs/test_cases.md)
- [Comprehensive DBMS Viva Questions & Answers](file:///c:/Users/naiks/Desktop/MY_INVENTORY/docs/viva_questions.md)
- [phpMyAdmin SQL Demonstration Guide](file:///c:/Users/naiks/Desktop/MY_INVENTORY/docs/sql_demonstration_guide.md)

