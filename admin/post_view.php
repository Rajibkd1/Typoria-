<?php
/**
 * Typoria Blog Platform
 * Admin - Post View
 */

// Include required files
require_once '../includes/functions.php';
require_once '../includes/theme.php';

// Require admin authentication
$auth = require_admin();
$user_id = $auth['user_id'];
$username = $auth['username'];

// Initialize database connection
$conn = get_db_connection();

// Get post ID from URL
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

if ($post_id <= 0) {
    // Invalid post ID
    header("Location: manage_posts.php");
    exit();
}

// Handle post actions
$message = '';
$message_type = '';

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'approve':
            // Approve post
            $sql = "UPDATE posts SET status = 'approved' WHERE post_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $post_id);
            
            if ($stmt->execute()) {
                $message = "Post approved successfully!";
                $message_type = "success";
                
                // Get post author to send notification
                $sql = "SELECT user_id, title FROM posts WHERE post_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $post_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    // Create notification for post author
                    create_notification(
                        $row['user_id'], 
                        'system', 
                        $post_id, 
                        $user_id,
                        "Your post \"" . substr($row['title'], 0, 30) . "\" has been approved."
                    );
                }
            } else {
                $message = "Error approving post: " . $conn->error;
                $message_type = "error";
            }
            break;
            
        case 'reject':
            // Reject post
            $sql = "UPDATE posts SET status = 'rejected' WHERE post_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $post_id);
            
            if ($stmt->execute()) {
                $message = "Post rejected!";
                $message_type = "success";
                
                // Get post author to send notification
                $sql = "SELECT user_id, title FROM posts WHERE post_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $post_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    // Create notification for post author
                    create_notification(
                        $row['user_id'], 
                        'system', 
                        $post_id, 
                        $user_id,
                        "Your post \"" . substr($row['title'], 0, 30) . "\" has been rejected."
                    );
                }
            } else {
                $message = "Error rejecting post: " . $conn->error;
                $message_type = "error";
            }
            break;
    }
}

// Get post details
$sql = "SELECT p.*, 
        u.name AS author_name, u.profile_image AS author_image, u.user_id AS author_id, 
        c.category,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.post_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Post not found
    header("Location: manage_posts.php");
    exit();
}

$post = $result->fetch_assoc();

// Get post tags
$tags_sql = "SELECT t.tag_name 
             FROM post_tags pt 
             JOIN tags t ON pt.tag_id = t.tag_id 
             WHERE pt.post_id = ?";
$tags_stmt = $conn->prepare($tags_sql);
$tags_stmt->bind_param("i", $post_id);
$tags_stmt->execute();
$tags_result = $tags_stmt->get_result();

$tags = [];
while ($tag = $tags_result->fetch_assoc()) {
    $tags[] = $tag['tag_name'];
}

// Get post comments
$comments_sql = "SELECT c.*, u.name AS commenter_name, u.profile_image AS commenter_image
                FROM comments c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.post_id = ?
                ORDER BY c.created_at DESC";
$comments_stmt = $conn->prepare($comments_sql);
$comments_stmt->bind_param("i", $post_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

// Additional CSS for the page
$additional_css = "
    .admin-layout {
        display: grid;
        grid-template-columns: 240px 1fr;
        min-height: 100vh;
    }
    
    @media (max-width: 768px) {
        .admin-layout {
            grid-template-columns: 1fr;
        }
    }
    
    .admin-sidebar {
        background-color: ".$TYPORIA_COLORS['dark'].";
    }
    
    .status-badge {
        @apply text-xs font-medium px-2 py-1 rounded-full;
    }
    
    .status-pending {
        @apply bg-yellow-100 text-yellow-800;
    }
    
    .status-approved {
        @apply bg-green-100 text-green-800;
    }
    
    .status-rejected {
        @apply bg-red-100 text-red-800;
    }
    
    .status-draft {
        @apply bg-gray-100 text-gray-800;
    }
    
    .status-scheduled {
        @apply bg-blue-100 text-blue-800;
    }
    
    .post-content {
        line-height: 1.8;
    }
    
    .post-content h1 {
        @apply text-2xl font-bold my-4;
    }
    
    .post-content h2 {
        @apply text-xl font-bold my-3;
    }
    
    .post-content h3 {
        @apply text-lg font-bold my-2;
    }
    
    .post-content p {
        @apply my-4;
    }
    
    .post-content ul, .post-content ol {
        @apply list-disc pl-6 my-4;
    }
    
    .post-content blockquote {
        @apply border-l-4 border-gray-300 pl-4 py-2 my-4 italic;
    }
    
    .post-content img {
        @apply max-w-full my-4 rounded-lg;
    }
    
    .post-content pre {
        @apply bg-gray-100 p-4 rounded-lg overflow-x-auto my-4;
    }
    
    .post-content code {
        @apply bg-gray-100 px-2 py-1 rounded;
    }
    
    .post-content a {
        @apply text-typoria-primary hover:underline;
    }
    
    .post-content table {
        @apply w-full border-collapse my-4;
    }
    
    .post-content th, .post-content td {
        @apply border border-gray-300 p-2;
    }
    
    .admin-tabs {
        @apply flex border-b border-gray-200;
    }
    
    .admin-tab {
        @apply px-4 py-2 text-sm font-medium;
    }
    
    .admin-tab.active {
        @apply border-b-2 border-typoria-primary text-typoria-primary;
    }
";

// Page title
$page_title = "View Post: " . $post['title'];

// Generate HTML header
typoria_header($page_title, $additional_css);
?>

<div class="admin-layout">
    <!-- Admin Sidebar -->
    <?php include 'admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="bg-gray-100 p-6">
        <div class="container mx-auto max-w-4xl">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800 truncate max-w-4xl">
                    <?php echo htmlspecialchars($post['title']); ?>
                </h1>
                <div class="mt-4 md:mt-0 flex space-x-2">
                    <a href="edit_post.php?post_id=<?php echo $post_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Post
                    </a>
                    <a href="manage_posts.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Posts
                    </a>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (!empty($message)) : ?>
                <div class="mb-6 bg-<?php echo $message_type == 'success' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $message_type == 'success' ? 'green' : 'red'; ?>-500 text-<?php echo $message_type == 'success' ? 'green' : 'red'; ?>-700 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <?php if ($message_type == 'success') : ?>
                                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            <?php else : ?>
                                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm"><?php echo $message; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Post Overview Card -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="border-b border-gray-200 px-6 py-4 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center">
                        <?php if (!empty($post['author_image']) && file_exists('../uploads/' . $post['author_image'])) : ?>
                            <img class="h-10 w-10 rounded-full object-cover" src="../uploads/<?php echo $post['author_image']; ?>" alt="<?php echo htmlspecialchars($post['author_name']); ?>">
                        <?php else : ?>
                            <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">
                                Author: <span class="text-indigo-600"><?php echo htmlspecialchars($post['author_name']); ?></span>
                            </p>
                            <p class="text-xs text-gray-500">
                                Published: <?php echo format_date($post['date_time']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center flex-wrap gap-2">
                        <span class="status-badge status-<?php echo $post['status']; ?>">
                            <?php echo ucfirst($post['status']); ?>
                        </span>
                        
                        <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <?php echo htmlspecialchars($post['category']); ?>
                        </span>
                        
                        <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?php echo $post['read_time']; ?> min read
                        </span>
                        
                        <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <?php echo $post['views']; ?> views
                        </span>
                    </div>
                </div>
                
                <!-- Post Content -->
                <div class="p-6">
                    <?php if ($post['status'] == 'pending') : ?>
                        <div class="mb-6 flex justify-end gap-2">
                            <a href="post_view.php?post_id=<?php echo $post_id; ?>&action=approve" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center" onclick="return confirm('Are you sure you want to approve this post?')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Approve Post
                            </a>
                            <a href="post_view.php?post_id=<?php echo $post_id; ?>&action=reject" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center" onclick="return confirm('Are you sure you want to reject this post?')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Reject Post
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($post['image']) && file_exists('../uploads/' . $post['image'])) : ?>
                        <div class="mb-6">
                            <img class="w-full rounded-lg object-cover max-h-96" src="../uploads/<?php echo $post['image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                        </div>
                    <?php endif; ?>
                    
                    <div class="post-content">
                        <?php echo $post['details']; ?>
                    </div>
                    
                    <?php if (!empty($tags)) : ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Tags:</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($tags as $tag) : ?>
                                    <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full">
                                        <?php echo htmlspecialchars($tag); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Engagement Stats -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-800 mb-4">Engagement Statistics</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="p-4 bg-blue-50 rounded-lg flex items-center">
                        <div class="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-blue-600"><?php echo $post['views']; ?></div>
                            <div class="text-sm text-blue-600">Views</div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-red-50 rounded-lg flex items-center">
                        <div class="h-12 w-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-red-600"><?php echo $post['like_count']; ?></div>
                            <div class="text-sm text-red-600">Likes</div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-green-50 rounded-lg flex items-center">
                        <div class="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-green-600"><?php echo $post['comment_count']; ?></div>
                            <div class="text-sm text-green-600">Comments</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Post Comments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-medium text-gray-800 mb-4">Comments (<?php echo $post['comment_count']; ?>)</h2>
                
                <?php if ($comments_result->num_rows > 0) : ?>
                    <div class="space-y-4">
                        <?php while ($comment = $comments_result->fetch_assoc()) : ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <?php if (!empty($comment['commenter_image']) && file_exists('../uploads/' . $comment['commenter_image'])) : ?>
                                        <img class="h-10 w-10 rounded-full object-cover" src="../uploads/<?php echo $comment['commenter_image']; ?>" alt="<?php echo htmlspecialchars($comment['commenter_name']); ?>">
                                    <?php else : ?>
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 font-bold">
                                            <?php echo strtoupper(substr($comment['commenter_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="ml-3 flex-1">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($comment['commenter_name']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo format_date($comment['created_at']); ?></div>
                                            </div>
                                            
                                            <div>
                                                <button type="button" class="text-red-600 hover:text-red-900 text-sm font-medium" onclick="if(confirm('Are you sure you want to delete this comment?')) window.location.href='delete_comment.php?id=<?php echo $comment['comment_id']; ?>&post_id=<?php echo $post_id; ?>'">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-2 text-sm text-gray-700">
                                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else : ?>
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <p class="text-lg font-medium text-gray-600">No comments yet</p>
                        <p class="text-gray-500 mt-1">Be the first to comment on this post</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php
// Do not include the standard footer for admin pages
?>
</body>
</html>