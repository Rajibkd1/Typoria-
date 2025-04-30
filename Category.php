<?php
/**
 * Typoria Blog Platform
 * Category Page
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

// Check if category_id is provided
if (!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    // Redirect to home page if no category_id is provided
    header("Location: index.php");
    exit();
}

$category_id = intval($_GET['category_id']);

// Get category details
$category_sql = "SELECT * FROM categories WHERE category_id = ?";
$stmt = $conn->prepare($category_sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$category_result = $stmt->get_result();

if ($category_result->num_rows === 0) {
    // Category not found, redirect to home page
    header("Location: index.php");
    exit();
}

$category = $category_result->fetch_assoc();

// Get sorting option
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$posts_per_page = 9;
$offset = ($page - 1) * $posts_per_page;

// Determine sort order
$order_by = "";
switch ($sort_by) {
    case 'popular':
        $order_by = "p.views DESC";
        break;
    case 'comments':
        $order_by = "comment_count DESC";
        break;
    case 'likes':
        $order_by = "like_count DESC";
        break;
    case 'oldest':
        $order_by = "p.date_time ASC";
        break;
    case 'latest':
    default:
        $order_by = "p.date_time DESC";
        break;
}

// Get posts in this category
$posts_sql = "SELECT p.*, u.name AS user_name, c.category,
             (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
             (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count
             FROM posts p
             JOIN users u ON p.user_id = u.user_id
             JOIN categories c ON p.category_id = c.category_id
             WHERE p.category_id = ? AND p.status = 'approved'
             ORDER BY $order_by
             LIMIT ?, ?";

$stmt = $conn->prepare($posts_sql);
$stmt->bind_param("iii", $category_id, $offset, $posts_per_page);
$stmt->execute();
$posts_result = $stmt->get_result();

// Get total posts count for pagination
$count_sql = "SELECT COUNT(*) AS total FROM posts WHERE category_id = ? AND status = 'approved'";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_posts = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Get related categories (other categories)
$related_categories_sql = "SELECT * FROM categories WHERE category_id != ? ORDER BY category LIMIT 5";
$stmt = $conn->prepare($related_categories_sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$related_categories_result = $stmt->get_result();

// Generate HTML header
typoria_header("Category: " . $category['category'], "
    .category-header {
        background: linear-gradient(135deg, #3B82F6, #8B5CF6);
        padding: 3rem 0;
        position: relative;
        color: white;
    }
    
    .category-header::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 2rem;
        background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(248,250,252,1));
        pointer-events: none;
    }
");
?>

<!-- Start of body content -->
<?php include 'navbar.php'; ?>

<!-- Category Header -->
<div class="category-header">
    <div class="container mx-auto px-4 relative z-10">
        <h1 class="text-4xl font-bold mb-2 font-serif"><?php echo htmlspecialchars($category['category']); ?></h1>
        <p class="text-xl text-white/90">
            <?php echo number_format($total_posts); ?> 
            <?php echo $total_posts == 1 ? 'post' : 'posts'; ?> in this category
        </p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Sorting Options -->
    <div class="mb-8 flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-800">
            All Posts in <?php echo htmlspecialchars($category['category']); ?>
        </h2>
        
        <div class="flex items-center">
            <label for="sort" class="text-gray-600 mr-2">Sort by:</label>
            <select id="sort" class="px-3 py-2 border border-gray-300 rounded-lg appearance-none bg-white pr-8 bg-no-repeat bg-right"
                    style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3e%3cpolyline points=\'6 9 12 15 18 9\'%3e%3c/polyline%3e%3c/svg%3e'); background-size: 15px;"
                    onchange="window.location.href = 'category.php?category_id=<?php echo $category_id; ?>&sort=' + this.value">
                <option value="latest" <?php echo $sort_by == 'latest' ? 'selected' : ''; ?>>Latest</option>
                <option value="oldest" <?php echo $sort_by == 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                <option value="popular" <?php echo $sort_by == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                <option value="likes" <?php echo $sort_by == 'likes' ? 'selected' : ''; ?>>Most Liked</option>
                <option value="comments" <?php echo $sort_by == 'comments' ? 'selected' : ''; ?>>Most Commented</option>
            </select>
        </div>
    </div>
    
    <!-- Posts Grid -->
    <?php if ($posts_result->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            <?php while ($post = $posts_result->fetch_assoc()): ?>
                <?php typoria_post_card($post, 'medium'); ?>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <?php 
            // Build pagination URL
            $pagination_url = "category.php?category_id=" . $category_id;
            if ($sort_by != 'latest') $pagination_url .= "&sort=" . $sort_by;
            
            typoria_pagination($page, $total_pages, $pagination_url);
            ?>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- No Posts -->
        <div class="text-center py-16 bg-gray-50 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1M19 20a2 2 0 002-2V8a2 2 0 00-2-2h-5M5 12h14" />
            </svg>
            
            <h3 class="text-xl font-bold text-gray-800 mb-2">No posts in this category</h3>
            <p class="text-gray-600 max-w-md mx-auto mb-6">
                There are no published posts in the <?php echo htmlspecialchars($category['category']); ?> category yet.
            </p>
            
            <?php if ($isLoggedIn): ?>
                <a href="create_post.php?category_id=<?php echo $category_id; ?>" class="gradient-button text-white px-6 py-3 rounded-lg font-medium inline-block">
                    Create the First Post
                </a>
            <?php else: ?>
                <a href="index.php" class="gradient-button text-white px-6 py-3 rounded-lg font-medium inline-block">
                    Back to Home
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Related Categories -->
    <div class="mt-12">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Explore Other Categories</h3>
        
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <?php while ($related_category = $related_categories_result->fetch_assoc()): ?>
                <?php 
                // Get post count for this category
                $cat_count_sql = "SELECT COUNT(*) AS total FROM posts WHERE category_id = ? AND status = 'approved'";
                $stmt = $conn->prepare($cat_count_sql);
                $stmt->bind_param("i", $related_category['category_id']);
                $stmt->execute();
                $cat_count_result = $stmt->get_result();
                $cat_post_count = $cat_count_result->fetch_assoc()['total'];
                ?>
                
                <a href="category.php?category_id=<?php echo $related_category['category_id']; ?>" 
                   class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow text-center">
                    <h4 class="font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($related_category['category']); ?></h4>
                    <p class="text-sm text-gray-500">
                        <?php echo number_format($cat_post_count); ?> 
                        <?php echo $cat_post_count == 1 ? 'post' : 'posts'; ?>
                    </p>
                </a>
            <?php endwhile; ?>
            
            <a href="categories.php" class="bg-gray-100 p-4 rounded-lg hover:bg-gray-200 transition-colors text-center flex items-center justify-center">
                <span class="text-gray-600 font-medium">View All Categories</span>
            </a>
        </div>
    </div>
</div>

<?php 
// Generate footer
typoria_footer(); 
?>