<?php
// This file will hold all database functions related to external API requests.

/**
 * Fetches all exams assigned to a specific candidate by their email.
 * @param mysqli $conn The database connection object.
 * @param string $email The email of the candidate.
 * @return array An array of assigned exams.
 */
function getAssignedExamsByEmail($conn, $email) {
    $exams = [];

    // The SQL query joins the assignments with the exams table to get exam details.
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
            // Dynamically add the candidate's email to the start link
            $row['start_link'] = BASE_URL . '/exam/take/' . $row['exam_id'] . '?email=' . urlencode($email);
            $exams[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
    return $exams;
}
