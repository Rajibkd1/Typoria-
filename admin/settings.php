<?php
/**
 * Typoria Blog Platform
 * Admin - Site Settings
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

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $site_name = sanitize_input($_POST['site_name'] ?? '');
    $site_tagline = sanitize_input($_POST['site_tagline'] ?? '');
    $site_description = sanitize_input($_POST['site_description'] ?? '');
    $contact_email = filter_var($_POST['contact_email'] ?? '', FILTER_SANITIZE_EMAIL);
    
    $social_facebook = sanitize_input($_POST['social_facebook'] ?? '');
    $social_twitter = sanitize_input($_POST['social_twitter'] ?? '');
    $social_instagram = sanitize_input($_POST['social_instagram'] ?? '');
    $social_github = sanitize_input($_POST['social_github'] ?? '');
    
    $posts_per_page = (int)($_POST['posts_per_page'] ?? 9);
    $enable_comments = isset($_POST['enable_comments']) ? 1 : 0;
    $enable_likes = isset($_POST['enable_likes']) ? 1 : 0;
    $enable_sharing = isset($_POST['enable_sharing']) ? 1 : 0;
    $enable_newsletter = isset($_POST['enable_newsletter']) ? 1 : 0;
    $enable_dark_mode = isset($_POST['enable_dark_mode']) ? 1 : 0;
    
    // Validate inputs
    $errors = [];
    
    if (empty($site_name)) {
        $errors[] = "Site name is required";
    }
    
    if (empty($site_tagline)) {
        $errors[] = "Site tagline is required";
    }
    
    if (empty($site_description)) {
        $errors[] = "Site description is required";
    }
    
    if (!empty($contact_email) && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid contact email format";
    }
    
    if ($posts_per_page < 1) {
        $errors[] = "Posts per page must be at least 1";
    }
    
    // If no errors, update settings in config file
    if (empty($errors)) {
        // Path to theme.php file
        $theme_file = '../includes/theme.php';
        
        // Read the current file content
        $content = file_get_contents($theme_file);
        
        // Update site configuration
        $content = preg_replace(
            "/\'site_name\' => \'(.*?)\'/",
            "'site_name' => '" . addslashes($site_name) . "'",
            $content
        );
        
        $content = preg_replace(
            "/\'site_tagline\' => \'(.*?)\'/",
            "'site_tagline' => '" . addslashes($site_tagline) . "'",
            $content
        );
        
        $content = preg_replace(
            "/\'site_description\' => \'(.*?)\'/",
            "'site_description' => '" . addslashes($site_description) . "'",
            $content
        );
        
        $content = preg_replace(
            "/\'logo_text\' => \'(.*?)\'/",
            "'logo_text' => '" . addslashes($site_name) . "'",
            $content
        );
        
        $content = preg_replace(
            "/\'posts_per_page\' => (.*?),/",
            "'posts_per_page' => " . $posts_per_page . ",",
            $content
        );
        
        $content = preg_replace(
            "/\'enable_comments\' => (.*?),/",
            "'enable_comments' => " . ($enable_comments ? 'true' : 'false') . ",",
            $content
        );
        
        $content = preg_replace(
            "/\'enable_likes\' => (.*?),/",
            "'enable_likes' => " . ($enable_likes ? 'true' : 'false') . ",",
            $content
        );
        
        $content = preg_replace(
            "/\'enable_sharing\' => (.*?),/",
            "'enable_sharing' => " . ($enable_sharing ? 'true' : 'false') . ",",
            $content
        );
        
        $content = preg_replace(
            "/\'enable_newsletter\' => (.*?),/",
            "'enable_newsletter' => " . ($enable_newsletter ? 'true' : 'false') . ",",
            $content
        );
        
        $content = preg_replace(
            "/\'enable_dark_mode\' => (.*?),/",
            "'enable_dark_mode' => " . ($enable_dark_mode ? 'true' : 'false') . ",",
            $content
        );
        
        $content = preg_replace(
            "/\'contact_email\' => \'(.*?)\'/",
            "'contact_email' => '" . addslashes($contact_email) . "'",
            $content
        );
        
        // Update social media links
        $content = preg_replace(
            "/\'facebook\' => \'(.*?)\'/",
            "'facebook' => '" . addslashes($social_facebook) . "'",
            $content
        );
        
        $content = preg_replace(
            "/\'twitter\' => \'(.*?)\'/",
            "'twitter' => '" . addslashes($social_twitter) . "'",
            $content
        );
        
        $content = preg_replace(
            "/\'instagram\' => \'(.*?)\'/",
            "'instagram' => '" . addslashes($social_instagram) . "'",
            $content
        );
        
        $content = preg_replace(
            "/\'github\' => \'(.*?)\'/",
            "'github' => '" . addslashes($social_github) . "'",
            $content
        );
        
        // Write updated content back to file
        if (file_put_contents($theme_file, $content)) {
            $message = "Settings updated successfully!";
            $message_type = "success";
            
            // Reload the theme configuration to reflect changes
            require_once '../includes/theme.php';
        } else {
            $message = "Error updating settings file. Please check file permissions.";
            $message_type = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}

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
    
    .settings-section {
        @apply border-b border-gray-200 pb-6 mb-6 last:border-b-0 last:pb-0 last:mb-0;
    }
    
    .settings-section h3 {
        @apply text-lg font-medium text-gray-800 mb-4;
    }
    
    .toggle-checkbox:checked {
        @apply right-0 border-typoria-primary;
    }
    
    .toggle-checkbox:checked + .toggle-label {
        @apply bg-typoria-primary;
    }
";

// Page title
$page_title = "Site Settings";

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
                <h1 class="text-2xl font-bold text-gray-800">Site Settings</h1>
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
            
            <!-- Settings Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <!-- General Settings -->
                    <div class="settings-section">
                        <h3>General Settings</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Site Name <span class="text-red-500">*</span></label>
                                <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($TYPORIA_CONFIG['site_name']); ?>" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20"
                                    required>
                            </div>
                            
                            <div>
                                <label for="site_tagline" class="block text-sm font-medium text-gray-700 mb-1">Site Tagline <span class="text-red-500">*</span></label>
                                <input type="text" id="site_tagline" name="site_tagline" value="<?php echo htmlspecialchars($TYPORIA_CONFIG['site_tagline']); ?>" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20"
                                    required>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="site_description" class="block text-sm font-medium text-gray-700 mb-1">Site Description <span class="text-red-500">*</span></label>
                                <textarea id="site_description" name="site_description" rows="3" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20"
                                    required><?php echo htmlspecialchars($TYPORIA_CONFIG['site_description']); ?></textarea>
                                <p class="mt-1 text-xs text-gray-500">Brief description of your site (used in meta tags for SEO)</p>
                            </div>
                            
                            <div>
                                <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                                <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($TYPORIA_CONFIG['contact_email']); ?>" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20">
                            </div>
                            
                            <div>
                                <label for="posts_per_page" class="block text-sm font-medium text-gray-700 mb-1">Posts Per Page</label>
                                <input type="number" id="posts_per_page" name="posts_per_page" value="<?php echo (int)$TYPORIA_CONFIG['posts_per_page']; ?>" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20"
                                    min="1" max="50">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media Links -->
                    <div class="settings-section">
                        <h3>Social Media Links</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="social_facebook" class="block text-sm font-medium text-gray-700 mb-1">Facebook URL</label>
                                <input type="url" id="social_facebook" name="social_facebook" value="<?php echo htmlspecialchars($TYPORIA_SOCIAL['facebook']); ?>" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20">
                            </div>
                            
                            <div>
                                <label for="social_twitter" class="block text-sm font-medium text-gray-700 mb-1">Twitter URL</label>
                                <input type="url" id="social_twitter" name="social_twitter" value="<?php echo htmlspecialchars($TYPORIA_SOCIAL['twitter']); ?>" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20">
                            </div>
                            
                            <div>
                                <label for="social_instagram" class="block text-sm font-medium text-gray-700 mb-1">Instagram URL</label>
                                <input type="url" id="social_instagram" name="social_instagram" value="<?php echo htmlspecialchars($TYPORIA_SOCIAL['instagram']); ?>" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20">
                            </div>
                            
                            <div>
                                <label for="social_github" class="block text-sm font-medium text-gray-700 mb-1">GitHub URL</label>
                                <input type="url" id="social_github" name="social_github" value="<?php echo htmlspecialchars($TYPORIA_SOCIAL['github']); ?>" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Feature Toggles -->
                    <div class="settings-section">
                        <h3>Feature Settings</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center">
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="enable_comments" name="enable_comments" 
                                        class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"
                                        <?php echo $TYPORIA_CONFIG['enable_comments'] ? 'checked' : ''; ?>>
                                    <label for="enable_comments" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                                <label for="enable_comments" class="text-sm text-gray-700">Enable Comments</label>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="enable_likes" name="enable_likes" 
                                        class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"
                                        <?php echo $TYPORIA_CONFIG['enable_likes'] ? 'checked' : ''; ?>>
                                    <label for="enable_likes" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                                <label for="enable_likes" class="text-sm text-gray-700">Enable Likes</label>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="enable_sharing" name="enable_sharing" 
                                        class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"
                                        <?php echo $TYPORIA_CONFIG['enable_sharing'] ? 'checked' : ''; ?>>
                                    <label for="enable_sharing" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                                <label for="enable_sharing" class="text-sm text-gray-700">Enable Social Sharing</label>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="enable_newsletter" name="enable_newsletter" 
                                        class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"
                                        <?php echo $TYPORIA_CONFIG['enable_newsletter'] ? 'checked' : ''; ?>>
                                    <label for="enable_newsletter" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                                <label for="enable_newsletter" class="text-sm text-gray-700">Enable Newsletter</label>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="enable_dark_mode" name="enable_dark_mode" 
                                        class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"
                                        <?php echo $TYPORIA_CONFIG['enable_dark_mode'] ? 'checked' : ''; ?>>
                                    <label for="enable_dark_mode" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                                <label for="enable_dark_mode" class="text-sm text-gray-700">Enable Dark Mode</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end mt-6">
                        <button type="submit" class="bg-gradient-to-r from-typoria-primary to-typoria-secondary hover:from-typoria-secondary hover:to-typoria-primary text-white px-6 py-3 rounded-md font-medium transition-all duration-300 shadow-md hover:shadow-lg">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Additional Settings Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <!-- Categories Management -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Categories</h3>
                            <p class="text-sm text-gray-600 mt-1">Manage post categories</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-typoria-primary opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    
                    <p class="text-gray-600 mb-4">Organize your content with categories to help users navigate and find related posts.</p>
                    
                    <a href="manage_categories.php" class="inline-block bg-typoria-primary hover:bg-typoria-primary/90 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Manage Categories
                    </a>
                </div>
                
                <!-- Backup & Restore -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Backup & Restore</h3>
                            <p class="text-sm text-gray-600 mt-1">Database backup and restore</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-typoria-primary opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                    </div>
                    
                    <p class="text-gray-600 mb-4">Backup your database regularly to prevent data loss. You can also restore from a previous backup.</p>
                    
                    <div class="flex space-x-3">
                        <a href="backup.php" class="inline-block bg-typoria-primary hover:bg-typoria-primary/90 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Backup Database
                        </a>
                        <a href="restore.php" class="inline-block bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Restore Database
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // Toggle switch styling
    const toggleCheckboxes = document.querySelectorAll('.toggle-checkbox');
    
    toggleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            
            if (this.checked) {
                label.classList.add('bg-typoria-primary');
                label.classList.remove('bg-gray-300');
            } else {
                label.classList.remove('bg-typoria-primary');
                label.classList.add('bg-gray-300');
            }
        });
    });
</script>

<?php
// Do not include the standard footer for admin pages
?>
</body>
</html>