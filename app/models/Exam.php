<?php
// This file will hold all database functions related to exams.

/**
 * Fetches all exams from the database.
 * @param mysqli $conn The database connection object.
 * @return mysqli_result|false The result set on success, false on failure.
 */
function getAllExams($conn) {
    $sql = "SELECT exam_id, title, description, duration, start_time, end_time, status FROM exams ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);
    return $result;
}

/**
 * Fetches a single exam's core details by its ID.
 * @param mysqli $conn The database connection object.
 * @param int $exam_id The ID of the exam.
 * @return array|null The exam data or null if not found.
 */
function getExamById($conn, $exam_id) {
    $exam_id = (int)$exam_id;
    $sql = "SELECT exam_id, title, duration FROM exams WHERE exam_id = ?";

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
 * Fetches just the title of a single exam by its ID.
 * @param mysqli $conn The database connection object.
 * @param int $exam_id The ID of the exam.
 * @return string|null The exam title or null if not found.
 */
function getExamTitleById($conn, $exam_id) {
    $exam_id = (int)$exam_id;
    $sql = "SELECT title FROM exams WHERE exam_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $exam_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exam = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $exam ? $exam['title'] : null;
    }
    return null;
}

/**
 * Fetches the total count of all exams.
 * @param mysqli $conn The database connection object.
 * @return int The total number of exams.
 */
function getTotalExamsCount($conn) {
    $sql = "SELECT COUNT(exam_id) as total FROM exams";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row ? (int)$row['total'] : 0;
}

/**
 * Fetches the count of exams grouped by their status.
 * @param mysqli $conn The database connection object.
 * @return array An array formatted for Google Charts.
 */
function getExamStatusCounts($conn) {
    $sql = "SELECT status, COUNT(exam_id) as count FROM exams GROUP BY status";
    $result = mysqli_query($conn, $sql);

    $status_counts = [['Status', 'Count']];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $status_counts[] = [ucfirst($row['status']), (int)$row['count']];
        }
    }
    // If there are no exams, add a default row to prevent chart errors
    if (count($status_counts) === 1) {
        $status_counts[] = ['None', 0];
    }
    return $status_counts;
}
