<?php
$currentPage = 'dashboard';
$pageTitle = 'StockWise Dashboard';
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Fetch Core Dashboard Metrics
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM `Product`")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM `Category`")->fetchColumn();
$totalSuppliers = (int)$pdo->query("SELECT COUNT(*) FROM `Supplier`")->fetchColumn();
$totalInventoryQty = (float)$pdo->query("SELECT COALESCE(SUM(quantity_available), 0) FROM `Inventory`")->fetchColumn();
$totalInventoryVal = (float)$pdo->query("SELECT COALESCE(SUM(quantity_available * average_cost), 0) FROM `Inventory`")->fetchColumn();

// View Metrics
$lowStockCount = (int)$pdo->query("SELECT COUNT(*) FROM `Low_Stock_Products`")->fetchColumn();
$deadStockCount = (int)$pdo->query("SELECT COUNT(*) FROM `Dead_Stock_Products`")->fetchColumn();

// Current Month Metrics (October 2026 or current calendar month)
$currentMonth = date('Y-m');
$stmtSales = $pdo->prepare("SELECT COALESCE(SUM(si.quantity * si.unit_selling_price), 0) FROM `Sales` s JOIN `Sale_Item` si ON s.sale_id = si.sale_id WHERE DATE_FORMAT(s.sale_date, '%Y-%m') = ?");
$stmtSales->execute([$currentMonth]);
$monthlySalesRevenue = (float)$stmtSales->fetchColumn();

$stmtPurchases = $pdo->prepare("SELECT COALESCE(SUM(pi.quantity * pi.unit_cost), 0) FROM `Purchase` p JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id WHERE DATE_FORMAT(p.purchase_date, '%Y-%m') = ?");
$stmtPurchases->execute([$currentMonth]);
$monthlyPurchaseExpenditure = (float)$stmtPurchases->fetchColumn();

// Recent Stock Movements (Audit Trail Preview)
$recentMovements = $pdo->query("
    SELECT sm.movement_id, sm.movement_date, sm.movement_type, sm.quantity, sm.remarks, p.product_name, p.unit
    FROM `Stock_Movement` sm
    JOIN `Product` p ON sm.product_id = p.product_id
    ORDER BY sm.movement_id DESC
    LIMIT 6
")->fetchAll();

// Low Stock Preview
$lowStockItems = $pdo->query("
    SELECT product_id, product_name, category_name, quantity_available, reorder_level, unit, stock_condition
    FROM `Low_Stock_Products`
    LIMIT 5
")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1>StockWise Inventory Dashboard</h1>
        <p>Operational Overview &bull; Month: <strong><?= date('F Y') ?></strong></p>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>sales/add_sale.php" class="btn btn-success">+ New Customer Bill</a>
        <a href="<?= BASE_URL ?>purchases/add_purchase.php" class="btn btn-primary">+ Record Stock-In</a>
    </div>
</div>

<!-- 8 Core Metrics Grid -->
<div class="metrics-grid">
    <div class="metric-card">
        <span class="metric-title">Catalog Products</span>
        <span class="metric-value"><?= $totalProducts ?></span>
        <span class="metric-sub"><?= $totalCategories ?> Grocery Categories</span>
    </div>

    <div class="metric-card">
        <span class="metric-title">Wholesale Suppliers</span>
        <span class="metric-value"><?= $totalSuppliers ?></span>
        <span class="metric-sub">Active Vendor Network</span>
    </div>

    <div class="metric-card highlight-blue">
        <span class="metric-title">Total Stock Physical Units</span>
        <span class="metric-value"><?= number_format($totalInventoryQty, 2) ?></span>
        <span class="metric-sub">Across All Grocery Units</span>
    </div>

    <div class="metric-card highlight-green">
        <span class="metric-title">Current Inventory Valuation</span>
        <span class="metric-value"><?= formatCurrency($totalInventoryVal) ?></span>
        <span class="metric-sub">Weighted Average Cost Method</span>
    </div>

    <div class="metric-card highlight-orange">
        <span class="metric-title">Low Stock Alerts</span>
        <span class="metric-value"><?= $lowStockCount ?></span>
        <span class="metric-sub">
            <a href="<?= BASE_URL ?>reports/low_stock.php" style="color:inherit;text-decoration:underline;">Stock &le; Reorder Level</a>
        </span>
    </div>

    <div class="metric-card highlight-red">
        <span class="metric-title">Dead Stock SKUs</span>
        <span class="metric-value"><?= $deadStockCount ?></span>
        <span class="metric-sub">
            <a href="<?= BASE_URL ?>reports/dead_stock.php" style="color:inherit;text-decoration:underline;">Unsold &ge; 90 Days</a>
        </span>
    </div>

    <div class="metric-card highlight-green">
        <span class="metric-title">Monthly Sales Revenue</span>
        <span class="metric-value"><?= formatCurrency($monthlySalesRevenue) ?></span>
        <span class="metric-sub">Month: <?= date('M Y') ?></span>
    </div>

    <div class="metric-card">
        <span class="metric-title">Monthly Purchases</span>
        <span class="metric-value"><?= formatCurrency($monthlyPurchaseExpenditure) ?></span>
        <span class="metric-sub">Stock Procurement Expense</span>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 1.5rem;">
    <!-- Low Stock Alert Panel -->
    <div class="card">
        <div class="card-header">
            <h3>⚠ Immediate Reorder Alerts</h3>
            <a href="<?= BASE_URL ?>reports/low_stock.php" class="btn btn-outline btn-sm">View All (<?= $lowStockCount ?>)</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th class="text-right">Available</th>
                        <th class="text-right">Reorder Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStockItems)): ?>
                        <tr><td colspan="5" class="text-center" style="padding: 2rem;">All grocery products have adequate stock levels.</td></tr>
                    <?php else: ?>
                        <?php foreach ($lowStockItems as $item): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($item['product_name']) ?></strong></td>
                                <td><?= htmlspecialchars($item['category_name']) ?></td>
                                <td class="text-right" style="color:var(--danger);font-weight:700;">
                                    <?= formatQuantity($item['quantity_available'], $item['unit']) ?>
                                </td>
                                <td class="text-right"><?= formatQuantity($item['reorder_level'], $item['unit']) ?></td>
                                <td>
                                    <span class="badge <?= $item['quantity_available'] == 0 ? 'badge-danger' : 'badge-warning' ?>">
                                        <?= htmlspecialchars($item['stock_condition']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Stock Movements Panel -->
    <div class="card">
        <div class="card-header">
            <h3>Recent Stock Movements Audit Log</h3>
            <a href="<?= BASE_URL ?>inventory/stock_history.php" class="btn btn-outline btn-sm">Full History</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th class="text-right">Quantity</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentMovements)): ?>
                        <tr><td colspan="5" class="text-center" style="padding: 2rem;">No stock movements recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentMovements as $mv): ?>
                            <tr>
                                <td><?= htmlspecialchars($mv['movement_date']) ?></td>
                                <td><?= htmlspecialchars($mv['product_name']) ?></td>
                                <td>
                                    <?php 
                                        $badgeClass = 'badge-secondary';
                                        if ($mv['movement_type'] === 'PURCHASE' || $mv['movement_type'] === 'OPENING') $badgeClass = 'badge-success';
                                        elseif ($mv['movement_type'] === 'SALE') $badgeClass = 'badge-info';
                                        elseif ($mv['movement_type'] === 'DAMAGE') $badgeClass = 'badge-danger';
                                        elseif ($mv['movement_type'] === 'INTERNAL_USE') $badgeClass = 'badge-warning';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($mv['movement_type']) ?></span>
                                </td>
                                <td class="text-right"><strong><?= formatQuantity($mv['quantity'], $mv['unit']) ?></strong></td>
                                <td><small class="text-muted"><?= htmlspecialchars($mv['remarks']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

