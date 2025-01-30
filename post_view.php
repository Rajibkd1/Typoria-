<?php
include "./db_connection.php";
include "./auth.php";

// Check if `post_id` is provided
if (isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']); // Sanitize the input
} else {
    header("Location: index.php");
    exit();
}

// Fetch post details
$sql = "SELECT posts.*, users.name AS user_name, categories.category 
        FROM posts 
        JOIN users ON posts.user_id = users.user_id 
        JOIN categories ON posts.category_id = categories.category_id 
        WHERE posts.post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $post = $result->fetch_assoc();
} else {
    header("Location: index.php");
    exit();
}

// Handle Like
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like']) && $isLoggedIn) {
    // Check if the user already liked the post
    $like_check_sql = "SELECT * FROM likes WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($like_check_sql);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $like_result = $stmt->get_result();

    if ($like_result->num_rows == 0) {
        // Insert like if not already liked
        $like_sql = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";
        $stmt = $conn->prepare($like_sql);
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
    }
}

// Handle Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text']) && $isLoggedIn) {
    $comment_text = trim($_POST['comment_text']);

    if (!empty($comment_text)) {
        $comment_sql = "INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($comment_sql);
        $stmt->bind_param("iis", $post_id, $user_id, $comment_text);
        $stmt->execute();
    }
}

// Fetch likes count
$likes_sql = "SELECT COUNT(*) AS total_likes FROM likes WHERE post_id = ?";
$stmt = $conn->prepare($likes_sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$likes_result = $stmt->get_result();
$likes_count = $likes_result->fetch_assoc()['total_likes'];

// Fetch comments
$comments_sql = "SELECT comments.*, users.name AS user_name FROM comments 
                 JOIN users ON comments.user_id = users.user_id 
                 WHERE post_id = ? ORDER BY comments.created_at DESC";
$stmt = $conn->prepare($comments_sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$comments_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100">

    <?php include "./navbar.php"; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Post Image -->
            <div>
                <img class="w-full h-72 object-cover" src="./uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image">
            </div>

            <!-- Post Content -->
            <div class="p-6">
                <!-- Post Title -->
                <h1 class="text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($post['title']); ?></h1>

                <!-- Post Details -->
                <p class="text-gray-600 mb-6 leading-relaxed"><?php echo htmlspecialchars($post['details']); ?></p>

                <div class="text-sm text-gray-500 mb-6">
                    <p>Posted by: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($post['user_name']); ?></span></p>
                    <p>Category: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($post['category']); ?></span></p>
                    <p>Posted on: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($post['date_time']); ?></span></p>
                </div>

                <!-- Like Section -->
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                        </svg>
                        <span class="text-gray-800 font-medium"><?php echo $likes_count; ?></span>
                    </div>

                    <?php if ($isLoggedIn) : ?>
                        <form method="POST" class="flex items-center space-x-2">
                            <button type="submit" name="like" class="flex items-center space-x-2 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                </svg>
                                <span>Like</span>
                            </button>
                        </form>
                    <?php else : ?>
                        <p class="text-gray-600 italic">Log in to like this post.</p>
                    <?php endif; ?>
                </div>

                <!-- Comments Section -->
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Comments</h2>
                <?php if ($isLoggedIn) : ?>
                    <form method="POST" class="mb-6">
                        <textarea name="comment_text" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Add a comment..."></textarea>
                        <button type="submit" class="mt-3 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Post Comment</button>
                    </form>
                <?php else : ?>
                    <p class="text-gray-600 italic">Log in to post a comment.</p>
                <?php endif; ?>

                <!-- Display Comments -->
                <div class="space-y-4">
                    <?php while ($comment = $comments_result->fetch_assoc()) : ?>
                        <div class="p-4 bg-gray-100 rounded-lg shadow-md">
                            <div class="flex items-center space-x-4">
                                <div class="h-10 w-10 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold">
                                    <?php echo strtoupper($comment['user_name'][0]); ?>
                                </div>
                                <div>
                                    <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($comment['user_name']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($comment['created_at']); ?></p>
                                </div>
                            </div>
                            <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($comment['comment']); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

</body>

</html>