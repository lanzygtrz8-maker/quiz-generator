<?php
// profile.php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/includes/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();
if (!$user) die("User not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token'])) die("Missing CSRF token.");
    validateCsrfToken($_POST['csrf_token']);

    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';

    $errors = [];
    if (strlen($username) < 3) $errors[] = "Username must be at least 3 characters.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if ($new_password !== '') {
        if (strlen($new_password) < 6) $errors[] = "Password must be at least 6 characters.";
        if ($new_password !== $confirm) $errors[] = "Passwords do not match.";
    }

    if ($username !== $user['username']) {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = :u AND id != :id LIMIT 1");
        $check->execute(['u' => $username, 'id' => $userId]);
        if ($check->fetch()) $errors[] = "Username is already taken.";
    }
    if ($email !== $user['email']) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = :e AND id != :id LIMIT 1");
        $check->execute(['e' => $email, 'id' => $userId]);
        if ($check->fetch()) $errors[] = "Email is already in use.";
    }

    // Profile picture upload
    $profilePic = $user['profile_pic'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $fileTmp  = $_FILES['profile_pic']['tmp_name'];
        $fileName = $_FILES['profile_pic']['name'];
        $fileSize = $_FILES['profile_pic']['size'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExt, $allowed)) {
            $errors[] = "Only JPG, PNG, and GIF files are allowed.";
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $errors[] = "File size must be less than 2 MB.";
        } else {
            $uploadDir = __DIR__ . '/uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $newName = 'user_' . $userId . '_' . time() . '.' . $fileExt;
            $dest = $uploadDir . $newName;
            if (move_uploaded_file($fileTmp, $dest)) {
                if ($user['profile_pic'] && file_exists($uploadDir . $user['profile_pic'])) {
                    unlink($uploadDir . $user['profile_pic']);
                }
                $profilePic = $newName;
            } else {
                $errors[] = "Failed to upload profile picture.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE users SET username = :u, email = :e, first_name = :f, last_name = :l, profile_pic = :pic";
            $params = ['u' => $username, 'e' => $email, 'f' => $first_name, 'l' => $last_name, 'pic' => $profilePic, 'id' => $userId];
            if (!empty($new_password)) {
                $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
                $sql .= ", password_hash = :hash";
                $params['hash'] = $hash;
            }
            $sql .= " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $_SESSION['username'] = $username;
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();
            $success = "Profile updated successfully.";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

$csrf_token = generateCsrfToken();
$basePath = '';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="card">
        <h2>Edit Profile</h2>
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <!-- Profile picture at impormasyon sa iisang row -->
            <div style="display: flex; gap: 24px; flex-wrap: wrap; align-items: flex-start;">
                <!-- Larawan -->
                <div style="text-align: center; flex-shrink: 0;">
                    <?php
                    $picFile = $user['profile_pic'] ?? '';
                    $picPath = 'uploads/profiles/' . $picFile;
                    if (!$picFile || !file_exists(__DIR__ . '/' . $picPath)) {
                        $picSrc = 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&size=120&background=2563eb&color=fff';
                    } else {
                        $picSrc = $picPath;
                    }
                    ?>
                    <img id="profilePreview" src="<?php echo $picSrc; ?>" alt="Profile" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border: 2px solid #e5e7eb; margin-bottom:8px;">
                    <label class="btn btn-outline btn-sm" style="cursor:pointer; margin-top:4px;">
                        <i class="fas fa-camera"></i> Change
                        <input type="file" name="profile_pic" accept="image/*" style="display:none;" onchange="previewImage(event)">
                    </label>
                </div>

                <!-- Mga input fields -->
                <div style="flex:1; min-width:250px;">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required maxlength="50">

                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required maxlength="100">

                    <div style="display: flex; gap: 16px;">
                        <div style="flex:1;">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" maxlength="50">
                        </div>
                        <div style="flex:1;">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" maxlength="50">
                        </div>
                    </div>

                    <!-- Toggle Password -->
                    <div style="margin-top: 16px;">
                        <a href="#" onclick="document.getElementById('passwordFields').classList.toggle('hidden'); return false;" class="btn btn-outline btn-sm">
                            <i class="fas fa-lock"></i> Change Password
                        </a>
                    </div>
                    <div id="passwordFields" class="hidden" style="margin-top: 12px;">
                        <label>New Password</label>
                        <input type="password" name="new_password" placeholder="Leave blank to keep current" minlength="6">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="Re-type new password">
                    </div>
                </div>
            </div>

            <!-- Button group -->
            <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Inline style para sa hidden class -->
<style>
    .hidden { display: none; }
</style>

<script>
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            document.getElementById('profilePreview').src = reader.result;
        }
        reader.readAsDataURL(event.target.files[0]);
    }
</script>

</div><!-- .wrapper -->
</body>
</html>