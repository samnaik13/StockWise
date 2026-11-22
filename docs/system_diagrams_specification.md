# GROCERY STORE INVENTORY MANAGEMENT SYSTEM
## COMPREHENSIVE SYSTEM & DATABASE ARCHITECTURE DIAGRAMS (PHASE 12)

This document contains the complete visual and technical diagrams for academic submission, project documentation, and viva voce presentation.

---

## 1. ENTITY-RELATIONSHIP (ER) DIAGRAM

The ER Diagram below depicts all **nine core entities**, attributes, primary keys (`PK`), foreign keys (`FK`), unique keys (`UK`), and Crow's Foot cardinalities.

```mermaid
erDiagram
    CATEGORY ||--o{ PRODUCT : "classifies (1:N)"
    SUPPLIER ||--o{ PURCHASE : "supplies (1:N)"
    PURCHASE ||--|{ PURCHASE_ITEM : "contains (1:N)"
    PRODUCT ||--o{ PURCHASE_ITEM : "received_in (1:N)"
    SALES ||--|{ SALE_ITEM : "contains (1:N)"
    PRODUCT ||--o{ SALE_ITEM : "sold_in (1:N)"
    PRODUCT ||--|| INVENTORY : "maintains_state (1:1)"
    PRODUCT ||--o{ STOCK_MOVEMENT : "audit_history (1:N)"

    CATEGORY {
        int category_id PK
        varchar category_name UK
        varchar description
    }

    PRODUCT {
        int product_id PK
        varchar product_name
        int category_id FK
        decimal selling_price
        decimal reorder_level
        varchar unit
        datetime created_at
    }

    SUPPLIER {
        int supplier_id PK
        varchar supplier_name
        varchar phone
        varchar email
        varchar address
    }

    PURCHASE {
        int purchase_id PK
        int supplier_id FK
        date purchase_date
        varchar invoice_number UK
    }

    PURCHASE_ITEM {
        int purchase_item_id PK
        int purchase_id FK
        int product_id FK
        decimal quantity
        decimal unit_cost
        date expiry_date
    }

    SALES {
        int sale_id PK
        date sale_date
        varchar bill_number UK
    }

    SALE_ITEM {
        int sale_item_id PK
        int sale_id FK
        int product_id FK
        decimal quantity
        decimal unit_selling_price
        decimal unit_cost_at_sale
    }

    INVENTORY {
        int product_id PK, FK
        decimal quantity_available
        decimal average_cost
        datetime last_updated
    }

    STOCK_MOVEMENT {
        int movement_id PK
        int product_id FK
        date movement_date
        varchar movement_type
        decimal quantity
        varchar remarks
    }
```

### Cardinality Summary
- **Category to Product (1 : N):** One category classifies zero or many products; every product belongs to exactly one category.
- **Supplier to Purchase (1 : N):** One supplier provides zero or many purchase invoices; each purchase is sourced from one supplier.
- **Purchase to Purchase_Item (1 : N):** One purchase order contains one or many line items; each line item belongs to one invoice.
- **Product to Purchase_Item (1 : N):** One product appears across zero or many purchase line items.
- **Sales to Sale_Item (1 : N):** One counter bill contains one or many line items; each line item belongs to one sales bill.
- **Product to Sale_Item (1 : N):** One product is sold across zero or many customer bills.
- **Product to Inventory (1 : 1):** Every product has exactly one physical inventory balance record (`product_id` is both PK and FK).
- **Product to Stock_Movement (1 : N):** Every product maintains a continuous chronological audit ledger of all stock additions and deductions.

---

## 2. DATA FLOW DIAGRAMS (DFD)

### A. DFD Level 0 (Context Diagram)
Depicts the entire application as a single central process interacting with external entities.

```mermaid
flowchart TD
    Admin["Shop Administrator / Cashier"]
    Supplier["Wholesale Supplier"]
    Customer["Retail Customer"]

    System(["Grocery Store Inventory Management System"])

    Admin -->|"Product Catalog, Pricing, Reorder Levels"| System
    Admin -->|"Stock Loss & Damage Entries"| System
    System -->|"Inventory Valuations & Alerts"| Admin
    System -->|"Monthly Financial & Profit Reports"| Admin

    Supplier -->|"Wholesale Invoices & Batch Deliveries"| System
    System -->|"Purchase Orders & Spend Tracking"| Supplier

    Customer -->|"Counter Purchases & Payments"| System
    System -->|"Printed Receipts & Counter Bills"| Customer
```

---

### B. DFD Level 1 (Detailed Functional Process Decomposition)
Breaks down the system into core database-driven processes and shows interactions with the persistent Data Stores ($D_1$ to $D_9$).

```mermaid
flowchart TD
    Admin["Shop Administrator"]
    
    subgraph SystemProcesses ["Grocery Inventory System Processes"]
        P1["1.0 Manage Products & Categories"]
        P2["2.0 Manage Wholesale Suppliers"]
        P3["3.0 Stock-In Processing (Algorithm 2)"]
        P4["4.0 Stock-Out Billing (Algorithm 3)"]
        P5["5.0 Stock Adjustments (Loss / Usage)"]
        P6["6.0 Monthly Reports & Alerts (Algo 4, 5, 6, 7)"]
    end

    subgraph DataStores ["Relational Data Stores (Database Tables)"]
        D1[("D1: Category")]
        D2[("D2: Product")]
        D3[("D3: Supplier")]
        D4[("D4: Purchase")]
        D5[("D5: Purchase_Item")]
        D6[("D6: Sales")]
        D7[("D7: Sale_Item")]
        D8[("D8: Inventory")]
        D9[("D9: Stock_Movement")]
    end

    Admin -->|"Product details"| P1
    P1 -->|"Write Category"| D1
    P1 -->|"Write Product"| D2
    P1 -->|"Initialize Stock"| D8
    P1 -->|"Log Opening"| D9

    Admin -->|"Supplier details"| P2
    P2 -->|"Write Supplier"| D3

    Admin -->|"Wholesale Invoice"| P3
    D3 -.->|"Read Supplier"| P3
    D2 -.->|"Read Product"| P3
    P3 -->|"Insert Header"| D4
    P3 -->|"Insert Items"| D5
    P3 -->|"Update Stock & WAC Cost"| D8
    P3 -->|"Log PURCHASE"| D9

    Admin -->|"Customer Bill Request"| P4
    D8 -.->|"Check Availability"| P4
    D2 -.->|"Read Price & Unit"| P4
    P4 -->|"Insert Header"| D6
    P4 -->|"Insert Items (Frozen Cost)"| D7
    P4 -->|"Deduct Stock"| D8
    P4 -->|"Log SALE"| D9

    Admin -->|"Internal Use / Damage"| P5
    D8 -.->|"Check Stock"| P5
    P5 -->|"Deduct Stock"| D8
    P5 -->|"Log DAMAGE/INTERNAL"| D9

    D8 -.->|"Query Stock Levels"| P6
    D7 -.->|"Query Revenue & COGS"| P6
    D5 -.->|"Query Purchase Spend"| P6
    D9 -.->|"Audit Ledger History"| P6
    P6 -->|"Low Stock Alerts (Algo 4)"| Admin
    P6 -->|"Dead Stock Detection (Algo 7)"| Admin
    P6 -->|"Financial Statements (Algo 5 & 6)"| Admin
```

---

## 3. USE CASE DIAGRAM

Illustrates the actor (Shop Administrator / Counter Cashier) and all primary business operations provided by the system.

```mermaid
flowchart LR
    Actor["👤 Shop Administrator / Counter Cashier"]

    subgraph SystemBoundary ["Grocery Store Inventory Management System"]
        UC1(["Manage Categories"])
        UC2(["Manage Product Catalog & Pricing"])
        UC3(["Manage Wholesale Suppliers"])
        UC4(["Record Stock-In Purchase"])
        UC5(["Update Weighted Average Cost"])
        UC6(["Process Sales Counter Bill"])
        UC7(["Validate Stock Availability"])
        UC8(["Record Damage / Internal Use"])
        UC9(["Audit Stock Movement Ledger"])
        UC10(["View Low Stock Reorder Alerts"])
        UC11(["Detect Dead Stock (90+ Days)"])
        UC12(["Generate Monthly Financial Report"])
    end

    Actor --> UC1
    Actor --> UC2
    Actor --> UC3
    Actor --> UC4
    Actor --> UC6
    Actor --> UC8
    Actor --> UC9
    Actor --> UC10
    Actor --> UC11
    Actor --> UC12

    UC4 -.->|"<<includes>>"| UC5
    UC6 -.->|"<<includes>>"| UC7
```

---

## 4. SEQUENCE DIAGRAMS

### Sequence Diagram 1: Counter Sales Billing (Algorithm 3 - Stock-Out Transaction)
Demonstrates the atomic sequence of events when a cashier rings up an item, stock is validated, historical costs are frozen, and inventory is reduced.

```mermaid
sequenceDiagram
    autonumber
    actor Cashier as Cashier / Admin
    participant UI as Sales Form (add_sale.php)
    participant Engine as Transaction Engine (db.php)
    participant DB_Inv as MariaDB: Inventory Table
    participant DB_Sale as MariaDB: Sales & Sale_Item
    participant DB_Move as MariaDB: Stock_Movement

    Cashier ->> UI: Select Product, Enter Quantity (e.g. 5kg)
    UI ->> UI: Client-Side Stock Pre-Check
    UI ->> Engine: POST recordSaleTransaction(sale_date, bill_no, items)
    
    activate Engine
    Engine ->> DB_Inv: START TRANSACTION
    Engine ->> DB_Inv: SELECT quantity_available, average_cost FROM Inventory WHERE product_id = ? FOR UPDATE
    DB_Inv -->> Engine: Returns available_stock (e.g. 70.000), avg_cost (₹27.00)
    
    alt Insufficient Stock (Requested Qty > Available)
        Engine ->> DB_Inv: ROLLBACK
        Engine -->> UI: Transaction Error: Insufficient stock
        UI -->> Cashier: Display: "Transaction Rejected (Stock Insufficient)"
    else Sufficient Stock Exists (Requested Qty <= Available)
        Engine ->> DB_Sale: INSERT INTO Sales (sale_date, bill_number)
        Engine ->> DB_Sale: INSERT INTO Sale_Item (product_id, qty, unit_price, unit_cost_at_sale=₹27.00)
        Engine ->> DB_Inv: UPDATE Inventory SET quantity_available = quantity_available - 5.000
        Engine ->> DB_Move: INSERT INTO Stock_Movement (product_id, date, 'SALE', qty=5.000, remarks)
        Engine ->> DB_Inv: COMMIT
        Engine -->> UI: Success (Sale ID, Bill Generated)
        deactivate Engine
        UI -->> Cashier: Display: Bill Generated & Stock Deducted
    end
```

---

### Sequence Diagram 2: Wholesale Purchase & Stock-In (Algorithm 2 - Weighted Average Costing)
Demonstrates how receiving wholesale inventory from suppliers increases physical stock and recalculates the Weighted Average Cost (WAC).

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Shop Administrator
    participant UI as Purchase Form (add_purchase.php)
    participant Engine as Transaction Engine (db.php)
    participant DB_Pur as MariaDB: Purchase & Purchase_Item
    participant DB_Inv as MariaDB: Inventory Table
    participant DB_Move as MariaDB: Stock_Movement

    Admin ->> UI: Enter Supplier, Invoice No, Items, Qty (40kg) & Unit Cost (₹82.00)
    UI ->> Engine: POST recordPurchaseTransaction(supplier_id, date, invoice_no, items)
    
    activate Engine
    Engine ->> DB_Inv: START TRANSACTION
    Engine ->> DB_Pur: INSERT INTO Purchase (supplier_id, date, invoice_number)
    Engine ->> DB_Pur: INSERT INTO Purchase_Item (product_id, qty=40, unit_cost=82.00)
    
    Engine ->> DB_Inv: SELECT quantity_available, average_cost FROM Inventory FOR UPDATE
    DB_Inv -->> Engine: Current Stock: 102kg @ ₹77.88/kg
    
    Engine ->> Engine: Calculate Weighted Average Cost (WAC)<br/>New Avg = (102*77.88 + 40*82.00) / (102 + 40) = ₹79.03
    
    Engine ->> DB_Inv: UPDATE Inventory SET quantity_available = 142kg, average_cost = 79.03
    Engine ->> DB_Move: INSERT INTO Stock_Movement (product_id, date, 'PURCHASE', qty=40, remarks)
    Engine ->> DB_Inv: COMMIT
    Engine -->> UI: Success (Batch Stocked In)
    deactivate Engine
    UI -->> Admin: Display: "Stock Increased & WAC Updated to ₹79.03"
```

---

## 5. PRODUCT & INVENTORY STATE TRANSITION DIAGRAM

Illustrates the lifecycle states of a grocery item from initial registration through stock changes, low stock reorders, out-of-stock conditions, and stagnant dead-stock detection.

```mermaid
stateDiagram-v2
    [*] --> Registered : Product created in Catalog
    Registered --> InStock : Initial Opening Stock Added

    InStock --> AdequateStock : Quantity > Reorder Level
    AdequateStock --> AdequateStock : Wholesale Purchase (Algorithm 2)
    AdequateStock --> AdequateStock : Customer Sale / Outflow

    AdequateStock --> LowStock : Stock falls <= Reorder Level (Algorithm 4)
    LowStock --> OutOfStock : Stock reduced to 0.000
    OutOfStock --> AdequateStock : Restocked via Wholesale Purchase
    LowStock --> AdequateStock : Restocked via Wholesale Purchase

    AdequateStock --> DeadStock : Stock > 0 AND No Sales for >= 90 Days (Algorithm 7)
    DeadStock --> AdequateStock : Customer Sale occurs (Resumes Active Movement)
    DeadStock --> OutOfStock : Cleared out / Damaged
```

---

## 6. RELATIONAL SCHEMA ARCHITECTURE MAPPING

A structural table-by-table reference showing primary and foreign key relational mapping:

```
[Category]
  └── (category_id) ◄───┐
                        │ FK: fk_product_category (ON DELETE RESTRICT, ON UPDATE CASCADE)
[Product] ──────────────┴──────┬──────────────────┬─────────────────┐
  ├── (product_id) ◄─────────┐ │                  │                 │
  │                          │ │ FK               │ FK              │ FK
  │                          │ ▼                  ▼                 ▼
[Inventory] (1:1)       [Purchase_Item]       [Sale_Item]     [Stock_Movement]
  └── (product_id)           │                    │                 │
                             │ FK                 │ FK              │
                             ▼                    ▼                 │
                        [Purchase]              [Sales]             │
                             │                                      │
                             │ FK: fk_purchase_supplier             │
                             ▼                                      │
                        [Supplier]                                  │
```

