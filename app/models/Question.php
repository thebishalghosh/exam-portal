<?php
// This file will hold all database functions related to questions.

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
