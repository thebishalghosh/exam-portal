<?php
// Define ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Include the controller
require_once ROOT_PATH . '/app/controllers/InterviewExamController.php';

// Instantiate the controller and call the method
$controller = new InterviewExamController();
$controller->showExams();
