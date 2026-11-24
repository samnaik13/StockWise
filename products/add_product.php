<?php
$currentPage = 'products';
$pageTitle = 'Add New Grocery Product';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$userId = getCurrentUserId();
$error = '';

$stmtCat = $pdo->prepare("SELECT category_id, category_name FROM `Category` WHERE user_id = ? ORDER BY category_name ASC");
$stmtCat->execute([$userId]);
$categories = $stmtCat->fetchAll();

$stmtSup = $pdo->prepare("SELECT supplier_id, supplier_name FROM `Supplier` WHERE user_id = ? ORDER BY supplier_name ASC");
$stmtSup->execute([$userId]);
$suppliers = $stmtSup->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $supplier_id = (int)($_POST['supplier_id'] ?? 0) ?: null;
    $unit = trim($_POST['unit'] ?? '');
    $selling_price = (float)($_POST['selling_price'] ?? 0);
    $reorder_level = (float)($_POST['reorder_level'] ?? 0);
    $initial_stock = (float)($_POST['initial_stock'] ?? 0);
    $initial_cost = (float)($_POST['initial_cost'] ?? 0);

    // Backend Validation
    if ($product_name === '') {
        $error = 'Product name is required.';
    } elseif ($category_id <= 0) {
        $error = 'Please select a valid grocery category.';
    } elseif ($unit === '') {
        $error = 'Please select a measurement unit.';
    } elseif ($selling_price < 0) {
        $error = 'Selling price cannot be negative.';
    } elseif ($reorder_level < 0) {
        $error = 'Reorder level cannot be negative.';
    } elseif ($initial_stock < 0 || $initial_cost < 0) {
        $error = 'Initial stock and cost cannot be negative.';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Insert into Product with user_id
            $stmt = $pdo->prepare("INSERT INTO `Product` (`product_name`, `category_id`, `supplier_id`, `selling_price`, `reorder_level`, `unit`, `created_at`, `user_id`) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->execute([$product_name, $category_id, $supplier_id, $selling_price, $reorder_level, $unit, $userId]);
            $product_id = $pdo->lastInsertId();

            // 2. Initialize 1:1 row in Inventory with user_id
            $stmtInv = $pdo->prepare("INSERT INTO `Inventory` (`product_id`, `quantity_available`, `average_cost`, `last_updated`, `user_id`) VALUES (?, ?, ?, NOW(), ?)");
            $stmtInv->execute([$product_id, $initial_stock, $initial_cost, $userId]);

            // 3. If initial stock > 0, record OPENING in Stock_Movement with user_id
            if ($initial_stock > 0) {
                $stmtMove = $pdo->prepare("INSERT INTO `Stock_Movement` (`product_id`, `movement_date`, `movement_type`, `quantity`, `remarks`, `user_id`) VALUES (?, CURDATE(), 'OPENING', ?, 'Initial opening stock balance at product creation', ?)");
                $stmtMove->execute([$product_id, $initial_stock, $userId]);
            }

            $pdo->commit();
            $_SESSION['flash_success'] = "Grocery product '{$product_name}' registered successfully with {$initial_stock} {$unit} opening stock.";
            header("Location: " . BASE_URL . "products/view_products.php");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div class="page-title">
        <h1>Register New Grocery Item</h1>
        <p>Define product identity, retail pricing, measurement unit, and initial physical stock</p>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>products/view_products.php" class="btn btn-outline">&larr; Back to Products</a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2>Product Details Form</h2>
    </div>
    <div class="card-body">
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Product Name <span class="required">*</span></label>
                    <input type="text" name="product_name" class="form-control" required placeholder="e.g. Sona Masoori Rice, Mustard Oil, Toor Dal" value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Grocery Category <span class="required">*</span></label>
                    <select name="category_id" class="form-control" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= ((int)($_POST['category_id'] ?? 0) === (int)$cat['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Wholesale Supplier (Default Vendor)</label>
                    <select name="supplier_id" class="form-control">
                        <option value="">-- Select Wholesaler (Optional) --</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= $sup['supplier_id'] ?>" <?= ((int)($_POST['supplier_id'] ?? 0) === (int)$sup['supplier_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sup['supplier_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Links this product to its primary wholesale distributor.</div>
                </div>

                <div class="form-group">
                    <label>Unit of Measurement (UOM) <span class="required">*</span></label>
                    <select name="unit" class="form-control" required>
                        <option value="">-- Select Unit --</option>
                        <?php 
                        $units = ['kg', 'g', 'litre', 'ml', 'packet', 'piece', 'bottle', 'box', 'dozen'];
                        foreach ($units as $u):
                        ?>
                            <option value="<?= $u ?>" <?= (($_POST['unit'] ?? '') === $u) ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">All sales, purchases & inventory will strictly use this unit.</div>
                </div>

                <div class="form-group">
                    <label>Retail Selling Price per Unit (₹) <span class="required">*</span></label>
                    <input type="number" step="0.01" min="0" name="selling_price" class="form-control" required placeholder="0.00" value="<?= htmlspecialchars($_POST['selling_price'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Reorder Level (Stock Alert Threshold) <span class="required">*</span></label>
                    <input type="number" step="0.001" min="0" name="reorder_level" class="form-control" required placeholder="0.000" value="<?= htmlspecialchars($_POST['reorder_level'] ?? '10.000') ?>">
                    <div class="form-text">Triggers Low Stock Alert when available stock &le; threshold.</div>
                </div>

                <div class="form-group">
                    <label>Initial Opening Stock Quantity</label>
                    <input type="number" step="0.001" min="0" name="initial_stock" class="form-control" placeholder="0.000" value="<?= htmlspecialchars($_POST['initial_stock'] ?? '0') ?>">
                    <div class="form-text">Optional initial balance. Automatically logged as OPENING stock.</div>
                </div>

                <div class="form-group">
                    <label>Initial Purchase / Valuation Cost (₹)</label>
                    <input type="number" step="0.01" min="0" name="initial_cost" class="form-control" placeholder="0.00" value="<?= htmlspecialchars($_POST['initial_cost'] ?? '0.00') ?>">
                    <div class="form-text">Initial cost per unit for Weighted Average Cost tracking.</div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Product</button>
                <a href="<?= BASE_URL ?>products/view_products.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

