<?php
session_start();
require __DIR__ . '/db.php';

$email = $_SESSION['reset_email'] ?? null;

if (!$email) { header("Location: forgot_password.php"); exit; }

$message = "";

// Convert OTP array to string
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Combine 6 input boxes into a single string
    $otp = implode("", $_POST['otp']);

    $stmt = $pdo->prepare("SELECT reset_otp, reset_expiry FROM users WHERE email=?");
    $stmt->execute([$email]);
    $data = $stmt->fetch();

    if ($data && $otp == $data['reset_otp']) {

        // Check expiry
        if (strtotime($data['reset_expiry']) >= time()) {
            header("Location: reset_password.php");
            exit;
        } else {
            $message = "OTP expired!";
        }

    } else {
        $message = "Invalid OTP!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP</title>

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

    .otp-card {
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
        color: #777;
        font-size: 14px;
        margin-bottom: 25px;
    }

    .otp-inputs {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .otp-inputs input {
        width: 48px;
        height: 55px;
        text-align: center;
        font-size: 22px;
        border: 2px solid #ddd;
        border-radius: 10px;
        outline: none;
        transition: 0.2s;
    }

    .otp-inputs input:focus {
        border-color: #2d82f7;
        box-shadow: 0 0 4px rgba(45, 130, 247, 0.4);
    }

    .verify-btn {
        width: 100%;
        padding: 12px;
        background: #2d82f7;
        color: #fff;
        border-radius: 8px;
        border: none;
        font-size: 16px;
        cursor: pointer;
        transition: 0.2s;
    }

    .verify-btn:hover {
        background: #1968d2;
    }

    .error-msg {
        margin-top: 10px;
        font-size: 14px;
        color: red;
    }

    .resend {
        margin-top: 15px;
        font-size: 14px;
    }

    .resend a {
        color: #2d82f7;
        text-decoration: none;
    }
</style>
</head>

<body>

<div class="otp-card">
    <h3>Verify OTP</h3>
    <p>We sent a 6-digit code to your email</p>

    <form method="POST">
        <div class="otp-inputs" id="otpInputs">
            <input type="text" maxlength="1" name="otp[]" required>
            <input type="text" maxlength="1" name="otp[]" required>
            <input type="text" maxlength="1" name="otp[]" required>
            <input type="text" maxlength="1" name="otp[]" required>
            <input type="text" maxlength="1" name="otp[]" required>
            <input type="text" maxlength="1" name="otp[]" required>
        </div>

        <button class="verify-btn">Verify OTP</button>

        <?php if (!empty($message)): ?>
            <p class="error-msg"><?= $message ?></p>
        <?php endif; ?>

        <p class="resend">Didn't receive the code?
            <a href="send_otp_again.php">Resend OTP</a>
        </p>
    </form>
</div>

<script>
// OTP Input Logic: Auto move, auto backspace, auto paste

const inputs = document.querySelectorAll("#otpInputs input");

// Auto move to next box
inputs.forEach((input, index) => {

    input.addEventListener("input", () => {
        if (input.value.length === 1 && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }
    });

    // Auto backspace
    input.addEventListener("keydown", (e) => {
        if (e.key === "Backspace" && !input.value && index > 0) {
            inputs[index - 1].focus();
        }
    });
});

// Auto paste full OTP
document.getElementById("otpInputs").addEventListener("paste", (e) => {
    const pasteData = e.clipboardData.getData("text");
    if (/^\d{6}$/.test(pasteData)) {
        inputs.forEach((input, i) => input.value = pasteData[i] || "");
        inputs[5].focus(); // Move to last input
    }
    e.preventDefault();
});

// Auto focus first box on load
inputs[0].focus();
</script>

</body>
</html>
