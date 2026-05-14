<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/src/Auth.php';

$auth = new Auth($pdo);
$error = '';

$usernameValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token'])) die("Missing CSRF token.");
    validateCsrfToken($_POST['csrf_token']);

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = $auth->login($username, $password);

    if (is_array($result)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = $result;
        $usernameValue = htmlspecialchars($username);
    }
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Quiz Generator</title>
    <link rel="stylesheet" href="public/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Quiz Generator</h1>
            <?php if ($error): ?>
                <div class="auth-alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" action="login.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" id="login-username" placeholder="Username" required maxlength="50"
                           value="<?php echo $usernameValue; ?>" autocomplete="off">
                </div>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="login-password" placeholder="Password" required
                           autocomplete="new-password">
                </div>
                <!-- Forgot Password – nakagitna na -->
                <div style="text-align: center; margin-bottom: 24px;">
                    <a href="forgot_password.php" style="color: #5b6af0; font-size: 13px; text-decoration: none; font-weight: 500;">Forgot password?</a>
                </div>
                <button type="submit" class="auth-btn">Login <i class="fas fa-arrow-right"></i></button>
            </form>
            <div class="auth-footer">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', function() {
            const passField = document.getElementById('login-password');
            if (passField) passField.value = '';
            setTimeout(function() {
                if (passField) passField.value = '';
            }, 150);
        });
    </script>
</body>
</html>