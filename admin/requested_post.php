<?php
include "./db_connection.php";
include "./navbar.php";

// session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch posts from the database
$sql = "SELECT posts.*, categories.category 
        FROM posts 
        JOIN categories ON posts.category_id = categories.category_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requested Posts</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100">
    <div class="container mx-auto mt-8">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Requested Posts</h1>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-md">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">#</th>
                        <th class="py-3 px-6 text-left">Image</th>
                        <th class="py-3 px-6 text-left">Post Name</th>
                        <th class="py-3 px-6 text-left">Category</th>
                        <th class="py-3 px-6 text-left">Status</th>
                        <th class="py-3 px-6 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    <?php
                    if ($result->num_rows > 0) {
                        $serial = 1;
                        while ($row = $result->fetch_assoc()) {
                            $imagePath = '../uploads/' . $row['image'];
                            echo '
                            <tr class="border-b border-gray-200 hover:bg-gray-100">
                                <td class="py-3 px-6">' . $serial++ . '</td>
                                <td class="py-3 px-6">
                                    <img src="' . $imagePath . '" alt="Post Image" class="w-16 h-16 object-cover rounded">
                                </td>
                                <td class="py-3 px-6">' . htmlspecialchars($row['title']) . '</td>
                                <td class="py-3 px-6">' . htmlspecialchars($row['category']) . '</td>
                                <td class="py-3 px-6 capitalize">' . htmlspecialchars($row['status']) . '</td>
                                <td class="py-3 px-6 text-center">
                                    <a href="modify_post.php?post_id=' . $row['post_id'] . '" 
                                       class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">View</a>
                                </td>
                            </tr>';
                        }
                    } else {
                        echo '
                        <tr>
                            <td colspan="6" class="py-4 px-6 text-center text-gray-500">
                                No posts found.
                            </td>
                        </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>