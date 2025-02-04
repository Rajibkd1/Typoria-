<?php
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $isLoggedIn = true;
} else {
    $isLoggedIn = false;
}

// Include database connection
include "./db_connection.php";
include "./navbar.php";

// Fetch categories from database
$sql = "SELECT * FROM categories";
$result = $conn->query($sql);

// Initialize categories array
$categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a New Post</title>
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg,rgb(135, 153, 234) 0%,rgb(181, 123, 240) 100%);
        }
        .gradient-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .gradient-button:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .falling-letters {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }
        .falling-letters span {
            position: absolute;
            display: block;
            color: rgba(255, 255, 255, 0.5);
            font-size: 24px;
            font-weight: bold;
            animation: animate 5s linear infinite;
            bottom: -150px;
        }
        @keyframes animate {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
            }
        }
    </style>
</head>

<body class="gradient-bg">

    <!-- Falling Letters Effect -->
    <div class="falling-letters" id="falling-letters"></div>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full bg-white p-10 rounded-2xl shadow-2xl transform transition-all duration-500 hover:scale-105">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-8 text-center animate__animated animate__fadeInDown">Create a New Post</h2>
            
            <!-- Post Form -->
            <form action="submit_post.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="mb-4 animate__animated animate__fadeInLeft">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                    <input type="text" name="title" id="title" placeholder="Enter title" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 px-4 py-3 placeholder-gray-400 text-gray-700 leading-tight focus:outline-none transition duration-300 ease-in-out hover:shadow-md">
                </div>
                <div class="mb-4 animate__animated animate__fadeInRight">
                    <label for="details" class="block text-sm font-medium text-gray-700 mb-2">Details</label>
                    <textarea name="details" id="details" rows="5" placeholder="Enter details" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 px-4 py-3 placeholder-gray-400 text-gray-700 leading-tight focus:outline-none resize-none transition duration-300 ease-in-out hover:shadow-md"></textarea>
                </div>
                <div class="mb-4 animate__animated animate__fadeInLeft">
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 px-4 py-3 text-gray-700 leading-tight focus:outline-none transition duration-300 ease-in-out hover:shadow-md" required>
                        <option value="" disabled selected>Select category</option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo $category['category']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4 animate__animated animate__fadeInRight">
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Image</label>
                    <input type="file" name="image" id="image" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 px-4 py-3 text-gray-700 leading-tight focus:outline-none transition duration-300 ease-in-out hover:shadow-md">
                </div>
                <div class="flex justify-end animate__animated animate__fadeInUp">
                    <button type="submit" class="inline-flex items-center px-8 py-4 border border-transparent text-lg font-medium rounded-xl shadow-lg text-white gradient-button transform transition-all duration-300 ease-in-out hover:scale-105 hover:shadow-xl">
                        Create Post
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for Falling Letters Effect -->
    <script>
        const titleInput = document.getElementById('title');
        const fallingLettersContainer = document.getElementById('falling-letters');

        titleInput.addEventListener('input', (event) => {
            const inputValue = event.target.value;
            const lastChar = inputValue[inputValue.length - 1];

            if (lastChar) {
                createFallingLetter(lastChar);
            }
        });

        function createFallingLetter(letter) {
            const span = document.createElement('span');
            span.textContent = letter;
            span.style.left = `${Math.random() * 100}%`;
            span.style.animationDuration = `${Math.random() * 3 + 2}s`;
            span.style.color = `rgba(${Math.random() * 255}, ${Math.random() * 255}, ${Math.random() * 255}, 0.8)`;
            span.style.fontSize = `${Math.random() * 24 + 16}px`;

            fallingLettersContainer.appendChild(span);

            // Remove the span after the animation ends
            span.addEventListener('animationend', () => {
                span.remove();
            });
        }
    </script>
</body>

</html>