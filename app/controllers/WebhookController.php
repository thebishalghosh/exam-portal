<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/ExamAssignment.php';
require_once ROOT_PATH . '/app/services/EmailService.php';

// Always respond with JSON from this controller
header('Content-Type: application/json');

// Log any fatal error that happens in this controller so we can debug 500s.
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $logDir = ROOT_PATH . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $message = '[' . date('Y-m-d H:i:s') . '] '
            . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line'] . "\n";
        @file_put_contents($logDir . '/webhook_fatal.log', $message, FILE_APPEND);
    }
});

// Small helper to safely fetch headers on all SAPIs (Apache, FPM, etc.)
function exam_get_request_headers() {
    if (function_exists('getallheaders')) {
        return getallheaders();
    }
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (strpos($name, 'HTTP_') === 0) {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
    return $headers;
}

// --- 1. Security Check ---
$headers = exam_get_request_headers();
// Be tolerant of different header casing produced by various servers/proxies
$api_key = '';
if (isset($headers['X-API-KEY'])) {
    $api_key = $headers['X-API-KEY'];
} elseif (isset($headers['x-api-key'])) {
    $api_key = $headers['x-api-key'];
} elseif (isset($headers['X-Api-Key'])) {
    $api_key = $headers['X-Api-Key'];
}

$valid_key = getenv('EXAM_API_KEY');

if (empty($api_key) || $api_key !== $valid_key) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid API Key']);
    exit();
}

// --- 2. Parse Input ---
$input = json_decode(file_get_contents('php://input'), true);

$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$exam_id = isset($input['exam_id']) ? (int)$input['exam_id'] : 0;

if (empty($email) || empty($name) || $exam_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: name, email, exam_id']);
    exit();
}

// --- 3. Create or Update User ---
$user_id = 0;
$sql_check = "SELECT id FROM users WHERE email = ?";
$stmt_check = mysqli_prepare($conn, $sql_check);

if (!$stmt_check) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error preparing user lookup.']);
    mysqli_close($conn);
    exit();
}

mysqli_stmt_bind_param($stmt_check, "s", $email);
mysqli_stmt_execute($stmt_check);

// Use bind_result/fetch instead of mysqli_stmt_get_result for maximum compatibility
mysqli_stmt_bind_result($stmt_check, $existing_user_id);

if (mysqli_stmt_fetch($stmt_check)) {
    $user_id = (int)$existing_user_id;
    mysqli_stmt_close($stmt_check);

    // Update name if provided
    $sql_update = "UPDATE users SET name = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "si", $name, $user_id);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
    }
} else {
    mysqli_stmt_close($stmt_check);

    // Create new user
    $sql_create = "INSERT INTO users (name, email) VALUES (?, ?)";
    $stmt_create = mysqli_prepare($conn, $sql_create);
    if (!$stmt_create) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error preparing user insert.']);
        mysqli_close($conn);
        exit();
    }
    mysqli_stmt_bind_param($stmt_create, "ss", $name, $email);
    if (mysqli_stmt_execute($stmt_create)) {
        $user_id = mysqli_insert_id($conn);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create user']);
        mysqli_stmt_close($stmt_create);
        mysqli_close($conn);
        exit();
    }
    mysqli_stmt_close($stmt_create);
}

// --- 4. Assign Exam ---
// Check if already assigned
$sql_assign_check = "SELECT assignment_id FROM exam_assignments WHERE exam_id = ? AND candidate_email = ?";
$stmt_assign_check = mysqli_prepare($conn, $sql_assign_check);
mysqli_stmt_bind_param($stmt_assign_check, "is", $exam_id, $email);
mysqli_stmt_execute($stmt_assign_check);
mysqli_stmt_store_result($stmt_assign_check);

if (mysqli_stmt_num_rows($stmt_assign_check) == 0) {
    // FIXED: Changed 'interview_portal' to 'interview' to match ENUM
    $sql_assign = "INSERT INTO exam_assignments (exam_id, candidate_id, candidate_email, candidate_source, status) VALUES (?, ?, ?, 'interview', 'assigned')";
    $stmt_assign = mysqli_prepare($conn, $sql_assign);
    mysqli_stmt_bind_param($stmt_assign, "iis", $exam_id, $user_id, $email);
    mysqli_stmt_execute($stmt_assign);
    mysqli_stmt_close($stmt_assign);
}
mysqli_stmt_close($stmt_assign_check);

// --- 5. Generate Link & Send Email ---
$exam_link = BASE_URL . "/exam/check/" . $exam_id . "?email=" . urlencode($email);

$subject = "Your Interview Exam Link";
$body  = "<p>Hello " . htmlspecialchars($name) . ",</p>";
$body .= "<p>You have been assigned an exam as part of your interview process.</p>";
$body .= "<p><a href=\"" . htmlspecialchars($exam_link) . "\">Click here to start your exam</a></p>";
$body .= "<p>Good luck!<br>Travarsa Team</p>";

$emailService = new EmailService();

try {
    $emailService->sendEmail($email, $subject, $body);
} catch (\Exception $e) {
    error_log('WebhookController email error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Exam assigned but failed to send email.'
    ]);
    mysqli_close($conn);
    exit();
}

// --- 6. Response ---
echo json_encode([
    'status' => 'success',
    'message' => 'Exam assigned and email sent successfully',
    'exam_link' => $exam_link
]);

mysqli_close($conn);
exit();
