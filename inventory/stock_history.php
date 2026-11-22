<?php
$currentPage = 'inventory';
$pageTitle = 'Stock Movement Audit History';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();

// Filters
$productId = (int)($_GET['product_id'] ?? 0);
$movementType = trim($_GET['movement_type'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

$query = "
    SELECT 
        sm.movement_id,
        sm.movement_date,
        sm.movement_type,
        sm.quantity,
        sm.remarks,
        p.product_id,
        p.product_name,
        p.unit,
        c.category_name
    FROM `Stock_Movement` sm
    JOIN `Product` p ON sm.product_id = p.product_id
    JOIN `Category` c ON p.category_id = c.category_id
    WHERE 1=1
";
$params = [];

if ($productId > 0) {
    $query .= " AND sm.product_id = ?";
    $params[] = $productId;
}

if ($movementType !== '') {
    $query .= " AND sm.movement_type = ?";
    $params[] = $movementType;
}

if ($startDate !== '') {
    $query .= " AND sm.movement_date >= ?";
    $params[] = $startDate;
}

if ($endDate !== '') {
    $query .= " AND sm.movement_date <= ?";
    $params[] = $endDate;
}

$query .= " ORDER BY sm.movement_date DESC, sm.movement_id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$movements = $stmt->fetchAll();

// Product list for filter dropdown
$products = $pdo->query("SELECT product_id, product_name FROM `Product` ORDER BY product_name ASC")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Stock Movement Audit Trail (Ledger)</h1>
        <p>Immutable chronological history of all stock additions, deductions, sales, damages, and returns</p>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>inventory/stock_adjustment.php" class="btn btn-warning">+ Record Adjustment</a>
        <a href="<?= BASE_URL ?>inventory/inventory.php" class="btn btn-outline">&larr; Current Inventory</a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body" style="padding: 1rem 1.25rem;">
        <form method="GET" action="" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 0.85rem; font-weight: 500; display: block; margin-bottom: 0.3rem;">Filter by Product</label>
                <select name="product_id" class="form-control">
                    <option value="0">-- All Products --</option>
                    <?php foreach ($products as $pr): ?>
                        <option value="<?= $pr['product_id'] ?>" <?= $productId === (int)$pr['product_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pr['product_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="width: 180px;">
                <label style="font-size: 0.85rem; font-weight: 500; display: block; margin-bottom: 0.3rem;">Movement Type</label>
                <select name="movement_type" class="form-control">
                    <option value="">-- All Types --</option>
                    <option value="OPENING" <?= $movementType === 'OPENING' ? 'selected' : '' ?>>OPENING</option>
                    <option value="PURCHASE" <?= $movementType === 'PURCHASE' ? 'selected' : '' ?>>PURCHASE</option>
                    <option value="SALE" <?= $movementType === 'SALE' ? 'selected' : '' ?>>SALE</option>
                    <option value="INTERNAL_USE" <?= $movementType === 'INTERNAL_USE' ? 'selected' : '' ?>>INTERNAL_USE</option>
                    <option value="DAMAGE" <?= $movementType === 'DAMAGE' ? 'selected' : '' ?>>DAMAGE</option>
                    <option value="CUSTOMER_RETURN" <?= $movementType === 'CUSTOMER_RETURN' ? 'selected' : '' ?>>CUSTOMER_RETURN</option>
                    <option value="SUPPLIER_RETURN" <?= $movementType === 'SUPPLIER_RETURN' ? 'selected' : '' ?>>SUPPLIER_RETURN</option>
                </select>
            </div>

            <div style="width: 150px;">
                <label style="font-size: 0.85rem; font-weight: 500; display: block; margin-bottom: 0.3rem;">From Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>

            <div style="width: 150px;">
                <label style="font-size: 0.85rem; font-weight: 500; display: block; margin-bottom: 0.3rem;">To Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
            </div>

            <button type="submit" class="btn btn-secondary">Filter</button>
            <?php if ($productId > 0 || $movementType !== '' || $startDate !== '' || $endDate !== ''): ?>
                <a href="<?= BASE_URL ?>inventory/stock_history.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Stock Movement Log (<?= count($movements) ?> entries)</h2>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Log #</th>
                    <th>Date</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Movement Type</th>
                    <th class="text-center">Impact</th>
                    <th class="text-right">Quantity</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr><td colspan="8" class="text-center" style="padding: 2.5rem;">No stock movements match the selected filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): 
                        $isPositive = in_array($m['movement_type'], ['OPENING', 'PURCHASE', 'CUSTOMER_RETURN']);
                        $badgeCls = 'badge-secondary';
                        if ($m['movement_type'] === 'OPENING' || $m['movement_type'] === 'PURCHASE') $badgeCls = 'badge-success';
                        elseif ($m['movement_type'] === 'SALE') $badgeCls = 'badge-info';
                        elseif ($m['movement_type'] === 'DAMAGE') $badgeCls = 'badge-danger';
                        elseif ($m['movement_type'] === 'INTERNAL_USE') $badgeCls = 'badge-warning';
                    ?>
                        <tr>
                            <td>#<?= $m['movement_id'] ?></td>
                            <td><?= htmlspecialchars($m['movement_date']) ?></td>
                            <td><strong><?= htmlspecialchars($m['product_name']) ?></strong></td>
                            <td><?= htmlspecialchars($m['category_name']) ?></td>
                            <td><span class="badge <?= $badgeCls ?>"><?= htmlspecialchars($m['movement_type']) ?></span></td>
                            <td class="text-center">
                                <span style="font-weight: 700; color: <?= $isPositive ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <?= $isPositive ? '+ ADD' : '- DEDUCT' ?>
                                </span>
                            </td>
                            <td class="text-right" style="font-weight: 700; color: <?= $isPositive ? 'var(--success)' : 'var(--danger)' ?>;">
                                <?= $isPositive ? '+' : '-' ?><?= formatQuantity($m['quantity'], $m['unit']) ?>
                            </td>
                            <td><small class="text-muted"><?= htmlspecialchars($m['remarks'] ?? '-') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

