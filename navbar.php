<?php

/**
 * Typoria Blog Platform
 * Fully Responsive Navigation Bar Component with Human-Written Style Logo
 */

// Include required files if not already included
if (!function_exists('check_auth')) {
    require_once 'includes/functions.php';
}
if (!isset($TYPORIA_CONFIG)) {
    require_once 'includes/theme.php';
}

// Get authentication status and user details
$auth = check_auth();
$isLoggedIn = $auth['isLoggedIn'];
$user_id = $auth['user_id'];
$username = $auth['username'];
$profile_image = $auth['profile_image'];

// Initialize database connection
$conn = get_db_connection();

// Get unread notifications count if user is logged in
$notification_count = 0;
if ($isLoggedIn) {
    $notification_count = get_unread_notifications_count($user_id);
}

// Define current page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Custom Styles for Enhanced Navigation Bar -->
<style>
    /* Modern color palette */
    :root {
        --primary: #7c3aed;
        /* Vibrant purple */
        --primary-hover: #6d28d9;
        --secondary: #8b5cf6;
        /* Lighter purple */
        --dark: #111827;
        /* Almost black */
        --dark-accent: #1f2937;
        /* Slightly lighter dark */
        --light-accent: #f9fafb;
        --text-light: #f3f4f6;
        --text-secondary: #9ca3af;
    }

    /* Navbar container */
    .typoria-navbar {
        background-color: var(--dark);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        position: sticky;
        top: 0;
        z-index: 50;
        transition: transform 0.3s ease, background-color 0.3s ease;
    }

    /* Logo styling with human-written feel */
    .typoria-logo {
        position: relative;
        transition: transform 0.3s ease;
        display: flex;
        align-items: center;
    }

    .typoria-logo:hover {
        transform: translateY(-2px);
    }

    /* Larger, more visible pen icon */
    .pen-icon {
        width: 32px;
        height: 32px;
        margin-right: 10px;
        filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.2));
        transition: all 0.3s ease;
    }

    .typoria-logo:hover .pen-icon {
        transform: rotate(-10deg);
    }

    /* The logo text with human-written style */
    .logo-text {
        position: relative;
        font-family: 'Dancing Script', cursive;
        font-weight: 700;
        font-size: 2.2rem;
        color: white;
        letter-spacing: 0.02em;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Gradient underline effect */
    .logo-text::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -2px;
        width: 100%;
        height: 2px;
        background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        border-radius: 2px;
        transform-origin: left;
        transform: scaleX(0.7);
        transition: transform 0.3s ease;
    }

    .typoria-logo:hover .logo-text::after {
        transform: scaleX(1);
    }

    /* Link styling */
    .nav-link {
        position: relative;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        transform: translateY(-1px);
    }

    .nav-link.active {
        background-color: var(--primary);
        color: white !important;
        font-weight: 500;
    }

    /* Elegant dropdown */
    .dropdown-menu {
        display: none;
        position: absolute;
        left: 0;
        top: 100%;
        margin-top: 0.5rem;
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        min-width: 10rem;
        z-index: 20;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }

    .dropdown-menu.show {
        display: block;
        animation: fadeIn 0.2s ease forwards;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dropdown-item {
        display: block;
        padding: 0.5rem 1rem;
        color: #4b5563;
        transition: all 0.2s ease;
        font-size: 0.875rem;
    }

    .dropdown-item:hover {
        background-color: #f3f4f6;
        color: var(--primary);
        padding-left: 1.25rem;
    }

    /* Beautiful search bar */
    .search-container {
        position: relative;
        width: 100%;
        max-width: 300px;
    }

    .search-input {
        width: 100%;
        padding: 0.5rem 0.75rem 0.5rem 2.5rem;
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 9999px;
        color: var(--text-light);
        font-size: 0.875rem;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        outline: none;
        background-color: rgba(255, 255, 255, 0.15);
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.3);
    }

    .search-input::placeholder {
        color: var(--text-secondary);
    }

    .search-icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        width: 1.25rem;
        height: 1.25rem;
        pointer-events: none;
    }


    /* User avatar */
    .user-avatar {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 50%;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 2px solid rgba(255, 255, 255, 0.1);
    }

    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .user-avatar:hover {
        transform: scale(1.05);
        box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.3);
        border-color: rgba(124, 58, 237, 0.5);
    }

    .initials {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1rem;
    }

    /* Mobile avatar */
    .mobile-avatar {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 0.75rem;
        border: 2px solid rgba(255, 255, 255, 0.1);
    }

    .mobile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Notification badge with animation */
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: linear-gradient(135deg, #f43f5e 0%, #ef4444 100%);
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 0.15rem 0.35rem;
        border-radius: 9999px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
        }

        70% {
            box-shadow: 0 0 0 5px rgba(239, 68, 68, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
        }
    }

    /* Button styling */
    .auth-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 0.375rem;
        transition: all 0.3s ease;
    }

    .login-button {
        color: var(--text-light);
        background-color: transparent;
    }

    .login-button:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .signup-button {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        border-radius: 9999px;
        padding: 0.5rem 1.25rem;
        box-shadow: 0 4px 6px rgba(124, 58, 237, 0.25);
    }

    .signup-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 10px rgba(124, 58, 237, 0.35);
    }

    /* Mobile menu button */
    .menu-button {
        display: none;
        background-color: rgba(255, 255, 255, 0.1);
        border: none;
        border-radius: 0.375rem;
        width: 2.5rem;
        height: 2.5rem;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .menu-button:hover {
        background-color: rgba(255, 255, 255, 0.15);
    }

    .menu-button:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.3);
    }

    /* Mobile menu styles */
    .mobile-menu {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 100;
    }

    .mobile-menu-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s ease;
    }

    .mobile-menu-container {
        position: absolute;
        top: 0;
        right: 0;
        width: 80%;
        max-width: 300px;
        height: 100%;
        background-color: var(--dark);
        overflow-y: auto;
        transform: translateX(100%);
        animation: slideIn 0.3s ease forwards;
        display: flex;
        flex-direction: column;
    }

    @keyframes slideIn {
        to {
            transform: translateX(0);
        }
    }

    .mobile-menu-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .mobile-close-button {
        background: transparent;
        border: none;
        color: var(--text-secondary);
        width: 2rem;
        height: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border-radius: 0.375rem;
        transition: background-color 0.2s ease;
    }

    .mobile-close-button:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .mobile-menu-content {
        flex: 1;
        padding: 1rem;
    }

    .mobile-nav-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        color: var(--text-secondary);
        border-radius: 0.375rem;
        margin-bottom: 0.5rem;
        transition: all 0.2s ease;
        font-weight: 500;
    }

    .mobile-nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .mobile-nav-link.active {
        background-color: var(--primary);
        color: white;
    }

    .mobile-nav-icon {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.75rem;
    }

    .mobile-divider {
        height: 1px;
        background-color: rgba(255, 255, 255, 0.1);
        margin: 1rem 0;
    }

    .mobile-dropdown-menu {
        margin-top: 0.5rem;
        margin-bottom: 1rem;
        background-color: rgba(255, 255, 255, 0.05);
        border-radius: 0.375rem;
        overflow: hidden;
        display: none;
    }

    .mobile-dropdown-item {
        display: block;
        padding: 0.75rem 1rem 0.75rem 2.75rem;
        color: var(--text-secondary);
        transition: all 0.2s ease;
    }

    .mobile-dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .mobile-user-info {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .mobile-username {
        color: white;
        font-weight: 500;
        font-size: 0.9375rem;
    }

    .mobile-role {
        color: var(--text-secondary);
        font-size: 0.75rem;
    }

    .mobile-search {
        padding: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
    }

    .mobile-search-input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.375rem;
        color: white;
        font-size: 0.9375rem;
    }

    .mobile-search-input:focus {
        outline: none;
        background-color: rgba(255, 255, 255, 0.15);
        border-color: var(--primary);
    }

    .mobile-search-icon {
        position: absolute;
        left: 1.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        width: 1.25rem;
        height: 1.25rem;
    }

    /* Mobile logo */
    .mobile-logo {
        display: flex;
        align-items: center;
    }

    .mobile-logo-text {
        font-family: 'Dancing Script', cursive;
        font-weight: 700;
        font-size: 1.5rem;
        color: white;
    }

    .mobile-pen-icon {
        width: 24px;
        height: 24px;
        margin-right: 6px;
    }

    /* Responsive styles */
    @media (max-width: 768px) {

        .desktop-nav,
        .desktop-search,
        .desktop-profile {
            display: none !important;
        }

        .menu-button {
            display: flex;
        }

        .logo-text {
            font-size: 1.8rem;
        }

        .pen-icon {
            width: 26px;
            height: 26px;
            margin-right: 8px;
        }
    }

    /* Fix for body when mobile menu is open */
    body.menu-open {
        overflow: hidden;
    }

    /* Navbar scroll effect */
    .navbar-scrolled {
        background-color: rgba(17, 24, 39, 0.95);
        backdrop-filter: blur(8px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
</style>

<!-- Add Font for Human-Written Logo Style -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&display=swap" rel="stylesheet">

<nav class="typoria-navbar py-3" id="navbar">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between">
            <!-- Logo with Pen Icon -->
            <div class="typoria-logo">
                <a href="index.php" class="flex items-center">
                    <!-- Pen Icon SVG - Larger and more visible -->
                    <svg class="pen-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="#7c3aed" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 5l-8 8M17 3l1 1.5L19.5 6 21 7l-3 3-7-7 3-3 1 1.5L16.5 3" />
                        <path d="M11 12L7 16l-3 1 1-3 4-4M7 16l4 4" />
                        <path d="M5 19l-2 2" />
                    </svg>

                    <!-- Human-Written Style Logo Text -->
                    <span class="logo-text"><?php echo htmlspecialchars($TYPORIA_CONFIG['logo_text']); ?></span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-1 desktop-nav">
                <a href="index.php" class="nav-link px-3 py-2 text-sm font-medium <?php echo $current_page == 'index.php' ? 'active' : 'text-gray-300 hover:text-white'; ?>">Home</a>

                <!-- Categories Dropdown -->
                <div class="relative dropdown">
                    <button type="button" class="nav-link dropdown-toggle px-3 py-2 text-sm font-medium text-gray-300 hover:text-white flex items-center">
                        Categories
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div class="dropdown-menu">
                        <?php
                        $sql = "SELECT * FROM categories ORDER BY category";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<a href="category.php?category_id=' . $row['category_id'] . '" class="dropdown-item">' . htmlspecialchars($row['category']) . '</a>';
                            }
                        } else {
                            echo '<span class="dropdown-item">No categories found</span>';
                        }
                        ?>
                    </div>
                </div>

                <?php if ($isLoggedIn) : ?>
                    <a href="create_post.php" class="nav-link px-3 py-2 text-sm font-medium flex items-center <?php echo $current_page == 'create_post.php' ? 'active' : 'text-gray-300 hover:text-white'; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create
                    </a>
                    <a href="profile.php" class="nav-link px-3 py-2 text-sm font-medium flex items-center <?php echo $current_page == 'profile.php' && !isset($_GET['id']) ? 'active' : 'text-gray-300 hover:text-white'; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Profile
                    </a>
                    <a href="bookmarks.php" class="nav-link px-3 py-2 text-sm font-medium flex items-center <?php echo $current_page == 'bookmarks.php' ? 'active' : 'text-gray-300 hover:text-white'; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        Bookmarks
                    </a>
                <?php endif; ?>
            </div>

            <!-- Desktop Search Bar -->
            <div class="hidden md:block mx-4 desktop-search">
                <div class="search-container">
                    <form method="POST" action="search.php">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo isset($_POST['search']) ? htmlspecialchars($_POST['search']) : ''; ?>"
                                class="search-input" placeholder="Search stories, authors, tags...">
                            <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </form>
                </div>
            </div>

            <!-- User Profile Section (Desktop) -->
            <div class="flex items-center space-x-3">
                <?php if ($isLoggedIn) : ?>
                    <!-- Notifications -->
                    <div class="relative">
                        <a href="notifications.php" class="text-gray-300 hover:text-white block p-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <?php if ($notification_count > 0) : ?>
                                <span class="notification-badge"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <!-- User Dropdown (Desktop) -->
                    <div class="relative dropdown hidden md:block desktop-profile">
                        <button type="button" class="dropdown-toggle flex items-center space-x-2 focus:outline-none">
                            <div class="user-avatar rounded-full overflow-hidden w-9 h-9 flex-shrink-0 border-2 border-gray-700">
                                <?php
                                $image_path = "uploads/profiles/{$profile_image}";
                                if (!file_exists($image_path) || empty($profile_image)) {
                                    // Display user initials if image doesn't exist
                                    echo '<div class="initials bg-gradient-to-r from-purple-600 to-indigo-600">' . strtoupper(substr($username, 0, 1)) . '</div>';
                                } else {
                                    // Display the image
                                    echo '<img src="' . $image_path . '" alt="' . htmlspecialchars($username) . '" class="w-full h-full object-cover">';
                                }
                                ?>
                            </div>
                            <span class="hidden sm:inline-block text-gray-300 text-sm"><?php echo htmlspecialchars($username); ?></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="hidden sm:inline-block h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div class="dropdown-menu right-0">
                            <a href="profile.php" class="dropdown-item">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    My Profile
                                </div>
                            </a>

                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="logout.php" class="dropdown-item text-red-600">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Logout
                                </div>
                            </a>
                        </div>
                    </div>
                <?php else : ?>
                    <!-- Auth Buttons -->
                    <a href="login.php" class="auth-button login-button">Log in</a>
                    <a href="register.php" class="auth-button signup-button">Sign up</a>
                <?php endif; ?>

                <!-- Mobile Menu Button -->
                <button type="button" id="mobileMenuButton" class="menu-button" aria-label="Open menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Menu (Hidden by default) -->
<div id="mobileMenu" class="mobile-menu">
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <div class="mobile-menu-container">
        <div class="mobile-menu-header">
            <div class="mobile-logo">
                <!-- Mobile Pen Icon SVG -->
                <svg class="mobile-pen-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="#7c3aed" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 5l-8 8M17 3l1 1.5L19.5 6 21 7l-3 3-7-7l3-3 1 1.5L16.5 3" />
                    <path d="M11 12L7 16l-3 1 1-3 4-4M7 16l4 4" />
                    <path d="M5 19l-2 2" />
                </svg>
                <span class="mobile-logo-text"><?php echo htmlspecialchars($TYPORIA_CONFIG['logo_text']); ?></span>
            </div>
            <button type="button" id="mobileMenuClose" class="mobile-close-button" aria-label="Close menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <?php if ($isLoggedIn) : ?>
            <div class="mobile-user-info">
                <div class="mobile-avatar rounded-full overflow-hidden w-10 h-10 flex-shrink-0 border-2 border-gray-700">
                    <?php
                    $image_path = "uploads/profiles/{$profile_image}";
                    if (!file_exists($image_path) || empty($profile_image)) {
                        // Display user initials if image doesn't exist
                        echo '<div class="initials bg-gradient-to-r from-purple-600 to-indigo-600">' . strtoupper(substr($username, 0, 1)) . '</div>';
                    } else {
                        // Display the image
                        echo '<img src="' . $image_path . '" alt="' . htmlspecialchars($username) . '" class="w-full h-full object-cover">';
                    }
                    ?>
                </div>
                <div>
                    <div class="mobile-username"><?php echo htmlspecialchars($username); ?></div>
                    <div class="mobile-role">Member</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="mobile-menu-content">
            <a href="index.php" class="mobile-nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Home
            </a>

            <button type="button" id="mobileCategoriesToggle" class="mobile-nav-link w-full text-left flex items-center justify-between">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Categories
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" id="categoryChevron" class="h-5 w-5 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div id="mobileCategoriesMenu" class="mobile-dropdown-menu">
                <?php
                $sql = "SELECT * FROM categories ORDER BY category";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<a href="category.php?category_id=' . $row['category_id'] . '" class="mobile-dropdown-item">' . htmlspecialchars($row['category']) . '</a>';
                    }
                } else {
                    echo '<span class="mobile-dropdown-item">No categories found</span>';
                }
                ?>
            </div>

            <?php if ($isLoggedIn) : ?>
                <a href="create_post.php" class="mobile-nav-link <?php echo $current_page == 'create_post.php' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Post
                </a>

                <a href="profile.php" class="mobile-nav-link <?php echo $current_page == 'profile.php' && !isset($_GET['id']) ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    My Profile
                </a>

                <a href="bookmarks.php" class="mobile-nav-link <?php echo $current_page == 'bookmarks.php' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                    Bookmarks
                </a>

                <a href="notifications.php" class="mobile-nav-link <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Notifications
                    <?php if ($notification_count > 0) : ?>
                        <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                </a>

                <div class="mobile-divider"></div>

                <a href="logout.php" class="mobile-nav-link text-red-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </a>
            <?php else : ?>
                <div class="mobile-divider"></div>

                <a href="login.php" class="mobile-nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Log In
                </a>

                <a href="register.php" class="mobile-nav-link bg-gradient-to-r from-purple-600 to-indigo-600 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Sign Up
                </a>
            <?php endif; ?>
        </div>

        <div class="mobile-search">
            <form method="POST" action="search.php" class="relative">
                <input type="text" name="search" value="<?php echo isset($_POST['search']) ? htmlspecialchars($_POST['search']) : ''; ?>"
                    class="mobile-search-input" placeholder="Search...">
                <svg xmlns="http://www.w3.org/2000/svg" class="mobile-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuClose = document.getElementById('mobileMenuClose');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        const mobileCategoriesToggle = document.getElementById('mobileCategoriesToggle');
        const mobileCategoriesMenu = document.getElementById('mobileCategoriesMenu');
        const categoryChevron = document.getElementById('categoryChevron');
        const dropdownToggleButtons = document.querySelectorAll('.dropdown-toggle');
        const body = document.body;
        const navbar = document.getElementById('navbar');

        // Function to open mobile menu
        function openMobileMenu() {
            mobileMenu.style.display = 'block';
            body.classList.add('menu-open');
        }

        // Function to close mobile menu
        function closeMobileMenu() {
            mobileMenu.style.display = 'none';
            body.classList.remove('menu-open');
        }

        // Mobile menu toggle
        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', openMobileMenu);
        }

        // Close mobile menu
        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', closeMobileMenu);
        }

        if (mobileMenuOverlay) {
            mobileMenuOverlay.addEventListener('click', closeMobileMenu);
        }

        // Mobile categories toggle
        if (mobileCategoriesToggle && mobileCategoriesMenu) {
            mobileCategoriesToggle.addEventListener('click', function() {
                if (mobileCategoriesMenu.style.display === 'block') {
                    mobileCategoriesMenu.style.display = 'none';
                    categoryChevron.classList.remove('rotate-180');
                } else {
                    mobileCategoriesMenu.style.display = 'block';
                    categoryChevron.classList.add('rotate-180');
                }
            });
        }

        // Desktop dropdowns
        dropdownToggleButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const dropdown = this.nextElementSibling;

                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    if (menu !== dropdown && menu.classList.contains('show')) {
                        menu.classList.remove('show');
                    }
                });

                // Toggle current dropdown
                dropdown.classList.toggle('show');
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                if (!menu.parentElement.contains(e.target)) {
                    menu.classList.remove('show');
                }
            });
        });

        // Close mobile menu when window is resized to desktop size
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768 && mobileMenu.style.display === 'block') {
                closeMobileMenu();
            }
        });

        // Improved Navbar scroll effect with backdrop blur
        let lastScrollPosition = 0;

        window.addEventListener('scroll', function() {
            const currentScrollPosition = window.pageYOffset;

            // Apply scrolled class for background effect
            if (currentScrollPosition > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }

            // Hide/show navbar based on scroll direction
            if (currentScrollPosition > 200) {
                if (currentScrollPosition > lastScrollPosition) {
                    // Scrolling down - hide navbar
                    navbar.style.transform = 'translateY(-100%)';
                } else {
                    // Scrolling up - show navbar
                    navbar.style.transform = 'translateY(0)';
                }
            }

            lastScrollPosition = currentScrollPosition;
        });
    });
</script>