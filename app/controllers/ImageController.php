<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
}

$filename = isset($_GET['file']) ? basename($_GET['file']) : '';

if (empty($filename)) {
    http_response_code(400);
    echo "Bad Request: No file specified.";
    exit();
}

// Security: Prevent directory traversal attacks
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    http_response_code(400);
    echo "Bad Request: Invalid filename.";
    exit();
}

$filepath = ROOT_PATH . '/storage/snapshots/' . $filename;

if (file_exists($filepath)) {
    // Get the file's mime type
    $mime_type = mime_content_type($filepath);
    if ($mime_type === 'image/jpeg' || $mime_type === 'image/png') {
        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    } else {
        http_response_code(403);
        echo "Forbidden: Not an allowed image type.";
        exit();
    }
} else {
    http_response_code(404);
    echo "Image not found.";
    exit();
}
