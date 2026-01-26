<?php
// Set content type to JSON
header('Content-Type: application/json');

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Api.php';
require_once ROOT_PATH . '/app/models/ExamAssignment.php';

// Check if the user is an admin (Security)
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$source = isset($_GET['source']) ? $_GET['source'] : '';
$candidates = [];

try {
    switch ($source) {
        case 'hr':
            $candidates = fetchCandidatesFromHR();
            break;
        case 'interview':
            $candidates = fetchCandidatesFromInterview();
            break;
        case 'open':
            $candidates = getOpenRegistrationCandidates($conn);
            break;
        default:
            // Return empty if no valid source
            break;
    }

    echo json_encode(['status' => 'success', 'data' => $candidates]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

mysqli_close($conn);
exit();
