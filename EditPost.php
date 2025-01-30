<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit();
}

include "./db_connection.php"; 
include "./navbar.php"; 
$post_id = $_GET['post_id'] ?? null;

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
        $success_message = "Post updated successfully!";
    } else {
        $error_message = "Error updating post: " . $conn->error;
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-input {
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
    </style>
</head>

<body class="min-h-screen bg-gray-100 ">
    <div class="flex items-center justify-center"> 
        <div class="bg-white p-8 rounded-lg shadow-2xl w-full max-w-md transform transition-all duration-300 hover:shadow-3xl">
            <!-- Header -->
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold gradient-text">Edit Post</h1>
                <p class="text-sm text-gray-500">Update your post details below.</p>
            </div>
    
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)) : ?>
                <div class="mb-4 p-3 text-sm text-green-700 bg-green-100 rounded-lg">
                    <?= $success_message ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error_message)) : ?>
                <div class="mb-4 p-3 text-sm text-red-700 bg-red-100 rounded-lg">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
    
            <!-- Edit Form -->
            <form action="" method="POST">
                <!-- Title -->
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" id="title" value="<?= htmlspecialchars($post['title']) ?>"
                        class="form-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
    
                <!-- Details -->
                <div class="mb-4">
                    <label for="details" class="block text-sm font-medium text-gray-700 mb-1">Details</label>
                    <textarea name="details" id="details" rows="4"
                        class="form-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"><?= htmlspecialchars($post['details']) ?></textarea>
                </div>
    
                <!-- Category -->
                <div class="mb-6">
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" id="category_id"
                        class="form-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
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
                        class="w-full gradient-bg px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90 transition-all duration-300">
                        Update Post <i class="fas fa-edit ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>