<?php
$currentPage = 'reports';
$pageTitle = 'Low Stock Reorder Alerts';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();

// Fetch low stock items using Database View 1: Low_Stock_Products
$items = $pdo->query("SELECT * FROM `Low_Stock_Products`")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Low Stock Reorder Alerts</h1>
        <p>Products whose physical available stock has fallen below or reached their minimum reorder threshold</p>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>purchases/add_purchase.php" class="btn btn-primary">+ Create Purchase Order</a>
    </div>
</div>

<div class="card">
    <div class="card-header" style="background-color: var(--warning-bg); border-bottom: 2px solid #ffe0b2;">
        <h2 style="color: var(--warning); display: flex; align-items: center; gap: 0.5rem;">
            <span>⚠</span> Stock Replenishment Action Required (<?= count($items) ?> SKUs)
        </h2>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th class="text-right">Available Stock</th>
                    <th class="text-right">Reorder Threshold</th>
                    <th class="text-right">Replenishment Shortage</th>
                    <th>Unit</th>
                    <th>Condition</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="9" class="text-center" style="padding: 2.5rem; color: var(--success); font-weight: 600;">✓ Excellent! All grocery products currently have adequate stock levels above their reorder thresholds.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td>#<?= $it['product_id'] ?></td>
                            <td><strong><?= htmlspecialchars($it['product_name']) ?></strong></td>
                            <td><?= htmlspecialchars($it['category_name']) ?></td>
                            <td class="text-right" style="font-weight: 700; color: <?= $it['quantity_available'] == 0 ? 'var(--danger)' : 'var(--warning)' ?>; font-size: 1rem;">
                                <?= formatQuantity($it['quantity_available']) ?>
                            </td>
                            <td class="text-right font-weight-bold"><?= formatQuantity($it['reorder_level']) ?></td>
                            <td class="text-right" style="color: var(--danger); font-weight: 700;">
                                <?= formatQuantity($it['shortage_quantity']) ?>
                            </td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($it['unit']) ?></span></td>
                            <td>
                                <span class="badge <?= $it['quantity_available'] == 0 ? 'badge-danger' : 'badge-warning' ?>">
                                    <?= htmlspecialchars($it['stock_condition']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>purchases/add_purchase.php" class="btn btn-primary btn-sm">Restock Item</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

