<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/src/Auth.php';

$auth = new Auth($pdo);
$error = '';
$success = '';

// Para sa pag-repopulate ng fields kung may error
$usernameValue = '';
$emailValue = '';
$firstNameValue = '';
$lastNameValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token'])) die("Missing CSRF token.");
    validateCsrfToken($_POST['csrf_token']);

    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $role       = $_POST['role'] ?? 'student';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');

    $result = $auth->register($username, $email, $password, $role, $first_name, $last_name);

    if (is_int($result)) {
        $success = "Registration successful! You can now <a href='login.php'>login</a>.";
    } else {
        $error = $result;
        // Panatilihin ang na-type na values maliban sa password
        $usernameValue = htmlspecialchars($username);
        $emailValue = htmlspecialchars($email);
        $firstNameValue = htmlspecialchars($first_name);
        $lastNameValue = htmlspecialchars($last_name);
    }
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Quiz Generator</title>
    <link rel="stylesheet" href="public/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Create your account</h1>
            <?php if ($error): ?>
                <div class="auth-alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="auth-alert success"><?php echo $success; ?></div>
            <?php else: ?>
            <form method="post" action="register.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" id="reg-username" placeholder="Username" required maxlength="50"
                           value="<?php echo $usernameValue; ?>" autocomplete="off">
                </div>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" id="reg-email" placeholder="Email" required maxlength="100"
                           value="<?php echo $emailValue; ?>" autocomplete="off">
                </div>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="reg-password" placeholder="Password (min 6 chars)" required minlength="6"
                           autocomplete="new-password">
                </div>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-user-tag"></i></span>
                    <select name="role" required>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                    </select>
                </div>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-id-card"></i></span>
                    <input type="text" name="first_name" id="reg-firstname" placeholder="First Name" maxlength="50"
                           value="<?php echo $firstNameValue; ?>" autocomplete="off">
                </div>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-id-card"></i></span>
                    <input type="text" name="last_name" id="reg-lastname" placeholder="Last Name" maxlength="50"
                           value="<?php echo $lastNameValue; ?>" autocomplete="off">
                </div>
                <button type="submit" class="auth-btn">Register <i class="fas fa-arrow-right"></i></button>
            </form>
            <?php endif; ?>
            <div class="auth-footer">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <!-- Linisin ang autofill -->
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            const passField = document.getElementById('reg-password');
            if (passField) passField.value = '';
            setTimeout(function() {
                if (passField) passField.value = '';
            }, 150);
        });
    </script>
</body>
</html>