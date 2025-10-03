// include.js
document.addEventListener("DOMContentLoaded", () => {
  // Load header
  fetch("header.php")
    .then(response => response.text())
    .then(data => {
      document.getElementById("header").innerHTML = data;
    });

  // Load footer
  fetch("footer.php")
    .then(response => response.text())
    .then(data => {
      document.getElementById("footer").innerHTML = data;
    });
});



// Select the <h1> inside .hero and change its text
document.addEventListener("DOMContentLoaded", () => {
  const heroHeading = document.querySelector(".hero h1");
  heroHeading.textContent = "Empowering Your Business with Chandusoft";
});




// Rotating hero heading text
document.addEventListener("DOMContentLoaded", () => {
  const heroHeading = document.querySelector(".hero h1");

  // List of phrases to rotate
  const phrases = [
    "Welcome to Chandusoft Technologies",
    "Empowering Your Business with Chandusoft",
    "Innovation. Reliability. Growth."
  ];

  let index = 0;

  // Function to update heading
  function changeHeading() {
    heroHeading.textContent = phrases[index];
    index = (index + 1) % phrases.length; // loop back to start
  }

  // Initial text
  changeHeading();
  setInterval(changeHeading, 3000);
});



document.addEventListener("DOMContentLoaded", () => {
  const backToTop = document.getElementById("backToTop");

  // Show button on scroll
  window.addEventListener("scroll", () => {
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
      backToTop.style.display = "block";
    } else {
      backToTop.style.display = "none";
    }
  });

  // Smooth scroll to top on click
  backToTop.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
});



// contact //

/*
 document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("contactForm");
  const name = document.getElementById("name");
  const email = document.getElementById("email");
  const message = document.getElementById("message");
  const sendBtn = document.getElementById("sendBtn");

  const nameError = document.getElementById("nameError");
  const emailError = document.getElementById("emailError");
  const messageError = document.getElementById("messageError");

  function validateForm() {
    let valid = true;

    // Name
    if (name.value.trim() === "") {
      nameError.textContent = "Name is required";
      name.style.border = "2px solid red";
      valid = false;
    } else {
      nameError.textContent = "";
      name.style.border = "2px solid green";
    }

    // Email
    const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,}$/i;
    if (email.value.trim() === "") {
      emailError.textContent = "Email is required";
      email.style.border = "2px solid red";
      valid = false;
    } else if (!emailPattern.test(email.value.trim())) {
      emailError.textContent = "Enter a valid email";
      email.style.border = "2px solid red";
      valid = false;
    } else {
      emailError.textContent = "";
      email.style.border = "2px solid green";
    }

    // Message
    if (message.value.trim() === "") {
      messageError.textContent = "Message cannot be empty";
      message.style.border = "2px solid red";
      valid = false;
    } else {
      messageError.textContent = "";
      message.style.border = "2px solid green";
    }

    // Enable/disable button
    sendBtn.disabled = !valid;
  }

  // Validate on input (immediate feedback)
  name.addEventListener("input", validateForm);
  email.addEventListener("input", validateForm);
  message.addEventListener("input", validateForm);

  // Prevent default form submit (demo only)
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    alert("Message sent successfully!");
    form.reset();

    // Reset borders after reset
    name.style.border = "";
    email.style.border = "";
    message.style.border = "";

    validateForm(); // reset button state
  });

  // Initial check
  validateForm();
});
*/
