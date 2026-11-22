<?php
$currentPage = 'sales';
$pageTitle = 'Record Counter Sale (Stock-Out)';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$error = '';

// Generate default bill number (e.g. BILL-YYYYMMDD-XXXX)
$todayStr = date('Ymd');
$randomSuffix = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
$defaultBillNumber = "BILL-{$todayStr}-{$randomSuffix}";

// Retrieve all grocery categories
$categories = $pdo->query("SELECT category_id, category_name FROM `Category` ORDER BY category_name ASC")->fetchAll();

// Retrieve all products with category info and physical stock
$products = $pdo->query("
    SELECT 
        p.product_id, 
        p.product_name, 
        p.category_id,
        c.category_name,
        p.unit, 
        p.selling_price, 
        COALESCE(i.quantity_available, 0) as quantity_available, 
        COALESCE(i.average_cost, 0) as average_cost
    FROM `Product` p
    JOIN `Category` c ON p.category_id = c.category_id
    LEFT JOIN `Inventory` i ON p.product_id = i.product_id
    ORDER BY c.category_name ASC, p.product_name ASC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_date = trim($_POST['sale_date'] ?? date('Y-m-d'));
    $bill_number = trim($_POST['bill_number'] ?? $defaultBillNumber);

    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['unit_selling_price'] ?? [];

    if ($bill_number === '') {
        $error = 'Bill / Invoice number is required.';
    } elseif (empty($product_ids)) {
        $error = 'Please add at least one product to the sales bill.';
    } else {
        $items = [];
        for ($i = 0; $i < count($product_ids); $i++) {
            $pid = (int)$product_ids[$i];
            $qty = (float)($quantities[$i] ?? 0);
            $prc = (float)($prices[$i] ?? 0);

            if ($pid > 0 && $qty > 0) {
                $items[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'unit_selling_price' => $prc
                ];
            }
        }

        if (empty($items)) {
            $error = 'Please enter valid positive quantities for items sold.';
        } else {
            // Execute Algorithm 3: Stock-Out Transaction
            $result = recordSaleTransaction($sale_date, $bill_number, $items);
            if ($result['success']) {
                $_SESSION['flash_success'] = "Sales bill {$bill_number} generated successfully! Stock balances deducted and historical cost snapshot saved.";
                header("Location: " . BASE_URL . "sales/sales.php?view=" . $result['sale_id']);
                exit;
            } else {
                $error = 'Transaction Rejection: ' . $result['error'];
            }
        }
    }
}
?>

<div class="page-header">
    <div class="page-title">
        <h1>Counter Sales Billing</h1>
        <p>Record customer sales, freeze point-in-time cost snapshots, and deduct physical stock balances</p>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>sales/sales.php" class="btn btn-outline">&larr; Back to Sales</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Grocery Counter POS Bill</h2>
    </div>
    <div class="card-body">
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="sale-form">
            <div class="form-grid" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label>Bill / Receipt Number <span class="required">*</span></label>
                    <input type="text" name="bill_number" class="form-control" required value="<?= htmlspecialchars($_POST['bill_number'] ?? $defaultBillNumber) ?>">
                </div>

                <div class="form-group">
                    <label>Billing Date <span class="required">*</span></label>
                    <input type="date" name="sale_date" class="form-control" required value="<?= htmlspecialchars($_POST['sale_date'] ?? date('Y-m-d')) ?>">
                </div>
            </div>

            <h3 style="font-size: 1.1rem; color: var(--primary); margin: 1.5rem 0 0.5rem 0; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                Customer Bill Line Items
            </h3>

            <div class="table-responsive">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Category</th>
                            <th style="width: 30%;">Grocery Item</th>
                            <th style="width: 10%;">Unit</th>
                            <th style="width: 13%;">Quantity Sold</th>
                            <th style="width: 13%;">Unit Selling Price (₹)</th>
                            <th style="width: 9%;" class="text-right">Subtotal</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody">
                        <tr class="item-row">
                            <td>
                                <select class="form-control category-select">
                                    <option value="">-- All Categories --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="product_id[]" class="form-control item-select" required>
                                    <option value="">-- Choose Product to Sell --</option>
                                    <?php 
                                    $currCat = null;
                                    foreach ($products as $p): 
                                        if ($p['category_name'] !== $currCat):
                                            if ($currCat !== null) echo '</optgroup>';
                                            $currCat = $p['category_name'];
                                            echo '<optgroup label="' . htmlspecialchars($currCat) . '">';
                                        endif;
                                    ?>
                                        <option value="<?= $p['product_id'] ?>" 
                                                data-category="<?= $p['category_id'] ?>"
                                                data-unit="<?= htmlspecialchars($p['unit']) ?>" 
                                                data-price="<?= $p['selling_price'] ?>" 
                                                data-stock="<?= $p['quantity_available'] ?>"
                                                <?= $p['quantity_available'] <= 0 ? 'disabled style="color:#adb5bd;"' : '' ?>>
                                            <?= htmlspecialchars($p['product_name']) ?> (Avail: <?= formatQuantity($p['quantity_available'], $p['unit']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if ($currCat !== null) echo '</optgroup>'; ?>
                                </select>
                                <span class="item-stock-hint" style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 2px;"></span>
                            </td>
                            <td class="item-unit" style="font-weight: 500; color: var(--text-muted);">-</td>
                            <td>
                                <input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control item-qty" required placeholder="0.000">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="unit_selling_price[]" class="form-control item-price" required placeholder="0.00">
                            </td>
                            <td class="item-subtotal text-right" style="font-weight: 600;">₹0.00</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline btn-sm remove-row-btn" title="Remove Row">&times;</button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7">
                                <button type="button" id="add-item-btn" class="btn btn-outline btn-sm">+ Add Another Grocery Item</button>
                            </td>
                        </tr>
                        <tr class="summary-table-highlight">
                            <td colspan="5" class="text-right" style="font-size: 1.05rem;">
                                <strong>Net Payable Bill Amount:</strong>
                            </td>
                            <td class="text-right font-weight-bold" style="font-size: 1.2rem; color: var(--success);" id="grand-total-amount">
                                ₹0.00
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">Complete Sale & Deduct Stock</button>
                <a href="<?= BASE_URL ?>sales/sales.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
window.ALL_SALE_PRODUCTS = <?= json_encode($products) ?>;
window.CATEGORIES = <?= json_encode($categories) ?>;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

