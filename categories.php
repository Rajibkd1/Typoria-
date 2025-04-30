<?php
/**
 * Typoria Blog Platform
 * Categories Page
 */

// Include required files
require_once 'includes/functions.php';
require_once 'includes/theme.php';

// Get authentication details
$auth = check_auth();
$isLoggedIn = $auth['isLoggedIn'];
$user_id = $auth['user_id'];

// Initialize database connection
$conn = get_db_connection();

// Get active category ID from URL, if any
$active_category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get all categories with post counts
$categories_sql = "SELECT c.*, COUNT(p.post_id) AS post_count 
                  FROM categories c
                  LEFT JOIN posts p ON c.category_id = p.category_id 
                          AND p.status = 'approved'
                  GROUP BY c.category_id
                  ORDER BY c.category ASC";
$categories_result = $conn->query($categories_sql);

// Define default category data
$active_category = [
    'category_id' => 0,
    'category' => 'All Categories',
    'post_count' => 0,
];

// Calculate total post count for 'All Categories'
$total_posts_sql = "SELECT COUNT(*) AS total FROM posts WHERE status = 'approved'";
$total_posts_result = $conn->query($total_posts_sql);
$active_category['post_count'] = $total_posts_result->fetch_assoc()['total'];

// Get posts for the active category or all posts if no category selected
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$posts_per_page = 12;
$offset = ($page - 1) * $posts_per_page;

if ($active_category_id > 0) {
    // Verify the category exists and get its data
    $category_info_sql = "SELECT c.*, COUNT(p.post_id) AS post_count 
                          FROM categories c
                          LEFT JOIN posts p ON c.category_id = p.category_id 
                                  AND p.status = 'approved'
                          WHERE c.category_id = ?
                          GROUP BY c.category_id";
    $stmt = $conn->prepare($category_info_sql);
    $stmt->bind_param("i", $active_category_id);
    $stmt->execute();
    $category_result = $stmt->get_result();
    
    if ($category_result->num_rows > 0) {
        $active_category = $category_result->fetch_assoc();
    } else {
        // Invalid category ID provided, redirect to all categories
        header("Location: categories.php");
        exit();
    }
    
    // Get posts for this category
    $posts_sql = "SELECT p.*, u.name AS user_name, u.profile_image AS user_image, c.category,
                 (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
                 (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count
                 FROM posts p
                 JOIN users u ON p.user_id = u.user_id
                 JOIN categories c ON p.category_id = c.category_id
                 WHERE p.status = 'approved' AND p.category_id = ?
                 ORDER BY p.date_time DESC
                 LIMIT ?, ?";
    
    $stmt = $conn->prepare($posts_sql);
    $stmt->bind_param("iii", $active_category_id, $offset, $posts_per_page);
    $stmt->execute();
    $posts_result = $stmt->get_result();
    
    // Get total posts for pagination
    $count_sql = "SELECT COUNT(*) AS total FROM posts 
                 WHERE status = 'approved' AND category_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $active_category_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_posts = $count_result->fetch_assoc()['total'];
} else {
    // Get all approved posts
    $posts_sql = "SELECT p.*, u.name AS user_name, u.profile_image AS user_image, c.category,
                 (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
                 (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count
                 FROM posts p
                 JOIN users u ON p.user_id = u.user_id
                 JOIN categories c ON p.category_id = c.category_id
                 WHERE p.status = 'approved'
                 ORDER BY p.date_time DESC
                 LIMIT ?, ?";
    
    $stmt = $conn->prepare($posts_sql);
    $stmt->bind_param("ii", $offset, $posts_per_page);
    $stmt->execute();
    $posts_result = $stmt->get_result();
    
    // Get total posts for pagination
    $total_posts = $active_category['post_count'];
}

// Calculate pagination
$total_pages = ceil($total_posts / $posts_per_page);

// Post card function (if not already defined in functions.php)
if (!function_exists('typoria_post_card')) {
    function typoria_post_card($post, $size = 'medium') {
        $post_id = $post['post_id'];
        $title = htmlspecialchars($post['title']);
        $excerpt = $post['excerpt'] ?: create_excerpt($post['details']);
        $image = $post['image'] ?: 'default-post.jpg';
        $date = format_date($post['date_time']);
        $read_time = $post['read_time'] ?: '1';
        $category = htmlspecialchars($post['category']);
        $category_id = $post['category_id'];
        $author = htmlspecialchars($post['user_name']);
        $author_id = $post['user_id'];
        $author_image = $post['user_image'] ?: 'default.png';
        $likes = $post['like_count'] ?? 0;
        $comments = $post['comment_count'] ?? 0;
        $views = $post['views'] ?? 0;
        
        // Card classes based on size
        $card_classes = 'bg-white rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:-translate-y-1 hover:shadow-lg';
        $image_classes = 'w-full object-cover';
        $title_classes = 'font-semibold text-gray-900 hover:text-typoria-primary transition-colors line-clamp-2';
        
        if ($size === 'small') {
            $image_height = 'h-40';
            $title_size = 'text-base';
            $show_excerpt = false;
        } elseif ($size === 'large') {
            $image_height = 'h-64';
            $title_size = 'text-2xl';
            $show_excerpt = true;
        } else { // medium is default
            $image_height = 'h-48';
            $title_size = 'text-xl';
            $show_excerpt = true;
        }
        
        // Generate card HTML
        echo '<div class="' . $card_classes . '">';
        
        // Card image
        echo '<a href="post_view.php?post_id=' . $post_id . '">';
        echo '<img class="' . $image_classes . ' ' . $image_height . '" src="uploads/' . $image . '" alt="' . $title . '">';
        echo '</a>';
        
        // Card content
        echo '<div class="p-5">';
        
        // Category badge
        echo '<a href="categories.php?id=' . $category_id . '" class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full mb-2 hover:bg-gray-200 transition-colors">' . $category . '</a>';
        
        // Title
        echo '<a href="post_view.php?post_id=' . $post_id . '" class="block mt-1">';
        echo '<h2 class="' . $title_classes . ' ' . $title_size . '">' . $title . '</h2>';
        echo '</a>';
        
        // Excerpt for medium and large cards
        if ($show_excerpt) {
            echo '<p class="mt-2 text-gray-600 text-sm line-clamp-2">' . $excerpt . '</p>';
        }
        
        // Card footer
        echo '<div class="mt-4 flex items-center justify-between">';
        
        // Author info
        echo '<div class="flex items-center">';
        echo '<a href="search.php?q=' . urlencode($author) . '" class="flex items-center">';
        echo '<img class="h-8 w-8 rounded-full object-cover" src="uploads/' . $author_image . '" alt="' . $author . '">';
        echo '<div class="ml-2">';
        echo '<span class="text-sm font-medium text-gray-900 hover:underline">' . $author . '</span>';
        echo '<p class="text-xs text-gray-500">' . $date . ' • ' . $read_time . ' min read</p>';
        echo '</div>';
        echo '</a>';
        echo '</div>';
        
        // Stats
        echo '<div class="flex items-center space-x-3 text-gray-500">';
        
        // Views
        echo '<span class="flex items-center text-xs">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />';
        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
        echo '</svg>';
        echo number_format($views);
        echo '</span>';
        
        // Likes
        echo '<span class="flex items-center text-xs">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />';
        echo '</svg>';
        echo number_format($likes);
        echo '</span>';
        
        // Comments
        echo '<span class="flex items-center text-xs">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />';
        echo '</svg>';
        echo number_format($comments);
        echo '</span>';
        
        echo '</div>'; // End stats
        echo '</div>'; // End card footer
        echo '</div>'; // End card content
        echo '</div>'; // End card
    }
}

// Pagination function (if not already defined in functions.php)
if (!function_exists('typoria_pagination')) {
    function typoria_pagination($current_page, $total_pages, $base_url) {
        if ($total_pages <= 1) return;
        
        echo '<div class="mt-8 flex justify-center">';
        echo '<nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">';
        
        // Previous page button
        if ($current_page > 1) {
            echo '<a href="' . $base_url . '&page=' . ($current_page - 1) . '" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">';
            echo '<span class="sr-only">Previous</span>';
            echo '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">';
            echo '<path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />';
            echo '</svg>';
            echo '</a>';
        } else {
            echo '<span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">';
            echo '<span class="sr-only">Previous</span>';
            echo '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">';
            echo '<path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />';
            echo '</svg>';
            echo '</span>';
        }
        
        // Page numbers
        $range = 2; // Number of pages to show before and after current page
        
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)) {
                if ($i == $current_page) {
                    echo '<span aria-current="page" class="z-10 bg-typoria-primary text-white relative inline-flex items-center px-4 py-2 border border-typoria-primary text-sm font-medium">';
                    echo $i;
                    echo '</span>';
                } else {
                    echo '<a href="' . $base_url . '&page=' . $i . '" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">';
                    echo $i;
                    echo '</a>';
                }
            } elseif (($i == $current_page - $range - 1) || ($i == $current_page + $range + 1)) {
                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">';
                echo '...';
                echo '</span>';
            }
        }
        
        // Next page button
        if ($current_page < $total_pages) {
            echo '<a href="' . $base_url . '&page=' . ($current_page + 1) . '" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">';
            echo '<span class="sr-only">Next</span>';
            echo '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">';
            echo '<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />';
            echo '</svg>';
            echo '</a>';
        } else {
            echo '<span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">';
            echo '<span class="sr-only">Next</span>';
            echo '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">';
            echo '<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />';
            echo '</svg>';
            echo '</span>';
        }
        
        echo '</nav>';
        echo '</div>';
    }
}

// Page title
$page_title = $active_category_id > 0 ? htmlspecialchars($active_category['category']) : "All Categories";

// Generate HTML header
typoria_header($page_title, "
    .category-card {
        transition: all 0.3s ease;
    }
    
    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .category-card.active {
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
    }
    
    .category-count {
        transition: all 0.3s ease;
    }
    
    .category-card:hover .category-count,
    .category-card.active .category-count {
        background-color: #3B82F6;
        color: white;
    }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .gradient-text {
        background: linear-gradient(135deg, #3B82F6, #8B5CF6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-fill-color: transparent;
    }
");
?>

<!-- Start of body content -->
<?php include 'navbar.php'; ?>

<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-2 font-serif">
            <?php if ($active_category_id > 0): ?>
                <span class="gradient-text"><?php echo htmlspecialchars($active_category['category']); ?></span> Category
            <?php else: ?>
                Explore Categories
            <?php endif; ?>
        </h1>
        <p class="text-gray-600">
            <?php if ($active_category_id > 0): ?>
                Discover <?php echo number_format($active_category['post_count']); ?> posts in this category
            <?php else: ?>
                Browse all categories and find interesting content
            <?php endif; ?>
        </p>
    </div>
    
    <!-- Categories List -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-12">
        <!-- All Categories Option -->
        <a href="categories.php" class="category-card border rounded-lg p-4 text-center bg-white <?php echo $active_category_id === 0 ? 'active' : ''; ?>">
            <div class="text-gray-600 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h3 class="font-medium text-gray-900">All Categories</h3>
            <span class="category-count inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full mt-2">
                <?php echo number_format($active_category['post_count']); ?>
            </span>
        </a>
        
        <!-- Individual Categories -->
        <?php
        $categories_result->data_seek(0); // Reset result pointer
        while ($category = $categories_result->fetch_assoc()): 
        ?>
            <a href="categories.php?id=<?php echo $category['category_id']; ?>" 
               class="category-card border rounded-lg p-4 text-center bg-white <?php echo $active_category_id === $category['category_id'] ? 'active' : ''; ?>">
                <div class="text-gray-600 mb-2">
                    <?php 
                    // Icon based on category name (customize as needed)
                    $icon = 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10';
                    
                    switch (strtolower($category['category'])) {
                        case 'technology':
                            $icon = 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z';
                            break;
                        case 'lifestyle':
                            $icon = 'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
                            break;
                        case 'travel':
                            $icon = 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
                            break;
                        case 'food':
                            $icon = 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
                            break;
                        case 'health':
                            $icon = 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z';
                            break;
                        case 'business':
                            $icon = 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z';
                            break;
                        case 'education':
                            $icon = 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253';
                            break;
                        case 'entertainment':
                            $icon = 'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z';
                            break;
                        case 'science':
                            $icon = 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z';
                            break;
                        case 'arts':
                            $icon = 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z';
                            break;
                    }
                    ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $icon; ?>" />
                    </svg>
                </div>
                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($category['category']); ?></h3>
                <span class="category-count inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full mt-2">
                    <?php echo number_format($category['post_count']); ?>
                </span>
            </a>
        <?php endwhile; ?>
    </div>
    
    <!-- Posts Section -->
    <div class="mt-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 font-serif">
            <?php if ($active_category_id > 0): ?>
                Latest in <?php echo htmlspecialchars($active_category['category']); ?>
            <?php else: ?>
                Latest Posts
            <?php endif; ?>
        </h2>
        
        <?php if ($posts_result->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($post = $posts_result->fetch_assoc()): ?>
                    <?php typoria_post_card($post, 'medium'); ?>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php 
                // Build pagination URL
                $pagination_url = "categories.php?";
                if ($active_category_id > 0) {
                    $pagination_url .= "id=" . $active_category_id;
                } else {
                    $pagination_url .= "id=0";
                }
                
                typoria_pagination($page, $total_pages, $pagination_url);
                ?>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- No Posts Found -->
            <div class="text-center py-16 bg-gray-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                
                <h3 class="text-xl font-bold text-gray-800 mb-2">No posts found in this category</h3>
                <p class="text-gray-600 max-w-md mx-auto mb-6">
                    There are currently no published posts in this category. Check back later or explore other categories.
                </p>
                
                <a href="categories.php" class="bg-typoria-primary hover:bg-typoria-secondary text-white px-6 py-3 rounded-lg font-medium inline-block transition-colors">
                    Explore All Categories
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// Generate footer
typoria_footer(); 
?>