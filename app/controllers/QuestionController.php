<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

session_start();
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Question.php';

if (!isset($_SESSION['admin_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit();
    }
    header("Location: " . BASE_URL . "/login");
    exit();
}

// Handle Create Question Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_question'])) {
    header('Content-Type: application/json');
    $exam_id = (int)$_POST['exam_id'];
    $type = $_POST['type'];
    $question_text = trim($_POST['question_text']);
    $marks = (int)$_POST['marks'];

    if (empty($question_text) || $marks <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Question text and marks are required.']);
        exit();
    }

    if ($type === 'mcq') {
        $options_array = $_POST['options'] ?? [];
        $options_array = array_filter($options_array, fn($v) => !empty(trim($v)));
        if (count($options_array) < 2) {
            echo json_encode(['status' => 'error', 'message' => 'MCQ must have at least two options.']);
            exit();
        }
        $options = json_encode($options_array);
        $correct_answer = $_POST['correct_answer'] ?? null;
        if (!$correct_answer || !array_key_exists($correct_answer, $options_array)) {
             echo json_encode(['status' => 'error', 'message' => 'Please select a valid correct answer.']);
             exit();
        }

        $sql = "INSERT INTO questions (exam_id, type, question_text, options, correct_answer, marks) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issssi", $exam_id, $type, $question_text, $options, $correct_answer, $marks);

    } else { // Descriptive question
        $sql = "INSERT INTO questions (exam_id, type, question_text, marks) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issi", $exam_id, $type, $question_text, $marks);
    }

    if ($stmt && mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Question added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }

    if ($stmt) mysqli_stmt_close($stmt);
    mysqli_close($conn);
    exit();
}

// Handle Update Question Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_question'])) {
    header('Content-Type: application/json');
    $question_id = (int)$_POST['question_id'];
    $type = $_POST['type'];
    $question_text = trim($_POST['question_text']);
    $marks = (int)$_POST['marks'];

    // **THE FIX:** Initialize to null, just like in create
    $options = null;
    $correct_answer = null;

    if ($type === 'mcq') {
        $options_array = $_POST['options'] ?? [];
        $options_array = array_filter($options_array, fn($v) => !empty(trim($v)));
        if (empty($options_array)) {
            echo json_encode(['status' => 'error', 'message' => 'MCQ must have at least one option.']);
            exit();
        }
        $options = json_encode($options_array);
        $correct_answer = $_POST['correct_answer'] ?? null;
        if (!$correct_answer || !array_key_exists($correct_answer, $options_array)) {
             echo json_encode(['status' => 'error', 'message' => 'Please select a valid correct answer.']);
             exit();
        }
    }

    if (empty($question_text) || $marks <= 0 || $question_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
        exit();
    }

    if (updateQuestion($conn, $question_id, $type, $question_text, $options, $correct_answer, $marks)) {
        echo json_encode(['status' => 'success', 'message' => 'Question updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: Failed to update question.']);
    }
    mysqli_close($conn);
    exit();
}

// Handle Delete Question Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_question'])) {
    header('Content-Type: application/json');
    $question_id = (int)$_POST['question_id'];
    if ($question_id > 0) {
        if (deleteQuestion($conn, $question_id)) {
            echo json_encode(['status' => 'success', 'message' => 'Question deleted.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete question.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid question ID.']);
    }
    mysqli_close($conn);
    exit();
}

// Fallback
header("Location: " . BASE_URL . "/admin/dashboard");
exit();
