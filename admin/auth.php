<?php
/**
 * Typoria Blog Platform
 * Authentication System
 */

// Include database connection
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (isset($_SESSION['admin_id'])) {
    $isLoggedIn = true;
    $admin_id = $_SESSION['admin_id'];
    
    // Get admin details
    $conn = get_db_connection();
    $sql = "SELECT name, email FROM admin WHERE admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        $adminName = $admin['name'];
        $adminEmail = $admin['email'];
    } else {
        // If admin not found in database, log them out
        session_unset();
        session_destroy();
        header("Location: admin-login.php");
        exit();
    }
} else {
    $isLoggedIn = false;
    $admin_id = null;
    $adminName = null;
    $adminEmail = null;
}

/**
 * Login admin
 * 
 * @param string $email Admin email
 * @param string $password Admin password
 * @param bool $remember Remember login
 * @return array Success status, error message, and redirect URL
 */
function login_admin($email, $password, $remember = false) {
    $conn = get_db_connection();
    
    // Debug point 1 - make sure this query works
    $sql = "SELECT admin_id, name, email, password, user_id FROM admin WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Debug point 2 - check if admin exists
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => "Invalid email or password",
            'redirect' => null
        ];
    }
    
    $admin = $result->fetch_assoc();
    
    // Debug point 3 - verify password works
    if (password_verify($password, $admin['password'])) {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set BOTH admin_id and user_id in session
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['user_id'] = $admin['user_id']; // This is important!
        $_SESSION['username'] = $admin['name'];
        $_SESSION['is_admin'] = true; // Explicitly set admin flag
        
        // More debugging
        error_log("Admin login successful: " . print_r($_SESSION, true));
        
        return [
            'success' => true,
            'message' => "Admin login successful!",
            'redirect' => 'admin/dashboard.php'
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
 * Register a new user
 * 
 * @param string $name User's name
 * @param string $email User's email
 * @param string $password User's password (plain text)
 * @return bool|string True on success, error message on failure
 */
function register_user($name, $email, $password) {
    $conn = get_db_connection();
    
    // Check if email already exists
    $check_sql = "SELECT user_id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        return "Email already registered. Please use a different email or try to login.";
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user into database
    $sql = "INSERT INTO users (name, email, password, join_date) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $email, $hashed_password);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set session variables for logged-in user
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $name;
        $_SESSION['is_admin'] = false;
        
        return true;
    } else {
        // Log the error
        error_log("Registration error: " . $stmt->error);
        return "Registration failed: " . $stmt->error;
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