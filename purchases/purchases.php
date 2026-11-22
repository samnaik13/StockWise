<?php
$currentPage = 'purchases';
$pageTitle = 'Stock-In Purchase Orders';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();

// View single purchase order modal/panel
$viewPurchaseId = (int)($_GET['view'] ?? 0);
$selectedPurchase = null;
$purchaseItems = [];

if ($viewPurchaseId > 0) {
    $stmt = $pdo->prepare("
        SELECT p.*, s.supplier_name, s.phone, s.email 
        FROM `Purchase` p
        JOIN `Supplier` s ON p.supplier_id = s.supplier_id
        WHERE p.purchase_id = ?
    ");
    $stmt->execute([$viewPurchaseId]);
    $selectedPurchase = $stmt->fetch();

    if ($selectedPurchase) {
        $stmtItems = $pdo->prepare("
            SELECT pi.*, pr.product_name, pr.unit 
            FROM `Purchase_Item` pi
            JOIN `Product` pr ON pi.product_id = pr.product_id
            WHERE pi.purchase_id = ?
        ");
        $stmtItems->execute([$viewPurchaseId]);
        $purchaseItems = $stmtItems->fetchAll();
    }
}

// Fetch all purchases
$purchases = $pdo->query("
    SELECT 
        p.purchase_id,
        p.invoice_number,
        p.purchase_date,
        s.supplier_name,
        COUNT(pi.purchase_item_id) AS items_count,
        ROUND(SUM(pi.quantity * pi.unit_cost), 2) AS total_amount
    FROM `Purchase` p
    JOIN `Supplier` s ON p.supplier_id = s.supplier_id
    JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id
    GROUP BY p.purchase_id, p.invoice_number, p.purchase_date, s.supplier_name
    ORDER BY p.purchase_date DESC, p.purchase_id DESC
")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Wholesale Purchases & Stock-In Register</h1>
        <p>Record stock receipts from suppliers, track historical batch purchase costs, and update inventory</p>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>purchases/add_purchase.php" class="btn btn-primary">+ Record New Stock-In Batch</a>
    </div>
</div>

<?php if ($selectedPurchase): ?>
    <!-- Invoice Detail Inspection Panel -->
    <div class="card" style="border: 2px solid var(--primary); margin-bottom: 2rem;">
        <div class="card-header" style="background-color: var(--primary-subtle);">
            <div>
                <h2>Purchase Invoice: <?= htmlspecialchars($selectedPurchase['invoice_number']) ?></h2>
                <small>Supplier: <strong><?= htmlspecialchars($selectedPurchase['supplier_name']) ?></strong> | Date: <?= htmlspecialchars($selectedPurchase['purchase_date']) ?></small>
            </div>
            <a href="<?= BASE_URL ?>purchases/purchases.php" class="btn btn-outline btn-sm">Close Invoice</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-right">Quantity Received</th>
                        <th class="text-right">Purchase Unit Cost</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotal = 0;
                    foreach ($purchaseItems as $it): 
                        $sub = $it['quantity'] * $it['unit_cost'];
                        $grandTotal += $sub;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($it['product_name']) ?></strong></td>
                            <td class="text-right"><?= formatQuantity($it['quantity'], $it['unit']) ?></td>
                            <td class="text-right"><?= formatCurrency($it['unit_cost']) ?></td>
                            <td class="text-right font-weight-bold"><?= formatCurrency($sub) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="summary-table-highlight">
                        <td colspan="3" class="text-right"><strong>Total Invoice Expenditure:</strong></td>
                        <td class="text-right font-weight-bold" style="font-size: 1.1rem; color: var(--primary);">
                            <?= formatCurrency($grandTotal) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Purchase Register Table -->
<div class="card">
    <div class="card-header">
        <h2>Stock-In Transaction History (<?= count($purchases) ?> invoices)</h2>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice Number</th>
                    <th>Supplier</th>
                    <th class="text-center">Items Received</th>
                    <th class="text-right">Total Purchase Cost</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchases)): ?>
                    <tr><td colspan="6" class="text-center" style="padding: 2.5rem;">No purchase transactions recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($purchases as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['purchase_date']) ?></td>
                            <td><strong><?= htmlspecialchars($p['invoice_number']) ?></strong></td>
                            <td><?= htmlspecialchars($p['supplier_name']) ?></td>
                            <td class="text-center"><span class="badge badge-secondary"><?= $p['items_count'] ?> products</span></td>
                            <td class="text-right font-weight-bold"><?= formatCurrency($p['total_amount']) ?></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>purchases/purchases.php?view=<?= $p['purchase_id'] ?>" class="btn btn-outline btn-sm">Inspect Items</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

