<?php
// Set the content type to JSON for all responses
header('Content-Type: application/json');

// Define ROOT_PATH for includes
define('ROOT_PATH', dirname(__DIR__, 2));

// Load environment variables and database connection
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Api.php';

// --- Define BASE_URL ---
// This is required because this script is accessed directly, bypassing index.php
if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('APP_URL'));
}

// --- Security Check ---
$apiKey = getenv('EXAM_API_KEY');
$headers = getallheaders();
$sentApiKey = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? null;

if (!$apiKey || $sentApiKey !== $apiKey) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid or missing API Key.']);
    exit();
}

// --- Input Validation ---
$email = $_GET['email'] ?? null;

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Bad Request: A valid email parameter is required.']);
    exit();
}

// --- Fetch Data ---
try {
    $assignedExams = getAssignedExamsByEmail($conn, $email);

    http_response_code(200); // OK
    echo json_encode([
        'status' => 'success',
        'candidate_email' => $email,
        'assigned_exams' => $assignedExams
    ]);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("API Error for getAssignedExamsByEmail: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred.']);
}

mysqli_close($conn);
exit();
