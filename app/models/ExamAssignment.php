<?php
// This file will hold all database functions related to exam assignments.

/**
 * Fetches all assigned candidate emails for a specific exam.
 * @param mysqli $conn The database connection object.
 * @param int $exam_id The ID of the exam.
 * @return array An array of email strings.
 */
function getAssignedEmailsByExamId($conn, $exam_id) {
    $exam_id = (int)$exam_id;
    $emails = [];

    $sql = "SELECT candidate_email FROM exam_assignments WHERE exam_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $exam_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $emails[] = $row['candidate_email'];
        }
        mysqli_stmt_close($stmt);
    }
    return $emails;
}

/**
 * Syncs the assignments for a given exam.
 * It first deletes all existing assignments for the exam and then inserts the new ones.
 * @param mysqli $conn The database connection object.
 * @param int $exam_id The ID of the exam.
 * @param array $assignments An array of assignment data, each element being an associative array
 *                           e.g., ['candidate_id' => 1, 'candidate_email' => 'a@b.com', 'candidate_source' => 'internal']
 * @return bool True on success, false on failure.
 */
function syncAssignments($conn, $exam_id, $assignments) {
    $exam_id = (int)$exam_id;

    // Start a transaction
    mysqli_begin_transaction($conn);

    try {
        // Step 1: Delete all existing assignments for this exam
        $sql_delete = "DELETE FROM exam_assignments WHERE exam_id = ?";
        $stmt_delete = mysqli_prepare($conn, $sql_delete);
        mysqli_stmt_bind_param($stmt_delete, "i", $exam_id);
        if (!mysqli_stmt_execute($stmt_delete)) {
            throw new Exception("Failed to delete old assignments.");
        }
        mysqli_stmt_close($stmt_delete);

        // Step 2: If there are new assignments, insert them
        if (!empty($assignments)) {
            $sql_insert = "INSERT INTO exam_assignments (exam_id, candidate_id, candidate_email, candidate_source) VALUES (?, ?, ?, ?)";
            $stmt_insert = mysqli_prepare($conn, $sql_insert);

            foreach ($assignments as $assignment) {
                mysqli_stmt_bind_param(
                    $stmt_insert,
                    "iiss",
                    $exam_id,
                    $assignment['candidate_id'],
                    $assignment['candidate_email'],
                    $assignment['candidate_source']
                );
                if (!mysqli_stmt_execute($stmt_insert)) {
                    throw new Exception("Failed to insert new assignment for email: " . $assignment['candidate_email']);
                }
            }
            mysqli_stmt_close($stmt_insert);
        }

        // If all went well, commit the transaction
        mysqli_commit($conn);
        return true;

    } catch (Exception $e) {
        // If anything failed, roll back the transaction
        mysqli_rollback($conn);
        error_log("Assignment Sync Error: " . $e->getMessage());
        return false;
    }
}
