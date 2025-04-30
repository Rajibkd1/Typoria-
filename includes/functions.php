<?php
/**
 * Typoria Blog Platform
 * Utility Functions
 */

/**
 * Database Connection
 * Creates a connection to the database
 * 
 * @return mysqli Database connection object
 */
function get_db_connection() {
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "typoria"; 
    
    // Create connection
    $conn = new mysqli($host, $username, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

/**
 * Check Authentication
 * Checks if the user is logged in and sets global variables
 * 
 * @return array User authentication details including isLoggedIn, user_id and username
 */
function check_auth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $auth = [
        'isLoggedIn' => false,
        'user_id' => null,
        'username' => null,
        'is_admin' => false,
        'profile_image' => null  // Added profile image field
    ];
    
    // Check if the user is logged in
    if (isset($_SESSION['user_id'])) {
        $auth['isLoggedIn'] = true;
        $auth['user_id'] = $_SESSION['user_id'];
        $auth['username'] = $_SESSION['username'] ?? "User";
        
        // Get database connection
        $conn = get_db_connection();
        
        // Check if user is admin
        $sql = "SELECT admin_id FROM admin WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $auth['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $auth['is_admin'] = true;
        }
        $stmt->close();
        
        // Get user's profile image
        $sql = "SELECT profile_image FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $auth['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            $auth['profile_image'] = $user_data['profile_image'];
        }
        $stmt->close();
    }
    
    return $auth;
}

/**
 * Redirect if Not Logged In
 * Redirects to login page if user is not authenticated
 * 
 * @param string $redirect_url URL to redirect to after login
 * @return void
 */
function require_login($redirect_url = '') {
    $auth = check_auth();
    
    if (!$auth['isLoggedIn']) {
        $redirect = empty($redirect_url) ? '' : '?redirect=' . urlencode($redirect_url);
        header("Location: login.php" . $redirect);
        exit();
    }
    
    return $auth;
}

/**
 * Redirect if Not Admin
 * Redirects to homepage if user is not an admin
 * 
 * @return void
 */
function require_admin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Debug session
    error_log("Session data: " . print_r($_SESSION, true));
    
    $auth = [
        'isLoggedIn' => false,
        'user_id' => null,
        'username' => null,
        'is_admin' => false
    ];
    
    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        $auth['isLoggedIn'] = true;
        $auth['user_id'] = $_SESSION['user_id'];
        $auth['username'] = $_SESSION['username'] ?? "User";
        
        // Use the session variable directly instead of querying the database
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
            $auth['is_admin'] = true;
        } else {
            // Fallback database check
            $conn = get_db_connection();
            $sql = "SELECT admin_id FROM admin WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $auth['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $auth['is_admin'] = true;
                // Update session for future checks
                $_SESSION['is_admin'] = true;
            }
            
            $stmt->close();
        }
    }
    
    // Now check if user is admin and redirect if not
    if (!$auth['isLoggedIn']) {
        error_log("Not logged in, redirecting to login");
        header("Location: ../login.php");
        exit();
    } else if (!$auth['is_admin']) {
        error_log("Not admin, redirecting to dashboard");
        header("Location: ../index.php");  // or wherever non-admins should go
        exit();
    }
    
    return $auth;
}

/**
 * Calculate estimated reading time for post content
 * 
 * @param string $content Post content
 * @return int Estimated reading time in minutes
 */
function calculate_reading_time($content) {
    // Remove HTML tags and strip whitespace
    $clean_content = strip_tags($content);
    $word_count = str_word_count($clean_content);
    
    // Average reading speed is about 200-250 words per minute
    $reading_time = ceil($word_count / 225);
    
    // Minimum reading time is 1 minute
    return max(1, $reading_time);
}

/**
 * Format date in a user-friendly way
 * 
 * @param string $date MySQL datetime string
 * @param bool $include_time Whether to include the time
 * @return string Formatted date
 */
function format_date($date, $include_time = true) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    // If less than 24 hours, show relative time
    if ($diff < 86400) {
        if ($diff < 60) {
            return "Just now";
        } elseif ($diff < 3600) {
            return floor($diff / 60) . " minutes ago";
        } else {
            return floor($diff / 3600) . " hours ago";
        }
    } 
    // If within the past week, show day of week
    elseif ($diff < 604800) {
        return date('l', $timestamp) . ($include_time ? ' at ' . date('g:i A', $timestamp) : '');
    } 
    // Otherwise show full date
    else {
        return date('F j, Y', $timestamp) . ($include_time ? ' at ' . date('g:i A', $timestamp) : '');
    }
}

/**
 * Generate a unique slug from a title
 * 
 * @param string $title Post title
 * @param object $conn Database connection
 * @param int $post_id Optional post ID for updates
 * @return string Unique slug
 */
function generate_slug($title, $conn, $post_id = null) {
    // Convert to lowercase and replace spaces with hyphens
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
    
    // Check if slug exists in database
    $sql = "SELECT post_id FROM posts WHERE post_id != ? AND title = ?";
    $stmt = $conn->prepare($sql);
    
    if ($post_id === null) {
        $post_id = 0;
    }
    
    $stmt->bind_param("is", $post_id, $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If slug exists, append a number
    if ($result->num_rows > 0) {
        $i = 1;
        do {
            $new_slug = $slug . "-" . $i;
            $sql = "SELECT post_id FROM posts WHERE post_id != ? AND title = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $post_id, $new_slug);
            $stmt->execute();
            $result = $stmt->get_result();
            $i++;
        } while ($result->num_rows > 0);
        
        $slug = $new_slug;
    }
    
    return $slug;
}

/**
 * Create excerpt from post content
 * 
 * @param string $content Post content
 * @param int $length Maximum length of excerpt
 * @return string Post excerpt
 */
function create_excerpt($content, $length = 150) {
    // Strip HTML tags
    $text = strip_tags($content);
    
    // Cut to length
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' ')) . '...';
    }
    
    return $text;
}

/**
 * Record post view
 * 
 * @param int $post_id Post ID
 * @param int $user_id User ID (or null for anonymous)
 * @return void
 */
function record_post_view($post_id, $user_id = null) {
    $conn = get_db_connection();
    
    // Get client IP address
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Check if this IP has viewed the post in the last 24 hours
    $sql = "SELECT view_id FROM post_views 
            WHERE post_id = ? AND ip_address = ? 
            AND view_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $post_id, $ip_address);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If no recent view, record a new view
    if ($result->num_rows == 0) {
        // Insert into post_views
        $sql = "INSERT INTO post_views (post_id, user_id, ip_address) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $post_id, $user_id, $ip_address);
        $stmt->execute();
        
        // Update post view count
        $sql = "UPDATE posts SET views = views + 1 WHERE post_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
    }
}

/**
 * Check if user has liked a post
 * 
 * @param int $post_id Post ID
 * @param int $user_id User ID
 * @return bool True if user has liked the post
 */
function has_user_liked_post($post_id, $user_id) {
    if (!$user_id) return false;
    
    $conn = get_db_connection();
    $sql = "SELECT like_id FROM likes WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return ($result->num_rows > 0);
}

/**
 * Check if user has bookmarked a post
 * 
 * @param int $post_id Post ID
 * @param int $user_id User ID
 * @return bool True if user has bookmarked the post
 */
function has_user_bookmarked_post($post_id, $user_id) {
    if (!$user_id) return false;
    
    $conn = get_db_connection();
    $sql = "SELECT bookmark_id FROM bookmarks WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return ($result->num_rows > 0);
}

/**
 * Create a notification
 * 
 * @param int $user_id User to notify
 * @param string $type Notification type (like, comment, follow, mention, system)
 * @param int $related_id ID related to notification (post_id, comment_id, etc.)
 * @param int|null $from_user_id User who triggered the notification (null for system)
 * @param string $message Notification message
 * @return bool Success status
 */
function create_notification($user_id, $type, $related_id, $from_user_id, $message) {
    $conn = get_db_connection();
    
    // Don't notify yourself
    if ($user_id == $from_user_id && $from_user_id !== null) {
        return false;
    }
    
    // For system notifications, set from_user_id to NULL
    if ($type === 'system') {
        $from_user_id = null;
    }
    
    // First, verify that the from_user_id exists in the users table (if not null)
    if ($from_user_id !== null) {
        $check_sql = "SELECT user_id FROM users WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $from_user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            // User doesn't exist, set from_user_id to NULL for a system notification
            $from_user_id = null;
        }
    }
    
    // Now proceed with creating the notification
    $sql = "INSERT INTO notifications (user_id, type, related_id, from_user_id, message) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Note: Using "i" for integer and "s" for string types
    $stmt->bind_param("isiss", $user_id, $type, $related_id, $from_user_id, $message);
    
    return $stmt->execute();
}

/**
 * Get user unread notifications count
 * 
 * @param int $user_id User ID
 * @return int Number of unread notifications
 */
function get_unread_notifications_count($user_id) {
    $conn = get_db_connection();
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Get recent notifications for a user
 * 
 * @param int $user_id User ID
 * @param int $limit Number of notifications to retrieve
 * @return array Notifications
 */
function get_user_notifications($user_id, $limit = 10) {
    $conn = get_db_connection();
    $sql = "SELECT n.*, u.name as from_user_name 
            FROM notifications n
            LEFT JOIN users u ON n.from_user_id = u.user_id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

/**
 * Sanitize input
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Get related posts based on category and tags
 * 
 * @param int $post_id Current post ID
 * @param int $category_id Category ID
 * @param int $limit Number of posts to retrieve
 * @return array Related posts
 */
function get_related_posts($post_id, $category_id, $limit = 3) {
    $conn = get_db_connection();
    
    // Get posts in the same category, excluding current post
    $sql = "SELECT p.*, u.name AS user_name, c.category,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count
            FROM posts p
            JOIN users u ON p.user_id = u.user_id
            JOIN categories c ON p.category_id = c.category_id
            WHERE p.category_id = ? AND p.post_id != ? AND p.status = 'approved'
            ORDER BY p.date_time DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $category_id, $post_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $related_posts = [];
    while ($row = $result->fetch_assoc()) {
        $related_posts[] = $row;
    }
    
    return $related_posts;
}

/**
 * Get trending posts
 * 
 * @param int $limit Number of posts to retrieve
 * @param int $days Number of days to consider for trending
 * @return array Trending posts
 */
function get_trending_posts($limit = 6, $days = 7) {
    $conn = get_db_connection();
    
    $sql = "SELECT p.*, u.name AS user_name, c.category, 
            (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count,
            (SELECT COUNT(*) FROM post_views WHERE post_id = p.post_id AND view_date > DATE_SUB(NOW(), INTERVAL ? DAY)) AS recent_views
            FROM posts p
            JOIN users u ON p.user_id = u.user_id
            JOIN categories c ON p.category_id = c.category_id
            WHERE p.status = 'approved' AND p.date_time > DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY (like_count + comment_count + recent_views) DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $days, $days, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trending_posts = [];
    while ($row = $result->fetch_assoc()) {
        $trending_posts[] = $row;
    }
    
    return $trending_posts;
}

/**
 * Upload and process image
 * 
 * @param array $file $_FILES array element
 * @param string $directory Directory to save image
 * @param string $prefix Optional prefix for filename
 * @return string|false Filename on success, false on failure
 */
function upload_image($file, $directory = 'uploads/', $prefix = '') {
    // Check if directory exists, create if not
    if (!file_exists($directory) && !is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    // Generate unique filename
    $filename = $prefix . uniqid() . '_' . basename($file['name']);
    $filepath = $directory . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}
/**
 * Register a new user
 * 
 * @param string $name User's name
 * @param string $email User's email
 * @param string $password User's password (plain text)
 * @return bool|string True on success, error message on failure
 */


 /**
 * Creates a new blog post
 * 
 * @param int $user_id ID of the user creating the post
 * @param string $title Post title
 * @param string $content Post content/details
 * @param int $category_id Category ID
 * @param string $tag_string Comma-separated list of tags
 * @param string $status Post status ('draft', 'pending', etc.)
 * @param array $image $_FILES['image'] array if image was uploaded
 * @return array Result with 'success' boolean, 'message' string, and 'post_id' if successful
 */

 function create_post($user_id, $title, $content, $category_id, $tag_string, $status, $image = null) {
    $conn = get_db_connection();
    $result = [
        'success' => false,
        'message' => '',
        'post_id' => null
    ];
    
    // Debug what we received
    error_log("Creating post - Title: {$title}");
    error_log("Content length: " . strlen($content));
    error_log("Content preview: " . substr($content, 0, 50));
    
    // Validate inputs
    if (empty($title)) {
        $result['message'] = "Post title is required";
        return $result;
    } 
    
    if (empty($content)) {
        $result['message'] = "Post content is required";
        return $result;
    } 
    
    if ($category_id <= 0) {
        $result['message'] = "Please select a category";
        return $result;
    }
    
    // Process image
    $image_name = 'default-post.jpg'; // Default image
    
    if ($image && $image['error'] != UPLOAD_ERR_NO_FILE) {
        $image_result = upload_image($image, 'uploads/', 'post_');
        if ($image_result) {
            $image_name = $image_result;
        } else {
            $result['message'] = "Image upload failed. Please try a different image.";
            return $result;
        }
    }
    
    // Calculate read time
    $read_time = calculate_reading_time($content);
    
    // Create excerpt
    $excerpt = create_excerpt($content);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert post into database
        $sql = "INSERT INTO posts (title, details, image, category_id, user_id, status, read_time, excerpt) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiisis", $title, $content, $image_name, $category_id, $user_id, $status, $read_time, $excerpt);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create post: " . $conn->error);
        }
        
        $post_id = $stmt->insert_id;
        
        // Process tags if provided
        if (!empty($tag_string)) {
            $tags_array = array_map('trim', explode(',', $tag_string));
            
            foreach ($tags_array as $tag_name) {
                if (empty($tag_name)) continue;
                
                // Check if tag exists
                $sql = "SELECT tag_id FROM tags WHERE tag_name = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $tag_name);
                $stmt->execute();
                $tag_result = $stmt->get_result();
                
                if ($tag_result->num_rows > 0) {
                    // Tag exists, get tag_id
                    $tag_row = $tag_result->fetch_assoc();
                    $tag_id = $tag_row['tag_id'];
                } else {
                    // Create new tag
                    $sql = "INSERT INTO tags (tag_name) VALUES (?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $tag_name);
                    $stmt->execute();
                    $tag_id = $stmt->insert_id;
                }
                
                // Associate tag with post
                $sql = "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $post_id, $tag_id);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $result['success'] = true;
        $result['message'] = "Post created successfully!";
        $result['post_id'] = $post_id;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $result['message'] = $e->getMessage();
        error_log("Post creation error: " . $e->getMessage());
    }
    
    return $result;
}


?>