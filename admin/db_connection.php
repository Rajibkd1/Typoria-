<?php
// Database configuration
$host = "localhost"; // Database server host
$username = "root";  // Database username
$password = "";      // Database password
$database = "edge";  // Database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// echo "Connected successfully";
?>
