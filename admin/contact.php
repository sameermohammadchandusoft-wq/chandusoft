<?php
$success = "";
$name = "";
$email = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $conn = new mysqli("127.0.0.1", "root", "", "chandusoft");

    if ($conn->connect_error) {
        die("DB Connection failed: " . $conn->connect_error);
    }

    // Get form data
    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $message = $conn->real_escape_string($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $sql = "INSERT INTO leads (name, email, message) VALUES ('$name', '$email', '$message')";
        if ($conn->query($sql) === TRUE) {
            $success = "✅ Message sent successfully!";
            // clear form values after success
            $name = $email = $message = "";
        } else {
            $success = "❌ Error saving message.";
        }
    } else {
        $success = "❌ Please fill all fields correctly.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Chandusoft</title>
  <link rel="stylesheet" href="Style.css" />
</head>
<body>
  <div id="header"></div>
 <script src="include.js"></script>

<main>
  <section id="Contact">
    <h2>Contact Us</h2>

<?php if (!empty($success)): ?>
  <div class="success-banner" id="successBanner"><?= $success ?></div>
<?php endif; ?>

<form id="contactForm" class="contact-form" action="contact.php" method="post">
  <label for="name">Your Name <span style="color:red">*</span></label>
  <input type="text" id="name" name="name" 
         value="<?= htmlspecialchars($name ?? '') ?>" 
         pattern="[A-Za-z\s]+" required>
  <small id="nameError" class="error"></small>

  <label for="email">Your Email <span style="color:red">*</span></label>
  <input type="email" id="email" name="email" 
         value="<?= htmlspecialchars($email ?? '') ?>" required>
  <small id="emailError" class="error"></small>

  <label for="message">Your Message <span style="color:red">*</span></label>
  <textarea id="message" name="message" rows="5" required><?= htmlspecialchars($message ?? '') ?></textarea>
  <small id="messageError" class="error"></small>

  <button type="submit" id="sendBtn">Send Message</button>
</form>

  </section>
</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const nameInput = document.getElementById("name");
  const emailInput = document.getElementById("email");
  const messageInput = document.getElementById("message");
  const sendBtn = document.getElementById("sendBtn");

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
    const nameValid = validateName();
    const emailValid = validateEmail();
    const messageValid = validateMessage();

    sendBtn.disabled = !(nameValid && emailValid && messageValid);
  }

  [nameInput, emailInput, messageInput].forEach(field => {
    field.addEventListener("input", validateForm);
  });

  // Auto-hide success banner after 5 seconds
  const successBanner = document.getElementById("successBanner");
  if (successBanner) {
    setTimeout(() => {
      successBanner.style.transition = "opacity 0.1s ease";
      successBanner.style.opacity = "0";
      setTimeout(() => successBanner.style.display = "none", 500);
    }, 5000);
  }

  // Initial state
  
  sendBtn.disabled = true;
});
</script>

</main>
<div id="footer"></div>
      <?php include("footer.php"); ?>
</body>
<button id="backToTop" title="Go to top">↑</button>

</html>
    