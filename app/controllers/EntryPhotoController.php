<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}

session_start();

// Check if candidate is logged in
if (!isset($_SESSION['candidate_logged_in']) || $_SESSION['candidate_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
    $image_data = isset($_POST['image']) ? $_POST['image'] : '';
    $user_id = $_SESSION['candidate_id'];

    if ($exam_id === 0 || empty($image_data)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        exit();
    }

    // Process Image
    $image_parts = explode(";base64,", $image_data);
    if (count($image_parts) < 2) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid image format']);
        exit();
    }

    $image_base64 = base64_decode($image_parts[1]);

    // Define path
    $storage_dir = ROOT_PATH . '/storage/snapshots/';
    if (!is_dir($storage_dir)) {
        mkdir($storage_dir, 0777, true);
    }

    // Filename: exam_{exam_id}_user_{user_id}_entry.jpg
    $filename = "exam_{$exam_id}_user_{$user_id}_entry.jpg";
    $filepath = $storage_dir . $filename;

    if (file_put_contents($filepath, $image_base64)) {
        echo json_encode(['status' => 'success', 'message' => 'Entry photo saved']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save file']);
    }
    exit();
}
