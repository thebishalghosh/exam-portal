<?php
// Define a constant for the project root directory
define('ROOT_PATH', dirname(__DIR__));

// Load environment variables from .env file
require_once ROOT_PATH . '/config/app.php';

// --- Environment & Debugging ---
// Set error reporting based on the APP_DEBUG value in .env
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
// Define the base URL from the .env file
define('BASE_URL', getenv('APP_URL'));

// Get the requested URL
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
$url = filter_var($url, FILTER_SANITIZE_URL);

// Redirect to login if the URL is empty
if ($url === '') {
    header("Location: " . BASE_URL . "/login");
    exit();
}

// Static routes map
$routes = [
    'login'               => '/public/login.php',
    'admin/dashboard'     => '/public/admin/dashboard.php',
    'admin/exams'         => '/public/admin/exams.php',
    'admin/candidates'    => '/public/admin/candidates.php',
    'login/process'       => '/app/controllers/LoginController.php',
    'logout'              => '/app/controllers/LogoutController.php',
    'admin/exam/create'   => '/app/controllers/ExamController.php',
    'admin/question/create' => '/app/controllers/QuestionController.php',
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

// If no route is found, show a 404 error
http_response_code(404);
echo "<h1>404 Page Not Found</h1>";
