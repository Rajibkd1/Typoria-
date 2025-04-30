<?php
/**
 * Typoria Blog Platform
 * Theme Configuration
 */

// Theme Information
$TYPORIA_THEME = [
    'name' => 'Typoria',
    'version' => '1.0.0',
    'description' => 'Modern blogging platform theme for Typoria',
    'author' => 'Rajib Kumar',
];

// Color Scheme
$TYPORIA_COLORS = [
    'primary' => '#3B82F6',      // Blue-500
    'secondary' => '#8B5CF6',    // Violet-500
    'accent' => '#10B981',       // Emerald-500
    'dark' => '#1E293B',         // Slate-800
    'light' => '#F8FAFC',        // Slate-50
    'gray' => '#64748B',         // Slate-500
    'error' => '#EF4444',        // Red-500
    'success' => '#22C55E',      // Green-500
    'warning' => '#F59E0B',      // Amber-500
    'info' => '#3B82F6',         // Blue-500
];

// Site Configuration
$TYPORIA_CONFIG = [
    'site_name' => 'Typoria',
    'site_tagline' => 'Express Your Thoughts',
    'site_description' => 'A modern blogging platform for sharing ideas and stories',
    'logo_text' => 'Typoria',
    'posts_per_page' => 9,
    'featured_posts_count' => 3,
    'related_posts_count' => 3,
    'enable_comments' => true,
    'enable_likes' => true,
    'enable_sharing' => true,
    'enable_newsletter' => true,
    'enable_dark_mode' => true,
    'contact_email' => 'contact@typoria.com',
];

// Social Media Links
$TYPORIA_SOCIAL = [
    'facebook' => 'https://facebook.com/typoria',
    'twitter' => 'https://twitter.com/typoria',
    'instagram' => 'https://instagram.com/typoria',
    'github' => 'https://github.com/typoria',
];

// Menu Configuration
$TYPORIA_MENU = [
    [
        'title' => 'Home',
        'url' => 'index.php',
        'icon' => 'home'
    ],
    [
        'title' => 'Categories',
        'url' => 'categories.php',
        'icon' => 'folder',
        'dropdown' => true
    ],
    [
        'title' => 'Write',
        'url' => 'create_post.php',
        'icon' => 'edit',
        'auth_required' => true
    ],
    [
        'title' => 'My Posts',
        'url' => 'my_posts.php',
        'icon' => 'file-text',
        'auth_required' => true
    ],
    [
        'title' => 'Bookmarks',
        'url' => 'bookmarks.php',
        'icon' => 'bookmark',
        'auth_required' => true
    ],
    [
        'title' => 'About',
        'url' => '#',
        'icon' => 'info'
    ],
    [
        'title' => 'Contact',
        'url' => '#',
        'icon' => 'mail'
    ],
];

// Footer Links
$TYPORIA_FOOTER = [
    [
        'title' => 'About',
        'url' => '#'
    ],
    [
        'title' => 'Features',
        'url' => '#'
    ],
    [
        'title' => 'Privacy Policy',
        'url' => '#'
    ],
    [
        'title' => 'Terms of Service',
        'url' => '#'
    ],
    [
        'title' => 'Contact',
        'url' => '#'
    ],
];

// Category Icons (for visual representation)
$TYPORIA_CATEGORY_ICONS = [
    'Technology' => 'laptop',
    'Lifestyle' => 'coffee',
    'Travel' => 'map-pin',
    'Food' => 'utensils',
    'Health' => 'heart',
    'Business' => 'briefcase',
    'Education' => 'book',
    'Entertainment' => 'film',
    'Science' => 'flask',
    'Arts' => 'image',
];

// Tailwind Configuration
$TYPORIA_TAILWIND = '
tailwind.config = {
    theme: {
        extend: {
            colors: {
                typoria: {
                    primary: \''.$TYPORIA_COLORS['primary'].'\',
                    secondary: \''.$TYPORIA_COLORS['secondary'].'\',
                    accent: \''.$TYPORIA_COLORS['accent'].'\',
                    dark: \''.$TYPORIA_COLORS['dark'].'\',
                    light: \''.$TYPORIA_COLORS['light'].'\',
                }
            },
            fontFamily: {
                sans: [\'Inter\', \'sans-serif\'],
                serif: [\'Merriweather\', \'serif\'],
            }
        }
    }
}
';

/**
 * Generate the HTML header with enhanced title and inline SVG favicon
 * 
 * @param string $page_title The title of the current page (optional)
 * @param string $additional_css Additional CSS to include (optional)
 * @param string $additional_js Additional JavaScript to include (optional)
 * @return void Outputs the HTML header
 */
function typoria_header($page_title = '', $additional_css = '', $additional_js = '') {
    global $TYPORIA_COLORS, $TYPORIA_TAILWIND, $TYPORIA_CONFIG;
    
    // Build the page title
    $title = !empty($page_title) 
        ? htmlspecialchars($page_title) . ' | Typoria' 
        : 'Typoria - Where Words Come to Life';

    // SVG Favicon data (pen icon)
    $svg_favicon = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none">
  <circle cx="50" cy="50" r="48" fill="#7c3aed"/>
  <path d="M65 25l-25 25M60 20l2 4L66 28 70 30l-8 8-20-20l8-8 3 4L57 20" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M40 45L30 55l-8 3 3-8 10-10M30 55l12 12" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
</svg>';
    
    // Begin HTML header
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $title . '</title>
    <meta name="description" content="' . htmlspecialchars($TYPORIA_CONFIG['site_description']) . '">
    
    <!-- Inline SVG Favicon for modern browsers -->
    <link rel="icon" href="data:image/svg+xml;base64,' . base64_encode($svg_favicon) . '">
    
    <!-- Fallback favicons for different devices -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="assets/favicon/site.webmanifest">
    <link rel="mask-icon" href="assets/favicon/safari-pinned-tab.svg" color="#7c3aed">
    <link rel="shortcut icon" href="assets/favicon/favicon.ico">
    <meta name="msapplication-TileColor" content="#7c3aed">
    <meta name="theme-color" content="#ffffff">
    
    <!-- Preconnect for Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        ' . $TYPORIA_TAILWIND . '
    </script>
    
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@400;700&family=Dancing+Script:wght@700&display=swap">
    
    <!-- Base Styles -->
    <style>
        /* Base Styles */
        body {
            font-family: "Inter", sans-serif;
        }
        
        .logo-text {
            background: linear-gradient(135deg, '.$TYPORIA_COLORS['primary'].', '.$TYPORIA_COLORS['secondary'].');
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .gradient-button {
            background: linear-gradient(135deg, '.$TYPORIA_COLORS['primary'].', '.$TYPORIA_COLORS['secondary'].');
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .gradient-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        /* Human-written logo style */
        .human-written-logo {
            font-family: "Dancing Script", cursive;
            font-weight: 700;
            color: white;
            position: relative;
        }
        
        .human-written-logo::after {
            content: "";
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, '.$TYPORIA_COLORS['primary'].', '.$TYPORIA_COLORS['secondary'].');
            border-radius: 2px;
            transform-origin: left;
            transform: scaleX(0.7);
            transition: transform 0.3s ease;
        }
        
        .human-written-logo:hover::after {
            transform: scaleX(1);
        }
        
        /* Pen icon styles */
        .pen-icon {
            width: 32px;
            height: 32px;
            margin-right: 10px;
            filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.2));
            transition: all 0.3s ease;
        }
        
        ' . $additional_css . '
    </style>
    
    ' . $additional_js . '
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
';
}


// Function to generate footer
function typoria_footer($additional_js = '') {
    global $TYPORIA_CONFIG, $TYPORIA_SOCIAL, $TYPORIA_FOOTER;
    
    echo '
    <!-- Footer -->
    <footer class="bg-white py-12 mt-auto">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-6 md:mb-0">
                    <div class="text-2xl font-bold">
                        <span class="logo-text">' . htmlspecialchars($TYPORIA_CONFIG['logo_text']) . '</span>
                    </div>
                    <p class="text-gray-600 mt-2">' . htmlspecialchars($TYPORIA_CONFIG['site_tagline']) . '</p>
                </div>
                
                <div class="flex flex-wrap justify-center gap-4">';
                
    foreach ($TYPORIA_FOOTER as $item) {
        echo '<a href="' . htmlspecialchars($item['url']) . '" class="text-gray-600 hover:text-typoria-primary">' . htmlspecialchars($item['title']) . '</a>';
    }
    
    echo '</div>
                
                <div class="mt-6 md:mt-0">
                    <div class="flex space-x-4">';
    
    if (!empty($TYPORIA_SOCIAL['facebook'])) {
        echo '<a href="' . htmlspecialchars($TYPORIA_SOCIAL['facebook']) . '" class="text-gray-600 hover:text-typoria-primary" target="_blank" rel="noopener noreferrer">
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                </svg>
            </a>';
    }
    
    if (!empty($TYPORIA_SOCIAL['twitter'])) {
        echo '<a href="' . htmlspecialchars($TYPORIA_SOCIAL['twitter']) . '" class="text-gray-600 hover:text-typoria-primary" target="_blank" rel="noopener noreferrer">
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                </svg>
            </a>';
    }
    
    if (!empty($TYPORIA_SOCIAL['instagram'])) {
        echo '<a href="' . htmlspecialchars($TYPORIA_SOCIAL['instagram']) . '" class="text-gray-600 hover:text-typoria-primary" target="_blank" rel="noopener noreferrer">
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" />
                </svg>
            </a>';
    }
    
    if (!empty($TYPORIA_SOCIAL['github'])) {
        echo '<a href="' . htmlspecialchars($TYPORIA_SOCIAL['github']) . '" class="text-gray-600 hover:text-typoria-primary" target="_blank" rel="noopener noreferrer">
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                </svg>
            </a>';
    }
    
    echo '</div>
                </div>
            </div>
            
            <div class="mt-8 pt-8 border-t border-gray-200 text-center">
                <p class="text-gray-500 text-sm">&copy; ' . date('Y') . ' ' . htmlspecialchars($TYPORIA_CONFIG['site_name']) . '. All rights reserved.</p>
            </div>
        </div>
    </footer>
    ' . $additional_js . '
</body>
</html>';
}

/**
 * Creates a flash message to be displayed on the next page load
 *
 * @param string $message The message to display
 * @param string $type The type of message (success, error, info, warning)
 * @param bool $overwrite Whether to overwrite existing messages (default: false)
 * @return void
 */
function typoria_flash_message($message, $type = 'info', $overwrite = false) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Initialize the flash messages array if it doesn't exist
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    // Clear existing messages if overwrite is true
    if ($overwrite) {
        $_SESSION['flash_messages'] = [];
    }
    
    // Validate message type
    $valid_types = ['success', 'error', 'warning', 'info'];
    if (!in_array($type, $valid_types)) {
        $type = 'info';
    }
    
    // Add the message to the session
    $_SESSION['flash_messages'][] = [
        'message' => $message,
        'type' => $type,
        'created' => time()
    ];
}

/**
 * Displays flash messages and clears them from the session
 *
 * @return string HTML markup for flash messages
 */
function typoria_display_flash_messages() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // If no messages exist, return empty string
    if (empty($_SESSION['flash_messages'])) {
        return '';
    }
    
    $output = '<div class="flash-messages">';
    
    // Loop through each message
    foreach ($_SESSION['flash_messages'] as $message) {
        $type_class = $message['type'];
        
        // Build CSS classes based on message type
        switch ($message['type']) {
            case 'success':
                $bg_color = 'bg-green-100 border-green-500 text-green-700';
                $icon = '<svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
                break;
            case 'error':
                $bg_color = 'bg-red-100 border-red-500 text-red-700';
                $icon = '<svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
                break;
            case 'warning':
                $bg_color = 'bg-yellow-100 border-yellow-500 text-yellow-700';
                $icon = '<svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                break;
            case 'info':
            default:
                $bg_color = 'bg-blue-100 border-blue-500 text-blue-700';
                $icon = '<svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
                break;
        }
        
        // Build the message HTML
        $output .= '<div class="flash-message rounded-md border-l-4 p-4 mb-4 ' . $bg_color . '" role="alert">';
        $output .= '<div class="flex items-center">';
        $output .= $icon;
        $output .= '<span>' . htmlspecialchars($message['message']) . '</span>';
        $output .= '</div>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    // Clear the messages from the session
    $_SESSION['flash_messages'] = [];
    
    return $output;
}

// Function to display pagination
function typoria_pagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) return;
    
    echo '<div class="flex justify-center my-8">
        <div class="inline-flex rounded-md shadow">
            <div class="inline-flex">';
    
    // Previous button
    if ($current_page > 1) {
        echo '<a href="' . $base_url . '?page=' . ($current_page - 1) . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">Previous</a>';
    } else {
        echo '<span class="px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-l-md cursor-not-allowed">Previous</span>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($start + 4, $total_pages);
    
    if ($start > 1) {
        echo '<a href="' . $base_url . '?page=1" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300 hover:bg-gray-50">1</a>';
        if ($start > 2) {
            echo '<span class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            echo '<span class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border-t border-b border-gray-300">' . $i . '</span>';
        } else {
            echo '<a href="' . $base_url . '?page=' . $i . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300 hover:bg-gray-50">' . $i . '</a>';
        }
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            echo '<span class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300">...</span>';
        }
        echo '<a href="' . $base_url . '?page=' . $total_pages . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300 hover:bg-gray-50">' . $total_pages . '</a>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        echo '<a href="' . $base_url . '?page=' . ($current_page + 1) . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">Next</a>';
    } else {
        echo '<span class="px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-r-md cursor-not-allowed">Next</span>';
    }
    
    echo '</div>
        </div>
    </div>';
}

// Function to display a post card
function typoria_post_card($post, $card_size = 'medium') {
    $image_path = './uploads/' . $post['image'];
    $date_formatted = format_date($post['date_time'], false);
    
    // Different card layouts based on size
    switch ($card_size) {
        case 'small':
            echo '
            <div class="post-card bg-white rounded-lg overflow-hidden shadow-md transition-all duration-300 hover:shadow-lg">
                <a href="post_view.php?post_id=' . $post['post_id'] . '" class="block">
                    <div class="relative overflow-hidden h-40">
                        <img class="w-full h-full object-cover transform transition-transform duration-500 hover:scale-110" src="' . $image_path . '" alt="' . htmlspecialchars($post['title']) . '">
                        <div class="absolute top-2 right-2">
                            <span class="bg-typoria-secondary text-white text-xs px-2 py-1 rounded-full">' . htmlspecialchars($post['category']) . '</span>
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1 truncate">' . htmlspecialchars($post['title']) . '</h3>
                        <p class="text-xs text-gray-500">By ' . htmlspecialchars($post['user_name']) . ' • ' . $date_formatted . '</p>
                    </div>
                </a>
            </div>';
            break;
            
        case 'featured':
            echo '
            <div class="post-card bg-white rounded-xl overflow-hidden shadow-lg transition-all duration-300 hover:shadow-xl transform hover:-translate-y-2">
                <div class="relative">
                    <a href="post_view.php?post_id=' . $post['post_id'] . '" class="block">
                        <div class="relative overflow-hidden h-80">
                            <img class="w-full h-full object-cover transform transition-transform duration-500 hover:scale-105" src="' . $image_path . '" alt="' . htmlspecialchars($post['title']) . '">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex flex-col justify-end p-6">
                                <span class="inline-block bg-typoria-secondary text-white text-xs px-3 py-1 rounded-full mb-3">' . htmlspecialchars($post['category']) . '</span>
                                <h3 class="text-2xl font-bold text-white mb-2">' . htmlspecialchars($post['title']) . '</h3>
                                <p class="text-gray-300 text-sm mb-1">By ' . htmlspecialchars($post['user_name']) . ' • ' . $date_formatted . '</p>
                                <div class="flex space-x-4 mt-3">
                                    <div class="flex items-center text-gray-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        <span>' . $post['like_count'] . '</span>
                                    </div>
                                    <div class="flex items-center text-gray-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <span>' . $post['comment_count'] . '</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>';
            break;
            
        case 'medium':
        default:
            echo '
            <div class="post-card bg-white rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <a href="post_view.php?post_id=' . $post['post_id'] . '" class="block">
                    <div class="relative overflow-hidden h-56">
                        <img class="w-full h-full object-cover transform transition-transform duration-500 hover:scale-110" src="' . $image_path . '" alt="' . htmlspecialchars($post['title']) . '">
                        <div class="absolute top-3 right-3">
                            <span class="bg-typoria-secondary text-white text-xs px-3 py-1 rounded-full">' . htmlspecialchars($post['category']) . '</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-2 line-clamp-2">' . htmlspecialchars($post['title']) . '</h3>
                        <p class="text-sm text-gray-500 mb-4">By <span class="font-medium text-typoria-primary">' . htmlspecialchars($post['user_name']) . '</span> • ' . $date_formatted . '</p>
                        
                        <div class="flex justify-between items-center mt-2">
                            <div class="flex space-x-4">
                                <div class="flex items-center text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                    </svg>
                                    <span>' . $post['like_count'] . '</span>
                                </div>
                                <div class="flex items-center text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd" />
                                    </svg>
                                    <span>' . $post['comment_count'] . '</span>
                                </div>
                            </div>
                            <span class="text-typoria-primary hover:text-typoria-secondary font-medium">Read More</span>
                        </div>
                    </div>
                </a>
            </div>';
    }
}

// Function to display author card
function typoria_author_card($user_id) {
    global $TYPORIA_COLORS;
    
    $conn = get_db_connection();
    $sql = "SELECT u.*, 
            (SELECT COUNT(*) FROM posts WHERE user_id = u.user_id) AS post_count,
            (SELECT COUNT(*) FROM followers WHERE followed_user_id = u.user_id) AS follower_count
            FROM users u 
            WHERE u.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) return;
    
    $author = $result->fetch_assoc();
    $auth = check_auth();
    
    // Check if current user is following this author
    $is_following = false;
    if ($auth['isLoggedIn'] && $auth['user_id'] != $user_id) {
        $sql = "SELECT follower_id FROM followers WHERE follower_user_id = ? AND followed_user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $auth['user_id'], $user_id);
        $stmt->execute();
        $follow_result = $stmt->get_result();
        $is_following = ($follow_result->num_rows > 0);
    }
    
    echo '
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center">';
    
    // Check if user has a profile image
    if (!empty($author['profile_image']) && $author['profile_image'] != 'default.png') {
        // Display actual profile image
        echo '<div class="h-16 w-16 rounded-full overflow-hidden">
                <img src="uploads/profiles/' . htmlspecialchars($author['profile_image']) . '" alt="' . htmlspecialchars($author['name']) . '" class="h-full w-full object-cover">
              </div>';
    } else {
        // Fall back to initial letter with gradient background
        echo '<div class="h-16 w-16 rounded-full bg-gradient-to-r from-typoria-primary to-typoria-secondary flex items-center justify-center text-white text-2xl font-bold">
                ' . strtoupper(substr($author['name'], 0, 1)) . '
              </div>';
    }
    
    echo '<div class="ml-4">
                <h3 class="text-xl font-bold text-gray-800">' . htmlspecialchars($author['name']) . '</h3>
                <div class="flex items-center text-sm text-gray-600 mt-1">
                    <span class="mr-3">' . $author['post_count'] . ' posts</span>
                    <span>' . $author['follower_count'] . ' followers</span>
                </div>
            </div>
        </div>';
        
    if (!empty($author['bio'])) {
        echo '<p class="text-gray-700 mt-4">' . htmlspecialchars($author['bio']) . '</p>';
    }
    
    // Rest of the function remains the same...
    echo '<div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">';
    
    
    // Social links
    echo '<div class="flex space-x-3">';
    if (!empty($author['social_twitter'])) {
        echo '<a href="' . htmlspecialchars($author['social_twitter']) . '" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:text-blue-600">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
            </svg>
        </a>';
    }
    
    if (!empty($author['social_facebook'])) {
        echo '<a href="' . htmlspecialchars($author['social_facebook']) . '" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
            </svg>
        </a>';
    }
    
    if (!empty($author['social_instagram'])) {
        echo '<a href="' . htmlspecialchars($author['social_instagram']) . '" target="_blank" rel="noopener noreferrer" class="text-pink-600 hover:text-pink-800">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" />
            </svg>
        </a>';
    }
    
    if (!empty($author['website'])) {
        echo '<a href="' . htmlspecialchars($author['website']) . '" target="_blank" rel="noopener noreferrer" class="text-gray-600 hover:text-gray-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
            </svg>
        </a>';
    }
    echo '</div>';
    
    // Follow button (if logged in and not self)
    if ($auth['isLoggedIn'] && $auth['user_id'] != $user_id) {
        if ($is_following) {
            echo '<form method="POST" action="follow.php">
                <input type="hidden" name="user_id" value="' . $user_id . '">
                <input type="hidden" name="action" value="unfollow">
                <button type="submit" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-medium hover:bg-gray-400 transition-colors">Unfollow</button>
            </form>';
        } else {
            echo '<form method="POST" action="follow.php">
                <input type="hidden" name="user_id" value="' . $user_id . '">
                <button type="submit" class="gradient-button text-white px-4 py-2 rounded-lg font-medium">Follow</button>
            </form>';
        }
    } else if (!$auth['isLoggedIn']) {
        echo '<a href="login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '" class="text-typoria-primary hover:text-typoria-secondary font-medium">Login to follow</a>';
    }
    
    echo '</div>
    </div>';
}

// Function to display like and bookmark buttons
function typoria_post_actions($post_id, $like_count, $user_id = null) {
    $liked = false;
    $bookmarked = false;
    
    if ($user_id) {
        $liked = has_user_liked_post($post_id, $user_id);
        $bookmarked = has_user_bookmarked_post($post_id, $user_id);
    }
    
    echo '<div class="flex space-x-4 items-center">';
    
    // Like button
    if ($user_id) {
        echo '<form method="POST" action="like_post.php" class="inline">
            <input type="hidden" name="post_id" value="' . $post_id . '">
            <input type="hidden" name="action" value="' . ($liked ? 'unlike' : 'like') . '">
            <button type="submit" class="flex items-center space-x-2 ' . ($liked ? 'text-red-500' : 'text-gray-500 hover:text-red-500') . ' transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" ' . ($liked ? 'fill="currentColor"' : 'fill="none" stroke="currentColor" stroke-width="2"') . ' viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span>' . ($liked ? 'Liked' : 'Like') . ' (' . $like_count . ')</span>
            </button>
        </form>';
    } else {
        echo '<a href="login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '" class="flex items-center space-x-2 text-gray-500 hover:text-red-500 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            <span>Like (' . $like_count . ')</span>
        </a>';
    }
    
    // Bookmark button
    if ($user_id) {
        echo '<form method="POST" action="bookmark_post.php" class="inline">
            <input type="hidden" name="post_id" value="' . $post_id . '">
            <input type="hidden" name="action" value="' . ($bookmarked ? 'remove' : 'add') . '">
            <button type="submit" class="flex items-center space-x-2 ' . ($bookmarked ? 'text-typoria-primary' : 'text-gray-500 hover:text-typoria-primary') . ' transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" ' . ($bookmarked ? 'fill="currentColor"' : 'fill="none" stroke="currentColor" stroke-width="2"') . ' viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                <span>' . ($bookmarked ? 'Bookmarked' : 'Bookmark') . '</span>
            </button>
        </form>';
    } else {
        echo '<a href="login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '" class="flex items-center space-x-2 text-gray-500 hover:text-typoria-primary transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
            </svg>
            <span>Bookmark</span>
        </a>';
    }
    
    // Share button
    echo '<button type="button" onclick="sharePost()" class="flex items-center space-x-2 text-gray-500 hover:text-typoria-secondary transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
        </svg>
        <span>Share</span>
    </button>';
    
    echo '</div>';
    
    // Share script
    echo '<script>
    function sharePost() {
        if (navigator.share) {
            navigator.share({
                title: document.title,
                url: window.location.href
            }).then(() => {
                console.log("Thanks for sharing!");
            }).catch(console.error);
        } else {
            // Fallback
            const dummy = document.createElement("input");
            document.body.appendChild(dummy);
            dummy.value = window.location.href;
            dummy.select();
            document.execCommand("copy");
            document.body.removeChild(dummy);
            alert("Link copied to clipboard!");
        }
    }
    </script>';
}

// Function to display social share buttons
function typoria_social_share_buttons($url, $title) {
    $encoded_url = urlencode($url);
    $encoded_title = urlencode($title);
    
    echo '<div class="flex space-x-2">';
    
    // Facebook
    echo '<a href="https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url . '" target="_blank" rel="noopener noreferrer" class="bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition-colors">
        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
        </svg>
    </a>';
    
    // Twitter
    echo '<a href="https://twitter.com/intent/tweet?text=' . $encoded_title . '&url=' . $encoded_url . '" target="_blank" rel="noopener noreferrer" class="bg-blue-400 text-white p-2 rounded-full hover:bg-blue-500 transition-colors">
        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
        </svg>
    </a>';
    
    // LinkedIn
    echo '<a href="https://www.linkedin.com/shareArticle?mini=true&url=' . $encoded_url . '&title=' . $encoded_title . '" target="_blank" rel="noopener noreferrer" class="bg-blue-800 text-white p-2 rounded-full hover:bg-blue-900 transition-colors">
        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
        </svg>
    </a>';
    
    // WhatsApp
    echo '<a href="https://wa.me/?text=' . $encoded_title . ' ' . $encoded_url . '" target="_blank" rel="noopener noreferrer" class="bg-green-500 text-white p-2 rounded-full hover:bg-green-600 transition-colors">
        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
        </svg>
    </a>';
    
    // Email
    echo '<a href="mailto:?subject=' . $encoded_title . '&body=' . $encoded_title . '%0A' . $encoded_url . '" class="bg-gray-500 text-white p-2 rounded-full hover:bg-gray-600 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    </a>';
    
    // Copy link button
    echo '<button onclick="copyLink()" class="bg-gray-700 text-white p-2 rounded-full hover:bg-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
    </button>';
    
    echo '</div>';
    
    // Copy link script
    echo '<script>
    function copyLink() {
        const dummy = document.createElement("input");
        document.body.appendChild(dummy);
        dummy.value = "' . $url . '";
        dummy.select();
        document.execCommand("copy");
        document.body.removeChild(dummy);
        alert("Link copied to clipboard!");
    }
    </script>';
}