<?php
// Debug helper: exercise the assign-exam webhook and show the response.
// URL (on your deployment): http://your-domain/exam/public/debug_webhook_assign_exam.php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

require_once ROOT_PATH . '/config/app.php';

header('Content-Type: text/html; charset=UTF-8');

$defaultKey = getenv('EXAM_API_KEY') ?: '';
$name   = isset($_GET['name']) ? trim($_GET['name']) : 'Test User';
$email  = isset($_GET['email']) ? trim($_GET['email']) : 'test@example.com';
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 1;
$apiKey = isset($_GET['api_key']) ? trim($_GET['api_key']) : $defaultKey;

$result = null;
$httpCode = null;
$curlError = null;

if (isset($_GET['run'])) {
    $payload = [
        'name'    => $name,
        'email'   => $email,
        'phone'   => '',
        'exam_id' => $examId,
    ];

    $url = rtrim(BASE_URL, '/') . '/api/webhook/assign-exam';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-KEY: ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debug Assign-Exam Webhook</title>
</head>
<body>
    <h1>Debug Assign-Exam Webhook</h1>

    <p>Endpoint under test: <code><?php echo htmlspecialchars(rtrim(BASE_URL, '/') . '/api/webhook/assign-exam'); ?></code></p>

    <form method="get">
        <input type="hidden" name="run" value="1">
        <div>
            <label>Name:
                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
            </label>
        </div>
        <div>
            <label>Email:
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </label>
        </div>
        <div>
            <label>Exam ID:
                <input type="number" name="exam_id" value="<?php echo htmlspecialchars((string)$examId); ?>" min="1" required>
            </label>
        </div>
        <div>
            <label>X-API-KEY:
                <input type="text" name="api_key" value="<?php echo htmlspecialchars($apiKey); ?>" required style="width: 320px;">
            </label>
        </div>
        <button type="submit">Send Webhook Request</button>
    </form>

    <?php if ($httpCode !== null): ?>
        <h2>Result</h2>
        <p><strong>HTTP status:</strong> <?php echo (int)$httpCode; ?></p>
        <?php if ($curlError): ?>
            <p style="color:red;"><strong>cURL error:</strong> <?php echo htmlspecialchars($curlError); ?></p>
        <?php endif; ?>
        <h3>Raw response body</h3>
        <pre><?php echo htmlspecialchars($result); ?></pre>

        <h3>JSON decode</h3>
        <pre><?php
            $decoded = json_decode($result, true);
            var_export($decoded);
        ?></pre>
    <?php endif; ?>

    <hr>
    <p><strong>Important:</strong> The current webhook controller (<code>app/controllers/WebhookController.php</code>) has the
        <code>mail()</code> call commented out and does <em>not</em> use <code>EmailService</code>, so it will
        successfully assign the exam but <strong>will not send any email</strong> even when this test returns status
        <code>success</code>.</p>
</body>
</html>

