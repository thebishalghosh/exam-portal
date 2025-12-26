<?php
// Set content type to JSON
header('Content-Type: application/json');

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Exam.php';

// Check if the user is logged in (basic security)
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]); // Return empty array for short queries
    exit();
}

// Use the existing getAllExams function but with a small limit for suggestions
$exams_result = getAllExams($conn, $query, 5, 0);
$suggestions = [];

if ($exams_result) {
    while ($row = mysqli_fetch_assoc($exams_result)) {
        $suggestions[] = [
            'id' => $row['exam_id'],
            'title' => $row['title']
        ];
    }
}

echo json_encode($suggestions);
mysqli_close($conn);
exit();
