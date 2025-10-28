<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ .'/vendor/autoload.php'; // Ensure correct path to autoload
 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // --- DB CONNECTION ---
    $conn = new mysqli("127.0.0.1", "root", "", "chandusoft");
    if ($conn->connect_error) {
        echo "error";
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
if ($conn->query($sql) === TRUE) {

    // --- SEND EMAIL ---
    $mail = new PHPMailer(true);
    try {
        // SMTP config
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sameer.mohammad.chandusoft@gmail.com'; // your Gmail
        $mail->Password = 'lomb nugc ispb owij'; // Gmail App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email headers
        $mail->setFrom('sameer.mohammad.chandusoft@gmail.com', 'Chandusoft Contact Form');
        $mail->addAddress('sameer.mohammad.chandusoft@gmail.com', 'Sameer');
        $mail->addReplyTo($email, $name);

        // Email content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8'; // Ensure body handles emojis

        // Encode subject with UTF-8 + Base64 for emojis
        $subject = "🚀 New Lead Submission";
        $mail->Subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        // Encode body safely
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        $mail->Body = "
        <h3>New Lead Submission</h3>
        <p><strong>Name:</strong> {$safeName}</p>
        <p><strong>Email:</strong> {$safeEmail}</p>
        <p><strong>Message:</strong><br>{$safeMessage}</p>
        ";

        $mail->send();
        echo "success";
        exit;

    } catch (Exception $e) {
        // --- LOG FAILURE ---
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logFile = $logDir . '/app.log';
        $timestamp = date('Y-m-d H:i:s');
        $errorMessage = "[$timestamp] Mail send failed for {$email} ({$name}). Error: {$mail->ErrorInfo}\nMessage: {$message}\n\n";
        file_put_contents($logFile, $errorMessage, FILE_APPEND);

        echo "Mailer Error";
        exit;
    }
} else {
    echo "Database insert error.";
    exit;
}

}
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

    /* Message box */
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

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(15px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div id="header">
        <?php include("header.php"); ?>
  </div>
  <script src="include.js"></script>

  <main>
    <section id="Contact">
      <h2>Contact Us</h2>

      <form id="contactForm">
        <label for="name">Your Name <span style="color:red">*</span></label>
        <input type="text" id="name" name="name" pattern="[A-Za-z\s]+" required>
        <small id="nameError" class="error"></small>

        <label for="email">Your Email <span style="color:red">*</span></label>
        <input type="email" id="email" name="email" required>
        <small id="emailError" class="error"></small>

        <label for="message">Your Message <span style="color:red">*</span></label>
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

  const nameInput = document.getElementById("name");
  const emailInput = document.getElementById("email");
  const messageInput = document.getElementById("message");

  const nameError = document.getElementById("nameError");
  const emailError = document.getElementById("emailError");
  const messageError = document.getElementById("messageError");

  const nameRegex = /^[A-Za-z\s]+$/;
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function validateName() {
    if (!nameInput.value.trim()) {
      nameError.textContent = "Name is required.";
      return false;
    } else if (!nameRegex.test(nameInput.value.trim())) {
      nameError.textContent = "Only letters and spaces allowed.";
      return false;
    }
    nameError.textContent = "";
    return true;
  }

  function validateEmail() {
    if (!emailInput.value.trim()) {
      emailError.textContent = "Email is required.";
      return false;
    } else if (!emailRegex.test(emailInput.value.trim())) {
      emailError.textContent = "Enter a valid email address.";
      return false;
    }
    emailError.textContent = "";
    return true;
  }

  function validateMessage() {
    if (!messageInput.value.trim()) {
      messageError.textContent = "Message cannot be empty.";
      return false;
    }
    messageError.textContent = "";
    return true;
  }

  function validateForm() {
    const valid = validateName() && validateEmail() && validateMessage();
    sendBtn.disabled = !valid; // ✅ Enable only if all valid
    return valid;
  }

  // Re-validate on each input
  [nameInput, emailInput, messageInput].forEach(input => {
    input.addEventListener("input", validateForm);
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!validateForm()) return;

    sendBtn.disabled = true;
    responseBox.style.display = "none";

    const formData = new FormData(form);

    try {
      const res = await fetch("contact.php", { method: "POST", body: formData });
      const text = await res.text();

      responseBox.style.display = "block";

      if (text.trim() === "success") {
        responseBox.className = "message-box success";
        responseBox.textContent = "✅ Message sent successfully!";

        // ✅ Reset form and keep button disabled
        form.reset();
        sendBtn.disabled = true;
      } else {
        responseBox.className = "message-box error";
        responseBox.textContent = "❌ " + text;
        sendBtn.disabled = false; // allow retry
      }

      // Hide message after 5 seconds
      setTimeout(() => responseBox.style.display = "none", 5000);

    } catch (error) {
      responseBox.className = "message-box error";
      responseBox.textContent = "❌ Network error. Please try again.";
      responseBox.style.display = "block";
      sendBtn.disabled = false;
    }
  });
});
</script>
</body>
</html>

    