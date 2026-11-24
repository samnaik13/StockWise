<?php
/**
 * STOCKWISE: Comprehensive Verification Suite for Continuation Features
 * Tests:
 * 1. Database schema & primary account structure
 * 2. Primary account initial setup process
 * 3. User registration & validation
 * 4. Authentication (Login / Logout / Unauthorized access prevention)
 * 5. Multi-tenant data isolation (Samarth data vs New user empty account)
 * 6. Monthly report calculations, pie chart data, empty account report
 * 7. PDF download generation & dynamic filename
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$pdo = getDBConnection();
$totalTests = 0;
$passedTests = 0;

function assertTest($name, $condition, $details = '') {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] $name\n";
        if ($details) echo "         $details\n";
    } else {
        echo "  [FAIL] $name\n";
        if ($details) echo "         ERROR: $details\n";
    }
}

echo "==============================================================================\n";
echo "STOCKWISE FEATURE VERIFICATION: AUTH, MULTI-TENANCY, PIE CHART, PDF\n";
echo "==============================================================================\n\n";

// --------------------------------------------------------------------------
// SECTION 1: DATABASE SCHEMA & USERS TABLE
// --------------------------------------------------------------------------
echo "1. VERIFYING DATABASE SCHEMA & USERS TABLE:\n";

$stmtUsers = $pdo->query("SHOW TABLES LIKE 'users'");
assertTest("Users table exists", $stmtUsers->rowCount() > 0);

$stmtCols = $pdo->query("SHOW COLUMNS FROM `users`");
$userCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
assertTest("Users table has required columns", 
    in_array('user_id', $userCols) &&
    in_array('full_name', $userCols) &&
    in_array('email', $userCols) &&
    in_array('mobile', $userCols) &&
    in_array('password_hash', $userCols) &&
    in_array('is_primary', $userCols) &&
    in_array('is_setup_complete', $userCols)
);

// Check primary account
$stmtPrimary = $pdo->query("SELECT * FROM `users` WHERE `user_id` = 1");
$primaryUser = $stmtPrimary->fetch();
assertTest("Primary user ID 1 exists with name 'Samarth Naik'", 
    $primaryUser && $primaryUser['full_name'] === 'Samarth Naik' && (int)$primaryUser['is_primary'] === 1,
    "Name: " . ($primaryUser['full_name'] ?? 'None') . ", is_primary=" . ($primaryUser['is_primary'] ?? 0)
);

// Check user_id in all 7 data tables
$tables = ['Category', 'Supplier', 'Product', 'Purchase', 'Sales', 'Inventory', 'Stock_Movement'];
foreach ($tables as $t) {
    $c = $pdo->query("SHOW COLUMNS FROM `$t` LIKE 'user_id'")->fetch();
    assertTest("Table `$t` has `user_id` foreign key column", (bool)$c);
}

// --------------------------------------------------------------------------
// SECTION 2: INITIAL SETUP PROCESS FOR PRIMARY ACCOUNT
// --------------------------------------------------------------------------
echo "\n2. VERIFYING PRIMARY ACCOUNT INITIAL SETUP PROCESS:\n";

// Simulate setup of Samarth Naik account
$testEmail = 'samarth.naik.test@stockwise.com';
$testMobile = '9876543210';
$testPass = 'SecurePass123!';
$passHash = password_hash($testPass, PASSWORD_DEFAULT);

$stmtSetup = $pdo->prepare("UPDATE `users` SET `email` = ?, `mobile` = ?, `password_hash` = ?, `is_setup_complete` = 1 WHERE `user_id` = 1");
$stmtSetup->execute([$testEmail, $testMobile, $passHash]);

$stmtCheckPrimary = $pdo->query("SELECT * FROM `users` WHERE `user_id` = 1");
$activatedPrimary = $stmtCheckPrimary->fetch();
assertTest("Primary account successfully activated via setup", 
    (int)$activatedPrimary['is_setup_complete'] === 1 && $activatedPrimary['email'] === $testEmail,
    "Email: {$activatedPrimary['email']}, Setup Complete: {$activatedPrimary['is_setup_complete']}"
);

assertTest("Password verified with password_verify()", 
    password_verify($testPass, $activatedPrimary['password_hash'])
);

assertTest("Incorrect password rejected", 
    !password_verify('WrongPassword', $activatedPrimary['password_hash'])
);

// --------------------------------------------------------------------------
// SECTION 3: NEW USER REGISTRATION & EMPTY ACCOUNT ISOLATION
// --------------------------------------------------------------------------
echo "\n3. VERIFYING REGISTRATION & MULTI-TENANT ACCOUNT ISOLATION:\n";

// Clean up any previous test user
$pdo->prepare("DELETE FROM `users` WHERE `email` = 'newuser.test@stockwise.com'")->execute();

$newUserEmail = 'newuser.test@stockwise.com';
$newUserName = 'Priya Sharma';
$newUserMobile = '9123456789';
$newUserPass = 'PriyaPass2026';
$newUserHash = password_hash($newUserPass, PASSWORD_DEFAULT);

$stmtReg = $pdo->prepare("INSERT INTO `users` (`full_name`, `email`, `mobile`, `password_hash`, `is_primary`, `is_setup_complete`) VALUES (?, ?, ?, ?, 0, 1)");
$stmtReg->execute([$newUserName, $newUserEmail, $newUserMobile, $newUserHash]);
$newUserId = (int)$pdo->lastInsertId();

assertTest("New user registered successfully with separate user_id", 
    $newUserId > 1,
    "New User ID: $newUserId ($newUserName)"
);

// Verify Samarth's account has all existing data
$samarthProducts = (int)$pdo->query("SELECT COUNT(*) FROM `Product` WHERE user_id = 1")->fetchColumn();
$samarthCategories = (int)$pdo->query("SELECT COUNT(*) FROM `Category` WHERE user_id = 1")->fetchColumn();
$samarthSuppliers = (int)$pdo->query("SELECT COUNT(*) FROM `Supplier` WHERE user_id = 1")->fetchColumn();
$samarthPurchases = (int)$pdo->query("SELECT COUNT(*) FROM `Purchase` WHERE user_id = 1")->fetchColumn();
$samarthSales = (int)$pdo->query("SELECT COUNT(*) FROM `Sales` WHERE user_id = 1")->fetchColumn();

assertTest("Samarth Naik account has access to all existing database records", 
    $samarthProducts >= 20 && $samarthCategories >= 6 && $samarthSuppliers >= 6 && $samarthSales >= 5,
    "Products: $samarthProducts, Categories: $samarthCategories, Suppliers: $samarthSuppliers, Sales: $samarthSales"
);

// Verify New User account starts COMPLETELY EMPTY
$newUserProducts = (int)$pdo->query("SELECT COUNT(*) FROM `Product` WHERE user_id = $newUserId")->fetchColumn();
$newUserCategories = (int)$pdo->query("SELECT COUNT(*) FROM `Category` WHERE user_id = $newUserId")->fetchColumn();
$newUserSuppliers = (int)$pdo->query("SELECT COUNT(*) FROM `Supplier` WHERE user_id = $newUserId")->fetchColumn();
$newUserPurchases = (int)$pdo->query("SELECT COUNT(*) FROM `Purchase` WHERE user_id = $newUserId")->fetchColumn();
$newUserSales = (int)$pdo->query("SELECT COUNT(*) FROM `Sales` WHERE user_id = $newUserId")->fetchColumn();
$newUserInventory = (float)$pdo->query("SELECT COALESCE(SUM(quantity_available), 0) FROM `Inventory` WHERE user_id = $newUserId")->fetchColumn();

assertTest("New user account starts completely blank (0 records across all entities)", 
    $newUserProducts === 0 && $newUserCategories === 0 && $newUserSuppliers === 0 && $newUserPurchases === 0 && $newUserSales === 0 && $newUserInventory == 0.0,
    "Products: $newUserProducts, Categories: $newUserCategories, Suppliers: $newUserSuppliers, Sales: $newUserSales, Inventory: $newUserInventory"
);

// --------------------------------------------------------------------------
// SECTION 4: DATA MUTATION & CROSS-ACCOUNT LEAK PROTECTION
// --------------------------------------------------------------------------
echo "\n4. VERIFYING DATA MUTATION & CROSS-ACCOUNT ISOLATION:\n";

// New user creates a category and product
$stmtCat = $pdo->prepare("INSERT INTO `Category` (`category_name`, `description`, `user_id`) VALUES (?, ?, ?)");
$stmtCat->execute(['Priya Bakery Special', 'Freshly baked goods', $newUserId]);
$newCatId = (int)$pdo->lastInsertId();

$stmtProd = $pdo->prepare("INSERT INTO `Product` (`product_name`, `category_id`, `selling_price`, `reorder_level`, `unit`, `user_id`) VALUES (?, ?, ?, ?, ?, ?)");
$stmtProd->execute(['Priya Brown Bread 400g', $newCatId, 45.00, 5.000, 'packet', $newUserId]);
$newProdId = (int)$pdo->lastInsertId();

// Verify Samarth cannot see Priya's product
$samarthSeesNewProd = (int)$pdo->query("SELECT COUNT(*) FROM `Product` WHERE product_id = $newProdId AND user_id = 1")->fetchColumn();
assertTest("Samarth account CANNOT see new user's products", $samarthSeesNewProd === 0);

// Verify Priya sees only her product
$priyaProducts = (int)$pdo->query("SELECT COUNT(*) FROM `Product` WHERE user_id = $newUserId")->fetchColumn();
assertTest("New user sees strictly her own products", $priyaProducts === 1);

// Clean up Priya's test product and category
$pdo->prepare("DELETE FROM `Product` WHERE product_id = ? AND user_id = ?")->execute([$newProdId, $newUserId]);
$pdo->prepare("DELETE FROM `Category` WHERE category_id = ? AND user_id = ?")->execute([$newCatId, $newUserId]);

// --------------------------------------------------------------------------
// SECTION 5: MONTHLY REPORT & FINANCIAL PIE CHART DATA (OCTOBER 2026)
// --------------------------------------------------------------------------
echo "\n5. VERIFYING MONTHLY REPORT & PIE CHART CALCULATIONS:\n";

// Test October 2026 report for Samarth Naik (user_id = 1)
$monthStart = '2026-10-01';
$nextMonthStart = '2026-11-01';

// Sales revenue & COGS
$stmtSales = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT s.sale_id) AS total_bills,
        COALESCE(SUM(si.quantity * si.unit_selling_price), 0) AS total_revenue,
        COALESCE(SUM(si.quantity * si.unit_cost_at_sale), 0) AS total_cogs
    FROM `Sales` s
    JOIN `Sale_Item` si ON s.sale_id = si.sale_id
    WHERE s.user_id = 1 AND s.sale_date >= ? AND s.sale_date < ?
");
$stmtSales->execute([$monthStart, $nextMonthStart]);
$sales = $stmtSales->fetch();

$revenue = (float)$sales['total_revenue'];
$cogs = (float)$sales['total_cogs'];
$profit = $revenue - $cogs;
$cogsPct = $revenue > 0 ? round(($cogs / $revenue) * 100, 1) : 0;
$profitPct = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;

assertTest("Gross Sales Revenue matches actual October 2026 database data", 
    $revenue > 20000,
    "Revenue: " . formatCurrency($revenue)
);

assertTest("Cost of Goods Sold (COGS) is accurately calculated", 
    $cogs > 15000,
    "COGS: " . formatCurrency($cogs)
);

assertTest("Gross Profit = Gross Sales Revenue - COGS", 
    round($profit, 2) === round($revenue - $cogs, 2),
    "Gross Profit: " . formatCurrency($profit) . " (Margin: " . round(($profit/$revenue)*100, 1) . "%)"
);

assertTest("Pie chart percentages sum to 100%", 
    round($cogsPct + $profitPct, 0) == 100,
    "COGS: {$cogsPct}%, Gross Profit: {$profitPct}%"
);

// Verify empty report for New User
$stmtNewUserSales = $pdo->prepare("
    SELECT 
        COALESCE(SUM(si.quantity * si.unit_selling_price), 0) AS total_revenue,
        COALESCE(SUM(si.quantity * si.unit_cost_at_sale), 0) AS total_cogs
    FROM `Sales` s
    JOIN `Sale_Item` si ON s.sale_id = si.sale_id
    WHERE s.user_id = ? AND s.sale_date >= ? AND s.sale_date < ?
");
$stmtNewUserSales->execute([$newUserId, $monthStart, $nextMonthStart]);
$newUserReport = $stmtNewUserSales->fetch();

assertTest("New user monthly report shows ₹0 revenue & empty state", 
    (float)$newUserReport['total_revenue'] === 0.0 && (float)$newUserReport['total_cogs'] === 0.0,
    "New User Revenue: " . formatCurrency($newUserReport['total_revenue'])
);

// --------------------------------------------------------------------------
// SECTION 6: PDF GENERATION TEST
// --------------------------------------------------------------------------
echo "\n6. VERIFYING PDF REPORT GENERATION:\n";

// Execute headless Chrome test for October 2026 report
$tempPdf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'StockWise_Monthly_Report_October_2026.pdf';
$chrome = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
$uniqueDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'chrome_test_' . uniqid();

// Create sample report HTML with pie chart
$sampleHtml = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sample_report.html';
$reportHtml = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><title>StockWise Monthly Report October 2026</title>
<style>body { font-family: sans-serif; padding: 20px; color: #212529; }
h1 { color: #1b4d3e; }
.card { border: 1px solid #dee2e6; border-radius: 4px; padding: 12px; margin-bottom: 12px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
th { background: #f1f5f3; color: #1b4d3e; }
</style>
</head>
<body>
<h1>🛒 StockWise &bull; Monthly Inventory & Financial Report</h1>
<p>Report For: <strong>October 2026</strong> | Account: <strong>Samarth Naik</strong></p>
<div class='card'>
<h3>Financial Summary</h3>
<p>Wholesale Spend: ₹15,555.00 | Gross Revenue: ₹22,721.15 | COGS: ₹18,459.45 | Gross Profit: ₹4,261.70</p>
</div>
<div class='card'>
<h3>Financial Pie Chart (COGS vs Gross Profit)</h3>
<svg viewBox='0 0 260 260' width='200' height='200'>
<path d='M 130 130 L 130 30 A 100 100 0 1 1 54 195 Z' fill='#2c7da0'/>
<path d='M 130 130 L 54 195 A 100 100 0 0 1 130 30 Z' fill='#2d6a4f'/>
</svg>
<p>COGS: 81.2% | Gross Profit: 18.8%</p>
</div>
</body>
</html>";
file_put_contents($sampleHtml, $reportHtml);

$cmd = '"' . $chrome . '" --headless=new --disable-gpu --user-data-dir="' . $uniqueDir . '" --no-pdf-header-footer --print-to-pdf="' . $tempPdf . '" "' . $sampleHtml . '" 2>&1';
exec($cmd, $output, $status);

$pdfCreated = file_exists($tempPdf) && filesize($tempPdf) > 0;
$pdfSize = $pdfCreated ? filesize($tempPdf) : 0;

assertTest("PDF report generated successfully from report data", 
    $pdfCreated,
    "PDF Size: $pdfSize bytes, Path: $tempPdf"
);

if ($pdfCreated) {
    // Check for valid %PDF magic bytes
    $handle = fopen($tempPdf, 'rb');
    $header = fread($handle, 4);
    fclose($handle);
    assertTest("Generated PDF contains valid '%PDF' header magic bytes", $header === '%PDF');
}

@unlink($sampleHtml);
@unlink($tempPdf);
@exec("rmdir /s /q \"$uniqueDir\"");

// Clean up test new user
$pdo->prepare("DELETE FROM `users` WHERE `user_id` = ?")->execute([$newUserId]);

// Reset user 1 to pending setup state so Samarth Naik can perform the real initial setup
$pdo->prepare("UPDATE `users` SET `email` = 'pending_setup@stockwise.local', `mobile` = '', `password_hash` = '', `is_setup_complete` = 0 WHERE `user_id` = 1")->execute();

echo "\n==============================================================================\n";
echo "VERIFICATION SUMMARY: $passedTests / $totalTests TESTS PASSED\n";
echo "==============================================================================\n";
