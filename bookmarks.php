<?php
/**
 * Typoria Blog Platform
 * User Bookmarks Page
 */

// Include required files
require_once 'includes/functions.php';
require_once 'includes/theme.php';

// Check if user is logged in
$auth = require_login();
$current_user_id = $auth['user_id'];
$username = $auth['username'];

// Initialize database connection
$conn = get_db_connection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$bookmarks_per_page = 10;
$offset = ($page - 1) * $bookmarks_per_page;

// Get user's bookmarks with pagination
$bookmarks_sql = "SELECT b.bookmark_id, b.created_at, 
                 p.post_id, p.title, p.image, p.details, p.date_time, p.read_time,
                 u.user_id AS author_id, u.name AS author_name, u.profile_image AS author_image,
                 c.category,
                 (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
                 (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count
                 FROM bookmarks b
                 JOIN posts p ON b.post_id = p.post_id
                 JOIN users u ON p.user_id = u.user_id
                 JOIN categories c ON p.category_id = c.category_id
                 WHERE b.user_id = ?
                 ORDER BY b.created_at DESC
                 LIMIT ?, ?";

$bookmarks_stmt = $conn->prepare($bookmarks_sql);
$bookmarks_stmt->bind_param("iii", $current_user_id, $offset, $bookmarks_per_page);
$bookmarks_stmt->execute();
$bookmarks_result = $bookmarks_stmt->get_result();

// Get total bookmarks count for pagination
$total_sql = "SELECT COUNT(*) as total FROM bookmarks WHERE user_id = ?";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->bind_param("i", $current_user_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_bookmarks = $total_row['total'];
$total_pages = ceil($total_bookmarks / $bookmarks_per_page);

// Custom CSS for the bookmarks page
$custom_css = "
    /* Bookmarks header */
    .bookmarks-header {
        background: linear-gradient(135deg, " . $TYPORIA_COLORS['primary'] . "15, " . $TYPORIA_COLORS['secondary'] . "15);
        padding: 2.5rem 0;
        border-radius: 1rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .bookmarks-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('assets/images/pattern-light.svg');
        background-size: cover;
        opacity: 0.15;
        z-index: 0;
    }
    
    .bookmarks-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 1rem;
        position: relative;
        z-index: 1;
    }
    
    .bookmarks-subtitle {
        color: #6b7280;
        font-size: 1.1rem;
        max-width: 600px;
        position: relative;
        z-index: 1;
    }
    
    /* Bookmark items */
    .bookmark-item {
        background-color: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        border: 1px solid #f3f4f6;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    
    .bookmark-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        border-color: " . $TYPORIA_COLORS['primary'] . "30;
    }
    
    .bookmark-content {
        display: flex;
        position: relative;
    }
    
    .bookmark-image {
        width: 200px;
        min-height: 200px;
        object-fit: cover;
        flex-shrink: 0;
    }
    
    .bookmark-details {
        padding: 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .bookmark-category {
        display: inline-flex;
        align-items: center;
        background-color: " . $TYPORIA_COLORS['primary'] . "15;
        color: " . $TYPORIA_COLORS['primary'] . ";
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        margin-bottom: 0.75rem;
    }
    
    .bookmark-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.75rem;
        transition: color 0.3s ease;
    }
    
    .bookmark-item:hover .bookmark-title {
        color: " . $TYPORIA_COLORS['primary'] . ";
    }
    
    .bookmark-excerpt {
        color: #6b7280;
        margin-bottom: 1rem;
        flex-grow: 1;
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .bookmark-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
    }
    
    .bookmark-author {
        display: flex;
        align-items: center;
    }
    
    .bookmark-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 0.75rem;
        border: 2px solid white;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    
    .bookmark-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .bookmark-avatar-fallback {
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
    
    .bookmark-author-info {
        display: flex;
        flex-direction: column;
    }
    
    .bookmark-author-name {
        font-weight: 600;
        color: #4b5563;
        font-size: 0.95rem;
    }
    
    .bookmark-date {
        color: #9ca3af;
        font-size: 0.8rem;
    }
    
    .bookmark-stats {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .bookmark-stat {
        display: flex;
        align-items: center;
        font-size: 0.9rem;
        color: #6b7280;
    }
    
    .bookmark-stat svg {
        width: 1.1rem;
        height: 1.1rem;
        margin-right: 0.4rem;
    }
    
    .bookmark-actions {
        padding: 0.75rem 1.5rem;
        border-top: 1px solid #f3f4f6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .bookmark-action-button {
        display: inline-flex;
        align-items: center;
        font-size: 0.9rem;
        font-weight: 600;
        color: #6b7280;
        transition: all 0.3s ease;
    }
    
    .bookmark-action-button:hover {
        color: " . $TYPORIA_COLORS['primary'] . ";
    }
    
    .bookmark-action-button svg {
        width: 1.1rem;
        height: 1.1rem;
        margin-right: 0.4rem;
    }
    
    .bookmark-remove-button {
        color: #ef4444;
    }
    
    .bookmark-remove-button:hover {
        color: #dc2626;
    }
    
    /* Empty bookmarks */
    .empty-bookmarks {
        background-color: white;
        border-radius: 1rem;
        padding: 3rem 2rem;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .empty-bookmarks svg {
        width: 4rem;
        height: 4rem;
        color: #d1d5db;
        margin: 0 auto 1.5rem;
    }
    
    .empty-bookmarks-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    
    .empty-bookmarks-message {
        color: #6b7280;
        max-width: 500px;
        margin: 0 auto 1.5rem;
    }
    
    .explore-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, " . $TYPORIA_COLORS['primary'] . ", " . $TYPORIA_COLORS['secondary'] . ");
        color: white;
        font-weight: 600;
        border-radius: 9999px;
        transition: all 0.3s ease;
        margin-top: 1rem;
    }
    
    .explore-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px " . $TYPORIA_COLORS['primary'] . "40;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .bookmark-content {
            flex-direction: column;
        }
        
        .bookmark-image {
            width: 100%;
            height: 200px;
        }
        
        .bookmark-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .bookmark-stats {
            width: 100%;
            justify-content: space-between;
        }
        
        .bookmark-actions {
            flex-wrap: wrap;
            gap: 0.5rem;
        }
    }
";

// Generate HTML header
typoria_header("My Bookmarks", $custom_css);
?>

<!-- Start of body content -->
<?php include 'navbar.php'; ?>

<!-- Bookmarks Header -->
<section class="container mx-auto px-4 py-8">
    <div class="bookmarks-header text-center">
        <h1 class="bookmarks-title">My Bookmarks</h1>
        <p class="bookmarks-subtitle mx-auto">Your collection of saved posts and articles for later reading. Easily manage and access your favorite content.</p>
    </div>
    
    <!-- Bookmarks List -->
    <div class="bookmarks-list">
        <?php if ($bookmarks_result->num_rows > 0) : ?>
            <?php while ($bookmark = $bookmarks_result->fetch_assoc()) : ?>
                <?php
                // Format the data
                $post_image = !empty($bookmark['image']) ? 'uploads/' . $bookmark['image'] : 'assets/images/default-post.jpg';
                $date_formatted = format_date($bookmark['created_at'], false);
                
                // Create excerpt
                $excerpt = strip_tags($bookmark['details']);
                if (strlen($excerpt) > 200) {
                    $excerpt = substr($excerpt, 0, 200) . '...';
                }
                
                // Format author initial
                $author_initial = strtoupper(substr($bookmark['author_name'], 0, 1));
                ?>
                
                <div class="bookmark-item">
                    <div class="bookmark-content">
                        <img src="<?php echo $post_image; ?>" alt="<?php echo htmlspecialchars($bookmark['title']); ?>" class="bookmark-image">
                        
                        <div class="bookmark-details">
                            <span class="bookmark-category"><?php echo htmlspecialchars($bookmark['category']); ?></span>
                            <h3 class="bookmark-title"><?php echo htmlspecialchars($bookmark['title']); ?></h3>
                            <p class="bookmark-excerpt"><?php echo $excerpt; ?></p>
                            
                            <div class="bookmark-meta">
                                <div class="bookmark-author">
                                    <?php if (!empty($bookmark['author_image']) && $bookmark['author_image'] != 'default.png') : ?>
                                        <div class="bookmark-avatar">
                                            <img src="uploads/profiles/<?php echo htmlspecialchars($bookmark['author_image']); ?>" alt="<?php echo htmlspecialchars($bookmark['author_name']); ?>">
                                        </div>
                                    <?php else : ?>
                                        <div class="bookmark-avatar-fallback"><?php echo $author_initial; ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="bookmark-author-info">
                                        <span class="bookmark-author-name"><?php echo htmlspecialchars($bookmark['author_name']); ?></span>
                                        <span class="bookmark-date">Bookmarked <?php echo $date_formatted; ?></span>
                                    </div>
                                </div>
                                
                                <div class="bookmark-stats">
                                    <div class="bookmark-stat">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <?php echo $bookmark['read_time']; ?> min read
                                    </div>
                                    <div class="bookmark-stat">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        <?php echo $bookmark['like_count']; ?>
                                    </div>
                                    <div class="bookmark-stat">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <?php echo $bookmark['comment_count']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bookmark-actions">
                        <a href="post_view.php?post_id=<?php echo $bookmark['post_id']; ?>" class="bookmark-action-button">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Read Post
                        </a>
                        
                        <form method="POST" action="bookmark_post.php" class="inline">
                            <input type="hidden" name="post_id" value="<?php echo $bookmark['post_id']; ?>">
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="bookmark-action-button bookmark-remove-button">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Remove Bookmark
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1) : ?>
                <div class="flex justify-center my-8">
                    <div class="inline-flex rounded-md shadow">
                        <div class="inline-flex">
                            <?php if ($page > 1) : ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">Previous</a>
                            <?php else : ?>
                                <span class="px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-l-md cursor-not-allowed">Previous</span>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($start_page + 4, $total_pages);
                            
                            if ($start_page > 1) {
                                echo '<a href="?page=1" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300 hover:bg-gray-50">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300">...</span>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                if ($i == $page) {
                                    echo '<span class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border-t border-b border-gray-300">' . $i . '</span>';
                                } else {
                                    echo '<a href="?page=' . $i . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300 hover:bg-gray-50">' . $i . '</a>';
                                }
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300">...</span>';
                                }
                                echo '<a href="?page=' . $total_pages . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300 hover:bg-gray-50">' . $total_pages . '</a>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages) : ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">Next</a>
                            <?php else : ?>
                                <span class="px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-r-md cursor-not-allowed">Next</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else : ?>
            <!-- Empty Bookmarks State -->
            <div class="empty-bookmarks">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                <h3 class="empty-bookmarks-title">No bookmarks yet</h3>
                <p class="empty-bookmarks-message">You haven't bookmarked any posts yet. When you find interesting posts, click the bookmark icon to save them here for later reading.</p>
                <a href="index.php" class="explore-button">Explore Posts</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// Generate footer
typoria_footer();
?>