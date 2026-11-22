# Phase 1 – Requirements and Database Planning

## Project
Grocery Store Inventory Management System. This is an internal system for a single grocery shop, not an e-commerce platform.

## Main functions
Manage grocery products and categories; maintain supplier information; record purchases and counter sales; track opening stock, customer returns, internal use, damage and supplier returns; calculate low-stock/dead-stock alerts; prepare monthly stock and financial reports.

## Nine entities
`categories`, `products`, `suppliers`, `purchases`, `purchase_items`, `sales`, `sale_items`, `inventory`, `stock_movements`.

## Grocery-specific decisions
- Quantity fields use `DECIMAL(10,3)` for values such as 2.500 kg or 1.250 litres.
- Each product has one consistent base unit selected from kg, g, litre, ml, packet, piece, bottle, box or dozen.
- Purchase and sale detail rows are separate because one invoice/bill can include multiple products.
- Inventory is the current summary. Stock movements preserve history.
- Every movement stores a positive quantity; movement type determines direction.
- Cost is stored on movements to support historical stock valuation. Sale lines preserve the cost used at sale time.
- Weighted average cost is recomputed on every purchase.
- Low stock is at or below the product reorder level.
- Dead stock requires positive stock and 90 days without a sale; never-sold products use first stock receipt date.

## Relationships
- Category 1:M Product
- Supplier 1:M Purchase
- Purchase 1:M Purchase_Item
- Product 1:M Purchase_Item
- Sale 1:M Sale_Item
- Product 1:M Sale_Item
- Product 1:1 Inventory
- Product 1:M Stock_Movement

## Data integrity
Use primary keys, foreign keys, unique invoice/bill numbers, NOT NULL fields, non-negative price/level constraints and positive movement quantities. Purchases, sales and adjustments use InnoDB transactions so stock, transaction details and movement history commit or roll back together.
