<?php
// VERY IMPORTANT: no blank lines or BOM before <?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/vendor/autoload.php';

// DEBUG: write method received (remove this file after testing)
file_put_contents(__DIR__ . '/debug_contact.txt', "METHOD=" . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . "\n", FILE_APPEND);

// -------------------------------------------------------------
//  PROCESS FORM (POST REQUEST)
// -------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // clear any accidental output so response is clean
    if (ob_get_length()) {
        ob_clean();
    }

    header("Content-Type: text/plain; charset=UTF-8");

    // --- DB CONNECTION ---
    $conn = new mysqli("127.0.0.1", "root", "", "chandusoft");
    if ($conn->connect_error) {
        echo "Database connection error.";
        exit;
    }

    // --- SANITIZE INPUT ---
    $name = trim($conn->real_escape_string($_POST['name'] ?? ''));
    $email = trim($conn->real_escape_string($_POST['email'] ?? ''));
    $message = trim($conn->real_escape_string($_POST['message'] ?? ''));

    // --- VALIDATE INPUT ---
    if (empty($name) || empty($email) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid input.";
        exit;
    }

    // --- INSERT INTO DB ---
    $sql = "INSERT INTO leads (name, email, message) VALUES ('$name', '$email', '$message')";
    if (!$conn->query($sql)) {
        echo "Database insert error.";
        exit;
    }

    // --- SEND EMAIL VIA MAILPIT ---
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = '127.0.0.1';
        $mail->Port = 1025;
        $mail->SMTPAuth = false;
        $mail->SMTPAutoTLS = false;

        $mail->setFrom('noreply@chandusoft.local', 'Chandusoft Contact Form');
        $mail->addAddress('test@chandusoft.local');
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        $subject = "🚀 New Lead Submission";
        $mail->Subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

        $mail->Body = "
            <h3>New Lead Submission</h3>
            <p><strong>Name:</strong> {$safeName}</p>
            <p><strong>Email:</strong> {$safeEmail}</p>
            <p><strong>Message:</strong><br>{$safeMessage}</p>
        ";

        $mail->send();

        // ensure no stray output
        if (ob_get_length()) { ob_clean(); }
        echo "success";
        exit;

    } catch (Exception $e) {
        // log error but don't print it to response
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/app.log';
        $timestamp = date('Y-m-d H:i:s');
        $errorMessage = "[$timestamp] Mail send failed for {$email} ({$name}). Exception: {$e->getMessage()}\n";
        file_put_contents($logFile, $errorMessage, FILE_APPEND);

        if (ob_get_length()) { ob_clean(); }
        echo "Mailer Error";
        exit;
    }
}

// -------------------------------------------------------------
//  FRONTEND PAGE (GET REQUEST)
// -------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Contact Us - Chandusoft</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="Style.css" />
  <style>
    body {
      background: linear-gradient(135deg, #e8f7f3b7, #00b4d8);
      font-family: "Poppins", sans-serif;
      margin: 0;
      padding: 0;
      color: #333;
    }
    #Contact {
      max-width: 500px;
      margin: 80px auto;
      background: #fff;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      animation: fadeIn 0.6s ease;
    }
    h2 {
      text-align: center;
      color: #007bff;
      font-size: 26px;
      margin-bottom: 25px;
      font-weight: 600;
    }
    label {
      font-weight: 600;
      display: block;
      margin-top: 15px;
      color: #333;
    }
    input, textarea {
      width: 100%;
      padding: 12px;
      margin-top: 8px;
      border: 1px solid #ddd;
      border-radius: 10px;
      font-size: 15px;
      box-sizing: border-box;
      transition: all 0.3s ease;
    }
    input:focus, textarea:focus {
      border-color: #007bff;
      box-shadow: 0 0 8px rgba(0, 123, 255, 0.3);
      outline: none;
    }
    small.error {
      color: red;
      font-size: 13px;
      margin-top: 4px;
      display: block;
    }
    button {
      background: linear-gradient(90deg, #63a6eeff, #9ed5e0ff);
      color: #fff;
      border: none;
      padding: 14px;
      font-size: 16px;
      border-radius: 10px;
      cursor: pointer;
      margin-top: 20px;
      width: 100%;
      font-weight: 600;
      transition: transform 0.2s, box-shadow 0.3s ease;
    }
    button:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
    }
    button:disabled {
      background: #ccc;
      cursor: not-allowed;
    }
    .message-box {
      margin-top: 20px;
      padding: 15px;
      border-radius: 10px;
      font-weight: 500;
      display: none;
      animation: fadeIn 0.5s ease;
      text-align: center;
    }
    .message-box.success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .message-box.error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
  </style>
</head>

<body>
  <div id="header">
      <?php include("header.php"); ?>
  </div>

  <main>
    <section id="Contact">
      <h2>Contact Us</h2>

      <form id="contactForm">
        <label>Your Name *</label>
        <input type="text" id="name" name="name" pattern="[A-Za-z\s]+" required>
        <small id="nameError" class="error"></small>

        <label>Your Email *</label>
        <input type="email" id="email" name="email" required>
        <small id="emailError" class="error"></small>

        <label>Your Message *</label>
        <textarea id="message" name="message" rows="5" required></textarea>
        <small id="messageError" class="error"></small>

        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <div class="cf-turnstile" data-sitekey="YOUR_SITE_KEY"></div>

        <button type="submit" id="sendBtn" disabled>Send Message</button>
      </form>

      <div id="responseMessage" class="message-box"></div>
    </section>
  </main>

  <div id="footer">
      <?php include("footer.php"); ?>
  </div>

<script>
document.addEventListener("DOMContentLoaded", () => {

  const form = document.getElementById("contactForm");
  const sendBtn = document.getElementById("sendBtn");
  const responseBox = document.getElementById("responseMessage");

  const name = document.getElementById("name");
  const email = document.getElementById("email");
  const message = document.getElementById("message");

  const nameError = document.getElementById("nameError");
  const emailError = document.getElementById("emailError");
  const messageError = document.getElementById("messageError");

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function validate() {
    let ok = true;

    if (!name.value.trim()) { nameError.textContent = "Name required"; ok = false; }
    else nameError.textContent = "";

    if (!emailRegex.test(email.value.trim())) { emailError.textContent = "Invalid email"; ok = false; }
    else emailError.textContent = "";

    if (!message.value.trim()) { messageError.textContent = "Message required"; ok = false; }
    else messageError.textContent = "";

    sendBtn.disabled = !ok;
    return ok;
  }

  [name, email, message].forEach(i => i.addEventListener("input", validate));

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!validate()) return;

    sendBtn.disabled = true;
    responseBox.style.display = "none";

    const fd = new FormData(form);

    try {
      const res = await fetch("contact.php", { method: "POST", body: fd });
      const text = await res.text();

      responseBox.style.display = "block";

      if (text.trim() === "success") {
        responseBox.className = "message-box success";
        responseBox.textContent = "✅ Message sent!";
        form.reset();
        sendBtn.disabled = true;
      } else {
        responseBox.className = "message-box error";
        responseBox.textContent = "❌ " + text;
        sendBtn.disabled = false;
      }

    } catch (err) {
      responseBox.className = "message-box error";
      responseBox.textContent = "❌ Network error";
      responseBox.style.display = "block";
      sendBtn.disabled = false;
    }
  });

});
</script>

</body>
</html>
