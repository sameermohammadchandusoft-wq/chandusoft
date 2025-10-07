<?php
// app/hash.php

function make_hash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_hash($password, $hash) {
    return password_verify($password, $hash);
}
