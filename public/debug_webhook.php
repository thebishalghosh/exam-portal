<?php
// Robust debug tool for webhook + email on the Exam system.
// URL (on the server): http(s)://your-domain/exam/public/debug_webhook.php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/app/services/EmailService.php';

header('Content-Type: text/html; charset=UTF-8');

$baseUrl   = rtrim(getenv('APP_URL') ?: BASE_URL, '/');
$webhookEndpoint = $baseUrl . '/api/webhook/assign-exam';
$examApiKey = getenv('EXAM_API_KEY') ?: '';

$testEmail = isset($_POST['test_email']) ? trim($_POST['test_email']) : '';
$testName  = isset($_POST['test_name']) ? trim($_POST['test_name']) : 'Test User';
$testExamId = isset($_POST['test_exam_id']) ? (int)$_POST['test_exam_id'] : 1;
$headerKey = isset($_POST['header_key']) ? trim($_POST['header_key']) : $examApiKey;

$mailStatus = null;
$mailError  = null;
$webhookResult = null;
$webhookHttp  = null;
$webhookError = null;

// 1) Direct mail test using EmailService (same as production code)
if (isset($_POST['send_mail']) && $testEmail !== '') {
    $mailer = new EmailService();
    $subject = 'Exam Portal - Direct Mail Debug';
    $body = '<p>This is a direct mail debug message from the Exam Portal.</p>'
          . '<p>Time: ' . date('Y-m-d H:i:s') . '</p>';

    try {
        $ok = $mailer->sendEmail($testEmail, $subject, $body);
        $mailStatus = $ok ? "Direct mail: sent successfully to {$testEmail}." : "Direct mail: sendEmail() returned false.";
    } catch (\Exception $e) {
        $mailError = $e->getMessage();
    }
}

// 2) Webhook test – call /api/webhook/assign-exam with JSON & X-API-KEY
if (isset($_POST['call_webhook']) && $testEmail !== '' && $testExamId > 0) {
    $payload = [
        'name'    => $testName ?: 'Test User',
        'email'   => $testEmail,
        'phone'   => '',
        'exam_id' => $testExamId,
    ];

    $ch = curl_init($webhookEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-KEY: ' . $headerKey,
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $webhookResult = curl_exec($ch);
    $webhookHttp   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $webhookError  = curl_error($ch);
    curl_close($ch);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debug Webhook & Mail</title>
</head>
<body>
    <h1>Debug Webhook & Mail</h1>

    <h2>Environment snapshot</h2>
    <ul>
        <li>APP_URL: <code><?php echo htmlspecialchars(getenv('APP_URL') ?: ''); ?></code></li>
        <li>BASE_URL (runtime): <code><?php echo htmlspecialchars($baseUrl); ?></code></li>
        <li>Webhook endpoint: <code><?php echo htmlspecialchars($webhookEndpoint); ?></code></li>
        <li>EXAM_API_KEY (server env): <code><?php echo htmlspecialchars($examApiKey ?: '(empty)'); ?></code></li>
        <li>SMTP_HOST: <code><?php echo htmlspecialchars(getenv('SMTP_HOST') ?: ''); ?></code></li>
        <li>SMTP_PORT: <code><?php echo htmlspecialchars(getenv('SMTP_PORT') ?: ''); ?></code></li>
        <li>SMTP_SECURE: <code><?php echo htmlspecialchars(getenv('SMTP_SECURE') ?: ''); ?></code></li>
        <li>SMTP_USERNAME: <code><?php echo htmlspecialchars(getenv('SMTP_USERNAME') ?: '(not set)'); ?></code></li>
    </ul>

    <hr>

    <form method="post">
        <h2>Inputs</h2>
        <p>
            <label>
                Test email (candidate & direct mail recipient):<br>
                <input type="email" name="test_email" value="<?php echo htmlspecialchars($testEmail); ?>" required style="width: 320px;">
            </label>
        </p>
        <p>
            <label>
                Candidate name:<br>
                <input type="text" name="test_name" value="<?php echo htmlspecialchars($testName); ?>" style="width: 320px;">
            </label>
        </p>
        <p>
            <label>
                Exam ID:<br>
                <input type="number" name="test_exam_id" value="<?php echo htmlspecialchars((string)$testExamId); ?>" min="1" required>
            </label>
        </p>
        <p>
            <label>
                X-API-KEY header to send to webhook:<br>
                <input type="text" name="header_key" value="<?php echo htmlspecialchars($headerKey); ?>" style="width: 320px;">
            </label>
        </p>

        <p>
            <button type="submit" name="send_mail" value="1">Send Direct Test Mail</button>
            <button type="submit" name="call_webhook" value="1">Call Assign-Exam Webhook</button>
        </p>
    </form>

    <?php if ($mailStatus || $mailError): ?>
        <hr>
        <h2>Direct Mail Result</h2>
        <?php if ($mailStatus): ?>
            <p><?php echo htmlspecialchars($mailStatus); ?></p>
        <?php endif; ?>
        <?php if ($mailError): ?>
            <p style="color:red;"><strong>Error:</strong> <?php echo htmlspecialchars($mailError); ?></p>
            <p>Check server logs for more details from PHPMailer (logged via <code>error_log()</code>).</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($webhookHttp !== null): ?>
        <hr>
        <h2>Webhook Call Result</h2>
        <p><strong>HTTP status:</strong> <?php echo (int)$webhookHttp; ?></p>
        <?php if ($webhookError): ?>
            <p style="color:red;"><strong>cURL error:</strong> <?php echo htmlspecialchars($webhookError); ?></p>
        <?php endif; ?>

        <h3>Request sent</h3>
        <pre><?php echo htmlspecialchars(json_encode([
            'url'     => $webhookEndpoint,
            'headers' => [
                'Content-Type: application/json',
                'X-API-KEY: ' . $headerKey,
            ],
            'body'    => [
                'name'    => $testName ?: 'Test User',
                'email'   => $testEmail,
                'phone'   => '',
                'exam_id' => $testExamId,
            ],
        ], JSON_PRETTY_PRINT)); ?></pre>

        <h3>Raw response body</h3>
        <pre><?php echo htmlspecialchars($webhookResult); ?></pre>

        <h3>JSON decode</h3>
        <pre><?php
            $decoded = json_decode($webhookResult, true);
            var_export($decoded);
        ?></pre>
    <?php endif; ?>

    <hr>
    <p><strong>How to use:</strong> First click "Send Direct Test Mail" to confirm SMTP works from this server. Then click
       "Call Assign-Exam Webhook" with the same email and a valid Exam ID to confirm the webhook path (including email sending)
       behaves correctly end-to-end.</p>
</body>
</html>

