<?php
/**
 * Typoria Blog Platform
 * Admin Login Page
 */

// Include required files
require_once 'auth.php';
require_once '../includes/functions.php';
require_once '../includes/theme.php';

// Check if already logged in as admin
if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header("Location: dashboard.php");
    exit();
} elseif (isset($_SESSION['user_id'])) {
    // Logged in as regular user, show error message
    $error_message = "You must be an administrator to access this page.";
} else {
    $error_message = '';
}

// Initialize variables
$email = $password = '';
$success_message = '';
$remember = false;

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password";
    } else {
        // Attempt login with admin flag set to true
        $result = login_admin($email, $password, $remember, true);
        
        if ($result['success']) {
            // Login successful, redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Login failed, show error message
            $error_message = is_array($result['message']) ? implode(' ', $result['message']) : $result['message'];
        }
    }
}

// Additional CSS for login page
$additional_css = "
    body {
        background: linear-gradient(135deg, #1E293B, #0F172A);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .admin-login-container {
        max-width: 480px;
        width: 100%;
    }
    
    .logo-glow {
        position: relative;
    }
    
    .logo-glow::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 50px;
        height: 50px;
        background: ".$TYPORIA_COLORS['primary'].";
        border-radius: 50%;
        opacity: 0.2;
        filter: blur(20px);
        z-index: -1;
    }
    
    .admin-badge {
        position: absolute;
        top: -10px;
        right: -10px;
        background: linear-gradient(135deg, ".$TYPORIA_COLORS['primary'].", ".$TYPORIA_COLORS['secondary'].");
        color: white;
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .admin-input {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        transition: all 0.3s ease;
    }
    
    .admin-input:focus {
        background: rgba(255, 255, 255, 0.1);
        border-color: ".$TYPORIA_COLORS['primary'].";
    }
    
    .admin-input::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }
    
    .login-btn {
        background: linear-gradient(135deg, ".$TYPORIA_COLORS['primary'].", ".$TYPORIA_COLORS['secondary'].");
        transition: all 0.3s ease;
    }
    
    .login-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .panel-decoration {
        position: absolute;
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, ".$TYPORIA_COLORS['primary']."20, ".$TYPORIA_COLORS['secondary']."20);
        border-radius: 50%;
        z-index: 0;
    }
    
    .panel-decoration-1 {
        top: -40px;
        left: -40px;
    }
    
    .panel-decoration-2 {
        bottom: -40px;
        right: -40px;
    }
";

// Custom header for admin login
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Typoria Admin Login</title>
    <meta name="description" content="' . htmlspecialchars($TYPORIA_CONFIG['site_description']) . '">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        ' . $TYPORIA_TAILWIND . '
    </script>
    
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@400;700&display=swap">
    
    <style>
        body {
            font-family: "Inter", sans-serif;
        }
        
        .logo-text {
            background: linear-gradient(135deg, '.$TYPORIA_COLORS['primary'].', '.$TYPORIA_COLORS['secondary'].');
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        ' . $additional_css . '
    </style>
</head>
<body>
';
?>

<div class="admin-login-container">
    <div class="bg-gray-900 rounded-2xl shadow-2xl overflow-hidden relative">
        <!-- Decorative elements -->
        <div class="panel-decoration panel-decoration-1"></div>
        <div class="panel-decoration panel-decoration-2"></div>
        
        <div class="p-8 relative z-10">
            <!-- Logo -->
            <div class="flex justify-center mb-8 logo-glow">
                <div class="relative">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                        </svg>
                        <span class="text-3xl font-bold ml-2">
                            <span class="logo-text"><?php echo htmlspecialchars($TYPORIA_CONFIG['logo_text']); ?></span>
                        </span>
                    </div>
                    <span class="admin-badge">Admin Panel</span>
                </div>
            </div>
            
            <!-- Login Header -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-white mb-2">Administrator Login</h1>
                <p class="text-gray-400">Enter your credentials to access the dashboard</p>
            </div>
            
            <!-- Error Message -->
            <?php if (!empty($error_message)) : ?>
                <div class="bg-red-900/40 border border-red-500/50 text-red-200 p-4 mb-6 rounded-lg" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm"><?php echo htmlspecialchars(is_array($error_message) ? implode(' ', $error_message) : $error_message); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if (!empty($success_message)) : ?>
                <div class="bg-green-900/40 border border-green-500/50 text-green-200 p-4 mb-6 rounded-lg" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm"><?php echo htmlspecialchars($success_message); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-6">
                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Admin Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" 
                        class="w-full rounded-lg admin-input px-4 py-3 focus:outline-none focus:ring-2 focus:ring-typoria-primary/50"
                        placeholder="Enter your admin email" required autocomplete="email">
                </div>
                
                <!-- Password Input -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-sm font-medium text-gray-300">Password</label>
                        <a href="../reset_password.php" class="text-xs text-typoria-primary hover:text-typoria-secondary transition-colors">Reset password</a>
                    </div>
                    <div class="relative">
                        <input type="password" id="password" name="password" 
                            class="w-full rounded-lg admin-input px-4 py-3 focus:outline-none focus:ring-2 focus:ring-typoria-primary/50"
                            placeholder="Enter your password" required>
                        <button type="button" id="togglePassword" class="absolute right-3 top-3 text-gray-400 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Remember Me -->
                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-typoria-primary focus:ring-typoria-primary border-gray-700 rounded bg-gray-800">
                    <label for="remember" class="ml-2 block text-sm text-gray-300">Remember this device</label>
                </div>
                
                <!-- Login Button -->
                <div>
                    <button type="submit" class="w-full login-btn text-white py-3 px-4 rounded-lg font-medium shadow-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v-1l1-1 1-1 .757-.757A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd" />
                        </svg>
                        Access Admin Dashboard
                    </button>
                </div>
            </form>
            
            <!-- Back to Site Link -->
            <div class="mt-8 text-center">
                <a href="../index.php" class="text-gray-400 hover:text-white transition-colors text-sm flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    Return to main site
                </a>
            </div>
        </div>
    </div>
    
    <!-- Security Note -->
    <div class="text-center mt-4">
        <p class="text-xs text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            Secure administrative access · <?php echo date('Y'); ?> &copy; <?php echo htmlspecialchars($TYPORIA_CONFIG['site_name']); ?>
        </p>
    </div>
</div>

<script>
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle icon
        if (type === 'text') {
            this.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
            </svg>`;
        } else {
            this.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
            </svg>`;
        }
    });
</script>

</body>
</html>