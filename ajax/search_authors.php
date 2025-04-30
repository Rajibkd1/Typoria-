<?php
/**
 * Typoria Blog Platform
 * AJAX Author Search
 */

// Include required files
require_once '../includes/functions.php';

// Get search query
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Initialize response
$response = [];

if (!empty($search_query)) {
    // Connect to database
    $conn = get_db_connection();
    
    // Search for authors
    $sql = "SELECT user_id, name FROM users 
            WHERE name LIKE ? OR email LIKE ?
            ORDER BY name ASC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $search_param = '%' . $search_query . '%';
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Add authors to response
    while ($row = $result->fetch_assoc()) {
        $response[] = [
            'user_id' => $row['user_id'],
            'name' => $row['name']
        ];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);