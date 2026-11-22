<?php
$currentPage = 'inventory';
$pageTitle = 'Record Stock Adjustment';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$error = '';

$categories = $pdo->query("SELECT category_id, category_name FROM `Category` ORDER BY category_name ASC")->fetchAll();

$products = $pdo->query("
    SELECT 
        p.product_id, 
        p.product_name, 
        p.category_id, 
        c.category_name, 
        p.unit, 
        COALESCE(i.quantity_available, 0) as quantity_available 
    FROM `Product` p 
    JOIN `Category` c ON p.category_id = c.category_id
    JOIN `Inventory` i ON p.product_id = i.product_id 
    ORDER BY c.category_name ASC, p.product_name ASC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $movement_type = trim($_POST['movement_type'] ?? '');
    $movement_date = trim($_POST['movement_date'] ?? date('Y-m-d'));
    $quantity = (float)($_POST['quantity'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');

    if ($product_id <= 0) {
        $error = 'Please select a grocery product.';
    } elseif ($quantity <= 0) {
        $error = 'Adjustment quantity must be strictly greater than zero.';
    } elseif ($remarks === '') {
        $error = 'Please provide an audit reason/remark for this stock adjustment.';
    } else {
        $result = recordStockAdjustment($product_id, $movement_date, $movement_type, $quantity, $remarks);
        if ($result['success']) {
            $_SESSION['flash_success'] = "Stock adjustment '{$movement_type}' recorded successfully for {$quantity} units.";
            header("Location: " . BASE_URL . "inventory/stock_history.php");
            exit;
        } else {
            $error = 'Adjustment Failed: ' . $result['error'];
        }
    }
}
?>

<div class="page-header">
    <div class="page-title">
        <h1>Record Non-Sale Stock Adjustment</h1>
        <p>Document internal consumption, damaged goods, spoilage shrinkage, or customer/supplier returns</p>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>inventory/inventory.php" class="btn btn-outline">&larr; Back to Inventory</a>
    </div>
</div>

<div class="card" style="max-width: 750px; margin: 0 auto;">
    <div class="card-header">
        <h2>Stock Adjustment Form</h2>
    </div>
    <div class="card-body">
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                        <div>
                            <label>Filter by Category</label>
                            <select id="adj-category" class="form-control">
                                <option value="">-- All Categories --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Choose a category to filter the product options.</div>
                        </div>
                        <div>
                            <label>Select Product <span class="required">*</span></label>
                            <select name="product_id" class="form-control" required id="adj-product">
                                <option value="">-- Choose Grocery Product --</option>
                                <?php 
                                $currCat = null;
                                foreach ($products as $p): 
                                    if ($p['category_name'] !== $currCat):
                                        if ($currCat !== null) echo '</optgroup>';
                                        $currCat = $p['category_name'];
                                        echo '<optgroup label="' . htmlspecialchars($currCat) . '">';
                                    endif;
                                ?>
                                    <option value="<?= $p['product_id'] ?>" 
                                            data-category="<?= $p['category_id'] ?>" 
                                            data-unit="<?= htmlspecialchars($p['unit']) ?>" 
                                            data-stock="<?= $p['quantity_available'] ?>">
                                        <?= htmlspecialchars($p['product_name']) ?> (Current Stock: <?= formatQuantity($p['quantity_available'], $p['unit']) ?>)
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($currCat !== null) echo '</optgroup>'; ?>
                            </select>
                            <div id="adj-stock-hint" style="margin-top: 4px; font-size: 0.85rem; font-weight: 600; color: var(--primary);"></div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Adjustment Type <span class="required">*</span></label>
                    <select name="movement_type" class="form-control" required>
                        <option value="INTERNAL_USE">INTERNAL USE (Deducts Stock - In-store consumption)</option>
                        <option value="DAMAGE">DAMAGE (Deducts Stock - Spoilage, leakage, expired)</option>
                        <option value="SUPPLIER_RETURN">SUPPLIER RETURN (Deducts Stock - Returned to wholesaler)</option>
                        <option value="CUSTOMER_RETURN">CUSTOMER RETURN (Increases Stock - Unopened return)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date of Adjustment <span class="required">*</span></label>
                    <input type="date" name="movement_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label>Quantity <span class="required">*</span></label>
                    <input type="number" step="0.001" min="0.001" name="quantity" class="form-control" required placeholder="0.000">
                    <div class="form-text">Always entered as a positive number. Movement type determines addition or subtraction.</div>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Audit Remarks / Reason <span class="required">*</span></label>
                    <input type="text" name="remarks" class="form-control" required placeholder="e.g. Milk pouch leaked during handling; Soap bar used for shop handwash">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-warning">Apply Stock Adjustment</button>
                <a href="<?= BASE_URL ?>inventory/inventory.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
const adjProducts = <?= json_encode($products) ?>;
const catSelect = document.getElementById('adj-category');
const prodSelect = document.getElementById('adj-product');
const stockHint = document.getElementById('adj-stock-hint');

function updateStockHint() {
    const opt = prodSelect.options[prodSelect.selectedIndex];
    if (opt && opt.value) {
        const unit = opt.getAttribute('data-unit') || '';
        const stock = opt.getAttribute('data-stock') || '0';
        stockHint.textContent = `Available in Warehouse: ${parseFloat(stock).toFixed(3).replace(/\.?0+$/, '')} ${unit}`;
    } else {
        stockHint.textContent = '';
    }
}

catSelect.addEventListener('change', () => {
    const catId = catSelect.value;
    const currentVal = prodSelect.value;
    prodSelect.innerHTML = '<option value="">-- Choose Grocery Product --</option>';

    let filtered = adjProducts;
    if (catId !== '') {
        filtered = filtered.filter(p => String(p.category_id) === String(catId));
    }

    let currCat = null;
    let optgroup = null;

    filtered.forEach(p => {
        if (catId === '') {
            if (p.category_name !== currCat) {
                currCat = p.category_name;
                optgroup = document.createElement('optgroup');
                optgroup.label = currCat;
                prodSelect.appendChild(optgroup);
            }
        }

        const opt = document.createElement('option');
        opt.value = p.product_id;
        const sVal = parseFloat(p.quantity_available) || 0;
        opt.textContent = `${p.product_name} (Current Stock: ${sVal.toFixed(3).replace(/\.?0+$/, '')} ${p.unit})`;
        opt.setAttribute('data-category', p.category_id);
        opt.setAttribute('data-unit', p.unit);
        opt.setAttribute('data-stock', p.quantity_available);

        if (String(p.product_id) === String(currentVal)) {
            opt.selected = true;
        }

        if (optgroup && catId === '') {
            optgroup.appendChild(opt);
        } else {
            prodSelect.appendChild(opt);
        }
    });

    updateStockHint();
});

prodSelect.addEventListener('change', () => {
    const opt = prodSelect.options[prodSelect.selectedIndex];
    if (opt && opt.value && catSelect.value === '') {
        const prodCat = opt.getAttribute('data-category');
        if (prodCat) {
            catSelect.value = prodCat;
        }
    }
    updateStockHint();
});

updateStockHint();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

