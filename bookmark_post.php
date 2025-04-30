<?php
/**
 * Typoria Blog Platform
 * AJAX Bookmark Post Handler
 */

// Include required files
require_once '../includes/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to bookmark posts'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if required parameters are provided
if (!isset($_POST['post_id']) || !isset($_POST['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing parameters'
    ]);
    exit;
}

$post_id = (int)$_POST['post_id'];
$action = $_POST['action'];

// Validate post_id
if ($post_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid post ID'
    ]);
    exit;
}

// Initialize database connection
$conn = get_db_connection();

// Check if post exists
$check_sql = "SELECT post_id FROM posts WHERE post_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('i', $post_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Post not found'
    ]);
    exit;
}

// Process bookmark action
if ($action === 'add') {
    // Check if bookmark already exists
    $exists_sql = "SELECT bookmark_id FROM bookmarks WHERE user_id = ? AND post_id = ?";
    $exists_stmt = $conn->prepare($exists_sql);
    $exists_stmt->bind_param('ii', $user_id, $post_id);
    $exists_stmt->execute();
    $exists_result = $exists_stmt->get_result();
    
    if ($exists_result->num_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Post already bookmarked'
        ]);
        exit;
    }
    
    // Add bookmark
    $add_sql = "INSERT INTO bookmarks (user_id, post_id) VALUES (?, ?)";
    $add_stmt = $conn->prepare($add_sql);
    $add_stmt->bind_param('ii', $user_id, $post_id);
    
    if ($add_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Post bookmarked successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to bookmark post: ' . $conn->error
        ]);
    }
} elseif ($action === 'remove') {
    // Remove bookmark
    $remove_sql = "DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?";
    $remove_stmt = $conn->prepare($remove_sql);
    $remove_stmt->bind_param('ii', $user_id, $post_id);
    
    if ($remove_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Bookmark removed successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to remove bookmark: ' . $conn->error
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}