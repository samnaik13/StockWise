<?php
$currentPage = 'inventory';
$pageTitle = 'Current Inventory Valuation & Stock';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$userId = getCurrentUserId();

// Fetch all items for overall inventory metric cards scoped to user
$stmtAll = $pdo->prepare("SELECT v.* FROM `Product_Inventory_Status` v JOIN `Product` p ON v.product_id = p.product_id WHERE p.user_id = ?");
$stmtAll->execute([$userId]);
$allStatus = $stmtAll->fetchAll();

$totalQty = 0;
$totalValuation = 0;
$lowCount = 0;
$outCount = 0;

foreach ($allStatus as $item) {
    $totalQty += (float)$item['quantity_available'];
    $totalValuation += (float)$item['current_inventory_value'];
    if ($item['stock_status'] === 'Low Stock') $lowCount++;
    if ($item['stock_status'] === 'Out of Stock') $outCount++;
}

// Categories for filter dropdown scoped to user
$stmtCat = $pdo->prepare("SELECT category_id, category_name FROM `Category` WHERE user_id = ? ORDER BY category_name ASC");
$stmtCat->execute([$userId]);
$categories = $stmtCat->fetchAll();

// Handle Search, Category Filter, and Sorting
$search = trim($_GET['search'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$sortBy = trim($_GET['sort'] ?? 'id_asc');

$query = "
    SELECT 
        v.*,
        p.category_id
    FROM `Product_Inventory_Status` v
    JOIN `Product` p ON v.product_id = p.product_id
    WHERE p.user_id = ?
";
$params = [$userId];

if ($search !== '') {
    $query .= " AND (v.product_name LIKE ? OR v.product_id = ?)";
    $params[] = "%$search%";
    $params[] = is_numeric($search) ? (int)$search : 0;
}

if ($categoryId > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

switch ($sortBy) {
    case 'id_desc':
        $query .= " ORDER BY v.product_id DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY v.product_name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY v.product_name DESC";
        break;
    case 'price_asc':
        $query .= " ORDER BY v.selling_price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY v.selling_price DESC";
        break;
    case 'cost_asc':
        $query .= " ORDER BY v.average_cost ASC";
        break;
    case 'cost_desc':
        $query .= " ORDER BY v.average_cost DESC";
        break;
    case 'stock_asc':
        $query .= " ORDER BY v.quantity_available ASC";
        break;
    case 'stock_desc':
        $query .= " ORDER BY v.quantity_available DESC";
        break;
    case 'val_asc':
        $query .= " ORDER BY v.current_inventory_value ASC";
        break;
    case 'val_desc':
        $query .= " ORDER BY v.current_inventory_value DESC";
        break;
    case 'id_asc':
    default:
        $sortBy = 'id_asc';
        $query .= " ORDER BY v.product_id ASC";
        break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$stockItems = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Real-Time Physical Inventory Status</h1>
        <p>Current stock levels, weighted average costs, capital valuation, and reorder alerts</p>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>inventory/stock_adjustment.php" class="btn btn-warning">⚡ Record Stock Adjustment</a>
        <a href="<?= BASE_URL ?>inventory/stock_history.php" class="btn btn-outline">Stock Movement Ledger</a>
    </div>
</div>

<div class="metrics-grid">
    <div class="metric-card">
        <span class="metric-title">Total Active SKUs</span>
        <span class="metric-value"><?= count($stockItems) ?></span>
        <span class="metric-sub">Catalog Products</span>
    </div>

    <div class="metric-card highlight-blue">
        <span class="metric-title">Total Available Stock Units</span>
        <span class="metric-value"><?= number_format($totalQty, 2) ?></span>
        <span class="metric-sub">Across All Grocery Units</span>
    </div>

    <div class="metric-card highlight-green">
        <span class="metric-title">Total Capital Valuation</span>
        <span class="metric-value"><?= formatCurrency($totalValuation) ?></span>
        <span class="metric-sub">Current Stock Asset Value</span>
    </div>

    <div class="metric-card highlight-orange">
        <span class="metric-title">Low Stock Reorders</span>
        <span class="metric-value"><?= $lowCount ?></span>
        <span class="metric-sub"><?= $outCount ?> Items Completely Out of Stock</span>
    </div>
</div>

<!-- Search & Sort Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body" style="padding: 1rem 1.25rem;">
        <form method="GET" action="" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 0.85rem; font-weight: 500; display: block; margin-bottom: 0.3rem;">Search Inventory</label>
                <input type="text" name="search" class="form-control" placeholder="Search by product name or ID..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div style="width: 200px;">
                <label style="font-size: 0.85rem; font-weight: 500; display: block; margin-bottom: 0.3rem;">Filter by Category</label>
                <select name="category_id" class="form-control">
                    <option value="0">-- All Categories --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= $categoryId === (int)$cat['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="width: 230px;">
                <label style="font-size: 0.85rem; font-weight: 500; display: block; margin-bottom: 0.3rem;">Sort Order</label>
                <select name="sort" class="form-control" onchange="this.form.submit()">
                    <option value="id_asc" <?= $sortBy === 'id_asc' ? 'selected' : '' ?>>Sort by ID: Low to High (Default)</option>
                    <option value="id_desc" <?= $sortBy === 'id_desc' ? 'selected' : '' ?>>Sort by ID: High to Low (Newest)</option>
                    <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Sort by Name (A to Z)</option>
                    <option value="name_desc" <?= $sortBy === 'name_desc' ? 'selected' : '' ?>>Sort by Name (Z to A)</option>
                    <option value="stock_asc" <?= $sortBy === 'stock_asc' ? 'selected' : '' ?>>Available Stock: Low to High</option>
                    <option value="stock_desc" <?= $sortBy === 'stock_desc' ? 'selected' : '' ?>>Available Stock: High to Low</option>
                    <option value="val_desc" <?= $sortBy === 'val_desc' ? 'selected' : '' ?>>Valuation: High to Low</option>
                    <option value="val_asc" <?= $sortBy === 'val_asc' ? 'selected' : '' ?>>Valuation: Low to High</option>
                    <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Selling Price: Low to High</option>
                    <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Selling Price: High to Low</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Apply</button>
            <?php if ($search !== '' || $categoryId > 0 || $sortBy !== 'id_asc'): ?>
                <a href="<?= BASE_URL ?>inventory/inventory.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Physical Inventory Register (<?= count($stockItems) ?> items)</h2>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th class="text-right">Selling Price</th>
                    <th class="text-right">Weighted Avg Cost</th>
                    <th class="text-right">Available Stock</th>
                    <th class="text-right">Inventory Valuation</th>
                    <th class="text-right">Reorder Level</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stockItems)): ?>
                    <tr><td colspan="11" class="text-center" style="padding: 2.5rem;">No inventory records match your search or filter criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($stockItems as $it): ?>
                    <tr>
                        <td>#<?= $it['product_id'] ?></td>
                        <td><strong><?= htmlspecialchars($it['product_name']) ?></strong></td>
                        <td><?= htmlspecialchars($it['category_name']) ?></td>
                        <td><span class="badge badge-secondary"><?= htmlspecialchars($it['unit']) ?></span></td>
                        <td class="text-right"><?= formatCurrency($it['selling_price']) ?></td>
                        <td class="text-right text-muted"><?= formatCurrency($it['average_cost']) ?></td>
                        <td class="text-right" style="font-weight: 700; font-size: 1rem;">
                            <?= formatQuantity($it['quantity_available'], $it['unit']) ?>
                        </td>
                        <td class="text-right font-weight-bold" style="color: var(--primary);">
                            <?= formatCurrency($it['current_inventory_value']) ?>
                        </td>
                        <td class="text-right"><?= formatQuantity($it['reorder_level'], $it['unit']) ?></td>
                        <td>
                            <?php
                                $cls = 'badge-success';
                                if ($it['stock_status'] === 'Low Stock') $cls = 'badge-warning';
                                if ($it['stock_status'] === 'Out of Stock') $cls = 'badge-danger';
                            ?>
                            <span class="badge <?= $cls ?>"><?= htmlspecialchars($it['stock_status']) ?></span>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($it['last_updated']) ?></small></td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="summary-table-highlight">
                    <td colspan="7" class="text-right"><strong>Grand Total Inventory Valuation:</strong></td>
                    <td class="text-right font-weight-bold" style="font-size: 1.15rem; color: var(--primary);">
                        <?= formatCurrency($totalValuation) ?>
                    </td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

