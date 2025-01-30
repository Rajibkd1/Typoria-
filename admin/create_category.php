<?php
include "./db_connection.php";
include "./auth.php";

// Ensure the user is logged in
if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name']);

    // Validate input
    if (empty($category_name)) {
        $error = "Category name cannot be empty.";
    } else {
        // Check for duplicate category
        $check_sql = "SELECT * FROM categories WHERE category = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $category_name);
        $stmt->execute();
        $check_result = $stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Category already exists.";
        } else {
            // Insert new category into the database
            $insert_sql = "INSERT INTO categories (category) VALUES (?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("s", $category_name);

            if ($stmt->execute()) {
                $success = "Category created successfully.";
            } else {
                $error = "Failed to create category: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Category</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100">
    <?php include "./navbar.php"; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Create New Category</h1>

        <?php if (isset($error)) : ?>
            <div class="bg-red-500 text-white p-4 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)) : ?>
            <div class="bg-green-500 text-white p-4 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="bg-white p-6 rounded-lg shadow-md max-w-lg mx-auto">
            <div class="mb-4">
                <label for="category_name" class="block text-gray-700 font-bold mb-2">Category Name:</label>
                <input type="text" id="category_name" name="category_name"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
            </div>

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Create Category</button>
            <a href="view_category.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 ml-2">Cancel</a>
        </form>
    </div>
</body>

</html>
