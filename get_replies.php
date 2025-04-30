<?php
/**
 * Typoria Blog Platform
 * AJAX Handler for Loading Comment Replies
 */

// Include required files
require_once 'includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if comment_id is provided
if (!isset($_GET['comment_id']) || !is_numeric($_GET['comment_id'])) {
    echo json_encode(['error' => 'Invalid comment ID']);
    exit;
}

$comment_id = intval($_GET['comment_id']);

// Initialize database connection
$conn = get_db_connection();

// Fetch replies for the given comment
$replies_sql = "SELECT c.*, u.name AS user_name, u.profile_image
                FROM comments c
                JOIN users u ON c.user_id = u.user_id 
                WHERE c.parent_comment_id = ?
                ORDER BY c.created_at ASC";

$stmt = $conn->prepare($replies_sql);
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$result = $stmt->get_result();

$replies = [];

if ($result->num_rows > 0) {
    while ($reply = $result->fetch_assoc()) {
        // Format date for display
        $reply['formatted_date'] = format_date($reply['created_at']);
        
        // Sanitize data for JSON output
        $reply['user_name'] = htmlspecialchars($reply['user_name']);
        $reply['comment'] = htmlspecialchars($reply['comment']);
        
        $replies[] = $reply;
    }
}

// Return the replies as JSON
echo json_encode($replies);