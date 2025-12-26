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

/**
 * Deletes a specific question from the database.
 * @param mysqli $conn The database connection object.
 * @param int $question_id The ID of the question to delete.
 * @return bool True on success, false on failure.
 */
function deleteQuestion($conn, $question_id) {
    $question_id = (int)$question_id;
    $sql = "DELETE FROM questions WHERE question_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $question_id);
        return mysqli_stmt_execute($stmt);
    }
    return false;
}

/**
 * Updates an existing question in the database.
 * @param mysqli $conn The database connection object.
 * @param int $question_id The ID of the question to update.
 * @param string $type The question type ('mcq' or 'descriptive').
 * @param string $question_text The text of the question.
 * @param string|null $options JSON string of options for MCQ.
 * @param string|null $correct_answer The correct answer for MCQ.
 * @param int $marks The marks for the question.
 * @return bool True on success, false on failure.
 */
function updateQuestion($conn, $question_id, $type, $question_text, $options, $correct_answer, $marks) {
    $question_id = (int)$question_id;
    $marks = (int)$marks;

    if ($type === 'mcq') {
        $sql = "UPDATE questions SET
                    type = ?,
                    question_text = ?,
                    options = ?,
                    correct_answer = ?,
                    marks = ?
                WHERE question_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssii", $type, $question_text, $options, $correct_answer, $marks, $question_id);
    } else { // Descriptive
        $sql = "UPDATE questions SET
                    type = ?,
                    question_text = ?,
                    options = NULL,
                    correct_answer = NULL,
                    marks = ?
                WHERE question_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssii", $type, $question_text, $marks, $question_id);
    }

    if ($stmt) {
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }
    return false;
}
