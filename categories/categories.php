<?php
$currentPage = 'categories';
$pageTitle = 'Category Management';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$error = '';
$editCategory = null;

// Handle Add / Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $category_name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);

    if ($category_name === '') {
        $error = 'Category name cannot be empty.';
    } else {
        try {
            if ($action === 'edit' && $category_id > 0) {
                $stmt = $pdo->prepare("UPDATE `Category` SET `category_name` = ?, `description` = ? WHERE `category_id` = ?");
                $stmt->execute([$category_name, $description, $category_id]);
                $_SESSION['flash_success'] = "Category '{$category_name}' updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO `Category` (`category_name`, `description`) VALUES (?, ?)");
                $stmt->execute([$category_name, $description]);
                $_SESSION['flash_success'] = "Category '{$category_name}' created successfully.";
            }
            header("Location: " . BASE_URL . "categories/categories.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "A category with the name '{$category_name}' already exists (UNIQUE constraint).";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete Request
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    try {
        // Check if any products reference this category
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `Product` WHERE category_id = ?");
        $stmtCheck->execute([$delId]);
        $prodCount = (int)$stmtCheck->fetchColumn();

        if ($prodCount > 0) {
            $_SESSION['flash_error'] = "Foreign Key Integrity: Cannot delete category #{$delId}. There are {$prodCount} product(s) classified under it.";
        } else {
            $pdo->prepare("DELETE FROM `Category` WHERE category_id = ?")->execute([$delId]);
            $_SESSION['flash_success'] = "Category deleted successfully.";
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Delete Error: " . $e->getMessage();
    }
    header("Location: " . BASE_URL . "categories/categories.php");
    exit;
}

// Handle Edit Mode Fetch
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM `Category` WHERE category_id = ?");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

// Handle Search and Sorting for Categories
$search = trim($_GET['search'] ?? '');
$sortBy = trim($_GET['sort'] ?? 'id_asc');

$query = "
    SELECT 
        c.category_id,
        c.category_name,
        c.description,
        COUNT(p.product_id) AS total_products
    FROM `Category` c
    LEFT JOIN `Product` p ON c.category_id = p.category_id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $query .= " AND (c.category_name LIKE ? OR c.category_id = ?)";
    $params[] = "%$search%";
    $params[] = is_numeric($search) ? (int)$search : 0;
}

$query .= " GROUP BY c.category_id, c.category_name, c.description";

switch ($sortBy) {
    case 'id_desc':
        $query .= " ORDER BY c.category_id DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY c.category_name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY c.category_name DESC";
        break;
    case 'prod_desc':
        $query .= " ORDER BY total_products DESC, c.category_id ASC";
        break;
    case 'prod_asc':
        $query .= " ORDER BY total_products ASC, c.category_id ASC";
        break;
    case 'id_asc':
    default:
        $sortBy = 'id_asc';
        $query .= " ORDER BY c.category_id ASC";
        break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$categories = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Grocery Category Management</h1>
        <p>Organize products into departments (Grains, Pulses, Dairy, Spices, Household, etc.)</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">
    <!-- Category Form (Add / Edit) -->
    <div class="card">
        <div class="card-header">
            <h2><?= $editCategory ? 'Edit Category' : 'Add New Category' ?></h2>
        </div>
        <div class="card-body">
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="<?= $editCategory ? 'edit' : 'add' ?>">
                <?php if ($editCategory): ?>
                    <input type="hidden" name="category_id" value="<?= $editCategory['category_id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Category Name <span class="required">*</span></label>
                    <input type="text" name="category_name" class="form-control" required placeholder="e.g. Organic Pulses, Frozen Foods" value="<?= htmlspecialchars($_POST['category_name'] ?? $editCategory['category_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief explanation of items in this category"><?= htmlspecialchars($_POST['description'] ?? $editCategory['description'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $editCategory ? 'Update Category' : 'Save Category' ?></button>
                    <?php if ($editCategory): ?>
                        <a href="<?= BASE_URL ?>categories/categories.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Listing Table -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
            <h2>Existing Grocery Categories (<?= count($categories) ?>)</h2>
        </div>
        <div style="padding: 0.75rem 1.25rem; background: var(--bg-surface); border-bottom: 1px solid var(--border-color);">
            <form method="GET" action="" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                <input type="text" name="search" class="form-control" style="flex: 1; min-width: 140px; padding: 0.4rem 0.6rem; font-size: 0.85rem;" placeholder="Search category name or ID..." value="<?= htmlspecialchars($search) ?>">
                <select name="sort" class="form-control" style="width: 220px; padding: 0.4rem 0.6rem; font-size: 0.85rem;" onchange="this.form.submit()">
                    <option value="id_asc" <?= $sortBy === 'id_asc' ? 'selected' : '' ?>>Sort by ID: Low to High (Default)</option>
                    <option value="id_desc" <?= $sortBy === 'id_desc' ? 'selected' : '' ?>>Sort by ID: High to Low (Newest)</option>
                    <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Sort by Name (A to Z)</option>
                    <option value="name_desc" <?= $sortBy === 'name_desc' ? 'selected' : '' ?>>Sort by Name (Z to A)</option>
                    <option value="prod_desc" <?= $sortBy === 'prod_desc' ? 'selected' : '' ?>>Products: High to Low</option>
                    <option value="prod_asc" <?= $sortBy === 'prod_asc' ? 'selected' : '' ?>>Products: Low to High</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.45rem 0.8rem;">Apply</button>
                <?php if ($search !== '' || $sortBy !== 'id_asc'): ?>
                    <a href="<?= BASE_URL ?>categories/categories.php" class="btn btn-outline btn-sm" style="padding: 0.45rem 0.8rem;">Reset</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th class="text-center">Products</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="5" class="text-center" style="padding: 2rem;">No categories defined yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>#<?= $cat['category_id'] ?></td>
                                <td><strong><?= htmlspecialchars($cat['category_name']) ?></strong></td>
                                <td><small class="text-muted"><?= htmlspecialchars($cat['description'] ?? '-') ?></small></td>
                                <td class="text-center">
                                    <span class="badge badge-info"><?= $cat['total_products'] ?> items</span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= BASE_URL ?>categories/categories.php?edit=<?= $cat['category_id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <a href="<?= BASE_URL ?>categories/categories.php?delete=<?= $cat['category_id'] ?>" 
                                       class="btn btn-outline btn-sm confirm-delete"
                                       data-name="Category '<?= htmlspecialchars($cat['category_name']) ?>'"
                                       style="color:var(--danger);border-color:#ffcdd2;">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

