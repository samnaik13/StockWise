<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// Quick counts for badges
$lowStockCount = 0;
$deadStockCount = 0;
try {
    $db = getDBConnection();
    $lowStockCount = (int)$db->query("SELECT COUNT(*) FROM `Low_Stock_Products`")->fetchColumn();
    $deadStockCount = (int)$db->query("SELECT COUNT(*) FROM `Dead_Stock_Products`")->fetchColumn();
} catch (Exception $e) {
    // If database or views are not yet imported
}

if (!isset($pageTitle)) {
    $pageTitle = 'StockWise - Inventory Management System';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | StockWise</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
</head>
<body>
    <header class="app-header">
        <div class="header-container">
            <div class="brand">
                <a href="<?= BASE_URL ?>index.php" class="brand-link">
                    <span class="brand-icon">🛒</span>
                    <span class="brand-text">
                        <strong>StockWise</strong>
                        <small>Inventory Management System</small>
                    </span>
                </a>
            </div>
            <nav class="main-nav">
                <ul class="nav-links">
                    <li><a href="<?= BASE_URL ?>index.php" class="<?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>products/view_products.php" class="<?= ($currentPage ?? '') === 'products' ? 'active' : '' ?>">Products</a></li>
                    <li><a href="<?= BASE_URL ?>categories/categories.php" class="<?= ($currentPage ?? '') === 'categories' ? 'active' : '' ?>">Categories</a></li>
                    <li><a href="<?= BASE_URL ?>suppliers/suppliers.php" class="<?= ($currentPage ?? '') === 'suppliers' ? 'active' : '' ?>">Suppliers</a></li>
                    <li><a href="<?= BASE_URL ?>purchases/purchases.php" class="<?= ($currentPage ?? '') === 'purchases' ? 'active' : '' ?>">Purchases</a></li>
                    <li><a href="<?= BASE_URL ?>sales/sales.php" class="<?= ($currentPage ?? '') === 'sales' ? 'active' : '' ?>">Sales Billing</a></li>
                    <li><a href="<?= BASE_URL ?>inventory/inventory.php" class="<?= ($currentPage ?? '') === 'inventory' ? 'active' : '' ?>">Inventory</a></li>
                    <li class="nav-dropdown">
                        <a href="<?= BASE_URL ?>reports/monthly_report.php" class="<?= ($currentPage ?? '') === 'reports' ? 'active' : '' ?>">Reports ▾</a>
                        <div class="dropdown-content">
                            <a href="<?= BASE_URL ?>reports/monthly_report.php">Monthly Inventory & Financials</a>
                            <a href="<?= BASE_URL ?>reports/purchase_report.php">Purchase Register</a>
                            <a href="<?= BASE_URL ?>reports/sales_report.php">Sales Register</a>
                            <a href="<?= BASE_URL ?>reports/low_stock.php">
                                Low Stock Alerts 
                                <?php if ($lowStockCount > 0): ?>
                                    <span class="badge badge-warning"><?= $lowStockCount ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?= BASE_URL ?>reports/dead_stock.php">
                                Dead Stock Detection
                                <?php if ($deadStockCount > 0): ?>
                                    <span class="badge badge-danger"><?= $deadStockCount ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?= BASE_URL ?>inventory/stock_history.php">Stock Movement Audit Log</a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="content-container">
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success">
                    <span>✓</span> <?= htmlspecialchars($_SESSION['flash_success']) ?>
                    <?php unset($_SESSION['flash_success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger">
                    <span>⚠</span> <?= htmlspecialchars($_SESSION['flash_error']) ?>
                    <?php unset($_SESSION['flash_error']); ?>
                </div>
            <?php endif; ?>

