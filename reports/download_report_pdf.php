<?php
/**
 * STOCKWISE: Monthly Inventory & Financial Report PDF Download
 * Generates an official, beautifully styled PDF report reflecting actual database values.
 * Filename format: StockWise_Monthly_Report_{Month}_{Year}.pdf
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireAuth();
$userId = getCurrentUserId();
$userName = getCurrentUserName();
$pdo = getDBConnection();

// Month & Year selection
$selectedYear = (int)($_GET['year'] ?? 2026);
$selectedMonth = (int)($_GET['month'] ?? 10);
if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = 10;
if ($selectedYear < 2000 || $selectedYear > 2099) $selectedYear = (int)date('Y');

$monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1));
$pdfFilename = "StockWise_Monthly_Report_{$monthName}_{$selectedYear}.pdf";

$monthStr = sprintf('%04d-%02d', $selectedYear, $selectedMonth);
$monthStart = "{$monthStr}-01";
$nextMonthTimestamp = strtotime("+1 month", strtotime($monthStart));
$nextMonthStart = date('Y-m-01', $nextMonthTimestamp);

// 1. Opening Stock prior to monthStart
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
    WHERE user_id = ? AND movement_date < ?
");
$stmtOpen->execute([$userId, $monthStart]);
$openingStockQty = (float)$stmtOpen->fetchColumn();

// Stock Movements within the month
$stmtMonthMove = $pdo->prepare("
    SELECT 
        movement_type,
        COALESCE(SUM(quantity), 0) AS total_qty
    FROM `Stock_Movement`
    WHERE user_id = ? AND movement_date >= ? AND movement_date < ?
    GROUP BY movement_type
");
$stmtMonthMove->execute([$userId, $monthStart, $nextMonthStart]);
$monthMoves = $stmtMonthMove->fetchAll(PDO::FETCH_KEY_PAIR);

$purchasedQty    = (float)($monthMoves['PURCHASE'] ?? 0);
$soldQty         = (float)($monthMoves['SALE'] ?? 0);
$internalUseQty  = (float)($monthMoves['INTERNAL_USE'] ?? 0);
$damagedQty      = (float)($monthMoves['DAMAGE'] ?? 0);
$customerRetQty  = (float)($monthMoves['CUSTOMER_RETURN'] ?? 0);
$supplierRetQty  = (float)($monthMoves['SUPPLIER_RETURN'] ?? 0);

$closingStockQty = $openingStockQty + $purchasedQty + $customerRetQty - $soldQty - $internalUseQty - $damagedQty - $supplierRetQty;

// 2. Purchases Summary
$stmtPurSummary = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT p.purchase_id) AS total_invoices,
        COALESCE(SUM(pi.quantity), 0) AS total_items_qty,
        COALESCE(SUM(pi.quantity * pi.unit_cost), 0) AS total_expenditure
    FROM `Purchase` p
    JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id
    WHERE p.user_id = ? AND p.purchase_date >= ? AND p.purchase_date < ?
");
$stmtPurSummary->execute([$userId, $monthStart, $nextMonthStart]);
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
    WHERE p.user_id = ? AND p.purchase_date >= ? AND p.purchase_date < ?
    GROUP BY s.supplier_id, s.supplier_name
    ORDER BY spend DESC
");
$stmtSuppPur->execute([$userId, $monthStart, $nextMonthStart]);
$supplierPurchases = $stmtSuppPur->fetchAll();

// 3. Sales Summary
$stmtSaleSummary = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT s.sale_id) AS total_bills,
        COALESCE(SUM(si.quantity), 0) AS total_sold_qty,
        COALESCE(SUM(si.quantity * si.unit_selling_price), 0) AS total_revenue,
        COALESCE(SUM(si.quantity * si.unit_cost_at_sale), 0) AS total_cogs
    FROM `Sales` s
    JOIN `Sale_Item` si ON s.sale_id = si.sale_id
    WHERE s.user_id = ? AND s.sale_date >= ? AND s.sale_date < ?
");
$stmtSaleSummary->execute([$userId, $monthStart, $nextMonthStart]);
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
    WHERE s.user_id = ? AND s.sale_date >= ? AND s.sale_date < ?
    GROUP BY pr.product_id, pr.product_name, pr.unit
    ORDER BY revenue DESC
");
$stmtProdSales->execute([$userId, $monthStart, $nextMonthStart]);
$productSales = $stmtProdSales->fetchAll();

// Current stock valuation
$stmtVal = $pdo->prepare("SELECT COALESCE(SUM(quantity_available * average_cost), 0) FROM `Inventory` WHERE user_id = ?");
$stmtVal->execute([$userId]);
$currentStockValuation = (float)$stmtVal->fetchColumn();

// Pie chart calculations
$cogsPercent = 0.0;
$profitPercent = 0.0;
$hasSalesData = ($totalSalesRevenue > 0);

if ($hasSalesData) {
    $cogsPercent = round(($totalCOGS / $totalSalesRevenue) * 100, 1);
    $profitPercent = round(($grossProfit / $totalSalesRevenue) * 100, 1);
}

// Generate Pie Chart SVG
$radius = 100;
$cx = 130;
$cy = 130;
$pieChartSvg = '';

if (!$hasSalesData) {
    $pieChartSvg = "<svg viewBox='0 0 260 260' width='220' height='220'>
        <circle cx='{$cx}' cy='{$cy}' r='{$radius}' fill='#f8f9fa' stroke='#dee2e6' stroke-width='2'/>
        <text x='130' y='135' text-anchor='middle' fill='#6c757d' font-size='13' font-family='sans-serif'>No Sales Data</text>
    </svg>";
} elseif ($grossProfit <= 0) {
    $pieChartSvg = "<svg viewBox='0 0 260 260' width='220' height='220'>
        <circle cx='{$cx}' cy='{$cy}' r='{$radius}' fill='#2c7da0' stroke='#ffffff' stroke-width='2'/>
        <text x='130' y='135' text-anchor='middle' fill='#ffffff' font-size='13' font-weight='bold' font-family='sans-serif'>COGS 100%</text>
    </svg>";
} elseif ($totalCOGS <= 0) {
    $pieChartSvg = "<svg viewBox='0 0 260 260' width='220' height='220'>
        <circle cx='{$cx}' cy='{$cy}' r='{$radius}' fill='#2d6a4f' stroke='#ffffff' stroke-width='2'/>
        <text x='130' y='135' text-anchor='middle' fill='#ffffff' font-size='13' font-weight='bold' font-family='sans-serif'>Profit 100%</text>
    </svg>";
} else {
    $cogsAngle = ($totalCOGS / $totalSalesRevenue) * 360;
    $rad = ($cogsAngle - 90) * M_PI / 180;
    $x = $cx + $radius * cos($rad);
    $y = $cy + $radius * sin($rad);
    $largeArc = $cogsAngle > 180 ? 1 : 0;
    $smallArc = $largeArc ? 0 : 1;

    $pieChartSvg = "<svg viewBox='0 0 260 260' width='220' height='220'>
        <path d='M {$cx} {$cy} L {$cx} " . ($cy - $radius) . " A {$radius} {$radius} 0 {$largeArc} 1 {$x} {$y} Z' fill='#2c7da0' stroke='#ffffff' stroke-width='2'/>
        <path d='M {$cx} {$cy} L {$x} {$y} A {$radius} {$radius} 0 {$smallArc} 1 {$cx} " . ($cy - $radius) . " Z' fill='#2d6a4f' stroke='#ffffff' stroke-width='2'/>
    </svg>";
}

// Build Print-Optimized HTML Document
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pdfFilename) ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm 15mm 15mm 15mm;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #212529;
            font-size: 11.5px;
            line-height: 1.45;
            background: #ffffff;
        }
        .header {
            border-bottom: 2.5px solid #1b4d3e;
            padding-bottom: 12px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .header h1 {
            color: #1b4d3e;
            font-size: 20px;
            margin-bottom: 2px;
            font-weight: 700;
        }
        .header p {
            color: #6c757d;
            font-size: 10.5px;
        }
        .meta-box {
            text-align: right;
            font-size: 10px;
            color: #555;
        }
        .meta-box strong { color: #1b4d3e; }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .metric-card {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            background: #fafafa;
        }
        .metric-title {
            font-size: 8.5px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 600;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }
        .metric-value {
            font-size: 15px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 2px;
        }
        .metric-sub {
            font-size: 8.5px;
            color: #6c757d;
        }
        .card {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 16px;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .card-header {
            background: #f1f5f3;
            border-bottom: 1px solid #dee2e6;
            padding: 7px 12px;
        }
        .card-header h3 {
            font-size: 12px;
            font-weight: 600;
            color: #1b4d3e;
        }
        .card-body { padding: 10px 12px; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        th, td {
            padding: 6px 9px;
            border-bottom: 1px solid #edf2f4;
            text-align: left;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #264653;
            border-bottom: 1.5px solid #dee2e6;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-row {
            background: #f4fbf7;
            font-weight: 600;
        }
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 8.5px;
            font-weight: 600;
            border-radius: 3px;
        }
        .badge-info { background: #e1f5fe; color: #0288d1; }
        .badge-success { background: #e8f5e9; color: #2e7d32; }
        .footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #dee2e6;
            font-size: 9px;
            color: #888;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>🛒 StockWise &bull; Monthly Inventory & Financial Report</h1>
            <p>Comprehensive audit of stock reconciliations, wholesale expenditure, sales revenue, COGS, and gross profit</p>
        </div>
        <div class="meta-box">
            <div>Report Month: <strong><?= $monthName ?> <?= $selectedYear ?></strong></div>
            <div>Account Holder: <strong><?= htmlspecialchars($userName) ?></strong></div>
            <div>Generated: <?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <!-- 4 Top Financial Metric Cards -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-title">Wholesale Procurement Spend</div>
            <div class="metric-value"><?= formatCurrency($totalPurchaseExpenditure) ?></div>
            <div class="metric-sub"><?= $totalPurchaseInvoices ?> Invoices Procured</div>
        </div>
        <div class="metric-card">
            <div class="metric-title">Gross Sales Revenue</div>
            <div class="metric-value" style="color: #2e7d32;"><?= formatCurrency($totalSalesRevenue) ?></div>
            <div class="metric-sub"><?= $totalSalesBills ?> Customer Bills Generated</div>
        </div>
        <div class="metric-card">
            <div class="metric-title">Cost of Goods Sold (COGS)</div>
            <div class="metric-value" style="color: #2c7da0;"><?= formatCurrency($totalCOGS) ?></div>
            <div class="metric-sub">Inventory Cost at Sale</div>
        </div>
        <div class="metric-card">
            <div class="metric-title">Gross Profit</div>
            <div class="metric-value" style="color: <?= $grossProfit >= 0 ? '#2e7d32' : '#d32f2f' ?>;"><?= formatCurrency($grossProfit) ?></div>
            <div class="metric-sub">Margin: <?= number_format($profitMargin, 1) ?>%</div>
        </div>
    </div>

    <!-- Section A: Monthly Physical Stock Balance Ledger -->
    <div class="card">
        <div class="card-header">
            <h3>Section A: Monthly Physical Stock Balance Ledger</h3>
        </div>
        <table>
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
                    <td class="text-right"><strong><?= number_format($openingStockQty, 3) ?></strong></td>
                    <td>Calculated from all stock movements prior to <?= $monthStart ?></td>
                </tr>
                <tr>
                    <td>(+) Purchased Stock Received</td>
                    <td style="color:#2e7d32;font-weight:600;">ADD (+)</td>
                    <td class="text-right" style="color:#2e7d32;font-weight:700;">+<?= number_format($purchasedQty, 3) ?></td>
                    <td>Wholesale stock batches received from suppliers</td>
                </tr>
                <tr>
                    <td>(+) Customer Returns</td>
                    <td style="color:#2e7d32;font-weight:600;">ADD (+)</td>
                    <td class="text-right" style="color:#2e7d32;font-weight:700;">+<?= number_format($customerRetQty, 3) ?></td>
                    <td>Unopened items returned by retail customers with bill</td>
                </tr>
                <tr>
                    <td>(-) Sold Stock to Customers</td>
                    <td style="color:#d32f2f;font-weight:600;">DEDUCT (-)</td>
                    <td class="text-right" style="color:#d32f2f;font-weight:700;">-<?= number_format($soldQty, 3) ?></td>
                    <td>Physical items sold across customer counter bills</td>
                </tr>
                <tr>
                    <td>(-) Internal Grocery Usage</td>
                    <td style="color:#d32f2f;font-weight:600;">DEDUCT (-)</td>
                    <td class="text-right" style="color:#d32f2f;font-weight:700;">-<?= number_format($internalUseQty, 3) ?></td>
                    <td>Store sanitizers, detergents, pantry tea consumed by shop</td>
                </tr>
                <tr>
                    <td>(-) Damaged / Spoiled Stock</td>
                    <td style="color:#d32f2f;font-weight:600;">DEDUCT (-)</td>
                    <td class="text-right" style="color:#d32f2f;font-weight:700;">-<?= number_format($damagedQty, 3) ?></td>
                    <td>Leaked packets, packaging tears, perishable expiry shrinkage</td>
                </tr>
                <tr>
                    <td>(-) Supplier Returns</td>
                    <td style="color:#d32f2f;font-weight:600;">DEDUCT (-)</td>
                    <td class="text-right" style="color:#d32f2f;font-weight:700;">-<?= number_format($supplierRetQty, 3) ?></td>
                    <td>Damaged batches sent back to wholesale distributor</td>
                </tr>
                <tr class="summary-row">
                    <td colspan="2"><strong>Calculated Closing Stock (End of Month):</strong></td>
                    <td class="text-right" style="font-size: 12px; color: #1b4d3e;"><strong><?= number_format($closingStockQty, 3) ?> units</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Section B & Section C Side-by-Side -->
    <div class="two-col">
        <!-- Section B: Wholesale Procurement Register -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <h3>Section B: Wholesale Procurement Register</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th class="text-center">Invoices</th>
                        <th class="text-right">Spend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($supplierPurchases)): ?>
                        <tr><td colspan="3" class="text-center" style="padding: 15px; color:#888;">No wholesale purchases for this month.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($supplierPurchases, 0, 6) as $sp): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($sp['supplier_name']) ?></strong></td>
                                <td class="text-center"><?= $sp['invoices'] ?></td>
                                <td class="text-right font-weight-bold"><?= formatCurrency($sp['spend']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="summary-row">
                            <td><strong>Total:</strong></td>
                            <td class="text-center"><strong><?= $totalPurchaseInvoices ?></strong></td>
                            <td class="text-right" style="color:#1b4d3e;"><strong><?= formatCurrency($totalPurchaseExpenditure) ?></strong></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Section C: Sales Summary -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <h3>Section C: Product-Wise Sales Performance</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-right">Sold</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productSales)): ?>
                        <tr><td colspan="4" class="text-center" style="padding: 15px; color:#888;">No customer sales for this month.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($productSales, 0, 6) as $ps): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ps['product_name']) ?></strong></td>
                                <td class="text-right"><?= formatQuantity($ps['qty'], $ps['unit']) ?></td>
                                <td class="text-right"><?= formatCurrency($ps['revenue']) ?></td>
                                <td class="text-right" style="color:#2e7d32;font-weight:600;"><?= formatCurrency($ps['profit']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="summary-row">
                            <td><strong>Total:</strong></td>
                            <td class="text-right"><strong><?= number_format($totalSalesItemsQty, 2) ?></strong></td>
                            <td class="text-right" style="color:#1b4d3e;"><strong><?= formatCurrency($totalSalesRevenue) ?></strong></td>
                            <td class="text-right" style="color:#2e7d32;"><strong><?= formatCurrency($grossProfit) ?></strong></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section D: Monthly Financial Statement -->
    <div class="card">
        <div class="card-header">
            <h3>Section D: Monthly Financial Statement & Gross Profit</h3>
        </div>
        <table>
            <tbody>
                <tr>
                    <td style="font-size: 11px;">1. Total Monthly Sales Revenue</td>
                    <td class="text-right" style="font-weight: 700; color: #2e7d32; font-size: 12px;"><?= formatCurrency($totalSalesRevenue) ?></td>
                </tr>
                <tr>
                    <td style="font-size: 11px;">2. Cost of Goods Sold (COGS)</td>
                    <td class="text-right" style="font-weight: 700; color: #2c7da0; font-size: 12px;">( - ) <?= formatCurrency($totalCOGS) ?></td>
                </tr>
                <tr class="summary-row" style="border-top: 1.5px solid #dee2e6; border-bottom: 1.5px solid #dee2e6;">
                    <td style="font-size: 12px; color: #1b4d3e;"><strong>3. Net Gross Profit (1 - 2)</strong></td>
                    <td class="text-right" style="font-size: 13px; font-weight: 700; color: <?= $grossProfit >= 0 ? '#2e7d32' : '#d32f2f' ?>;">
                        <?= formatCurrency($grossProfit) ?>
                    </td>
                </tr>
                <tr>
                    <td>Gross Profit Margin Percentage</td>
                    <td class="text-right" style="font-weight: 700;"><?= number_format($profitMargin, 2) ?>%</td>
                </tr>
                <tr>
                    <td>Total Wholesale Purchase Expenditure (Cash Outflow)</td>
                    <td class="text-right" style="color: #6c757d;"><?= formatCurrency($totalPurchaseExpenditure) ?></td>
                </tr>
                <tr>
                    <td>Current Unsold Physical Inventory Capital Valuation</td>
                    <td class="text-right" style="font-weight: 700; color: #1b4d3e;"><?= formatCurrency($currentStockValuation) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Section E: Financial Revenue Breakdown & Pie Chart -->
    <div class="card">
        <div class="card-header">
            <h3>Section E: Financial Revenue Distribution (COGS vs Gross Profit)</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; align-items: center; justify-content: space-around; gap: 20px;">
                <!-- SVG Pie Chart -->
                <div style="width: 220px; text-align: center;">
                    <?= $pieChartSvg ?>
                </div>

                <!-- Legend & Composition -->
                <div style="flex: 1; max-width: 360px;">
                    <div style="margin-bottom: 10px;">
                        <strong style="color: #1b4d3e; font-size: 11.5px;">Gross Sales Revenue Composition</strong>
                        <div style="font-size: 10px; color: #666; margin-top: 2px;">
                            Total Revenue: <strong><?= formatCurrency($totalSalesRevenue) ?></strong>
                        </div>
                    </div>

                    <div style="margin-bottom: 8px; padding: 7px 10px; background: #e1f5fe; border-left: 4px solid #2c7da0; border-radius: 3px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="display:inline-block; width:10px; height:10px; background:#2c7da0; border-radius:2px; vertical-align:middle; margin-right:4px;"></span>
                            <strong style="color: #0277bd; font-size: 10.5px;">Cost of Goods Sold (COGS)</strong>
                        </div>
                        <div style="text-align: right;">
                            <strong style="color: #0277bd;"><?= formatCurrency($totalCOGS) ?></strong>
                            <span class="badge badge-info" style="margin-left: 4px;"><?= number_format($cogsPercent, 1) ?>%</span>
                        </div>
                    </div>

                    <div style="padding: 7px 10px; background: #e8f5e9; border-left: 4px solid #2d6a4f; border-radius: 3px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="display:inline-block; width:10px; height:10px; background:#2d6a4f; border-radius:2px; vertical-align:middle; margin-right:4px;"></span>
                            <strong style="color: #2e7d32; font-size: 10.5px;">Gross Profit</strong>
                        </div>
                        <div style="text-align: right;">
                            <strong style="color: #2e7d32;"><?= formatCurrency($grossProfit) ?></strong>
                            <span class="badge badge-success" style="margin-left: 4px;"><?= number_format($profitPercent, 1) ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div>StockWise Inventory Management System &bull; Confidential Business Audit Report</div>
        <div>Page 1 of 1 &bull; Account: <?= htmlspecialchars($userName) ?></div>
    </div>
</body>
</html>
<?php
$htmlContent = ob_get_clean();

// Generate PDF using Headless Chrome / Edge
$tempHtml = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'stockwise_rpt_' . uniqid() . '.html';
$tempPdf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'stockwise_rpt_' . uniqid() . '.pdf';
$uniqueDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'chrome_tmp_' . uniqid();

file_put_contents($tempHtml, $htmlContent);

$browserCandidates = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
];

$browserExe = null;
foreach ($browserCandidates as $cand) {
    if (file_exists($cand)) {
        $browserExe = $cand;
        break;
    }
}

if ($browserExe) {
    $cmd = '"' . $browserExe . '" --headless=new --disable-gpu --user-data-dir="' . $uniqueDir . '" --no-pdf-header-footer --print-to-pdf="' . $tempPdf . '" "' . $tempHtml . '" 2>&1';
    exec($cmd, $output, $status);

    if (file_exists($tempPdf) && filesize($tempPdf) > 0) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $pdfFilename . '"');
        header('Content-Length: ' . filesize($tempPdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($tempPdf);

        @unlink($tempHtml);
        @unlink($tempPdf);
        @exec("rmdir /s /q \"$uniqueDir\"");
        exit;
    }
}

// Fallback: If browser execution fails, serve the print-optimized HTML directly with auto-print
@unlink($tempHtml);
@unlink($tempPdf);
header('Content-Type: text/html; charset=UTF-8');
echo $htmlContent;
echo "<script>window.onload = function() { window.print(); };</script>";
exit;
