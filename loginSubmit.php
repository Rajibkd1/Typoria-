<?php

include "./db_connection.php";

// Retrieve form data
$email = $_POST['email'];
$password = $_POST['password'];

// Validate and sanitize input
function validate_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$email = validate_input($email);

// Check in users table
$sql_user = "SELECT * FROM users WHERE email=?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $email);  // Removed password from the bind
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    session_start();
    $row_user = $result_user->fetch_assoc();

    // Verify password
    if (password_verify($password, $row_user['password'])) {
        $_SESSION['user_id'] = $row_user['user_id'];
        $_SESSION['email'] = $row_user['email'];
        header("location: index.php");
        exit();
    } else {
        // Invalid password
        header("location: login.php");
        exit();
    }
}

// Check in admin table (same fix applies here)
$sql_admin = "SELECT * FROM admin WHERE email=?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("s", $email);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();

if ($result_admin->num_rows > 0) {
    session_start();
    $row_admin = $result_admin->fetch_assoc();

    // Verify password
    if (password_verify($password, $row_admin['password'])) {
        $_SESSION['admin_id'] = $row_admin['admin_id'];
        $_SESSION['email'] = $row_admin['email'];
        header("location: ./admin/admin_dashboard.php");
        exit();
    } else {
        // Invalid password
        header("location: login.php");
        exit();
    }
}

// If no match found in either table
header("location: login.php");
exit();

$stmt_user->close();
$stmt_admin->close();
$conn->close();
