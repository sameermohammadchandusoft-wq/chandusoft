<?php
session_start();

$client_id = "86dfh3jk3kf982";
$redirect_uri = "https://yourdomain.com/app/linkedin_callback.php";

$scope = "r_liteprofile r_emailaddress";
$state = bin2hex(random_bytes(16));
$_SESSION['linkedin_oauth_state'] = $state;

$url = "https://www.linkedin.com/oauth/v2/authorization?response_type=code"
    . "&client_id={$client_id}"
    . "&redirect_uri=" . urlencode($redirect_uri)
    . "&state={$state}"
    . "&scope=" . urlencode($scope);

header("Location: $url");
exit;
