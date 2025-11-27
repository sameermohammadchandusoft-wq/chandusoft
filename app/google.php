<?php
session_start();

$client_id = "474844950796-sk8b8cik8gaal9444skruje2behnri2s.apps.googleusercontent.com";
$redirect_uri = "https://localhost/app/google_callback.php";

$scope = "email profile";
$state = bin2hex(random_bytes(16));

$_SESSION['google_oauth_state'] = $state;

$url = "https://accounts.google.com/o/oauth2/v2/auth?"
    . "client_id={$client_id}"

    
    . "&redirect_uri=" . urlencode($redirect_uri)
    . "&response_type=code"
    . "&scope=" . urlencode($scope)
    . "&state={$state}"
    . "&access_type=online";

header("Location: $url");
exit;
