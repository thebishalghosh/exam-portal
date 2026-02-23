<?php
// Lightweight mail debug script for deployment verification.
// Visit: http://your-domain/exam/public/debugmail.php?to=you@example.com

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/app/services/EmailService.php';

header('Content-Type: text/html; charset=UTF-8');

$to = isset($_GET['to']) ? trim($_GET['to']) : '';
$status = null;
$error = null;

if ($to) {
    $subject = 'Exam Portal Mail Test';
    $body = '<p>This is a test email from the Exam Portal deployment.</p>'
          . '<p>Time: ' . date('Y-m-d H:i:s') . '</p>';

    $mailer = new EmailService();
    try {
        $ok = $mailer->sendEmail($to, $subject, $body);
        if ($ok) {
            $status = "Mail sent successfully to {$to}.";
        } else {
            $status = "sendEmail() returned false for {$to}.";
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debug Mail</title>
</head>
<body>
    <h1>Debug Mail</h1>
    <p>Current SMTP config (from environment):</p>
    <ul>
        <li>SMTP_HOST: <?php echo htmlspecialchars(getenv('SMTP_HOST') ?: ''); ?></li>
        <li>SMTP_PORT: <?php echo htmlspecialchars(getenv('SMTP_PORT') ?: ''); ?></li>
        <li>SMTP_SECURE: <?php echo htmlspecialchars(getenv('SMTP_SECURE') ?: ''); ?></li>
        <li>SMTP_USERNAME: <?php echo htmlspecialchars(getenv('SMTP_USERNAME') ?: '(not set)'); ?></li>
    </ul>

    <form method="get">
        <label>
            Send test email to:
            <input type="email" name="to" value="<?php echo htmlspecialchars($to ?: ''); ?>" required>
        </label>
        <button type="submit">Send Test Mail</button>
    </form>

    <?php if ($status): ?>
        <h2>Status</h2>
        <p><?php echo htmlspecialchars($status); ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <h2 style="color:red;">Error</h2>
        <pre><?php echo htmlspecialchars($error); ?></pre>
        <p>Also check your PHP error log for "Mailer Error" entries.</p>
    <?php endif; ?>
</body>
</html>

