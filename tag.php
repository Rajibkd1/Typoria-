<?php
/**
 * Typoria Blog Platform
 * Tag Page
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

// Check if tag_id is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect to home page if no tag_id is provided
    header("Location: index.php");
    exit();
}

$tag_id = intval($_GET['id']);

// Get tag details
$tag_sql = "SELECT * FROM tags WHERE tag_id = ?";
$stmt = $conn->prepare($tag_sql);
$stmt->bind_param("i", $tag_id);
$stmt->execute();
$tag_result = $stmt->get_result();

if ($tag_result->num_rows === 0) {
    // Tag not found, redirect to home page
    header("Location: index.php");
    exit();
}

$tag = $tag_result->fetch_assoc();

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

// Get posts with this tag
$posts_sql = "SELECT p.*, u.name AS user_name, c.category,
             (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
             (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count
             FROM posts p
             JOIN users u ON p.user_id = u.user_id
             JOIN categories c ON p.category_id = c.category_id
             JOIN post_tags pt ON p.post_id = pt.post_id
             WHERE pt.tag_id = ? AND p.status = 'approved'
             ORDER BY $order_by
             LIMIT ?, ?";

$stmt = $conn->prepare($posts_sql);
$stmt->bind_param("iii", $tag_id, $offset, $posts_per_page);
$stmt->execute();
$posts_result = $stmt->get_result();

// Get total posts count for pagination
$count_sql = "SELECT COUNT(*) AS total 
              FROM posts p 
              JOIN post_tags pt ON p.post_id = pt.post_id
              WHERE pt.tag_id = ? AND p.status = 'approved'";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $tag_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_posts = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Get related tags (other popular tags)
$related_tags_sql = "SELECT t.*, COUNT(pt.post_id) AS post_count
                    FROM tags t
                    JOIN post_tags pt ON t.tag_id = pt.tag_id
                    JOIN posts p ON pt.post_id = p.post_id
                    WHERE p.status = 'approved' AND t.tag_id != ?
                    GROUP BY t.tag_id
                    ORDER BY post_count DESC
                    LIMIT 10";
$stmt = $conn->prepare($related_tags_sql);
$stmt->bind_param("i", $tag_id);
$stmt->execute();
$related_tags_result = $stmt->get_result();

// Generate HTML header
typoria_header("Tag: " . $tag['tag_name'], "
    .tag-header {
        background: linear-gradient(135deg, #10B981, #3B82F6);
        padding: 3rem 0;
        position: relative;
        color: white;
    }
    
    .tag-header::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 2rem;
        background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(248,250,252,1));
        pointer-events: none;
    }
    
    .tag-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .tag-badge:hover {
        transform: translateY(-2px);
    }
");
?>

<!-- Start of body content -->
<?php include 'navbar.php'; ?>

<!-- Tag Header -->
<div class="tag-header">
    <div class="container mx-auto px-4 relative z-10">
        <div class="inline-block bg-white/20 px-3 py-1 rounded-full text-white/90 text-sm mb-2">Tag</div>
        <h1 class="text-4xl font-bold mb-2 font-serif">#<?php echo htmlspecialchars($tag['tag_name']); ?></h1>
        <p class="text-xl text-white/90">
            <?php echo number_format($total_posts); ?> 
            <?php echo $total_posts == 1 ? 'post' : 'posts'; ?> with this tag
        </p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Sorting Options -->
    <div class="mb-8 flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-800">
            All Posts tagged with #<?php echo htmlspecialchars($tag['tag_name']); ?>
        </h2>
        
        <div class="flex items-center">
            <label for="sort" class="text-gray-600 mr-2">Sort by:</label>
            <select id="sort" class="px-3 py-2 border border-gray-300 rounded-lg appearance-none bg-white pr-8 bg-no-repeat bg-right"
                    style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3e%3cpolyline points=\'6 9 12 15 18 9\'%3e%3c/polyline%3e%3c/svg%3e'); background-size: 15px;"
                    onchange="window.location.href = 'tag.php?id=<?php echo $tag_id; ?>&sort=' + this.value">
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
            $pagination_url = "tag.php?id=" . $tag_id;
            if ($sort_by != 'latest') $pagination_url .= "&sort=" . $sort_by;
            
            typoria_pagination($page, $total_pages, $pagination_url);
            ?>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- No Posts -->
        <div class="text-center py-16 bg-gray-50 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
            </svg>
            
            <h3 class="text-xl font-bold text-gray-800 mb-2">No posts with this tag</h3>
            <p class="text-gray-600 max-w-md mx-auto mb-6">
                There are no published posts tagged with #<?php echo htmlspecialchars($tag['tag_name']); ?> yet.
            </p>
            
            <?php if ($isLoggedIn): ?>
                <a href="create_post.php" class="gradient-button text-white px-6 py-3 rounded-lg font-medium inline-block">
                    Create a Post with This Tag
                </a>
            <?php else: ?>
                <a href="index.php" class="gradient-button text-white px-6 py-3 rounded-lg font-medium inline-block">
                    Back to Home
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Related Tags -->
    <?php if ($related_tags_result->num_rows > 0): ?>
        <div class="mt-12">
            <h3 class="text-xl font-bold text-gray-800 mb-6">Explore Related Tags</h3>
            
            <div class="flex flex-wrap gap-3">
                <?php 
                $colors = [
                    'bg-blue-100 text-blue-800 hover:bg-blue-200',
                    'bg-purple-100 text-purple-800 hover:bg-purple-200',
                    'bg-green-100 text-green-800 hover:bg-green-200',
                    'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
                    'bg-red-100 text-red-800 hover:bg-red-200',
                    'bg-indigo-100 text-indigo-800 hover:bg-indigo-200',
                    'bg-pink-100 text-pink-800 hover:bg-pink-200',
                    'bg-gray-100 text-gray-800 hover:bg-gray-200',
                ];
                
                $i = 0;
                while ($related_tag = $related_tags_result->fetch_assoc()): 
                    $color_class = $colors[$i % count($colors)];
                    $i++;
                ?>
                    <a href="tag.php?id=<?php echo $related_tag['tag_id']; ?>" 
                       class="tag-badge <?php echo $color_class; ?>">
                        #<?php echo htmlspecialchars($related_tag['tag_name']); ?>
                        <span class="text-xs opacity-75">(<?php echo $related_tag['post_count']; ?>)</span>
                    </a>
                <?php endwhile; ?>
                
                <a href="tags.php" class="tag-badge bg-gray-200 text-gray-700 hover:bg-gray-300">
                    View All Tags
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
// Generate footer
typoria_footer(); 
?>