<?php
include "./db_connection.php";
include "./auth.php";

// Ensure the user is logged in
if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

// Check if `category_id` is provided via POST
if (!isset($_POST['category_id']) || empty($_POST['category_id'])) {
    header("Location: view_category.php?error=Invalid request. No category ID provided.");
    exit();
}

$category_id = intval($_POST['category_id']);

// Check if there are any posts in this category
$post_check_sql = "SELECT COUNT(*) AS post_count FROM posts WHERE category_id = ?";
$stmt = $conn->prepare($post_check_sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$post_check = $result->fetch_assoc();

if ($post_check['post_count'] > 0) {
    header("Location: view_category.php?error=Cannot delete category with associated posts.");
    exit();
}

// Delete the category from the database
$sql = "DELETE FROM categories WHERE category_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $category_id);

if ($stmt->execute()) {
    header("Location: view_category.php?success=Category deleted successfully.");
    exit();
} else {
    header("Location: view_category.php?error=Failed to delete category: " . $conn->error);
    exit();
}
?>
