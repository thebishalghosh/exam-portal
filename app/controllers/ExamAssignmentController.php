<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

session_start();
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/ExamAssignment.php'; // Use the new model

// Check if the user is an admin and the request is AJAX
if (!isset($_SESSION['admin_id']) || empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$exam_id = isset($data['exam_id']) ? (int)$data['exam_id'] : 0;
$assignments = isset($data['assignments']) && is_array($data['assignments']) ? $data['assignments'] : [];

if ($exam_id === 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid Exam ID.']);
    exit();
}

// Call the new sync function from the model
if (syncAssignments($conn, $exam_id, $assignments)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Assignments saved successfully.']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Failed to save assignments to the database.']);
}

mysqli_close($conn);
exit();
