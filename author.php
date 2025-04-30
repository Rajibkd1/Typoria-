<?php
/**
 * Typoria Blog Platform
 * Author Profile Page
 */

// Include required files
require_once 'includes/functions.php';
require_once 'includes/theme.php';

// Check if author ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$author_id = $_GET['id'];

// Get authentication details
$auth = check_auth();
$isLoggedIn = $auth['isLoggedIn'];
$current_user_id = $auth['user_id'];

// Initialize database connection
$conn = get_db_connection();

// Get author details
$author_sql = "SELECT u.*, 
               (SELECT COUNT(*) FROM posts WHERE user_id = u.user_id AND status = 'approved') AS post_count,
               (SELECT COUNT(*) FROM followers WHERE followed_user_id = u.user_id) AS follower_count,
               (SELECT COUNT(*) FROM followers WHERE follower_user_id = u.user_id) AS following_count
               FROM users u
               WHERE u.user_id = ?";

$author_stmt = $conn->prepare($author_sql);
$author_stmt->bind_param("i", $author_id);
$author_stmt->execute();
$author_result = $author_stmt->get_result();

// Check if author exists
if ($author_result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$author = $author_result->fetch_assoc();

// Check if current user is following this author
$is_following = false;
if ($isLoggedIn && $current_user_id != $author_id) {
    $follow_sql = "SELECT follower_id FROM followers WHERE follower_user_id = ? AND followed_user_id = ?";
    $follow_stmt = $conn->prepare($follow_sql);
    $follow_stmt->bind_param("ii", $current_user_id, $author_id);
    $follow_stmt->execute();
    $follow_result = $follow_stmt->get_result();
    $is_following = ($follow_result->num_rows > 0);
}

// Get author's posts
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts_per_page = 9;
$offset = ($page - 1) * $posts_per_page;

$posts_sql = "SELECT p.*, c.category,
              (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
              (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count
              FROM posts p
              JOIN categories c ON p.category_id = c.category_id
              WHERE p.user_id = ? AND p.status = 'approved'
              ORDER BY p.date_time DESC
              LIMIT ?, ?";

$posts_stmt = $conn->prepare($posts_sql);
$posts_stmt->bind_param("iii", $author_id, $offset, $posts_per_page);
$posts_stmt->execute();
$posts_result = $posts_stmt->get_result();

// Get total number of posts for pagination
$total_posts_sql = "SELECT COUNT(*) as total FROM posts WHERE user_id = ? AND status = 'approved'";
$total_posts_stmt = $conn->prepare($total_posts_sql);
$total_posts_stmt->bind_param("i", $author_id);
$total_posts_stmt->execute();
$total_posts_result = $total_posts_stmt->get_result();
$total_posts = $total_posts_result->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Get popular tags used by this author
$tags_sql = "SELECT t.tag_name, COUNT(*) as tag_count
            FROM tags t
            JOIN post_tags pt ON t.tag_id = pt.tag_id
            JOIN posts p ON pt.post_id = p.post_id
            WHERE p.user_id = ? AND p.status = 'approved'
            GROUP BY t.tag_id
            ORDER BY tag_count DESC
            LIMIT 10";

$tags_stmt = $conn->prepare($tags_sql);
$tags_stmt->bind_param("i", $author_id);
$tags_stmt->execute();
$tags_result = $tags_stmt->get_result();

// Custom CSS for author page
$custom_css = "
    /* Author profile section */
    .author-header {
        background: linear-gradient(135deg, " . $TYPORIA_COLORS['primary'] . "20, " . $TYPORIA_COLORS['secondary'] . "20);
        border-radius: 1rem;
        position: relative;
        overflow: hidden;
    }
    
    .author-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('assets/images/pattern-bg.svg');
        background-size: cover;
        opacity: 0.15;
        z-index: 0;
    }
    
    .author-profile-image {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        overflow: hidden;
        border: 4px solid white;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 1;
    }
    
    .author-profile-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .author-profile-initial {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: linear-gradient(135deg, " . $TYPORIA_COLORS['primary'] . ", " . $TYPORIA_COLORS['secondary'] . ");
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 3.5rem;
        border: 4px solid white;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 1;
    }
    
    .author-name {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1f2937;
        margin-top: 1.5rem;
        position: relative;
        z-index: 1;
    }
    
    .author-bio {
        color: #4b5563;
        max-width: 700px;
        margin: 1rem auto 2rem;
        line-height: 1.7;
        position: relative;
        z-index: 1;
    }
    
    .author-stats {
        display: flex;
        justify-content: center;
        gap: 2.5rem;
        position: relative;
        z-index: 1;
    }
    
    .author-stat {
        text-align: center;
    }
    
    .author-stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1f2937;
    }
    
    .author-stat-label {
        color: #6b7280;
        font-size: 0.95rem;
    }
    
    .follow-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 2rem;
        font-weight: 600;
        border-radius: 9999px;
        transition: all 0.3s ease;
        margin-top: 2rem;
        position: relative;
        z-index: 1;
    }
    
    .follow-button.follow {
        background: linear-gradient(135deg, " . $TYPORIA_COLORS['primary'] . ", " . $TYPORIA_COLORS['secondary'] . ");
        color: white;
        box-shadow: 0 8px 20px " . $TYPORIA_COLORS['primary'] . "40;
    }
    
    .follow-button.unfollow {
        background-color: #f3f4f6;
        color: #6b7280;
    }
    
    .follow-button:hover {
        transform: translateY(-3px);
    }
    
    .follow-button.follow:hover {
        box-shadow: 0 12px 25px " . $TYPORIA_COLORS['primary'] . "50;
    }
    
    .follow-button svg {
        margin-right: 0.5rem;
    }
    
    .social-icons {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 2rem;
        position: relative;
        z-index: 1;
    }
    
    .social-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    
    .social-icon:hover {
        transform: translateY(-3px) scale(1.1);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    
    .social-icon.twitter:hover {
        color: #1DA1F2;
    }
    
    .social-icon.facebook:hover {
        color: #4267B2;
    }
    
    .social-icon.instagram:hover {
        color: #E1306C;
    }
    
    .social-icon.globe:hover {
        color: " . $TYPORIA_COLORS['primary'] . ";
    }
    
    /* Author posts */
    .author-section-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f3f4f6;
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
    
    /* Tags */
    .tag-cloud {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
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
    
    .tag:hover {
        background-color: " . $TYPORIA_COLORS['primary'] . "20;
        color: " . $TYPORIA_COLORS['primary'] . ";
        transform: translateY(-2px);
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
    
    /* No posts message */
    .no-posts {
        background-color: white;
        border-radius: 1rem;
        padding: 3rem 2rem;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .no-posts svg {
        width: 4rem;
        height: 4rem;
        color: #d1d5db;
        margin: 0 auto 1.5rem;
    }
    
    .no-posts-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    
    .no-posts-message {
        color: #6b7280;
        max-width: 500px;
        margin: 0 auto;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .author-profile-image,
        .author-profile-initial {
            width: 120px;
            height: 120px;
            font-size: 2.8rem;
        }
        
        .author-name {
            font-size: 2rem;
        }
        
        .author-stats {
            gap: 1.5rem;
        }
        
        .author-stat-number {
            font-size: 1.4rem;
        }
    }
    
    @media (max-width: 640px) {
        .author-profile-image,
        .author-profile-initial {
            width: 100px;
            height: 100px;
            font-size: 2.4rem;
        }
        
        .author-name {
            font-size: 1.75rem;
            margin-top: 1rem;
        }
        
        .author-stats {
            gap: 1rem;
        }
        
        .author-stat-number {
            font-size: 1.2rem;
        }
        
        .social-icons {
            margin-top: 1.5rem;
        }
        
        .social-icon {
            width: 35px;
            height: 35px;
        }
    }
";

// Generate HTML header
typoria_header("Author: " . $author['name'], $custom_css);
?>

<!-- Start of body content -->
<?php include 'navbar.php'; ?>

<!-- Author Profile Header -->
<section class="container mx-auto px-4 py-12">
    <div class="author-header p-8 md:p-12 text-center">
        <?php if (!empty($author['profile_image']) && $author['profile_image'] != 'default.png') : ?>
            <div class="author-profile-image mx-auto">
                <img src="uploads/profiles/<?php echo htmlspecialchars($author['profile_image']); ?>" alt="<?php echo htmlspecialchars($author['name']); ?>">
            </div>
        <?php else : ?>
            <div class="author-profile-initial mx-auto">
                <?php echo strtoupper(substr($author['name'], 0, 1)); ?>
            </div>
        <?php endif; ?>

        <h1 class="author-name"><?php echo htmlspecialchars($author['name']); ?></h1>
        
        <?php if (!empty($author['bio'])) : ?>
            <p class="author-bio"><?php echo htmlspecialchars($author['bio']); ?></p>
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
            <div class="author-stat">
                <div class="author-stat-number"><?php echo $author['following_count']; ?></div>
                <div class="author-stat-label">Following</div>
            </div>
        </div>

        <div class="social-icons">
            <?php if (!empty($author['social_twitter'])) : ?>
                <a href="<?php echo htmlspecialchars($author['social_twitter']); ?>" class="social-icon twitter" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                    </svg>
                </a>
            <?php endif; ?>
            
            <?php if (!empty($author['social_facebook'])) : ?>
                <a href="<?php echo htmlspecialchars($author['social_facebook']); ?>" class="social-icon facebook" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                    </svg>
                </a>
            <?php endif; ?>
            
            <?php if (!empty($author['social_instagram'])) : ?>
                <a href="<?php echo htmlspecialchars($author['social_instagram']); ?>" class="social-icon instagram" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" />
                    </svg>
                </a>
            <?php endif; ?>
            
            <?php if (!empty($author['website'])) : ?>
                <a href="<?php echo htmlspecialchars($author['website']); ?>" class="social-icon globe" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
                    </svg>
                </a>
            <?php endif; ?>
        </div>

        <?php if ($isLoggedIn && $current_user_id != $author_id) : ?>
            <form method="POST" action="follow.php">
                <input type="hidden" name="user_id" value="<?php echo $author_id; ?>">
                <?php if ($is_following) : ?>
                    <input type="hidden" name="action" value="unfollow">
                    <button type="submit" class="follow-button unfollow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
                        </svg>
                        Unfollow
                    </button>
                <?php else : ?>
                    <input type="hidden" name="action" value="follow">
                    <button type="submit" class="follow-button follow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Follow
                    </button>
                <?php endif; ?>
            </form>
        <?php elseif (!$isLoggedIn) : ?>
            <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="follow-button follow">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Follow
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- Author's Posts -->
<section class="container mx-auto px-4 py-8">
    <h2 class="author-section-title">Published Posts</h2>

    <?php if ($posts_result->num_rows > 0) : ?>
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
                ?>
                
                <a href="post_view.php?post_id=<?php echo $post['post_id']; ?>" class="post-card">
                    <div class="post-image-container">
                        <img src="<?php echo $post_image; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                        <span class="post-category"><?php echo htmlspecialchars($post['category']); ?></span>
                    </div>
                    <div class="post-content">
                        <div class="post-date"><?php echo $date_formatted; ?></div>
                        <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p class="post-excerpt"><?php echo $excerpt; ?></p>
                        
                        <div class="post-meta">
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
                            <span class="text-sm text-blue-500">Read more</span>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1) : ?>
            <div class="flex justify-center mt-8">
                <div class="inline-flex rounded-md shadow">
                    <div class="inline-flex">
                        <?php if ($page > 1) : ?>
                            <a href="?id=<?php echo $author_id; ?>&page=<?php echo $page - 1; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">Previous</a>
                        <?php else : ?>
                            <span class="px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-l-md cursor-not-allowed">Previous</span>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                            <?php if ($i == $page) : ?>
                                <span class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-gray-300"><?php echo $i; ?></span>
                            <?php else : ?>
                                <a href="?id=<?php echo $author_id; ?>&page=<?php echo $i; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages) : ?>
                            <a href="?id=<?php echo $author_id; ?>&page=<?php echo $page + 1; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">Next</a>
                        <?php else : ?>
                            <span class="px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-r-md cursor-not-allowed">Next</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <div class="no-posts">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
            <h3 class="no-posts-title">No posts yet</h3>
            <p class="no-posts-message">This author hasn't published any posts yet. Check back later!</p>
        </div>
    <?php endif; ?>
</section>

<!-- Author's Tags -->
<?php if ($tags_result->num_rows > 0) : ?>
<section class="container mx-auto px-4 py-8">
    <h2 class="author-section-title">Frequently Used Tags</h2>
    
    <div class="tag-cloud">
        <?php while ($tag = $tags_result->fetch_assoc()) : ?>
            <a href="search.php?tag=<?php echo urlencode($tag['tag_name']); ?>" class="tag">
                <?php echo htmlspecialchars($tag['tag_name']); ?>
                <span class="tag-count"><?php echo $tag['tag_count']; ?></span>
            </a>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>

<?php
// Generate footer
typoria_footer();
?>