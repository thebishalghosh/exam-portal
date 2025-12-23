<?php
// This file will hold all database functions related to submissions.

/**
 * Fetches the total count of all submissions.
 * @param mysqli $conn The database connection object.
 * @return int The total number of submissions.
 */
function getTotalSubmissionsCount($conn) {
    $sql = "SELECT COUNT(submission_id) as total FROM submissions";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row ? (int)$row['total'] : 0;
}

/**
 * Fetches the count of submissions for each of the last 7 days.
 * @param mysqli $conn The database connection object.
 * @return array An array formatted for Google Charts.
 */
function getRecentSubmissionsCount($conn) {
    $sql = "SELECT DATE(end_time) as submission_date, COUNT(submission_id) as count
            FROM submissions
            WHERE end_time >= CURDATE() - INTERVAL 7 DAY
            GROUP BY DATE(end_time)
            ORDER BY submission_date ASC";

    $result = mysqli_query($conn, $sql);

    $chart_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('M d', strtotime("-$i days"));
        $chart_data[$date] = 0;
    }

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $date = date('M d', strtotime($row['submission_date']));
            if (isset($chart_data[$date])) {
                $chart_data[$date] = (int)$row['count'];
            }
        }
    }

    $final_data = [['Day', 'Submissions']];
    foreach ($chart_data as $day => $count) {
        $final_data[] = [$day, $count];
    }

    return $final_data;
}

/**
 * Fetches all submissions with exam, user, and score details.
 * @param mysqli $conn The database connection object.
 * @return mysqli_result|false The result set on success, false on failure.
 */
function getAllSubmissions($conn) {
    $sql = "SELECT
                s.submission_id,
                s.status as submission_status,
                s.end_time,
                e.title as exam_title,
                u.email as candidate_email,
                ea.score
            FROM submissions s
            JOIN exams e ON s.exam_id = e.exam_id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN exam_assignments ea ON s.exam_id = ea.exam_id AND u.email = ea.candidate_email
            ORDER BY s.end_time DESC";

    return mysqli_query($conn, $sql);
}

/**
 * Fetches detailed information for a single submission, including questions.
 * @param mysqli $conn The database connection object.
 * @param int $submission_id The ID of the submission.
 * @return array|null An associative array with submission details and questions, or null if not found.
 */
function getSubmissionDetails($conn, $submission_id) {
    $submission_id = (int)$submission_id;

    $sql = "SELECT
                s.*,
                e.title as exam_title,
                u.email as candidate_email
            FROM submissions s
            JOIN exams e ON s.exam_id = e.exam_id
            JOIN users u ON s.user_id = u.id
            WHERE s.submission_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;

    mysqli_stmt_bind_param($stmt, "i", $submission_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $submission = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$submission) return null;

    $exam_id = $submission['exam_id'];
    $sql_q = "SELECT * FROM questions WHERE exam_id = ? ORDER BY question_id ASC";
    $stmt_q = mysqli_prepare($conn, $sql_q);

    $questions = [];
    if ($stmt_q) {
        mysqli_stmt_bind_param($stmt_q, "i", $exam_id);
        mysqli_stmt_execute($stmt_q);
        $result_q = mysqli_stmt_get_result($stmt_q);
        while ($row = mysqli_fetch_assoc($result_q)) {
            $questions[] = $row;
        }
        mysqli_stmt_close($stmt_q);
    }

    $submission['questions'] = $questions;
    return $submission;
}

/**
 * Saves the final calculated score and the breakdown for a submission.
 * @param mysqli $conn The database connection object.
 * @param int $submission_id The ID of the submission.
 * @param int $exam_id The ID of the exam.
 * @param string $candidate_email The email of the candidate.
 * @param float $total_score The total score.
 * @param string $marks_breakdown_json The JSON string of individual marks.
 * @return bool True on success, false on failure.
 */
function saveFinalGrade($conn, $submission_id, $exam_id, $candidate_email, $total_score, $marks_breakdown_json) {
    mysqli_begin_transaction($conn);

    try {
        $sql1 = "UPDATE exam_assignments SET score = ? WHERE exam_id = ? AND candidate_email = ?";
        $stmt1 = mysqli_prepare($conn, $sql1);
        mysqli_stmt_bind_param($stmt1, "dis", $total_score, $exam_id, $candidate_email);
        if (!mysqli_stmt_execute($stmt1)) {
            throw new Exception("Failed to update exam_assignments");
        }
        mysqli_stmt_close($stmt1);

        $sql2 = "UPDATE submissions SET marks_breakdown = ? WHERE submission_id = ?";
        $stmt2 = mysqli_prepare($conn, $sql2);
        // FIXED: Added the missing variable $marks_breakdown_json
        mysqli_stmt_bind_param($stmt2, "si", $marks_breakdown_json, $submission_id);
        if (!mysqli_stmt_execute($stmt2)) {
            throw new Exception("Failed to update submissions");
        }
        mysqli_stmt_close($stmt2);

        mysqli_commit($conn);
        return true;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Grade Save Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Checks if a submission already exists for a given user and exam.
 * @param mysqli $conn The database connection object.
 * @param int $exam_id The ID of the exam.
 * @param int $user_id The ID of the user.
 * @return bool True if a submission exists, false otherwise.
 */
function submissionExists($conn, $exam_id, $user_id) {
    $sql = "SELECT submission_id FROM submissions WHERE exam_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $exam_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $num_rows = mysqli_stmt_num_rows($stmt);
    mysqli_stmt_close($stmt);
    return $num_rows > 0;
}
