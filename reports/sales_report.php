<?php
$currentPage = 'reports';
$pageTitle = 'Detailed Sales Analysis Report';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();

$startDate = trim($_GET['start_date'] ?? date('Y-06-01'));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

// Aggregate sales metrics
$stmtAgg = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT s.sale_id) AS total_bills,
        COALESCE(SUM(si.quantity), 0) AS total_qty_sold,
        COALESCE(SUM(si.quantity * si.unit_selling_price), 0) AS total_revenue,
        COALESCE(SUM(si.quantity * si.unit_cost_at_sale), 0) AS total_cogs
    FROM `Sales` s
    JOIN `Sale_Item` si ON s.sale_id = si.sale_id
    WHERE s.sale_date BETWEEN ? AND ?
");
$stmtAgg->execute([$startDate, $endDate]);
$summary = $stmtAgg->fetch();

$totalRevenue = (float)$summary['total_revenue'];
$totalCOGS = (float)$summary['total_cogs'];
$grossProfit = $totalRevenue - $totalCOGS;
$profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

// Product Performance Ranking
$stmtRank = $pdo->prepare("
    SELECT 
        p.product_id,
        p.product_name,
        c.category_name,
        p.unit,
        SUM(si.quantity) AS qty_sold,
        ROUND(SUM(si.quantity * si.unit_selling_price), 2) AS revenue,
        ROUND(SUM(si.quantity * si.unit_cost_at_sale), 2) AS cogs,
        ROUND(SUM(si.quantity * si.unit_selling_price) - SUM(si.quantity * si.unit_cost_at_sale), 2) AS profit
    FROM `Sales` s
    JOIN `Sale_Item` si ON s.sale_id = si.sale_id
    JOIN `Product` p ON si.product_id = p.product_id
    JOIN `Category` c ON p.category_id = c.category_id
    WHERE s.sale_date BETWEEN ? AND ?
    GROUP BY p.product_id, p.product_name, c.category_name, p.unit
    ORDER BY revenue DESC
");
$stmtRank->execute([$startDate, $endDate]);
$rankings = $stmtRank->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Detailed Sales Analysis & Revenue Register</h1>
        <p>Monitor counter sales revenue, volume trends, top product performance, and gross margins</p>
    </div>
</div>

<!-- Date Filter Form -->
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
            <button type="submit" class="btn btn-secondary">Filter Period</button>
        </form>
    </div>
</div>

<div class="metrics-grid">
    <div class="metric-card">
        <span class="metric-title">Total Customer Bills</span>
        <span class="metric-value"><?= (int)$summary['total_bills'] ?></span>
        <span class="metric-sub"><?= number_format((float)$summary['total_qty_sold'], 2) ?> Items Sold</span>
    </div>

    <div class="metric-card highlight-green">
        <span class="metric-title">Gross Sales Revenue</span>
        <span class="metric-value"><?= formatCurrency($totalRevenue) ?></span>
        <span class="metric-sub">Counter Receipts</span>
    </div>

    <div class="metric-card highlight-blue">
        <span class="metric-title">Cost of Goods Sold (COGS)</span>
        <span class="metric-value"><?= formatCurrency($totalCOGS) ?></span>
        <span class="metric-sub">Historical Inventory Cost</span>
    </div>

    <div class="metric-card highlight-green">
        <span class="metric-title">Gross Profit</span>
        <span class="metric-value"><?= formatCurrency($grossProfit) ?></span>
        <span class="metric-sub">Margin: <?= number_format($profitMargin, 1) ?>%</span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Product Sales Ranking (Period: <?= htmlspecialchars($startDate) ?> to <?= htmlspecialchars($endDate) ?>)</h2>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th class="text-right">Quantity Sold</th>
                    <th class="text-right">Revenue Generated</th>
                    <th class="text-right">Cost (COGS)</th>
                    <th class="text-right">Gross Profit</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rankings)): ?>
                    <tr><td colspan="7" class="text-center" style="padding: 2.5rem;">No sales records found within the selected period.</td></tr>
                <?php else: ?>
                    <?php 
                    $rank = 1;
                    foreach ($rankings as $r): 
                    ?>
                        <tr>
                            <td>#<?= $rank++ ?></td>
                            <td><strong><?= htmlspecialchars($r['product_name']) ?></strong></td>
                            <td><?= htmlspecialchars($r['category_name']) ?></td>
                            <td class="text-right font-weight-bold"><?= formatQuantity($r['qty_sold'], $r['unit']) ?></td>
                            <td class="text-right font-weight-bold" style="color: var(--primary);"><?= formatCurrency($r['revenue']) ?></td>
                            <td class="text-right text-muted"><?= formatCurrency($r['cogs']) ?></td>
                            <td class="text-right font-weight-bold profit-positive"><?= formatCurrency($r['profit']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

