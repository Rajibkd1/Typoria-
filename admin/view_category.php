<?php
include "./db_connection.php";
include "./auth.php";
include "./navbar.php";

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch categories with post counts using LEFT JOIN
$sql = "SELECT 
            categories.category_id, 
            categories.category, 
            COUNT(posts.post_id) AS post_count 
        FROM categories
        LEFT JOIN posts ON categories.category_id = posts.category_id
        GROUP BY categories.category_id";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Categories</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100">

    <?php
    $data = [];

    // Fetch rows into $data for reuse
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        // Debugging output
        // echo "<pre>";
        // print_r($row);
        // echo "</pre>";
    }
    ?>



    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Categories</h1>
            <a href="./create_category.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Create New Category
            </a>
        </div>

        <table class="min-w-full bg-white rounded-lg shadow-md overflow-hidden">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="py-3 px-6 text-left">Category Name</th>
                    <th class="py-3 px-6 text-left">Posts</th>
                    <th class="py-3 px-6 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($data) > 0) : ?>
                    <?php foreach ($data as $row) : ?>
                        <tr class="border-b">
                            <td class="py-3 px-6 text-gray-700"><?php echo htmlspecialchars($row['category']); ?></td>
                            <td class="py-3 px-6 text-gray-700"><?php echo $row['post_count']; ?></td>
                            <td class="py-3 px-6 text-center">
                                <a href="./edit_category.php?category_id=<?php echo $row['category_id']; ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Edit</a>
                                <form action="./delete_category.php" method="POST" class="inline">
                                    <input type="hidden" name="category_id" value="<?php echo $row['category_id']; ?>">
                                    <button type="submit" onclick="return confirm('Are you sure you want to delete this category?');" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3" class="py-3 px-6 text-center text-gray-600">No categories found</td>
                    </tr>
                <?php endif; ?>
            </tbody>

        </table>
    </div>

</body>

</html>