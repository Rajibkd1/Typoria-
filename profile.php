<?php
/**
 * Typoria Blog Platform
 * User Profile Page
 */

// Include required files
require_once 'includes/functions.php';
require_once 'includes/theme.php';

// Get authentication details
$auth = check_auth();
$isLoggedIn = $auth['isLoggedIn'];
$current_user_id = $auth['user_id'];

// Initialize database connection
$conn = get_db_connection();

// Determine which user's profile to show
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    // Viewing another user's profile
    $user_id = intval($_GET['id']);
    $viewing_own_profile = $isLoggedIn && ($user_id == $current_user_id);
} else if ($isLoggedIn) {
    // Viewing own profile
    $user_id = $current_user_id;
    $viewing_own_profile = true;
} else {
    // Not logged in and no user specified, redirect to login
    header("Location: login.php");
    exit();
}

// Fetch user details
$user_sql = "SELECT u.*, 
            (SELECT COUNT(*) FROM posts WHERE user_id = u.user_id AND status = 'approved') AS post_count,
            (SELECT COUNT(*) FROM followers WHERE followed_user_id = u.user_id) AS follower_count,
            (SELECT COUNT(*) FROM followers WHERE follower_user_id = u.user_id) AS following_count
            FROM users u 
            WHERE u.user_id = ?";

$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found
    header("Location: index.php");
    exit();
}

$user = $result->fetch_assoc();

// Check if current user follows this user
$is_following = false;
if ($isLoggedIn && $user_id != $current_user_id) {
    $follow_check_sql = "SELECT follower_id FROM followers 
                        WHERE follower_user_id = ? AND followed_user_id = ?";
    $stmt = $conn->prepare($follow_check_sql);
    $stmt->bind_param("ii", $current_user_id, $user_id);
    $stmt->execute();
    $follow_result = $stmt->get_result();
    $is_following = ($follow_result->num_rows > 0);
}

// Handle follow/unfollow actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
    if (isset($_POST['follow']) && $user_id != $current_user_id) {
        // Check if already following
        if (!$is_following) {
            // Add follow relationship
            $follow_sql = "INSERT INTO followers (follower_user_id, followed_user_id, created_at) 
                          VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($follow_sql);
            $stmt->bind_param("ii", $current_user_id, $user_id);
            $stmt->execute();
            
            // Create notification
            $notification_message = $auth['username'] . " started following you";
            create_notification($user_id, 'follow', $current_user_id, $current_user_id, $notification_message);
            
            // Update follow status
            $is_following = true;
            $user['follower_count']++;
        }
    } 
    else if (isset($_POST['unfollow']) && $user_id != $current_user_id) {
        // Remove follow relationship if exists
        if ($is_following) {
            $unfollow_sql = "DELETE FROM followers 
                            WHERE follower_user_id = ? AND followed_user_id = ?";
            $stmt = $conn->prepare($unfollow_sql);
            $stmt->bind_param("ii", $current_user_id, $user_id);
            $stmt->execute();
            
            // Update follow status
            $is_following = false;
            $user['follower_count']--;
        }
    }
    
    // Handle profile updates (if viewing own profile)
    else if (isset($_POST['update_profile']) && $viewing_own_profile) {
        $name = trim($_POST['name']);
        $bio = trim($_POST['bio']);
        $website = trim($_POST['website']);
        $twitter = trim($_POST['twitter']);
        $facebook = trim($_POST['facebook']);
        $instagram = trim($_POST['instagram']);
        
        // Validate inputs
        if (empty($name)) {
            $error_message = "Name cannot be empty";
        } else {
            // Update profile information
            $update_sql = "UPDATE users SET 
                          name = ?, 
                          bio = ?, 
                          website = ?, 
                          social_twitter = ?, 
                          social_facebook = ?, 
                          social_instagram = ? 
                          WHERE user_id = ?";
                          
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssssi", $name, $bio, $website, $twitter, $facebook, $instagram, $user_id);
            $stmt->execute();
            
            // Update profile image if provided
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
                $profile_image = upload_image($_FILES['profile_image'], 'uploads/profiles/', 'profile_');
                
                if ($profile_image) {
                    $image_sql = "UPDATE users SET profile_image = ? WHERE user_id = ?";
                    $stmt = $conn->prepare($image_sql);
                    $stmt->bind_param("si", $profile_image, $user_id);
                    $stmt->execute();
                }
            }
            
            // Set success message
            $success_message = "Profile updated successfully";
            
            // Refresh user data
            $stmt = $conn->prepare($user_sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        }
    }
}

// Fetch user's posts with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$posts_per_page = 6;
$offset = ($page - 1) * $posts_per_page;

$posts_sql = "SELECT p.*, c.category, 
              (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
              (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count
              FROM posts p
              JOIN categories c ON p.category_id = c.category_id
              WHERE p.user_id = ? AND p.status = 'approved'
              ORDER BY p.date_time DESC
              LIMIT ?, ?";

$stmt = $conn->prepare($posts_sql);
$stmt->bind_param("iii", $user_id, $offset, $posts_per_page);
$stmt->execute();
$posts_result = $stmt->get_result();

// Get total post count for pagination
$count_sql = "SELECT COUNT(*) AS total FROM posts 
              WHERE user_id = ? AND status = 'approved'";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_posts = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Generate HTML header
typoria_header($user['name'] . "'s Profile", "
    .profile-header {
        position: relative;
        background: linear-gradient(135deg, #3B82F6, #8B5CF6);
        color: white;
    }
    
    .profile-header::after {
        content: '';
        position: absolute;
        bottom: -2rem;
        left: 0;
        right: 0;
        height: 4rem;
        background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(255,255,255,1));
        pointer-events: none;
    }
    
    .profile-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        text-align: center;
        gap: 1rem;
    }
    
    .tab-button {
        padding: 0.75rem 1.5rem;
        border-bottom: 2px solid transparent;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .tab-button.active {
        border-bottom-color: #3B82F6;
        color: #3B82F6;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
");
?>

<!-- Start of body content -->
<?php include 'navbar.php'; ?>

<!-- Profile Header -->
<div class="profile-header py-12 px-4">
    <div class="container mx-auto max-w-6xl">
        <div class="flex flex-col md:flex-row items-center md:items-start gap-8">
            <!-- Profile Image -->
            <div class="h-32 w-32 rounded-full bg-gradient-to-r from-white/30 to-white/10 flex items-center justify-center text-white text-5xl font-bold overflow-hidden">
                <?php if (!empty($user['profile_image']) && $user['profile_image'] != 'default.png'): ?>
                    <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            
            <!-- Profile Info -->
            <div class="text-center md:text-left flex-1">
                <h1 class="text-4xl font-bold mb-2"><?php echo htmlspecialchars($user['name']); ?></h1>
                
                <?php if (!empty($user['bio'])): ?>
                    <p class="text-white/90 mb-4 max-w-3xl"><?php echo htmlspecialchars($user['bio']); ?></p>
                <?php endif; ?>
                
                <div class="flex flex-wrap gap-4 items-center justify-center md:justify-start mb-6">
                    <?php if (!empty($user['website'])): ?>
                        <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" rel="noopener noreferrer" class="flex items-center text-white/90 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
                            </svg>
                            Website
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['social_twitter'])): ?>
                        <a href="<?php echo htmlspecialchars($user['social_twitter']); ?>" target="_blank" rel="noopener noreferrer" class="flex items-center text-white/90 hover:text-white">
                            <svg class="h-5 w-5 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                            </svg>
                            Twitter
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['social_facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($user['social_facebook']); ?>" target="_blank" rel="noopener noreferrer" class="flex items-center text-white/90 hover:text-white">
                            <svg class="h-5 w-5 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                            </svg>
                            Facebook
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['social_instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($user['social_instagram']); ?>" target="_blank" rel="noopener noreferrer" class="flex items-center text-white/90 hover:text-white">
                            <svg class="h-5 w-5 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" />
                            </svg>
                            Instagram
                        </a>
                    <?php endif; ?>
                    
                    <span class="text-white/90">
                        Joined <?php echo date('F Y', strtotime($user['join_date'])); ?>
                    </span>
                </div>
                
                <!-- Profile Stats -->
                <div class="profile-stats max-w-md mx-auto md:mx-0">
                    <div>
                        <div class="text-2xl font-bold"><?php echo number_format($user['post_count']); ?></div>
                        <div class="text-white/80">Posts</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold"><?php echo number_format($user['follower_count']); ?></div>
                        <div class="text-white/80">Followers</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold"><?php echo number_format($user['following_count']); ?></div>
                        <div class="text-white/80">Following</div>
                    </div>
                </div>
            </div>
            
            <!-- Action Button (Follow/Edit Profile) -->
            <div class="mt-4 md:mt-0">
                <?php if ($viewing_own_profile): ?>
                    <button type="button" onclick="showEditProfile()" class="bg-white text-typoria-primary hover:bg-gray-100 px-6 py-3 rounded-lg font-bold transition-colors">
                        Edit Profile
                    </button>
                <?php elseif ($isLoggedIn): ?>
                    <?php if ($is_following): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="unfollow" value="1">
                            <button type="submit" class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-lg font-bold transition-colors">
                                Following
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="follow" value="1">
                            <button type="submit" class="bg-white text-typoria-primary hover:bg-gray-100 px-6 py-3 rounded-lg font-bold transition-colors">
                                Follow
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="bg-white text-typoria-primary hover:bg-gray-100 px-6 py-3 rounded-lg font-bold transition-colors inline-block">
                        Follow
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto max-w-6xl px-4 py-8">
    <?php if (isset($success_message)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
            <p><?php echo $success_message; ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            <p><?php echo $error_message; ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Profile Tabs -->
    <div class="border-b border-gray-200 mb-8">
        <div class="flex flex-wrap -mb-px">
            <button type="button" onclick="showTab('posts')" class="tab-button active" id="tab-button-posts">
                Posts
            </button>
            <?php if ($viewing_own_profile): ?>
            <button type="button" onclick="showTab('bookmarks')" class="tab-button" id="tab-button-bookmarks">
                Bookmarks
            </button>
            <?php endif; ?>
            <button type="button" onclick="showTab('about')" class="tab-button" id="tab-button-about">
                About
            </button>
        </div>
    </div>
    
    <!-- Posts Tab Content -->
    <div class="tab-content active" id="tab-content-posts">
        <?php if ($posts_result->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($post = $posts_result->fetch_assoc()): ?>
                    <?php
                    // Add the user name to post data for the post card
                    $post['user_name'] = $user['name'];
                    typoria_post_card($post, 'medium');
                    ?>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php typoria_pagination($page, $total_pages, "profile.php?id={$user_id}"); ?>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-16 bg-gray-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1M19 20a2 2 0 002-2V8a2 2 0 00-2-2h-5M5 12h14" />
                </svg>
                
                <h3 class="text-xl font-bold text-gray-800 mb-2">No posts yet</h3>
                <p class="text-gray-600 max-w-md mx-auto mb-6">
                    <?php echo $viewing_own_profile ? 
                        "You haven't created any posts yet. Start writing and sharing your thoughts with the world!" : 
                        htmlspecialchars($user['name']) . " hasn't created any posts yet."; ?>
                </p>
                
                <?php if ($viewing_own_profile): ?>
                    <a href="create_post.php" class="gradient-button text-white px-6 py-3 rounded-lg font-medium inline-block">
                        Create Your First Post
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($viewing_own_profile): ?>
    <!-- Bookmarks Tab Content -->
    <div class="tab-content" id="tab-content-bookmarks">
        <?php
        // Fetch bookmarked posts
        $bookmarks_sql = "SELECT p.*, u.name AS user_name, c.category, 
                         (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
                         (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count
                         FROM bookmarks b
                         JOIN posts p ON b.post_id = p.post_id
                         JOIN users u ON p.user_id = u.user_id
                         JOIN categories c ON p.category_id = c.category_id
                         WHERE b.user_id = ? AND p.status = 'approved'
                         ORDER BY b.created_at DESC
                         LIMIT 9";
        
        $stmt = $conn->prepare($bookmarks_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $bookmarks_result = $stmt->get_result();
        ?>
        
        <?php if ($bookmarks_result->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($post = $bookmarks_result->fetch_assoc()): ?>
                    <?php typoria_post_card($post, 'medium'); ?>
                <?php endwhile; ?>
            </div>
            
            <?php if ($bookmarks_result->num_rows >= 9): ?>
                <div class="text-center mt-8">
                    <a href="bookmarks.php" class="text-typoria-primary hover:text-typoria-secondary font-medium">View All Bookmarks →</a>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-16 bg-gray-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                
                <h3 class="text-xl font-bold text-gray-800 mb-2">No bookmarks yet</h3>
                <p class="text-gray-600 max-w-md mx-auto mb-6">
                    You haven't bookmarked any posts yet. Browse posts and save the ones you want to read later.
                </p>
                
                <a href="index.php" class="gradient-button text-white px-6 py-3 rounded-lg font-medium inline-block">
                    Explore Posts
                </a>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- About Tab Content -->
    <div class="tab-content" id="tab-content-about">
        <div class="bg-white p-8 rounded-xl shadow-md">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">About <?php echo $viewing_own_profile ? "Me" : htmlspecialchars($user['name']); ?></h2>
            
            <?php if (!empty($user['bio'])): ?>
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Biography</h3>
                    <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Contact Information</h3>
                    <ul class="space-y-3">
                        <?php if (!empty($user['email'])): ?>
                            <li class="flex items-center text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-typoria-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['website'])): ?>
                            <li class="flex items-center text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-typoria-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
                                </svg>
                                <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" rel="noopener noreferrer" class="text-typoria-primary hover:underline">
                                    <?php echo htmlspecialchars($user['website']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Social Media</h3>
                    <ul class="space-y-3">
                        <?php if (!empty($user['social_twitter'])): ?>
                            <li class="flex items-center text-gray-600">
                                <svg class="h-5 w-5 mr-2 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                                </svg>
                                <a href="<?php echo htmlspecialchars($user['social_twitter']); ?>" target="_blank" rel="noopener noreferrer" class="text-typoria-primary hover:underline">
                                    Twitter
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['social_facebook'])): ?>
                            <li class="flex items-center text-gray-600">
                                <svg class="h-5 w-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                                </svg>
                                <a href="<?php echo htmlspecialchars($user['social_facebook']); ?>" target="_blank" rel="noopener noreferrer" class="text-typoria-primary hover:underline">
                                    Facebook
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['social_instagram'])): ?>
                            <li class="flex items-center text-gray-600">
                                <svg class="h-5 w-5 mr-2 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" />
                                </svg>
                                <a href="<?php echo htmlspecialchars($user['social_instagram']); ?>" target="_blank" rel="noopener noreferrer" class="text-typoria-primary hover:underline">
                                    Instagram
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Account Information</h3>
                <ul class="space-y-3">
                    <li class="flex items-center text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-typoria-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Joined on <?php echo date('F j, Y', strtotime($user['join_date'])); ?></span>
                    </li>
                    <li class="flex items-center text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-typoria-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span><?php echo number_format($user['post_count']); ?> posts published</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php if ($viewing_own_profile): ?>
    <!-- Edit Profile Modal (Hidden by Default) -->
    <div id="edit-profile-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl p-8 max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Edit Profile</h2>
                <button type="button" onclick="hideEditProfile()" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                
                <!-- Profile Image Upload -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Profile Image</label>
                    <div class="flex items-center">
                        <div class="h-20 w-20 rounded-full bg-gradient-to-r from-typoria-primary to-typoria-secondary flex items-center justify-center text-white text-3xl font-bold overflow-hidden mr-4">
                            <?php if (!empty($user['profile_image']) && $user['profile_image'] != 'default.png'): ?>
                                <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="w-full h-full object-cover" id="profile-image-preview">
                            <?php else: ?>
                                <span id="profile-initial"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                                <img src="" alt="Profile" class="w-full h-full object-cover hidden" id="profile-image-preview">
                            <?php endif; ?>
                        </div>
                        <div>
                            <input type="file" id="profile-image" name="profile_image" accept="image/*" class="hidden" onchange="previewProfileImage(this)">
                            <button type="button" onclick="document.getElementById('profile-image').click()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded transition-colors mb-2">
                                Choose Image
                            </button>
                            <p class="text-xs text-gray-500">Recommended: Square image, at least 200x200px</p>
                        </div>
                    </div>
                </div>
                
                <!-- Name -->
                <div class="mb-6">
                    <label for="name" class="block text-gray-700 font-medium mb-2">Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-typoria-primary focus:border-transparent transition-all">
                </div>
                
                <!-- Bio -->
                <div class="mb-6">
                    <label for="bio" class="block text-gray-700 font-medium mb-2">Bio</label>
                    <textarea id="bio" name="bio" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-typoria-primary focus:border-transparent transition-all"
                              placeholder="Tell us a bit about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>
                
                <!-- Website -->
                <div class="mb-6">
                    <label for="website" class="block text-gray-700 font-medium mb-2">Website</label>
                    <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-typoria-primary focus:border-transparent transition-all"
                           placeholder="https://yourwebsite.com">
                </div>
                
                <!-- Social Media -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Social Media</h3>
                    
                    <div class="mb-4">
                        <label for="twitter" class="block text-gray-700 font-medium mb-2">Twitter</label>
                        <input type="url" id="twitter" name="twitter" value="<?php echo htmlspecialchars($user['social_twitter'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-typoria-primary focus:border-transparent transition-all"
                               placeholder="https://twitter.com/yourusername">
                    </div>
                    
                    <div class="mb-4">
                        <label for="facebook" class="block text-gray-700 font-medium mb-2">Facebook</label>
                        <input type="url" id="facebook" name="facebook" value="<?php echo htmlspecialchars($user['social_facebook'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-typoria-primary focus:border-transparent transition-all"
                               placeholder="https://facebook.com/yourusername">
                    </div>
                    
                    <div>
                        <label for="instagram" class="block text-gray-700 font-medium mb-2">Instagram</label>
                        <input type="url" id="instagram" name="instagram" value="<?php echo htmlspecialchars($user['social_instagram'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-typoria-primary focus:border-transparent transition-all"
                               placeholder="https://instagram.com/yourusername">
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="flex justify-end mt-8">
                    <button type="button" onclick="hideEditProfile()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-medium transition-colors mr-4">
                        Cancel
                    </button>
                    <button type="submit" class="gradient-button text-white px-6 py-3 rounded-lg font-medium">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- JavaScript for Tabs and Modal -->
<script>
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Show the selected tab content
    document.getElementById('tab-content-' + tabName).classList.add('active');
    
    // Update active state for tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    document.getElementById('tab-button-' + tabName).classList.add('active');
}

<?php if ($viewing_own_profile): ?>
function showEditProfile() {
    document.getElementById('edit-profile-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
}

function hideEditProfile() {
    document.getElementById('edit-profile-modal').classList.add('hidden');
    document.body.style.overflow = 'auto'; // Restore scrolling
}

function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('profile-image-preview');
            const initial = document.getElementById('profile-initial');
            
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            
            if (initial) {
                initial.classList.add('hidden');
            }
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
<?php endif; ?>
</script>

<?php 
// Generate footer
typoria_footer(); 
?>