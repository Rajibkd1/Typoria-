<?php
session_start();
include './db_connection.php'; 
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle OTP Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_otp'])) {
    $email = $_POST['email'];

    // Check if email exists in users table
    $sql = "SELECT email FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email already registered! Try logging in.');</script>";
    } else {
        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['email'] = $email;

        // Send OTP via Email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'rajibinf00@gmail.com'; // Your email
            $mail->Password = 'dbld phzr atar fmqe'; // Use App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('rajibinf00@gmail.com', 'EDGE_Blog');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Your OTP Code";
            $mail->Body = "Your OTP Code is <strong>$otp</strong>. It expires in 5 minutes.";

            $mail->send();
            echo "<script>alert('OTP Sent! Check your email.');</script>";
        } catch (Exception $e) {
            echo "<script>alert('Failed to send OTP. Try again later.');</script>";
        }
    }
}

// Handle OTP Verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    if ($_POST['otp'] == $_SESSION['otp']) {
        $_SESSION['otp_verified'] = true;
        echo "<script>alert('OTP Verified! Enter your details.');</script>";
    } else {
        echo "<script>alert('Invalid OTP! Try again.');</script>";
    }
}

// Handle Name & Password Setup
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_password'])) {
    if ($_SESSION['otp_verified']) {
        $name = $_POST['name'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Password validation
        if ($password !== $confirm_password) {
            echo "<script>alert('Passwords do not match!');</script>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $email = $_SESSION['email'];
            $join_date = date('Y-m-d H:i:s');

            // Insert new user into database
            $sql = "INSERT INTO users (name, email, password, join_date) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $join_date);
            if ($stmt->execute()) {
                session_destroy();
                echo "<script>alert('Account Created Successfully! Redirecting to home page...'); window.location.href = 'index.php';</script>";
            } else {
                echo "<script>alert('Registration Failed. Try again.');</script>";
            }
        }
    } else {
        echo "<script>alert('OTP not verified!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Register</title>
</head>
<body class="bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center h-screen">
    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-2xl transform transition-all duration-500 hover:scale-105">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Create Account</h2>

        <?php if (!isset($_SESSION['otp'])): ?>
            <!-- OTP Request Form -->
            <form method="post" class="space-y-4">
                <input type="email" name="email" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" placeholder="Enter your email">
                <button type="submit" name="request_otp" class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition duration-300">Send OTP</button>
            </form>

        <?php elseif (!isset($_SESSION['otp_verified'])): ?>
            <!-- OTP Verification Form -->
            <form method="post" class="space-y-4">
                <input type="number" name="otp" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition duration-300" placeholder="Enter OTP">
                <button type="submit" name="verify_otp" class="w-full bg-green-600 text-white p-3 rounded-lg hover:bg-green-700 transition duration-300">Verify OTP</button>
            </form>

        <?php else: ?>
            <!-- Name & Password Setup Form -->
            <form method="post" class="space-y-4">
                <input type="text" name="name" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-300" placeholder="Enter your name">
                <input type="password" name="password" id="password" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 transition duration-300" placeholder="Create Password">
                <input type="password" name="confirm_password" id="confirm_password" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 transition duration-300" placeholder="Confirm Password">
                
                <!-- Password Toggle -->
                <label class="inline-flex items-center mt-2">
                    <input type="checkbox" onclick="togglePassword()" class="form-checkbox h-5 w-5 text-purple-600">
                    <span class="ml-2 text-gray-600">Show Password</span>
                </label>

                <button type="submit" name="set_password" class="w-full bg-purple-600 text-white p-3 rounded-lg hover:bg-purple-700 transition duration-300">Register</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword() {
            let pass = document.getElementById("password");
            let confirmPass = document.getElementById("confirm_password");
            if (pass.type === "password") {
                pass.type = "text";
                confirmPass.type = "text";
            } else {
                pass.type = "password";
                confirmPass.type = "password";
            }
        }
    </script>
</body>
</html>
