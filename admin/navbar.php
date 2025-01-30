<?php
include "./db_connection.php";
include "./auth.php";

if ($isLoggedIn) {
    $sql = "SELECT name FROM admin WHERE admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        $adminname = $admin['name'];
    } else {
        $adminname = "admin";
    }
} else {
    $adminname = null;
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hello, World!</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    <nav class="bg-gray-800">
        <div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8">
            <div class="relative flex h-16 items-center justify-between">
                <!-- Left Section -->
                <div class="flex items-center space-x-4">
                    <a href="./admin_dashboard.php" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white" aria-current="page">Home</a>
                    <div class="relative">
                        <button id="categoryDropdown" class="rounded-md px-3 py-2 text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white focus:outline-none">
                            Categories
                        </button>
                        <div id="categoryDropdownContent" class="absolute hidden bg-white rounded-md shadow-lg mt-1 py-1 w-48 z-10">
                            <?php
                            $sql = "SELECT * FROM categories";
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo '<a href="./Category.php?category_id=' . $row['category_id'] . '" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">' . $row['category'] . '</a>';
                                }
                            } else {
                                echo '<span class="block px-4 py-2 text-sm text-gray-700">No categories found</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php if ($isLoggedIn) : ?>

                        <a href="./view_category.php" class="rounded-md px-3 py-2 text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">Edit Category</a>
                        <a href="./requested_post.php" class="rounded-md px-3 py-2 text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">See posts</a>

                    <?php endif; ?>
                </div>

                <!-- Right Section (Profile & Search Bar) -->
                <div class="flex items-center space-x-6">
                    <!-- Search Bar -->
                    <form method="POST" action="../search_post.php" class="relative flex items-center mt-4 sm:mt-0 sm:ml-6">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>"
                            class="w-full rounded-md bg-gray-700 text-gray-300 placeholder-gray-400 focus:ring-2 
           focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-800 px-3 py-2 text-sm pl-10"
                            placeholder="Search posts...">
                        <button type="submit" class="absolute right-3 text-gray-400 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m2.85-7.15a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                    </form>

                    <!-- Profile Section -->
                    <div class="flex items-center space-x-3">
                        <?php if ($isLoggedIn) : ?>
                            <img class="h-8 w-8 rounded-full object-cover" src="../avater.png" alt="Profile Picture">
                            <span class="text-gray-300 text-sm font-medium"><?php echo htmlspecialchars($adminname); ?></span>
                            <a href="../logout.php" class="text-gray-300 hover:text-white px-3 py-2 text-sm font-medium">Logout</a>
                        <?php else : ?>
                            <a href="../login.php" class="text-gray-300 hover:text-white px-3 py-2 text-sm font-medium">Log in</a>
                            <a href="./Registration.php" class="text-gray-300 hover:text-white px-3 py-2 text-sm font-medium">Sign up</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="sm:hidden" id="mobile-menu">
            <div class="space-y-1 px-2 pb-3 pt-2">
                <a href="#" class="block rounded-md bg-gray-900 px-3 py-2 text-base font-medium text-white">Home</a>
                <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-gray-300 hover:bg-gray-700 hover:text-white">See Posts</a>
                <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-gray-300 hover:bg-gray-700 hover:text-white">Categories</a>
                <!-- Search Bar -->
                <div>
                    <input type="text" class="block w-full rounded-md bg-gray-700 text-gray-300 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-800 px-3 py-2 text-base" placeholder="Search...">
                </div>
            </div>
        </div>
    </nav>

    <!-- JavaScript for Dropdown -->
    <script>
        document.getElementById('categoryDropdown').addEventListener('click', function() {
            const dropdownContent = document.getElementById('categoryDropdownContent');
            dropdownContent.classList.toggle('hidden');
        });
    </script>
</body>

</html>