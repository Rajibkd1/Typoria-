<?php
/**
 * Typoria Blog Platform
 * Admin - Manage Users
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

// Handle user actions
$message = '';
$message_type = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $target_user_id = (int)$_GET['id'];
    
    // Don't allow actions on own account
    if ($target_user_id == $user_id && ($action == 'delete' || $action == 'ban')) {
        $message = "You cannot perform this action on your own account.";
        $message_type = "error";
    } else {
        switch ($action) {
            case 'make_admin':
                // Check if user is already an admin
                $check_sql = "SELECT admin_id FROM admin WHERE user_id = ?";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("i", $target_user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $message = "User is already an admin.";
                    $message_type = "error";
                } else {
                    // Get user details
                    $user_sql = "SELECT name, email FROM users WHERE user_id = ?";
                    $stmt = $conn->prepare($user_sql);
                    $stmt->bind_param("i", $target_user_id);
                    $stmt->execute();
                    $user_result = $stmt->get_result();
                    
                    if ($user_row = $user_result->fetch_assoc()) {
                        // Make user an admin
                        $admin_sql = "INSERT INTO admin (name, email, password, user_id) 
                                    VALUES (?, ?, '', ?)";
                        $stmt = $conn->prepare($admin_sql);
                        $stmt->bind_param("ssi", $user_row['name'], $user_row['email'], $target_user_id);
                        
                        if ($stmt->execute()) {
                            $message = "User has been made an admin successfully!";
                            $message_type = "success";
                            
                            // Create notification for the user
                            create_notification(
                                $target_user_id,
                                'system',
                                0,
                                $user_id,
                                "You have been granted admin privileges."
                            );
                        } else {
                            $message = "Error making user an admin: " . $conn->error;
                            $message_type = "error";
                        }
                    } else {
                        $message = "User not found.";
                        $message_type = "error";
                    }
                }
                break;
                
            case 'remove_admin':
                // Check if user is actually an admin
                $check_sql = "SELECT admin_id FROM admin WHERE user_id = ?";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("i", $target_user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows == 0) {
                    $message = "User is not an admin.";
                    $message_type = "error";
                } else {
                    // Remove admin status
                    $sql = "DELETE FROM admin WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $target_user_id);
                    
                    if ($stmt->execute()) {
                        $message = "Admin privileges removed successfully!";
                        $message_type = "success";
                        
                        // Create notification for the user
                        create_notification(
                            $target_user_id,
                            'system',
                            0,
                            $user_id,
                            "Your admin privileges have been revoked."
                        );
                    } else {
                        $message = "Error removing admin privileges: " . $conn->error;
                        $message_type = "error";
                    }
                }
                break;
                
            case 'delete':
                // Delete user and all related data
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Delete user's posts
                    $sql = "DELETE FROM posts WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $target_user_id);
                    $stmt->execute();
                    
                    // Delete user's comments
                    $sql = "DELETE FROM comments WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $target_user_id);
                    $stmt->execute();
                    
                    // Delete user's likes
                    $sql = "DELETE FROM likes WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $target_user_id);
                    $stmt->execute();
                    
                    // Delete user's bookmarks
                    $sql = "DELETE FROM bookmarks WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $target_user_id);
                    $stmt->execute();
                    
                    // Delete user's followers relationships
                    $sql = "DELETE FROM followers WHERE follower_user_id = ? OR followed_user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $target_user_id, $target_user_id);
                    $stmt->execute();
                    
                    // Delete user's notifications
                    $sql = "DELETE FROM notifications WHERE user_id = ? OR from_user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $target_user_id, $target_user_id);
                    $stmt->execute();
                    
                    // Delete admin record if exists
                    $sql = "DELETE FROM admin WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $target_user_id);
                    $stmt->execute();
                    
                    // Finally delete the user
                    $sql = "DELETE FROM users WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $target_user_id);
                    $stmt->execute();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    $message = "User and all related data deleted successfully!";
                    $message_type = "success";
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    $message = "Error deleting user: " . $e->getMessage();
                    $message_type = "error";
                }
                break;
        }
    }
}

// Pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

// Build query based on filters
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search_query)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

if ($role_filter == 'admin') {
    $where_conditions[] = "a.admin_id IS NOT NULL";
} elseif ($role_filter == 'user') {
    $where_conditions[] = "a.admin_id IS NULL";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Count total users for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM users u 
              LEFT JOIN admin a ON u.user_id = a.user_id 
              $where_clause";
$stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$count_result = $stmt->get_result();
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Get users with filtering and pagination
$sql = "SELECT u.*, 
        (a.admin_id IS NOT NULL) as is_admin,
        (SELECT COUNT(*) FROM posts WHERE user_id = u.user_id) as post_count,
        (SELECT COUNT(*) FROM followers WHERE followed_user_id = u.user_id) as follower_count
        FROM users u
        LEFT JOIN admin a ON u.user_id = a.user_id
        $where_clause
        ORDER BY u.join_date DESC
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);

// Add limit and offset to params
$params[] = $offset;
$params[] = $limit;
$param_types .= 'ii';

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$users_result = $stmt->get_result();

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
    
    .role-badge {
        @apply text-xs font-medium px-2 py-1 rounded-full;
    }
    
    .role-admin {
        @apply bg-typoria-secondary/20 text-typoria-secondary;
    }
    
    .role-user {
        @apply bg-typoria-primary/20 text-typoria-primary;
    }
";

// Page title
$page_title = "Manage Users";

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
                <h1 class="text-2xl font-bold text-gray-800">Manage Users</h1>
                <div class="mt-4 md:mt-0 bg-typoria-primary/10 text-typoria-primary px-4 py-2 rounded-lg text-sm">
                    <span class="font-medium"><?php echo number_format($total_users); ?></span> total users registered
                </div>
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
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Users</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20"
                                placeholder="Search by name or email...">
                        </div>
                        
                        <!-- Role Filter -->
                        <div class="w-full md:w-auto">
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select id="role" name="role" class="rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20">
                                <option value="">All Roles</option>
                                <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>Regular User</option>
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
                            <a href="manage_users.php" class="block text-center w-full md:w-auto bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    User
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Role
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Join Date
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
                            <?php if ($users_result->num_rows > 0) : ?>
                                <?php while ($user_row = $users_result->fetch_assoc()) : ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-typoria-primary to-typoria-secondary flex items-center justify-center text-white font-bold">
                                                    <?php echo strtoupper(substr($user_row['name'], 0, 1)); ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <a href="../profile.php?id=<?php echo $user_row['user_id']; ?>" class="hover:text-typoria-primary" target="_blank">
                                                            <?php echo htmlspecialchars($user_row['name']); ?>
                                                        </a>
                                                        <?php if ($user_row['user_id'] == $user_id) : ?>
                                                            <span class="ml-2 text-xs text-gray-500">(You)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        ID: <?php echo $user_row['user_id']; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user_row['email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="role-badge <?php echo $user_row['is_admin'] ? 'role-admin' : 'role-user'; ?>">
                                                <?php echo $user_row['is_admin'] ? 'Admin' : 'User'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo format_date($user_row['join_date']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                                <div class="flex items-center" title="Posts">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <?php echo $user_row['post_count']; ?>
                                                </div>
                                                <div class="flex items-center" title="Followers">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                    </svg>
                                                    <?php echo $user_row['follower_count']; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-2">
                                                <a href="../profile.php?id=<?php echo $user_row['user_id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="View Profile" target="_blank">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>
                                                
                                                <!-- Admin Actions (only show for different users) -->
                                                <?php if ($user_row['user_id'] != $user_id) : ?>
                                                    <?php if (!$user_row['is_admin']) : ?>
                                                        <a href="manage_users.php?action=make_admin&id=<?php echo $user_row['user_id']; ?>" class="text-violet-600 hover:text-violet-900" title="Make Admin" onclick="return confirm('Are you sure you want to make this user an admin?')">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                            </svg>
                                                        </a>
                                                    <?php else : ?>
                                                        <a href="manage_users.php?action=remove_admin&id=<?php echo $user_row['user_id']; ?>" class="text-orange-600 hover:text-orange-900" title="Remove Admin" onclick="return confirm('Are you sure you want to remove admin privileges from this user?')">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                            </svg>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="manage_users.php?action=delete&id=<?php echo $user_row['user_id']; ?>" class="text-red-600 hover:text-red-900" title="Delete User" onclick="return confirm('Are you sure you want to delete this user? This will delete all their posts, comments, likes, and other data. This action cannot be undone.')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center py-8">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            <p class="text-xl font-medium text-gray-600">No users found</p>
                                            <p class="text-gray-500 mt-1">Try changing your search filters</p>
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
                                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_query); ?>&role=<?php echo $role_filter; ?>" class="px-3 py-1 bg-white rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Page Numbers -->
                            <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++) : ?>
                                <li>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>&role=<?php echo $role_filter; ?>" 
                                       class="px-3 py-1 rounded-md <?php echo $i == $page ? 'bg-typoria-primary text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Next Page -->
                            <?php if ($page < $total_pages) : ?>
                                <li>
                                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_query); ?>&role=<?php echo $role_filter; ?>" class="px-3 py-1 bg-white rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <p class="text-center text-gray-500 text-sm mt-2">
                        Showing <?php echo min(($page - 1) * $limit + 1, $total_users); ?> to <?php echo min($page * $limit, $total_users); ?> of <?php echo $total_users; ?> users
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