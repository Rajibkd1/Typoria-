<?php
include "./db_connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);

    // Verify OTP
    $sql = "SELECT * FROM otp_verification WHERE email = ? AND otp = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Move user data to the main `users` table
        $user = $result->fetch_assoc();
        $insert_sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sss", $user['name'], $user['email'], $user['password']);

        if ($stmt->execute()) {
            // Delete the entry from `otp_verification`
            $delete_sql = "DELETE FROM otp_verification WHERE email = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();

            echo "Email verified successfully. You can now log in.";
        } else {
            echo "Failed to verify email. Please try again.";
        }
    } else {
        echo "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-r from-green-400 to-blue-500">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-center text-gray-800">Verify OTP</h2>
            <form action="" method="POST" class="mt-6">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">
                <div class="mb-4">
                    <label for="otp" class="block text-gray-700">Enter OTP:</label>
                    <input id="otp" name="otp" type="text" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600">Verify</button>
            </form>
        </div>
    </div>
</body>

</html>
