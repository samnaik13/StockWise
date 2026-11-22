<?php
$currentPage = 'products';
$pageTitle = 'Product Catalog Management';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();

// Handle Search and Filter
$search = trim($_GET['search'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$sortBy = trim($_GET['sort'] ?? 'id_asc');

$query = "
    SELECT 
        p.product_id,
        p.product_name,
        c.category_name,
        s.supplier_name,
        p.unit,
        p.selling_price,
        p.reorder_level,
        p.created_at,
        i.quantity_available,
        i.average_cost,
        ROUND(i.quantity_available * i.average_cost, 2) AS stock_valuation,
        CASE 
            WHEN i.quantity_available = 0 THEN 'Out of Stock'
            WHEN i.quantity_available <= p.reorder_level THEN 'Low Stock'
            ELSE 'Adequate'
        END AS stock_status
    FROM `Product` p
    JOIN `Category` c ON p.category_id = c.category_id
    LEFT JOIN `Supplier` s ON p.supplier_id = s.supplier_id
    LEFT JOIN `Inventory` i ON p.product_id = i.product_id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $query .= " AND (p.product_name LIKE ? OR p.product_id = ?)";
    $params[] = "%$search%";
    $params[] = is_numeric($search) ? (int)$search : 0;
}

if ($categoryId > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

// Sorting logic: Default by ID ASC
switch ($sortBy) {
    case 'name_asc':
        $query .= " ORDER BY p.product_name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.product_name DESC";
        break;
    case 'id_desc':
        $query .= " ORDER BY p.product_id DESC";
        break;
    case 'price_asc':
        $query .= " ORDER BY p.selling_price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.selling_price DESC";
        break;
    case 'stock_asc':
        $query .= " ORDER BY i.quantity_available ASC";
        break;
    case 'stock_desc':
        $query .= " ORDER BY i.quantity_available DESC";
        break;
    case 'id_asc':
    default:
        $sortBy = 'id_asc';
        $query .= " ORDER BY p.product_id ASC";
        break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Categories for filter dropdown
$categories = $pdo->query("SELECT category_id, category_name FROM `Category` ORDER BY category_name ASC")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Product Catalog Management</h1>
        <p>Maintain grocery items, pricing, reorder thresholds, and physical inventory units</p>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>products/add_product.php" class="btn btn-primary">+ Add New Grocery Item</a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body" style="padding: 1rem 1.25rem;">
        <form method="GET" action="" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 0.85rem; font-weight: 500; display: block; margin-bottom: 0.3rem;">Search Products</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name or product ID..." value="<?= htmlspecialchars($search) ?>">
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
            <div style="width: 220px;">
                <label style="font-size: 0.85rem; font-weight: 500; display: block; margin-bottom: 0.3rem;">Sort Order</label>
                <select name="sort" class="form-control" onchange="this.form.submit()">
                    <option value="id_asc" <?= $sortBy === 'id_asc' ? 'selected' : '' ?>>Sort by ID: Low to High (Default)</option>
                    <option value="id_desc" <?= $sortBy === 'id_desc' ? 'selected' : '' ?>>Sort by ID: High to Low (Newest)</option>
                    <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Sort by Name (A to Z)</option>
                    <option value="name_desc" <?= $sortBy === 'name_desc' ? 'selected' : '' ?>>Sort by Name (Z to A)</option>
                    <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="stock_asc" <?= $sortBy === 'stock_asc' ? 'selected' : '' ?>>In Stock: Low to High</option>
                    <option value="stock_desc" <?= $sortBy === 'stock_desc' ? 'selected' : '' ?>>In Stock: High to Low</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Apply</button>
            <?php if ($search !== '' || $categoryId > 0 || $sortBy !== 'id_asc'): ?>
                <a href="<?= BASE_URL ?>products/view_products.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-header">
        <h2>Grocery Items Master List (<?= count($products) ?> items)</h2>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Wholesaler</th>
                    <th>Unit</th>
                    <th class="text-right">Selling Price</th>
                    <th class="text-right">Avg Cost</th>
                    <th class="text-right">In Stock</th>
                    <th class="text-right">Reorder Level</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="11" class="text-center" style="padding: 2.5rem;">No grocery products matched your criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>#<?= $p['product_id'] ?></td>
                            <td><strong><?= htmlspecialchars($p['product_name']) ?></strong></td>
                            <td><?= htmlspecialchars($p['category_name']) ?></td>
                            <td><small style="color:var(--text-muted);"><?= htmlspecialchars($p['supplier_name'] ?? '—') ?></small></td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($p['unit']) ?></span></td>
                            <td class="text-right font-weight-bold"><?= formatCurrency($p['selling_price']) ?></td>
                            <td class="text-right text-muted"><?= formatCurrency($p['average_cost'] ?? 0) ?></td>
                            <td class="text-right" style="font-size: 1rem; font-weight: 600;">
                                <?= formatQuantity($p['quantity_available'] ?? 0, $p['unit']) ?>
                            </td>
                            <td class="text-right"><?= formatQuantity($p['reorder_level'], $p['unit']) ?></td>
                            <td>
                                <?php
                                    $st = $p['stock_status'];
                                    $cls = 'badge-success';
                                    if ($st === 'Low Stock') $cls = 'badge-warning';
                                    if ($st === 'Out of Stock') $cls = 'badge-danger';
                                ?>
                                <span class="badge <?= $cls ?>"><?= htmlspecialchars($st) ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>products/edit_product.php?id=<?= $p['product_id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                <a href="<?= BASE_URL ?>products/delete_product.php?id=<?= $p['product_id'] ?>" 
                                   class="btn btn-outline btn-sm confirm-delete" 
                                   data-name="<?= htmlspecialchars($p['product_name']) ?>"
                                   style="color:var(--danger);border-color:#ffcdd2;">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

