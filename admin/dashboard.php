<?php
/**
 * Typoria Blog Platform
 * Admin Dashboard
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

// Get statistics for dashboard
// Total posts
$total_posts_sql = "SELECT COUNT(*) as count FROM posts";
$total_posts_result = $conn->query($total_posts_sql);
$total_posts = $total_posts_result->fetch_assoc()['count'];

// Posts by status
$posts_by_status_sql = "SELECT status, COUNT(*) as count FROM posts GROUP BY status";
$posts_by_status_result = $conn->query($posts_by_status_sql);
$posts_by_status = [];
while ($row = $posts_by_status_result->fetch_assoc()) {
    $posts_by_status[$row['status']] = $row['count'];
}

// Total users
$total_users_sql = "SELECT COUNT(*) as count FROM users";
$total_users_result = $conn->query($total_users_sql);
$total_users = $total_users_result->fetch_assoc()['count'];

// Total comments
$total_comments_sql = "SELECT COUNT(*) as count FROM comments";
$total_comments_result = $conn->query($total_comments_sql);
$total_comments = $total_comments_result->fetch_assoc()['count'];

// Recent activity
$recent_activity_sql = "
(SELECT 'post' as type, p.post_id as id, p.title as title, u.name as user, p.date_time as date 
 FROM posts p JOIN users u ON p.user_id = u.user_id ORDER BY p.date_time DESC LIMIT 5)
UNION ALL
(SELECT 'comment' as type, c.comment_id as id, SUBSTRING(c.comment, 1, 50) as title, u.name as user, c.created_at as date
 FROM comments c JOIN users u ON c.user_id = u.user_id ORDER BY c.created_at DESC LIMIT 5)
ORDER BY date DESC LIMIT 10";
$recent_activity_result = $conn->query($recent_activity_sql);

// Pending posts
$pending_posts_sql = "SELECT p.*, u.name as user_name, c.category
                     FROM posts p 
                     JOIN users u ON p.user_id = u.user_id
                     JOIN categories c ON p.category_id = c.category_id
                     WHERE p.status = 'pending'
                     ORDER BY p.date_time DESC
                     LIMIT 5";
$pending_posts_result = $conn->query($pending_posts_sql);

// Additional CSS for the page
$additional_css = "
    .stat-card {
        position: relative;
        overflow: hidden;
    }
    
    .stat-card .icon {
        position: absolute;
        bottom: -15px;
        right: -15px;
        font-size: 70px;
        opacity: 0.1;
    }
    
    .stat-card-1 {
        background: linear-gradient(135deg, ".$TYPORIA_COLORS['primary'].", #4c8dff);
    }
    
    .stat-card-2 {
        background: linear-gradient(135deg, ".$TYPORIA_COLORS['secondary'].", #ad7dff);
    }
    
    .stat-card-3 {
        background: linear-gradient(135deg, ".$TYPORIA_COLORS['accent'].", #4ce0c0);
    }
    
    .stat-card-4 {
        background: linear-gradient(135deg, ".$TYPORIA_COLORS['warning'].", #ffb74d);
    }
    
    /* Admin layout */
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
";

// Additional JavaScript for the page
$additional_js = "
<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
";

// Page title
$page_title = "Admin Dashboard";

// Generate HTML header
typoria_header($page_title, $additional_css, $additional_js);
?>

<div class="admin-layout">
    <!-- Admin Sidebar -->
    <?php include 'admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="bg-gray-100 p-6">
        <div class="container mx-auto">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
                <div class="flex items-center mt-4 md:mt-0">
                    <span class="text-sm text-gray-600 mr-4">Last updated: <?php echo date('F j, Y, g:i a'); ?></span>
                    <button type="button" onclick="window.location.reload()" class="bg-white hover:bg-gray-50 text-gray-700 px-3 py-1 rounded-md text-sm border border-gray-300 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Posts -->
                <div class="stat-card stat-card-1 rounded-lg shadow-md text-white p-6">
                    <div class="flex flex-col">
                        <span class="text-sm uppercase font-light opacity-80">Total Posts</span>
                        <span class="text-3xl font-bold mt-1"><?php echo number_format($total_posts); ?></span>
                    </div>
                    <div class="flex justify-between items-center mt-4">
                        <a href="manage_posts.php" class="text-white text-xs hover:underline flex items-center">
                            <span>View All</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <span class="text-xs opacity-80"><?php echo isset($posts_by_status['pending']) ? $posts_by_status['pending'] : 0; ?> pending</span>
                    </div>
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
                
                <!-- Total Users -->
                <div class="stat-card stat-card-2 rounded-lg shadow-md text-white p-6">
                    <div class="flex flex-col">
                        <span class="text-sm uppercase font-light opacity-80">Total Users</span>
                        <span class="text-3xl font-bold mt-1"><?php echo number_format($total_users); ?></span>
                    </div>
                    <div class="flex justify-between items-center mt-4">
                        <a href="manage_users.php" class="text-white text-xs hover:underline flex items-center">
                            <span>View All</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <span class="text-xs opacity-80">Active</span>
                    </div>
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
                
                <!-- Comments -->
                <div class="stat-card stat-card-3 rounded-lg shadow-md text-white p-6">
                    <div class="flex flex-col">
                        <span class="text-sm uppercase font-light opacity-80">Total Comments</span>
                        <span class="text-3xl font-bold mt-1"><?php echo number_format($total_comments); ?></span>
                    </div>
                    <div class="flex justify-between items-center mt-4">
                        <a href="#" class="text-white text-xs hover:underline flex items-center">
                            <span>View All</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <span class="text-xs opacity-80">All time</span>
                    </div>
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                </div>
                
                <!-- Post Status Distribution -->
                <div class="stat-card stat-card-4 rounded-lg shadow-md text-white p-6">
                    <div class="flex flex-col">
                        <span class="text-sm uppercase font-light opacity-80">Pending Posts</span>
                        <span class="text-3xl font-bold mt-1"><?php echo isset($posts_by_status['pending']) ? number_format($posts_by_status['pending']) : 0; ?></span>
                    </div>
                    <div class="flex justify-between items-center mt-4">
                        <a href="manage_posts.php?filter=pending" class="text-white text-xs hover:underline flex items-center">
                            <span>Review All</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <span class="text-xs opacity-80">Need review</span>
                    </div>
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Post Status Chart -->
                <div class="bg-white rounded-lg shadow-md p-6 col-span-1">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Post Status Distribution</h2>
                    <div>
                        <canvas id="postStatusChart" width="100%" height="220"></canvas>
                    </div>
                </div>
                
                <!-- Center Column - Recent Activity -->
                <div class="bg-white rounded-lg shadow-md p-6 col-span-1">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Recent Activity</h2>
                    <div class="space-y-4">
                        <?php if ($recent_activity_result->num_rows > 0) : ?>
                            <?php while ($activity = $recent_activity_result->fetch_assoc()) : ?>
                                <div class="flex items-start">
                                    <?php if ($activity['type'] == 'post') : ?>
                                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                    <?php else : ?>
                                        <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="ml-3 flex-1">
                                        <div class="flex justify-between items-start">
                                            <p class="text-sm font-medium text-gray-800 truncate">
                                                <?php if ($activity['type'] == 'post') : ?>
                                                    New post: <a href="../post_view.php?post_id=<?php echo $activity['id']; ?>" class="hover:text-typoria-primary"><?php echo htmlspecialchars($activity['title']); ?></a>
                                                <?php else : ?>
                                                    New comment: <?php echo htmlspecialchars($activity['title']); ?>...
                                                <?php endif; ?>
                                            </p>
                                            <span class="text-xs text-gray-500"><?php echo format_date($activity['date']); ?></span>
                                        </div>
                                        <p class="text-xs text-gray-500">by <?php echo htmlspecialchars($activity['user']); ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <p class="text-gray-500 text-sm">No recent activity found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column - Pending Posts -->
                <div class="bg-white rounded-lg shadow-md p-6 col-span-1">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-bold text-gray-800">Pending Posts</h2>
                        <a href="manage_posts.php?filter=pending" class="text-sm text-typoria-primary hover:underline">View All</a>
                    </div>
                    <div class="space-y-4">
                        <?php if ($pending_posts_result->num_rows > 0) : ?>
                            <?php while ($post = $pending_posts_result->fetch_assoc()) : ?>
                                <div class="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-800 truncate w-48">
                                                <a href="../post_view.php?post_id=<?php echo $post['post_id']; ?>" class="hover:text-typoria-primary"><?php echo htmlspecialchars($post['title']); ?></a>
                                            </h3>
                                            <div class="flex items-center mt-1">
                                                <span class="text-xs text-gray-500">By <?php echo htmlspecialchars($post['user_name']); ?></span>
                                                <span class="mx-2 text-gray-300">•</span>
                                                <span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full"><?php echo htmlspecialchars($post['category']); ?></span>
                                            </div>
                                        </div>
                                        <div class="flex space-x-1">
                                            <a href="post_review.php?post_id=<?php echo $post['post_id']; ?>&action=approve" class="text-green-500 hover:text-green-700" title="Approve">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                            <a href="post_review.php?post_id=<?php echo $post['post_id']; ?>&action=reject" class="text-red-500 hover:text-red-700" title="Reject">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo format_date($post['date_time']); ?></p>
                                </div>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <p class="text-gray-500 text-sm">No pending posts found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Action Card -->
            <div class="bg-gradient-to-r from-typoria-primary to-typoria-secondary rounded-lg shadow-md p-6 mt-6 text-white">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Ready to manage your blog?</h2>
                        <p class="mt-2 text-white/80">Configure settings, manage posts and users, or create new content.</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-3">
                        <a href="settings.php" class="bg-white text-typoria-primary hover:bg-gray-100 px-4 py-2 rounded-md font-medium transition-colors">
                            Settings
                        </a>
                        <a href="../create_post.php" class="bg-typoria-accent text-white hover:bg-typoria-accent/80 px-4 py-2 rounded-md font-medium transition-colors">
                            Create Post
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // Post Status Chart
    const postStatusData = {
        labels: [
            <?php
            $statuses = array_keys($posts_by_status);
            foreach ($statuses as $status) {
                echo "'" . ucfirst($status) . "', ";
            }
            ?>
        ],
        datasets: [{
            data: [
                <?php
                foreach ($posts_by_status as $count) {
                    echo $count . ", ";
                }
                ?>
            ],
            backgroundColor: [
                '<?php echo $TYPORIA_COLORS['primary']; ?>',
                '<?php echo $TYPORIA_COLORS['secondary']; ?>',
                '<?php echo $TYPORIA_COLORS['accent']; ?>',
                '<?php echo $TYPORIA_COLORS['warning']; ?>',
                '<?php echo $TYPORIA_COLORS['error']; ?>'
            ],
            borderColor: 'white',
            borderWidth: 2
        }]
    };

    const ctxPostStatus = document.getElementById('postStatusChart').getContext('2d');
    const postStatusChart = new Chart(ctxPostStatus, {
        type: 'doughnut',
        data: postStatusData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>

<?php
// Do not include the standard footer for admin pages
?>
</body>
</html>