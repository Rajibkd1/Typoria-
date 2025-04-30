<?php
/**
 * Typoria Blog Platform
 * Admin - Category Management
 */

// Include required files
require_once '../includes/functions.php';
require_once '../includes/theme.php';

// Check if admin is logged in
$auth = require_admin();

// Initialize database connection
$conn = get_db_connection();

// Process category actions (add, edit, delete)
$message = '';
$message_type = '';
$edit_id = 0;
$edit_category = '';

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    try {
        // First check if category is in use
        $check_sql = "SELECT COUNT(*) AS post_count FROM posts WHERE category_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $delete_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['post_count'] > 0) {
            // Category has posts, cannot delete
            $message = "Cannot delete category because it contains {$row['post_count']} posts. Please reassign these posts to another category first.";
            $message_type = "error";
        } else {
            // Safe to delete
            $delete_sql = "DELETE FROM categories WHERE category_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $delete_id);
            
            if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
                $message = "Category deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Failed to delete category.";
                $message_type = "error";
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Handle Edit Request (just to load data)
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    
    // Get category details
    $edit_sql = "SELECT * FROM categories WHERE category_id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    
    if ($edit_result->num_rows > 0) {
        $edit_data = $edit_result->fetch_assoc();
        $edit_category = $edit_data['category'];
    } else {
        $edit_id = 0;
        $message = "Category not found!";
        $message_type = "error";
    }
}

// Handle Form Submission (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    if (empty($_POST['category'])) {
        $message = "Category name is required!";
        $message_type = "error";
    } else {
        $category_name = trim($_POST['category']);
        
        if (isset($_POST['category_id']) && is_numeric($_POST['category_id']) && $_POST['category_id'] > 0) {
            // Update existing category
            $category_id = (int)$_POST['category_id'];
            
            try {
                // Check if name already exists (excluding current)
                $check_sql = "SELECT category_id FROM categories WHERE category = ? AND category_id != ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("si", $category_name, $category_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $message = "A category with this name already exists!";
                    $message_type = "error";
                } else {
                    // Update category
                    $update_sql = "UPDATE categories SET category = ? WHERE category_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("si", $category_name, $category_id);
                    
                    if ($update_stmt->execute()) {
                        $message = "Category updated successfully!";
                        $message_type = "success";
                        $edit_id = 0;
                        $edit_category = '';
                    } else {
                        $message = "Failed to update category!";
                        $message_type = "error";
                    }
                }
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            // Add new category
            try {
                // Check if name already exists
                $check_sql = "SELECT category_id FROM categories WHERE category = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("s", $category_name);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $message = "A category with this name already exists!";
                    $message_type = "error";
                } else {
                    // Insert new category
                    $insert_sql = "INSERT INTO categories (category) VALUES (?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("s", $category_name);
                    
                    if ($insert_stmt->execute()) {
                        $message = "Category added successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Failed to add category!";
                        $message_type = "error";
                    }
                }
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $message_type = "error";
            }
        }
    }
}

// Get all categories with post count
$categories_sql = "SELECT c.category_id, c.category, COUNT(p.post_id) as post_count 
                  FROM categories c
                  LEFT JOIN posts p ON c.category_id = p.category_id
                  GROUP BY c.category_id
                  ORDER BY c.category";
$categories_result = $conn->query($categories_sql);

// Custom CSS for admin
$custom_css = "
    .admin-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
    }
    
    .admin-header {
        background: linear-gradient(135deg, " . $TYPORIA_COLORS['primary'] . "20, " . $TYPORIA_COLORS['secondary'] . "20);
        padding: 2rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }
    
    .admin-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    
    .admin-description {
        color: #6b7280;
    }
    
    .admin-card {
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
        overflow: hidden;
    }
    
    .admin-card-header {
        padding: 1.5rem;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .admin-card-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #374151;
    }
    
    .admin-card-body {
        padding: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #4b5563;
    }
    
    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        font-size: 1rem;
    }
    
    .form-input:focus {
        outline: none;
        border-color: " . $TYPORIA_COLORS['primary'] . ";
        box-shadow: 0 0 0 3px " . $TYPORIA_COLORS['primary'] . "25;
    }
    
    .button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        border-radius: 0.375rem;
        transition: all 0.2s ease;
    }
    
    .button-primary {
        background-color: " . $TYPORIA_COLORS['primary'] . ";
        color: white;
    }
    
    .button-primary:hover {
        background-color: " . $TYPORIA_COLORS['primary'] . "dd;
    }
    
    .button-secondary {
        background-color: #f3f4f6;
        color: #4b5563;
    }
    
    .button-secondary:hover {
        background-color: #e5e7eb;
    }
    
    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .admin-table th {
        background-color: #f9fafb;
        font-weight: 600;
        color: #4b5563;
    }
    
    .admin-table tr:hover {
        background-color: #f9fafb;
    }
    
    .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .badge-primary {
        background-color: " . $TYPORIA_COLORS['primary'] . "15;
        color: " . $TYPORIA_COLORS['primary'] . ";
    }
    
    .badge-secondary {
        background-color: #e5e7eb;
        color: #4b5563;
    }
    
    .badge-success {
        background-color: #dcfce7;
        color: #16a34a;
    }
    
    .badge-danger {
        background-color: #fee2e2;
        color: #dc2626;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
    
    .action-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease;
    }
    
    .action-button-edit {
        background-color: #dbeafe;
        color: #2563eb;
    }
    
    .action-button-edit:hover {
        background-color: #bfdbfe;
    }
    
    .action-button-delete {
        background-color: #fee2e2;
        color: #dc2626;
    }
    
    .action-button-delete:hover {
        background-color: #fecaca;
    }
    
    .alert {
        padding: 1rem;
        border-radius: 0.375rem;
        margin-bottom: 1.5rem;
    }
    
    .alert-success {
        background-color: #dcfce7;
        color: #16a34a;
        border-left: 4px solid #16a34a;
    }
    
    .alert-error {
        background-color: #fee2e2;
        color: #dc2626;
        border-left: 4px solid #dc2626;
    }
    
    .alert-info {
        background-color: #dbeafe;
        color: #2563eb;
        border-left: 4px solid #2563eb;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }
    
    .empty-state-icon {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }
    
    .empty-state-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 0.5rem;
    }
    
    .empty-state-message {
        color: #6b7280;
        max-width: 400px;
        margin: 0 auto;
    }
    
    /* Confirm Dialog */
    .confirm-dialog-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        display: none;
    }
    
    .confirm-dialog {
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
        overflow: hidden;
    }
    
    .confirm-dialog-header {
        padding: 1.5rem;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .confirm-dialog-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
    }
    
    .confirm-dialog-body {
        padding: 1.5rem;
        color: #4b5563;
    }
    
    .confirm-dialog-footer {
        padding: 1rem 1.5rem;
        background-color: #f9fafb;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }
";

// Generate HTML header
typoria_header("Manage Categories", $custom_css);
?>

<!-- Admin Dashboard Layout -->
<div class="bg-gray-50 min-h-screen pb-12">
    <!-- Admin Header -->
    <?php include '../admin/components/admin_header.php'; ?>
    
    <div class="admin-container mt-6">
        <div class="admin-header">
            <h1 class="admin-title">Manage Categories</h1>
            <p class="admin-description">Add, edit, and manage categories for blog posts.</p>
        </div>
        
        <!-- Display Message if any -->
        <?php if (!empty($message)) : ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Category Form Card -->
            <div class="lg:col-span-1">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title"><?php echo $edit_id ? 'Edit Category' : 'Add New Category'; ?></h2>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" action="manage_categories.php">
                            <?php if ($edit_id) : ?>
                                <input type="hidden" name="category_id" value="<?php echo $edit_id; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="category" class="form-label">Category Name</label>
                                <input type="text" id="category" name="category" class="form-input" value="<?php echo htmlspecialchars($edit_category); ?>" required>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <button type="submit" class="button button-primary">
                                    <?php echo $edit_id ? 'Update Category' : 'Add Category'; ?>
                                </button>
                                
                                <?php if ($edit_id) : ?>
                                    <a href="manage_categories.php" class="button button-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Categories List Card -->
            <div class="lg:col-span-2">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Categories List</h2>
                    </div>
                    <div class="admin-card-body">
                        <?php if ($categories_result && $categories_result->num_rows > 0) : ?>
                            <div class="overflow-x-auto">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Category Name</th>
                                            <th>Posts</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($category = $categories_result->fetch_assoc()) : ?>
                                            <tr>
                                                <td><?php echo $category['category_id']; ?></td>
                                                <td><?php echo htmlspecialchars($category['category']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $category['post_count'] > 0 ? 'badge-primary' : 'badge-secondary'; ?>">
                                                        <?php echo $category['post_count']; ?> posts
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="manage_categories.php?edit=<?php echo $category['category_id']; ?>" class="action-button action-button-edit" title="Edit">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </a>
                                                        
                                                        <?php if ($category['post_count'] == 0) : ?>
                                                            <a href="#" onclick="confirmDelete(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['category']); ?>')" class="action-button action-button-delete" title="Delete">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </a>
                                                        <?php else : ?>
                                                            <span class="action-button action-button-delete opacity-50 cursor-not-allowed" title="Cannot delete category with posts">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📋</div>
                                <h3 class="empty-state-title">No Categories Found</h3>
                                <p class="empty-state-message">Start by adding a new category using the form on the left.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Dialog -->
<div id="confirmDeleteDialog" class="confirm-dialog-backdrop">
    <div class="confirm-dialog">
        <div class="confirm-dialog-header">
            <h3 class="confirm-dialog-title">Confirm Delete</h3>
        </div>
        <div class="confirm-dialog-body">
            <p>Are you sure you want to delete the category <strong id="categoryName"></strong>?</p>
            <p class="text-sm text-red-600 mt-2">This action cannot be undone.</p>
        </div>
        <div class="confirm-dialog-footer">
            <button onclick="closeConfirmDialog()" class="button button-secondary">Cancel</button>
            <a href="#" id="confirmDeleteButton" class="button button-primary bg-red-600 hover:bg-red-700">Delete</a>
        </div>
    </div>
</div>

<!-- JavaScript for Confirm Dialog -->
<script>
    function confirmDelete(categoryId, categoryName) {
        // Set the category name in the dialog
        document.getElementById('categoryName').textContent = categoryName;
        
        // Set the delete URL
        document.getElementById('confirmDeleteButton').href = 'manage_categories.php?delete=' + categoryId;
        
        // Show the dialog
        document.getElementById('confirmDeleteDialog').style.display = 'flex';
        
        // Prevent default link behavior
        return false;
    }
    
    function closeConfirmDialog() {
        // Hide the dialog
        document.getElementById('confirmDeleteDialog').style.display = 'none';
    }
    
    // Close dialog when clicking outside
    document.getElementById('confirmDeleteDialog').addEventListener('click', function(e) {
        if (e.target === this) {
            closeConfirmDialog();
        }
    });
</script>

<?php
// Generate footer
typoria_footer();
?>