<?php
// Define a constant for the project root directory
define('ROOT_PATH', dirname(__DIR__));

// Load environment variables from .env file
require_once ROOT_PATH . '/config/app.php';

// --- Environment & Debugging ---
if (getenv('APP_DEBUG') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// --- Base URL ---
define('BASE_URL', getenv('APP_URL'));

// --- Routing Logic ---
// Get the path from the request URI
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = dirname($_SERVER['SCRIPT_NAME']);

// Remove the script path from the request URI to get the relative route
// Example: Request: /exam/login, Script: /exam, Result: /login
if (strpos($request_uri, $script_name) === 0) {
    $url = substr($request_uri, strlen($script_name));
} else {
    $url = $request_uri;
}

$url = trim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);

// Redirect to login if the URL is empty
if ($url === '') {
    header("Location: " . BASE_URL . "/login");
    exit();
}

// Static routes map
$routes = [
    'login'               => '/public/login.php',
    'login/sso'           => '/app/controllers/SSOController.php',
    'admin/dashboard'     => '/public/admin/dashboard.php',
    'admin/exams'         => '/public/admin/exams.php',
    'admin/candidates'    => '/public/admin/candidates.php',
    'admin/submissions'   => '/public/admin/submissions.php',
    'login/process'       => '/app/controllers/LoginController.php',
    'logout'              => '/app/controllers/LogoutController.php',
    'admin/exam/create'   => '/app/controllers/ExamController.php',
    'admin/exam/delete'   => '/app/controllers/ExamController.php',
    'admin/question/create' => '/app/controllers/QuestionController.php',
    'admin/question/delete' => '/app/controllers/QuestionController.php',
    'admin/question/update' => '/app/controllers/QuestionController.php',
    'admin/exam/save-assignment' => '/app/controllers/ExamAssignmentController.php',
    'admin/submission/save-grade' => '/app/controllers/SubmissionController.php',
    'api/submit-exam'     => '/app/controllers/SubmissionController.php',
    'api/log-activity'    => '/app/controllers/LogController.php',
    'api/upload-snapshot' => '/app/controllers/SnapshotController.php',
    'admin/image/view'    => '/app/controllers/ImageController.php',
    'api/search-exams'    => '/app/controllers/SearchController.php',
    'api/candidate-exams' => '/public/api/candidate-exams.php',
    'register/open-exam'  => '/app/controllers/OpenRegistrationController.php',
    'exams/open'          => '/public/exams/open.php',
    'api/get-candidates'  => '/public/api/get-candidates.php',
];

// Check static routes first
if (array_key_exists($url, $routes)) {
    require_once ROOT_PATH . $routes[$url];
    exit();
}

// Handle dynamic routes with regular expressions
if (preg_match('#^admin/exam/questions/(\d+)$#', $url, $matches)) {
    $_GET['exam_id'] = $matches[1];
    require_once ROOT_PATH . '/public/admin/questions.php';
    exit();
}

if (preg_match('#^admin/exam/assign/(\d+)$#', $url, $matches)) {
    $_GET['exam_id'] = $matches[1];
    require_once ROOT_PATH . '/public/admin/assign_exam.php';
    exit();
}

if (preg_match('#^exam/take/(\d+)$#', $url, $matches)) {
    $_GET['exam_id'] = $matches[1];
    require_once ROOT_PATH . '/public/exam/take.php';
    exit();
}

if (preg_match('#^admin/submission/view/(\d+)$#', $url, $matches)) {
    $_GET['submission_id'] = $matches[1];
    require_once ROOT_PATH . '/public/admin/view_submission.php';
    exit();
}

if (preg_match('#^exam/start-open/(\d+)$#', $url, $matches)) {
    $_GET['exam_id'] = $matches[1];
    require_once ROOT_PATH . '/app/controllers/OpenExamController.php';
    exit();
}

// If no route is found, show a 404 error
http_response_code(404);
echo "<h1>404 Page Not Found</h1>";
echo "<p>Requested URL: " . htmlspecialchars($url) . "</p>"; // Debugging help
