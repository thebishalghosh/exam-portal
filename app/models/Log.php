<?php
// This file will hold all functions related to activity logs.

/**
 * Fetches all activity logs for a specific submission.
 * @param mysqli $conn The database connection object.
 * @param int $exam_id The ID of the exam.
 * @param int $user_id The ID of the user.
 * @return array An array of log entries.
 */
function getLogsForSubmission($conn, $exam_id, $user_id) {
    $logs = [];

    $sql = "SELECT event_type, timestamp
            FROM activity_logs
            WHERE exam_id = ? AND user_id = ?
            ORDER BY timestamp ASC";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $exam_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $logs[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    return $logs;
}
