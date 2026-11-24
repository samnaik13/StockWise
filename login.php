<?php
/**
 * STOCKWISE: Login Page
 * Database-based authentication with secure password verification.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Check if primary account setup is needed
if (isPrimarySetupPending()) {
    header('Location: ' . BASE_URL . 'setup.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $error = 'Please enter your email address.';
    } elseif ($password === '') {
        $error = 'Please enter your password.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT `user_id`, `full_name`, `email`, `password_hash`, `is_setup_complete` FROM `users` WHERE `email` = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Invalid email address or password.';
            } elseif ((int)$user['is_setup_complete'] === 0) {
                $error = 'This account has not been set up yet. Please complete the initial setup.';
            } elseif (!password_verify($password, $user['password_hash'])) {
                $error = 'Invalid email address or password.';
            } else {
                // Successful login — create session
                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];

                header('Location: ' . BASE_URL . 'index.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | StockWise</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg-light); }
        .login-container { width: 100%; max-width: 420px; padding: 0 1rem; }
        .login-brand { text-align: center; margin-bottom: 2rem; }
        .login-brand .brand-icon { font-size: 3rem; display: block; margin-bottom: 0.5rem; }
        .login-brand h1 { color: var(--primary); font-size: 1.6rem; margin: 0; }
        .login-brand p { color: var(--text-muted); font-size: 0.9rem; margin: 0.25rem 0 0; }
        .password-wrapper { position: relative; }
        .password-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 1.1rem; padding: 4px; }
        .login-footer { text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted); }
        .login-footer a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .login-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-brand">
            <span class="brand-icon">🛒</span>
            <h1>StockWise</h1>
            <p>Inventory Management System</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Sign In to Your Account</h2>
            </div>
            <div class="card-body">
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autofocus>
                    </div>

                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="login-password" class="form-control" required placeholder="Enter your password">
                            <button type="button" class="password-toggle" onclick="togglePassword()" title="Toggle password visibility">👁</button>
                        </div>
                    </div>

                    <div class="form-actions" style="border-top: none; padding-top: 0.5rem;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.7rem;">Sign In</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="login-footer">
            <p>Don't have an account? <a href="<?= BASE_URL ?>register.php">Create Account</a></p>
        </div>
    </div>

    <script>
    function togglePassword() {
        const input = document.getElementById('login-password');
        const btn = input.nextElementSibling;
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
