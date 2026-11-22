# GROCERY STORE INVENTORY MANAGEMENT SYSTEM
## 20-POINT COMPREHENSIVE DBMS TEST SUITE REPORT (PHASE 11)

**Execution Environment:** PHP 8.2.12 CLI / Apache + MariaDB 10.4.32 (XAMPP)  
**Database Name:** `grocery_inventory`  
**Test Suite File:** [`tests/run_all_tests.php`](tests/run_all_tests.php)  
**Overall Result:** 100% PASS (All 20 Operations Verified)

---

| Test ID | Operational Test Case | Input Parameters | Expected Output | Actual Output | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **TC-01** | Add a new grocery product | `product_name='Organic Almonds 500g'`, `category_id=6`, `selling_price=650.00`, `reorder_level=10.000`, `unit='kg'` | Row created in `Product` table and 1:1 row initialized in `Inventory`. | Inserted Product ID #19 with 0 available stock. | **PASS** |
| **TC-02** | Update product information | Update `selling_price=680.00`, `reorder_level=12.000` | Attributes updated; historical transactions remain unaffected. | Updated selling_price=680.00, reorder_level=12.000 | **PASS** |
| **TC-03** | Record a wholesale supplier | `supplier_name='Nut & DryFruit Traders'`, `phone='9811002233'` | New supplier record created with auto-increment ID. | Supplier recorded with ID #6. | **PASS** |
| **TC-04** | Record a purchase order | Purchase 20.000 kg Organic Almonds @ 520.00/kg from Supplier #6 | Purchase header, line items, and stock movement created. | Purchase recorded successfully under invoice TEST-PUR-XXXX. | **PASS** |
| **TC-05** | Verify stock increase after purchase | Inflow of 20.000 kg Organic Almonds | `quantity_available` increases from 0.000 to 20.000 kg, WAC set to 520.00. | Stock increased to 20 kg, Average Cost updated to ₹520.00. | **PASS** |
| **TC-06** | Record a counter sale | Sell 5.000 kg Organic Almonds @ 680.00/kg | Counter bill created with frozen `unit_cost_at_sale=520.00`. | Bill generated; frozen cost snapshot = ₹520.00. | **PASS** |
| **TC-07** | Verify stock decrease after sale | Outflow of 5.000 kg Organic Almonds | `quantity_available` decreases from 20.000 to 15.000 kg. | Stock reduced to 15.000 kg. | **PASS** |
| **TC-08** | Attempt to oversell (insufficient stock) | Attempt to sell 50.000 kg when only 15.000 kg is available | Transaction rejected, exception thrown, stock remains 15.000 kg. | Rejected with message: 'Insufficient stock... Transaction rolled back.'. Stock intact at 15 kg. | **PASS** |
| **TC-09** | Record internal stock usage | Deduct 1.000 kg Organic Almonds for store tasting samples | Inventory decreases from 15.000 to 14.000 kg; movement type `INTERNAL_USE`. | Internal usage recorded; stock updated to 14.000 kg. | **PASS** |
| **TC-10** | Record damaged / spoiled products | Deduct 0.500 kg Organic Almonds due to torn vacuum bag | Inventory decreases from 14.000 to 13.500 kg; movement type `DAMAGE`. | Damage recorded; available stock updated to 13.500 kg. | **PASS** |
| **TC-11** | Detect low-stock products (Algorithm 4) | Query `Low_Stock_Products` view | Returns all SKUs where `quantity_available <= reorder_level`. | Detected 3 low-stock SKUs (Cow Milk, Coffee, Toor Dal) needing reorder. | **PASS** |
| **TC-12** | Detect dead-stock products (Algorithm 7) | Query `Dead_Stock_Products` view (threshold &ge; 90 days) | Returns SKUs with positive stock and zero sales in &ge; 90 days. | Detected 6 stagnant SKUs tying up capital with zero recent sales. | **PASS** |
| **TC-13** | Generate monthly purchase report | Query procurement for August 2026 | Aggregates all wholesale invoices and expenditures for the month. | Report generated: 4 items purchased totaling ₹5,720.00. | **PASS** |
| **TC-14** | Generate monthly sales report | Query sales for September 2026 | Aggregates all customer bills and revenues for the month. | Report generated: 1 customer bill totaling revenue ₹10,825.00. | **PASS** |
| **TC-15** | Calculate Cost of Goods Sold (COGS) | $\sum (\text{Sold Qty} \times \text{Unit Cost at Sale})$ | Stored historical costs summed without recalculation. | Calculated COGS across all sales = ₹31,473.45. | **PASS** |
| **TC-16** | Calculate Gross Profit (Algorithm 6) | $\text{Gross Profit} = \text{Sales Revenue} - \text{COGS}$ | Revenue - COGS computed with positive margin percentage. | Revenue: ₹39,539.00, COGS: ₹31,473.45, Gross Profit: ₹8,065.55 (20.40% margin). | **PASS** |
| **TC-17** | Verify Inventory Valuation | $\sum (\text{Available Qty} \times \text{Weighted Avg Cost})$ | Matches current physical capital invested in unsold inventory. | Total inventory capital valuation = ₹82,132.26. | **PASS** |
| **TC-18** | Stock Movement Reconciliation Audit | $\sum \text{Stock In} - \sum \text{Stock Out} \equiv \text{Inventory Qty}$ | Audit variance is exactly 0.000 for every single SKU. | Audited 19 SKUs. Every product verified with 0.000 variance. | **PASS** |
| **TC-19** | Test foreign key constraint enforcement | Attempt to insert Product with invalid `category_id=99999` | MariaDB rejects insertion with foreign key constraint violation. | Rejected by MariaDB with SQLSTATE 23000 (Integrity constraint violation). | **PASS** |
| **TC-20** | Test ACID Transaction Rollback | Execute valid transaction step followed by forced SQL error | Rollback undoes all changes, leaving database in pristine original state. | Transaction successfully rolled back. Sales row count remained unchanged (7). | **PASS** |

