<?php
session_start();
require __DIR__ . '/db.php';

$email = $_SESSION['reset_email'] ?? null;
if (!$email) { header("Location: forgot_password.php"); exit; }

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm'];

    if ($pass1 !== $pass2) {
        $message = "Passwords do not match!";
    } else {

        $hashed = password_hash($pass1, PASSWORD_BCRYPT);

        // Update password and clear OTP
        $stmt = $pdo->prepare("UPDATE users SET password=?, reset_otp=NULL, reset_expiry=NULL WHERE email=?");
        $stmt->execute([$hashed, $email]);

        unset($_SESSION['reset_email']);

        header("Location: /login.php?reset=success");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>

<style>
    body {
        font-family: "Inter", sans-serif;
        background: #f5f7fa;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .reset-card {
        background: #fff;
        width: 380px;
        padding: 40px;
        border-radius: 14px;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        animation: fadeIn 0.4s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    h3 {
        font-size: 22px;
        color: #333;
        margin-bottom: 10px;
    }

    p {
        font-size: 14px;
        color: #777;
        margin-bottom: 25px;
    }

    .input-box {
        margin-bottom: 20px;
        text-align: left;
    }

    .input-box label {
        display: block;
        font-size: 14px;
        color: #444;
        margin-bottom: 5px;
    }

    .input-box input {
        width: 100%;
        padding: 12px;
        border: 1.8px solid #ddd;
        border-radius: 8px;
        font-size: 15px;
        outline: none;
        transition: .25s;
    }

    .input-box input:focus {
        border-color: #2d82f7;
        box-shadow: 0 0 4px rgba(45, 130, 247, 0.3);
    }

    .pass-wrapper {
        position: relative;
    }

    .pass-wrapper i {
        position: absolute;
        right: 12px;
        top: 14px;
        cursor: pointer;
        color: #777;
    }

    .reset-btn {
        width: 100%;
        padding: 12px;
        margin-top: 10px;
        background: #2d82f7;
        border: none;
        border-radius: 8px;
        color: white;
        font-size: 16px;
        cursor: pointer;
        transition: .2s;
    }

    .reset-btn:hover {
        background: #1968d2;
    }

    .error-msg {
        margin-top: 15px;
        color: red;
        font-size: 14px;
    }

    .back-login {
        margin-top: 15px;
        display: block;
        font-size: 14px;
        color: #2d82f7;
        text-decoration: none;
    }

</style>
</head>

<body>

<div class="reset-card">

    <h3>Reset Password</h3>
    <p>Create a new password for your account</p>

    <form method="POST">

        <div class="input-box">
            <label>New Password</label>
            <div class="pass-wrapper">
                <input type="password" id="pass1" name="password" required placeholder="Enter new password">
                <i class="fa fa-eye" onclick="toggle('pass1')"></i>
            </div>
        </div>

        <div class="input-box">
            <label>Confirm Password</label>
            <div class="pass-wrapper">
                <input type="password" id="pass2" name="confirm" required placeholder="Confirm new password">
                <i class="fa fa-eye" onclick="toggle('pass2')"></i>
            </div>
        </div>

        <button class="reset-btn">Reset Password</button>

        <?php if (!empty($message)): ?>
            <p class="error-msg"><?= $message ?></p>
        <?php endif; ?>

        <a href="/login.php" class="back-login">Back to Login</a>
    </form>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>

<script>
function toggle(id) {
    let input = document.getElementById(id);
    input.type = (input.type === "password") ? "text" : "password";
}
</script>

</body>
</html>
