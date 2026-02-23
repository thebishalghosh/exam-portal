<?php
// Set content type to JSON for API requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Submission.php';
require_once ROOT_PATH . '/app/models/Api.php'; // for talking to external HR/interview portals

session_start();

// --- Handle Grade Saving (from Admin) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grade'])) {
    if (!isset($_SESSION['admin_id'])) {
        die("Unauthorized access.");
    }

    $submission_id = (int)$_POST['submission_id'];
    $marks = $_POST['marks'] ?? [];

    $total_score = 0;
    foreach ($marks as $mark) {
        $total_score += (float)$mark;
    }

    $marks_breakdown_json = json_encode($marks);

    $submission_details = getSubmissionDetails($conn, $submission_id);
    if (!$submission_details) {
        die("Submission not found.");
    }

    $exam_id = $submission_details['exam_id'];
    $candidate_email = $submission_details['candidate_email'];

    if (saveFinalGrade($conn, $submission_id, $exam_id, $candidate_email, $total_score, $marks_breakdown_json)) {
        // notify interview portal that grading is done (manual score)
        $exam_title = $submission_details['exam_title'] ?? null;
        reportExamResultToInterview($candidate_email, $exam_id, 'graded', $total_score, $exam_title, 'manual');
        header("Location: " . BASE_URL . "/admin/submissions?success=graded");
    } else {
        header("Location: " . BASE_URL . "/admin/submission/view/$submission_id?error=dberror");
    }
    exit();
}


// --- Handle Exam Submission (from Candidate) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data received.']);
        exit();
    }

    $exam_id = isset($data['exam_id']) ? (int)$data['exam_id'] : 0;
    $email = isset($data['email']) ? trim($data['email']) : '';
    $answers = isset($data['answers']) ? json_encode($data['answers']) : '{}';
    $status = isset($data['auto']) && $data['auto'] === true ? 'Disqualified' : 'Completed';

    if ($exam_id === 0 || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing exam ID or email.']);
        exit();
    }

    // attempt to fetch exam title
    $exam_title = '';
    $stmt_et = mysqli_prepare($conn, "SELECT title FROM exams WHERE exam_id = ?");
    if ($stmt_et) {
        mysqli_stmt_bind_param($stmt_et, "i", $exam_id);
        mysqli_stmt_execute($stmt_et);
        mysqli_stmt_bind_result($stmt_et, $exam_title);
        mysqli_stmt_fetch($stmt_et);
        mysqli_stmt_close($stmt_et);
    }

    // compute an automatic score based on mcq correctness, if possible
    $auto_score = null;
    $answers_arr = json_decode($answers, true);
    if (is_array($answers_arr)) {
        $auto_score = 0;
        $sql_q = "SELECT question_id, type, correct_answer, marks FROM questions WHERE exam_id = ?";
        $stmt_q = mysqli_prepare($conn, $sql_q);
        if ($stmt_q) {
            mysqli_stmt_bind_param($stmt_q, "i", $exam_id);
            mysqli_stmt_execute($stmt_q);
            mysqli_stmt_bind_result($stmt_q, $q_id, $q_type, $q_correct, $q_marks);
            while (mysqli_stmt_fetch($stmt_q)) {
                if ($q_type === 'mcq' && isset($answers_arr[$q_id]) && $answers_arr[$q_id] == $q_correct) {
                    $auto_score += (float)$q_marks;
                }
            }
            mysqli_stmt_close($stmt_q);
        }
    }

    // Find or Create User
    $user_id = 0;
    $sql_check = "SELECT id FROM users WHERE email = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $email);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if ($row = mysqli_fetch_assoc($result_check)) {
        $user_id = $row['id'];
    } else {
        $sql_create = "INSERT INTO users (email) VALUES (?)";
        $stmt_create = mysqli_prepare($conn, $sql_create);
        mysqli_stmt_bind_param($stmt_create, "s", $email);
        if (mysqli_stmt_execute($stmt_create)) {
            $user_id = mysqli_insert_id($conn);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create user record.']);
            exit();
        }
        mysqli_stmt_close($stmt_create);
    }
    mysqli_stmt_close($stmt_check);

    // --- CRITICAL FIX: Check for existing submission ---
    $sql_exists = "SELECT submission_id FROM submissions WHERE exam_id = ? AND user_id = ?";
    $stmt_exists = mysqli_prepare($conn, $sql_exists);
    mysqli_stmt_bind_param($stmt_exists, "ii", $exam_id, $user_id);
    mysqli_stmt_execute($stmt_exists);
    mysqli_stmt_store_result($stmt_exists);

    if (mysqli_stmt_num_rows($stmt_exists) > 0) {
        // Submission already exists!
        echo json_encode(['status' => 'error', 'message' => 'You have already submitted this exam.']);
        mysqli_stmt_close($stmt_exists);
        exit();
    }
    mysqli_stmt_close($stmt_exists);
    // ---------------------------------------------------

    // Save Submission
    $sql_submit = "INSERT INTO submissions (exam_id, user_id, submitted_answers, status, start_time, end_time) VALUES (?, ?, ?, ?, NOW(), NOW())";
    $stmt_submit = mysqli_prepare($conn, $sql_submit);

    if ($stmt_submit) {
        mysqli_stmt_bind_param($stmt_submit, "iiss", $exam_id, $user_id, $answers, $status);

        if (mysqli_stmt_execute($stmt_submit)) {
            // Also update the status in exam_assignments to 'completed'
            $sql_update_assign = "UPDATE exam_assignments SET status = 'completed' WHERE exam_id = ? AND candidate_email = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update_assign);
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, "is", $exam_id, $email);
                mysqli_stmt_execute($stmt_update);
                mysqli_stmt_close($stmt_update);
            }

            // let the portal know the candidate finished the exam
            reportExamResultToInterview(
                $email,
                $exam_id,
                $status,
                $auto_score,
                $exam_title,
                'auto'
            );

            echo json_encode(['status' => 'success', 'message' => 'Exam submitted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
        }
        mysqli_stmt_close($stmt_submit);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare submission statement.']);
    }

    mysqli_close($conn);
    exit();
}

header("Location: " . BASE_URL . "/admin/dashboard");
exit();
