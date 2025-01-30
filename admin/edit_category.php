<?php
include "./db_connection.php";
include "./auth.php";

if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['category_id']) || empty($_GET['category_id'])) {
    die("Invalid request. No category ID provided.");
}

$category_id = intval($_GET['category_id']);

$sql = "SELECT * FROM categories WHERE category_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Category not found.");
}

$category = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_category_name = trim($_POST['category_name']);

    if (empty($new_category_name)) {
        $error = "Category name cannot be empty.";
    } else {
        $update_sql = "UPDATE categories SET category = ? WHERE category_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_category_name, $category_id);

        if ($update_stmt->execute()) {
            header("Location: view_category.php?success=Category updated successfully.");
            exit();
        } else {
            $error = "Failed to update category: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100">
    <?php include "./navbar.php"; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Edit Category</h1>

        <?php if (isset($error)) : ?>
            <div class="bg-red-500 text-white p-4 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="bg-white p-6 rounded-lg shadow-md max-w-lg mx-auto">
            <div class="mb-4">
                <label for="category_name" class="block text-gray-700 font-bold mb-2">Category Name:</label>
                <input type="text" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category['category']); ?>"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Update Category</button>
            <a href="view_category.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 ml-2">Cancel</a>
        </form>
    </div>
</body>

</html>
