<?php
$currentPage = 'suppliers';
$pageTitle = 'Wholesale Supplier Management';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$userId = getCurrentUserId();
$error = '';
$editSupplier = null;

// Handle Add / Edit Supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $supplier_name = trim($_POST['supplier_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $supplier_id = (int)($_POST['supplier_id'] ?? 0);

    if ($supplier_name === '') {
        $error = 'Supplier / Vendor business name is required.';
    } else {
        try {
            if ($action === 'edit' && $supplier_id > 0) {
                $stmt = $pdo->prepare("UPDATE `Supplier` SET `supplier_name` = ?, `phone` = ?, `email` = ?, `address` = ? WHERE `supplier_id` = ? AND `user_id` = ?");
                $stmt->execute([$supplier_name, $phone, $email, $address, $supplier_id, $userId]);
                $_SESSION['flash_success'] = "Supplier '{$supplier_name}' updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO `Supplier` (`supplier_name`, `phone`, `email`, `address`, `user_id`) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$supplier_name, $phone, $email, $address, $userId]);
                $_SESSION['flash_success'] = "Supplier '{$supplier_name}' added successfully.";
            }
            header("Location: " . BASE_URL . "suppliers/suppliers.php");
            exit;
        } catch (Exception $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}

// Handle Delete Supplier (Supports Force Delete for linked purchase invoices)
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $force = isset($_GET['force']) && $_GET['force'] == '1';
    try {
        $stmtSupp = $pdo->prepare("SELECT supplier_name FROM `Supplier` WHERE supplier_id = ? AND user_id = ?");
        $stmtSupp->execute([$delId, $userId]);
        $suppName = $stmtSupp->fetchColumn();

        if (!$suppName) {
            $_SESSION['flash_error'] = "Supplier #{$delId} not found.";
            header("Location: " . BASE_URL . "suppliers/suppliers.php");
            exit;
        }

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `Purchase` WHERE supplier_id = ? AND user_id = ?");
        $stmtCheck->execute([$delId, $userId]);
        $purCount = (int)$stmtCheck->fetchColumn();

        if ($purCount > 0 && !$force) {
            $_SESSION['flash_force_delete_id'] = $delId;
            $_SESSION['flash_force_delete_name'] = $suppName;
            $_SESSION['flash_force_delete_count'] = $purCount;
            $_SESSION['flash_error'] = "Supplier '{$suppName}' has {$purCount} linked purchase invoice(s). See below to confirm deletion.";
        } else {
            $pdo->beginTransaction();
            if ($purCount > 0) {
                // Delete purchase items first
                $pdo->prepare("
                    DELETE pi FROM `Purchase_Item` pi 
                    JOIN `Purchase` p ON pi.purchase_id = p.purchase_id 
                    WHERE p.supplier_id = ? AND p.user_id = ?
                ")->execute([$delId, $userId]);

                // Delete purchase headers
                $pdo->prepare("DELETE FROM `Purchase` WHERE supplier_id = ? AND user_id = ?")->execute([$delId, $userId]);
            }

            // Delete supplier
            $pdo->prepare("DELETE FROM `Supplier` WHERE supplier_id = ? AND user_id = ?")->execute([$delId, $userId]);
            $pdo->commit();

            $_SESSION['flash_success'] = "Supplier '{$suppName}' " . ($purCount > 0 ? "and {$purCount} linked purchase invoice(s) " : "") . "were deleted successfully.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['flash_error'] = "Delete Error: " . $e->getMessage();
    }
    header("Location: " . BASE_URL . "suppliers/suppliers.php");
    exit;
}

// Handle Edit Fetch
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM `Supplier` WHERE supplier_id = ? AND user_id = ?");
    $stmt->execute([$editId, $userId]);
    $editSupplier = $stmt->fetch();
}

// View Purchases for a specific supplier
$viewSupplierId = (int)($_GET['view'] ?? 0);
$supplierPurchases = [];
$activeSupplier = null;
if ($viewSupplierId > 0) {
    $stmtSupp = $pdo->prepare("SELECT * FROM `Supplier` WHERE supplier_id = ? AND user_id = ?");
    $stmtSupp->execute([$viewSupplierId, $userId]);
    $activeSupplier = $stmtSupp->fetch();

    $stmtPur = $pdo->prepare("
        SELECT 
            p.purchase_id,
            p.invoice_number,
            p.purchase_date,
            COUNT(pi.purchase_item_id) AS total_items,
            ROUND(SUM(pi.quantity * pi.unit_cost), 2) AS invoice_total
        FROM `Purchase` p
        JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id
        WHERE p.supplier_id = ? AND p.user_id = ?
        GROUP BY p.purchase_id, p.invoice_number, p.purchase_date
        ORDER BY p.purchase_date DESC
    ");
    $stmtPur->execute([$viewSupplierId, $userId]);
    $supplierPurchases = $stmtPur->fetchAll();
}

// Fetch all suppliers with purchase stats scoped to user
$stmtAllSupp = $pdo->prepare("
    SELECT 
        s.supplier_id,
        s.supplier_name,
        s.phone,
        s.email,
        s.address,
        COUNT(DISTINCT p.purchase_id) AS total_invoices,
        COALESCE(SUM(pi.quantity * pi.unit_cost), 0) AS total_spend
    FROM `Supplier` s
    LEFT JOIN `Purchase` p ON s.supplier_id = p.supplier_id AND p.user_id = ?
    LEFT JOIN `Purchase_Item` pi ON p.purchase_id = pi.purchase_id
    WHERE s.user_id = ?
    GROUP BY s.supplier_id, s.supplier_name, s.phone, s.email, s.address
    ORDER BY s.supplier_id ASC
");
$stmtAllSupp->execute([$userId, $userId]);
$suppliers = $stmtAllSupp->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Wholesale Supplier Management</h1>
        <p>Maintain vendor contacts, wholesale distributors, and track supplier procurement history</p>
    </div>
</div>

<?php if (isset($_SESSION['flash_force_delete_id'])): ?>
    <!-- Force Delete Action Banner -->
    <div class="alert alert-warning" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-left: 5px solid var(--warning);">
        <div>
            <strong>Referential Integrity Notice:</strong><br>
            Supplier <strong><?= htmlspecialchars($_SESSION['flash_force_delete_name']) ?></strong> has <?= (int)$_SESSION['flash_force_delete_count'] ?> associated purchase invoice(s).<br>
            <span style="font-size: 0.88rem; color: #663c00;">Would you like to delete this supplier along with all their purchase records?</span>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= BASE_URL ?>suppliers/suppliers.php?delete=<?= (int)$_SESSION['flash_force_delete_id'] ?>&force=1" class="btn btn-danger btn-sm">Yes, Delete Supplier & Invoices</a>
            <a href="<?= BASE_URL ?>suppliers/suppliers.php" class="btn btn-outline btn-sm">Cancel</a>
        </div>
    </div>
    <?php 
    unset($_SESSION['flash_force_delete_id']); 
    unset($_SESSION['flash_force_delete_name']); 
    unset($_SESSION['flash_force_delete_count']); 
    ?>
<?php endif; ?>

<?php if ($activeSupplier): ?>
    <!-- Supplier Purchase History Panel -->
    <div class="card" style="border: 2px solid var(--primary); margin-bottom: 2rem;">
        <div class="card-header" style="background-color: var(--primary-subtle);">
            <div>
                <h2>Purchase History for: <?= htmlspecialchars($activeSupplier['supplier_name']) ?></h2>
                <small>Supplier ID: #<?= $activeSupplier['supplier_id'] ?> | Phone: <?= htmlspecialchars($activeSupplier['phone'] ?? 'N/A') ?></small>
            </div>
            <a href="<?= BASE_URL ?>suppliers/suppliers.php" class="btn btn-outline btn-sm">Close View</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Purchase Date</th>
                        <th class="text-center">Line Items</th>
                        <th class="text-right">Total Invoice Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($supplierPurchases)): ?>
                        <tr><td colspan="4" class="text-center" style="padding: 1.5rem;">No purchases on record for this vendor.</td></tr>
                    <?php else: ?>
                        <?php foreach ($supplierPurchases as $sp): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($sp['invoice_number']) ?></strong></td>
                                <td><?= htmlspecialchars($sp['purchase_date']) ?></td>
                                <td class="text-center"><?= $sp['total_items'] ?> items</td>
                                <td class="text-right font-weight-bold"><?= formatCurrency($sp['invoice_total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="supplier-grid">
    <!-- Add / Edit Supplier Form -->
    <div class="card">
        <div class="card-header">
            <h2><?= $editSupplier ? 'Edit Supplier' : 'Register New Supplier' ?></h2>
        </div>
        <div class="card-body">
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="<?= $editSupplier ? 'edit' : 'add' ?>">
                <?php if ($editSupplier): ?>
                    <input type="hidden" name="supplier_id" value="<?= $editSupplier['supplier_id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Supplier / Company Name <span class="required">*</span></label>
                    <input type="text" name="supplier_name" class="form-control" required placeholder="e.g. Royal Grains Wholesalers" value="<?= htmlspecialchars($_POST['supplier_name'] ?? $editSupplier['supplier_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="e.g. 9822011223" value="<?= htmlspecialchars($_POST['phone'] ?? $editSupplier['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="e.g. sales@wholesaler.com" value="<?= htmlspecialchars($_POST['email'] ?? $editSupplier['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Physical Address / Warehouse</label>
                    <textarea name="address" class="form-control" rows="3" style="resize: vertical; min-height: 85px; font-family: inherit; line-height: 1.45;" placeholder="Plot / Shop Number, Wholesale Market, Street, City"><?= htmlspecialchars($_POST['address'] ?? $editSupplier['address'] ?? '') ?></textarea>
                    <div class="form-text">Warehouse address or wholesale trading office.</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" style="flex: 1;"><?= $editSupplier ? 'Update Supplier' : 'Save Supplier' ?></button>
                    <?php if ($editSupplier): ?>
                        <a href="<?= BASE_URL ?>suppliers/suppliers.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Suppliers Table -->
    <div class="card">
        <div class="card-header">
            <h2>Active Supplier Directory (<?= count($suppliers) ?> vendors)</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 22%;">Supplier</th>
                        <th style="width: 18%;">Contact Info</th>
                        <th style="width: 32%; min-width: 200px;">Address / Warehouse</th>
                        <th style="width: 10%;" class="text-center">Invoices</th>
                        <th style="width: 10%;" class="text-right">Total Spend</th>
                        <th style="width: 8%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr><td colspan="6" class="text-center" style="padding: 2rem;">No suppliers registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $s): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($s['supplier_name']) ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">ID: #<?= $s['supplier_id'] ?></div>
                                </td>
                                <td>
                                    <div><strong><?= htmlspecialchars($s['phone'] ?? '-') ?></strong></div>
                                    <small class="text-muted"><?= htmlspecialchars($s['email'] ?? '') ?></small>
                                </td>
                                <td class="col-address">
                                    <?= nl2br(htmlspecialchars($s['address'] ?? '-')) ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= BASE_URL ?>suppliers/suppliers.php?view=<?= $s['supplier_id'] ?>" class="badge badge-info" style="text-decoration:none;" title="View past invoices">
                                        <?= $s['total_invoices'] ?> invoices
                                    </a>
                                </td>
                                <td class="text-right font-weight-bold">
                                    <?= formatCurrency($s['total_spend']) ?>
                                </td>
                                <td class="text-center">
                                    <div style="display: flex; gap: 0.3rem; justify-content: center;">
                                        <a href="<?= BASE_URL ?>suppliers/suppliers.php?edit=<?= $s['supplier_id'] ?>" class="btn btn-outline btn-sm" title="Edit Supplier">Edit</a>
                                        <a href="<?= BASE_URL ?>suppliers/suppliers.php?delete=<?= $s['supplier_id'] ?><?= $s['total_invoices'] > 0 ? '&force=1' : '' ?>" 
                                           class="btn btn-outline btn-sm confirm-delete-supplier" 
                                           data-name="<?= htmlspecialchars($s['supplier_name']) ?>"
                                           data-invoices="<?= (int)$s['total_invoices'] ?>"
                                           style="color:var(--danger);border-color:#ffcdd2;"
                                           title="Delete Supplier">Delete</a>
                                    </div>
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
