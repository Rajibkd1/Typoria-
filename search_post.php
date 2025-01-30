<?php
include "./db_connection.php";
include "./navbar.php";

// Initialize variables
$searchQuery = "";
$searchResults = [];

// Handle search query
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchQuery = trim($_POST['search']);
    
    // Check if the search query is not empty
    if (!empty($searchQuery)) {
        $sql = "SELECT * FROM posts WHERE title LIKE ?";
        $stmt = $conn->prepare($sql);
        $searchParam = "%" . $searchQuery . "%";
        $stmt->bind_param("s", $searchParam);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch all matching results
        while ($row = $result->fetch_assoc()) {
            $searchResults[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto mt-8">
        <?php if (!empty($searchQuery)) : ?>
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h2>
            <?php if (count($searchResults) > 0) : ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($searchResults as $post) : ?>
                        <?php $imagePath = './uploads/' . $post['image']; ?>
                        <div class="max-w-sm mx-4 mb-6">
                            <div class="group bg-white rounded-lg shadow-md overflow-hidden relative">
                                <!-- Post Image -->
                                <img class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-110" src="<?php echo htmlspecialchars($imagePath); ?>" alt="Post Image">
                                
                                <!-- Overlay for View Button -->
                                <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <a href="post_view.php?post_id=<?php echo $post['post_id']; ?>" class="bg-blue-500 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-600">View</a>
                                </div>

                                <!-- Post Content -->
                                <div class="p-4">
                                    <h2 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($post['title']); ?></h2>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="text-gray-600">No posts found matching your query.</p>
            <?php endif; ?>
        <?php else : ?>
            <h2 class="text-xl font-medium text-gray-700 mb-6">Enter a keyword to search for posts.</h2>
        <?php endif; ?>
    </div>
</body>

</html>
