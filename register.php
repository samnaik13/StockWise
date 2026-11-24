<?php
/**
 * STOCKWISE: Registration Page
 * Create new user accounts with proper validation and secure password hashing.
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
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if ($full_name === '') {
        $errors[] = 'Full name is required.';
    }

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
            $pdo = getDBConnection();

            // Check for duplicate email
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `email` = ?");
            $stmtCheck->execute([$email]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $errors[] = 'An account with this email address already exists.';
            } else {
                // Create new account
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO `users` (`full_name`, `email`, `mobile`, `password_hash`, `is_primary`, `is_setup_complete`) VALUES (?, ?, ?, ?, 0, 1)");
                $stmt->execute([$full_name, $email, $mobile, $passwordHash]);

                $_SESSION['flash_success'] = "Account created successfully! Please sign in.";
                header('Location: ' . BASE_URL . 'login.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred during registration. Please try again.';
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
    <title>Register | StockWise</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg-light); }
        .register-container { width: 100%; max-width: 480px; padding: 1rem; }
        .register-brand { text-align: center; margin-bottom: 1.5rem; }
        .register-brand .brand-icon { font-size: 3rem; display: block; margin-bottom: 0.5rem; }
        .register-brand h1 { color: var(--primary); font-size: 1.6rem; margin: 0; }
        .register-brand p { color: var(--text-muted); font-size: 0.9rem; margin: 0.25rem 0 0; }
        .password-wrapper { position: relative; }
        .password-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 1.1rem; padding: 4px; }
        .register-footer { text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted); }
        .register-footer a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .register-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-brand">
            <span class="brand-icon">🛒</span>
            <h1>StockWise</h1>
            <p>Create Your Account</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Registration</h2>
            </div>
            <div class="card-body">
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" class="form-control" required placeholder="Enter your full name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Mobile Number <span class="required">*</span></label>
                        <input type="text" name="mobile" class="form-control" required placeholder="10-digit mobile number" value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="reg-password" class="form-control" required placeholder="Minimum 6 characters" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePassword('reg-password', this)" title="Toggle password visibility">👁</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="reg-confirm" class="form-control" required placeholder="Re-enter your password" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePassword('reg-confirm', this)" title="Toggle password visibility">👁</button>
                        </div>
                    </div>

                    <div class="form-actions" style="border-top: none; padding-top: 0.5rem;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.7rem;">Create Account</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="register-footer">
            <p>Already have an account? <a href="<?= BASE_URL ?>login.php">Sign In</a></p>
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
