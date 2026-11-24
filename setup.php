<?php
/**
 * STOCKWISE: Primary Account Initial Setup
 * One-time setup for the primary account (Samarth Naik).
 * Collects email, mobile, and password. Cannot be repeated once completed.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

// Check if setup is already complete - block repeated access
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT `user_id`, `full_name`, `is_setup_complete` FROM `users` WHERE `is_primary` = 1 LIMIT 1");
$stmt->execute();
$primaryUser = $stmt->fetch();

if (!$primaryUser || (int)$primaryUser['is_setup_complete'] === 1) {
    // Setup already done or no primary user - redirect to login
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($mobile === '') {
        $errors[] = 'Mobile number is required.';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $mobile)) {
        $errors[] = 'Please enter a valid mobile number (10-15 digits).';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($confirm_password !== $password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            // Check if email is already taken by another user
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `email` = ? AND `user_id` != ?");
            $stmtCheck->execute([$email, $primaryUser['user_id']]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $errors[] = 'This email address is already in use.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmtUpdate = $pdo->prepare("UPDATE `users` SET `email` = ?, `mobile` = ?, `password_hash` = ?, `is_setup_complete` = 1 WHERE `user_id` = ?");
                $stmtUpdate->execute([$email, $mobile, $passwordHash, $primaryUser['user_id']]);

                $_SESSION['flash_success'] = "Primary account setup complete! You can now sign in as " . htmlspecialchars($primaryUser['full_name']) . ".";
                header('Location: ' . BASE_URL . 'login.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred. Please try again.';
        }
    }

    if (!empty($errors)) {
        $error = implode('<br>', array_map('htmlspecialchars', $errors));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Setup | StockWise</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg-light); }
        .setup-container { width: 100%; max-width: 500px; padding: 1rem; }
        .setup-brand { text-align: center; margin-bottom: 1.5rem; }
        .setup-brand .brand-icon { font-size: 3rem; display: block; margin-bottom: 0.5rem; }
        .setup-brand h1 { color: var(--primary); font-size: 1.6rem; margin: 0; }
        .setup-brand p { color: var(--text-muted); font-size: 0.9rem; margin: 0.25rem 0 0; }
        .password-wrapper { position: relative; }
        .password-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 1.1rem; padding: 4px; }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-brand">
            <span class="brand-icon">🛒</span>
            <h1>StockWise</h1>
            <p>Initial Account Setup</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Welcome, <?= htmlspecialchars($primaryUser['full_name']) ?>!</h2>
            </div>
            <div class="card-body">
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($primaryUser['full_name']) ?>" disabled style="background: #f1f3f5; font-weight: 600;">
                    </div>

                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="your.email@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autofocus>
                    </div>

                    <div class="form-group">
                        <label>Mobile Number <span class="required">*</span></label>
                        <input type="text" name="mobile" class="form-control" required placeholder="10-digit mobile number" value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Create Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="setup-password" class="form-control" required placeholder="Minimum 6 characters" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePassword('setup-password', this)">👁</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="setup-confirm" class="form-control" required placeholder="Re-enter your password" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePassword('setup-confirm', this)">👁</button>
                        </div>
                    </div>

                    <div class="form-actions" style="border-top: none; padding-top: 0.5rem;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.7rem;">Activate Primary Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = '🙈';
        } else {
            input.type = 'password';
            btn.textContent = '👁';
        }
    }
    </script>
</body>
</html>
