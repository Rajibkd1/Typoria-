<?php
include "./db_connection.php";
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

<body class="  min-h-screen bg-gray-100">
    <?php
    include "./navbar.php";

    ?>
    <div class="flex">
        <?php
        $category_id = $_GET['category_id'];

        $sql = "SELECT posts.*, users.name as user_name, categories.category 
        FROM posts 
        JOIN users ON posts.user_id = users.user_id 
        JOIN categories ON posts.category_id = categories.category_id 
        WHERE posts.category_id = " . $category_id . " 
        AND posts.status = 'approved'";

        $result = $conn->query($sql);



        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                echo '
            <div  class="mt-4 max-w-sm mx-auto bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Post Image -->
        <img class="w-full h-48 object-cover" src="./working-employee_720x405.jpg" alt="Post Image">
        <!-- Post Content -->
        <div class="p-4">
            <!-- Post Title -->
            <h2 class="text-lg font-semibold text-gray-800">' . $row['title'] . '</h2>
             <!-- Post Description -->
    <p class="text-gray-600 mt-2">
      Description : ' . $row['details'] . '
    </p>
            <p>Posted by ' . $row['user_name'] . '</p>
            <p>Category: ' . $row['category'] . '</p>
        </div>
      </div>';
            }
        } else {
            echo "0 results";
        }
        ?>




    </div>


</body>

</html>