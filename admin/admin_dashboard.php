<?php
include "./db_connection.php";
include "./auth.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100">

    <?php
    include "./navbar.php";
    ?>

    <div class="flex flex-wrap justify-center mt-8">

        <?php
        $sql = "SELECT posts.*, users.name AS user_name, categories.category 
                FROM posts 
                JOIN users ON posts.user_id = users.user_id 
                JOIN categories ON posts.category_id = categories.category_id 
                WHERE posts.status = 'approved'";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $imagePath = '../uploads/' . $row['image'];

                echo '
                <div class="max-w-sm mx-4 mb-6">
                    <div class="group bg-white rounded-lg shadow-md overflow-hidden relative">
                        <!-- Post Image -->
                        <img class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-110" src="' . $imagePath . '" alt="Post Image">
                        
                        <!-- Overlay for View Button -->
                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <a href="../post_view.php?post_id=' . $row['post_id'] . '" class="bg-blue-500 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-600">View</a>
                        </div>

                        <!-- Post Content -->
                        <div class="p-4">
                            <h2 class="text-lg font-semibold text-gray-800">' . $row['title'] . '</h2>
                        </div>
                    </div>
                </div>';
            }
        } else {
            echo "<p class='text-gray-600'>No posts found</p>";
        }
        ?>

    </div>

</body>

</html>

