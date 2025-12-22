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
    echo json_encode(['status' => 'error', 'message' => 'Invalid data received.']);
    exit();
}

$exam_id = isset($data['exam_id']) ? (int)$data['exam_id'] : 0;
$email = isset($data['email']) ? trim($data['email']) : '';
$answers = isset($data['answers']) ? json_encode($data['answers']) : '{}';
$status = isset($data['auto']) && $data['auto'] === true ? 'Disqualified' : 'Completed';

if ($exam_id === 0 || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing exam ID or email.']);
    exit();
}

// --- 1. Find or Create User ---
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
        echo json_encode(['status' => 'error', 'message' => 'Failed to create user record.']);
        exit();
    }
    mysqli_stmt_close($stmt_create);
}
mysqli_stmt_close($stmt_check);

// --- 2. Save Submission ---
// We now include start_time in the insert.
// A more accurate start_time would require another API call on exam start.
// For now, we use the submission time as a close approximation for both.
$sql_submit = "INSERT INTO submissions (exam_id, user_id, submitted_answers, status, start_time, end_time) VALUES (?, ?, ?, ?, NOW(), NOW())";
$stmt_submit = mysqli_prepare($conn, $sql_submit);

if ($stmt_submit) {
    mysqli_stmt_bind_param($stmt_submit, "iiss", $exam_id, $user_id, $answers, $status);

    if (mysqli_stmt_execute($stmt_submit)) {
        echo json_encode(['status' => 'success', 'message' => 'Exam submitted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    mysqli_stmt_close($stmt_submit);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare submission statement.']);
}

mysqli_close($conn);
exit();
