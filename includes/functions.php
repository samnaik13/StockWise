<?php
declare(strict_types=1);

const DEAD_STOCK_DAYS = 90;
const IN_MOVEMENTS = ['OPENING', 'PURCHASE', 'CUSTOMER_RETURN'];
const OUT_MOVEMENTS = ['SALE', 'INTERNAL_USE', 'DAMAGE', 'SUPPLIER_RETURN'];

function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function money(mixed $value): string { return '₹' . number_format((float)$value, 2); }
function qty(mixed $value): string { return rtrim(rtrim(number_format((float)$value, 3, '.', ','), '0'), '.'); }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function flash(string $type, string $message): void { $_SESSION['flash'] = ['type'=>$type, 'message'=>$message]; }
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void {
    $sent = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals(csrf_token(), $sent)) { http_response_code(419); exit('Invalid form token. Please go back and reload the page.'); }
}
function set_flash_and_redirect(string $type, string $message, string $url): never { flash($type, $message); redirect($url); }
function positive_decimal(mixed $value, string $label): float {
    if (!is_numeric($value) || (float)$value <= 0) throw new RuntimeException($label . ' must be greater than zero.');
    return round((float)$value, 3);
}
function nonnegative_money(mixed $value, string $label): float {
    if (!is_numeric($value) || (float)$value < 0) throw new RuntimeException($label . ' cannot be negative.');
    return round((float)$value, 2);
}
function inventory_row(PDO $pdo, int $productId, bool $lock = true): array {
    $sql = 'SELECT i.*, p.product_name, p.unit, p.selling_price FROM inventory i JOIN products p ON p.product_id=i.product_id WHERE i.product_id=?' . ($lock ? ' FOR UPDATE' : '');
    $st = $pdo->prepare($sql); $st->execute([$productId]);
    $row = $st->fetch();
    if (!$row) throw new RuntimeException('Product inventory record was not found.');
    return $row;
}
function insert_movement(PDO $pdo, int $productId, string $type, float $quantity, float $unitCost, string $remarks, ?string $referenceCode = null, ?string $movementDate = null): void {
    if ($movementDate !== null) {
        $st = $pdo->prepare('INSERT INTO stock_movements (product_id, movement_date, movement_type, quantity, unit_cost, remarks, reference_code) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $st->execute([$productId, $movementDate, $type, $quantity, $unitCost, $remarks, $referenceCode]);
    } else {
        $st = $pdo->prepare('INSERT INTO stock_movements (product_id, movement_date, movement_type, quantity, unit_cost, remarks, reference_code) VALUES (?, NOW(), ?, ?, ?, ?, ?)');
        $st->execute([$productId, $type, $quantity, $unitCost, $remarks, $referenceCode]);
    }
}
function apply_inbound(PDO $pdo, int $productId, string $type, float $amount, float $unitCost, string $remarks, ?string $referenceCode = null, ?string $movementDate = null): void {
    if (!in_array($type, IN_MOVEMENTS, true)) throw new RuntimeException('Invalid stock-in movement type.');
    $row = inventory_row($pdo, $productId);
    $oldQty = (float)$row['quantity_available'];
    $oldCost = (float)$row['average_cost'];
    $newQty = $oldQty + $amount;
    $newCost = $newQty > 0 ? (($oldQty * $oldCost) + ($amount * $unitCost)) / $newQty : 0;
    $st = $pdo->prepare('UPDATE inventory SET quantity_available=?, average_cost=?, last_updated=NOW() WHERE product_id=?');
    $st->execute([$newQty, round($newCost, 2), $productId]);
    insert_movement($pdo, $productId, $type, $amount, $unitCost, $remarks, $referenceCode, $movementDate);
}
function apply_outbound(PDO $pdo, int $productId, string $type, float $amount, string $remarks, ?string $referenceCode = null, ?string $movementDate = null): float {
    if (!in_array($type, OUT_MOVEMENTS, true)) throw new RuntimeException('Invalid stock-out movement type.');
    $row = inventory_row($pdo, $productId);
    $available = (float)$row['quantity_available'];
    if ($amount > $available + 0.000001) throw new RuntimeException('Insufficient stock for ' . $row['product_name'] . '. Available: ' . qty($available) . ' ' . $row['unit'] . '.');
    $unitCost = (float)$row['average_cost'];
    $newQty = max(0, $available - $amount);
    $st = $pdo->prepare('UPDATE inventory SET quantity_available=?, last_updated=NOW() WHERE product_id=?');
    $st->execute([$newQty, $productId]);
    insert_movement($pdo, $productId, $type, $amount, $unitCost, $remarks, $referenceCode, $movementDate);
    return $unitCost;
}
function month_bounds(string $month): array {
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) throw new RuntimeException('Choose a valid reporting month.');
    $start = DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01');
    if (!$start) throw new RuntimeException('Choose a valid reporting month.');
    return [$start->format('Y-m-d'), $start->modify('+1 month')->format('Y-m-d')];
}
function product_options(PDO $pdo): array { return $pdo->query('SELECT product_id, product_name, unit, selling_price FROM products ORDER BY product_name')->fetchAll(); }
function category_options(PDO $pdo): array { return $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll(); }
