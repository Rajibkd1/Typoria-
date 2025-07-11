<?php
/**
 * Typoria Blog Platform
 * OTP Verification Page
 */

// Include required files
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/theme.php';
require_once 'includes/mailer.php';

// Check if session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize database connection
$conn = get_db_connection();

// Check if registration data exists
if (!isset($_SESSION['registration'])) {
    // Redirect to registration page
    header("Location: register.php");
    exit();
}

$registration_data = $_SESSION['registration'];
$email = $registration_data['email'];
$name = $registration_data['name'];

// If OTP not set or expired, generate a new one
if (!isset($_SESSION['otp_data']) || time() > $_SESSION['otp_data']['expiry_time']) {
    // Generate and send new OTP
    $otp_data = send_otp_email($email, $name);
    
    if ($otp_data) {
        $_SESSION['otp_data'] = $otp_data;
        $success_message = "A verification code has been sent to $email";
    } else {
        $error_message = "Failed to send verification code. Please try again.";
    }
} else {
    // Use existing OTP
    $otp_data = $_SESSION['otp_data'];
    $expiry_time = $otp_data['expiry_time'];
    $minutes_remaining = ceil(($expiry_time - time()) / 60);
    $success_message = "Your verification code is valid for another $minutes_remaining minutes";
}

// Debug: Log OTP data for troubleshooting
error_log("Current OTP: " . (isset($_SESSION['otp_data']['otp']) ? $_SESSION['otp_data']['otp'] : 'Not set'));

// Handle AJAX OTP verification
if (isset($_POST['ajax_verify']) && isset($_POST['otp'])) {
    $user_otp = trim($_POST['otp']);
    $response = ['success' => false, 'message' => ''];
    
    // Debug: Log submitted OTP
    error_log("User submitted OTP: $user_otp");
    
    // Validate input
    if (empty($user_otp)) {
        $response['message'] = "Please enter the verification code";
    } elseif (!isset($_SESSION['otp_data'])) {
        $response['message'] = "Verification code not found. Please request a new one.";
    } elseif (time() > $_SESSION['otp_data']['expiry_time']) {
        $response['message'] = "Verification code has expired. Please request a new one.";
    } elseif ($user_otp != $_SESSION['otp_data']['otp']) {
        $response['message'] = "Invalid verification code. Please try again.";
        // Debug: Log comparison
        error_log("OTP Comparison - User: $user_otp vs Stored: " . $_SESSION['otp_data']['otp']);
    } else {
        // OTP verified, create new user
        $name = $registration_data['name'];
        $email = $registration_data['email'];
        $password = $registration_data['password'];
        
        try {
            // Register the user
            $result = register_user($name, $email, $password);
            
            if ($result === true) {
                // Send welcome email
                send_welcome_email($email, $name);
                
                // Clear registration session data
                unset($_SESSION['registration']);
                unset($_SESSION['otp_data']);
                
                // Set success flag
                $response['success'] = true;
                $response['message'] = "Account created successfully!";
                $response['redirect'] = "index.php";
            } else {
                $response['message'] = $result;
            }
        } catch (Exception $e) {
            error_log("Error during registration: " . $e->getMessage());
            $response['message'] = "An error occurred during registration. Please try again.";
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle standard form submission
$error_message = "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $user_otp = trim($_POST['otp']);
    
    // Validate input
    if (empty($user_otp)) {
        $error_message = "Please enter the verification code";
    } elseif (!isset($_SESSION['otp_data'])) {
        $error_message = "Verification code not found. Please request a new one.";
    } elseif (time() > $_SESSION['otp_data']['expiry_time']) {
        $error_message = "Verification code has expired. Please request a new one.";
    } elseif ($user_otp != $_SESSION['otp_data']['otp']) {
        $error_message = "Invalid verification code. Please try again.";
    } else {
        // OTP verified, create new user
        $name = $registration_data['name'];
        $email = $registration_data['email'];
        $password = $registration_data['password'];
        
        // Register the user
        $result = register_user($name, $email, $password);
        
        if ($result === true) {
            // Send welcome email
            send_welcome_email($email, $name);
            
            // Clear registration session data
            unset($_SESSION['registration']);
            unset($_SESSION['otp_data']);
            
            // Set success message and redirect
            typoria_flash_message("Account created successfully! You are now logged in.", 'success');
            header("Location: index.php");
            exit();
        } else {
            $error_message = $result;
        }
    }
}

// Handle resend OTP request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])) {
    // Generate and send new OTP
    $otp_data = send_otp_email($email, $name);
    
    if ($otp_data) {
        $_SESSION['otp_data'] = $otp_data;
        $success_message = "A new verification code has been sent to $email";
    } else {
        $error_message = "Failed to send verification code. Please try again.";
    }
}

// Generate HTML header
typoria_header("Verify Email", "
    .otp-container {
        max-width: 480px;
        margin: 0 auto;
    }
    
    .otp-form {
        transition: transform 0.3s ease-out, box-shadow 0.3s ease-out;
        background: linear-gradient(to bottom, #ffffff, #fafafa);
        border: 1px solid rgba(229, 231, 235, 0.8);
    }
    
    .otp-form:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    }
    
    .gradient-line {
        height: 4px;
        background: linear-gradient(135deg, #3B82F6, #8B5CF6);
        border-radius: 4px;
        animation: shimmer 2s infinite linear;
        background-size: 200% 100%;
    }
    
    @keyframes shimmer {
        0% { background-position: 100% 0; }
        100% { background-position: -100% 0; }
    }
    
    .otp-input {
        width: 100%;
        letter-spacing: 1.5rem;
        text-align: center;
        font-size: 1.5rem;
    }
    
    .timer {
        font-size: 0.875rem;
        color: #6B7280;
        text-align: center;
        margin-top: 1rem;
    }
    
    .timer-highlight {
        font-weight: bold;
        color: #3B82F6;
    }
    
    .digit-group {
        display: flex;
        justify-content: center;
        gap: 10px;
    }
    
    .digit-input {
        width: 50px;
        height: 60px;
        border: 2px solid #d1d5db;
        border-radius: 12px;
        text-align: center;
        font-size: 28px;
        font-weight: bold;
        transition: all 0.2s ease;
        background-color: #f9fafb;
    }
    
    .digit-input:focus {
        border-color: #3B82F6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        outline: none;
        background-color: white;
    }
    
    #status-message {
        display: none;
        padding: 1rem;
        border-radius: 0.75rem;
        margin-top: 1rem;
    }
    
    .status-success {
        background-color: #D1FAE5;
        color: #065F46;
        border-left: 4px solid #10B981;
    }
    
    .status-error {
        background-color: #FEE2E2;
        color: #B91C1C;
        border-left: 4px solid #EF4444;
    }
    
    .loading {
        display: none;
        text-align: center;
        margin-top: 1rem;
    }
    
    .loading-spinner {
        display: inline-block;
        width: 30px;
        height: 30px;
        border: 3px solid rgba(59, 130, 246, 0.3);
        border-radius: 50%;
        border-top-color: #3B82F6;
        animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .gradient-button {
        background: linear-gradient(135deg, #3B82F6, #8B5CF6);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
    }
    
    .gradient-button:hover {
        background: linear-gradient(135deg, #2563EB, #7C3AED);
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(139, 92, 246, 0.3);
    }
    
    .gradient-button:active {
        transform: translateY(1px);
    }
    
    .spam-notice {
        background-color: #FEF3C7;
        border-left: 4px solid #F59E0B;
        color: #92400E;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-top: 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: flex-start;
    }
    
    .spam-notice-icon {
        flex-shrink: 0;
        margin-right: 0.75rem;
        margin-top: 0.125rem;
    }
    
    .email-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #3B82F6, #8B5CF6);
        border-radius: 50%;
        margin: 0 auto 1.5rem;
        color: white;
    }
    
    .resend-container {
        background-color: #F9FAFB;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-top: 1.5rem;
        text-align: center;
        border: 1px solid #E5E7EB;
    }
    
    .resend-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1.25rem;
        background-color: white;
        color: #3B82F6;
        border: 1px solid #E5E7EB;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    
    .resend-button:hover {
        background-color: #F3F4F6;
        border-color: #D1D5DB;
        color: #2563EB;
    }
    
    .resend-button svg {
        margin-right: 0.5rem;
    }
");
?>

<!-- Start of body content -->
<?php include 'navbar.php'; ?>

<div class="container mx-auto px-4 py-12">
    <div class="otp-container">
        <div class="text-center mb-6">
            <div class="email-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-3 font-serif">Verify Your Email</h1>
            <p class="text-gray-600">We've sent a verification code to <span class="font-medium"><?php echo htmlspecialchars($email); ?></span></p>
        </div>
        
        <!-- OTP Form -->
        <div class="otp-form bg-white rounded-xl shadow-md p-8 mb-6">
            <div class="gradient-line mb-8"></div>
            
            <?php if (!empty($error_message)) : ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)) : ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                    <p><?php echo $success_message; ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Spam Notice -->
            <div class="spam-notice">
                <svg xmlns="http://www.w3.org/2000/svg" class="spam-notice-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="font-medium">Can't find the verification code?</p>
                    <p class="mt-1">Please check your spam or junk folder. If you still don't see it, click the "Resend Code" button below.</p>
                </div>
            </div>
            
            <form method="POST" id="otp-form">
                <!-- Hidden input to store complete OTP -->
                <input type="hidden" id="complete-otp" name="otp">
                
                <!-- OTP Input Group -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-4 text-center">Enter Verification Code</label>
                    <div class="digit-group">
                        <input type="text" id="digit-1" class="digit-input" maxlength="1" autocomplete="off">
                        <input type="text" id="digit-2" class="digit-input" maxlength="1" autocomplete="off">
                        <input type="text" id="digit-3" class="digit-input" maxlength="1" autocomplete="off">
                        <input type="text" id="digit-4" class="digit-input" maxlength="1" autocomplete="off">
                        <input type="text" id="digit-5" class="digit-input" maxlength="1" autocomplete="off">
                        <input type="text" id="digit-6" class="digit-input" maxlength="1" autocomplete="off">
                    </div>
                </div>
                
                <!-- Status Message -->
                <div id="status-message"></div>
                
                <!-- Loading Indicator -->
                <div class="loading" id="loading-indicator">
                    <div class="loading-spinner"></div>
                    <p class="mt-2 text-gray-600">Verifying...</p>
                </div>
                
                <!-- Timer Display -->
                <?php if (isset($_SESSION['otp_data']) && isset($_SESSION['otp_data']['expiry_time'])): ?>
                    <div class="timer">
                        Code expires in <span id="timer" class="timer-highlight">15:00</span>
                    </div>
                    
                    <script>
                        // Set the expiry time
                        const expiryTime = <?php echo $_SESSION['otp_data']['expiry_time']; ?> * 1000; // Convert to milliseconds
                        
                        // Update the countdown timer
                        function updateTimer() {
                            const now = new Date().getTime();
                            const timeLeft = expiryTime - now;
                            
                            if (timeLeft <= 0) {
                                document.getElementById('timer').innerHTML = "Expired";
                                return;
                            }
                            
                            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                            
                            document.getElementById('timer').innerHTML = 
                                (minutes < 10 ? "0" + minutes : minutes) + ":" + 
                                (seconds < 10 ? "0" + seconds : seconds);
                        }
                        
                        // Update timer immediately
                        updateTimer();
                        
                        // Update timer every second
                        setInterval(updateTimer, 1000);
                    </script>
                <?php endif; ?>
                
                <!-- Verify Button (fallback for non-JS browsers) -->
                <button type="submit" name="verify_otp" id="verify-button" class="w-full gradient-button text-white px-4 py-3 rounded-lg font-medium transition-all mt-6">
                    Verify
                </button>
            </form>
        </div>
        
        <!-- Resend OTP -->
        <div class="resend-container">
            <p class="text-gray-700 mb-3">
                Didn't receive the code or code expired?
            </p>
            <form method="post">
                <button type="submit" name="resend_otp" class="resend-button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                    </svg>
                    Resend Code
                </button>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for OTP input enhancement -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const digitInputs = document.querySelectorAll('.digit-input');
        const completeOtpInput = document.getElementById('complete-otp');
        const form = document.getElementById('otp-form');
        const statusMessage = document.getElementById('status-message');
        const loadingIndicator = document.getElementById('loading-indicator');
        
        // Focus first input on page load
        digitInputs[0].focus();
        
        // Handle digit input
        digitInputs.forEach((input, index) => {
            // Only allow numbers
            input.addEventListener('keypress', function(e) {
                if (isNaN(parseInt(e.key))) {
                    e.preventDefault();
                }
            });
            
            // Auto-tab to next input
            input.addEventListener('input', function() {
                if (this.value.length === 1) {
                    // Move to next input if available
                    if (index < digitInputs.length - 1) {
                        digitInputs[index + 1].focus();
                    }
                    
                    // Check if all digits are filled
                    checkAllDigits();
                }
            });
            
            // Handle backspace
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    digitInputs[index - 1].focus();
                }
            });
            
            // Handle paste event
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                
                // Get pasted content
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                
                // Skip if not a number
                if (!/^\d+$/.test(pastedText)) return;
                
                // Distribute digits across inputs
                const digits = pastedText.split('');
                for (let i = 0; i < digitInputs.length && i < digits.length; i++) {
                    digitInputs[i].value = digits[i];
                    
                    // Focus on next empty input if available
                    if (i < digitInputs.length - 1 && i === digits.length - 1) {
                        digitInputs[i + 1].focus();
                    }
                }
                
                // Check if all digits are filled
                checkAllDigits();
            });
        });
        
        // Check if all digits are filled and verify OTP
        function checkAllDigits() {
            // Combine all digits
            let otp = '';
            let allFilled = true;
            
            digitInputs.forEach(input => {
                otp += input.value;
                if (input.value === '') {
                    allFilled = false;
                }
            });
            
            // Update hidden input
            completeOtpInput.value = otp;
            
            // If all inputs are filled, verify OTP automatically
            if (allFilled && otp.length === 6) {
                verifyOtp(otp);
            }
        }
        
        // Verify OTP via AJAX
        function verifyOtp(otp) {
            // Show loading indicator
            loadingIndicator.style.display = 'block';
            statusMessage.style.display = 'none';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('ajax_verify', '1');
            formData.append('otp', otp);
            
            // Debug
            console.log("Sending OTP: " + otp);
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                // Hide loading indicator
                loadingIndicator.style.display = 'none';
                
                // Show status message
                statusMessage.style.display = 'block';
                
                console.log("Server response:", data);
                
                if (data.success) {
                    // Success
                    statusMessage.className = 'status-success';
                    statusMessage.textContent = data.message;
                    
                    // Disable inputs
                    digitInputs.forEach(input => {
                        input.disabled = true;
                    });
                    
                    // Redirect after a delay
                    setTimeout(() => {
                        window.location.href = data.redirect || 'index.php';
                    }, 1500);
                } else {
                    // Error
                    statusMessage.className = 'status-error';
                    statusMessage.textContent = data.message || 'Verification failed. Please try again.';
                    
                    // Clear inputs for retry
                    digitInputs.forEach(input => {
                        input.value = '';
                    });
                    digitInputs[0].focus();
                }
            })
            .catch(error => {
                // Hide loading indicator
                loadingIndicator.style.display = 'none';
                
                // Show error
                statusMessage.style.display = 'block';
                statusMessage.className = 'status-error';
                statusMessage.textContent = 'An error occurred during verification. Please try again.';
                console.error('Error:', error);
            });
        }
        
        // Handle form submission (fallback for non-JS browsers)
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Combine all digits
            let otp = '';
            digitInputs.forEach(input => {
                otp += input.value;
            });
            
            // Validate
            if (otp.length !== 6) {
                statusMessage.style.display = 'block';
                statusMessage.className = 'status-error';
                statusMessage.textContent = 'Please enter all 6 digits of the verification code.';
                return;
            }
            
            // Verify
            verifyOtp(otp);
        });
    });
</script>

<?php 
// Generate footer
typoria_footer(); 
?>