<?php
$currentPage = 'reports';
$pageTitle = 'Wholesale Purchase Report';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$userId = getCurrentUserId();

$startDate = trim($_GET['start_date'] ?? date('Y-06-01'));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));
$supplierId = (int)($_GET['supplier_id'] ?? 0);

$query = "
    SELECT 
        p.purchase_id,
        p.invoice_number,
        p.purchase_date,
        s.supplier_name,
        COUNT(pi.purchase_item_id) AS items_count,
        SUM(pi.quantity) AS total_units,
        ROUND(SUM(pi.quantity * pi.unit_cost), 2) AS invoice_amount
    FROM `Purchase` p
    JOIN `Supplier` s ON p.supplier_id = s.supplier_id
    JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id
    WHERE p.user_id = ? AND p.purchase_date BETWEEN ? AND ?
";
$params = [$userId, $startDate, $endDate];

if ($supplierId > 0) {
    $query .= " AND p.supplier_id = ?";
    $params[] = $supplierId;
}

$query .= " GROUP BY p.purchase_id, p.invoice_number, p.purchase_date, s.supplier_name ORDER BY p.purchase_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$purchases = $stmt->fetchAll();

$totalInvoices = count($purchases);
$totalSpend = 0;
$totalUnits = 0;
foreach ($purchases as $p) {
    $totalSpend += (float)$p['invoice_amount'];
    $totalUnits += (float)$p['total_units'];
}

$stmtSupp = $pdo->prepare("SELECT supplier_id, supplier_name FROM `Supplier` WHERE user_id = ? ORDER BY supplier_name ASC");
$stmtSupp->execute([$userId]);
$suppliers = $stmtSupp->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Wholesale Procurement & Spend Register</h1>
        <p>Audit supplier invoices, inventory inflow volumes, and working capital cash outflows</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body" style="padding: 1rem 1.25rem;">
        <form method="GET" action="" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div>
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 0.3rem;">From Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div>
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 0.3rem;">To Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div style="min-width: 220px;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 0.3rem;">Filter by Supplier</label>
                <select name="supplier_id" class="form-control">
                    <option value="0">-- All Suppliers --</option>
                    <?php foreach ($suppliers as $sp): ?>
                        <option value="<?= $sp['supplier_id'] ?>" <?= $supplierId === (int)$sp['supplier_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sp['supplier_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filter Register</button>
        </form>
    </div>
</div>

<div class="metrics-grid">
    <div class="metric-card">
        <span class="metric-title">Procurement Invoices</span>
        <span class="metric-value"><?= $totalInvoices ?></span>
        <span class="metric-sub">Wholesale Batches</span>
    </div>

    <div class="metric-card highlight-blue">
        <span class="metric-title">Physical Units Received</span>
        <span class="metric-value"><?= number_format($totalUnits, 2) ?></span>
        <span class="metric-sub">Across All Grocery Batches</span>
    </div>

    <div class="metric-card highlight-green">
        <span class="metric-title">Total Procurement Outflow</span>
        <span class="metric-value"><?= formatCurrency($totalSpend) ?></span>
        <span class="metric-sub">Wholesale Purchase Spend</span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Procurement Invoices Register</h2>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice Number</th>
                    <th>Supplier</th>
                    <th class="text-center">Products Count</th>
                    <th class="text-right">Total Units</th>
                    <th class="text-right">Total Invoice Value</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchases)): ?>
                    <tr><td colspan="7" class="text-center" style="padding: 2.5rem;">No purchases found for the selected criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($purchases as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['purchase_date']) ?></td>
                            <td><strong><?= htmlspecialchars($p['invoice_number']) ?></strong></td>
                            <td><?= htmlspecialchars($p['supplier_name']) ?></td>
                            <td class="text-center"><span class="badge badge-secondary"><?= $p['items_count'] ?> products</span></td>
                            <td class="text-right font-weight-bold"><?= number_format($p['total_units'], 2) ?></td>
                            <td class="text-right font-weight-bold" style="color: var(--primary);"><?= formatCurrency($p['invoice_amount']) ?></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>purchases/purchases.php?view=<?= $p['purchase_id'] ?>" class="btn btn-outline btn-sm">Inspect Batch</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

