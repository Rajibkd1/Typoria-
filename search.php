<?php
/**
 * Typoria Blog Platform
 * Advanced Search Page
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

// Search parameters
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$author = isset($_GET['author']) ? (int)$_GET['author'] : 0;
$search_type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, posts, authors

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Get categories for filter
$categories_sql = "SELECT category_id, category FROM categories ORDER BY category";
$categories_result = $conn->query($categories_sql);

// Get popular tags for filter
$tags_sql = "SELECT tag_name, COUNT(*) as tag_count 
             FROM tags t
             JOIN post_tags pt ON t.tag_id = pt.tag_id
             JOIN posts p ON pt.post_id = p.post_id
             WHERE p.status = 'approved'
             GROUP BY t.tag_id
             ORDER BY tag_count DESC
             LIMIT 20";
$tags_result = $conn->query($tags_sql);

// Prepare base query for posts without LIMIT
$posts_sql_base = "SELECT p.*, u.name AS author_name, u.profile_image AS author_image, 
                c.category, 
                (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count
                FROM posts p
                JOIN users u ON p.user_id = u.user_id
                JOIN categories c ON p.category_id = c.category_id";

// Add tag join if needed
if (!empty($tag)) {
    $posts_sql_base .= " JOIN post_tags pt ON p.post_id = pt.post_id
                         JOIN tags t ON pt.tag_id = t.tag_id";
}

// Add WHERE clause
$posts_sql_base .= " WHERE p.status = 'approved'";

// Prepare base query for authors without LIMIT
$authors_sql_base = "SELECT u.*, 
                  (SELECT COUNT(*) FROM posts WHERE user_id = u.user_id AND status = 'approved') AS post_count,
                  (SELECT COUNT(*) FROM followers WHERE followed_user_id = u.user_id) AS follower_count
                  FROM users u
                  WHERE 1=1";

// Add search conditions based on parameters
$where_conditions_posts = [];
$where_conditions_authors = [];
$params_posts = [];
$params_authors = [];
$param_types_posts = '';
$param_types_authors = '';

if (!empty($keyword)) {
    // Add keyword search for posts
    $where_conditions_posts[] = "(p.title LIKE ? OR p.details LIKE ?)";
    $params_posts[] = "%$keyword%";
    $params_posts[] = "%$keyword%";
    $param_types_posts .= 'ss';
    
    // Add keyword search for authors
    $where_conditions_authors[] = "(u.name LIKE ? OR u.bio LIKE ?)";
    $params_authors[] = "%$keyword%";
    $params_authors[] = "%$keyword%";
    $param_types_authors .= 'ss';
}

if ($category > 0) {
    $where_conditions_posts[] = "p.category_id = ?";
    $params_posts[] = $category;
    $param_types_posts .= 'i';
}

if (!empty($tag)) {
    $where_conditions_posts[] = "t.tag_name = ?";
    $params_posts[] = $tag;
    $param_types_posts .= 's';
}

if ($author > 0) {
    $where_conditions_posts[] = "p.user_id = ?";
    $params_posts[] = $author;
    $param_types_posts .= 'i';
}

// Combine conditions
if (!empty($where_conditions_posts)) {
    $posts_sql_base .= " AND " . implode(" AND ", $where_conditions_posts);
}

if (!empty($where_conditions_authors)) {
    $authors_sql_base .= " AND " . implode(" AND ", $where_conditions_authors);
}

// Create count queries for pagination
$count_posts_sql = "SELECT COUNT(*) as total FROM (" . $posts_sql_base . ") as temp";
$count_authors_sql = "SELECT COUNT(*) as total FROM (" . $authors_sql_base . ") as temp";

// Add order by and LIMIT for posts and authors
$posts_sql = $posts_sql_base . " ORDER BY p.date_time DESC LIMIT ?, ?";
$authors_sql = $authors_sql_base . " ORDER BY post_count DESC LIMIT ?, ?";

// Add pagination parameters - these are always needed
$params_posts_paged = $params_posts;
$params_posts_paged[] = $offset;
$params_posts_paged[] = $items_per_page;
$param_types_posts_paged = $param_types_posts . 'ii';

$params_authors_paged = $params_authors;
$params_authors_paged[] = $offset;
$params_authors_paged[] = $items_per_page;
$param_types_authors_paged = $param_types_authors . 'ii';

// Get post count for pagination
$total_posts = 0;
$total_posts_pages = 1;
$count_posts_stmt = $conn->prepare($count_posts_sql);
if (!empty($params_posts)) {
    $count_posts_stmt->bind_param($param_types_posts, ...$params_posts);
}
$count_posts_stmt->execute();
$total_posts_result = $count_posts_stmt->get_result();
if ($row = $total_posts_result->fetch_assoc()) {
    $total_posts = $row['total'];
    $total_posts_pages = ceil($total_posts / $items_per_page);
}

// Get author count for pagination
$total_authors = 0;
$total_authors_pages = 1;
$count_authors_stmt = $conn->prepare($count_authors_sql);
if (!empty($params_authors)) {
    $count_authors_stmt->bind_param($param_types_authors, ...$params_authors);
}
$count_authors_stmt->execute();
$total_authors_result = $count_authors_stmt->get_result();
if ($row = $total_authors_result->fetch_assoc()) {
    $total_authors = $row['total'];
    $total_authors_pages = ceil($total_authors / $items_per_page);
}

// Execute post search if needed
$posts_result = null;
if ($search_type == 'all' || $search_type == 'posts') {
    $posts_stmt = $conn->prepare($posts_sql);
    $posts_stmt->bind_param($param_types_posts_paged, ...$params_posts_paged);
    $posts_stmt->execute();
    $posts_result = $posts_stmt->get_result();
}

// Execute author search if needed
$authors_result = null;
if ($search_type == 'all' || $search_type == 'authors') {
    $authors_stmt = $conn->prepare($authors_sql);
    $authors_stmt->bind_param($param_types_authors_paged, ...$params_authors_paged);
    $authors_stmt->execute();
    $authors_result = $authors_stmt->get_result();
}

// Custom CSS for search page
$custom_css = "
    /* Search section */
    .search-container {
        max-width: 800px;
        margin: 0 auto 3rem;
    }
    
    .search-form {
        display: flex;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        border-radius: 9999px;
        overflow: hidden;
        background-color: white;
        position: relative;
    }
    
    .search-input {
        flex-grow: 1;
        padding: 1.25rem 1.5rem;
        border: none;
        font-size: 1.1rem;
        outline: none;
        color: #1f2937; /* Add explicit text color */
    }
    
    .search-button {
        padding: 1.25rem 2rem;
        background: linear-gradient(135deg, " . $TYPORIA_COLORS['primary'] . ", " . $TYPORIA_COLORS['secondary'] . ");
        color: white;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
        display: flex;
        align-items: center;
    }
    
    .search-button svg {
        margin-right: 0.5rem;
    }
    
    .search-button:hover {
        box-shadow: inset 0 0 100px rgba(255,255,255,0.2);
    }
    
    /* Filter section */
    .filter-container {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin: 2rem 0;
        align-items: center;
    }
    
    .filter-label {
        font-weight: 600;
        color: #4b5563;
    }
    
    .filter-select {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        outline: none;
        background-color: white;
        cursor: pointer;
        min-width: 150px;
    }
    
    .filter-select:focus {
        border-color: " . $TYPORIA_COLORS['primary'] . ";
        box-shadow: 0 0 0 3px " . $TYPORIA_COLORS['primary'] . "30;
    }
    
    .filter-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        border-radius: 9999px;
        background-color: #f3f4f6;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .filter-tab.active {
        background-color: " . $TYPORIA_COLORS['primary'] . ";
        color: white;
    }
    
    .filter-tab:hover:not(.active) {
        background-color: #e5e7eb;
    }
    
    .filter-tab svg {
        margin-right: 0.5rem;
    }
    
    /* Tag cloud */
    .tag-cloud {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin: 2rem 0;
    }
    
    .tag {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        background-color: #f3f4f6;
        border-radius: 9999px;
        font-size: 0.9rem;
        color: #6b7280;
        transition: all 0.3s ease;
    }
    
    .tag:hover, .tag.active {
        background-color: " . $TYPORIA_COLORS['primary'] . "20;
        color: " . $TYPORIA_COLORS['primary'] . ";
        transform: translateY(-2px);
    }
    
    .tag.active {
        background-color: " . $TYPORIA_COLORS['primary'] . "40;
        font-weight: 600;
    }
    
    .tag-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.5rem;
        height: 1.5rem;
        background-color: white;
        border-radius: 50%;
        font-size: 0.75rem;
        margin-left: 0.5rem;
        color: #6b7280;
    }
    
    /* Search results */
    .search-heading {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f3f4f6;
    }
    
    .search-count {
        color: " . $TYPORIA_COLORS['primary'] . ";
    }
    
    /* Post cards */
    .post-card {
        background-color: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        border: 1px solid #f3f4f6;
    }
    
    .post-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        border-color: " . $TYPORIA_COLORS['primary'] . "30;
    }
    
    .post-image-container {
        position: relative;
        overflow: hidden;
        height: 200px;
    }
    
    .post-image {
        height: 100%;
        width: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .post-card:hover .post-image {
        transform: scale(1.05);
    }
    
    .post-category {
        position: absolute;
        top: 1rem;
        right: 1rem;
        display: inline-block;
        padding: 0.25rem 1rem;
        background-color: " . $TYPORIA_COLORS['primary'] . ";
        color: white;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    
    .post-content {
        padding: 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .post-date {
        font-size: 0.85rem;
        color: #6b7280;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
    }
    
    .post-date svg {
        width: 0.9rem;
        height: 0.9rem;
        margin-right: 0.4rem;
    }
    
    .post-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.75rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        transition: color 0.3s ease;
    }
    
    .post-card:hover .post-title {
        color: " . $TYPORIA_COLORS['primary'] . ";
    }
    
    .post-excerpt {
        color: #6b7280;
        margin-bottom: 1.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex-grow: 1;
    }
    
    .post-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        padding-top: 1rem;
        border-top: 1px solid #f3f4f6;
    }
    
    .post-author {
        display: flex;
        align-items: center;
    }
    
    .post-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 0.75rem;
        border: 2px solid white;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    
    .post-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .post-avatar-fallback {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, " . $TYPORIA_COLORS['primary'] . ", " . $TYPORIA_COLORS['secondary'] . ");
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
        margin-right: 0.75rem;
        border: 2px solid white;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    
    .post-author-name {
        font-weight: 600;
        color: #4b5563;
        transition: color 0.3s ease;
    }
    
    .post-card:hover .post-author-name {
        color: " . $TYPORIA_COLORS['primary'] . ";
    }
    
    .post-stats {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .post-stat {
        display: flex;
        align-items: center;
        font-size: 0.9rem;
        color: #6b7280;
    }
    
    .post-stat svg {
        width: 1.1rem;
        height: 1.1rem;
        margin-right: 0.4rem;
    }
    
    /* Author cards */
    .author-card {
        background-color: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        border: 1px solid #f3f4f6;
        padding: 2rem;
        text-align: center;
    }
    
    .author-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        border-color: " . $TYPORIA_COLORS['primary'] . "30;
    }
    
    .author-image {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto 1.5rem;
        border: 3px solid white;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    
    .author-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .author-initial {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, " . $TYPORIA_COLORS['primary'] . ", " . $TYPORIA_COLORS['secondary'] . ");
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 2.5rem;
        margin: 0 auto 1.5rem;
        border: 3px solid white;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    
    .author-name {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.75rem;
        transition: color 0.3s ease;
    }
    
    .author-card:hover .author-name {
        color: " . $TYPORIA_COLORS['primary'] . ";
    }
    
    .author-bio {
        color: #6b7280;
        margin-bottom: 1.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex-grow: 1;
    }
    
    .author-stats {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-top: 1rem;
    }
    
    .author-stat {
        text-align: center;
    }
    
    .author-stat-number {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
    }
    
    .author-stat-label {
        font-size: 0.85rem;
        color: #6b7280;
    }
    
    .view-profile-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1.5rem;
        margin-top: 1.5rem;
        background: linear-gradient(135deg, " . $TYPORIA_COLORS['primary'] . ", " . $TYPORIA_COLORS['secondary'] . ");
        color: white;
        font-weight: 600;
        border-radius: 9999px;
        transition: all 0.3s ease;
    }
    
    .view-profile-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px " . $TYPORIA_COLORS['primary'] . "40;
    }
    
    /* No results */
    .no-results {
        background-color: white;
        border-radius: 1rem;
        padding: 3rem 2rem;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        margin: 2rem 0;
    }
    
    .no-results svg {
        width: 4rem;
        height: 4rem;
        color: #d1d5db;
        margin: 0 auto 1.5rem;
    }
    
    .no-results-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    
    .no-results-message {
        color: #6b7280;
        max-width: 500px;
        margin: 0 auto;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        margin: 3rem 0;
    }
    
    .pagination-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        margin: 0 0.25rem;
        border-radius: 0.5rem;
        background-color: white;
        color: #4b5563;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .pagination-link:hover:not(.active, .disabled) {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        background-color: #f9fafb;
    }
    
    .pagination-link.active {
        background-color: " . $TYPORIA_COLORS['primary'] . ";
        color: white;
        box-shadow: 0 4px 12px " . $TYPORIA_COLORS['primary'] . "40;
    }
    
    .pagination-link.disabled {
        background-color: #f3f4f6;
        color: #9ca3af;
        cursor: not-allowed;
    }
    
    .pagination-nav {
        width: auto;
        padding: 0 1rem;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .search-form {
            flex-direction: column;
            border-radius: 1rem;
        }
        
        .search-input {
            width: 100%;
            border-radius: 1rem 1rem 0 0;
        }
        
        .search-button {
            width: 100%;
            border-radius: 0 0 1rem 1rem;
            justify-content: center;
        }
        
        .author-card, .post-card {
            padding: 1.5rem;
        }
        
        .author-image, .author-initial {
            width: 80px;
            height: 80px;
            font-size: 2rem;
        }
        
        .author-name {
            font-size: 1.25rem;
        }
        
        .author-stat-number {
            font-size: 1.1rem;
        }
        
        .filter-container {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .filter-select {
            width: 100%;
        }
        
        .post-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .post-stats {
            width: 100%;
            justify-content: space-between;
        }
    }
";

// Generate HTML header
typoria_header("Search", $custom_css);
?>

<!-- Start of body content -->
<?php include 'navbar.php'; ?>

<!-- Search Header -->
<section class="bg-gray-50 py-12">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Search Typoria</h1>
        
        <div class="search-container">
            <form method="GET" action="search.php" class="search-form">
                <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Search for posts, topics, or authors..." class="search-input">
                <input type="hidden" name="type" value="<?php echo $search_type; ?>">
                <?php if ($category > 0) : ?>
                    <input type="hidden" name="category" value="<?php echo $category; ?>">
                <?php endif; ?>
                <?php if (!empty($tag)) : ?>
                    <input type="hidden" name="tag" value="<?php echo htmlspecialchars($tag); ?>">
                <?php endif; ?>
                <?php if ($author > 0) : ?>
                    <input type="hidden" name="author" value="<?php echo $author; ?>">
                <?php endif; ?>
                <button type="submit" class="search-button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Search
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Filters Section -->
<section class="container mx-auto px-4 py-8">
    <!-- Search Type Tabs -->
    <div class="flex justify-center space-x-4 mb-8">
        <a href="<?php echo update_query_string(['type' => 'all', 'page' => 1]); ?>" class="filter-tab <?php echo $search_type == 'all' ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            All Results
        </a>
        <a href="<?php echo update_query_string(['type' => 'posts', 'page' => 1]); ?>" class="filter-tab <?php echo $search_type == 'posts' ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
            Posts
        </a>
        <a href="<?php echo update_query_string(['type' => 'authors', 'page' => 1]); ?>" class="filter-tab <?php echo $search_type == 'authors' ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Authors
        </a>
    </div>

    <!-- Filter Options -->
    <?php if ($search_type == 'all' || $search_type == 'posts') : ?>
        <div class="filter-container">
            <span class="filter-label">Filter by:</span>
            
            <!-- Category Filter -->
            <div>
                <select name="category" id="category-filter" class="filter-select" onchange="window.location.href=this.value">
                    <option value="<?php echo update_query_string(['category' => 0, 'page' => 1]); ?>">All Categories</option>
                    <?php
                    if ($categories_result && $categories_result->num_rows > 0) {
                        $categories_result->data_seek(0);
                        while ($cat = $categories_result->fetch_assoc()) {
                            $selected = ($category == $cat['category_id']) ? 'selected' : '';
                            echo '<option value="' . update_query_string(['category' => $cat['category_id'], 'page' => 1]) . '" ' . $selected . '>' .
                                htmlspecialchars($cat['category']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <!-- Sort Order Filter (can be extended) -->
            <div>
                <select name="sort" id="sort-filter" class="filter-select">
                    <option value="latest">Latest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="popular">Most Popular</option>
                </select>
            </div>
        </div>
    <?php endif; ?>

    <!-- Popular Tags -->
    <?php if (($search_type == 'all' || $search_type == 'posts') && $tags_result && $tags_result->num_rows > 0) : ?>
        <div class="mt-8">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Popular Tags:</h3>
            <div class="tag-cloud">
                <?php
                $tags_result->data_seek(0);
                while ($tag_item = $tags_result->fetch_assoc()) {
                    $active_class = ($tag == $tag_item['tag_name']) ? 'active' : '';
                    echo '<a href="' . update_query_string(['tag' => $tag_item['tag_name'], 'page' => 1]) . '" class="tag ' . $active_class . '">' .
                        htmlspecialchars($tag_item['tag_name']) .
                        '<span class="tag-count">' . $tag_item['tag_count'] . '</span>' .
                        '</a>';
                }
                ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Active Filters -->
    <?php if (!empty($keyword) || $category > 0 || !empty($tag) || $author > 0) : ?>
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold text-gray-700">Active filters:</span>
            
            <?php if (!empty($keyword)) : ?>
                <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm flex items-center">
                    <span>Keyword: <?php echo htmlspecialchars($keyword); ?></span>
                    <a href="<?php echo update_query_string(['keyword' => '', 'page' => 1]); ?>" class="ml-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($category > 0) : ?>
                <?php
                $category_name = 'Category';
                if ($categories_result && $categories_result->num_rows > 0) {
                    $categories_result->data_seek(0);
                    while ($cat = $categories_result->fetch_assoc()) {
                        if ($cat['category_id'] == $category) {
                            $category_name = $cat['category'];
                            break;
                        }
                    }
                }
                ?>
                <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm flex items-center">
                    <span>Category: <?php echo htmlspecialchars($category_name); ?></span>
                    <a href="<?php echo update_query_string(['category' => 0, 'page' => 1]); ?>" class="ml-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($tag)) : ?>
                <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm flex items-center">
                    <span>Tag: <?php echo htmlspecialchars($tag); ?></span>
                    <a href="<?php echo update_query_string(['tag' => '', 'page' => 1]); ?>" class="ml-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($author > 0) : ?>
                <?php
                // Get author name
                $author_name_sql = "SELECT name FROM users WHERE user_id = ?";
                $author_name_stmt = $conn->prepare($author_name_sql);
                $author_name_stmt->bind_param("i", $author);
                $author_name_stmt->execute();
                $author_name_result = $author_name_stmt->get_result();
                $author_name = 'Author';
                
                if ($author_name_result->num_rows > 0) {
                    $author_data = $author_name_result->fetch_assoc();
                    $author_name = $author_data['name'];
                }
                ?>
                <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm flex items-center">
                    <span>Author: <?php echo htmlspecialchars($author_name); ?></span>
                    <a href="<?php echo update_query_string(['author' => 0, 'page' => 1]); ?>" class="ml-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
            
            <a href="search.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium ml-auto">Clear all filters</a>
        </div>
    <?php endif; ?>
</section>

<!-- Search Results: Posts -->
<?php if ($search_type == 'all' || $search_type == 'posts') : ?>
    <section class="container mx-auto px-4 py-8">
        <h2 class="search-heading">
            Posts 
            <?php if (!empty($keyword) || $category > 0 || !empty($tag) || $author > 0) : ?>
                <span class="search-count">(<?php echo $total_posts; ?> results)</span>
            <?php endif; ?>
        </h2>

        <?php if ($posts_result && $posts_result->num_rows > 0) : ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                <?php while ($post = $posts_result->fetch_assoc()) : ?>
                    <?php
                    $post_image = !empty($post['image']) ? 'uploads/' . $post['image'] : 'assets/images/default-post.jpg';
                    $date_formatted = format_date($post['date_time'], false);
                    
                    // Create excerpt
                    $excerpt = strip_tags($post['details']);
                    if (strlen($excerpt) > 150) {
                        $excerpt = substr($excerpt, 0, 150) . '...';
                    }
                    
                    // Format author initial
                    $author_initial = strtoupper(substr($post['author_name'], 0, 1));
                    ?>
                    
                    <a href="post_view.php?post_id=<?php echo $post['post_id']; ?>" class="post-card">
                        <div class="post-image-container">
                            <img src="<?php echo $post_image; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                            <span class="post-category"><?php echo htmlspecialchars($post['category']); ?></span>
                        </div>
                        <div class="post-content">
                            <div class="post-date">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                <?php echo $date_formatted; ?>
                            </div>
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="post-excerpt"><?php echo $excerpt; ?></p>
                            
                            <div class="post-meta">
                                <div class="post-author">
                                    <?php if (!empty($post['author_image']) && $post['author_image'] != 'default.png') : ?>
                                        <div class="post-avatar">
                                            <img src="uploads/profiles/<?php echo htmlspecialchars($post['author_image']); ?>" alt="<?php echo htmlspecialchars($post['author_name']); ?>">
                                        </div>
                                    <?php else : ?>
                                        <div class="post-avatar-fallback"><?php echo $author_initial; ?></div>
                                    <?php endif; ?>
                                    <span class="post-author-name"><?php echo htmlspecialchars($post['author_name']); ?></span>
                                </div>
                                <div class="post-stats">
                                    <div class="post-stat">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        <?php echo $post['like_count']; ?>
                                    </div>
                                    <div class="post-stat">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <?php echo $post['comment_count']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination for Posts -->
            <?php if ($total_posts_pages > 1) : ?>
                <div class="pagination">
                    <?php if ($page > 1) : ?>
                        <a href="<?php echo update_query_string(['page' => $page - 1]); ?>" class="pagination-link pagination-nav">
                            Previous
                        </a>
                    <?php else : ?>
                        <span class="pagination-link pagination-nav disabled">Previous</span>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($start_page + 4, $total_posts_pages);
                    
                    if ($start_page > 1) {
                        echo '<a href="' . update_query_string(['page' => 1]) . '" class="pagination-link">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="pagination-link disabled">...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo '<span class="pagination-link active">' . $i . '</span>';
                        } else {
                            echo '<a href="' . update_query_string(['page' => $i]) . '" class="pagination-link">' . $i . '</a>';
                        }
                    }
                    
                    if ($end_page < $total_posts_pages) {
                        if ($end_page < $total_posts_pages - 1) {
                            echo '<span class="pagination-link disabled">...</span>';
                        }
                        echo '<a href="' . update_query_string(['page' => $total_posts_pages]) . '" class="pagination-link">' . $total_posts_pages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_posts_pages) : ?>
                        <a href="<?php echo update_query_string(['page' => $page + 1]); ?>" class="pagination-link pagination-nav">
                            Next
                        </a>
                    <?php else : ?>
                        <span class="pagination-link pagination-nav disabled">Next</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="no-results">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="no-results-title">No posts found</h3>
                <p class="no-results-message">We couldn't find any posts matching your search criteria. Try adjusting your filters or search for something else.</p>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<!-- Search Results: Authors -->
<?php if ($search_type == 'all' || $search_type == 'authors') : ?>
    <section class="container mx-auto px-4 py-8">
        <h2 class="search-heading">
            Authors 
            <?php if (!empty($keyword)) : ?>
                <span class="search-count">(<?php echo $total_authors; ?> results)</span>
            <?php endif; ?>
        </h2>

        <?php if ($authors_result && $authors_result->num_rows > 0) : ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                <?php while ($author = $authors_result->fetch_assoc()) : ?>
                    <?php
                    // Format author initial
                    $author_initial = strtoupper(substr($author['name'], 0, 1));
                    
                    // Create excerpt for bio
                    $bio_excerpt = '';
                    if (!empty($author['bio'])) {
                        $bio_excerpt = strip_tags($author['bio']);
                        if (strlen($bio_excerpt) > 150) {
                            $bio_excerpt = substr($bio_excerpt, 0, 150) . '...';
                        }
                    }
                    ?>
                    
                    <a href="author.php?id=<?php echo $author['user_id']; ?>" class="author-card">
                        <?php if (!empty($author['profile_image']) && $author['profile_image'] != 'default.png') : ?>
                            <div class="author-image">
                                <img src="uploads/profiles/<?php echo htmlspecialchars($author['profile_image']); ?>" alt="<?php echo htmlspecialchars($author['name']); ?>">
                            </div>
                        <?php else : ?>
                            <div class="author-initial">
                                <?php echo $author_initial; ?>
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="author-name"><?php echo htmlspecialchars($author['name']); ?></h3>
                        
                        <?php if (!empty($bio_excerpt)) : ?>
                            <p class="author-bio"><?php echo $bio_excerpt; ?></p>
                        <?php endif; ?>
                        
                        <div class="author-stats">
                            <div class="author-stat">
                                <div class="author-stat-number"><?php echo $author['post_count']; ?></div>
                                <div class="author-stat-label">Posts</div>
                            </div>
                            <div class="author-stat">
                                <div class="author-stat-number"><?php echo $author['follower_count']; ?></div>
                                <div class="author-stat-label">Followers</div>
                            </div>
                        </div>
                        
                        <div class="view-profile-button">View Profile</div>
                    </a>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination for Authors -->
            <?php if ($total_authors_pages > 1) : ?>
                <div class="pagination">
                    <?php if ($page > 1) : ?>
                        <a href="<?php echo update_query_string(['page' => $page - 1]); ?>" class="pagination-link pagination-nav">
                            Previous
                        </a>
                    <?php else : ?>
                        <span class="pagination-link pagination-nav disabled">Previous</span>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($start_page + 4, $total_authors_pages);
                    
                    if ($start_page > 1) {
                        echo '<a href="' . update_query_string(['page' => 1]) . '" class="pagination-link">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="pagination-link disabled">...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo '<span class="pagination-link active">' . $i . '</span>';
                        } else {
                            echo '<a href="' . update_query_string(['page' => $i]) . '" class="pagination-link">' . $i . '</a>';
                        }
                    }
                    
                    if ($end_page < $total_authors_pages) {
                        if ($end_page < $total_authors_pages - 1) {
                            echo '<span class="pagination-link disabled">...</span>';
                        }
                        echo '<a href="' . update_query_string(['page' => $total_authors_pages]) . '" class="pagination-link">' . $total_authors_pages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_authors_pages) : ?>
                        <a href="<?php echo update_query_string(['page' => $page + 1]); ?>" class="pagination-link pagination-nav">
                            Next
                        </a>
                    <?php else : ?>
                        <span class="pagination-link pagination-nav disabled">Next</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="no-results">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <h3 class="no-results-title">No authors found</h3>
                <p class="no-results-message">We couldn't find any authors matching your search criteria. Try adjusting your search term.</p>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<!-- No Results for Both -->
<?php if ((!$posts_result || $posts_result->num_rows === 0) && 
          (!$authors_result || $authors_result->num_rows === 0) && 
          (!empty($keyword) || $category > 0 || !empty($tag) || $author > 0)) : ?>
    <section class="container mx-auto px-4 py-8">
        <div class="no-results">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <h3 class="no-results-title">No results found</h3>
            <p class="no-results-message">We couldn't find anything matching your search criteria. Try different keywords or filters.</p>
            <a href="search.php" class="view-profile-button mt-6">Clear Search</a>
        </div>
    </section>
<?php endif; ?>

<?php
// Helper function to update query string
function update_query_string($params = [])
{
    $current_params = $_GET;
    $updated_params = array_merge($current_params, $params);
    
    // Remove empty parameters
    foreach ($updated_params as $key => $value) {
        if ($value === '' || $value === 0 && $key !== 'page') {
            unset($updated_params[$key]);
        }
    }
    
    return 'search.php' . (empty($updated_params) ? '' : '?' . http_build_query($updated_params));
}

// Generate footer
typoria_footer();
?>