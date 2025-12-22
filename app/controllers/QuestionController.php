<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

session_start();
require_once ROOT_PATH . '/config/database.php';

// Check if the user is an admin
if (!isset($_SESSION['admin_id'])) {
    if (isAjaxRequest()) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit();
    }
    header("Location: " . BASE_URL . "/login");
    exit();
}

// Helper to check for AJAX
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
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
        sendResponse('error', 'Please fill in all required fields.', $exam_id);
    }

    // If it's an MCQ, process the options
    if ($type === 'mcq') {
        if (!isset($_POST['options']) || !is_array($_POST['options'])) {
             sendResponse('error', 'Options are required for MCQ.', $exam_id);
        }

        // Filter out empty options
        $valid_options = array_filter($_POST['options'], function($value) {
            return !empty(trim($value));
        });

        if (count($valid_options) < 2) {
            sendResponse('error', 'At least 2 options are required.', $exam_id);
        }

        $options = json_encode($_POST['options']); // Store original array to keep keys A, B, C, D
        $correct_answer = $_POST['correct_answer'] ?? null;

        if (empty($correct_answer)) {
            sendResponse('error', 'Please select a correct answer.', $exam_id);
        }
    }

    // Prepare and bind
    $sql = "INSERT INTO questions (exam_id, type, question_text, options, correct_answer, marks) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "issssi", $exam_id, $type, $question_text, $options, $correct_answer, $marks);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            if (isAjaxRequest()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Question saved successfully',
                    'question' => [
                        'question_id' => $new_id,
                        'question_text' => $question_text,
                        'type' => $type,
                        'marks' => $marks,
                        'options' => $options,
                        'correct_answer' => $correct_answer
                    ]
                ]);
                exit();
            } else {
                header("Location: " . BASE_URL . "/admin/exam/questions/$exam_id?success=question_added");
                exit();
            }
        } else {
            sendResponse('error', 'Database error: ' . mysqli_error($conn), $exam_id);
        }
        mysqli_stmt_close($stmt);
    } else {
        sendResponse('error', 'Failed to prepare statement.', $exam_id);
    }
    mysqli_close($conn);

} else {
    header("Location: " . BASE_URL . "/admin/exams");
    exit();
}

function sendResponse($status, $message, $exam_id) {
    if (isAjaxRequest()) {
        echo json_encode(['status' => $status, 'message' => $message]);
        exit();
    } else {
        header("Location: " . BASE_URL . "/admin/exam/questions/$exam_id?error=" . urlencode($message));
        exit();
    }
}
