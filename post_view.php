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
    <!-- Custom CSS for Animations -->
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .like-button:hover svg {
            transform: scale(1.1);
            transition: transform 0.2s ease-in-out;
        }

        .comment-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .comment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Scrollable Comments Container */
        .comments-container {
            max-height: 300px; /* Adjust height as needed */
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #6b46c1 #e9d8fd;
        }

        .comments-container::-webkit-scrollbar {
            width: 8px;
        }

        .comments-container::-webkit-scrollbar-track {
            background: #e9d8fd;
            border-radius: 4px;
        }

        .comments-container::-webkit-scrollbar-thumb {
            background: #6b46c1;
            border-radius: 4px;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-100">

    <?php include "./navbar.php"; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden fade-in">
            <!-- Post Image -->
            <div class="relative">
                <img class="w-full h-96 object-cover" src="./uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image">
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                <div class="absolute bottom-0 left-0 p-6">
                    <h1 class="text-4xl font-bold text-white"><?php echo htmlspecialchars($post['title']); ?></h1>
                </div>
            </div>

            <!-- Post Content -->
            <div class="p-8">
                <!-- Post Details -->
                <p class="text-gray-700 leading-relaxed mb-8"><?php echo htmlspecialchars($post['details']); ?></p>

                <!-- Post Metadata -->
                <div class="flex flex-col space-y-2 text-sm text-gray-600 mb-8">
                    <p>Posted by: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($post['user_name']); ?></span></p>
                    <p>Category: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($post['category']); ?></span></p>
                    <p>Posted on: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($post['date_time']); ?></span></p>
                </div>

                <!-- Like Section -->
                <div class="flex items-center justify-between mb-8 p-6 bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg shadow-sm">
                    <div class="flex items-center space-x-3">
                        <!-- Heart Icon -->
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500 hover:text-red-600 transition-all duration-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                            <!-- Like Count -->
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                <?php echo $likes_count; ?>
                            </span>
                        </div>
                        <span class="text-xl font-semibold text-gray-800">Likes</span>
                    </div>

                    <?php if ($isLoggedIn) : ?>
                        <!-- Like Button -->
                        <form method="POST">
                            <button type="submit" name="like" class="like-button flex items-center space-x-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all duration-300 transform hover:scale-105">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                </svg>
                                <span>Like</span>
                            </button>
                        </form>
                    <?php else : ?>
                        <!-- Login Prompt -->
                        <p class="text-gray-600 italic">Log in to like this post.</p>
                    <?php endif; ?>
                </div>

                <!-- Comments Section -->
                <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg shadow-sm p-6 mb-8">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Comments</h2>

                    <?php if ($isLoggedIn) : ?>
                        <!-- Comment Form -->
                        <form method="POST" class="mb-8">
                            <textarea name="comment_text" rows="4" class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300" placeholder="Add a comment..."></textarea>
                            <button type="submit" class="mt-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all duration-300 transform hover:scale-105">
                                Post Comment
                            </button>
                        </form>
                    <?php else : ?>
                        <!-- Login Prompt -->
                        <p class="text-gray-600 italic">Log in to post a comment.</p>
                    <?php endif; ?>

                    <!-- See Comments Button -->
                    <button id="see-comments-btn" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all duration-300 transform hover:scale-105 mb-6">
                        See Comments
                    </button>

                    <!-- Comments Container (Hidden by Default) -->
                    <div id="comments-container" class="comments-container space-y-6 hidden">
                        <?php
                        $comment_count = 0;
                        while ($comment = $comments_result->fetch_assoc()) :
                            $comment_count++;
                            if ($comment_count > 5) break; // Show only 5 comments initially
                        ?>
                            <div class="comment-card p-6 bg-white rounded-lg shadow-md transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                                <div class="flex items-center space-x-4">
                                    <!-- User Avatar -->
                                    <div class="h-12 w-12 bg-gradient-to-r from-purple-500 to-blue-500 text-white rounded-full flex items-center justify-center font-bold text-xl">
                                        <?php echo strtoupper($comment['user_name'][0]); ?>
                                    </div>
                                    <div>
                                        <!-- User Name -->
                                        <p class="text-lg font-medium text-gray-800"><?php echo htmlspecialchars($comment['user_name']); ?></p>
                                        <!-- Comment Timestamp -->
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($comment['created_at']); ?></p>
                                    </div>
                                </div>
                                <!-- Comment Text -->
                                <p class="mt-4 text-gray-700"><?php echo htmlspecialchars($comment['comment']); ?></p>
                            </div>
                        <?php endwhile; ?>

                        <!-- Load More Comments (if more than 5) -->
                        <?php if ($comments_result->num_rows > 5) : ?>
                            <div id="load-more-comments" class="text-center mt-6">
                                <button class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all duration-300 transform hover:scale-105">
                                    Load More Comments
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Toggle and Load More -->
    <script>
        // Toggle Comments Visibility
        const seeCommentsBtn = document.getElementById('see-comments-btn');
        const commentsContainer = document.getElementById('comments-container');

        seeCommentsBtn.addEventListener('click', () => {
            commentsContainer.classList.toggle('hidden');
            seeCommentsBtn.textContent = commentsContainer.classList.contains('hidden') ? 'See Comments' : 'Hide Comments';
        });

        // Load More Comments
        const loadMoreBtn = document.getElementById('load-more-comments');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                // Fetch and append more comments (you can implement this with AJAX or PHP)
                alert('Load more comments functionality can be implemented here.');
            });
        }
    </script>
</body>

</html>