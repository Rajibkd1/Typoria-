<?php
/**
 * Typoria Blog Platform
 * Authentication System
 */

// Include database connection
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $isLoggedIn = true;
    $user_id = $_SESSION['user_id'];
    
    // Get user details
    $conn = get_db_connection();
    $sql = "SELECT name, email, profile_image FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $username = $user['name'];
        $userEmail = $user['email'];
        $userImage = $user['profile_image'] ?? 'default.png';
    } else {
        // If user not found in database, log them out
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    // Check if user is admin
    $sql = "SELECT admin_id FROM admin WHERE admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $isAdmin = ($result->num_rows > 0);
    
    // Store admin status in session for easier access across pages
    $_SESSION['is_admin'] = $isAdmin;
    
} else {
    $isLoggedIn = false;
    $user_id = null;
    $username = null;
    $userEmail = null;
    $userImage = 'default.png';
    $isAdmin = false;
}

/**
 * Login user
 * 
 * @param string $email User email
 * @param string $password User password
 * @param bool $remember Remember login
 * @param bool $admin_login Whether this is an admin login attempt
 * @return array Success status, error message, and redirect URL
 */
function login_user($email, $password, $remember = false, $admin_login = false) {
    $conn = get_db_connection();
    
    // Get user with matching email
    $sql = "SELECT user_id, name, email, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => "Invalid email or password",
            'redirect' => null
        ];
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // First, check if this user is an admin (if admin login was requested)
        $is_admin = false;
        
        $admin_sql = "SELECT admin_id FROM admin WHERE user_id = ?";
        $stmt = $conn->prepare($admin_sql);
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $admin_result = $stmt->get_result();
        $is_admin = ($admin_result->num_rows > 0);
        
        // If admin login requested but user is not an admin
        if ($admin_login && !$is_admin) {
            return [
                'success' => false,
                'message' => "You do not have administrator privileges",
                'redirect' => null
            ];
        }
        
        // Set session variables
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['name'];
        $_SESSION['is_admin'] = $is_admin;
        
        // Set remember me cookie if requested
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (30 * 24 * 60 * 60); // 30 days
            
            // Store token in database
            $sql = "INSERT INTO auth_tokens (user_id, token, expires) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $expires_date = date('Y-m-d H:i:s', $expires);
            $stmt->bind_param("iss", $user['user_id'], $token, $expires_date);
            $stmt->execute();
            
            // Set cookie
            setcookie('remember_token', $token, $expires, '/', '', true, true);
        }
        
        // Note: We'll skip updating last_login since the column doesn't exist
        // If you want to track last login time, you'd need to add this column to your users table
        // ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL;
        
        // Determine redirect URL
        $redirect = $is_admin && $admin_login ? 'admin/dashboard.php' : 'index.php';
        
        return [
            'success' => true,
            'message' => "Login successful!",
            'redirect' => $redirect,
            'is_admin' => $is_admin
        ];
    } else {
        return [
            'success' => false,
            'message' => "Invalid email or password",
            'redirect' => null
        ];
    }
}

/**
 * Register new user
 * 
 * @param string $name User name
 * @param string $email User email
 * @param string $password User password
 * @return bool|string True on success, error message on failure
 */
function register_user($name, $email, $password) {
    $conn = get_db_connection();
    
    // Check if email already exists
    $sql = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return "Email already registered";
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $join_date = date('Y-m-d H:i:s');
    
    // Insert new user
    $sql = "INSERT INTO users (name, email, password, join_date) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $join_date);
    
    if ($stmt->execute()) {
        // Set session variables
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['username'] = $name;
        $_SESSION['is_admin'] = false;
        return true;
    } else {
        return "Registration failed: " . $conn->error;
    }
}

/**
 * Log out user
 * 
 * @return void
 */
function logout_user() {
    // Clear remember me cookie if set
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $conn = get_db_connection();
        
        // Delete token from database
        $sql = "DELETE FROM auth_tokens WHERE token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        // Clear cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
    
    // Destroy session
    session_unset();
    session_destroy();
}

/**
 * Check remember me cookie
 * 
 * @return bool True if valid remember me cookie found
 */
function check_remember_cookie() {
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    $token = $_COOKIE['remember_token'];
    $conn = get_db_connection();
    
    // Get token from database
    $sql = "SELECT a.user_id, u.name 
            FROM auth_tokens a 
            JOIN users u ON a.user_id = u.user_id 
            WHERE a.token = ? AND a.expires > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Invalid or expired token
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        return false;
    }
    
    $user = $result->fetch_assoc();
    
    // Check if user is admin
    $admin_sql = "SELECT admin_id FROM admin WHERE user_id = ?";
    $stmt = $conn->prepare($admin_sql);
    $stmt->bind_param("i", $user['user_id']);
    $stmt->execute();
    $admin_result = $stmt->get_result();
    $is_admin = ($admin_result->num_rows > 0);
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['name'];
    $_SESSION['is_admin'] = $is_admin;
    
    // Renew token
    $new_token = bin2hex(random_bytes(32));
    $expires = time() + (30 * 24 * 60 * 60); // 30 days
    
    // Update token in database
    $sql = "UPDATE auth_tokens SET token = ?, expires = ? WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $expires_date = date('Y-m-d H:i:s', $expires);
    $stmt->bind_param("sss", $new_token, $expires_date, $token);
    $stmt->execute();
    
    // Set new cookie
    setcookie('remember_token', $new_token, $expires, '/', '', true, true);
    
    return true;
}

// Auto-login with remember me cookie if not already logged in
if (!$isLoggedIn && isset($_COOKIE['remember_token'])) {
    check_remember_cookie();
    // Refresh page to update session variables
    if (isset($_SESSION['user_id'])) {
        header("Refresh:0");
    }
}
?>