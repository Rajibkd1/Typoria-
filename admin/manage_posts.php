<?php
/**
 * Typoria Blog Platform
 * Admin - Manage Posts
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

// Handle post actions
$message = '';
$message_type = '';

if (isset($_GET['action']) && isset($_GET['post_id'])) {
    $action = $_GET['action'];
    $post_id = (int)$_GET['post_id'];
    
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
                    // Create notification for post author (sending as a system notification)
                    create_notification(
                        $row['user_id'], 
                        'system',  // Setting this as a system notification
                        $post_id, 
                        null,     // Send as system (null) instead of using $user_id
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
                    // Create notification for post author (sending as a system notification)
                    create_notification(
                        $row['user_id'], 
                        'system',  // Setting this as a system notification
                        $post_id, 
                        null,     // Send as system (null) instead of using $user_id
                        "Your post \"" . substr($row['title'], 0, 30) . "\" has been rejected."
                    );
                }
            } else {
                $message = "Error rejecting post: " . $conn->error;
                $message_type = "error";
            }
            break;
            
        case 'delete':
            // Delete post
            $sql = "DELETE FROM posts WHERE post_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $post_id);
            
            if ($stmt->execute()) {
                $message = "Post deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting post: " . $conn->error;
                $message_type = "error";
            }
            break;
    }
}

// Pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$status_filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build query based on filters
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($search_query)) {
    $where_conditions[] = "(p.title LIKE ? OR p.details LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $param_types .= 'i';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Count total posts for pagination
$count_sql = "SELECT COUNT(*) as total FROM posts p $where_clause";
$stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$count_result = $stmt->get_result();
$total_posts = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $limit);

// Get posts with filtering and pagination
$sql = "SELECT p.*, u.name AS user_name, c.category,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        JOIN categories c ON p.category_id = c.category_id
        $where_clause
        ORDER BY p.date_time DESC
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);

// Add limit and offset to params
$params[] = $offset;
$params[] = $limit;
$param_types .= 'ii';

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$posts_result = $stmt->get_result();

// Get categories for filter
$categories_sql = "SELECT * FROM categories ORDER BY category";
$categories_result = $conn->query($categories_sql);

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
";

// Page title
$page_title = "Manage Posts";

// Generate HTML header
typoria_header($page_title, $additional_css);
?>

<div class="admin-layout">
    <!-- Admin Sidebar -->
    <?php include 'admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="bg-gray-100 p-6">
        <div class="container mx-auto">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Manage Posts</h1>
                <a href="../create_post.php" class="mt-4 md:mt-0 bg-typoria-primary hover:bg-typoria-primary/90 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create New Post
                </a>
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
            
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-4">
                    <div class="flex flex-col md:flex-row md:items-end md:space-x-4 space-y-4 md:space-y-0">
                        <!-- Search -->
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Posts</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20"
                                placeholder="Search by title or content...">
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="w-full md:w-auto">
                            <label for="filter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="filter" name="filter" class="rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="scheduled" <?php echo $status_filter == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            </select>
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="w-full md:w-auto">
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select id="category" name="category" class="rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20">
                                <option value="">All Categories</option>
                                <?php if ($categories_result->num_rows > 0) : ?>
                                    <?php while ($category = $categories_result->fetch_assoc()) : ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <!-- Filter Button -->
                        <div>
                            <button type="submit" class="w-full md:w-auto bg-typoria-primary hover:bg-typoria-primary/90 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                Apply Filters
                            </button>
                        </div>
                        
                        <!-- Reset Filters -->
                        <div>
                            <a href="manage_posts.php" class="block text-center w-full md:w-auto bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Posts Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Title
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Author
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Category
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Stats
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($posts_result->num_rows > 0) : ?>
                                <?php while ($post = $posts_result->fetch_assoc()) : ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0">
                                                    <?php if (!empty($post['image']) && file_exists('../uploads/' . $post['image'])) : ?>
                                                        <img class="h-10 w-10 rounded-md object-cover" src="../uploads/<?php echo $post['image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                                    <?php else : ?>
                                                        <div class="h-10 w-10 rounded-md bg-gray-200 flex items-center justify-center text-gray-500">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 max-w-xs truncate">
                                                        <a href="post_view.php?post_id=<?php echo $post['post_id']; ?>" class="hover:text-typoria-primary" target="_blank">
                                                            <?php echo htmlspecialchars($post['title']); ?>
                                                        </a>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        ID: <?php echo $post['post_id']; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($post['user_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($post['category']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="status-badge status-<?php echo $post['status']; ?>">
                                                <?php echo ucfirst($post['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo format_date($post['date_time']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                                <div class="flex items-center" title="Views">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <?php echo $post['views']; ?>
                                                </div>
                                                <div class="flex items-center" title="Likes">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                    </svg>
                                                    <?php echo $post['like_count']; ?>
                                                </div>
                                                <div class="flex items-center" title="Comments">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                    <?php echo $post['comment_count']; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-2">
                                                <a href="post_view.php?post_id=<?php echo $post['post_id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="View" target="_blank">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>
                                                <a href="edit_post.php?post_id=<?php echo $post['post_id']; ?>" class="text-blue-600 hover:text-blue-900" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </a>
                                                
                                                <?php if ($post['status'] == 'pending') : ?>
                                                    <a href="manage_posts.php?action=approve&post_id=<?php echo $post['post_id']; ?>" class="text-green-600 hover:text-green-900" title="Approve" onclick="return confirm('Are you sure you want to approve this post?')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </a>
                                                    <a href="manage_posts.php?action=reject&post_id=<?php echo $post['post_id']; ?>" class="text-red-600 hover:text-red-900" title="Reject" onclick="return confirm('Are you sure you want to reject this post?')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="manage_posts.php?action=delete&post_id=<?php echo $post['post_id']; ?>" class="text-red-600 hover:text-red-900" title="Delete" onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center py-8">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-xl font-medium text-gray-600">No posts found</p>
                                            <p class="text-gray-500 mt-1">Try changing your filters or create a new post</p>
                                            <a href="../create_post.php" class="mt-4 bg-typoria-primary hover:bg-typoria-primary/90 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                                Create New Post
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1) : ?>
                <div class="mt-6">
                    <nav class="flex justify-center">
                        <ul class="flex space-x-2">
                            <!-- Previous Page -->
                            <?php if ($page > 1) : ?>
                                <li>
                                    <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo $status_filter; ?>&search=<?php echo $search_query; ?>&category=<?php echo $category_filter; ?>" class="px-3 py-1 bg-white rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Page Numbers -->
                            <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++) : ?>
                                <li>
                                    <a href="?page=<?php echo $i; ?>&filter=<?php echo $status_filter; ?>&search=<?php echo $search_query; ?>&category=<?php echo $category_filter; ?>" 
                                       class="px-3 py-1 rounded-md <?php echo $i == $page ? 'bg-typoria-primary text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Next Page -->
                            <?php if ($page < $total_pages) : ?>
                                <li>
                                    <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo $status_filter; ?>&search=<?php echo $search_query; ?>&category=<?php echo $category_filter; ?>" class="px-3 py-1 bg-white rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <p class="text-center text-gray-500 text-sm mt-2">
                        Showing <?php echo min(($page - 1) * $limit + 1, $total_posts); ?> to <?php echo min($page * $limit, $total_posts); ?> of <?php echo $total_posts; ?> posts
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php
// Do not include the standard footer for admin pages
?>
</body>
</html>