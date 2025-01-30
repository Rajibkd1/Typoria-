<?php
include "./navbar.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    exit;
}

include "./db_connection.php"; // Include your database connection script here

$user_id = $_SESSION['user_id'];

// Fetch posts for the logged-in user
$sql = "SELECT posts.*, categories.category 
FROM posts 
JOIN categories ON posts.category_id = categories.category_id
WHERE posts.user_id = $user_id";

$result = $conn->query($sql);

if (!$result) {
    // Query failed
    echo "Error: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Posts</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.3/dist/tailwind.min.css">
    <style>
        .post-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>

<body class="min-h-screen bg-gray-100">

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-center mb-8 text-gray-800">My Posts</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php
    if ($result->num_rows > 0) {
        // Output data of each row
        while ($row = $result->fetch_assoc()) {
            $imagePath = './uploads/' . $row['image']; // Path to the image file

            echo '
            <div class="post-card bg-white rounded-lg shadow-lg overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                <!-- Post Image -->
                <img class="w-full h-48 object-cover" src="' . $imagePath . '" alt="Post Image">
                <!-- Post Content -->
                <div class="p-6">
                    <!-- Post Title -->
                    <h2 class="text-xl font-bold text-gray-800 mb-4 hover:text-purple-600 transition-colors duration-300">' . $row['title'] . '</h2>
                    <!-- Category -->
                    <p class="text-sm text-gray-600 mb-4">
                        <span class="font-semibold">Category:</span> 
                        <span class="bg-purple-100 text-purple-600 px-2 py-1 rounded-full text-xs">' . $row['category'] . '</span>
                    </p>
                    <!-- Edit Button -->
                    <a href="EditPost.php?post_id=' . $row['post_id'] . '" 
                       class="inline-block w-full text-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-300">
                        Edit Post
                    </a>
                </div>
            </div>';
        }
    } else {
        echo '<p class="text-gray-800 text-center w-full text-lg">You have not created any posts yet.</p>';
    }
    ?>
</div>
    </div>

</body>

</html>