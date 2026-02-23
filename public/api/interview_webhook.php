<?php
// Set the content type to JSON
header('Content-Type: application/json');

// Define ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
}

// Load environment and database configuration so controllers can rely on getenv()
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

// Include the controller
require_once ROOT_PATH . '/app/controllers/InterviewController.php';

// Instantiate the controller and call the method
$controller = new InterviewController();
$controller->handleCandidateWebhook();
