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
$image_data = isset($data['image']) ? $data['image'] : '';
$timestamp = isset($data['ts']) ? (int)$data['ts'] : time();

if ($exam_id === 0 || empty($email) || empty($image_data)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data.']);
    exit();
}

// --- Find User ID ---
$user_id = 0;
$sql_check = "SELECT id FROM users WHERE email = ?";
$stmt_check = mysqli_prepare($conn, $sql_check);
mysqli_stmt_bind_param($stmt_check, "s", $email);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if ($row = mysqli_fetch_assoc($result_check)) {
    $user_id = $row['id'];
} else {
    // If user doesn't exist yet (rare case if they haven't submitted), create them
    $sql_create = "INSERT INTO users (email) VALUES (?)";
    $stmt_create = mysqli_prepare($conn, $sql_create);
    mysqli_stmt_bind_param($stmt_create, "s", $email);
    if (mysqli_stmt_execute($stmt_create)) {
        $user_id = mysqli_insert_id($conn);
    }
    mysqli_stmt_close($stmt_create);
}
mysqli_stmt_close($stmt_check);

if ($user_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit();
}

// --- Process Image ---
// Remove the "data:image/jpeg;base64," part
$image_parts = explode(";base64,", $image_data);
if (count($image_parts) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid image format.']);
    exit();
}

$image_base64 = base64_decode($image_parts[1]);

// Check size (Limit: 2MB)
if (strlen($image_base64) > 2 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'Image too large.']);
    exit();
}

// --- Save File ---
$storage_dir = ROOT_PATH . '/storage/snapshots/';
if (!is_dir($storage_dir)) {
    // Fallback if directory wasn't created manually
    mkdir($storage_dir, 0777, true);
}

// Filename format: exam_{exam_id}_user_{user_id}_{timestamp}.jpg
$filename = "exam_{$exam_id}_user_{$user_id}_{$timestamp}.jpg";
$filepath = $storage_dir . $filename;

if (file_put_contents($filepath, $image_base64)) {
    echo json_encode(['status' => 'success', 'message' => 'Snapshot saved.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save file.']);
}

mysqli_close($conn);
exit();
