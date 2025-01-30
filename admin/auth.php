<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Check if the admin is logged in
if (isset($_SESSION['admin_id'])) {
    $isLoggedIn = true;
    $admin_id = $_SESSION['admin_id'];
    $username = $_SESSION['username'] ?? "User"; 
} else {
    $isLoggedIn = false;
    $admin_id = null;
    $username = null;
}


