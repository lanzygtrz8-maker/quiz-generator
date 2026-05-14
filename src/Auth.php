<?php
class Auth {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function register($username, $email, $password, $role, $firstName, $lastName) {
        $errors = [];
        if (strlen($username) < 3) $errors[] = "Username must be at least 3 characters.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
        if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
        if (!in_array($role, ['teacher','student'])) $errors[] = "Invalid role.";
        if (!empty($errors)) return implode(' ', $errors);

        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
        $stmt->execute(['username'=>$username, 'email'=>$email]);
        if ($stmt->fetch()) return "Username or email already taken.";

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $stmt = $this->pdo->prepare("CALL sp_register_user(:username, :email, :hash, :role, :fname, :lname)");
            $stmt->execute([
                'username' => $username,
                'email'    => $email,
                'hash'     => $hash,
                'role'     => $role,
                'fname'    => $firstName,
                'lname'    => $lastName
            ]);
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }

    public function login($username, $password) {
        $stmt = $this->pdo->prepare("CALL sp_get_user_by_username(:username)");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        if (!$user) return "Invalid username or password.";

        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Buong pangalan
            $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $_SESSION['full_name'] = $fullName ?: $user['username'];

            return $user;
        }
        return "Invalid username or password.";
    }

    public static function logout() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}