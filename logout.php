<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Start a new session to store the logout message
session_start();
$_SESSION['logout_message'] = "You have been logged out.";

// Redirect to login page
header("Location: /login.php");
exit;
?>
