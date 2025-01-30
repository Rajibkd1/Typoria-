<?php
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $isLoggedIn = true;
} else {
    $isLoggedIn = false;
}

// Include database connection
include "./db_connection.php";

// Fetch categories from database
$sql = "SELECT * FROM categories";
$result = $conn->query($sql);

// Initialize categories array
$categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a New Post</title>
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">

    <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full bg-white p-8 rounded-lg shadow-lg">
            <h2 class="text-3xl font-extrabold text-gray-900 mb-6">Create a New Post</h2>
            
            <!-- Post Form -->
            <form action="submit_post.php" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="title" id="title" placeholder="Enter title" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 px-3 py-2 placeholder-gray-400 text-gray-700 leading-tight focus:outline-none">
                </div>
                <div class="mb-4">
                    <label for="details" class="block text-sm font-medium text-gray-700">Details</label>
                    <textarea name="details" id="details" rows="5" placeholder="Enter details" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 px-3 py-2 placeholder-gray-400 text-gray-700 leading-tight focus:outline-none resize-none"></textarea>
                </div>
                <div class="mb-4">
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 px-3 py-2 text-gray-700 leading-tight focus:outline-none" required>
                        <option value="" disabled selected>Select category</option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo $category['category']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="image" class="block text-sm font-medium text-gray-700">Image</label>
                    <input type="file" name="image" id="image" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 px-3 py-2 text-gray-700 leading-tight focus:outline-none">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 hover:from-purple-700 hover:via-pink-700 hover:to-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Create Post
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>
