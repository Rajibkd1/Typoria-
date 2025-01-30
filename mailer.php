<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php'; // Ensure this path is correct

function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Change for another SMTP provider
        $mail->SMTPAuth = true;
        $mail->Username = 'rajibinf00@gmail.com'; // Your email
        $mail->Password = 'dbld phzr atar fmqe'; // Use App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email Headers
        $mail->setFrom('rajibinf00@gmail.com', 'Your Website');
        $mail->addAddress($to);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;

        // Send email
        if ($mail->send()) {
            return true;
        } else {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
    } catch (Exception $e) {
        error_log("Exception: " . $mail->ErrorInfo);
        return false;
    }
}

?>
