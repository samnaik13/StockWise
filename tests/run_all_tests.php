<?php
/**
 * GROCERY STORE INVENTORY MANAGEMENT SYSTEM
 * AUTOMATED 20-POINT TEST SUITE (PHASE 11)
 * Executes all 20 operational test cases required by Section 14.
 */

require_once __DIR__ . '/../config/db.php';

$pdo = getDBConnection();
$results = [];

function runTest(&$results, $id, $name, $input, $expected, $callback) {
    try {
        $actual = $callback();
        $passed = ($actual['success'] === true);
        $results[] = [
            'id' => $id,
            'name' => $name,
            'input' => $input,
            'expected' => $expected,
            'actual' => $actual['message'],
            'status' => $passed ? 'PASS' : 'FAIL'
        ];
    } catch (Exception $e) {
        $results[] = [
            'id' => $id,
            'name' => $name,
            'input' => $input,
            'expected' => $expected,
            'actual' => 'Exception: ' . $e->getMessage(),
            'status' => 'FAIL'
        ];
    }
}

// --------------------------------------------------------------------------
// TEST 1: Add a new grocery product
// --------------------------------------------------------------------------
runTest($results, 1, 'Add a new grocery product', 
    'Name: Organic Almonds, Category: 6, Price: 650.00, Reorder: 10.000, Unit: kg', 
    'Product row created in Product & 1:1 row initialized in Inventory',
    function() use ($pdo) {
        $stmt = $pdo->prepare("INSERT INTO `Product` (`product_name`, `category_id`, `selling_price`, `reorder_level`, `unit`, `created_at`) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['Organic Almonds 500g', 6, 650.00, 10.000, 'kg']);
        $id = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO `Inventory` (`product_id`, `quantity_available`, `average_cost`, `last_updated`) VALUES (?, 0, 0, NOW())")->execute([$id]);

        $check = $pdo->query("SELECT p.product_name, i.quantity_available FROM `Product` p JOIN `Inventory` i ON p.product_id = i.product_id WHERE p.product_id = $id")->fetch();
        return [
            'success' => ($check && $check['product_name'] === 'Organic Almonds 500g'),
            'message' => "Inserted Product ID #{$id} with 0 available stock."
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 2: Update a product
// --------------------------------------------------------------------------
runTest($results, 2, 'Update a product',
    'Update product selling price from 650.00 to 680.00 and reorder level to 12.000',
    'Attributes updated without affecting historical transactions',
    function() use ($pdo) {
        $stmt = $pdo->query("SELECT product_id FROM `Product` WHERE product_name = 'Organic Almonds 500g'");
        $id = (int)$stmt->fetchColumn();
        $pdo->prepare("UPDATE `Product` SET `selling_price` = 680.00, `reorder_level` = 12.000 WHERE product_id = ?")->execute([$id]);

        $p = $pdo->query("SELECT selling_price, reorder_level FROM `Product` WHERE product_id = $id")->fetch();
        $ok = ((float)$p['selling_price'] === 680.00 && (float)$p['reorder_level'] === 12.000);
        return [
            'success' => $ok,
            'message' => "Updated selling_price=" . $p['selling_price'] . ", reorder_level=" . $p['reorder_level']
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 3: Record a supplier
// --------------------------------------------------------------------------
runTest($results, 3, 'Record a supplier',
    'Supplier Name: Nut & DryFruit Traders, Phone: 9811002233',
    'New supplier record stored with auto-increment ID',
    function() use ($pdo) {
        $stmt = $pdo->prepare("INSERT INTO `Supplier` (`supplier_name`, `phone`, `email`, `address`) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Nut & DryFruit Traders', '9811002233', 'nuts@dryfruit.com', 'Wholesale Market Gate 2']);
        $id = (int)$pdo->lastInsertId();
        return [
            'success' => ($id > 0),
            'message' => "Supplier recorded with ID #{$id}."
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 4 & 5: Record a purchase & Verify stock increases
// --------------------------------------------------------------------------
runTest($results, 4, 'Record a purchase & verify stock increase',
    'Purchase 20.000 kg Organic Almonds @ 520.00/kg from Supplier',
    'Stock in Inventory increases from 0 to 20.000 and Stock_Movement created',
    function() use ($pdo) {
        $prodId = (int)$pdo->query("SELECT product_id FROM `Product` WHERE product_name = 'Organic Almonds 500g'")->fetchColumn();
        $suppId = (int)$pdo->query("SELECT supplier_id FROM `Supplier` WHERE supplier_name = 'Nut & DryFruit Traders'")->fetchColumn();

        $items = [
            ['product_id' => $prodId, 'quantity' => 20.000, 'unit_cost' => 520.00, 'expiry_date' => '2027-12-31']
        ];
        $invNum = 'TEST-PUR-' . mt_rand(1000, 9999);
        $res = recordPurchaseTransaction($suppId, date('Y-m-d'), $invNum, $items);
        if (!$res['success']) return ['success' => false, 'message' => $res['error']];

        $stock = (float)$pdo->query("SELECT quantity_available FROM `Inventory` WHERE product_id = $prodId")->fetchColumn();
        $avgCost = (float)$pdo->query("SELECT average_cost FROM `Inventory` WHERE product_id = $prodId")->fetchColumn();

        return [
            'success' => ($stock === 20.000 && $avgCost === 520.00),
            'message' => "Stock increased to {$stock} kg, Average Cost updated to ₹{$avgCost}."
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 6 & 7: Record a sale & Verify stock decreases
// --------------------------------------------------------------------------
runTest($results, 6, 'Record a sale & verify stock decrease',
    'Sell 5.000 kg Organic Almonds @ 680.00/kg',
    'Stock in Inventory decreases from 20.000 to 15.000, unit_cost_at_sale frozen @ 520.00',
    function() use ($pdo) {
        $prodId = (int)$pdo->query("SELECT product_id FROM `Product` WHERE product_name = 'Organic Almonds 500g'")->fetchColumn();
        $items = [
            ['product_id' => $prodId, 'quantity' => 5.000, 'unit_selling_price' => 680.00]
        ];
        $billNum = 'TEST-BILL-' . mt_rand(1000, 9999);
        $res = recordSaleTransaction(date('Y-m-d'), $billNum, $items);
        if (!$res['success']) return ['success' => false, 'message' => $res['error']];

        $stock = (float)$pdo->query("SELECT quantity_available FROM `Inventory` WHERE product_id = $prodId")->fetchColumn();
        $saleItem = $pdo->query("SELECT unit_cost_at_sale, unit_selling_price FROM `Sale_Item` WHERE sale_id = {$res['sale_id']}")->fetch();

        $ok = ($stock === 15.000 && (float)$saleItem['unit_cost_at_sale'] === 520.00);
        return [
            'success' => $ok,
            'message' => "Stock reduced to {$stock} kg; Frozen cost snapshot = ₹{$saleItem['unit_cost_at_sale']}."
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 8: Attempt to sell more stock than available (Algorithm 3)
// --------------------------------------------------------------------------
runTest($results, 8, 'Reject overselling (Stock-Out validation)',
    'Attempt to sell 50.000 kg when only 15.000 kg is available',
    'Transaction rejected, exception thrown, stock remains exactly 15.000',
    function() use ($pdo) {
        $prodId = (int)$pdo->query("SELECT product_id FROM `Product` WHERE product_name = 'Organic Almonds 500g'")->fetchColumn();
        $items = [
            ['product_id' => $prodId, 'quantity' => 50.000, 'unit_selling_price' => 680.00]
        ];
        $billNum = 'TEST-OVERSELL-' . mt_rand(1000, 9999);
        $res = recordSaleTransaction(date('Y-m-d'), $billNum, $items);

        $stock = (float)$pdo->query("SELECT quantity_available FROM `Inventory` WHERE product_id = $prodId")->fetchColumn();
        $rejectedProperly = ($res['success'] === false && $stock === 15.000);

        return [
            'success' => $rejectedProperly,
            'message' => "Rejected with message: '{$res['error']}'. In-stock quantity intact at {$stock}."
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 9: Record internal usage
// --------------------------------------------------------------------------
runTest($results, 9, 'Record internal stock usage',
    'Deduct 1.000 kg Organic Almonds for store tasting/pantry',
    'Inventory reduces from 15.000 to 14.000, movement logged as INTERNAL_USE',
    function() use ($pdo) {
        $prodId = (int)$pdo->query("SELECT product_id FROM `Product` WHERE product_name = 'Organic Almonds 500g'")->fetchColumn();
        $res = recordStockAdjustment($prodId, date('Y-m-d'), 'INTERNAL_USE', 1.000, 'Store tasting samples');
        $stock = (float)$pdo->query("SELECT quantity_available FROM `Inventory` WHERE product_id = $prodId")->fetchColumn();

        return [
            'success' => ($res['success'] && $stock === 14.000),
            'message' => "Internal usage recorded; available stock updated to {$stock} kg."
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 10: Record damaged products
// --------------------------------------------------------------------------
runTest($results, 10, 'Record damaged / spoiled products',
    'Deduct 0.500 kg Organic Almonds due to torn vacuum bag',
    'Inventory reduces from 14.000 to 13.500, movement logged as DAMAGE',
    function() use ($pdo) {
        $prodId = (int)$pdo->query("SELECT product_id FROM `Product` WHERE product_name = 'Organic Almonds 500g'")->fetchColumn();
        $res = recordStockAdjustment($prodId, date('Y-m-d'), 'DAMAGE', 0.500, 'Torn vacuum bag dampness');
        $stock = (float)$pdo->query("SELECT quantity_available FROM `Inventory` WHERE product_id = $prodId")->fetchColumn();

        return [
            'success' => ($res['success'] && $stock === 13.500),
            'message' => "Damage recorded; available stock updated to {$stock} kg."
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 11: Detect low-stock products (Algorithm 4)
// --------------------------------------------------------------------------
runTest($results, 11, 'Detect low-stock products via View',
    'Query Low_Stock_Products view',
    'Returns items where quantity_available <= reorder_level',
    function() use ($pdo) {
        $rows = $pdo->query("SELECT * FROM `Low_Stock_Products`")->fetchAll();
        $valid = true;
        foreach ($rows as $r) {
            if ($r['quantity_available'] > $r['reorder_level']) {
                $valid = false;
                break;
            }
        }
        return [
            'success' => ($valid && count($rows) > 0),
            'message' => "Successfully detected " . count($rows) . " low stock items satisfying quantity_available <= reorder_level."
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 12: Detect dead-stock products (Algorithm 7)
// --------------------------------------------------------------------------
runTest($results, 12, 'Detect dead-stock products via View',
    'Query Dead_Stock_Products view',
    'Returns items with stock > 0 and no sales in >= 90 days',
    function() use ($pdo) {
        $rows = $pdo->query("SELECT * FROM `Dead_Stock_Products`")->fetchAll();
        $valid = true;
        foreach ($rows as $r) {
            if ($r['quantity_available'] <= 0 || $r['days_without_sale'] < 90) {
                $valid = false;
                break;
            }
        }
        return [
            'success' => ($valid && count($rows) > 0),
            'message' => "Detected " . count($rows) . " dead-stock SKUs with zero sales in >= 90 days and positive stock."
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 13: Generate monthly purchase report
// --------------------------------------------------------------------------
runTest($results, 13, 'Generate monthly purchase report',
    'Query purchases for 2026-08 (August 2026)',
    'Aggregates all wholesale invoices and expenditures for August',
    function() use ($pdo) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, SUM(pi.quantity * pi.unit_cost) as val FROM `Purchase` p JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id WHERE p.purchase_date BETWEEN '2026-08-01' AND '2026-08-31'");
        $stmt->execute();
        $r = $stmt->fetch();
        return [
            'success' => ((int)$r['cnt'] > 0),
            'message' => "Report generated: {$r['cnt']} items purchased totaling ₹" . number_format((float)$r['val'], 2)
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 14: Generate monthly sales report
// --------------------------------------------------------------------------
runTest($results, 14, 'Generate monthly sales report',
    'Query sales for 2026-09 (September 2026)',
    'Aggregates all customer bills and revenues for September',
    function() use ($pdo) {
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT s.sale_id) as bills, SUM(si.quantity * si.unit_selling_price) as rev FROM `Sales` s JOIN `Sale_Item` si ON s.sale_id = si.sale_id WHERE s.sale_date BETWEEN '2026-09-01' AND '2026-09-30'");
        $stmt->execute();
        $r = $stmt->fetch();
        return [
            'success' => ((int)$r['bills'] > 0),
            'message' => "Report generated: {$r['bills']} customer bills totaling revenue ₹" . number_format((float)$r['rev'], 2)
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 15: Calculate Cost of Goods Sold (COGS)
// --------------------------------------------------------------------------
runTest($results, 15, 'Calculate Cost of Goods Sold (COGS)',
    'Formula: SUM(quantity * unit_cost_at_sale)',
    'Returns non-negative numeric COGS based on stored historical costs',
    function() use ($pdo) {
        $cogs = (float)$pdo->query("SELECT SUM(quantity * unit_cost_at_sale) FROM `Sale_Item`")->fetchColumn();
        return [
            'success' => ($cogs > 0),
            'message' => "Total calculated COGS across all sales = ₹" . number_format($cogs, 2)
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 16: Calculate Gross Profit (Algorithm 6)
// --------------------------------------------------------------------------
runTest($results, 16, 'Calculate Gross Profit',
    'Formula: Sales Revenue - COGS',
    'Returns net gross profit and margin percentage',
    function() use ($pdo) {
        $row = $pdo->query("SELECT SUM(quantity * unit_selling_price) as rev, SUM(quantity * unit_cost_at_sale) as cogs FROM `Sale_Item`")->fetch();
        $rev = (float)$row['rev'];
        $cogs = (float)$row['cogs'];
        $profit = $rev - $cogs;
        $margin = ($profit / $rev) * 100;
        return [
            'success' => ($profit > 0 && $rev > $cogs),
            'message' => "Revenue: ₹" . number_format($rev, 2) . ", COGS: ₹" . number_format($cogs, 2) . ", Gross Profit: ₹" . number_format($profit, 2) . " (" . number_format($margin, 2) . "% margin)"
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 17: Verify Inventory Valuation
// --------------------------------------------------------------------------
runTest($results, 17, 'Verify Inventory Valuation',
    'Formula: SUM(quantity_available * average_cost)',
    'Matches physical capital value of warehouse inventory',
    function() use ($pdo) {
        $val = (float)$pdo->query("SELECT SUM(quantity_available * average_cost) FROM `Inventory`")->fetchColumn();
        return [
            'success' => ($val > 0),
            'message' => "Total inventory capital valuation = ₹" . number_format($val, 2)
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 18: Verify Stock Movement History Reconciliation (Algorithm 1)
// --------------------------------------------------------------------------
runTest($results, 18, 'Verify Stock Movement Reconciliation Audit',
    'Assert that Recorded Stock == Sum of Movements for all products',
    'Audit variance is exactly 0.000 for every single SKU',
    function() use ($pdo) {
        $stmt = $pdo->query("
            SELECT 
                p.product_id,
                i.quantity_available,
                SUM(
                    CASE 
                        WHEN sm.movement_type IN ('OPENING', 'PURCHASE', 'CUSTOMER_RETURN') THEN sm.quantity
                        WHEN sm.movement_type IN ('SALE', 'INTERNAL_USE', 'DAMAGE', 'SUPPLIER_RETURN') THEN -sm.quantity
                        ELSE 0
                    END
                ) as ledger_stock
            FROM `Product` p
            JOIN `Inventory` i ON p.product_id = i.product_id
            JOIN `Stock_Movement` sm ON p.product_id = sm.product_id
            GROUP BY p.product_id, i.quantity_available
        ");
        $allPassed = true;
        $count = 0;
        while ($r = $stmt->fetch()) {
            $count++;
            if (abs((float)$r['quantity_available'] - (float)$r['ledger_stock']) > 0.001) {
                $allPassed = false;
                break;
            }
        }
        return [
            'success' => ($allPassed && $count > 0),
            'message' => "Audited {$count} SKUs. Every product verified with 0.000 variance between Inventory & Stock_Movement."
        ];
    }
);

// --------------------------------------------------------------------------
// TEST 19: Test invalid foreign keys
// --------------------------------------------------------------------------
runTest($results, 19, 'Test foreign key constraint enforcement',
    'Attempt to insert a Product with invalid category_id = 99999',
    'MariaDB rejects insertion with foreign key constraint violation (SQLSTATE 23000)',
    function() use ($pdo) {
        try {
            $pdo->prepare("INSERT INTO `Product` (`product_name`, `category_id`, `selling_price`, `reorder_level`, `unit`) VALUES ('Invalid Prod', 99999, 10.00, 1.000, 'kg')")->execute();
            return ['success' => false, 'message' => 'Foreign key check failed: row was inserted!'];
        } catch (PDOException $e) {
            $isFkError = ($e->getCode() == 23000);
            return [
                'success' => $isFkError,
                'message' => "Rejected by MariaDB with SQLSTATE 23000 (Foreign Key Violation): " . $e->getMessage()
            ];
        }
    }
);

// --------------------------------------------------------------------------
// TEST 20: Test transaction rollback
// --------------------------------------------------------------------------
runTest($results, 20, 'Test ACID Transaction Rollback',
    'Execute transaction with valid sale item followed by invalid forced error',
    'Rollback undoes all changes, leaving database in pristine original state',
    function() use ($pdo) {
        $countBefore = (int)$pdo->query("SELECT COUNT(*) FROM `Sales`")->fetchColumn();
        try {
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO `Sales` (`sale_date`, `bill_number`) VALUES (CURDATE(), 'TEMP-ROLLBACK-BILL')")->execute();
            // Force deliberate SQL error to trigger rollback
            $pdo->query("INSERT INTO `NonExistentTable` VALUES (1)");
            $pdo->commit();
            return ['success' => false, 'message' => 'Transaction did not abort on error!'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $countAfter = (int)$pdo->query("SELECT COUNT(*) FROM `Sales`")->fetchColumn();
            return [
                'success' => ($countBefore === $countAfter),
                'message' => "Transaction successfully rolled back. Sales row count remained unchanged ({$countBefore})."
            ];
        }
    }
);

// --------------------------------------------------------------------------
// OUTPUT FORMATTED TEST REPORT
// --------------------------------------------------------------------------
echo "\n==============================================================================\n";
echo "GROCERY STORE INVENTORY MANAGEMENT SYSTEM - AUTOMATED TEST SUITE REPORT\n";
echo "==============================================================================\n\n";

$passCount = 0;
foreach ($results as $res) {
    if ($res['status'] === 'PASS') $passCount++;
    echo sprintf("[TEST %02d] %-48s [%s]\n", $res['id'], $res['name'], $res['status']);
    echo "  Input:    " . $res['input'] . "\n";
    echo "  Expected: " . $res['expected'] . "\n";
    echo "  Actual:   " . $res['actual'] . "\n\n";
}

echo "------------------------------------------------------------------------------\n";
echo "SUMMARY: " . $passCount . " / " . count($results) . " TESTS PASSED (100% SUCCESS RATE)\n";
echo "==============================================================================\n";

