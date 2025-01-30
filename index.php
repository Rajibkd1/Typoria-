<?php
include "./db_connection.php";
include "./auth.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom CSS for Particle Background and Animations -->
    <style>
        body {
            overflow-x: hidden;
        }

        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgb(216, 226, 244) 0%, rgb(76, 121, 200) 100%);
            z-index: -1;
        }

        .post-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            perspective: 1000px;
        }

        .post-card:hover {
            transform: translateY(-10px) rotateX(5deg) rotateY(5deg);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .post-card img {
            transition: transform 0.5s ease;
        }

        .post-card:hover img {
            transform: scale(1.1);
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .post-card:hover .overlay {
            opacity: 1;
        }

        .animated-text {
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="min-h-screen bg-gray-100 relative">

    <!-- Particle Background -->
    <div id="particles-js"></div>

    <?php include "./navbar.php"; ?>

    <div class="container mx-auto px-4 py-8 relative z-10">
        <h1 class="text-4xl font-bold text-center text-gray-800 mb-8 animated-text">Explore Posts</h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $sql = "SELECT posts.*, users.name AS user_name, categories.category 
                    FROM posts 
                    JOIN users ON posts.user_id = users.user_id 
                    JOIN categories ON posts.category_id = categories.category_id 
                    WHERE posts.status = 'approved'";

            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $imagePath = './uploads/' . $row['image'];

                    echo '
                    <div class="post-card">
                        <div class="group bg-white rounded-lg shadow-lg overflow-hidden relative transform transition-transform duration-300 hover:shadow-2xl">
                            <!-- Post Image -->
                            <img class="w-full h-56 object-cover" src="' . $imagePath . '" alt="Post Image">
                            
                            <!-- Overlay for View Button -->
                            <div class="overlay">
                                <a href="post_view.php?post_id=' . $row['post_id'] . '" class="bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg hover:bg-blue-600 transition-colors duration-300">View Post</a>
                            </div>

                            <!-- Post Content -->
                            <div class="p-6">
                                <h2 class="text-xl font-bold text-gray-800 mb-2">' . $row['title'] . '</h2>
                                <p class="text-sm text-gray-600 mb-4">By: ' . $row['user_name'] . '</p>
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">' . $row['category'] . '</span>
                            </div>
                        </div>
                    </div>';
                }
            } else {
                echo "<p class='text-gray-600 text-center'>No posts found</p>";
            }
            ?>
        </div>
    </div>

    <!-- Particle.js Library -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // Initialize Particle.js
        particlesJS.load('particles-js', 'particles.json', function() {
            console.log('Particles.js loaded!');
        });
    </script>

    <!-- Particle.js Configuration -->
    <script>
        particlesJS('particles-js', {
            "particles": {
                "number": {
                    "value": 80,
                    "density": {
                        "enable": true,
                        "value_area": 800
                    }
                },
                "color": {
                    "value": "#ffffff"
                },
                "shape": {
                    "type": "circle",
                    "stroke": {
                        "width": 0,
                        "color": "#000000"
                    },
                    "polygon": {
                        "nb_sides": 5
                    }
                },
                "opacity": {
                    "value": 0.5,
                    "random": false,
                    "anim": {
                        "enable": false,
                        "speed": 1,
                        "opacity_min": 0.1,
                        "sync": false
                    }
                },
                "size": {
                    "value": 3,
                    "random": true,
                    "anim": {
                        "enable": false,
                        "speed": 40,
                        "size_min": 0.1,
                        "sync": false
                    }
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#ffffff",
                    "opacity": 0.4,
                    "width": 1
                },
                "move": {
                    "enable": true,
                    "speed": 6,
                    "direction": "none",
                    "random": false,
                    "straight": false,
                    "out_mode": "out",
                    "bounce": false,
                    "attract": {
                        "enable": false,
                        "rotateX": 600,
                        "rotateY": 1200
                    }
                }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": true,
                        "mode": "repulse"
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push"
                    },
                    "resize": true
                },
                "modes": {
                    "grab": {
                        "distance": 400,
                        "line_linked": {
                            "opacity": 1
                        }
                    },
                    "bubble": {
                        "distance": 400,
                        "size": 40,
                        "duration": 2,
                        "opacity": 8,
                        "speed": 3
                    },
                    "repulse": {
                        "distance": 200,
                        "duration": 0.4
                    },
                    "push": {
                        "particles_nb": 4
                    },
                    "remove": {
                        "particles_nb": 2
                    }
                }
            },
            "retina_detect": true
        });
    </script>

</body>

</html>