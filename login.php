<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Your App</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Animate.css CDN for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            overflow: hidden;
        }
        .glassmorphism {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        .falling-letter {
            position: absolute;
            color: white;
            font-size: 1.5rem;
            animation: fall 1s ease-out forwards;
        }
        @keyframes fall {
            to {
                transform: translateY(100vh);
                opacity: 0;
            }
        }
        .glow {
            animation: glow 2s infinite alternate;
        }
        @keyframes glow {
            from {
                box-shadow: 0 0 10px rgba(255, 255, 255, 0.5), 0 0 20px rgba(255, 255, 255, 0.5);
            }
            to {
                box-shadow: 0 0 20px rgba(255, 255, 255, 0.8), 0 0 40px rgba(255, 255, 255, 0.8);
            }
        }
        .particle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            animation: particle-float 5s infinite ease-in-out;
        }
        @keyframes particle-float {
            0%, 100% {
                transform: translateY(0) translateX(0);
            }
            50% {
                transform: translateY(-20px) translateX(20px);
            }
        }
        .gradient-text {
            background: linear-gradient(45deg, #ff9a9e, #fad0c4, #fbc2eb, #a6c1ee, #fbc2eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-size: 300% 300%;
            animation: gradient 5s ease infinite;
        }
        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        .gradient-border {
            border: 2px solid transparent;
            background: linear-gradient(135deg, #1e3c72, #2a5298) padding-box,
                        linear-gradient(45deg, #ff9a9e, #fad0c4, #fbc2eb, #a6c1ee, #fbc2eb) border-box;
            border-radius: 20px;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="glassmorphism p-8 max-w-md w-full animate__animated animate__fadeInUp floating glow gradient-border">
        <h2 class="text-4xl font-bold text-center mb-6 gradient-text">Welcome Back</h2>
        <p class="text-gray-200 text-center mb-6">Sign in to your account</p>
        <form method="POST" action="loginSubmit.php" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300">Email Address</label>
                <input type="email" name="email" id="email" class="w-full rounded-lg border-2 border-gray-300 bg-transparent text-white placeholder-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-3 transition duration-300" placeholder="Enter your email" required>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300">Password</label>
                <input type="password" name="password" id="password" class="w-full rounded-lg border-2 border-gray-300 bg-transparent text-white placeholder-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-3 transition duration-300" placeholder="Enter your password" required>
            </div>
            <div class="flex items-center justify-between">
                <label for="remember-me" class="flex items-center text-gray-300">
                    <input type="checkbox" id="remember-me" name="remember-me" class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded">
                    <span class="ml-2 text-sm">Remember me</span>
                </label>
                <a href="#" class="text-sm text-blue-400 hover:underline">Forgot password?</a>
            </div>
            <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-medium hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-300">
                Sign In
            </button>
        </form>
        <div class="text-center mt-4">
            <p class="text-gray-300 text-sm">Don't have an account? 
                <a href="./Registration.php" class="text-blue-400 font-medium hover:underline">Sign up</a>
            </p>
        </div>
    </div>
    <script>
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('input', (event) => {
            const email = event.target.value;
            const lastChar = email[email.length - 1];
            if (lastChar) {
                const fallingLetter = document.createElement('span');
                fallingLetter.textContent = lastChar;
                fallingLetter.classList.add('falling-letter');
                fallingLetter.style.left = `${event.target.offsetLeft + event.target.offsetWidth / 2}px`;
                fallingLetter.style.top = `${event.target.offsetTop}px`;
                document.body.appendChild(fallingLetter);
                setTimeout(() => {
                    fallingLetter.remove();
                }, 1000);
            }
        });

        // Add floating particles
        const particleCount = 50;
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');
            particle.style.left = `${Math.random() * 100}vw`;
            particle.style.top = `${Math.random() * 100}vh`;
            particle.style.animationDuration = `${Math.random() * 5 + 3}s`;
            document.body.appendChild(particle);
        }
    </script>
</body>
</html>