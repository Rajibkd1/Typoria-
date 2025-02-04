<?php

include "./db_connection.php";

// Retrieve form data
$email = $_POST['email'];
$password = $_POST['password'];

// Validate and sanitize input
function validate_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$email = validate_input($email);

// Check in users table
$sql_user = "SELECT * FROM users WHERE email=?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $email);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    $row_user = $result_user->fetch_assoc();
    
    // Verify hashed password
    if (password_verify($password, $row_user['password'])) {
        session_start();
        $_SESSION['user_id'] = $row_user['user_id'];
        $_SESSION['email'] = $row_user['email'];
        
        // Close statements before redirection
        $stmt_user->close();
        $conn->close();
        
        header("location: index.php");
        exit();
    }
}
$stmt_user->close(); // Close statement after use

// Check in admin table
$sql_admin = "SELECT * FROM admin WHERE email=?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("s", $email);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();

if ($result_admin->num_rows > 0) {
    $row_admin = $result_admin->fetch_assoc();
    
    // Verify hashed password
    if (password_verify($password, $row_admin['password'])) {
        session_start();
        $_SESSION['admin_id'] = $row_admin['admin_id'];
        $_SESSION['email'] = $row_admin['email'];
        
        // Close statements before redirection
        $stmt_admin->close();
        $conn->close();
        
        header("location: ./admin/admin_dashboard.php");
        exit();
    }
}
$stmt_admin->close(); // Close statement after use

// If no match found in either table
$conn->close();
header("location: login.php");
exit();
?>
