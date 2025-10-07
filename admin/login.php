<?php
session_start();
require __DIR__ . '/../app/db.php';

$errors = ['email' => '', 'password' => '', 'general' => ''];
$email = '';

// ✅ generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("⛔ Security token invalid.");
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Enter a valid email";
    }
    if (empty($password)) {
        $errors['password'] = "Password is required";
    }

    if (empty($errors['email']) && empty($errors['password'])) {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $errors['general'] = "Invalid email or password";
        } else {
            $_SESSION['user_id'] = $user['id'];
            header("Location: /app/dashboard.php"); // ✅ redirect after login
            exit;
        }
    }
}
?>
<?php include __DIR__ . '/login_form.php'; ?>
