<?php
/**
 * STOCKWISE: Authentication & Session Guard
 * Include this file at the top of every protected page.
 * Handles session management, login verification, and user context.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Get current logged-in user's ID
 */
function getCurrentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

/**
 * Get current logged-in user's name
 */
function getCurrentUserName(): string {
    return $_SESSION['user_name'] ?? '';
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        $baseUrl = getBaseUrl();
        header('Location: ' . $baseUrl . 'login.php');
        exit;
    }
}

/**
 * Check if primary account setup is pending
 */
function isPrimarySetupPending(): bool {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT `is_setup_complete` FROM `users` WHERE `is_primary` = 1 LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row && (int)$row['is_setup_complete'] === 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get base URL helper (works from any directory)
 */
function getBaseUrl(): string {
    return defined('BASE_URL') ? BASE_URL : '/grocery_inventory/';
}

// Auto-guard: if this file is included, enforce auth (except on login/register/setup pages and CLI test runners)
$currentScript = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
$publicPages = ['login.php', 'register.php', 'setup.php'];

if (php_sapi_name() !== 'cli' && !in_array($currentScript, $publicPages)) {
    requireAuth();
}
