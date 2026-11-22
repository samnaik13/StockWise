<?php
$currentPage = 'reports';
$pageTitle = 'Dead Stock Detection';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();

// Allow configurable threshold (default 90 days as required by Algorithm 7)
$thresholdDays = (int)($_GET['threshold'] ?? 90);
if ($thresholdDays < 1) $thresholdDays = 90;

// Parameterized Algorithm 7 query allowing custom threshold
$stmt = $pdo->prepare("
    SELECT 
        p.product_id,
        p.product_name,
        c.category_name,
        i.quantity_available,
        p.unit,
        i.average_cost,
        ROUND(i.quantity_available * i.average_cost, 2) AS tied_up_capital,
        sales_info.last_sale_date,
        stock_info.first_stock_date,
        COALESCE(sales_info.last_sale_date, stock_info.first_stock_date) AS reference_date,
        DATEDIFF(CURRENT_DATE(), COALESCE(sales_info.last_sale_date, stock_info.first_stock_date)) AS days_without_sale,
        CASE 
            WHEN sales_info.last_sale_date IS NULL THEN 'Never Sold Since Initial Receipt'
            ELSE 'No Customer Sales in Stagnant Period'
        END AS classification_reason
    FROM `Product` p
    JOIN `Category` c ON p.category_id = c.category_id
    JOIN `Inventory` i ON p.product_id = i.product_id
    LEFT JOIN (
        SELECT 
            si.product_id, 
            MAX(s.sale_date) AS last_sale_date
        FROM `Sale_Item` si
        JOIN `Sales` s ON si.sale_id = s.sale_id
        GROUP BY si.product_id
    ) sales_info ON p.product_id = sales_info.product_id
    LEFT JOIN (
        SELECT 
            product_id, 
            MIN(movement_date) AS first_stock_date
        FROM `Stock_Movement`
        WHERE movement_type IN ('OPENING', 'PURCHASE')
        GROUP BY product_id
    ) stock_info ON p.product_id = stock_info.product_id
    WHERE i.quantity_available > 0
      AND (
          (sales_info.last_sale_date IS NOT NULL AND DATEDIFF(CURRENT_DATE(), sales_info.last_sale_date) >= ?)
          OR 
          (sales_info.last_sale_date IS NULL AND stock_info.first_stock_date IS NOT NULL AND DATEDIFF(CURRENT_DATE(), stock_info.first_stock_date) >= ?)
      )
    ORDER BY days_without_sale DESC
");
$stmt->execute([$thresholdDays, $thresholdDays]);
$deadStockItems = $stmt->fetchAll();

$totalTiedUpCapital = 0;
foreach ($deadStockItems as $it) {
    $totalTiedUpCapital += (float)$it['tied_up_capital'];
}
?>

<div class="page-header">
    <div class="page-title">
        <h1>Dead Stock Detection</h1>
        <p>Identify unsold inventory tying up shelf space and store working capital for 90 days or more</p>
    </div>
</div>

<!-- Threshold Selector -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body" style="padding: 1rem 1.25rem;">
        <form method="GET" action="" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div>
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 0.3rem;">Stagnation Threshold (Days Without Sale)</label>
                <select name="threshold" class="form-control" style="width: 220px;">
                    <option value="30" <?= $thresholdDays === 30 ? 'selected' : '' ?>>30 Days (Fast Stagnation)</option>
                    <option value="60" <?= $thresholdDays === 60 ? 'selected' : '' ?>>60 Days (Moderate Stagnation)</option>
                    <option value="90" <?= $thresholdDays === 90 ? 'selected' : '' ?>>90 Days (Standard Dead Stock)</option>
                    <option value="120" <?= $thresholdDays === 120 ? 'selected' : '' ?>>120 Days (Severe Stagnation)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Apply Threshold Filter</button>
        </form>
    </div>
</div>

<div class="metrics-grid">
    <div class="metric-card highlight-red">
        <span class="metric-title">Detected Dead-Stock Items</span>
        <span class="metric-value"><?= count($deadStockItems) ?> SKUs</span>
        <span class="metric-sub">With Threshold &ge; <?= $thresholdDays ?> Days</span>
    </div>

    <div class="metric-card highlight-orange">
        <span class="metric-title">Blocked / Tied-Up Working Capital</span>
        <span class="metric-value"><?= formatCurrency($totalTiedUpCapital) ?></span>
        <span class="metric-sub">Capital Tied in Stagnant Stock</span>
    </div>
</div>

<div class="card">
    <div class="card-header" style="background-color: var(--danger-bg); border-bottom: 2px solid #ffcdd2;">
        <h2 style="color: var(--danger);">Dead Stock Report (&ge; <?= $thresholdDays ?> Days Inactive)</h2>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th class="text-right">Unsold Available Stock</th>
                    <th class="text-right">Avg Unit Cost</th>
                    <th class="text-right">Tied-Up Working Capital</th>
                    <th>Last Sale / Inflow Date</th>
                    <th class="text-center">Days Without Sale</th>
                    <th>Classification Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deadStockItems)): ?>
                    <tr><td colspan="9" class="text-center" style="padding: 2.5rem; color: var(--success); font-weight: 600;">✓ Excellent! No grocery products meet the dead stock criteria for <?= $thresholdDays ?> days.</td></tr>
                <?php else: ?>
                    <?php foreach ($deadStockItems as $ds): ?>
                        <tr>
                            <td>#<?= $ds['product_id'] ?></td>
                            <td><strong><?= htmlspecialchars($ds['product_name']) ?></strong></td>
                            <td><?= htmlspecialchars($ds['category_name']) ?></td>
                            <td class="text-right font-weight-bold"><?= formatQuantity($ds['quantity_available'], $ds['unit']) ?></td>
                            <td class="text-right text-muted"><?= formatCurrency($ds['average_cost']) ?></td>
                            <td class="text-right font-weight-bold" style="color: var(--danger);">
                                <?= formatCurrency($ds['tied_up_capital']) ?>
                            </td>
                            <td>
                                <div><strong><?= htmlspecialchars($ds['reference_date']) ?></strong></div>
                                <small class="text-muted">
                                    <?= $ds['last_sale_date'] ? 'Last Sale Date' : 'First Stock Date' ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-danger" style="font-size: 0.85rem;">
                                    <?= $ds['days_without_sale'] ?> days
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    <?= htmlspecialchars($ds['classification_reason']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="summary-table-highlight">
                    <td colspan="5" class="text-right"><strong>Total Blocked Capital in Dead Stock:</strong></td>
                    <td class="text-right font-weight-bold" style="font-size: 1.15rem; color: var(--danger);">
                        <?= formatCurrency($totalTiedUpCapital) ?>
                    </td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

