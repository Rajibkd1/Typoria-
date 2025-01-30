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

$hashed_password = md5($password); 

// Check in users table
$sql_user = "SELECT * FROM users WHERE email=? AND password=?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("ss", $email, $hashed_password);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    session_start();

    $row_user = $result_user->fetch_assoc();

    $_SESSION['user_id'] = $row_user['user_id'];
    $_SESSION['email'] = $row_user['email'];
    
    header("location: index.php");
    exit();
}

// Check in admin table
$sql_admin = "SELECT * FROM admin WHERE email=? AND password=?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("ss", $email, $hashed_password);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();

if ($result_admin->num_rows > 0) {
    session_start();

    $row_admin = $result_admin->fetch_assoc();

    $_SESSION['admin_id'] = $row_admin['admin_id'];
    $_SESSION['email'] = $row_admin['email'];
    
    header("location: ./admin/admin_dashboard.php");
    exit();
}

// If no match found in either table
header("location: login.php");
exit();

$stmt_user->close();
$stmt_admin->close();
$conn->close();
?>
