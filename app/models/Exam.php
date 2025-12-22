<?php
// This file will hold all database functions related to exams.

/**
 * Fetches all exams from the database with all necessary fields for viewing.
 * @param mysqli $conn The database connection object.
 * @return mysqli_result|false The result set on success, false on failure.
 */
function getAllExams($conn) {
    // Updated to select all columns needed for the "View" modal
    $sql = "SELECT exam_id, title, description, duration, start_time, end_time, status FROM exams ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);
    return $result;
}

/**
 * Fetches a single exam by ID.
 * @param mysqli $conn The database connection object.
 * @param int $exam_id The ID of the exam.
 * @return array|null The exam data or null if not found.
 */
function getExamById($conn, $exam_id) {
    $exam_id = (int)$exam_id;
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
