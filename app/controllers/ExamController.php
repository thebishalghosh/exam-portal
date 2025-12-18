<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

session_start();
require_once ROOT_PATH . '/config/database.php';

// Check if the user is an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/login");
    exit();
}

// Handle Create Exam Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_exam'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if (empty($title) || empty($duration) || empty($start_time) || empty($end_time)) {
        header("Location: " . BASE_URL . "/admin/exams?error=emptyfields");
        exit();
    }

    $sql = "INSERT INTO exams (title, description, duration, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, 'active')";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssiss", $title, $description, $duration, $start_time, $end_time);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: " . BASE_URL . "/admin/exams?success=created");
            exit();
        } else {
            header("Location: " . BASE_URL . "/admin/exams?error=sqlerror");
            exit();
        }
        mysqli_stmt_close($stmt);
    } else {
        header("Location: " . BASE_URL . "/admin/exams?error=preparefailed");
        exit();
    }
    mysqli_close($conn);
} else {
    // If not a POST request, redirect to the exams page
    header("Location: " . BASE_URL . "/admin/exams");
    exit();
}
