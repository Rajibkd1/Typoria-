<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                       // Set the SMTP server
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username = 'rajibinf00@gmail.com'; // Your email
    $mail->Password = 'dbld phzr atar fmqe';             // SMTP password (use app password for Gmail)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
    $mail->Port       = 587;                                   // TCP port to connect to

    // Recipients
    $mail->setFrom('rajibinf00@gmail.com', 'Rajib');
    $mail->addAddress('rrajibkd@gmail.com', 'Recipient Name'); // Add a recipient

    // Attach a PDF file
    $mail->addAttachment('./shafaetsplanet.com-গরাফ অযালগরিদম বই.pdf', 'Optional-Filename.pdf'); // Path to the file and optional custom name

    // Content
    $mail->isHTML(true);                                        // Set email format to HTML
    $mail->Subject = 'Test Email with PDF Attachment';
    $mail->Body    = 'Please find the attached PDF file.';

    // Send the email
    $mail->send();
    echo 'Email has been sent successfully.';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

?>
