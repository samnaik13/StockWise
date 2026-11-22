<?php
$currentPage = 'reports';
$pageTitle = 'Monthly Inventory & Financial Report';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();

// Select Month & Year (Default to current or October 2026)
$selectedYear = (int)($_GET['year'] ?? 2026);
$selectedMonth = (int)($_GET['month'] ?? 10);
if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = 10;
if ($selectedYear < 2000 || $selectedYear > 2099) $selectedYear = (int)date('Y');

$monthStr = sprintf('%04d-%02d', $selectedYear, $selectedMonth);
$monthStart = "{$monthStr}-01";
// Calculate exclusive start of next month
$nextMonthTimestamp = strtotime("+1 month", strtotime($monthStart));
$nextMonthStart = date('Y-m-01', $nextMonthTimestamp);

// ==============================================================================
// 1. SECTION A: STOCK SUMMARY (ALGORITHM 1 & 5)
// Historical opening and closing calculated using stock movements!
// ==============================================================================

// Opening Stock prior to monthStart
$stmtOpen = $pdo->prepare("
    SELECT 
        COALESCE(SUM(
            CASE 
                WHEN movement_type IN ('OPENING', 'PURCHASE', 'CUSTOMER_RETURN') THEN quantity
                WHEN movement_type IN ('SALE', 'INTERNAL_USE', 'DAMAGE', 'SUPPLIER_RETURN') THEN -quantity
                ELSE 0
            END
        ), 0) AS opening_stock
    FROM `Stock_Movement`
    WHERE movement_date < ?
");
$stmtOpen->execute([$monthStart]);
$openingStockQty = (float)$stmtOpen->fetchColumn();

// Stock Movements within the month
$stmtMonthMove = $pdo->prepare("
    SELECT 
        movement_type,
        COALESCE(SUM(quantity), 0) AS total_qty
    FROM `Stock_Movement`
    WHERE movement_date >= ? AND movement_date < ?
    GROUP BY movement_type
");
$stmtMonthMove->execute([$monthStart, $nextMonthStart]);
$monthMoves = $stmtMonthMove->fetchAll(PDO::FETCH_KEY_PAIR);

$purchasedQty    = (float)($monthMoves['PURCHASE'] ?? 0);
$soldQty         = (float)($monthMoves['SALE'] ?? 0);
$internalUseQty  = (float)($monthMoves['INTERNAL_USE'] ?? 0);
$damagedQty      = (float)($monthMoves['DAMAGE'] ?? 0);
$customerRetQty  = (float)($monthMoves['CUSTOMER_RETURN'] ?? 0);
$supplierRetQty  = (float)($monthMoves['SUPPLIER_RETURN'] ?? 0);

// Calculated Closing Stock
$closingStockQty = $openingStockQty + $purchasedQty + $customerRetQty - $soldQty - $internalUseQty - $damagedQty - $supplierRetQty;

// ==============================================================================
// 2. SECTION B: PURCHASE SUMMARY
// ==============================================================================
$stmtPurSummary = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT p.purchase_id) AS total_invoices,
        COALESCE(SUM(pi.quantity), 0) AS total_items_qty,
        COALESCE(SUM(pi.quantity * pi.unit_cost), 0) AS total_expenditure
    FROM `Purchase` p
    JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id
    WHERE p.purchase_date >= ? AND p.purchase_date < ?
");
$stmtPurSummary->execute([$monthStart, $nextMonthStart]);
$purSummary = $stmtPurSummary->fetch();

$totalPurchaseInvoices = (int)$purSummary['total_invoices'];
$totalPurchaseItemsQty = (float)$purSummary['total_items_qty'];
$totalPurchaseExpenditure = (float)$purSummary['total_expenditure'];

// Supplier-wise purchases
$stmtSuppPur = $pdo->prepare("
    SELECT 
        s.supplier_name,
        COUNT(DISTINCT p.purchase_id) AS invoices,
        ROUND(SUM(pi.quantity * pi.unit_cost), 2) AS spend
    FROM `Purchase` p
    JOIN `Supplier` s ON p.supplier_id = s.supplier_id
    JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id
    WHERE p.purchase_date >= ? AND p.purchase_date < ?
    GROUP BY s.supplier_id, s.supplier_name
    ORDER BY spend DESC
");
$stmtSuppPur->execute([$monthStart, $nextMonthStart]);
$supplierPurchases = $stmtSuppPur->fetchAll();

// Product-wise purchases
$stmtProdPur = $pdo->prepare("
    SELECT 
        pr.product_name,
        pr.unit,
        SUM(pi.quantity) AS qty,
        ROUND(SUM(pi.quantity * pi.unit_cost), 2) AS total_cost
    FROM `Purchase` p
    JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id
    JOIN `Product` pr ON pi.product_id = pr.product_id
    WHERE p.purchase_date >= ? AND p.purchase_date < ?
    GROUP BY pr.product_id, pr.product_name, pr.unit
    ORDER BY total_cost DESC
");
$stmtProdPur->execute([$monthStart, $nextMonthStart]);
$productPurchases = $stmtProdPur->fetchAll();

// ==============================================================================
// 3. SECTION C: SALES SUMMARY
// ==============================================================================
$stmtSaleSummary = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT s.sale_id) AS total_bills,
        COALESCE(SUM(si.quantity), 0) AS total_sold_qty,
        COALESCE(SUM(si.quantity * si.unit_selling_price), 0) AS total_revenue,
        COALESCE(SUM(si.quantity * si.unit_cost_at_sale), 0) AS total_cogs
    FROM `Sales` s
    JOIN `Sale_Item` si ON s.sale_id = si.sale_id
    WHERE s.sale_date >= ? AND s.sale_date < ?
");
$stmtSaleSummary->execute([$monthStart, $nextMonthStart]);
$saleSummary = $stmtSaleSummary->fetch();

$totalSalesBills = (int)$saleSummary['total_bills'];
$totalSalesItemsQty = (float)$saleSummary['total_sold_qty'];
$totalSalesRevenue = (float)$saleSummary['total_revenue'];
$totalCOGS = (float)$saleSummary['total_cogs'];
$grossProfit = $totalSalesRevenue - $totalCOGS;
$profitMargin = $totalSalesRevenue > 0 ? ($grossProfit / $totalSalesRevenue) * 100 : 0.0;

// Product-wise sales
$stmtProdSales = $pdo->prepare("
    SELECT 
        pr.product_name,
        pr.unit,
        SUM(si.quantity) AS qty,
        ROUND(SUM(si.quantity * si.unit_selling_price), 2) AS revenue,
        ROUND(SUM(si.quantity * si.unit_cost_at_sale), 2) AS cogs,
        ROUND(SUM(si.quantity * si.unit_selling_price) - SUM(si.quantity * si.unit_cost_at_sale), 2) AS profit
    FROM `Sales` s
    JOIN `Sale_Item` si ON s.sale_id = si.sale_id
    JOIN `Product` pr ON si.product_id = pr.product_id
    WHERE s.sale_date >= ? AND s.sale_date < ?
    GROUP BY pr.product_id, pr.product_name, pr.unit
    ORDER BY revenue DESC
");
$stmtProdSales->execute([$monthStart, $nextMonthStart]);
$productSales = $stmtProdSales->fetchAll();

// Current stock valuation as reference
$currentStockValuation = (float)$pdo->query("SELECT COALESCE(SUM(quantity_available * average_cost), 0) FROM `Inventory`")->fetchColumn();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Monthly Inventory & Financial Report</h1>
        <p>Comprehensive audit of stock reconciliations, wholesale expenditure, sales revenue, COGS, and gross profit</p>
    </div>
</div>

<!-- Month & Year Selector -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body" style="padding: 1.25rem;">
        <form method="GET" action="" style="display: flex; gap: 1.25rem; align-items: flex-end; flex-wrap: wrap;">
            <div>
                <label style="font-weight: 600; font-size: 0.9rem; display: block; margin-bottom: 0.3rem;">Select Month</label>
                <select name="month" class="form-control" style="width: 180px;">
                    <?php 
                    for ($m = 1; $m <= 12; $m++):
                        $mName = date('F', mktime(0, 0, 0, $m, 1));
                    ?>
                        <option value="<?= $m ?>" <?= $selectedMonth === $m ? 'selected' : '' ?>><?= $mName ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label style="font-weight: 600; font-size: 0.9rem; display: block; margin-bottom: 0.3rem;">Select Year</label>
                <select name="year" class="form-control" style="width: 140px;">
                    <?php for ($y = 2024; $y <= 2027; $y++): ?>
                        <option value="<?= $y ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Generate Monthly Report</button>
        </form>
    </div>
</div>

<h2 style="color: var(--primary); margin-bottom: 1rem; font-size: 1.35rem;">
    Report For: <strong><?= date('F Y', strtotime($monthStart)) ?></strong>
</h2>

<!-- Financial Top Highlights -->
<div class="metrics-grid">
    <div class="metric-card">
        <span class="metric-title">Wholesale Procurement Spend</span>
        <span class="metric-value"><?= formatCurrency($totalPurchaseExpenditure) ?></span>
        <span class="metric-sub"><?= $totalPurchaseInvoices ?> Invoices Procured</span>
    </div>

    <div class="metric-card highlight-green">
        <span class="metric-title">Gross Sales Revenue</span>
        <span class="metric-value"><?= formatCurrency($totalSalesRevenue) ?></span>
        <span class="metric-sub"><?= $totalSalesBills ?> Customer Bills Generated</span>
    </div>

    <div class="metric-card highlight-blue">
        <span class="metric-title">Cost of Goods Sold (COGS)</span>
        <span class="metric-value"><?= formatCurrency($totalCOGS) ?></span>
    </div>

    <div class="metric-card <?= $grossProfit >= 0 ? 'highlight-green' : 'highlight-red' ?>">
        <span class="metric-title">Gross Profit</span>
        <span class="metric-value"><?= formatCurrency($grossProfit) ?></span>
        <span class="metric-sub">Profit Margin: <?= number_format($profitMargin, 1) ?>%</span>
    </div>
</div>

<!-- ============================================================================== -->
<!-- SECTION A: STOCK SUMMARY TABLE                                                 -->
<!-- ============================================================================== -->
<div class="card report-section">
    <div class="card-header">
        <h3>Section A: Monthly Physical Stock Balance Ledger</h3>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Stock Movement Dimension</th>
                    <th>Mathematical Impact</th>
                    <th class="text-right">Quantity (Physical Units)</th>
                    <th>Audit Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Opening Stock (1st of Month)</strong></td>
                    <td>Base Balance</td>
                    <td class="text-right font-weight-bold"><?= number_format($openingStockQty, 3) ?></td>
                    <td><small class="text-muted">Calculated from all stock movements prior to <?= $monthStart ?></small></td>
                </tr>
                <tr>
                    <td>(+) Purchased Stock Received</td>
                    <td style="color:var(--success);font-weight:600;">ADD (+)</td>
                    <td class="text-right" style="color:var(--success);font-weight:700;">+<?= number_format($purchasedQty, 3) ?></td>
                    <td>Wholesale stock batches received from suppliers</td>
                </tr>
                <tr>
                    <td>(+) Customer Returns</td>
                    <td style="color:var(--success);font-weight:600;">ADD (+)</td>
                    <td class="text-right" style="color:var(--success);font-weight:700;">+<?= number_format($customerRetQty, 3) ?></td>
                    <td>Unopened items returned by retail customers with bill</td>
                </tr>
                <tr>
                    <td>(-) Sold Stock to Customers</td>
                    <td style="color:var(--danger);font-weight:600;">DEDUCT (-)</td>
                    <td class="text-right" style="color:var(--danger);font-weight:700;">-<?= number_format($soldQty, 3) ?></td>
                    <td>Physical items sold across customer counter bills</td>
                </tr>
                <tr>
                    <td>(-) Internal Grocery Usage</td>
                    <td style="color:var(--danger);font-weight:600;">DEDUCT (-)</td>
                    <td class="text-right" style="color:var(--danger);font-weight:700;">-<?= number_format($internalUseQty, 3) ?></td>
                    <td>Store sanitizers, detergents, pantry tea consumed by shop</td>
                </tr>
                <tr>
                    <td>(-) Damaged / Spoiled Stock</td>
                    <td style="color:var(--danger);font-weight:600;">DEDUCT (-)</td>
                    <td class="text-right" style="color:var(--danger);font-weight:700;">-<?= number_format($damagedQty, 3) ?></td>
                    <td>Leaked packets, packaging tears, perishable expiry shrinkage</td>
                </tr>
                <tr>
                    <td>(-) Supplier Returns</td>
                    <td style="color:var(--danger);font-weight:600;">DEDUCT (-)</td>
                    <td class="text-right" style="color:var(--danger);font-weight:700;">-<?= number_format($supplierRetQty, 3) ?></td>
                    <td>Damaged batches sent back to wholesale distributor</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="summary-table-highlight">
                    <td colspan="2"><strong>Calculated Closing Stock (End of Month):</strong></td>
                    <td class="text-right font-weight-bold" style="font-size: 1.15rem; color: var(--primary);">
                        <?= number_format($closingStockQty, 3) ?> units
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- ============================================================================== -->
<!-- SECTION B & C: PURCHASE & SALES SUMMARIES                                      -->
<!-- ============================================================================== -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(480px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Section B: Purchase Summary -->
    <div class="card">
        <div class="card-header">
            <h3>Section B: Wholesale Procurement Register</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th class="text-center">Invoices</th>
                        <th class="text-right">Procurement Spend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($supplierPurchases)): ?>
                        <tr><td colspan="3" class="text-center" style="padding: 2rem;">No wholesale purchases recorded for this month.</td></tr>
                    <?php else: ?>
                        <?php foreach ($supplierPurchases as $sp): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($sp['supplier_name']) ?></strong></td>
                                <td class="text-center"><?= $sp['invoices'] ?></td>
                                <td class="text-right font-weight-bold"><?= formatCurrency($sp['spend']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="summary-table-highlight">
                        <td><strong>Total Monthly Expenditure:</strong></td>
                        <td class="text-center"><strong><?= $totalPurchaseInvoices ?></strong></td>
                        <td class="text-right font-weight-bold" style="color: var(--primary);">
                            <?= formatCurrency($totalPurchaseExpenditure) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Section C: Sales Summary -->
    <div class="card">
        <div class="card-header">
            <h3>Section C: Product-Wise Sales Performance</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-right">Qty Sold</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productSales)): ?>
                        <tr><td colspan="4" class="text-center" style="padding: 2rem;">No customer sales recorded for this month.</td></tr>
                    <?php else: ?>
                        <?php foreach ($productSales as $ps): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ps['product_name']) ?></strong></td>
                                <td class="text-right"><?= formatQuantity($ps['qty'], $ps['unit']) ?></td>
                                <td class="text-right font-weight-bold"><?= formatCurrency($ps['revenue']) ?></td>
                                <td class="text-right profit-positive"><?= formatCurrency($ps['profit']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="summary-table-highlight">
                        <td><strong>Total Monthly Revenue:</strong></td>
                        <td class="text-right"><strong><?= number_format($totalSalesItemsQty, 2) ?></strong></td>
                        <td class="text-right font-weight-bold" style="color: var(--primary);"><?= formatCurrency($totalSalesRevenue) ?></td>
                        <td class="text-right font-weight-bold profit-positive"><?= formatCurrency($grossProfit) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================================== -->
<!-- SECTION D: FINANCIAL STATEMENT & COGS RECONCILIATION                          -->
<!-- ============================================================================== -->
<div class="card">
    <div class="card-header">
        <h3>Section D: Monthly Financial Statement & Gross Profit</h3>
    </div>
    <div class="card-body">
        <table class="data-table" style="max-width: 800px; margin: 0 auto; border: 1px solid var(--border-color);">
            <tbody>
                <tr>
                    <td style="font-size: 1rem;">1. Total Monthly Sales Revenue</td>
                    <td class="text-right font-weight-bold" style="font-size: 1.1rem; color: var(--success);">
                        <?= formatCurrency($totalSalesRevenue) ?>
                    </td>
                </tr>
                <tr>
                    <td style="font-size: 1rem;">2. Cost of Goods Sold (COGS)</td>
                    <td class="text-right font-weight-bold" style="font-size: 1.1rem; color: var(--danger);">
                        ( - ) <?= formatCurrency($totalCOGS) ?>
                    </td>
                </tr>
                <tr class="summary-table-highlight" style="border-top: 2px solid var(--border-color); border-bottom: 2px solid var(--border-color);">
                    <td style="font-size: 1.15rem; color: var(--primary);"><strong>3. Net Gross Profit (1 - 2)</strong></td>
                    <td class="text-right font-weight-bold <?= $grossProfit >= 0 ? 'profit-positive' : 'profit-negative' ?>" style="font-size: 1.25rem;">
                        <?= formatCurrency($grossProfit) ?>
                    </td>
                </tr>
                <tr>
                    <td>Gross Profit Margin Percentage</td>
                    <td class="text-right font-weight-bold"><?= number_format($profitMargin, 2) ?>%</td>
                </tr>
                <tr>
                    <td>Total Wholesale Purchase Expenditure (Cash Outflow)</td>
                    <td class="text-right text-muted"><?= formatCurrency($totalPurchaseExpenditure) ?></td>
                </tr>
                <tr>
                    <td>Current Unsold Physical Inventory Capital Valuation</td>
                    <td class="text-right font-weight-bold" style="color: var(--primary);"><?= formatCurrency($currentStockValuation) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

