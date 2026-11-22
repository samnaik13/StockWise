<?php
$currentPage = 'sales';
$pageTitle = 'Sales Billing & Outflow Register';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();

// View single bill modal/panel
$viewSaleId = (int)($_GET['view'] ?? 0);
$selectedSale = null;
$saleItems = [];

if ($viewSaleId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM `Sales` WHERE sale_id = ?");
    $stmt->execute([$viewSaleId]);
    $selectedSale = $stmt->fetch();

    if ($selectedSale) {
        $stmtItems = $pdo->prepare("
            SELECT si.*, pr.product_name, pr.unit 
            FROM `Sale_Item` si
            JOIN `Product` pr ON si.product_id = pr.product_id
            WHERE si.sale_id = ?
        ");
        $stmtItems->execute([$viewSaleId]);
        $saleItems = $stmtItems->fetchAll();
    }
}

// Fetch all sales bills with revenue, COGS, and gross profit
$sales = $pdo->query("
    SELECT 
        s.sale_id,
        s.bill_number,
        s.sale_date,
        COUNT(si.sale_item_id) AS items_count,
        ROUND(SUM(si.quantity * si.unit_selling_price), 2) AS total_revenue,
        ROUND(SUM(si.quantity * si.unit_cost_at_sale), 2) AS total_cogs,
        ROUND(SUM(si.quantity * si.unit_selling_price) - SUM(si.quantity * si.unit_cost_at_sale), 2) AS gross_profit
    FROM `Sales` s
    JOIN `Sale_Item` si ON s.sale_id = si.sale_id
    GROUP BY s.sale_id, s.bill_number, s.sale_date
    ORDER BY s.sale_date DESC, s.sale_id DESC
")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Counter Sales Billing Register (Stock-Out)</h1>
        <p>Record customer sales, deduct physical inventory, freeze historical costs, and calculate profit</p>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>sales/add_sale.php" class="btn btn-success">+ New Customer Bill (POS)</a>
    </div>
</div>

<?php if ($selectedSale): ?>
    <!-- Bill Detail Inspection Panel -->
    <div class="card" style="border: 2px solid var(--primary); margin-bottom: 2rem;">
        <div class="card-header" style="background-color: var(--primary-subtle);">
            <div>
                <h2>Customer Bill: <?= htmlspecialchars($selectedSale['bill_number']) ?></h2>
                <small>Billing Date: <strong><?= htmlspecialchars($selectedSale['sale_date']) ?></strong></small>
            </div>
            <a href="<?= BASE_URL ?>sales/sales.php" class="btn btn-outline btn-sm">Close Bill</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-right">Quantity Sold</th>
                        <th class="text-right">Selling Price</th>
                        <th class="text-right">Historical Cost at Sale</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">COGS</th>
                        <th class="text-right">Gross Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $billRevenue = 0;
                    $billCOGS = 0;
                    foreach ($saleItems as $it): 
                        $rev = $it['quantity'] * $it['unit_selling_price'];
                        $cogs = $it['quantity'] * $it['unit_cost_at_sale'];
                        $profit = $rev - $cogs;
                        $billRevenue += $rev;
                        $billCOGS += $cogs;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($it['product_name']) ?></strong></td>
                            <td class="text-right"><?= formatQuantity($it['quantity'], $it['unit']) ?></td>
                            <td class="text-right"><?= formatCurrency($it['unit_selling_price']) ?></td>
                            <td class="text-right text-muted"><?= formatCurrency($it['unit_cost_at_sale']) ?></td>
                            <td class="text-right font-weight-bold"><?= formatCurrency($rev) ?></td>
                            <td class="text-right text-muted"><?= formatCurrency($cogs) ?></td>
                            <td class="text-right font-weight-bold profit-positive"><?= formatCurrency($profit) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="summary-table-highlight">
                        <td colspan="4" class="text-right"><strong>Bill Totals:</strong></td>
                        <td class="text-right font-weight-bold" style="color: var(--primary); font-size: 1.05rem;">
                            <?= formatCurrency($billRevenue) ?>
                        </td>
                        <td class="text-right text-muted font-weight-bold">
                            <?= formatCurrency($billCOGS) ?>
                        </td>
                        <td class="text-right font-weight-bold profit-positive" style="font-size: 1.05rem;">
                            <?= formatCurrency($billRevenue - $billCOGS) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Sales Register Table -->
<div class="card">
    <div class="card-header">
        <h2>Counter Sales Invoices History (<?= count($sales) ?> bills)</h2>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Bill Number</th>
                    <th class="text-center">Items Sold</th>
                    <th class="text-right">Sales Revenue</th>
                    <th class="text-right">Cost of Goods Sold (COGS)</th>
                    <th class="text-right">Gross Profit</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                    <tr><td colspan="7" class="text-center" style="padding: 2.5rem;">No customer bills recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($sales as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['sale_date']) ?></td>
                            <td><strong><?= htmlspecialchars($s['bill_number']) ?></strong></td>
                            <td class="text-center"><span class="badge badge-secondary"><?= $s['items_count'] ?> items</span></td>
                            <td class="text-right font-weight-bold"><?= formatCurrency($s['total_revenue']) ?></td>
                            <td class="text-right text-muted"><?= formatCurrency($s['total_cogs']) ?></td>
                            <td class="text-right font-weight-bold profit-positive"><?= formatCurrency($s['gross_profit']) ?></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>sales/sales.php?view=<?= $s['sale_id'] ?>" class="btn btn-outline btn-sm">Inspect Bill</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

