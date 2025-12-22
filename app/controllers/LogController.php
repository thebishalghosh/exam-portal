<?php
// Set content type to JSON
header('Content-Type: application/json');

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}

require_once ROOT_PATH . '/config/database.php';

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data.']);
    exit();
}

$exam_id = isset($data['exam_id']) ? (int)$data['exam_id'] : 0;
$email = isset($data['email']) ? trim($data['email']) : '';
$events = isset($data['events']) && is_array($data['events']) ? $data['events'] : [];

if ($exam_id === 0 || empty($email) || empty($events)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data.']);
    exit();
}

// --- Find or Create User ---
$user_id = 0;
$sql_check = "SELECT id FROM users WHERE email = ?";
$stmt_check = mysqli_prepare($conn, $sql_check);
mysqli_stmt_bind_param($stmt_check, "s", $email);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if ($row = mysqli_fetch_assoc($result_check)) {
    $user_id = $row['id'];
} else {
    $sql_create = "INSERT INTO users (email) VALUES (?)";
    $stmt_create = mysqli_prepare($conn, $sql_create);
    mysqli_stmt_bind_param($stmt_create, "s", $email);
    if (mysqli_stmt_execute($stmt_create)) {
        $user_id = mysqli_insert_id($conn);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create user for logging.']);
        exit();
    }
    mysqli_stmt_close($stmt_create);
}
mysqli_stmt_close($stmt_check);

if ($user_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Could not determine user ID.']);
    exit();
}

// --- Save Logs ---
$sql_log = "INSERT INTO activity_logs (user_id, exam_id, event_type, timestamp) VALUES (?, ?, ?, ?)";
$stmt_log = mysqli_prepare($conn, $sql_log);

if ($stmt_log) {
    foreach ($events as $event) {
        $event_type = $event['message'] ?? 'Unknown event';
        // Convert JS timestamp (milliseconds) to SQL DATETIME format
        $timestamp = date('Y-m-d H:i:s', $event['ts'] / 1000);

        mysqli_stmt_bind_param($stmt_log, "iiss", $user_id, $exam_id, $event_type, $timestamp);
        mysqli_stmt_execute($stmt_log);
    }
    mysqli_stmt_close($stmt_log);
    echo json_encode(['status' => 'success', 'message' => 'Logs recorded.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare log statement.']);
}

mysqli_close($conn);
exit();
