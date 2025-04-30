<?php
/**
 * Typoria Blog Platform
 * Email Utility Functions for OTP and Notifications
 */

// Include PHPMailer classes if not using Composer autoload
// require_once 'path/to/PHPMailer/src/Exception.php';
// require_once 'path/to/PHPMailer/src/PHPMailer.php';
// require_once 'path/to/PHPMailer/src/SMTP.php';

// If using Composer, uncomment this line:
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email using Gmail SMTP
 * 
 * @param string $to_email Recipient's email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML format)
 * @param string $alt_body Plain text alternative (optional)
 * @return bool True on success, false on failure
 */
function send_email($to_email, $subject, $body, $alt_body = '') {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true); // true enables exceptions
    
    try {
        // Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Uncomment for debugging
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rajibinf00@gmail.com'; // CHANGE THIS: Your Gmail address
        $mail->Password   = 'dbld phzr atar fmqe'; // CHANGE THIS: Your Gmail app password (not your regular password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Sender and recipient
        $mail->setFrom('rajibinf00@gmail.com', 'Typoria Blog'); // CHANGE THIS: Your Gmail address and name
        $mail->addAddress($to_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $alt_body ?: strip_tags($body);
        
        // Send the email
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error or handle it as needed
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Generate and send OTP via email
 * 
 * @param string $to_email Recipient's email address
 * @param string $name Recipient's name
 * @return array|bool Array with OTP and expiry time on success, false on failure
 */
function send_otp_email($to_email, $name) {
    // Generate a 6-digit OTP
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    
    // Set expiry time (15 minutes from now)
    $expiry_time = time() + (15 * 60);
    
    // Create HTML email body
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #e1e1e1;
                border-radius: 5px;
            }
            .header {
                background: linear-gradient(135deg, #3B82F6, #8B5CF6);
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 5px 5px 0 0;
                margin: -20px -20px 20px;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 12px;
                color: #666;
            }
            .otp {
                font-size: 24px;
                font-weight: bold;
                text-align: center;
                letter-spacing: 5px;
                margin: 30px 0;
                padding: 10px;
                background-color: #f7f7f7;
                border-radius: 5px;
            }
            .note {
                font-size: 14px;
                color: #777;
                font-style: italic;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Typoria Email Verification</h1>
            </div>
            <p>Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
            <p>Thank you for registering with Typoria! To complete your registration, please use the OTP code below to verify your email address:</p>
            
            <div class="otp">' . $otp . '</div>
            
            <p>This code will expire in 15 minutes.</p>
            
            <p class="note">If you did not request this verification, please ignore this email or contact support if you have concerns.</p>
            
            <div class="footer">
                <p>&copy; ' . date('Y') . ' Typoria Blog. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Plain text alternative
    $alt_body = "Hello $name,\n\n"
              . "Thank you for registering with Typoria! To complete your registration, please use the OTP code below to verify your email address:\n\n"
              . "$otp\n\n"
              . "This code will expire in 15 minutes.\n\n"
              . "If you did not request this verification, please ignore this email or contact support if you have concerns.";
    
    // Send the email
    $email_sent = send_email($to_email, 'Verify Your Email - Typoria', $body, $alt_body);
    
    if ($email_sent) {
        return [
            'otp' => $otp,
            'expiry_time' => $expiry_time
        ];
    }
    
    return false;
}

/**
 * Send welcome email after successful registration
 * 
 * @param string $to_email Recipient's email address
 * @param string $name Recipient's name
 * @return bool True on success, false on failure
 */
function send_welcome_email($to_email, $name) {
    // Create HTML email body
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #e1e1e1;
                border-radius: 5px;
            }
            .header {
                background: linear-gradient(135deg, #3B82F6, #8B5CF6);
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 5px 5px 0 0;
                margin: -20px -20px 20px;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 12px;
                color: #666;
            }
            .button {
                display: inline-block;
                background: linear-gradient(135deg, #3B82F6, #8B5CF6);
                color: white;
                text-decoration: none;
                padding: 10px 20px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .section {
                margin: 25px 0;
            }
            .section-title {
                font-weight: bold;
                color: #3B82F6;
                margin-bottom: 10px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Welcome to Typoria!</h1>
            </div>
            
            <p>Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
            <p>Thank you for joining the Typoria community! Your account has been successfully created, and you can now start sharing your thoughts and connecting with other writers.</p>
            
            <div class="section">
                <div class="section-title">Getting Started</div>
                <p>Here are a few things you can do:</p>
                <ul>
                    <li>Create your first blog post</li>
                    <li>Explore posts by other writers</li>
                    <li>Complete your profile</li>
                    <li>Follow authors you enjoy</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <a href="https://yourdomain.com/login.php" class="button">Start Writing</a>
            </div>
            
            <div class="section">
                <div class="section-title">Need Help?</div>
                <p>If you have any questions or need assistance, don\'t hesitate to contact our support team.</p>
            </div>
            
            <div class="footer">
                <p>&copy; ' . date('Y') . ' Typoria Blog. All rights reserved.</p>
                <p>You received this email because you signed up for Typoria.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Send the email
    return send_email($to_email, 'Welcome to Typoria!', $body);
}

/**
 * Send notification email (for comments, follows, etc.)
 * 
 * @param string $to_email Recipient's email address
 * @param string $name Recipient's name
 * @param string $notification_type Type of notification
 * @param array $data Additional data for the notification
 * @return bool True on success, false on failure
 */
function send_notification_email($to_email, $name, $notification_type, $data) {
    $subject = '';
    $body = '';
    
    switch ($notification_type) {
        case 'comment':
            $subject = 'New Comment on Your Post - Typoria';
            $body = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                    }
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                        border: 1px solid #e1e1e1;
                        border-radius: 5px;
                    }
                    .header {
                        background: linear-gradient(135deg, #3B82F6, #8B5CF6);
                        color: white;
                        padding: 15px;
                        text-align: center;
                        border-radius: 5px 5px 0 0;
                        margin: -20px -20px 20px;
                    }
                    .footer {
                        margin-top: 30px;
                        text-align: center;
                        font-size: 12px;
                        color: #666;
                    }
                    .button {
                        display: inline-block;
                        background: linear-gradient(135deg, #3B82F6, #8B5CF6);
                        color: white;
                        text-decoration: none;
                        padding: 8px 15px;
                        border-radius: 5px;
                        margin: 15px 0;
                    }
                    .comment {
                        background-color: #f7f7f7;
                        padding: 15px;
                        border-left: 4px solid #3B82F6;
                        margin: 15px 0;
                        border-radius: 0 5px 5px 0;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>New Comment on Your Post</h2>
                    </div>
                    
                    <p>Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
                    <p><strong>' . htmlspecialchars($data['commenter_name']) . '</strong> has commented on your post "<strong>' . htmlspecialchars($data['post_title']) . '</strong>":</p>
                    
                    <div class="comment">
                        "' . htmlspecialchars($data['comment_text']) . '"
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="' . $data['post_url'] . '" class="button">View Comment</a>
                    </div>
                    
                    <div class="footer">
                        <p>&copy; ' . date('Y') . ' Typoria Blog. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ';
            break;
            
        case 'follow':
            $subject = 'New Follower on Typoria';
            $body = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                    }
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                        border: 1px solid #e1e1e1;
                        border-radius: 5px;
                    }
                    .header {
                        background: linear-gradient(135deg, #3B82F6, #8B5CF6);
                        color: white;
                        padding: 15px;
                        text-align: center;
                        border-radius: 5px 5px 0 0;
                        margin: -20px -20px 20px;
                    }
                    .footer {
                        margin-top: 30px;
                        text-align: center;
                        font-size: 12px;
                        color: #666;
                    }
                    .button {
                        display: inline-block;
                        background: linear-gradient(135deg, #3B82F6, #8B5CF6);
                        color: white;
                        text-decoration: none;
                        padding: 8px 15px;
                        border-radius: 5px;
                        margin: 15px 0;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>You Have a New Follower!</h2>
                    </div>
                    
                    <p>Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
                    <p><strong>' . htmlspecialchars($data['follower_name']) . '</strong> has started following you on Typoria.</p>
                    
                    <div style="text-align: center;">
                        <a href="' . $data['profile_url'] . '" class="button">View Profile</a>
                    </div>
                    
                    <div class="footer">
                        <p>&copy; ' . date('Y') . ' Typoria Blog. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ';
            break;
            
        // Add more notification types as needed
    }
    
    // Send the email
    return send_email($to_email, $subject, $body);
}