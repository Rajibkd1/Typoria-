<?php
/**
 * Typoria Blog Platform
 * Admin - Edit Post
 */

// Include required files
require_once '../includes/functions.php';
require_once '../includes/theme.php';

// Require admin authentication
$auth = require_admin();
$admin_id = $auth['user_id'];
$admin_name = $auth['username'];

// Initialize database connection
$conn = get_db_connection();

// Get post ID from URL
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

if ($post_id <= 0) {
    // Invalid post ID
    header("Location: manage_posts.php");
    exit();
}

// Initialize variables
$title = '';
$details = '';
$category_id = 0;
$status = '';
$tag_string = '';
$post_user_id = 0; // Original author
$post_image = ''; // Current post image
$scheduled_date = ''; // For scheduled posts
$message = '';
$message_type = '';

// Get post details
$sql = "SELECT p.*, GROUP_CONCAT(t.tag_name SEPARATOR ', ') as tags
        FROM posts p
        LEFT JOIN post_tags pt ON p.post_id = pt.post_id
        LEFT JOIN tags t ON pt.tag_id = t.tag_id
        WHERE p.post_id = ?
        GROUP BY p.post_id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Post not found
    header("Location: manage_posts.php");
    exit();
}

$post = $result->fetch_assoc();
$title = $post['title'];
$details = $post['details'];
$category_id = $post['category_id'];
$status = $post['status'];
$tag_string = $post['tags'] ?? '';
$post_user_id = $post['user_id'];
$post_image = $post['image'];
$scheduled_date = $post['scheduled_date'] ? date('Y-m-d\TH:i', strtotime($post['scheduled_date'])) : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $details = $_POST['details'];
    $category_id = (int)$_POST['category_id'];
    $status = $_POST['status'];
    $tag_string = $_POST['tags'];
    $scheduled_date = !empty($_POST['scheduled_date']) ? date('Y-m-d H:i:s', strtotime($_POST['scheduled_date'])) : null;
    
    // Validate input
    if (empty($title)) {
        $message = "Post title is required!";
        $message_type = "error";
    } else if (empty($details)) {
        $message = "Post content is required!";
        $message_type = "error";
    } else if ($category_id <= 0) {
        $message = "Please select a category!";
        $message_type = "error";
    } else {
        // All inputs are valid, update post
        try {
            $conn->begin_transaction();
            
            // Check if new image was uploaded
            $new_image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
                $new_image = upload_image($_FILES['image'], '../uploads/', 'post_');
                
                if (!$new_image) {
                    throw new Exception("Image upload failed. Please try a different image.");
                }
            }
            
            // Calculate read time if content changed
            $read_time = calculate_reading_time($details);
            
            // Create excerpt if content changed
            $excerpt = create_excerpt($details);
            
            // Update post
            $update_sql = "UPDATE posts SET 
                          title = ?, 
                          details = ?, 
                          category_id = ?, 
                          status = ?, 
                          read_time = ?, 
                          excerpt = ?";
            
            $update_params = [$title, $details, $category_id, $status, $read_time, $excerpt];
            $update_types = "ssisss";
            
            // Add image parameter if new image was uploaded
            if ($new_image) {
                $update_sql .= ", image = ?";
                $update_params[] = $new_image;
                $update_types .= "s";
            }
            
            // Add scheduled date parameter if status is 'scheduled'
            if ($status === 'scheduled') {
                if (empty($scheduled_date)) {
                    throw new Exception("Scheduled date is required for scheduled posts!");
                }
                $update_sql .= ", scheduled_date = ?";
                $update_params[] = $scheduled_date;
                $update_types .= "s";
            } else {
                $update_sql .= ", scheduled_date = NULL";
            }
            
            // Add post ID parameter
            $update_sql .= " WHERE post_id = ?";
            $update_params[] = $post_id;
            $update_types .= "i";
            
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param($update_types, ...$update_params);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update post: " . $conn->error);
            }
            
            // Update tags if provided
            if (!empty($tag_string)) {
                // First remove all existing tags for this post
                $delete_tags_sql = "DELETE FROM post_tags WHERE post_id = ?";
                $delete_tags_stmt = $conn->prepare($delete_tags_sql);
                $delete_tags_stmt->bind_param("i", $post_id);
                $delete_tags_stmt->execute();
                
                // Add new tags
                $tags_array = array_map('trim', explode(',', $tag_string));
                
                foreach ($tags_array as $tag_name) {
                    if (empty($tag_name)) continue;
                    
                    // Check if tag exists
                    $tag_sql = "SELECT tag_id FROM tags WHERE tag_name = ?";
                    $tag_stmt = $conn->prepare($tag_sql);
                    $tag_stmt->bind_param("s", $tag_name);
                    $tag_stmt->execute();
                    $tag_result = $tag_stmt->get_result();
                    
                    if ($tag_result->num_rows > 0) {
                        // Tag exists, get tag_id
                        $tag_row = $tag_result->fetch_assoc();
                        $tag_id = $tag_row['tag_id'];
                    } else {
                        // Create new tag
                        $new_tag_sql = "INSERT INTO tags (tag_name) VALUES (?)";
                        $new_tag_stmt = $conn->prepare($new_tag_sql);
                        $new_tag_stmt->bind_param("s", $tag_name);
                        $new_tag_stmt->execute();
                        $tag_id = $conn->insert_id;
                    }
                    
                    // Associate tag with post
                    $post_tag_sql = "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)";
                    $post_tag_stmt = $conn->prepare($post_tag_sql);
                    $post_tag_stmt->bind_param("ii", $post_id, $tag_id);
                    $post_tag_stmt->execute();
                }
            }
            
            // Send notification to author if status changed to approved or rejected
            $get_old_status_sql = "SELECT status FROM posts WHERE post_id = ?";
            $old_status_stmt = $conn->prepare($get_old_status_sql);
            $old_status_stmt->bind_param("i", $post_id);
            $old_status_stmt->execute();
            $old_status_result = $old_status_stmt->get_result();
            $old_status_row = $old_status_result->fetch_assoc();
            $old_status = $old_status_row['status'];
            
            if ($old_status !== $status && ($status === 'approved' || $status === 'rejected') && $post_user_id != $admin_id) {
                $notification_message = "Your post \"" . substr($title, 0, 30) . "\" has been " . $status . ".";
                create_notification($post_user_id, 'system', $post_id, $admin_id, $notification_message);
            }
            
            $conn->commit();
            
            $message = "Post updated successfully!";
            $message_type = "success";
            
            // Reload post data after update
            $stmt->execute();
            $result = $stmt->get_result();
            $post = $result->fetch_assoc();
            $title = $post['title'];
            $details = $post['details'];
            $category_id = $post['category_id'];
            $status = $post['status'];
            $tag_string = $post['tags'] ?? '';
            $post_image = $post['image'];
            $scheduled_date = $post['scheduled_date'] ? date('Y-m-d\TH:i', strtotime($post['scheduled_date'])) : '';
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = $e->getMessage();
            $message_type = "error";
        }
    }
}

// Get categories for dropdown
$categories_sql = "SELECT * FROM categories ORDER BY category";
$categories_result = $conn->query($categories_sql);

// Additional CSS for rich text editor
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
    
    /* Form Elements */
    .form-label {
        @apply block text-sm font-medium text-gray-700 mb-1;
    }
    
    .form-input,
    .form-select,
    .form-textarea {
        @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-typoria-primary focus:ring focus:ring-typoria-primary focus:ring-opacity-20;
    }
    
    .form-textarea {
        @apply h-60 resize-y;
    }
    
    .input-error {
        @apply text-red-500 text-sm mt-1;
    }
    
    /* Toggles */
    .toggle-checkbox {
        @apply absolute block w-4 h-4 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer transition-all duration-300;
    }
    
    .toggle-checkbox:checked {
        @apply border-typoria-primary right-0 transform translate-x-full;
    }
    
    .toggle-label {
        @apply block h-4 overflow-hidden rounded-full bg-gray-300 cursor-pointer transition-all duration-300;
    }
    
    .toggle-checkbox:checked + .toggle-label {
        @apply bg-typoria-primary;
    }
";

// Additional JS for rich text editor
$additional_js = '
<script src="https://cdn.ckeditor.com/ckeditor5/37.0.0/classic/ckeditor.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        ClassicEditor
            .create(document.querySelector("#editor"), {
                toolbar: ["heading", "|", "bold", "italic", "link", "bulletedList", "numberedList", "|", "indent", "outdent", "|", "imageUpload", "blockQuote", "insertTable", "mediaEmbed", "undo", "redo"]
            })
            .then(editor => {
                // Save for form submit
                const form = document.querySelector("#post-form");
                form.addEventListener("submit", function() {
                    const detailsInput = document.querySelector("#details");
                    detailsInput.value = editor.getData();
                });
            })
            .catch(error => {
                console.error(error);
            });
            
        // Show/hide scheduled date input based on status
        const statusSelect = document.querySelector("#status");
        const scheduledDateGroup = document.querySelector("#scheduled-date-group");
        
        function toggleScheduledDate() {
            if (statusSelect.value === "scheduled") {
                scheduledDateGroup.classList.remove("hidden");
            } else {
                scheduledDateGroup.classList.add("hidden");
            }
        }
        
        toggleScheduledDate();
        statusSelect.addEventListener("change", toggleScheduledDate);
    });
</script>
';

// Page title
$page_title = "Edit Post";

// Generate HTML header
typoria_header($page_title, $additional_css, $additional_js);
?>

<div class="admin-layout">
    <!-- Admin Sidebar -->
    <?php include 'admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="bg-gray-100 p-6">
        <div class="container mx-auto max-w-4xl">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Edit Post</h1>
                <div class="mt-4 md:mt-0 flex space-x-2">
                    <a href="post_view.php?post_id=<?php echo $post_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Post
                    </a>
                    <a href="manage_posts.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Posts
                    </a>
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
            
            <!-- Post Form -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-800">Edit Post Details</h2>
                </div>
                
                <form id="post-form" method="POST" action="edit_post.php?post_id=<?php echo $post_id; ?>" enctype="multipart/form-data" class="p-6">
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Title -->
                        <div>
                            <label for="title" class="form-label">Post Title</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" class="form-input" required>
                        </div>
                        
                        <!-- Category -->
                        <div>
                            <label for="category_id" class="form-label">Category</label>
                            <select id="category_id" name="category_id" class="form-select" required>
                                <option value="">Select a category</option>
                                <?php if ($categories_result->num_rows > 0) : ?>
                                    <?php while ($category = $categories_result->fetch_assoc()) : ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <!-- Tags -->
                        <div>
                            <label for="tags" class="form-label">Tags (comma separated)</label>
                            <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($tag_string); ?>" class="form-input" placeholder="e.g., technology, programming, web-design">
                        </div>
                        
                        <!-- Status -->
                        <div>
                            <label for="status" class="form-label">Post Status</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                                <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="scheduled" <?php echo $status == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            </select>
                        </div>
                        
                        <!-- Scheduled Date (shown only for scheduled posts) -->
                        <div id="scheduled-date-group" class="<?php echo $status != 'scheduled' ? 'hidden' : ''; ?>">
                            <label for="scheduled_date" class="form-label">Scheduled Date & Time</label>
                            <input type="datetime-local" id="scheduled_date" name="scheduled_date" value="<?php echo htmlspecialchars($scheduled_date); ?>" class="form-input">
                            <p class="text-sm text-gray-500 mt-1">Set when this post should be automatically published</p>
                        </div>
                        
                        <!-- Featured Image -->
                        <div>
                            <label for="image" class="form-label">Featured Image</label>
                            
                            <?php if (!empty($post_image) && file_exists('../uploads/' . $post_image)) : ?>
                                <div class="mb-3">
                                    <p class="text-sm text-gray-500 mb-2">Current image:</p>
                                    <img src="../uploads/<?php echo $post_image; ?>" alt="Current featured image" class="h-32 object-cover rounded-lg">
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" id="image" name="image" class="form-input" accept="image/*">
                            <p class="text-sm text-gray-500 mt-1">Leave empty to keep current image</p>
                        </div>
                        
                        <!-- Content -->
                        <div>
                            <label for="editor" class="form-label">Post Content</label>
                            <div class="border border-gray-300 rounded-md">
                                <textarea id="editor" name="editor"><?php echo htmlspecialchars($details); ?></textarea>
                                <input type="hidden" id="details" name="details" value="<?php echo htmlspecialchars($details); ?>">
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-3">
                            <a href="manage_posts.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">Cancel</a>
                            <button type="submit" class="px-4 py-2 bg-typoria-primary text-white rounded-md hover:bg-typoria-primary/90 transition-colors">
                                Update Post
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php
// Do not include the standard footer for admin pages
?>
</body>
</html>