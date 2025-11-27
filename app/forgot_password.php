<?php
session_start();
require __DIR__ . '/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);
        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Store in DB
        $stmt = $pdo->prepare("UPDATE users SET reset_otp=?, reset_expiry=? WHERE email=?");
        $stmt->execute([$otp, $expiry, $email]);

        // Store email temporarily
        $_SESSION['reset_email'] = $email;

        // Send OTP email
        mail($email, "Your OTP Code", "Your OTP is: $otp");

        header("Location: verify_otp.php");
        exit;
    } 
    else {
        $message = "Email not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>

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

    .fp-card {
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

    .email-input {
        width: 100%;
        padding: 12px;
        border: 1.8px solid #ddd;
        border-radius: 8px;
        font-size: 15px;
        outline: none;
        transition: .25s;
    }

    .email-input:focus {
        border-color: #2d82f7;
        box-shadow: 0 0 4px rgba(45, 130, 247, 0.3);
    }

    .send-btn {
        width: 100%;
        padding: 12px;
        margin-top: 20px;
        background: #2d82f7;
        border: none;
        border-radius: 8px;
        color: #fff;
        font-size: 16px;
        cursor: pointer;
        transition: .2s;
    }

    .send-btn:hover {
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

<div class="fp-card">
    <h3>Forgot Password</h3>
    <p>Enter your email to receive a 6-digit OTP</p>

    <form method="POST">

        <input type="email" name="email" class="email-input" placeholder="Enter email" required>

        <button class="send-btn">Send OTP</button>

        <?php if (!empty($message)): ?>
            <p class="error-msg"><?= $message ?></p>
        <?php endif; ?>

        <a href="/login.php" class="back-login">Back to Login</a>
    </form>
</div>

</body>
</html>

