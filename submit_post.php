<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if user is not logged in
    header("Location: login.php");
    exit();
}

// Include database connection
include "./db_connection.php";

// Initialize variables to store form data
$title = $_POST['title'];
$details = $_POST['details'];
$category_id = $_POST['category_id'];
$user_id = $_SESSION['user_id'];

// File upload handling for image
$uploadDir = './uploads/';
$uploadedFile = $_FILES['image']['tmp_name'];
$fileName = $_FILES['image']['name'];
$targetFilePath = $uploadDir . $fileName;
$fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

// Check if file is selected
if (!empty($uploadedFile)) {
    // Allow certain file formats
    $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
    if (in_array($fileType, $allowTypes)) {
        // Upload file to server
        if (move_uploaded_file($uploadedFile, $targetFilePath)) {
            // Insert post data into database
            $sql = "INSERT INTO posts (title, details, image, date_time, category_id, user_id) 
                    VALUES ('$title', '$details', '$fileName', NOW(), '$category_id', '$user_id')";
            if ($conn->query($sql) === TRUE) {
                // Redirect to index.php after successful post creation
                header("Location: index.php");
                exit();
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        } else {
            echo "Error uploading file.";
        }
    } else {
        echo 'Invalid file format. Allowed formats: jpg, jpeg, png, gif.';
    }
} else {
    echo 'Please select an image file.';
}

// Close database connection
$conn->close();
?>
