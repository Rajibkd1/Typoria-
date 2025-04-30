<?php
/**
 * Typoria Blog Platform
 * Registration Page
 */

// Include required files
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/theme.php';
require_once 'includes/mailer.php';

// Get redirect URL if provided
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

// Check if user is already logged in
$auth = check_auth();
if ($auth['isLoggedIn']) {
    // Redirect to home page or requested page
    header("Location: " . $redirect);
    exit();
}

// Initialize database connection
$conn = get_db_connection();

// Handle registration form submission
$error_message = "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        // Check if email already exists
        $sql = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Email already registered. Please log in or use a different email.";
        } else {
            // Store registration data for OTP verification
            $_SESSION['registration'] = [
                'name' => $name,
                'email' => $email,
                'password' => $password
            ];
            
            // Generate and send OTP
            $otp_data = send_otp_email($email, $name);
            
            if ($otp_data) {
                $_SESSION['otp_data'] = $otp_data;
                
                // Redirect to OTP verification page
                header("Location: verify_otp.php");
                exit();
            } else {
                $error_message = "Failed to send verification code. Please try again.";
            }
        }
    }
}

// Generate HTML header
typoria_header("Register", "
    .register-container {
        max-width: 500px;
        margin: 0 auto;
    }
    
    .register-form {
        transition: transform 0.3s ease-out, box-shadow 0.3s ease-out;
        background: linear-gradient(to bottom, #ffffff, #f9fafb);
        border: 1px solid rgba(229, 231, 235, 0.8);
    }
    
    .register-form:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    }
    
    .gradient-line {
        height: 5px;
        background: linear-gradient(135deg, #3B82F6, #8B5CF6);
        border-radius: 4px;
        animation: shimmer 2s infinite linear;
        background-size: 200% 100%;
    }
    
    @keyframes shimmer {
        0% { background-position: 100% 0; }
        100% { background-position: -100% 0; }
    }
    
    .strength-meter {
        height: 6px;
        border-radius: 3px;
        margin-top: 8px;
        background-color: #e5e7eb;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .strength-meter div {
        height: 100%;
        width: 0;
        transition: width 0.5s ease, background-color 0.5s ease;
    }
    
    .weak { background-color: #EF4444; }
    .medium { background-color: #F59E0B; }
    .strong { background-color: #10B981; }
    .very-strong { background-color: #059669; }
    
    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #6B7280;
        transition: color 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4px;
        border-radius: 4px;
    }
    
    .password-toggle:hover {
        color: #3B82F6;
        background-color: rgba(59, 130, 246, 0.05);
    }
    
    .password-toggle:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    }
    
    .password-field-wrapper {
        position: relative;
    }
    
    .animated-input {
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }
    
    .animated-input:focus {
        transform: scale(1.01);
    }
    
    .gradient-button {
        background: linear-gradient(135deg, #3B82F6, #8B5CF6);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
    }
    
    .gradient-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(139, 92, 246, 0.3);
    }
    
    .gradient-button:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.3);
    }
    
    .gradient-button:active {
        transform: translateY(1px);
    }
    
    .gradient-button::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: rgba(255, 255, 255, 0.2);
        transform: rotate(30deg);
        transition: transform 0.5s ease-out;
    }
    
    .gradient-button:hover::after {
        transform: rotate(30deg) translate(10%, 10%);
    }
    
    .input-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #9CA3AF;
        transition: color 0.2s ease;
        pointer-events: none;
        z-index: 10;
    }
    
    .input-with-icon {
        padding-left: 42px !important;
    }
    
    .form-input:focus + .input-icon {
        color: #3B82F6;
    }
    
    .error-shake {
        animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
    }
    
    @keyframes shake {
        10%, 90% { transform: translate3d(-1px, 0, 0); }
        20%, 80% { transform: translate3d(2px, 0, 0); }
        30%, 50%, 70% { transform: translate3d(-3px, 0, 0); }
        40%, 60% { transform: translate3d(3px, 0, 0); }
    }
    
    .alert {
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
    }
    
    .alert-icon {
        flex-shrink: 0;
        margin-right: 12px;
        margin-top: 2px;
    }
    
    .alert-error {
        background-color: #FEF2F2;
        border: 1px solid #FEE2E2;
        color: #B91C1C;
    }
    
    .alert-success {
        background-color: #F0FDF4;
        border: 1px solid #DCFCE7;
        color: #166534;
    }
    
    /* Fixed Input Styling */
    .form-group {
        position: relative;
        margin-bottom: 1.5rem;
    }
    
    .form-control {
        display: block;
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #374151;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #D1D5DB;
        border-radius: 0.5rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .form-control:focus {
        color: #374151;
        background-color: #fff;
        border-color: #93C5FD;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
    }
    
    .form-label {
        position: absolute;
        top: 0;
        left: 0;
        padding: 0.75rem 0 0.75rem 2.75rem;
        pointer-events: none;
        border: 1px solid transparent;
        transform-origin: 0 0;
        transition: opacity 0.15s ease-in-out, transform 0.15s ease-in-out;
        color: #6B7280;
        font-size: 1rem;
    }
    
    .form-control:focus ~ .form-label,
    .form-control:not(:placeholder-shown) ~ .form-label {
        opacity: 0.65;
        transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        color: #3B82F6;
    }
    
    .form-control::placeholder {
        color: transparent;
    }
    
    .form-control:focus::placeholder {
        color: #9CA3AF;
    }
    
    .pulse-focus:focus {
        animation: pulse 1.5s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.4); }
        70% { box-shadow: 0 0 0 6px rgba(139, 92, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0); }
    }
    
    /* Ensure password toggle doesn't overlap with input text */
    .password-field .form-control {
        padding-right: 2.75rem;
    }
");
?>

<!-- Start of body content -->
<?php include 'navbar.php'; ?>

<div class="container mx-auto px-4 py-12">
    <div class="register-container">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-3 font-serif">Create an Account</h1>
            <p class="text-gray-600">Join Typoria and start sharing your thoughts</p>
        </div>
        
        <!-- Registration Form -->
        <div class="register-form bg-white rounded-xl shadow-md p-8 mb-6">
            <div class="gradient-line mb-8"></div>
            
            <?php if (!empty($error_message)) : ?>
                <div class="alert alert-error" id="error-alert">
                    <svg class="alert-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="font-medium"><?php echo $error_message; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)) : ?>
                <div class="alert alert-success" id="success-alert">
                    <svg class="alert-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="font-medium"><?php echo $success_message; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registration-form">
                <!-- Name Field -->
                <div class="form-group">
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="input-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                        <input type="text" id="name" name="name" class="form-control" placeholder=" " 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                        <label for="name" class="form-label">Full Name</label>
                    </div>
                </div>
                
                <!-- Email Field -->
                <div class="form-group">
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="input-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                        <input type="email" id="email" name="email" class="form-control" placeholder=" " 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <label for="email" class="form-label">Email Address</label>
                    </div>
                    <p class="text-xs mt-1 text-gray-500">We'll send a verification code to this email</p>
                </div>
                
                <!-- Password Field -->
                <div class="form-group">
                    <div class="password-field relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="input-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                        <input type="password" id="password" name="password" class="form-control" placeholder=" " 
                               onkeyup="checkPasswordStrength()" required>
                        <label for="password" class="form-label">Password</label>
                        
                        <!-- Password Toggle Button -->
                        <button type="button" id="toggle-password" class="password-toggle" tabindex="-1" aria-label="Toggle password visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" id="eye-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" id="eye-slash-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                                <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Password Strength Meter -->
                    <div class="strength-meter mt-2">
                        <div id="strength-bar"></div>
                    </div>
                    <p id="strength-text" class="text-xs mt-1 text-gray-500">Password should be at least 8 characters long</p>
                    
                    <!-- Password Requirements -->
                    <div class="mt-2 grid grid-cols-2 gap-1">
                        <div class="flex items-center">
                            <span id="length-check" class="inline-block w-4 h-4 rounded-full mr-2 border border-gray-300"></span>
                            <span class="text-xs text-gray-500">8+ characters</span>
                        </div>
                        <div class="flex items-center">
                            <span id="uppercase-check" class="inline-block w-4 h-4 rounded-full mr-2 border border-gray-300"></span>
                            <span class="text-xs text-gray-500">Uppercase</span>
                        </div>
                        <div class="flex items-center">
                            <span id="lowercase-check" class="inline-block w-4 h-4 rounded-full mr-2 border border-gray-300"></span>
                            <span class="text-xs text-gray-500">Lowercase</span>
                        </div>
                        <div class="flex items-center">
                            <span id="number-check" class="inline-block w-4 h-4 rounded-full mr-2 border border-gray-300"></span>
                            <span class="text-xs text-gray-500">Number</span>
                        </div>
                    </div>
                </div>
                
                <!-- Confirm Password Field -->
                <div class="form-group mb-8">
                    <div class="password-field relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="input-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder=" " 
                               onkeyup="checkPasswordMatch()" required>
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        
                        <!-- Confirm Password Toggle Button -->
                        <button type="button" id="toggle-confirm-password" class="password-toggle" tabindex="-1" aria-label="Toggle password visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" id="confirm-eye-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" id="confirm-eye-slash-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                                <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                            </svg>
                        </button>
                    </div>
                    <p id="match-message" class="text-xs mt-1 hidden">Passwords do not match</p>
                </div>
                
                <!-- Register Button -->
                <button type="submit" id="submit-button" class="w-full gradient-button text-white px-4 py-3 rounded-lg font-medium transition-all">
                    Create Account
                </button>
                
                <p class="text-xs mt-4 text-gray-500 text-center">
                    By creating an account, you agree to our<br>
                    <a href="terms.php" class="text-typoria-primary hover:underline">Terms of Service</a> and 
                    <a href="privacy.php" class="text-typoria-primary hover:underline">Privacy Policy</a>
                </p>
            </form>
        </div>
        
        <!-- Login Link -->
        <div class="text-center">
            <p class="text-gray-600">
                Already have an account? 
                <a href="login.php<?php echo !empty($redirect) && $redirect != 'index.php' ? '?redirect=' . urlencode($redirect) : ''; ?>" class="text-typoria-primary hover:text-typoria-secondary font-medium">
                    Log In
                </a>
            </p>
        </div>
    </div>
</div>

<!-- JavaScript for password validation and toggle -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        const togglePassword = document.getElementById('toggle-password');
        const password = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        const eyeSlashIcon = document.getElementById('eye-slash-icon');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle icons
            eyeIcon.classList.toggle('hidden');
            eyeSlashIcon.classList.toggle('hidden');
        });
        
        // Toggle confirm password visibility
        const toggleConfirmPassword = document.getElementById('toggle-confirm-password');
        const confirmPassword = document.getElementById('confirm_password');
        const confirmEyeIcon = document.getElementById('confirm-eye-icon');
        const confirmEyeSlashIcon = document.getElementById('confirm-eye-slash-icon');
        
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            
            // Toggle icons
            confirmEyeIcon.classList.toggle('hidden');
            confirmEyeSlashIcon.classList.toggle('hidden');
        });
        
        // Form validation on submit
        const form = document.getElementById('registration-form');
        
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Validate name
            const name = document.getElementById('name');
            if (name.value.trim() === '') {
                isValid = false;
                highlightError(name, 'Name is required');
            }
            
            // Validate email
            const email = document.getElementById('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                isValid = false;
                highlightError(email, 'Please enter a valid email address');
            }
            
            // Validate password
            const password = document.getElementById('password');
            if (password.value.length < 8) {
                isValid = false;
                highlightError(password, 'Password must be at least 8 characters long');
            }
            
            // Validate password match
            const confirmPassword = document.getElementById('confirm_password');
            if (password.value !== confirmPassword.value) {
                isValid = false;
                highlightError(confirmPassword, 'Passwords do not match');
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
        
        // Function to highlight error fields
        function highlightError(element, message) {
            element.classList.add('border-red-500');
            element.classList.add('error-shake');
            
            // Create or update error message
            let errorElement = element.parentElement.querySelector('.error-message');
            
            if (!errorElement) {
                errorElement = document.createElement('p');
                errorElement.className = 'error-message text-xs mt-1 text-red-500';
                element.parentElement.appendChild(errorElement);
            }
            
            errorElement.textContent = message;
            
            // Remove shake animation after it completes
            setTimeout(() => {
                element.classList.remove('error-shake');
            }, 500);
        }
        
        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                if (alert) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }
            }, 5000);
        });
    });
    
    function checkPasswordStrength() {
        const password = document.getElementById('password').value;
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        
        // Check requirements
        const lengthCheck = document.getElementById('length-check');
        const uppercaseCheck = document.getElementById('uppercase-check');
        const lowercaseCheck = document.getElementById('lowercase-check');
        const numberCheck = document.getElementById('number-check');
        
        // Length check
        if (password.length >= 8) {
            lengthCheck.className = 'inline-block w-4 h-4 rounded-full mr-2 bg-green-500';
        } else {
            lengthCheck.className = 'inline-block w-4 h-4 rounded-full mr-2 border border-gray-300';
        }
        
        // Uppercase check
        if (/[A-Z]/.test(password)) {
            uppercaseCheck.className = 'inline-block w-4 h-4 rounded-full mr-2 bg-green-500';
        } else {
            uppercaseCheck.className = 'inline-block w-4 h-4 rounded-full mr-2 border border-gray-300';
        }
        
        // Lowercase check
        if (/[a-z]/.test(password)) {
            lowercaseCheck.className = 'inline-block w-4 h-4 rounded-full mr-2 bg-green-500';
        } else {
            lowercaseCheck.className = 'inline-block w-4 h-4 rounded-full mr-2 border border-gray-300';
        }
        
        // Number check
        if (/[0-9]/.test(password)) {
            numberCheck.className = 'inline-block w-4 h-4 rounded-full mr-2 bg-green-500';
        } else {
            numberCheck.className = 'inline-block w-4 h-4 rounded-full mr-2 border border-gray-300';
        }
        
        // Remove all classes
        strengthBar.className = '';
        
        if (password.length === 0) {
            strengthBar.style.width = '0%';
            strengthText.textContent = 'Password should be at least 8 characters long';
            strengthText.className = 'text-xs mt-1 text-gray-500';
            return;
        }
        
        // Calculate strength
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength += 1;
        if (password.length >= 12) strength += 1;
        
        // Character variety checks
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[a-z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        // Update the strength meter with animation
        if (strength < 3) {
            strengthBar.className = 'weak';
            strengthBar.style.width = '25%';
            strengthText.textContent = 'Weak password';
            strengthText.className = 'text-xs mt-1 text-red-500';
        } else if (strength < 5) {
            strengthBar.className = 'medium';
            strengthBar.style.width = '50%';
            strengthText.textContent = 'Medium strength password';
            strengthText.className = 'text-xs mt-1 text-yellow-600';
        } else if (strength < 7) {
            strengthBar.className = 'strong';
            strengthBar.style.width = '75%';
            strengthText.textContent = 'Strong password';
            strengthText.className = 'text-xs mt-1 text-green-600';
        } else {
            strengthBar.className = 'very-strong';
            strengthBar.style.width = '100%';
            strengthText.textContent = 'Very strong password';
            strengthText.className = 'text-xs mt-1 text-green-700';
        }
        
        // Also check match if confirm password has value
        if (document.getElementById('confirm_password').value) {
            checkPasswordMatch();
        }
    }
    
    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const matchMessage = document.getElementById('match-message');
        const confirmInput = document.getElementById('confirm_password');
        
        if (confirmPassword.length === 0) {
            matchMessage.classList.add('hidden');
            confirmInput.classList.remove('border-red-500');
            confirmInput.classList.remove('border-green-500');
            return;
        }
        
        if (password === confirmPassword) {
            matchMessage.textContent = 'Passwords match';
            matchMessage.className = 'text-xs mt-1 text-green-600';
            confirmInput.classList.remove('border-red-500');
            confirmInput.classList.add('border-green-500');
        } else {
            matchMessage.textContent = 'Passwords do not match';
            matchMessage.className = 'text-xs mt-1 text-red-500';
            confirmInput.classList.remove('border-green-500');
            confirmInput.classList.add('border-red-500');
        }
    }
</script>

<?php 
// Generate footer
typoria_footer(); 
?>