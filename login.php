<?php
/**
 * Typoria Blog Platform
 * Enhanced User Login Page
 */

// Include required files
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/theme.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables
$email = $password = '';
$error_message = '';
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
        // Attempt login (setting admin flag to false)
        $result = login_user($email, $password, $remember, false);
        
        if ($result['success']) {
            // Login successful, redirect
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
            header("Location: " . $redirect);
            exit();
        } else {
            // Login failed, show error message
            $error_message = is_array($result['message']) ? implode(' ', $result['message']) : $result['message'];
        }
    }
}

// Get redirect URL if provided
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

// Generate page title
$page_title = "Login to Typoria";

// Additional CSS for enhanced login page
$additional_css = "
/* Main container styles */
.login-page {
    min-height: calc(100vh - 64px);
    background-image: url('assets/images/login-bg-pattern.svg'), linear-gradient(135deg, #f5f7fa 0%, #eef2f7 100%);
    background-size: cover;
    position: relative;
    overflow: hidden;
}

.login-page::before {
    content: '';
    position: absolute;
    width: 1200px;
    height: 1200px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.03) 0%, rgba(99, 102, 241, 0.07) 100%);
    top: -400px;
    right: -400px;
    z-index: 0;
    animation: float 15s ease-in-out infinite alternate;
}

.login-page::after {
    content: '';
    position: absolute;
    width: 1000px;
    height: 1000px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.05) 0%, rgba(124, 58, 237, 0.08) 100%);
    bottom: -400px;
    left: -300px;
    z-index: 0;
    animation: float 20s ease-in-out infinite alternate-reverse;
}

@keyframes float {
    0% {
        transform: translate(0, 0);
    }
    100% {
        transform: translate(50px, 30px);
    }
}

.login-container {
    max-width: 460px;
    width: 100%;
    margin: 0 auto;
    padding: 0 1.5rem;
    position: relative;
    z-index: 10;
}

/* Card styles */
.login-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1), 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.login-card:hover {
    box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.15), 0 18px 36px -18px rgba(0, 0, 0, 0.1);
    transform: translateY(-5px);
}

/* Header styles */
.login-header {
    position: relative;
    overflow: hidden;
    padding: 3.5rem 2.5rem;
    text-align: center;
}

.login-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, ".$TYPORIA_COLORS['primary']." 0%, ".$TYPORIA_COLORS['secondary']." 100%);
    z-index: -1;
}

.login-header h1 {
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    letter-spacing: -0.025em;
    font-weight: 800;
}

/* Floating particles */
.floating-particle {
    position: absolute;
    background-color: rgba(255, 255, 255, 0.15);
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
}

.particle-1 {
    width: 100px;
    height: 100px;
    top: -20px;
    left: -30px;
    animation: float-slow 15s infinite alternate ease-in-out;
}

.particle-2 {
    width: 60px;
    height: 60px;
    bottom: 30px;
    right: 20px;
    animation: float-slow 20s infinite alternate-reverse ease-in-out;
}

.particle-3 {
    width: 40px;
    height: 40px;
    top: 40px;
    right: 80px;
    animation: float-slow 12s infinite alternate ease-in-out;
}

.particle-4 {
    width: 24px;
    height: 24px;
    bottom: 70px;
    left: 100px;
    animation: float-slow 18s infinite alternate-reverse ease-in-out;
}

@keyframes float-slow {
    0% {
        transform: translate(0, 0) rotate(0deg);
    }
    50% {
        transform: translate(15px, 15px) rotate(5deg);
    }
    100% {
        transform: translate(-15px, 10px) rotate(-5deg);
    }
}

.auth-decoration {
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, white 0%, #f9fafb 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    z-index: 10;
}

.auth-decoration svg {
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}

/* Form styles */
.form-content {
    padding: 3rem 2.5rem 2.5rem;
}

.input-group {
    position: relative;
    margin-bottom: 1.75rem;
}

.form-input {
    width: 100%;
    padding: 0.875rem 1.25rem;
    background-color: #f9fafb;
    border: 2px solid #f3f4f6;
    border-radius: 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-input:focus {
    border-color: ".$TYPORIA_COLORS['primary'].";
    background-color: white;
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.15);
    outline: none;
}

.form-input::placeholder {
    color: #9ca3af;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    transition: all 0.3s ease;
}

.input-with-icon {
    padding-left: 2.75rem;
}

.input-with-toggle {
    padding-right: 2.75rem;
}

.password-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    background: transparent;
    padding: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.password-toggle:hover {
    color: ".$TYPORIA_COLORS['primary'].";
}

.form-input:focus + .input-icon {
    color: ".$TYPORIA_COLORS['primary'].";
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #4b5563;
    font-size: 0.875rem;
}

.submit-button {
    display: block;
    width: 100%;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, ".$TYPORIA_COLORS['primary']." 0%, ".$TYPORIA_COLORS['secondary']." 100%);
    color: white;
    border: none;
    border-radius: 1rem;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
    position: relative;
    overflow: hidden;
}

.submit-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(124, 58, 237, 0.4);
}

.submit-button:active {
    transform: translateY(0);
}

.submit-button:after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(30deg);
    transition: transform 0.5s ease-out;
    pointer-events: none;
}

.submit-button:hover:after {
    transform: rotate(30deg) translate(10%, 10%);
}

/* Checkbox styles */
.remember-checkbox {
    display: flex;
    align-items: center;
    color: #4b5563;
    cursor: pointer;
    user-select: none;
    transition: all 0.2s ease;
}

.remember-checkbox:hover {
    color: #374151;
}

.checkbox-container {
    position: relative;
    height: 20px;
    width: 20px;
    margin-right: 8px;
}

.checkbox-container input {
    position: absolute;
    opacity: 0;
    height: 0;
    width: 0;
}

.checkmark {
    position: absolute;
    top: 0;
    left: 0;
    height: 20px;
    width: 20px;
    background-color: #f3f4f6;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.remember-checkbox:hover .checkmark {
    background-color: #e5e7eb;
}

.checkbox-container input:checked ~ .checkmark {
    background-color: ".$TYPORIA_COLORS['primary'].";
    border-color: ".$TYPORIA_COLORS['primary'].";
}

.checkmark:after {
    content: '';
    position: absolute;
    display: none;
    left: 7px;
    top: 3px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.checkbox-container input:checked ~ .checkmark:after {
    display: block;
}

/* Utility styles */
.auth-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 1.5rem 0;
}

.forgot-link {
    color: ".$TYPORIA_COLORS['primary'].";
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    position: relative;
}

.forgot-link:after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 1px;
    background-color: ".$TYPORIA_COLORS['primary'].";
    transform: scaleX(0);
    transform-origin: right;
    transition: transform 0.3s ease;
}

.forgot-link:hover {
    color: ".$TYPORIA_COLORS['secondary'].";
}

.forgot-link:hover:after {
    transform: scaleX(1);
    transform-origin: left;
}

.footer-text {
    text-align: center;
    margin-top: 1.5rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.signup-link {
    color: ".$TYPORIA_COLORS['primary'].";
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    position: relative;
}

.signup-link:after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 1px;
    background-color: ".$TYPORIA_COLORS['primary'].";
    transform: scaleX(0);
    transform-origin: right;
    transition: transform 0.3s ease;
}

.signup-link:hover {
    color: ".$TYPORIA_COLORS['secondary'].";
}

.signup-link:hover:after {
    transform: scaleX(1);
    transform-origin: left;
}

.admin-link {
    display: inline-flex;
    align-items: center;
    color: #9ca3af;
    font-size: 0.75rem;
    text-decoration: none;
    margin-top: 1rem;
    transition: all 0.2s ease;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
}

.admin-link:hover {
    color: ".$TYPORIA_COLORS['primary'].";
    background-color: #f9fafb;
}

.admin-link svg {
    margin-right: 0.25rem;
}

.secure-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.625rem 1.25rem;
    background-color: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(8px);
    border-radius: 2rem;
    margin-top: 1.75rem;
    color: #4b5563;
    font-size: 0.75rem;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.5);
    transition: all 0.3s ease;
}

.secure-badge:hover {
    background-color: rgba(255, 255, 255, 0.9);
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
}

.secure-badge svg {
    margin-right: 0.5rem;
    color: ".$TYPORIA_COLORS['secondary'].";
}

/* Alert styles */
.alert {
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: flex-start;
    animation: slideIn 0.5s ease forwards;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

@keyframes slideIn {
    0% {
        opacity: 0;
        transform: translateY(-10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert svg {
    flex-shrink: 0;
    margin-right: 0.75rem;
    margin-top: 0.125rem;
}

.alert-error {
    background-color: #fee2e2;
    border-left: 4px solid #ef4444;
    color: #b91c1c;
}

.alert-success {
    background-color: #dcfce7;
    border-left: 4px solid #22c55e;
    color: #15803d;
}

/* Animation for card */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.login-card {
    animation: fadeIn 0.7s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

/* Password strength indicator */
.password-strength {
    height: 4px;
    margin-top: 8px;
    border-radius: 2px;
    background-color: #e5e7eb;
    overflow: hidden;
    transition: all 0.3s ease;
}

.strength-meter {
    height: 100%;
    width: 0;
    transition: all 0.3s ease;
}

.strength-weak {
    width: 33%;
    background-color: #ef4444;
}

.strength-medium {
    width: 66%;
    background-color: #f59e0b;
}

.strength-strong {
    width: 100%;
    background-color: #22c55e;
}

/* Social auth buttons */
.social-login {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin: 1.5rem 0;
}

.social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 0.75rem;
    background-color: #f9fafb;
    border: 1px solid #f3f4f6;
    transition: all 0.3s ease;
    color: #4b5563;
}

.social-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.social-btn.google:hover {
    color: #ea4335;
    background-color: #fef2f2;
}

.social-btn.facebook:hover {
    color: #1877f2;
    background-color: #eff6ff;
}

.social-btn.twitter:hover {
    color: #1da1f2;
    background-color: #f0f9ff;
}

.social-btn.github:hover {
    color: #333;
    background-color: #f3f4f6;
}

/* Divider */
.divider {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 1.5rem 0;
    color: #9ca3af;
    font-size: 0.875rem;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #e5e7eb;
}

.divider::before {
    margin-right: 1rem;
}

.divider::after {
    margin-left: 1rem;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .login-container {
        padding: 0 1rem;
    }
    
    .login-header {
        padding: 2.5rem 1.5rem;
    }
    
    .form-content {
        padding: 2.5rem 1.5rem 2rem;
    }
    
    .social-login {
        gap: 0.75rem;
    }
    
    .social-btn {
        width: 38px;
        height: 38px;
    }
}
";

// Additional JavaScript for form validation and effects
$additional_js = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('password-toggle');
    const passwordIcon = document.getElementById('password-icon-visible');
    const passwordIconHidden = document.getElementById('password-icon-hidden');
    
    toggleBtn.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            passwordIcon.style.display = 'none';
            passwordIconHidden.style.display = 'block';
        } else {
            passwordInput.type = 'password';
            passwordIcon.style.display = 'block';
            passwordIconHidden.style.display = 'none';
        }
    });
    
    // Focus/blur effects for inputs
    const inputs = document.querySelectorAll('.form-input');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('input-focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('input-focused');
        });
    });
    
    // Particle animation
    const particles = document.querySelectorAll('.floating-particle');
    
    particles.forEach(particle => {
        const randomX = Math.random() * 15 - 7.5;
        const randomY = Math.random() * 15 - 7.5;
        const randomDuration = Math.random() * 5 + 15;
        const randomDelay = Math.random() * 5;
        
        particle.style.animation = `float-slow \${randomDuration}s \${randomDelay}s infinite alternate ease-in-out`;
    });
    
    // Form validation
    const loginForm = document.getElementById('login-form');
    const emailInput = document.getElementById('email');
    
    loginForm.addEventListener('submit', function(e) {
        let valid = true;
        
        // Basic email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!emailRegex.test(emailInput.value)) {
            showInputError(emailInput, 'Please enter a valid email address');
            valid = false;
        } else {
            clearInputError(emailInput);
        }
        
        // Basic password validation
        if (passwordInput.value.length < 6) {
            showInputError(passwordInput, 'Password must be at least 6 characters');
            valid = false;
        } else {
            clearInputError(passwordInput);
        }
        
        if (!valid) {
            e.preventDefault();
        }
    });
    
    function showInputError(input, message) {
        const formGroup = input.closest('.input-group');
        const errorMessage = formGroup.querySelector('.error-message') || document.createElement('div');
        
        errorMessage.className = 'error-message text-red-500 text-xs mt-1 font-medium';
        errorMessage.textContent = message;
        
        if (!formGroup.querySelector('.error-message')) {
            formGroup.appendChild(errorMessage);
        }
        
        input.classList.add('border-red-500');
        input.classList.add('bg-red-50');
    }
    
    function clearInputError(input) {
        const formGroup = input.closest('.input-group');
        const errorMessage = formGroup.querySelector('.error-message');
        
        if (errorMessage) {
            formGroup.removeChild(errorMessage);
        }
        
        input.classList.remove('border-red-500');
        input.classList.remove('bg-red-50');
    }
    
    // Closing alert messages
    const closeButtons = document.querySelectorAll('.alert-close');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const alert = this.closest('.alert');
            
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        });
    });
});
</script>
";

// Generate HTML header
typoria_header($page_title, $additional_css, $additional_js);
?>

<?php include 'navbar.php'; ?>

<div class="login-page flex items-center justify-center py-16">
    <div class="login-container">
        <!-- Login Card with Animation -->
        <div class="login-card">
            <!-- Login Header with Background -->
            <div class="login-header">
                <!-- Floating particles -->
                <div class="floating-particle particle-1"></div>
                <div class="floating-particle particle-2"></div>
                <div class="floating-particle particle-3"></div>
                <div class="floating-particle particle-4"></div>
                
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-3">Welcome Back</h1>
                <p class="text-white/90 text-lg">Sign in to continue your journey</p>
                
                <!-- Circle decoration with icon -->
                <div class="auth-decoration">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-typoria-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
            </div>
            
            <!-- Form Content -->
            <div class="form-content">
                <!-- Success/Error Messages -->
                <?php if (!empty($error_message)) : ?>
                    <div class="alert alert-error" role="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div class="flex-1">
                            <p><?php echo htmlspecialchars(is_array($error_message) ? implode(' ', $error_message) : $error_message); ?></p>
                        </div>
                        <button type="button" class="alert-close text-red-700 hover:text-red-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)) : ?>
                    <div class="alert alert-success" role="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <div class="flex-1">
                            <p><?php echo htmlspecialchars($success_message); ?></p>
                        </div>
                        <button type="button" class="alert-close text-green-700 hover:text-green-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
                

                <!-- Login Form -->
                <form id="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . ($redirect ? '?redirect=' . urlencode($redirect) : '')); ?>">
                    <!-- Email Input -->
                    <div class="input-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="relative">
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($email); ?>" 
                                class="form-input input-with-icon" 
                                placeholder="your@email.com" 
                                required 
                                autocomplete="email"
                            >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 input-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Password Input with Toggle -->
                    <div class="input-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input input-with-icon input-with-toggle" 
                                placeholder="Enter your password" 
                                required
                            >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 input-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                            <button type="button" id="password-toggle" class="password-toggle" tabindex="-1">
                                <svg id="password-icon-visible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                </svg>
                                <svg id="password-icon-hidden" class="h-5 w-5" style="display: none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 00-1.06 1.06l14.5 14.5a.75.75 0 101.06-1.06l-1.745-1.745a10.029 10.029 0 003.3-4.38 1.651 1.651 0 000-1.185A10.004 10.004 0 009.999 3a9.956 9.956 0 00-4.744 1.194L3.28 2.22zM7.752 6.69l1.092 1.092a2.5 2.5 0 013.374 3.373l1.091 1.092a4 4 0 00-5.557-5.557z" clip-rule="evenodd" />
                                    <path d="M10.748 13.93l2.523 2.523a9.987 9.987 0 01-3.27.547c-4.258 0-7.894-2.66-9.337-6.41a1.651 1.651 0 010-1.186A10.007 10.007 0 012.839 6.02L6.07 9.252a4 4 0 004.678 4.678z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Remember & Forgot Password -->
                    <div class="auth-options">
                        <label class="remember-checkbox">
                            <span class="checkbox-container">
                                <input type="checkbox" id="remember" name="remember" <?php echo $remember ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                            </span>
                            Remember me
                        </label>
                        <a href="reset_password.php" class="forgot-link">Forgot password?</a>
                    </div>
                    
                    <!-- Login Button -->
                    <button type="submit" class="submit-button">
                        Sign In
                    </button>
                </form>
                
                <!-- Additional Links and Badges -->
                <div class="footer-text mt-6">
                    Don't have an account? <a href="register.php" class="signup-link">Sign up now</a>
                </div>
                
                <!-- Admin Link -->
                <div class="footer-text">
                    <a href="admin/login.php" class="admin-link">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                        Admin Login
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Secure Badge -->
        <div class="flex justify-center">
            <div class="secure-badge">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Secure Login · Your data is protected
            </div>
        </div>
    </div>
</div>

<?php 
// Generate footer
typoria_footer(); 
?>