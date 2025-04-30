<?php
/**
 * Typoria Blog Platform
 * Enhanced Create Post Page
 */

// Include required files
require_once 'includes/functions.php';
require_once 'includes/theme.php';

// Require user to be logged in
$auth = require_login($_SERVER['REQUEST_URI']);
$user_id = $auth['user_id'];
$username = $auth['username'];

// Initialize database connection
$conn = get_db_connection();

// Initialize variables
$title = $content = '';
$category_id = 0;
$tags = [];
$error = $success = '';
$image_uploaded = false;
$image_name = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug the entire POST data
    error_log("POST data received: " . print_r($_POST, true));
    
    // Get form data
    $title = sanitize_input($_POST['title'] ?? '');
    
    // Try to get content from multiple possible sources
    $content = '';
    if (!empty($_POST['content'])) {
        $content = $_POST['content'];
    } elseif (!empty($_POST['editor-content'])) {
        $content = $_POST['editor-content'];
    } elseif (!empty($_POST['direct_content'])) {
        $content = $_POST['direct_content'];
    }
    
    // If still empty, try to get raw editor content
    if (empty($content) && !empty($_POST['editor'])) {
        $content = $_POST['editor'];
    }
    
    $category_id = (int)($_POST['category_id'] ?? 0);
    $tag_string = sanitize_input($_POST['tags'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'pending');
    
    // Create the post
    $result = create_post(
        $user_id, 
        $title, 
        $content, 
        $category_id, 
        $tag_string, 
        $status, 
        $_FILES['image'] ?? null
    );
    
    if ($result['success']) {
        $success = $result['message'];
        // Reset form data
        $title = $content = '';
        $category_id = 0;
        $tags = [];
        
        // Redirect to the post view page after short delay
        header("refresh:2;url=post_view.php?post_id=" . $result['post_id']);
    } else {
        $error = $result['message'];
    }
}

// Get categories for dropdown
$categories_sql = "SELECT * FROM categories ORDER BY category";
$categories_result = $conn->query($categories_sql);

// Get popular tags for suggestions
$tags_sql = "SELECT tag_name FROM tags ORDER BY RAND() LIMIT 10";
$tags_result = $conn->query($tags_sql);
$popular_tags = [];
if ($tags_result->num_rows > 0) {
    while ($tag = $tags_result->fetch_assoc()) {
        $popular_tags[] = $tag['tag_name'];
    }
}

// Additional CSS for the page
$additional_css = "
    :root {
        --primary: ".$TYPORIA_COLORS['primary'].";
        --secondary: ".$TYPORIA_COLORS['secondary'].";
        --accent: ".$TYPORIA_COLORS['accent'].";
        --dark: #1f2937;
        --light: #f9fafb;
    }

    /* Create post page styles */
    .create-post-page {
        background-color: #f5f7fa;
        position: relative;
        overflow: hidden;
        min-height: calc(100vh - 64px);
        padding: 3rem 1rem;
    }
    
    .create-post-page::before,
    .create-post-page::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        z-index: 0;
    }
    
    .create-post-page::before {
        background: radial-gradient(circle, rgba(var(--primary-rgb), 0.05) 0%, rgba(var(--primary-rgb), 0.02) 70%, transparent 100%);
        width: 100vw;
        height: 100vw;
        top: -50vw;
        right: -50vw;
    }
    
    .create-post-page::after {
        background: radial-gradient(circle, rgba(var(--secondary-rgb), 0.05) 0%, rgba(var(--secondary-rgb), 0.02) 70%, transparent 100%);
        width: 80vw;
        height: 80vw;
        bottom: -40vw;
        left: -40vw;
    }
    
    .page-header {
        position: relative;
        z-index: 10;
        margin-bottom: 2.5rem;
    }
    
    .page-header::after {
        content: '';
        position: absolute;
        bottom: -0.75rem;
        left: 50%;
        transform: translateX(-50%);
        width: 5rem;
        height: 0.25rem;
        background: linear-gradient(to right, var(--primary), var(--secondary));
        border-radius: 1rem;
    }
    
    .create-post-container {
        position: relative;
        z-index: 10;
        max-width: 48rem;
        margin: 0 auto;
    }
    
    .post-form-card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .post-form-card:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        transform: translateY(-3px);
    }
    
    .form-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        padding: 1.5rem;
        color: white;
        position: relative;
    }
    
    .form-header-decoration {
        position: absolute;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }
    
    .decor-1 {
        width: 80px;
        height: 80px;
        top: -20px;
        right: -20px;
    }
    
    .decor-2 {
        width: 40px;
        height: 40px;
        bottom: 20px;
        right: 40px;
    }
    
    .form-content {
        padding: 2rem;
    }
    
    /* Form element styles */
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }
    
    .form-input, 
    .form-select {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        background-color: #f9fafb;
        transition: all 0.3s ease;
    }
    
    .form-input:focus,
    .form-select:focus {
        border-color: var(--primary);
        background-color: white;
        outline: none;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.15);
    }
    
    .required::after {
        content: '*';
        color: #ef4444;
        margin-left: 0.25rem;
    }
    
    /* CKEditor styles */
    .ck-editor__editable {
        min-height: 300px;
        max-height: 600px;
        border-radius: 0 0 0.5rem 0.5rem !important;
    }
    
    .ck.ck-editor__main>.ck-editor__editable:not(.ck-focused) {
        border-color: #e5e7eb !important;
    }
    
    .ck.ck-editor__main>.ck-editor__editable.ck-focused {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.15) !important;
    }
    
    .ck.ck-toolbar {
        border-radius: 0.5rem 0.5rem 0 0 !important;
        border-color: #e5e7eb !important;
        background: #f3f4f6 !important;
    }
    
    /* Image upload area */
    .image-upload-container {
        border: 2px dashed #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.5rem;
        text-align: center;
        background-color: #f9fafb;
        transition: all 0.3s ease;
    }
    
    .image-upload-container:hover {
        border-color: var(--primary);
        background-color: rgba(var(--primary-rgb), 0.05);
    }
    
    .image-upload-area {
        padding: 2rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer;
    }
    
    .image-upload-icon {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        box-shadow: 0 4px 6px rgba(var(--primary-rgb), 0.25);
    }
    
    .image-preview-container {
        margin-top: 1rem;
        display: flex;
        align-items: center;
        background-color: white;
        padding: 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .image-preview-container img {
        border-radius: 0.375rem;
        object-fit: cover;
        border: 1px solid #e5e7eb;
    }
    
    .remove-image-btn {
        background-color: #fee2e2;
        border: none;
        color: #dc2626;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .remove-image-btn:hover {
        background-color: #fecaca;
        transform: scale(1.05);
    }
    
    /* Tag suggestions */
    .tag-suggestions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .tag-suggestion {
        background-color: #e5e7eb;
        color: #4b5563;
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
    }
    
    .tag-suggestion:hover {
        background-color: var(--primary);
        color: white;
        transform: translateY(-1px);
    }
    
    .tag-suggestion::before {
        content: '+';
        margin-right: 0.25rem;
        font-weight: bold;
    }
    
    /* Radio buttons */
    .status-options {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .radio-button {
        position: relative;
        flex: 1;
        min-width: 10rem;
    }
    
    .radio-button-input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .radio-button-label {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        background-color: #f9fafb;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .radio-button-input:checked + .radio-button-label {
        border-color: var(--primary);
        background-color: rgba(var(--primary-rgb), 0.05);
    }
    
    .radio-button-input:focus + .radio-button-label {
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.15);
    }
    
    .radio-button-icon {
        background-color: white;
        border: 2px solid #d1d5db;
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 50%;
        margin-right: 0.75rem;
        position: relative;
        transition: all 0.2s ease;
    }
    
    .radio-button-icon::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        width: 0.625rem;
        height: 0.625rem;
        border-radius: 50%;
        background-color: var(--primary);
        transition: transform 0.2s ease;
    }
    
    .radio-button-input:checked + .radio-button-label .radio-button-icon {
        border-color: var(--primary);
    }
    
    .radio-button-input:checked + .radio-button-label .radio-button-icon::after {
        transform: translate(-50%, -50%) scale(1);
    }
    
    .radio-button-text {
        font-weight: 500;
    }
    
    /* Submit button */
    .submit-container {
        margin-top: 2rem;
        display: flex;
        justify-content: flex-end;
    }
    
    .submit-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.875rem 2rem;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-weight: 600;
        border-radius: 0.5rem;
        border: none;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(var(--primary-rgb), 0.25);
    }
    
    .submit-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 10px rgba(var(--primary-rgb), 0.3);
    }
    
    .submit-button:active {
        transform: translateY(0);
    }
    
    .submit-button::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
        transform: translateX(-100%);
        transition: transform 0.7s ease;
    }
    
    .submit-button:hover::after {
        transform: translateX(100%);
    }
    
    .submit-button-icon {
        margin-right: 0.75rem;
    }
    
    /* Writing tips card */
    .tips-card {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        margin-top: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
    }
    
    .tips-header {
        background: linear-gradient(90deg, rgba(var(--primary-rgb), 0.1), rgba(var(--secondary-rgb), 0.1));
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .tips-icon {
        margin-right: 0.75rem;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .tips-content {
        padding: 1.5rem;
    }
    
    .tips-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .tips-item {
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        transition: all 0.2s ease;
    }
    
    .tips-item:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }
    
    .tips-item-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
        color: var(--primary);
        font-weight: 600;
    }
    
    .tips-item-icon {
        margin-right: 0.5rem;
        flex-shrink: 0;
    }
    
    /* Alert messages */
    .alert {
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
        position: relative;
        animation: slideDown 0.3s ease forwards;
    }
    
    .alert-success {
        background-color: #ecfdf5;
        border-left: 4px solid #10b981;
        color: #065f46;
    }
    
    .alert-error {
        background-color: #fef2f2;
        border-left: 4px solid #ef4444;
        color: #b91c1c;
    }
    
    .alert-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
    }
    
    .alert-content {
        padding-left: 2.5rem;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Debug panel */
    .debug-panel {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        margin: 1rem 0;
        overflow: hidden;
    }
    
    .debug-header {
        background-color: #f3f4f6;
        padding: 0.5rem 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .debug-content {
        padding: 1rem;
        background-color: white;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .code-content {
        font-family: monospace;
        white-space: pre-wrap;
        font-size: 0.875rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .create-post-page {
            padding: 2rem 1rem;
        }
        
        .form-content {
            padding: 1.5rem;
        }
        
        .status-options {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .radio-button {
            width: 100%;
        }
        
        .tips-list {
            grid-template-columns: 1fr;
        }
    }
";

// Additional JavaScript for the page
$additional_js = "
    <!-- CKEditor 5 -->
    <script src=\"https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js\"></script>
";

// Page title
$page_title = "Create New Post";

// Calculate RGB values for CSS variables
$primary_hex = $TYPORIA_COLORS['primary'];
$secondary_hex = $TYPORIA_COLORS['secondary'];
$accent_hex = $TYPORIA_COLORS['accent'];

// Convert hex to rgb for CSS variables
function hex_to_rgb($hex) {
    $hex = str_replace('#', '', $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "$r, $g, $b";
}

// Add RGB variables to CSS
$additional_css = "
    :root {
        --primary: ".$TYPORIA_COLORS['primary'].";
        --secondary: ".$TYPORIA_COLORS['secondary'].";
        --accent: ".$TYPORIA_COLORS['accent'].";
        --primary-rgb: ".hex_to_rgb($primary_hex).";
        --secondary-rgb: ".hex_to_rgb($secondary_hex).";
        --accent-rgb: ".hex_to_rgb($accent_hex).";
        --dark: #1f2937;
        --light: #f9fafb;
    }
" . $additional_css;

// Generate HTML header
typoria_header($page_title, $additional_css, $additional_js);
?>

<?php include 'navbar.php'; ?>

<div class="create-post-page">
    <div class="create-post-container">
        <!-- Page Header -->
        <div class="page-header text-center">
            <h1 class="text-3xl font-bold text-gray-800 mb-2 font-serif">Create New Post</h1>
            <p class="text-gray-600">Share your ideas and inspire the world with your words</p>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (!empty($success)) : ?>
            <div class="alert alert-success" role="alert">
                <svg class="alert-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <div class="alert-content">
                    <p><?php echo $success; ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)) : ?>
            <div class="alert alert-error" role="alert">
                <svg class="alert-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <div class="alert-content">
                    <p><?php echo $error; ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Post Form Card -->
        <div class="post-form-card">
            <!-- Form Header -->
            <div class="form-header">
                <div class="form-header-decoration decor-1"></div>
                <div class="form-header-decoration decor-2"></div>
                <h2 class="text-xl font-bold">Craft Your Story</h2>
                <p class="text-white/80 text-sm mt-1">Fill in the details below to create your post</p>
            </div>
            
            <!-- Form Content -->
            <div class="form-content">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data" id="post-form">
                    <!-- Post Title -->
                    <div class="form-group">
                        <label for="title" class="form-label required">Post Title</label>
                        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>" 
                            class="form-input"
                            placeholder="Enter a captivating title for your post" required>
                    </div>
                    
                    <!-- Category -->
                    <div class="form-group">
                        <label for="category_id" class="form-label required">Category</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">Select a category</option>
                            <?php
                            if ($categories_result && $categories_result->num_rows > 0) {
                                while ($category = $categories_result->fetch_assoc()) {
                                    $selected = ($category_id == $category['category_id']) ? 'selected' : '';
                                    echo '<option value="' . $category['category_id'] . '" ' . $selected . '>' . htmlspecialchars($category['category']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Post Image -->
                    <div class="form-group">
                        <label for="image" class="form-label">Featured Image</label>
                        <div class="image-upload-container">
                            <label for="image" class="image-upload-area">
                                <div class="image-upload-icon">
                                    <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="text-gray-700 font-medium">Click to upload an image</div>
                                <p class="text-sm text-gray-500 mt-1">or drag and drop</p>
                                <p class="text-xs text-gray-400 mt-3">Supported formats: JPEG, PNG, GIF (Max size: 2MB)</p>
                                <input id="image" name="image" type="file" class="hidden" accept="image/*" />
                            </label>
                        </div>
                        <div id="image-preview" class="image-preview-container hidden">
                            <img id="preview-img" src="#" alt="Preview" class="h-20 w-auto">
                            <button type="button" id="remove-image" class="remove-image-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Post Content -->
                    <div class="form-group">
                        <label for="editor" class="form-label required">Content</label>
                        
                        <!-- Multiple content fields for redundancy -->
                        <input type="hidden" name="content" id="content" value="<?php echo htmlspecialchars($content); ?>">
                        <input type="hidden" name="editor-content" id="editor-content" value="<?php echo htmlspecialchars($content); ?>">
                        <input type="hidden" name="direct_content" id="direct_content" value="<?php echo htmlspecialchars($content); ?>">
                        
                        <!-- Editor container -->
                        <div id="editor"><?php echo $content; ?></div>
                        
                        <!-- Fallback textarea (normally hidden) -->
                        <textarea id="fallback-editor" name="fallback_content" class="hidden form-input" rows="10"><?php echo htmlspecialchars($content); ?></textarea>
                        
                        <p class="text-xs text-gray-500 mt-2">
                            Having issues with the editor? <a href="#" id="show-fallback" class="text-typoria-primary hover:underline">Switch to simple editor</a>
                        </p>
                    </div>
                    
                    <!-- Tags -->
                    <div class="form-group">
                        <label for="tags" class="form-label">Tags</label>
                        <input type="text" name="tags" id="tags" value="<?php echo htmlspecialchars(implode(', ', $tags)); ?>" 
                            class="form-input"
                            placeholder="Add tags separated by commas (e.g., technology, design, inspiration)">
                        
                        <div class="mt-2">
                            <p class="text-sm text-gray-600 mb-1">Suggested tags (click to add):</p>
                            <div class="tag-suggestions">
                                <?php foreach ($popular_tags as $tag) : ?>
                                    <span class="tag-suggestion"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Post Status -->
                    <div class="form-group">
                        <label class="form-label">Publish Status</label>
                        <div class="status-options">
                            <div class="radio-button">
                                <input type="radio" name="status" id="status-publish" value="pending" class="radio-button-input" checked>
                                <label for="status-publish" class="radio-button-label">
                                    <span class="radio-button-icon"></span>
                                    <div>
                                        <span class="radio-button-text">Submit for Review</span>
                                        <p class="text-xs text-gray-500 mt-1">Editors will review before publishing</p>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="radio-button">
                                <input type="radio" name="status" id="status-draft" value="draft" class="radio-button-input">
                                <label for="status-draft" class="radio-button-label">
                                    <span class="radio-button-icon"></span>
                                    <div>
                                        <span class="radio-button-text">Save as Draft</span>
                                        <p class="text-xs text-gray-500 mt-1">Continue editing later</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Debug panel (hidden by default) -->
                    <div class="debug-panel hidden" id="debug-panel">
                        <div class="debug-header">
                            <h3 class="text-sm font-medium">Content Debug</h3>
                            <button type="button" id="hide-debug" class="text-gray-500 hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                        <div class="debug-content">
                            <div id="content-preview" class="code-content"></div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="submit-container">
                        <button type="button" id="show-debug" class="text-gray-500 text-sm px-3 py-1 mr-4 hidden">Debug</button>
                        <button type="submit" id="submit-post" class="submit-button">
                            <span class="submit-button-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            Publish Post
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Writing Tips Card -->
        <div class="tips-card">
            <div class="tips-header">
                <div class="tips-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Writing Tips to Engage Your Readers</h3>
            </div>
            
            <div class="tips-content">
                <ul class="tips-list">
                    <li class="tips-item">
                        <div class="tips-item-header">
                            <svg xmlns="http://www.w3.org/2000/svg" class="tips-item-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                            </svg>
                            Craft a Compelling Title
                        </div>
                        <p class="text-sm text-gray-600">Use clear, specific titles that spark curiosity and tell readers what they'll gain.</p>
                    </li>
                    
                    <li class="tips-item">
                        <div class="tips-item-header">
                            <svg xmlns="http://www.w3.org/2000/svg" class="tips-item-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                            </svg>
                            Include Quality Images
                        </div>
                        <p class="text-sm text-gray-600">A relevant, eye-catching featured image can increase engagement by up to 94%.</p>
                    </li>
                    
                    <li class="tips-item">
                        <div class="tips-item-header">
                            <svg xmlns="http://www.w3.org/2000/svg" class="tips-item-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z" />
                            </svg>
                            Structure Your Content
                        </div>
                        <p class="text-sm text-gray-600">Use headings, subheadings, and short paragraphs to improve readability and flow.</p>
                    </li>
                    
                    <li class="tips-item">
                        <div class="tips-item-header">
                            <svg xmlns="http://www.w3.org/2000/svg" class="tips-item-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            Use Relevant Tags
                        </div>
                        <p class="text-sm text-gray-600">Add 3-5 specific tags to help readers discover your content and find related posts.</p>
                    </li>
                    
                    <li class="tips-item">
                        <div class="tips-item-header">
                            <svg xmlns="http://www.w3.org/2000/svg" class="tips-item-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" />
                            </svg>
                            Start Strong, End Strong
                        </div>
                        <p class="text-sm text-gray-600">Hook readers with a powerful introduction and leave them with a memorable conclusion.</p>
                    </li>
                    
                    <li class="tips-item">
                        <div class="tips-item-header">
                            <svg xmlns="http://www.w3.org/2000/svg" class="tips-item-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Proofread Carefully
                        </div>
                        <p class="text-sm text-gray-600">Review your post for spelling, grammar, and clarity before publishing.</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced JavaScript for Form Handling -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Reference to form and elements
        const form = document.getElementById('post-form');
        const content = document.getElementById('content');
        const editorContent = document.getElementById('editor-content');
        const directContent = document.getElementById('direct_content');
        const editorContainer = document.getElementById('editor');
        const fallbackEditor = document.getElementById('fallback-editor');
        const showFallbackBtn = document.getElementById('show-fallback');
        const showDebugBtn = document.getElementById('show-debug');
        const hideDebugBtn = document.getElementById('hide-debug');
        const debugPanel = document.getElementById('debug-panel');
        const contentPreview = document.getElementById('content-preview');
        const submitButton = document.getElementById('submit-post');
        
        // Show fallback editor if needed
        showFallbackBtn.addEventListener('click', function(e) {
            e.preventDefault();
            editorContainer.classList.add('hidden');
            fallbackEditor.classList.remove('hidden');
            this.textContent = 'Using simple editor';
            this.classList.add('text-green-500');
        });
        
        // Toggle debug button visibility for developers (press D key)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'd' && e.ctrlKey && e.shiftKey) {
                showDebugBtn.classList.toggle('hidden');
            }
        });
        
        // Debug panel toggle
        showDebugBtn.addEventListener('click', function() {
            debugPanel.classList.remove('hidden');
            // Update content preview
            updateDebugPanel();
        });
        
        hideDebugBtn.addEventListener('click', function() {
            debugPanel.classList.add('hidden');
        });
        
        // Function to update debug panel
        function updateDebugPanel() {
            const previewContent = 
                'content: ' + (content.value ? content.value.substring(0, 50) + '...' : 'EMPTY') + '\n' +
                'editor-content: ' + (editorContent.value ? editorContent.value.substring(0, 50) + '...' : 'EMPTY') + '\n' +
                'direct_content: ' + (directContent.value ? directContent.value.substring(0, 50) + '...' : 'EMPTY') + '\n' +
                'fallback_content: ' + (fallbackEditor.value ? fallbackEditor.value.substring(0, 50) + '...' : 'EMPTY');
            
            contentPreview.textContent = previewContent;
        }
        
        // Image preview functionality
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        const removeButton = document.getElementById('remove-image');
        
        imageInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                
                reader.onload = (e) => {
                    previewImg.src = e.target.result;
                    imagePreview.classList.remove('hidden');
                };
                
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Enable drag and drop for images
        const uploadContainer = document.querySelector('.image-upload-container');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadContainer.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadContainer.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadContainer.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            uploadContainer.classList.add('border-typoria-primary');
            uploadContainer.classList.add('bg-opacity-10');
        }
        
        function unhighlight() {
            uploadContainer.classList.remove('border-typoria-primary');
            uploadContainer.classList.remove('bg-opacity-10');
        }
        
        uploadContainer.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length) {
                imageInput.files = files;
                const event = new Event('change', { bubbles: true });
                imageInput.dispatchEvent(event);
            }
        }
        
        removeButton.addEventListener('click', () => {
            imageInput.value = '';
            imagePreview.classList.add('hidden');
        });
        
        // Tag suggestion functionality
        const tagSuggestions = document.querySelectorAll('.tag-suggestion');
        const tagsInput = document.getElementById('tags');
        
        tagSuggestions.forEach(tag => {
            tag.addEventListener('click', () => {
                const tagText = tag.textContent.trim();
                let currentTags = tagsInput.value.split(',').map(t => t.trim()).filter(t => t !== '');
                
                // Don't add if tag already exists
                if (!currentTags.includes(tagText)) {
                    if (currentTags.length > 0 && currentTags[0] !== '') {
                        tagsInput.value = tagsInput.value + ', ' + tagText;
                    } else {
                        tagsInput.value = tagText;
                    }
                }
                
                // Add pulsing animation to the tag
                tag.classList.add('animate-pulse');
                setTimeout(() => {
                    tag.classList.remove('animate-pulse');
                }, 300);
                
                tagsInput.focus();
            });
        });

        // Try to initialize CKEditor
        let editor;
        try {
            ClassicEditor
                .create(document.querySelector('#editor'), {
                    // Editor configuration
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'outdent', 'indent', '|', 'blockQuote', 'insertTable', 'undo', 'redo'],
                })
                .then(newEditor => {
                    editor = newEditor;
                    console.log('CKEditor initialized successfully');
                    
                    // Set up data change listener
                    editor.model.document.on('change:data', () => {
                        const editorData = editor.getData();
                        content.value = editorData;
                        editorContent.value = editorData;
                        directContent.value = editorData;
                        
                        // Update debug panel if visible
                        if (!debugPanel.classList.contains('hidden')) {
                            updateDebugPanel();
                        }
                    });
                })
                .catch(error => {
                    console.error('CKEditor failed to initialize:', error);
                    // Show fallback editor if CKEditor fails
                    editorContainer.classList.add('hidden');
                    fallbackEditor.classList.remove('hidden');
                    showFallbackBtn.textContent = 'Using simple editor (CKEditor failed)';
                    showFallbackBtn.classList.add('text-red-500');
                });
        } catch (e) {
            console.error('Error setting up CKEditor:', e);
            // Show fallback editor
            editorContainer.classList.add('hidden');
            fallbackEditor.classList.remove('hidden');
        }
        
        // Form validation and submission
        form.addEventListener('submit', function(e) {
            // Get content from all possible sources 
            let postContent = '';
            
            if (editor) {
                // If CKEditor is active, get its content
                postContent = editor.getData();
                content.value = postContent;
                editorContent.value = postContent;
                directContent.value = postContent;
            } else if (!fallbackEditor.classList.contains('hidden')) {
                // If fallback editor is visible, use its content
                postContent = fallbackEditor.value;
                content.value = postContent;
                editorContent.value = postContent;
                directContent.value = postContent;
            }
            
            // Also try to get content directly from DOM if CKEditor is initialized
            try {
                const editorElement = document.querySelector('.ck-editor__editable');
                if (editorElement) {
                    const domContent = editorElement.innerHTML;
                    if (domContent && domContent.trim() !== '') {
                        // Use as a last resort if other methods failed
                        if (!postContent || postContent.trim() === '') {
                            directContent.value = domContent;
                        }
                    }
                }
            } catch (err) {
                console.error('Error getting DOM content:', err);
            }
            
            // Final check for empty content
            if ((!content.value || content.value.trim() === '') && 
                (!editorContent.value || editorContent.value.trim() === '') && 
                (!directContent.value || directContent.value.trim() === '') && 
                (!fallbackEditor.value || fallbackEditor.value.trim() === '')) {
                
                e.preventDefault();
                
                // Show error message
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-error';
                errorAlert.innerHTML = `
                    <svg class="alert-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div class="alert-content">
                        <p>Post content is required. Please add some content to your post.</p>
                    </div>
                `;
                
                // Insert at the top of the form
                form.parentNode.insertBefore(errorAlert, form);
                
                // Scroll to error message
                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                // Remove after 5 seconds
                setTimeout(() => {
                    errorAlert.remove();
                }, 5000);
                
                return false;
            }
            
            // Check title length
            const titleInput = document.getElementById('title');
            if (titleInput.value.trim().length < 3) {
                e.preventDefault();
                
                // Show error message
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-error';
                errorAlert.innerHTML = `
                    <svg class="alert-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div class="alert-content">
                        <p>Post title must be at least 3 characters long.</p>
                    </div>
                `;
                
                // Insert at the top of the form
                form.parentNode.insertBefore(errorAlert, form);
                
                // Highlight the input field
                titleInput.classList.add('border-red-500');
                
                // Focus on the field
                titleInput.focus();
                
                // Remove error highlighting when typing
                titleInput.addEventListener('input', function() {
                    if (this.value.trim().length >= 3) {
                        this.classList.remove('border-red-500');
                    }
                });
                
                // Scroll to error message
                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                // Remove after 5 seconds
                setTimeout(() => {
                    errorAlert.remove();
                }, 5000);
                
                return false;
            }
            
            // Log what's being submitted 
            console.log('Submitting form with content length:', content.value.length);
            
            // Disable submit button and show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Publishing...
            `;
        });
    });
</script>

<?php
// Generate footer
typoria_footer();
?>