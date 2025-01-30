<?php
include "./db_connection.php";
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Check if post ID and status are provided
if (isset($_POST['post_id']) && isset($_POST['status'])) {
    $post_id = intval($_POST['post_id']);
    $new_status = $_POST['status'];

    // Validate the new status
    $valid_statuses = ['pending', 'approved', 'rejected'];
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['error'] = "Invalid status value.";
        header("Location: post_view.php?post_id=$post_id");
        exit();
    }

    // Update the status in the database
    $sql = "UPDATE posts SET status = ? WHERE post_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $post_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Status updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update the status. Please try again.";
    }

    // Redirect back to the post view page
    header("Location: post_view.php?post_id=$post_id");
    exit();
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: requested_post.php");
    exit();
}
?>
