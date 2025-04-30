<?php
/**
 * Typoria Blog Platform
 * Post View Page
 */

// Include required files
require_once 'includes/functions.php';
require_once 'includes/theme.php';

// Get authentication details
$auth = check_auth();
$isLoggedIn = $auth['isLoggedIn'];
$user_id = $auth['user_id'];
$username = $auth['username'];
$profile_image = $auth['profile_image'] ?? null;

// Initialize database connection
$conn = get_db_connection();

// Check if post_id is provided
if (isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']); // Sanitize the input
} else {
    // Redirect to home page if no post ID is provided
    header("Location: index.php");
    exit();
}

// Fetch post details
$sql = "SELECT posts.*, users.name AS user_name, users.user_id AS author_id, 
        users.bio AS user_bio, users.profile_image, categories.category,
        posts.read_time
        FROM posts 
        JOIN users ON posts.user_id = users.user_id 
        JOIN categories ON posts.category_id = categories.category_id 
        WHERE posts.post_id = ? AND posts.status = 'approved'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $post = $result->fetch_assoc();
    
    // Calculate reading time if not set
    if (empty($post['read_time'])) {
        $post['read_time'] = calculate_reading_time($post['details']);
        
        // Update reading time in database
        $update_sql = "UPDATE posts SET read_time = ? WHERE post_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $post['read_time'], $post_id);
        $update_stmt->execute();
    }
    
    // Record post view
    record_post_view($post_id, $isLoggedIn ? $user_id : null);
} else {
    // Post not found or not approved
    header("Location: index.php");
    exit();
}

// Handle Like
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like']) && $isLoggedIn) {
    // Check if the user already liked the post
    $like_check_sql = "SELECT like_id FROM likes WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($like_check_sql);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $like_result = $stmt->get_result();

    if ($like_result->num_rows == 0) {
        // Insert like if not already liked
        $like_sql = "INSERT INTO likes (post_id, user_id, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($like_sql);
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        
        // Create notification for post author
        if ($post['author_id'] != $user_id) {
            $notification_message = $username . " liked your post \"" . $post['title'] . "\"";
            create_notification($post['author_id'], 'like', $post_id, $user_id, $notification_message);
        }
        
        // Redirect to avoid form resubmission
        header("Location: post_view.php?post_id=" . $post_id);
        exit();
    }
}

// Handle Bookmark
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bookmark']) && $isLoggedIn) {
    // Check if the user already bookmarked the post
    $bookmark_check_sql = "SELECT bookmark_id FROM bookmarks WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($bookmark_check_sql);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $bookmark_result = $stmt->get_result();

    if ($bookmark_result->num_rows == 0) {
        // Insert bookmark if not already bookmarked
        $bookmark_sql = "INSERT INTO bookmarks (post_id, user_id, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($bookmark_sql);
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        
        // Redirect to avoid form resubmission
        header("Location: post_view.php?post_id=" . $post_id);
        exit();
    }
}

// Handle Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text']) && $isLoggedIn) {
    $comment_text = trim($_POST['comment_text']);
    $parent_comment_id = isset($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;

    if (!empty($comment_text)) {
        // Insert comment
        $comment_sql = "INSERT INTO comments (post_id, user_id, comment, parent_comment_id, created_at) 
                       VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($comment_sql);
        $stmt->bind_param("iisi", $post_id, $user_id, $comment_text, $parent_comment_id);
        $stmt->execute();
        $comment_id = $stmt->insert_id;
        
        // Create notification for post author
        if ($post['author_id'] != $user_id) {
            $notification_message = $username . " commented on your post \"" . $post['title'] . "\"";
            create_notification($post['author_id'], 'comment', $comment_id, $user_id, $notification_message);
        }
        
        // If this is a reply, notify the parent comment author as well
        if ($parent_comment_id) {
            $parent_sql = "SELECT user_id FROM comments WHERE comment_id = ?";
            $stmt = $conn->prepare($parent_sql);
            $stmt->bind_param("i", $parent_comment_id);
            $stmt->execute();
            $parent_result = $stmt->get_result();
            
            if ($parent_result->num_rows > 0) {
                $parent_user_id = $parent_result->fetch_assoc()['user_id'];
                
                // Don't notify if it's the same user or the post author (already notified above)
                if ($parent_user_id != $user_id && $parent_user_id != $post['author_id']) {
                    $notification_message = $username . " replied to your comment on \"" . $post['title'] . "\"";
                    create_notification($parent_user_id, 'comment', $comment_id, $user_id, $notification_message);
                }
            }
        }
        
        // Redirect to avoid form resubmission
        header("Location: post_view.php?post_id=" . $post_id . "#comment-" . $comment_id);
        exit();
    }
}

// Fetch likes count
$likes_sql = "SELECT COUNT(*) AS total_likes FROM likes WHERE post_id = ?";
$stmt = $conn->prepare($likes_sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$likes_result = $stmt->get_result();
$likes_count = $likes_result->fetch_assoc()['total_likes'];

// Check if user has liked or bookmarked this post
$user_liked = $isLoggedIn ? has_user_liked_post($post_id, $user_id) : false;
$user_bookmarked = $isLoggedIn ? has_user_bookmarked_post($post_id, $user_id) : false;

// Fetch comments (parent comments first, then replies)
$comments_sql = "SELECT c.*, u.name AS user_name, u.profile_image, 
                (SELECT COUNT(*) FROM comments WHERE parent_comment_id = c.comment_id) AS reply_count
                FROM comments c
                JOIN users u ON c.user_id = u.user_id 
                WHERE c.post_id = ? AND c.parent_comment_id IS NULL
                ORDER BY c.created_at DESC";
$stmt = $conn->prepare($comments_sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$comments_result = $stmt->get_result();

// Get related posts
$related_posts = get_related_posts($post_id, $post['category_id'], 3);

// Generate HTML header
typoria_header($post['title'], "
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

    .post-content {
        line-height: 1.8;
    }
    
    .post-content p {
        margin-bottom: 1.5em;
    }
    
    .post-content h2, .post-content h3, .post-content h4 {
        margin-top: 1.5em;
        margin-bottom: 0.75em;
        font-weight: bold;
    }
    
    .post-content h2 {
        font-size: 1.5em;
    }
    
    .post-content h3 {
        font-size: 1.25em;
    }
    
    .post-content ul, .post-content ol {
        margin-left: 1.5em;
        margin-bottom: 1.5em;
    }
    
    .post-content li {
        margin-bottom: 0.5em;
    }
    
    .post-content blockquote {
        border-left: 4px solid #8B5CF6;
        padding-left: 1em;
        font-style: italic;
        margin: 1.5em 0;
        color: #4B5563;
    }
    
    .comment-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .comment-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    /* Reply form styling */
    .reply-form {
        display: none;
    }
    
    .reply-form.active {
        display: block;
    }
    
    /* User avatar styles */
    .user-avatar {
        height: 3rem;
        width: 3rem;
        border-radius: 9999px;
        overflow: hidden;
        background: linear-gradient(to right, #7c3aed, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        flex-shrink: 0;
    }
    
    .user-avatar.small {
        height: 2.5rem;
        width: 2.5rem;
        font-size: 0.875rem;
    }
    
    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .comment-avatar {
        height: 2.5rem;
        width: 2.5rem;
        border-radius: 9999px;
        overflow: hidden;
        background: linear-gradient(to right, #7c3aed, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 0.875rem;
        flex-shrink: 0;
    }
    
    .comment-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .reply-avatar {
        height: 2rem;
        width: 2rem;
        border-radius: 9999px;
        overflow: hidden;
        background: linear-gradient(to right, #7c3aed, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 0.75rem;
        flex-shrink: 0;
    }
    
    .reply-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
");
?>

<!-- Start of body content -->
<?php include 'navbar.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Back button -->
        <a href="javascript:history.back()" class="inline-flex items-center text-typoria-primary hover:text-typoria-secondary mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back
        </a>
        
        <article class="bg-white rounded-xl shadow-lg overflow-hidden fade-in">
            <!-- Post Image -->
            <div class="relative">
                <img class="w-full h-96 object-cover" src="./uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                <div class="absolute bottom-0 left-0 p-8">
                    <span class="inline-block bg-typoria-secondary text-white text-xs px-3 py-1 rounded-full mb-3"><?php echo htmlspecialchars($post['category']); ?></span>
                    <h1 class="text-4xl font-bold text-white font-serif"><?php echo htmlspecialchars($post['title']); ?></h1>
                </div>
            </div>

            <!-- Post Metadata -->
            <div class="p-8 border-b border-gray-200">
                <div class="flex flex-wrap items-center justify-between">
                    <div class="flex items-center">
                        <div class="user-avatar mr-4">
                            <?php if (!empty($post['profile_image']) && $post['profile_image'] != 'default.png'): ?>
                                <img src="uploads/profiles/<?php echo htmlspecialchars($post['profile_image']); ?>" alt="<?php echo htmlspecialchars($post['user_name']); ?>">
                            <?php else: ?>
                                <?php echo strtoupper(substr($post['user_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="author.php?id=<?php echo $post['author_id']; ?>" class="font-medium text-gray-800 hover:text-typoria-primary"><?php echo htmlspecialchars($post['user_name']); ?></a>
                            <p class="text-sm text-gray-500"><?php echo format_date($post['date_time']); ?></p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4 mt-4 sm:mt-0">
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-typoria-primary" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            <span><?php echo $post['read_time']; ?> min read</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                            </svg>
                            <span><?php echo $likes_count; ?> likes</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Post Content -->
            <div class="p-8">
                <div class="post-content text-gray-800 text-lg">
                    <?php echo $post['details']; ?>
                </div>
                
                <!-- Tags (if you have them) -->
                <div class="flex flex-wrap gap-2 mt-8">
                    <?php
                    // Fetch tags for this post (if you've implemented tags)
                    $tags_sql = "SELECT t.tag_id, t.tag_name 
                                FROM tags t
                                JOIN post_tags pt ON t.tag_id = pt.tag_id
                                WHERE pt.post_id = ?";
                    $stmt = $conn->prepare($tags_sql);
                    $stmt->bind_param("i", $post_id);
                    $stmt->execute();
                    $tags_result = $stmt->get_result();
                    
                    if ($tags_result->num_rows > 0) {
                        while ($tag = $tags_result->fetch_assoc()) {
                            echo '<a href="tag.php?id=' . $tag['tag_id'] . '" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-1 rounded-full text-sm transition-colors">' . htmlspecialchars($tag['tag_name']) . '</a>';
                        }
                    }
                    ?>
                </div>
                
                <!-- Post Actions -->
                <div class="flex flex-wrap justify-between items-center mt-8 pt-8 border-t border-gray-200">
                    <div class="flex space-x-4 mb-4 sm:mb-0">
                        <?php if ($isLoggedIn) : ?>
                            <!-- Like Button -->
                            <form method="POST" class="inline">
                                <input type="hidden" name="like" value="1">
                                <button type="submit" class="flex items-center space-x-2 <?php echo $user_liked ? 'text-red-500' : 'text-gray-500 hover:text-red-500'; ?> transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" <?php echo $user_liked ? 'fill="currentColor"' : 'fill="none" stroke="currentColor" stroke-width="2"'; ?> viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <span><?php echo $user_liked ? 'Liked' : 'Like'; ?></span>
                                </button>
                            </form>
                            
                            <!-- Bookmark Button -->
                            <form method="POST" class="inline">
                                <input type="hidden" name="bookmark" value="1">
                                <button type="submit" class="flex items-center space-x-2 <?php echo $user_bookmarked ? 'text-typoria-primary' : 'text-gray-500 hover:text-typoria-primary'; ?> transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" <?php echo $user_bookmarked ? 'fill="currentColor"' : 'fill="none" stroke="currentColor" stroke-width="2"'; ?> viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                    <span><?php echo $user_bookmarked ? 'Bookmarked' : 'Bookmark'; ?></span>
                                </button>
                            </form>
                        <?php else : ?>
                            <!-- Login prompts for unauthenticated users -->
                            <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="flex items-center space-x-2 text-gray-500 hover:text-red-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                <span>Login to like</span>
                            </a>
                            
                            <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="flex items-center space-x-2 text-gray-500 hover:text-typoria-primary transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                </svg>
                                <span>Login to bookmark</span>
                            </a>
                        <?php endif; ?>
                        
                        <!-- Share Button -->
                        <button type="button" onclick="sharePost()" class="flex items-center space-x-2 text-gray-500 hover:text-typoria-secondary transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                            </svg>
                            <span>Share</span>
                        </button>
                    </div>
                    
                    <!-- Social Share Buttons -->
                    <div class="flex space-x-2">
                        <?php 
                        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                        typoria_social_share_buttons($current_url, $post['title']); 
                        ?>
                    </div>
                </div>
            </div>
        </article>
        
        <!-- Author Card -->
        <div class="mt-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-4 font-serif">About the Author</h3>
            <?php typoria_author_card($post['author_id']); ?>
        </div>
        
        <!-- Related Posts -->
        <?php if (count($related_posts) > 0) : ?>
        <div class="mt-12">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 font-serif">Related Posts</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($related_posts as $related_post) : ?>
                    <?php typoria_post_card($related_post, 'small'); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Comments Section -->
        <div class="mt-12">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 font-serif">Comments</h3>
            
            <?php if ($isLoggedIn) : ?>
                <!-- Comment Form -->
                <form method="POST" class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <div class="flex items-start">
                        <div class="user-avatar small mr-3">
                            <?php if (!empty($profile_image) && $profile_image != 'default.png'): ?>
                                <img src="uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" alt="<?php echo htmlspecialchars($username); ?>">
                            <?php else: ?>
                                <?php echo strtoupper(substr($username, 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <textarea name="comment_text" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-typoria-primary focus:border-transparent transition-all" placeholder="Add a comment..."></textarea>
                            <button type="submit" class="mt-3 bg-gradient-to-r from-typoria-primary to-typoria-secondary text-white px-4 py-2 rounded-lg font-medium hover:shadow-md transition-all">
                                Post Comment
                            </button>
                        </div>
                    </div>
                </form>
            <?php else : ?>
                <!-- Login Prompt -->
                <div class="bg-gray-100 rounded-lg p-6 mb-8 text-center">
                    <p class="text-gray-700 mb-4">Login to join the conversation</p>
                    <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="bg-gradient-to-r from-typoria-primary to-typoria-secondary text-white px-6 py-2 rounded-full font-medium inline-block hover:shadow-md transition-all">
                        Login to Comment
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Comments List -->
            <div class="space-y-6">
                <?php 
                if ($comments_result->num_rows > 0) {
                    while ($comment = $comments_result->fetch_assoc()) {
                        // Display parent comment
                        echo '<div id="comment-' . $comment['comment_id'] . '" class="comment-card bg-white rounded-lg shadow-md p-6">';
                        echo '<div class="flex items-start">';
                        
                        // Comment author avatar
                        echo '<div class="comment-avatar mr-3">';
                        if (!empty($comment['profile_image']) && $comment['profile_image'] != 'default.png') {
                            echo '<img src="uploads/profiles/' . htmlspecialchars($comment['profile_image']) . '" alt="' . htmlspecialchars($comment['user_name']) . '">';
                        } else {
                            echo strtoupper(substr($comment['user_name'], 0, 1));
                        }
                        echo '</div>';
                        
                        echo '<div class="flex-1">';
                        echo '<div class="flex justify-between items-start">';
                        echo '<div>';
                        echo '<h4 class="font-bold text-gray-800">' . htmlspecialchars($comment['user_name']) . '</h4>';
                        echo '<p class="text-sm text-gray-500">' . format_date($comment['created_at']) . '</p>';
                        echo '</div>';
                        
                        // Show reply button if logged in
                        if ($isLoggedIn) {
                            echo '<button type="button" onclick="toggleReplyForm(' . $comment['comment_id'] . ')" class="text-typoria-primary hover:text-typoria-secondary text-sm">Reply</button>';
                        }
                        
                        echo '</div>';
                        echo '<div class="mt-2 text-gray-700">' . htmlspecialchars($comment['comment']) . '</div>';
                        
                        // Reply form (hidden by default)
                        if ($isLoggedIn) {
                            echo '<div id="reply-form-' . $comment['comment_id'] . '" class="reply-form mt-4">';
                            echo '<form method="POST">';
                            echo '<input type="hidden" name="parent_comment_id" value="' . $comment['comment_id'] . '">';
                            echo '<div class="flex items-start">';
                            
                            // Current user avatar in reply form
                            echo '<div class="reply-avatar mr-2">';
                            if (!empty($profile_image) && $profile_image != 'default.png') {
                                echo '<img src="uploads/profiles/' . htmlspecialchars($profile_image) . '" alt="' . htmlspecialchars($username) . '">';
                            } else {
                                echo strtoupper(substr($username, 0, 1));
                            }
                            echo '</div>';
                            
                            echo '<div class="flex-1">';
                            echo '<textarea name="comment_text" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-typoria-primary focus:border-transparent transition-all text-sm" placeholder="Write a reply..."></textarea>';
                            echo '<div class="flex justify-end mt-2">';
                            echo '<button type="button" onclick="toggleReplyForm(' . $comment['comment_id'] . ')" class="text-gray-600 hover:text-gray-800 mr-2 text-sm">Cancel</button>';
                            echo '<button type="submit" class="bg-typoria-primary hover:bg-typoria-secondary text-white px-3 py-1 rounded text-sm transition-colors">Reply</button>';
                            echo '</div>';
                            echo '</div>';
                            
                            echo '</div>';
                            echo '</form>';
                            echo '</div>';
                        }
                        
                        // Display replies count and load replies button
                        if ($comment['reply_count'] > 0) {
                            echo '<div class="mt-3">';
                            echo '<button type="button" onclick="loadReplies(' . $comment['comment_id'] . ')" class="text-typoria-primary hover:text-typoria-secondary text-sm flex items-center">';
                            echo '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">';
                            echo '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />';
                            echo '</svg>';
                            echo 'Show ' . $comment['reply_count'] . ' ' . ($comment['reply_count'] == 1 ? 'reply' : 'replies');
                            echo '</button>';
                            echo '<div id="replies-' . $comment['comment_id'] . '" class="replies-container mt-3 ml-6 space-y-4"></div>';
                            echo '</div>';
                        }
                        
                        echo '</div>'; // End of comment content
                        echo '</div>'; // End of flex container
                        echo '</div>'; // End of comment card
                    }
                } else {
                    echo '<div class="text-center text-gray-600 py-8">Be the first to comment!</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for share, reply, and loading replies -->
<script>
function sharePost() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo addslashes($post['title']); ?>',
            url: window.location.href
        }).then(() => {
            console.log("Thanks for sharing!");
        }).catch(console.error);
    } else {
        // Fallback
        const dummy = document.createElement("input");
        document.body.appendChild(dummy);
        dummy.value = window.location.href;
        dummy.select();
        document.execCommand("copy");
        document.body.removeChild(dummy);
        alert("Link copied to clipboard!");
    }
}

function toggleReplyForm(commentId) {
    const replyForm = document.getElementById('reply-form-' + commentId);
    replyForm.classList.toggle('active');
    
    if (replyForm.classList.contains('active')) {
        // Focus the textarea when form is revealed
        replyForm.querySelector('textarea').focus();
    }
}

function loadReplies(commentId) {
    const repliesContainer = document.getElementById('replies-' + commentId);
    const loadButton = repliesContainer.previousElementSibling;
    
    // If replies are already loaded, just toggle visibility
    if (repliesContainer.innerHTML.trim() !== '') {
        repliesContainer.classList.toggle('hidden');
        loadButton.textContent = repliesContainer.classList.contains('hidden') ? 
            'Show replies' : 'Hide replies';
        return;
    }
    
    // Show loading indicator
    repliesContainer.innerHTML = '<div class="text-center py-2">Loading replies...</div>';
    
    // Fetch replies using AJAX
    fetch('get_replies.php?comment_id=' + commentId)
        .then(response => response.json())
        .then(replies => {
            repliesContainer.innerHTML = '';
            
            if (replies.length === 0) {
                repliesContainer.innerHTML = '<div class="text-center py-2 text-gray-600">No replies found</div>';
                return;
            }
            
            replies.forEach(reply => {
                // Create user avatar HTML based on profile image
                let avatarHtml = '';
                if (reply.profile_image && reply.profile_image !== 'default.png') {
                    avatarHtml = `<div class="reply-avatar mr-3"><img src="uploads/profiles/${reply.profile_image}" alt="${reply.user_name}"></div>`;
                } else {
                    avatarHtml = `<div class="reply-avatar mr-3">${reply.user_name.charAt(0).toUpperCase()}</div>`;
                }
                
                const replyHtml = `
                    <div id="comment-${reply.comment_id}" class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-start">
                            ${avatarHtml}
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h5 class="font-bold text-gray-800 text-sm">${reply.user_name}</h5>
                                        <p class="text-xs text-gray-500">${reply.formatted_date}</p>
                                    </div>
                                </div>
                                <div class="mt-1 text-gray-700 text-sm">${reply.comment}</div>
                            </div>
                        </div>
                    </div>
                `;
                repliesContainer.innerHTML += replyHtml;
            });
            
            // Update button text
            loadButton.textContent = 'Hide replies';
        })
        .catch(error => {
            console.error('Error loading replies:', error);
            repliesContainer.innerHTML = '<div class="text-center py-2 text-red-500">Error loading replies</div>';
        });
}
</script>

<?php 
// Generate footer
typoria_footer(); 
?>