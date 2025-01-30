<?php
include "./db_connection.php";
require "./mailer.php"; // Include the mailer script

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password securely
    $otp = rand(100000, 999999); // Generate a 6-digit OTP

    // Check if the email is already registered
    $check_email_sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email_sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "This email is already registered.";
        exit();
    }

    // Insert user data into the `otp_verification` table
    $insert_sql = "INSERT INTO otp_verification (name, email, password, otp) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("sssi", $name, $email, $password, $otp);

    if ($stmt->execute()) {
        // Send OTP via email
        $subject = "Verify Your Email";
        $message = "Hello $name,\n\nYour OTP for email verification is: $otp\n\nThank you for registering with us.";
        if (sendEmail($email, $subject, $message)) {
            header("Location: verify_otp.php?email=$email");
            exit();
        } else {
            echo "Failed to send OTP. Please try again.";
        }
    } else {
        echo "Registration failed. Please try again.";
    }
}
?>
