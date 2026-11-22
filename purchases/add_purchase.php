<?php
$currentPage = 'purchases';
$pageTitle = 'Record Purchase (Stock-In)';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$error = '';

$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM `Supplier` ORDER BY supplier_name ASC")->fetchAll();
$products = $pdo->query("
    SELECT p.product_id, p.product_name, p.unit, p.selling_price, p.supplier_id,
           COALESCE(i.quantity_available, 0) as quantity_available, 
           COALESCE(i.average_cost, 0) as average_cost
    FROM `Product` p
    LEFT JOIN `Inventory` i ON p.product_id = i.product_id
    ORDER BY p.product_name ASC
")->fetchAll();

// Build mapping of supplier_id => array of product_ids (both primary vendor & past purchase history)
$supplierProductMap = [];
foreach ($products as $p) {
    if (!empty($p['supplier_id'])) {
        $supplierProductMap[(int)$p['supplier_id']][] = (int)$p['product_id'];
    }
}
$histStmt = $pdo->query("SELECT DISTINCT pu.supplier_id, pi.product_id FROM `Purchase` pu JOIN `Purchase_Item` pi ON pu.purchase_id = pi.purchase_id");
while ($row = $histStmt->fetch()) {
    $sid = (int)$row['supplier_id'];
    $pid = (int)$row['product_id'];
    if (!isset($supplierProductMap[$sid])) {
        $supplierProductMap[$sid] = [];
    }
    if (!in_array($pid, $supplierProductMap[$sid])) {
        $supplierProductMap[$sid][] = $pid;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = (int)($_POST['supplier_id'] ?? 0);
    $purchase_date = trim($_POST['purchase_date'] ?? date('Y-m-d'));
    $invoice_number = trim($_POST['invoice_number'] ?? '');

    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unit_costs = $_POST['unit_cost'] ?? [];

    if ($supplier_id <= 0) {
        $error = 'Please select a wholesale supplier.';
    } elseif ($invoice_number === '') {
        $error = 'Supplier invoice number is required.';
    } elseif (empty($product_ids)) {
        $error = 'At least one product line item is required.';
    } else {
        $items = [];
        for ($i = 0; $i < count($product_ids); $i++) {
            $pid = (int)$product_ids[$i];
            $qty = (float)($quantities[$i] ?? 0);
            $cost = (float)($unit_costs[$i] ?? 0);

            if ($pid > 0 && $qty > 0) {
                $items[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'expiry_date' => null
                ];
            }
        }

        if (empty($items)) {
            $error = 'Please enter valid positive quantities for the purchase items.';
        } else {
            $result = recordPurchaseTransaction($supplier_id, $purchase_date, $invoice_number, $items);
            if ($result['success']) {
                $_SESSION['flash_success'] = "Purchase invoice {$invoice_number} recorded successfully! Inventory balances and Weighted Average Costs have been updated.";
                header("Location: " . BASE_URL . "purchases/purchases.php?view=" . $result['purchase_id']);
                exit;
            } else {
                $error = 'Transaction Error: ' . $result['error'];
            }
        }
    }
}
?>

<div class="page-header">
    <div class="page-title">
        <h1>Record New Stock-In Batch</h1>
        <p>Receiving inventory from supplier, updating physical stock balances & recalculating Weighted Average Cost</p>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>purchases/purchases.php" class="btn btn-outline">&larr; Back to Purchases</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Wholesale Purchase Invoice Form</h2>
    </div>
    <div class="card-body">
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="purchase-form">
            <div class="form-grid" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label>Wholesale Supplier <span class="required">*</span></label>
                    <select name="supplier_id" id="wholesale-supplier-select" class="form-control" required>
                        <option value="">-- Select Wholesale Supplier --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['supplier_id'] ?>" <?= ((int)($_POST['supplier_id'] ?? 0) === (int)$s['supplier_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['supplier_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Invoice Number <span class="required">*</span></label>
                    <input type="text" name="invoice_number" class="form-control" required placeholder="e.g. INV-2026-9901" value="<?= htmlspecialchars($_POST['invoice_number'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Purchase Date <span class="required">*</span></label>
                    <input type="date" name="purchase_date" class="form-control" required value="<?= htmlspecialchars($_POST['purchase_date'] ?? date('Y-m-d')) ?>">
                </div>
            </div>

            <h3 style="font-size: 1.1rem; color: var(--primary); margin: 1.5rem 0 0.5rem 0; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                Stock-In Line Items (Multi-Product Receipt)
            </h3>

            <div class="table-responsive">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Product</th>
                            <th style="width: 10%;">Unit</th>
                            <th style="width: 20%;">Quantity</th>
                            <th style="width: 18%;">Unit Cost (₹)</th>
                            <th style="width: 7%;" class="text-right">Subtotal</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody">
                        <tr class="item-row">
                            <td>
                                <select name="product_id[]" class="form-control item-select" required>
                                    <option value="">-- Choose Product --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['product_id'] ?>" 
                                                data-unit="<?= htmlspecialchars($p['unit']) ?>" 
                                                data-price="<?= $p['average_cost'] ?>" 
                                                data-stock="<?= $p['quantity_available'] ?>">
                                            <?= htmlspecialchars($p['product_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="item-unit" style="font-weight: 500; color: var(--text-muted);">-</td>
                            <td>
                                <input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control item-qty" required placeholder="0.000">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="unit_cost[]" class="form-control item-price" required placeholder="0.00">
                            </td>
                            <td class="item-subtotal text-right" style="font-weight: 600;">₹0.00</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline btn-sm remove-row-btn" title="Remove Item">&times;</button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                                    <button type="button" id="add-item-btn" class="btn btn-outline btn-sm">+ Add Another Item</button>
                                    <label style="font-size: 0.85rem; color: var(--text-muted); cursor: pointer; user-select: none; display: inline-flex; align-items: center; gap: 0.4rem;">
                                        <input type="checkbox" id="show-all-products-toggle" style="margin: 0;">
                                        <span>Show all store items (override wholesaler filter)</span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr class="summary-table-highlight">
                            <td colspan="4" class="text-right" style="font-size: 1.05rem;">
                                <strong>Total Batch Purchase Expenditure:</strong>
                            </td>
                            <td class="text-right font-weight-bold" style="font-size: 1.15rem; color: var(--primary);" id="grand-total-amount">
                                ₹0.00
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Commit Stock-In Transaction</button>
                <a href="<?= BASE_URL ?>purchases/purchases.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
window.ALL_PRODUCTS = <?= json_encode($products) ?>;
window.SUPPLIER_PRODUCT_MAP = <?= json_encode($supplierProductMap) ?>;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

