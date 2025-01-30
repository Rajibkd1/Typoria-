<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

include "./db_connection.php"; // Include your database connection script

$post_id = $_GET['post_id'] ?? null; // Get the post_id from the URL

if (!$post_id) {
    echo "Invalid post ID.";
    exit();
}

// Fetch the post data
$sql = "SELECT * FROM posts WHERE post_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $post_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    echo "Post not found or you do not have permission to edit this post.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $details = $_POST['details'];
    $category_id = $_POST['category_id'];

    // Update the post in the database
    $update_sql = "UPDATE posts SET title = ?, details = ?, category_id = ? WHERE post_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssii", $title, $details, $category_id, $post_id);

    if ($update_stmt->execute()) {
        echo "<p class='text-green-600'>Post updated successfully!</p>";
    } else {
        echo "<p class='text-red-600'>Error updating post: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.3/dist/tailwind.min.css">
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Post</h1>

        <form action="" method="POST">
            <!-- Title -->
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($post['title']) ?>"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Details -->
            <div class="mb-4">
                <label for="details" class="block text-sm font-medium text-gray-700">Details</label>
                <textarea name="details" id="details" rows="4"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500"><?= htmlspecialchars($post['details']) ?></textarea>
            </div>

            <!-- Category -->
            <div class="mb-6">
                <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                <select name="category_id" id="category_id"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <?php
                    // Fetch categories from the database
                    $category_sql = "SELECT * FROM categories";
                    $category_result = $conn->query($category_sql);
                    while ($category = $category_result->fetch_assoc()) {
                        $selected = $category['category_id'] == $post['category_id'] ? 'selected' : '';
                        echo "<option value='{$category['category_id']}' $selected>{$category['category']}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit"
                    class="w-full px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    Update Post
                </button>
            </div>
        </form>
    </div>
</body>

</html>