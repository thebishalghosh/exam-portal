<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

session_start();
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Exam.php';

// Check if the user is an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/login");
    exit();
}

// Handle Create Exam Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_exam'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = (int)$_POST['duration'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Basic validation
    if (empty($title) || $duration <= 0 || empty($start_time) || empty($end_time)) {
        header("Location: " . BASE_URL . "/admin/exams?error=missing_fields");
        exit();
    }

    // Prepare and bind
    $sql = "INSERT INTO exams (title, description, duration, start_time, end_time) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssiss", $title, $description, $duration, $start_time, $end_time);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: " . BASE_URL . "/admin/exams?success=created");
        } else {
            header("Location: " . BASE_URL . "/admin/exams?error=db_error");
        }
        mysqli_stmt_close($stmt);
    } else {
        header("Location: " . BASE_URL . "/admin/exams?error=prepare_failed");
    }
    mysqli_close($conn);
    exit();
}

// Handle Delete Exam Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_exam'])) {
    $exam_id = (int)$_POST['exam_id'];

    if ($exam_id > 0) {
        if (deleteExam($conn, $exam_id)) {
            header("Location: " . BASE_URL . "/admin/exams?success=deleted");
        } else {
            header("Location: " . BASE_URL . "/admin/exams?error=delete_failed");
        }
    } else {
        header("Location: " . BASE_URL . "/admin/exams?error=invalid_id");
    }
    mysqli_close($conn);
    exit();
}

// Fallback for invalid access
header("Location: " . BASE_URL . "/admin/exams");
exit();
