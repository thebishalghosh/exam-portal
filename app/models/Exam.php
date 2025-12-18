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
