<?php
include "./db_connection.php";
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get the post ID from the query parameter
if (isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']);
} else {
    header("Location: requested_post.php");
    exit();
}

// Fetch post details
$sql = "SELECT posts.*, users.name AS user_name, categories.category 
        FROM posts 
        JOIN users ON posts.user_id = users.user_id 
        JOIN categories ON posts.category_id = categories.category_id 
        WHERE posts.post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

// If post not found
if ($result->num_rows == 0) {
    header("Location: requested_post.php");
    exit();
}

$post = $result->fetch_assoc();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    $update_sql = "UPDATE posts SET status = ? WHERE post_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_status, $post_id);

    if ($update_stmt->execute()) {
        // Reload the page with updated data
        header("Location: post_view.php?post_id=$post_id");
        exit();
    } else {
        $error = "Failed to update the status. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Details</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-r from-purple-400 via-pink-500 to-red-500 flex items-center justify-center">

    <div class="max-w-lg w-full bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="relative">
            <!-- Post Image -->
            <img src="../uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image"
                class="w-full h-64 object-cover">
            <!-- Status Badge -->
            <div
                class="absolute top-4 left-4 bg-yellow-500 text-white text-sm px-3 py-1 rounded-full capitalize shadow-lg">
                <?php echo htmlspecialchars($post['status']); ?>
            </div>
        </div>
        <div class="p-6">
            <!-- Title -->
            <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($post['title']); ?></h2>
            <!-- Description -->
            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($post['details']); ?></p>
            <!-- Category -->
            <p class="text-gray-800 font-medium mb-2">Category: <span
                    class="text-gray-600"><?php echo htmlspecialchars($post['category']); ?></span></p>
            <!-- Posted By -->
            <p class="text-gray-800 font-medium mb-6">Posted By: <span
                    class="text-gray-600"><?php echo htmlspecialchars($post['user_name']); ?></span></p>
            <!-- Update Form -->
            <?php if (isset($error)) { ?>
            <div class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>
            <form method="POST" class="space-y-4">
                <label for="status" class="block text-gray-800 font-semibold">Update Status</label>
                <select name="status" id="status"
                    class="block w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="pending" <?php echo $post['status'] == 'pending' ? 'selected' : ''; ?>>Pending
                    </option>
                    <option value="approved" <?php echo $post['status'] == 'approved' ? 'selected' : ''; ?>>Approved
                    </option>
                    <option value="rejected" <?php echo $post['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected
                    </option>
                </select>
                <button type="submit"
                    class="w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition">
                    Update
                </button>
            </form>
        </div>
        <div class="p-4 text-center bg-gray-100">
            <a href="requested_post.php"
                class="text-blue-500 hover:underline text-sm">Back to Requested Posts</a>
        </div>
    </div>

</body>

</html>
