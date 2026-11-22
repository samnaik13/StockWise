<?php
$currentPage = 'products';
$pageTitle = 'Edit Grocery Product';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header("Location: " . BASE_URL . "products/view_products.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM `Product` WHERE `product_id` = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['flash_error'] = "Product ID #{$product_id} not found.";
    header("Location: " . BASE_URL . "products/view_products.php");
    exit;
}

$categories = $pdo->query("SELECT category_id, category_name FROM `Category` ORDER BY category_name ASC")->fetchAll();
$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM `Supplier` ORDER BY supplier_name ASC")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $supplier_id = (int)($_POST['supplier_id'] ?? 0) ?: null;
    $unit = trim($_POST['unit'] ?? '');
    $selling_price = (float)($_POST['selling_price'] ?? 0);
    $reorder_level = (float)($_POST['reorder_level'] ?? 0);

    if ($product_name === '') {
        $error = 'Product name is required.';
    } elseif ($category_id <= 0) {
        $error = 'Please select a valid grocery category.';
    } elseif ($unit === '') {
        $error = 'Please select a measurement unit.';
    } elseif ($selling_price < 0 || $reorder_level < 0) {
        $error = 'Selling price and reorder level cannot be negative.';
    } else {
        try {
            $stmtUpdate = $pdo->prepare("UPDATE `Product` SET `product_name` = ?, `category_id` = ?, `supplier_id` = ?, `selling_price` = ?, `reorder_level` = ?, `unit` = ? WHERE `product_id` = ?");
            $stmtUpdate->execute([$product_name, $category_id, $supplier_id, $selling_price, $reorder_level, $unit, $product_id]);

            $_SESSION['flash_success'] = "Product #{$product_id} ('{$product_name}') updated successfully.";
            header("Location: " . BASE_URL . "products/view_products.php");
            exit;
        } catch (Exception $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div class="page-title">
        <h1>Edit Product: <?= htmlspecialchars($product['product_name']) ?></h1>
        <p>Modify catalog attributes, selling price, and inventory alert threshold</p>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>products/view_products.php" class="btn btn-outline">&larr; Back to Products</a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2>Update Product Information</h2>
    </div>
    <div class="card-body">
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Product Name <span class="required">*</span></label>
                    <input type="text" name="product_name" class="form-control" required value="<?= htmlspecialchars($_POST['product_name'] ?? $product['product_name']) ?>">
                </div>

                <div class="form-group">
                    <label>Category <span class="required">*</span></label>
                    <select name="category_id" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= ((int)($_POST['category_id'] ?? $product['category_id']) === (int)$cat['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Wholesale Supplier</label>
                    <select name="supplier_id" class="form-control">
                        <option value="">-- No Supplier Assigned --</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= $sup['supplier_id'] ?>" <?= ((int)($_POST['supplier_id'] ?? $product['supplier_id']) === (int)$sup['supplier_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sup['supplier_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Unit of Measurement <span class="required">*</span></label>
                    <select name="unit" class="form-control" required>
                        <?php 
                        $units = ['kg', 'g', 'litre', 'ml', 'packet', 'piece', 'bottle', 'box', 'dozen'];
                        foreach ($units as $u):
                        ?>
                            <option value="<?= $u ?>" <?= (($_POST['unit'] ?? $product['unit']) === $u) ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Selling Price per Unit (₹) <span class="required">*</span></label>
                    <input type="number" step="0.01" min="0" name="selling_price" class="form-control" required value="<?= htmlspecialchars($_POST['selling_price'] ?? $product['selling_price']) ?>">
                    <div class="form-text">Note: Changing selling price will not affect historical sales records.</div>
                </div>

                <div class="form-group">
                    <label>Reorder Level <span class="required">*</span></label>
                    <input type="number" step="0.001" min="0" name="reorder_level" class="form-control" required value="<?= htmlspecialchars($_POST['reorder_level'] ?? $product['reorder_level']) ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Product</button>
                <a href="<?= BASE_URL ?>products/view_products.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

