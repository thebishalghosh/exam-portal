<?php
// This file will hold all database functions related to exams.

/**
 * Fetches exams from the database with search and pagination.
 * @param mysqli $conn The database connection object.
 * @param string $search The search term (optional).
 * @param int $limit The number of records to return.
 * @param int $offset The number of records to skip.
 * @return mysqli_result|false The result set on success, false on failure.
 */
function getAllExams($conn, $search = '', $limit = 10, $offset = 0) {
    $search = "%" . $search . "%";
    $limit = (int)$limit;
    $offset = (int)$offset;

    $sql = "SELECT exam_id, title, description, duration, start_time, end_time, status
            FROM exams
            WHERE title LIKE ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sii", $search, $limit, $offset);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }
    return false;
}

/**
 * Fetches the total count of exams matching a search term.
 * @param mysqli $conn The database connection object.
 * @param string $search The search term (optional).
 * @return int The total number of matching exams.
 */
function getExamsCount($conn, $search = '') {
    $search = "%" . $search . "%";
    $sql = "SELECT COUNT(exam_id) as total FROM exams WHERE title LIKE ?";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $search);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row ? (int)$row['total'] : 0;
    }
    return 0;
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
 * Fetches the total count of all exams (unfiltered).
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
    if (count($status_counts) === 1) {
        $status_counts[] = ['None', 0];
    }
    return $status_counts;
}

/**
 * Deletes an exam, its related database records, and physical snapshot files.
 * @param mysqli $conn The database connection object.
 * @param int $exam_id The ID of the exam to delete.
 * @return bool True on success, false on failure.
 */
function deleteExam($conn, $exam_id) {
    $exam_id = (int)$exam_id;

    // 1. Clean up physical snapshot files
    $storage_dir = ROOT_PATH . '/storage/snapshots/';
    $file_pattern = "exam_{$exam_id}_user_*.jpg";

    $files = glob($storage_dir . $file_pattern);
    if ($files) {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    // 2. Delete from database
    $sql = "DELETE FROM exams WHERE exam_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $exam_id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }
    return false;
}
