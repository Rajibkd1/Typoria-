<?php
/**
 * Typoria Blog Platform
 * Enhanced Admin Sidebar Component
 */

// Get current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get admin stats if available (can be implemented based on your application)
$pending_posts = isset($pending_posts_count) ? $pending_posts_count : 0;
$pending_comments = isset($pending_comments_count) ? $pending_comments_count : 0;
$new_users = isset($new_users_count) ? $new_users_count : 0;
?>

<!-- Enhanced Admin Sidebar with Responsive Design -->
<div class="sidebar-wrapper">
    <aside id="adminSidebar" class="admin-sidebar text-white">
        <div class="sidebar-content">
            <!-- Admin Logo and Header -->
            <div class="sidebar-header">
                <div class="logo-container">
                    <svg class="logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                    </svg>
                    <div class="logo-text">
                        <h2 class="site-name"><?php echo $TYPORIA_CONFIG['site_name']; ?></h2>
                        <p class="panel-label">Admin Panel</p>
                    </div>
                </div>
                <!-- Mobile toggle button (visible only on small screens) -->
                <button id="sidebarCollapseBtn" class="collapse-btn" aria-label="Toggle Sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="collapse-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-bar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Search..." class="search-input">
                </div>
            </div>
            
            <!-- Admin Navigation -->
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <p class="nav-section-title">MAIN</p>
                    
                    <!-- Dashboard -->
                    <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <div class="nav-icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </div>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    
                    <!-- Posts Management -->
                    <a href="manage_posts.php" class="nav-link <?php echo $current_page == 'manage_posts.php' ? 'active' : ''; ?>">
                        <div class="nav-icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </div>
                        <span class="nav-text">Manage Posts</span>
                        <?php if ($pending_posts > 0): ?>
                            <span class="nav-badge"><?php echo $pending_posts; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Users Management -->
                    <a href="manage_users.php" class="nav-link <?php echo $current_page == 'manage_users.php' ? 'active' : ''; ?>">
                        <div class="nav-icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <span class="nav-text">Manage Users</span>
                        <?php if ($new_users > 0): ?>
                            <span class="nav-badge"><?php echo $new_users; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <div class="nav-section">
                    <p class="nav-section-title">CONTENT</p>
                    
                    <!-- Categories & Tags -->
                    <a href="manage_categories.php" class="nav-link <?php echo $current_page == 'manage_categories.php' ? 'active' : ''; ?>">
                        <div class="nav-icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path>
                                <rect x="9" y="3" width="6" height="4" rx="2"></rect>
                                <path d="M9 14h.01"></path>
                                <path d="M13 14h2"></path>
                                <path d="M9 18h.01"></path>
                                <path d="M13 18h2"></path>
                            </svg>
                        </div>
                        <span class="nav-text">Categories & Tags</span>
                    </a>
                    
                    <!-- Comments -->
                    <a href="manage_comments.php" class="nav-link <?php echo $current_page == 'manage_comments.php' ? 'active' : ''; ?>">
                        <div class="nav-icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                <path d="M8 10h.01"></path>
                                <path d="M12 10h.01"></path>
                                <path d="M16 10h.01"></path>
                            </svg>
                        </div>
                        <span class="nav-text">Comments</span>
                        <?php if ($pending_comments > 0): ?>
                            <span class="nav-badge"><?php echo $pending_comments; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Media Library -->
                    <a href="media_library.php" class="nav-link <?php echo $current_page == 'media_library.php' ? 'active' : ''; ?>">
                        <div class="nav-icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                        </div>
                        <span class="nav-text">Media Library</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <p class="nav-section-title">SETTINGS</p>
                    
                    <!-- Site Settings -->
                    <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                        <div class="nav-icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                        </div>
                        <span class="nav-text">Site Settings</span>
                    </a>
                    
                    <!-- Theme Options -->
                    <a href="theme_options.php" class="nav-link <?php echo $current_page == 'theme_options.php' ? 'active' : ''; ?>">
                        <div class="nav-icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3v18"></path>
                                <circle cx="6" cy="9" r="3"></circle>
                                <circle cx="18" cy="15" r="3"></circle>
                            </svg>
                        </div>
                        <span class="nav-text">Theme Options</span>
                    </a>
                    
                    <!-- Analytics -->
                    <a href="analytics.php" class="nav-link <?php echo $current_page == 'analytics.php' ? 'active' : ''; ?>">
                        <div class="nav-icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="14"></line>
                                <line x1="2" y1="20" x2="22" y2="20"></line>
                            </svg>
                        </div>
                        <span class="nav-text">Analytics</span>
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <!-- View Site -->
                <a href="../index.php" class="footer-link view-site" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <span>View Site</span>
                </a>
                
                <!-- Logout -->
                <a href="../logout.php" class="footer-link logout">
                    <svg xmlns="http://www.w3.org/2000/svg" class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Admin Info -->
        <div class="user-profile">
            <div class="avatar">
                <?php echo strtoupper(substr($username ?? 'A', 0, 1)); ?>
            </div>
            <div class="user-info">
                <p class="user-name"><?php echo htmlspecialchars($username ?? 'Admin'); ?></p>
                <p class="user-role">Administrator</p>
            </div>
            <div class="user-actions">
                <a href="profile.php" class="profile-link" title="Edit Profile">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
            </div>
        </div>
    </aside>
    
    <!-- Mobile Sidebar Toggle Button (Visible on small screens) -->
    <button id="mobileSidebarToggle" class="mobile-toggle-btn" aria-label="Toggle Sidebar">
        <svg xmlns="http://www.w3.org/2000/svg" class="toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
</div>

<style>
    /* Admin Sidebar Variables */
    :root {
        --sidebar-width: 280px;
        --sidebar-collapsed-width: 80px;
        --sidebar-bg: #1e1e2d;
        --sidebar-hover: rgba(255, 255, 255, 0.1);
        --sidebar-active: rgba(124, 58, 237, 0.2);
        --sidebar-active-border: #7c3aed;
        --sidebar-text: #a0aec0;
        --sidebar-text-active: #ffffff;
        --sidebar-border: rgba(255, 255, 255, 0.1);
        --sidebar-icon: rgba(255, 255, 255, 0.5);
        --sidebar-section: #64748b;
        --sidebar-header-bg: rgba(0, 0, 0, 0.2);
        --sidebar-footer-bg: rgba(0, 0, 0, 0.2);
        --badge-bg: #7c3aed;
        --transition-speed: 0.3s;
        --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.1);
        --shadow-dark: 0 5px 15px rgba(0, 0, 0, 0.35);
    }

    /* Sidebar Layout */
    .sidebar-wrapper {
        position: relative;
        height: 100%;
    }
    
    .admin-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: var(--sidebar-width);
        background-color: var(--sidebar-bg);
        color: var(--sidebar-text);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 50;
        box-shadow: var(--shadow-dark);
        transition: width var(--transition-speed), transform var(--transition-speed);
    }
    
    .sidebar-content {
        display: flex;
        flex-direction: column;
        height: calc(100% - 80px);
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--sidebar-border) transparent;
    }
    
    .sidebar-content::-webkit-scrollbar {
        width: 4px;
    }
    
    .sidebar-content::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .sidebar-content::-webkit-scrollbar-thumb {
        background-color: var(--sidebar-border);
        border-radius: 20px;
    }
    
    /* Sidebar Header */
    .sidebar-header {
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: var(--sidebar-header-bg);
        border-bottom: 1px solid var(--sidebar-border);
    }
    
    .logo-container {
        display: flex;
        align-items: center;
    }
    
    .logo-icon {
        width: 2rem;
        height: 2rem;
        color: white;
        flex-shrink: 0;
        filter: drop-shadow(0 0 6px rgba(124, 58, 237, 0.5));
    }
    
    .logo-text {
        margin-left: 0.75rem;
        overflow: hidden;
    }
    
    .site-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: white;
        margin: 0;
        white-space: nowrap;
    }
    
    .panel-label {
        font-size: 0.75rem;
        color: var(--sidebar-text);
        margin: 0;
        white-space: nowrap;
    }
    
    .collapse-btn {
        display: none;
        background: transparent;
        border: none;
        color: var(--sidebar-text);
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 0.25rem;
        transition: color var(--transition-speed), background-color var(--transition-speed);
    }
    
    .collapse-btn:hover {
        color: white;
        background-color: var(--sidebar-hover);
    }
    
    .collapse-icon {
        width: 1.25rem;
        height: 1.25rem;
    }
    
    /* Search Bar */
    .search-container {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--sidebar-border);
    }
    
    .search-bar {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .search-icon {
        position: absolute;
        left: 0.75rem;
        width: 1rem;
        height: 1rem;
        color: var(--sidebar-text);
    }
    
    .search-input {
        width: 100%;
        padding: 0.5rem 0.5rem 0.5rem 2.25rem;
        border-radius: 0.375rem;
        background-color: rgba(0, 0, 0, 0.2);
        border: 1px solid var(--sidebar-border);
        color: white;
        font-size: 0.875rem;
        transition: all var(--transition-speed);
    }
    
    .search-input:focus {
        outline: none;
        background-color: rgba(0, 0, 0, 0.3);
        border-color: var(--sidebar-active-border);
        box-shadow: 0 0 0 1px var(--sidebar-active-border);
    }
    
    .search-input::placeholder {
        color: var(--sidebar-text);
    }
    
    /* Navigation */
    .sidebar-nav {
        flex: 1;
        padding: 1.25rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .nav-section {
        display: flex;
        flex-direction: column;
    }
    
    .nav-section-title {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--sidebar-section);
        margin: 0 0 0.75rem 0.5rem;
        white-space: nowrap;
        letter-spacing: 0.05em;
    }
    
    .nav-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        color: var(--sidebar-text);
        text-decoration: none;
        transition: all var(--transition-speed);
        position: relative;
        margin-bottom: 0.375rem;
        white-space: nowrap;
    }
    
    .nav-link:hover {
        color: var(--sidebar-text-active);
        background-color: var(--sidebar-hover);
    }
    
    .nav-link.active {
        color: var(--sidebar-text-active);
        background-color: var(--sidebar-active);
        border-left: 3px solid var(--sidebar-active-border);
        font-weight: 500;
    }
    
    .nav-icon-container {
        width: 2rem;
        height: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.375rem;
        margin-right: 0.75rem;
        background-color: rgba(0, 0, 0, 0.2);
        transition: all var(--transition-speed);
    }
    
    .nav-link:hover .nav-icon-container {
        background-color: rgba(124, 58, 237, 0.2);
    }
    
    .nav-link.active .nav-icon-container {
        background-color: rgba(124, 58, 237, 0.25);
    }
    
    .nav-icon {
        width: 1.25rem;
        height: 1.25rem;
        color: var(--sidebar-icon);
        transition: color var(--transition-speed);
    }
    
    .nav-link:hover .nav-icon,
    .nav-link.active .nav-icon {
        color: white;
    }
    
    .nav-text {
        flex: 1;
        font-size: 0.9375rem;
    }
    
    .nav-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        background-color: var(--badge-bg);
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        transition: all var(--transition-speed);
    }
    
    /* Sidebar Footer */
    .sidebar-footer {
        padding: 1rem 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: auto;
        border-top: 1px solid var(--sidebar-border);
    }
    
    .footer-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        color: var(--sidebar-text);
        text-decoration: none;
        transition: all var(--transition-speed);
        font-size: 0.9375rem;
    }
    
    .footer-icon {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.75rem;
    }
    
    .view-site:hover {
        background-color: rgba(37, 99, 235, 0.1);
        color: #60a5fa;
    }
    
    .view-site:hover .footer-icon {
        color: #60a5fa;
    }
    
    .logout:hover {
        background-color: rgba(220, 38, 38, 0.1);
        color: #f87171;
    }
    
    .logout:hover .footer-icon {
        color: #f87171;
    }
    
    /* User Profile */
    .user-profile {
        height: 80px;
        border-top: 1px solid var(--sidebar-border);
        background-color: var(--sidebar-footer-bg);
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        transition: padding var(--transition-speed);
    }
    
    .avatar {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        background: linear-gradient(135deg, #7c3aed, #6366f1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1rem;
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        transition: all var(--transition-speed);
    }
    
    .user-info {
        margin-left: 0.75rem;
        overflow: hidden;
        flex: 1;
    }
    
    .user-name {
        color: white;
        font-size: 0.9375rem;
        font-weight: 500;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .user-role {
        color: var(--sidebar-text);
        font-size: 0.75rem;
        margin: 0;
        white-space: nowrap;
    }
    
    .user-actions {
        display: flex;
        align-items: center;
    }
    
    .profile-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 0.375rem;
        color: var(--sidebar-text);
        transition: all var(--transition-speed);
    }
    
    .profile-link:hover {
        background-color: var(--sidebar-hover);
        color: white;
    }
    
    /* Mobile Toggle Button */
    .mobile-toggle-btn {
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 40;
        background-color: var(--sidebar-bg);
        border: none;
        color: white;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: var(--shadow-light);
    }
    
    .toggle-icon {
        width: 1.5rem;
        height: 1.5rem;
    }
    
    /* Responsive Design - Tablet */
    @media (max-width: 1024px) {
        :root {
            --sidebar-width: 240px;
        }
        
        .nav-icon-container {
            margin-right: 0.5rem;
        }
        
        .sidebar-header,
        .search-container,
        .sidebar-footer,
        .user-profile {
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }
    
    /* Responsive Design - Mobile */
    @media (max-width: 768px) {
        .admin-sidebar {
            transform: translateX(-100%);
            box-shadow: none;
        }
        
        .admin-sidebar.open {
            transform: translateX(0);
            box-shadow: var(--shadow-dark);
        }
        
        .mobile-toggle-btn {
            display: flex;
        }
        
        .collapse-btn {
            display: block;
        }
        
        .sidebar-header {
            padding-top: 1.5rem;
        }
    }
    
    /* Collapsed Sidebar (Optional) */
    .admin-sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }
    
    .admin-sidebar.collapsed .logo-text,
    .admin-sidebar.collapsed .nav-text,
    .admin-sidebar.collapsed .nav-section-title,
    .admin-sidebar.collapsed .footer-link span,
    .admin-sidebar.collapsed .user-info,
    .admin-sidebar.collapsed .user-actions,
    .admin-sidebar.collapsed .search-container {
        display: none;
    }
    
    .admin-sidebar.collapsed .nav-link {
        justify-content: center;
    }
    
    .admin-sidebar.collapsed .nav-icon-container {
        margin-right: 0;
    }
    
    .admin-sidebar.collapsed .nav-badge {
        position: absolute;
        top: 0.25rem;
        right: 0.25rem;
        width: 1rem;
        height: 1rem;
        font-size: 0.625rem;
    }
    
    .admin-sidebar.collapsed .user-profile {
        justify-content: center;
        padding: 1rem 0;
    }
    
    .admin-sidebar.collapsed .sidebar-footer {
        align-items: center;
    }
    
    .admin-sidebar.collapsed .footer-link {
        justify-content: center;
        padding: 0.75rem;
    }
    
    .admin-sidebar.collapsed .footer-icon {
        margin-right: 0;
    }
    
    /* Animation for mobile sidebar */
    @keyframes slideIn {
        0% {
            transform: translateX(-100%);
            opacity: 0;
        }
        100% {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .admin-sidebar.open {
        animation: slideIn 0.3s forwards;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get DOM elements
        const sidebar = document.getElementById('adminSidebar');
        const collapseBtn = document.getElementById('sidebarCollapseBtn');
        const mobileToggleBtn = document.getElementById('mobileSidebarToggle');
        
        // Handle sidebar toggle on mobile
        if (mobileToggleBtn) {
            mobileToggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                
                // Change toggle button icon based on sidebar state
                if (sidebar.classList.contains('open')) {
                    this.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    `;
                } else {
                    this.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    `;
                }
            });
        }
        
        // Handle sidebar collapse button on mobile
        if (collapseBtn) {
            collapseBtn.addEventListener('click', function() {
                sidebar.classList.remove('open');
            });
        }
        
        // Optional: Collapse sidebar on wider screens with keyboard shortcut or double-click
        document.addEventListener('keydown', function(e) {
            // Alt + S to toggle sidebar collapse
            if (e.altKey && e.key === 's') {
                sidebar.classList.toggle('collapsed');
            }
        });
        
        // Double-click on header to collapse/expand sidebar
        const sidebarHeader = document.querySelector('.sidebar-header');
        if (sidebarHeader) {
            sidebarHeader.addEventListener('dblclick', function() {
                sidebar.classList.toggle('collapsed');
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            // Only apply this on mobile screens
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !mobileToggleBtn.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
            }
        });
        
        // Optional: Save sidebar state in localStorage
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState === 'true') {
            sidebar.classList.add('collapsed');
        }
        
        // Update localStorage when sidebar state changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    const isCollapsed = sidebar.classList.contains('collapsed');
                    localStorage.setItem('sidebarCollapsed', isCollapsed);
                }
            });
        });
        
        // Start observing the sidebar for class changes
        observer.observe(sidebar, { attributes: true });
    });
</script>