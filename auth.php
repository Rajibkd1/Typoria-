<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $isLoggedIn = true;
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? "User"; 
} else {
    $isLoggedIn = false;
    $user_id = null;
    $username = null;
}


