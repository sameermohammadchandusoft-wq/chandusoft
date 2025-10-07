<?php
session_start();
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/auth.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = ['name'=>'','email'=>'','password'=>'','general'=>''];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("⛔ Security token invalid.");
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name)) $errors['name'] = "Name is required";
    if (empty($email)) $errors['email'] = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Enter a valid email";
    if (empty($password)) $errors['password'] = "Password is required";
    elseif (strlen($password) < 6) $errors['password'] = "Password must be at least 6 characters";

    if (empty($errors['name']) && empty($errors['email']) && empty($errors['password'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors['general'] = "Email is already registered";
        else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
            $stmt->execute([$name,$email,$hashed,$role]);

            $user_id = $pdo->lastInsertId();
            login($user_id, $name, $role);

            // ✅ Flash welcome message
            set_flash('welcome', "Welcome, {$name}! Your account has been created.");

            redirect('/app/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register</title>
<style>
body { font-family: Arial, sans-serif; margin:40px; background:#f9f9f9; }
.container { max-width:400px; margin:auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.error { color:red; font-size:0.9em; margin-top:4px; }
.input-group { margin-bottom:15px; }
input { width:100%; padding:10px; border:1px solid #ccc; border-radius:5px; }
input.error-border { border-color:red; }
button { width:100%; padding:10px; border:none; border-radius:5px; background:#28a745; color:#fff; font-size:16px; cursor:pointer; }
button:hover { background:#1e7e34; }
h2{text-align:center;color:#333;}
p{text-align:center;}
a{color:#1690e8;text-decoration:none;}
</style>
</head>
<body>
<div class="container">
<h2>Create Account</h2>
<?php if (!empty($errors['general'])): ?>
<p class="error"><?= htmlspecialchars($errors['general']) ?></p>
<?php endif; ?>
<form method="POST" action="register.php">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

<div class="input-group">
<label>Name</label><br>
<input type="text" name="name" value="<?= htmlspecialchars($name) ?>" class="<?= !empty($errors['name']) ? 'error-border' : '' ?>">
<?php if (!empty($errors['name'])): ?><div class="error"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
</div>

<div class="input-group">
<label>Email</label><br>
<input type="text" name="email" value="<?= htmlspecialchars($email) ?>" class="<?= !empty($errors['email']) ? 'error-border' : '' ?>">
<?php if (!empty($errors['email'])): ?><div class="error"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
</div>

<div class="input-group">
<label>Password</label><br>
<input type="password" name="password" class="<?= !empty($errors['password']) ? 'error-border' : '' ?>">
<?php if (!empty($errors['password'])): ?><div class="error"><?= htmlspecialchars($errors['password']) ?></div><?php endif; ?>
</div>

<button type="submit">Register</button>
</form>
<p>Already have an account? <a href="login.php">Login here</a></p>
</div>
</body>
</html>



