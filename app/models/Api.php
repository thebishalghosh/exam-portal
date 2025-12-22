<?php
// This file will hold all database functions related to external API requests.

/**
 * Fetches all exams assigned to a specific candidate by their email.
 * @param mysqli $conn The database connection object.
 * @param string $email The email of the candidate.
 * @param string|null $session_token The user's session token from the HR portal.
 * @return array An array of assigned exams.
 */
function getAssignedExamsByEmail($conn, $email, $session_token = null) {
    $exams = [];

    $sql = "SELECT
                e.exam_id,
                e.title,
                e.description,
                e.duration,
                a.status
            FROM exam_assignments a
            JOIN exams e ON a.exam_id = e.exam_id
            WHERE a.candidate_email = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            // Generate the secure SSO link instead of a direct link
            if ($session_token) {
                $row['start_link'] = BASE_URL . '/login/sso?session_token=' . urlencode($session_token) . '&exam_id=' . $row['exam_id'];
            } else {
                // Fallback for testing, though it won't work without a real token
                $row['start_link'] = BASE_URL . '/exam/take/' . $row['exam_id'] . '?email=' . urlencode($email);
            }
            $exams[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
    return $exams;
}
