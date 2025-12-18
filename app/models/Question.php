<?php
// This file will hold all database functions related to questions.

/**
 * Fetches a single exam by its ID.
 * @param mysqli $conn The database connection object.
 * @param int $exam_id The ID of the exam to fetch.
 * @return array|null The exam data as an associative array, or null if not found.
 */
function getExamById($conn, $exam_id) {
    $exam_id = (int)$exam_id; // Sanitize input
    $sql = "SELECT exam_id, title FROM exams WHERE exam_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $exam_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exam = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $exam;
    }
    return null;
}

/**
 * Fetches all questions for a specific exam.
 * @param mysqli $conn The database connection object.
 * @param int $exam_id The ID of the exam.
 * @return mysqli_result|false The result set on success, false on failure.
 */
function getQuestionsByExamId($conn, $exam_id) {
    $exam_id = (int)$exam_id;
    $sql = "SELECT * FROM questions WHERE exam_id = ? ORDER BY question_id ASC";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $exam_id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }
    return false;
}
