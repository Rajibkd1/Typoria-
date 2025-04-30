<?php
/**
 * Typoria Blog Platform
 * Notifications Page
 */

// Include required files
require_once 'includes/functions.php';
require_once 'includes/theme.php';

// Require user to be logged in
$auth = require_login();
$user_id = $auth['user_id'];
$username = $auth['username'];

// Initialize database connection
$conn = get_db_connection();

// Handle marking notifications as read
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    
    // Verify the notification belongs to the current user
    $verify_sql = "SELECT notification_id FROM notifications WHERE notification_id = ? AND user_id = ?";
    $stmt = $conn->prepare($verify_sql);
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update notification status to read
        $update_sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();
        
        typoria_flash_message("Notification marked as read", "success");
    }
    
    // Redirect to avoid form resubmission
    header("Location: notifications.php");
    exit();
}

// Handle marking all notifications as read
if (isset($_POST['mark_all_read'])) {
    $update_sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    typoria_flash_message("All notifications marked as read", "success");
    
    // Redirect to avoid form resubmission
    header("Location: notifications.php");
    exit();
}

// Handle notification deletion
if (isset($_POST['delete']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    
    // Verify the notification belongs to the current user
    $verify_sql = "SELECT notification_id FROM notifications WHERE notification_id = ? AND user_id = ?";
    $stmt = $conn->prepare($verify_sql);
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Delete the notification
        $delete_sql = "DELETE FROM notifications WHERE notification_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();
        
        typoria_flash_message("Notification deleted", "success");
    }
    
    // Redirect to avoid form resubmission
    header("Location: notifications.php");
    exit();
}

// Handle clearing all notifications
if (isset($_POST['clear_all'])) {
    $delete_sql = "DELETE FROM notifications WHERE user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    typoria_flash_message("All notifications cleared", "success");
    
    // Redirect to avoid form resubmission
    header("Location: notifications.php");
    exit();
}

// Get notifications for the current user with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count total notifications for pagination
$count_sql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_notifications = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_notifications / $per_page);

// Fetch notifications with user info using correct column names
$notifications_sql = "
    SELECT n.*, 
           u.name as from_user_name,
           u.profile_image as from_user_image,
           p.title as post_title
    FROM notifications n
    LEFT JOIN users u ON n.from_user_id = u.user_id
    LEFT JOIN posts p ON n.related_id = p.post_id AND n.type IN ('like', 'comment', 'mention')
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($notifications_sql);
$stmt->bind_param("iii", $user_id, $per_page, $offset);
$stmt->execute();
$notifications_result = $stmt->get_result();

// Count unread notifications
$unread_sql = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_result = $stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread'];

// Custom CSS for notifications page
$additional_css = "
    .notification-item {
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }
    
    .notification-item:hover {
        background-color: #f9fafb;
    }
    
    .notification-item.unread {
        border-left-color: #8b5cf6;
        background-color: rgba(139, 92, 246, 0.05);
    }
    
    .notification-item.unread:hover {
        background-color: rgba(139, 92, 246, 0.1);
    }
    
    .notification-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #8b5cf6;
    }
    
    .notification-avatar {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        overflow: hidden;
        background: linear-gradient(135deg, #8b5cf6, #6366f1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        flex-shrink: 0;
    }
    
    .notification-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .notification-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        background-color: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .icon-like {
        color: #ef4444;
    }
    
    .icon-comment {
        color: #3b82f6;
    }
    
    .icon-follow {
        color: #10b981;
    }
    
    .icon-mention {
        color: #f59e0b;
    }
    
    .icon-system {
        color: #6b7280;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 2rem;
    }
    
    .pagination-item {
        padding: 0.5rem 1rem;
        margin: 0 0.25rem;
        border-radius: 0.375rem;
        background-color: #f3f4f6;
        color: #374151;
        transition: all 0.2s ease;
    }
    
    .pagination-item:hover {
        background-color: #e5e7eb;
    }
    
    .pagination-item.active {
        background-color: #8b5cf6;
        color: white;
    }
    
    .pagination-item.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .empty-state {
        padding: 3rem;
        text-align: center;
    }
    
    .empty-state-icon {
        width: 4rem;
        height: 4rem;
        margin: 0 auto 1.5rem;
        color: #9ca3af;
    }
";

// Page title
$page_title = "Notifications";

// Generate HTML header
typoria_header($page_title, $additional_css);
?>

<?php include 'navbar.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header Section -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Your Notifications</h1>
            
            <div class="flex space-x-3">
                <?php if ($total_notifications > 0): ?>
                    <form method="POST" class="inline-block">
                        <input type="hidden" name="mark_all_read" value="1">
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-typoria-primary">
                            Mark All as Read
                        </button>
                    </form>
                    
                    <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to clear all notifications?');">
                        <input type="hidden" name="clear_all" value="1">
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-gray-300 rounded-md hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Clear All
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Info Bar -->
        <div class="bg-gray-100 rounded-lg p-4 mb-6 flex justify-between items-center">
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                </svg>
                <span class="text-gray-700">
                    You have <span class="font-bold text-typoria-primary"><?php echo $unread_count; ?></span> unread notification<?php echo $unread_count !== 1 ? 's' : ''; ?>
                </span>
            </div>
            
            <div class="text-sm text-gray-500">
                <?php echo $total_notifications; ?> total notification<?php echo $total_notifications !== 1 ? 's' : ''; ?>
            </div>
        </div>
        
        <?php 
        // Display flash messages if the function exists
        if (function_exists('typoria_display_flash_messages')) {
            echo typoria_display_flash_messages(); 
        }
        ?>
        
        <!-- Notifications List -->
        <div class="bg-white shadow-sm rounded-lg divide-y divide-gray-200">
            <?php if ($notifications_result->num_rows > 0): ?>
                <?php while ($notification = $notifications_result->fetch_assoc()): ?>
                    <?php
                    // Determine notification icon and link
                    $icon_class = 'icon-system';
                    $icon_svg = '<path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />';
                    $notification_link = '#';
                    
                    switch ($notification['type']) {
                        case 'like':
                            $icon_class = 'icon-like';
                            $icon_svg = '<path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />';
                            $notification_link = 'post_view.php?post_id=' . $notification['related_id'];
                            break;
                        case 'comment':
                            $icon_class = 'icon-comment';
                            $icon_svg = '<path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd" />';
                            $notification_link = 'post_view.php?post_id=' . $notification['related_id'];
                            break;
                        case 'follow':
                            $icon_class = 'icon-follow';
                            $icon_svg = '<path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />';
                            $notification_link = 'profile.php?id=' . $notification['from_user_id'];
                            break;
                        case 'mention':
                            $icon_class = 'icon-mention';
                            $icon_svg = '<path fill-rule="evenodd" d="M14.243 5.757a6 6 0 10-.986 9.284 1 1 0 111.087 1.678A8 8 0 1118 10a3 3 0 01-4.8 2.401A4 4 0 1114 10a1 1 0 102 0c0-1.537-.586-3.07-1.757-4.243zM12 10a2 2 0 10-4 0 2 2 0 004 0z" clip-rule="evenodd" />';
                            $notification_link = 'post_view.php?post_id=' . $notification['related_id'];
                            break;
                    }
                    
                    // Format the time
                    $time_ago = time_elapsed_string($notification['created_at']);
                    
                    // Get first letter of user's name for default avatar
                    $user_initial = strtoupper(substr($notification['from_user_name'] ?? 'T', 0, 1));
                    ?>
                    
                    <div class="notification-item p-4 <?php echo $notification['is_read'] ? '' : 'unread'; ?> flex">
                        <!-- Notification Icon or User Avatar -->
                        <div class="mr-4 flex-shrink-0">
                            <?php if ($notification['from_user_id']): ?>
                                <div class="notification-avatar">
                                    <?php if (!empty($notification['from_user_image']) && $notification['from_user_image'] != 'default.png'): ?>
                                        <img src="uploads/profiles/<?php echo htmlspecialchars($notification['from_user_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($notification['from_user_name']); ?>">
                                    <?php else: ?>
                                        <?php echo $user_initial; ?>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="notification-icon <?php echo $icon_class; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <?php echo $icon_svg; ?>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Notification Content -->
                        <div class="flex-1 min-w-0">
                            <!-- Notification Text and Time -->
                            <div class="mb-1 flex justify-between items-start">
                                <div class="text-sm font-medium text-gray-900">
                                    <a href="<?php echo $notification_link; ?>" class="hover:underline">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </a>
                                    <?php if (!$notification['is_read']): ?>
                                        <span class="inline-block ml-2 notification-dot"></span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs text-gray-500 whitespace-nowrap ml-4"><?php echo $time_ago; ?></span>
                            </div>
                            
                            <!-- Reference Preview (e.g., post title) -->
                            <?php if (!empty($notification['post_title'])): ?>
                                <div class="text-sm text-gray-500 truncate">
                                    on: "<?php echo htmlspecialchars($notification['post_title']); ?>"
                                </div>
                            <?php endif; ?>
                            
                            <!-- Actions -->
                            <div class="mt-2 flex space-x-2">
                                <?php if (!$notification['is_read']): ?>
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                        <input type="hidden" name="mark_read" value="1">
                                        <button type="submit" class="text-xs text-typoria-primary hover:text-typoria-secondary">
                                            Mark as read
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                    <input type="hidden" name="delete" value="1">
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </form>
                                
                                <a href="<?php echo $notification_link; ?>" class="text-xs text-blue-600 hover:text-blue-800">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900">No notifications yet</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        When you get notifications, they'll appear here.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination mt-6">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="pagination-item">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                <?php else: ?>
                    <span class="pagination-item disabled">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </span>
                <?php endif; ?>
                
                <?php
                // Show at most 5 page links
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $start_page + 4);
                
                if ($end_page - $start_page < 4) {
                    $start_page = max(1, $end_page - 4);
                }
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <a href="?page=<?php echo $i; ?>" class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="pagination-item">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                <?php else: ?>
                    <span class="pagination-item disabled">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Helper function to format time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Calculate weeks
    $weeks = floor($diff->days / 7);
    $days_remaining = $diff->days % 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    $values = array(
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days_remaining,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    );
    
    $result = array();
    
    foreach ($string as $k => $v) {
        if ($values[$k]) {
            $result[$k] = $values[$k] . ' ' . $v . ($values[$k] > 1 ? 's' : '');
        }
    }

    if (!$full) $result = array_slice($result, 0, 1);
    return $result ? implode(', ', $result) . ' ago' : 'just now';
}

// Generate footer
typoria_footer();
?>