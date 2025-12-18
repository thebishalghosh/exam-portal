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

// Handle Create Question Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_question'])) {
    $exam_id = (int)$_POST['exam_id'];
    $question_text = $_POST['question_text'];
    $type = $_POST['type'];
    $marks = (int)$_POST['marks'];

    $options = null;
    $correct_answer = null;

    // Basic validation
    if (empty($question_text) || empty($type) || $exam_id === 0) {
        header("Location: " . BASE_URL . "/admin/exam/questions/$exam_id?error=emptyfields");
        exit();
    }

    // If it's an MCQ, process the options
    if ($type === 'mcq') {
        // Encode the options array into a JSON string
        $options = json_encode($_POST['options']);
        $correct_answer = $_POST['correct_answer'];

        if (empty($options) || empty($correct_answer)) {
            header("Location: " . BASE_URL . "/admin/exam/questions/$exam_id?error=mcq_fields_required");
            exit();
        }
    }

    // Prepare and bind
    $sql = "INSERT INTO questions (exam_id, type, question_text, options, correct_answer, marks) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "issssi", $exam_id, $type, $question_text, $options, $correct_answer, $marks);

        if (mysqli_stmt_execute($stmt)) {
            // Success - redirect back to the questions page
            header("Location: " . BASE_URL . "/admin/exam/questions/$exam_id?success=question_added");
            exit();
        } else {
            // Handle execution error
            header("Location: " . BASE_URL . "/admin/exam/questions/$exam_id?error=sqlerror");
            exit();
        }
        mysqli_stmt_close($stmt);
    } else {
        // Handle statement preparation error
        header("Location: " . BASE_URL . "/admin/exam/questions/$exam_id?error=preparefailed");
        exit();
    }
    mysqli_close($conn);

} else {
    // If accessed directly, redirect to the exams list
    header("Location: " . BASE_URL . "/admin/exams");
    exit();
}
