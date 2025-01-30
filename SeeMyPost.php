<?php
include "./navbar.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    exit;
}

include "./db_connection.php"; // Include your database connection script here

$user_id = $_SESSION['user_id'];

// Fetch posts for the logged-in user
$sql = "SELECT posts.*, users.name AS user_name, categories.category 
FROM posts 
JOIN users ON posts.user_id = users.user_id 
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
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="min-h-screen bg-gray-100">


    <div class="flex flex-wrap justify-center mt-8">
        <?php
        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                $imagePath = './uploads/' . $row['image']; // Path to the image file

                echo '
                <div class="max-w-sm mx-4 bg-white rounded-lg shadow-md overflow-hidden mb-4">
                    <!-- Post Image -->
                    <img class="w-full h-48 object-cover" src="' . $imagePath . '" alt="Post Image">
                    <!-- Post Content -->
                    <div class="p-4">
                        <!-- Post Title -->
                        <h2 class="text-lg font-semibold text-gray-800">' . $row['title'] . '</h2>
                        <!-- Post Description -->
                        <p class="text-gray-600 mt-2">Description: ' . $row['details'] . '</p>
                        <p>Posted by ' . $row['user_name'] . '</p>
                        <p>Category: ' . $row['category'] . '</p>
                        <!-- Edit Button -->
                       <a href="EditPost.php?post_id=' . $row['post_id'] . '" 
                               class="inline-block mt-4 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg shadow-md hover:from-purple-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-gray-100">
                                Edit Post
                            </a>
                    </div>
                </div>';
            }
        } else {
            echo "<p class='text-gray-800'>You have not created any posts yet.</p>";
        }
        ?>
    </div>

</body>

</html>