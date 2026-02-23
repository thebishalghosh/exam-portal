<?php

// Require Composer's autoloader for PHPMailer
require_once ROOT_PATH . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    public function sendEmail($to, $subject, $body) {
        $mail = new PHPMailer(true); // Passing true enables exceptions

        try {
            // Server settings (defaults are Mailpit-friendly)
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = getenv('SMTP_HOST') ?: 'localhost';     // SMTP server
            $mail->SMTPAuth   = false;                                  // assume no auth unless configured
            $mail->Port       = getenv('SMTP_PORT') ?: 1025;            // default Mailpit port

            // security settings – allow overriding via env vars
            $secure = getenv('SMTP_SECURE');                            // e.g. '', 'tls', 'ssl'
            if ($secure) {
                $mail->SMTPSecure = $secure;
            } else {
                // Mailpit / many local servers don't implement STARTTLS
                $mail->SMTPSecure = '';
            }
            // don't auto‑upgrade to TLS unless explicitly asked
            $mail->SMTPAutoTLS = false;

            // credentials (if SMTPAuth true then these should be set)
            $user = getenv('SMTP_USERNAME');
            $pass = getenv('SMTP_PASSWORD');
            if ($user && $pass) {
                $mail->SMTPAuth = true;
                $mail->Username = $user;
                $mail->Password = $pass;
            }

            // Recipients
            $mail->setFrom('no-reply@exam.travarsa.net', 'Travarsa Exam Portal'); // Sender
            $mail->addAddress($to);                                     // Add a recipient

            // Content
            $mail->isHTML(true);                                        // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);                         // Plain text for non-HTML mail clients

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log the error for debugging and rethrow so callers know something went wrong
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            throw $e;
        }
    }
}
