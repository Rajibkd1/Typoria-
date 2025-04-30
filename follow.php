<?php
/**
 * Typoria Blog Platform
 * Follow/Unfollow Functionality
 */

// Include required files
require_once 'includes/functions.php';
require_once 'includes/theme.php';

// Check if user is logged in
$auth = require_login();
$current_user_id = $auth['user_id'];

// Initialize database connection
$conn = get_db_connection();

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Not a POST request, redirect back with error
    typoria_flash_message("Invalid request method", "error");
    header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php'));
    exit();
}

// Get the user ID to follow/unfollow
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : 'follow'; // Default action is follow

// Validate user ID
if ($user_id <= 0) {
    typoria_flash_message("Invalid user", "error");
    header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php'));
    exit();
}

// Check if user is trying to follow/unfollow themselves
if ($user_id == $current_user_id) {
    typoria_flash_message("You cannot follow yourself", "error");
    header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php'));
    exit();
}

// Check if user exists
$user_check_sql = "SELECT user_id, name FROM users WHERE user_id = ?";
$user_check_stmt = $conn->prepare($user_check_sql);
$user_check_stmt->bind_param("i", $user_id);
$user_check_stmt->execute();
$user_check_result = $user_check_stmt->get_result();

if ($user_check_result->num_rows === 0) {
    typoria_flash_message("User not found", "error");
    header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php'));
    exit();
}

$user_data = $user_check_result->fetch_assoc();
$username = $user_data['name'];

// Process follow/unfollow action
try {
    $conn->begin_transaction();
    
    if ($action === 'follow') {
        // Check if already following
        $check_sql = "SELECT follower_id FROM followers 
                     WHERE follower_user_id = ? AND followed_user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $current_user_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Already following, no need to do anything
            typoria_flash_message("You are already following {$username}", "info");
        } else {
            // Add follow relationship
            $follow_sql = "INSERT INTO followers (follower_user_id, followed_user_id) VALUES (?, ?)";
            $follow_stmt = $conn->prepare($follow_sql);
            $follow_stmt->bind_param("ii", $current_user_id, $user_id);
            $follow_stmt->execute();
            
            // Create notification for the user being followed
            $notification_message = "started following you";
            create_notification($user_id, 'follow', $current_user_id, $current_user_id, $notification_message);
            
            typoria_flash_message("You are now following {$username}", "success");
        }
    } else if ($action === 'unfollow') {
        // Remove follow relationship
        $unfollow_sql = "DELETE FROM followers 
                         WHERE follower_user_id = ? AND followed_user_id = ?";
        $unfollow_stmt = $conn->prepare($unfollow_sql);
        $unfollow_stmt->bind_param("ii", $current_user_id, $user_id);
        $unfollow_stmt->execute();
        
        if ($unfollow_stmt->affected_rows > 0) {
            typoria_flash_message("You have unfollowed {$username}", "success");
        } else {
            typoria_flash_message("You are not following {$username}", "info");
        }
    } else {
        // Invalid action
        typoria_flash_message("Invalid action", "error");
    }
    
    $conn->commit();
} catch (Exception $e) {
    // Something went wrong, rollback
    $conn->rollback();
    typoria_flash_message("An error occurred: " . $e->getMessage(), "error");
}

// Redirect back to the referring page or user profile
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: author.php?id=" . $user_id);
}
exit();
?>