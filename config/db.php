<?php
/**
 * GROCERY STORE INVENTORY MANAGEMENT SYSTEM
 * Configuration & Core Database Engine Functions
 * Standard PDO Connection with UTF-8 and Exception Mode
 */

// Define database credentials
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'grocery_inventory');
define('DB_USER', 'root');
define('DB_PASS', '');

// Compute base URL dynamically
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$basePath = preg_replace('#/(products|categories|suppliers|purchases|sales|inventory|reports|includes|config).*$#', '', $scriptName);
$basePath = rtrim($basePath, '/');
define('BASE_URL', $basePath === '' ? '/' : $basePath . '/');

/**
 * Get PDO Database Connection
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("<div style='font-family:sans-serif;max-width:700px;margin:50px auto;padding:25px;background:#fff1f0;border:1px solid #ffa39e;border-radius:8px;color:#cf1322;'>"
                . "<h2 style='margin-top:0;'>Database Connection Error</h2>"
                . "<p>Unable to connect to the database <strong>" . htmlspecialchars(DB_NAME) . "</strong>.</p>"
                . "<p><strong>Reason:</strong> " . htmlspecialchars($e->getMessage()) . "</p>"
                . "<hr style='border:none;border-top:1px solid #ffccc7;margin:15px 0;'/>"
                . "<p><strong>Quick Fix Instructions:</strong></p>"
                . "<ol>"
                . "<li>Ensure Apache and MySQL services are started in <strong>XAMPP Control Panel</strong>.</li>"
                . "<li>Open <a href='http://localhost/phpmyadmin/' target='_blank'>phpMyAdmin</a>.</li>"
                . "<li>Click the <strong>Import</strong> tab and upload <code>database/grocery_inventory.sql</code>.</li>"
                . "<li>Refresh this page.</li>"
                . "</ol>"
                . "</div>");
        }
    }
    return $pdo;
}

/**
 * Format currency with Indian Rupee symbol
 */
function formatCurrency($amount) {
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Format quantity with appropriate decimals
 */
function formatQuantity($qty, $unit = '') {
    $formatted = rtrim(rtrim(number_format((float)$qty, 3, '.', ''), '0'), '.');
    return $unit ? $formatted . ' ' . htmlspecialchars($unit) : $formatted;
}

/**
 * Sanitize string input
 */
function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

// ==============================================================================
// CORE INVENTORY ALGORITHMS IMPLEMENTED AS DATABASE TRANSACTIONS
// ==============================================================================

/**
 * ALGORITHM 2: STOCK-IN (PURCHASE TRANSACTION)
 * Atomically records purchase invoice, line items, updates inventory quantity,
 * recalculates Weighted Average Cost, and writes to Stock_Movement.
 */
function recordPurchaseTransaction($supplier_id, $purchase_date, $invoice_number, $items) {
    $pdo = getDBConnection();
    try {
        $pdo->beginTransaction();

        // 1. Insert Purchase Header
        $stmt = $pdo->prepare("INSERT INTO `Purchase` (`supplier_id`, `purchase_date`, `invoice_number`) VALUES (?, ?, ?)");
        $stmt->execute([$supplier_id, $purchase_date, $invoice_number]);
        $purchase_id = $pdo->lastInsertId();

        // Prepared statements for details and updates
        $stmtItem = $pdo->prepare("INSERT INTO `Purchase_Item` (`purchase_id`, `product_id`, `quantity`, `unit_cost`, `expiry_date`) VALUES (?, ?, ?, ?, ?)");
        $stmtMovement = $pdo->prepare("INSERT INTO `Stock_Movement` (`product_id`, `movement_date`, `movement_type`, `quantity`, `remarks`) VALUES (?, ?, 'PURCHASE', ?, ?)");
        $stmtGetInv = $pdo->prepare("SELECT `quantity_available`, `average_cost` FROM `Inventory` WHERE `product_id` = ? FOR UPDATE");
        $stmtUpdateInv = $pdo->prepare("UPDATE `Inventory` SET `quantity_available` = ?, `average_cost` = ?, `last_updated` = NOW() WHERE `product_id` = ?");

        foreach ($items as $item) {
            $product_id = (int)$item['product_id'];
            $quantity = (float)$item['quantity'];
            $unit_cost = (float)$item['unit_cost'];
            $expiry_date = !empty($item['expiry_date']) ? $item['expiry_date'] : null;

            if ($quantity <= 0) {
                throw new Exception("Purchase quantity must be greater than zero for product ID: $product_id");
            }
            if ($unit_cost < 0) {
                throw new Exception("Unit cost cannot be negative for product ID: $product_id");
            }

            // Insert line item
            $stmtItem->execute([$purchase_id, $product_id, $quantity, $unit_cost, $expiry_date]);

            // Fetch current inventory for update
            $stmtGetInv->execute([$product_id]);
            $currentInv = $stmtGetInv->fetch();

            $current_qty = $currentInv ? (float)$currentInv['quantity_available'] : 0.0;
            $current_avg_cost = $currentInv ? (float)$currentInv['average_cost'] : 0.0;

            // ALGORITHM 6: WEIGHTED AVERAGE COST CALCULATION
            $new_qty = $current_qty + $quantity;
            if ($current_qty <= 0) {
                $new_avg_cost = $unit_cost;
            } else {
                $total_value = ($current_qty * $current_avg_cost) + ($quantity * $unit_cost);
                $new_avg_cost = $new_qty > 0 ? ($total_value / $new_qty) : $unit_cost;
            }

            // Update inventory
            $stmtUpdateInv->execute([$new_qty, round($new_avg_cost, 2), $product_id]);

            // Insert Stock_Movement
            $remarks = "Purchase Invoice: " . $invoice_number;
            $stmtMovement->execute([$product_id, $purchase_date, $quantity, $remarks]);
        }

        $pdo->commit();
        return ['success' => true, 'purchase_id' => $purchase_id];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * ALGORITHM 3: STOCK-OUT (SALES BILLING TRANSACTION)
 * Validates stock availability, freezes selling price and cost snapshot,
 * deducts inventory, and records Stock_Movement. Rolls back on insufficient stock.
 */
function recordSaleTransaction($sale_date, $bill_number, $items) {
    $pdo = getDBConnection();
    try {
        $pdo->beginTransaction();

        // 1. Insert Sales Header
        $stmt = $pdo->prepare("INSERT INTO `Sales` (`sale_date`, `bill_number`) VALUES (?, ?)");
        $stmt->execute([$sale_date, $bill_number]);
        $sale_id = $pdo->lastInsertId();

        $stmtGetInv = $pdo->prepare("SELECT i.quantity_available, i.average_cost, p.product_name, p.unit, p.selling_price 
                                      FROM `Inventory` i 
                                      JOIN `Product` p ON i.product_id = p.product_id 
                                      WHERE i.product_id = ? FOR UPDATE");
        $stmtItem = $pdo->prepare("INSERT INTO `Sale_Item` (`sale_id`, `product_id`, `quantity`, `unit_selling_price`, `unit_cost_at_sale`) VALUES (?, ?, ?, ?, ?)");
        $stmtUpdateInv = $pdo->prepare("UPDATE `Inventory` SET `quantity_available` = ?, `last_updated` = NOW() WHERE `product_id` = ?");
        $stmtMovement = $pdo->prepare("INSERT INTO `Stock_Movement` (`product_id`, `movement_date`, `movement_type`, `quantity`, `remarks`) VALUES (?, ?, 'SALE', ?, ?)");

        foreach ($items as $item) {
            $product_id = (int)$item['product_id'];
            $quantity = (float)$item['quantity'];

            if ($quantity <= 0) {
                throw new Exception("Sale quantity must be strictly positive for product ID: $product_id");
            }

            $stmtGetInv->execute([$product_id]);
            $prod = $stmtGetInv->fetch();

            if (!$prod) {
                throw new Exception("Product ID $product_id not found in system.");
            }

            $available = (float)$prod['quantity_available'];
            if ($quantity > $available) {
                throw new Exception("Insufficient stock for '{$prod['product_name']}'. Requested: {$quantity} {$prod['unit']}, Available: {$available} {$prod['unit']}. Transaction rolled back.");
            }

            $unit_selling_price = isset($item['unit_selling_price']) && $item['unit_selling_price'] !== '' 
                ? (float)$item['unit_selling_price'] 
                : (float)$prod['selling_price'];
            $unit_cost_at_sale = (float)$prod['average_cost'];

            // Save sale line item with snapshot of selling price and cost at sale
            $stmtItem->execute([$sale_id, $product_id, $quantity, $unit_selling_price, $unit_cost_at_sale]);

            // Deduct stock from Inventory
            $new_available = $available - $quantity;
            $stmtUpdateInv->execute([$new_available, $product_id]);

            // Insert Stock Movement
            $remarks = "Customer Bill: " . $bill_number;
            $stmtMovement->execute([$product_id, $sale_date, $quantity, $remarks]);
        }

        $pdo->commit();
        return ['success' => true, 'sale_id' => $sale_id];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * STOCK ADJUSTMENT TRANSACTION (Internal Use, Damage, Returns)
 * Atomically updates inventory and logs Stock_Movement.
 */
function recordStockAdjustment($product_id, $movement_date, $movement_type, $quantity, $remarks) {
    $pdo = getDBConnection();
    $validTypes = ['INTERNAL_USE', 'DAMAGE', 'CUSTOMER_RETURN', 'SUPPLIER_RETURN'];
    if (!in_array($movement_type, $validTypes)) {
        return ['success' => false, 'error' => 'Invalid adjustment movement type.'];
    }

    $quantity = (float)$quantity;
    if ($quantity <= 0) {
        return ['success' => false, 'error' => 'Quantity must be strictly greater than zero.'];
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT i.quantity_available, p.product_name, p.unit FROM `Inventory` i JOIN `Product` p ON i.product_id = p.product_id WHERE i.product_id = ? FOR UPDATE");
        $stmt->execute([$product_id]);
        $inv = $stmt->fetch();
        if (!$inv) {
            throw new Exception("Product ID $product_id not found.");
        }

        $available = (float)$inv['quantity_available'];

        if (in_array($movement_type, ['INTERNAL_USE', 'DAMAGE', 'SUPPLIER_RETURN'])) {
            if ($quantity > $available) {
                throw new Exception("Cannot reduce {$quantity} {$inv['unit']}. Available stock is only {$available} {$inv['unit']}.");
            }
            $new_stock = $available - $quantity;
        } else {
            // CUSTOMER_RETURN increases inventory
            $new_stock = $available + $quantity;
        }

        // Update Inventory
        $stmtUpdate = $pdo->prepare("UPDATE `Inventory` SET `quantity_available` = ?, `last_updated` = NOW() WHERE `product_id` = ?");
        $stmtUpdate->execute([$new_stock, $product_id]);

        // Insert Stock_Movement
        $stmtMove = $pdo->prepare("INSERT INTO `Stock_Movement` (`product_id`, `movement_date`, `movement_type`, `quantity`, `remarks`) VALUES (?, ?, ?, ?, ?)");
        $stmtMove->execute([$product_id, $movement_date, $movement_type, $quantity, $remarks]);

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

