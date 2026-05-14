<?php
// forgot_password.php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/includes/csrf.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token'])) die("Missing CSRF token.");
    validateCsrfToken($_POST['csrf_token']);

    $email       = trim($_POST['email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';

    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (strlen($newPassword) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($newPassword !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $updateStmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $updateStmt->execute(['hash' => $hash, 'id' => $user['id']]);

            $success = "Your password has been reset successfully. You can now <a href='login.php'>login</a> with your new password.";
        } else {
            $error = "No account found with that email address.";
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Quiz Generator</title>
    <link rel="stylesheet" href="public/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Karagdagan para sa magandang back button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 24px;
            background: #ffffff;
            color: #374151;
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            margin-top: 10px;
        }
        .back-btn:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .auth-footer {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Reset Password</h1>
            <?php if ($error): ?>
                <div class="auth-alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="auth-alert success"><?php echo $success; ?></div>
            <?php else: ?>
            <form method="post" action="forgot_password.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" placeholder="Email address" required maxlength="100"
                           value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="new_password" id="new_password" placeholder="New password (min 6 chars)" required minlength="6" autocomplete="new-password">
                </div>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required minlength="6" autocomplete="new-password">
                </div>
                <button type="submit" class="auth-btn">Reset Password <i class="fas fa-key"></i></button>
            </form>
            <?php endif; ?>
            <div class="auth-footer">
                <a href="login.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>