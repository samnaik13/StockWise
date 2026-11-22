<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$pdo = getDBConnection();
$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header("Location: " . BASE_URL . "products/view_products.php");
    exit;
}

try {
    // 1. Check if product exists
    $stmt = $pdo->prepare("SELECT product_name FROM `Product` WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $prod = $stmt->fetch();

    if (!$prod) {
        $_SESSION['flash_error'] = "Product #{$product_id} not found.";
        header("Location: " . BASE_URL . "products/view_products.php");
        exit;
    }

    $product_name = $prod['product_name'];

    // 2. Check for historical purchase items
    $stmtPur = $pdo->prepare("SELECT COUNT(*) FROM `Purchase_Item` WHERE product_id = ?");
    $stmtPur->execute([$product_id]);
    $purchaseCount = (int)$stmtPur->fetchColumn();

    // 3. Check for historical sale items
    $stmtSale = $pdo->prepare("SELECT COUNT(*) FROM `Sale_Item` WHERE product_id = ?");
    $stmtSale->execute([$product_id]);
    $saleCount = (int)$stmtSale->fetchColumn();

    if ($purchaseCount > 0 || $saleCount > 0) {
        $_SESSION['flash_error'] = "Referential Integrity Protection: Cannot delete '{$product_name}'. It is associated with {$purchaseCount} purchase record(s) and {$saleCount} sales transaction(s). Historical transaction audit data must be preserved.";
        header("Location: " . BASE_URL . "products/view_products.php");
        exit;
    }

    // 4. If no transactional history, delete inventory, stock movements, and product
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM `Stock_Movement` WHERE product_id = ?")->execute([$product_id]);
    $pdo->prepare("DELETE FROM `Inventory` WHERE product_id = ?")->execute([$product_id]);
    $pdo->prepare("DELETE FROM `Product` WHERE product_id = ?")->execute([$product_id]);
    $pdo->commit();

    $_SESSION['flash_success'] = "Product '{$product_name}' was deleted successfully.";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = "Database Deletion Error: " . $e->getMessage();
}

header("Location: " . BASE_URL . "products/view_products.php");
exit;

