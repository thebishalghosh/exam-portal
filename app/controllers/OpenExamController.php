<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}

session_start();

// 1. Check if candidate is logged in
if (!isset($_SESSION['candidate_logged_in']) || $_SESSION['candidate_logged_in'] !== true) {
    header("Location: " . BASE_URL . "/register/open-exam");
    exit();
}

require_once ROOT_PATH . '/config/database.php';

// 2. Get data from URL and Session
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$candidate_email = $_SESSION['candidate_email'];
$candidate_id = $_SESSION['candidate_id']; // This is the user_id

if ($exam_id === 0) {
    die("Invalid exam specified.");
}

// 3. Check if an assignment already exists
$sql_check = "SELECT assignment_id FROM exam_assignments WHERE exam_id = ? AND candidate_email = ?";
$stmt_check = mysqli_prepare($conn, $sql_check);
mysqli_stmt_bind_param($stmt_check, "is", $exam_id, $candidate_email);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);

if (mysqli_stmt_num_rows($stmt_check) === 0) {
    // 4. If not, create a new assignment
    $sql_insert = "INSERT INTO exam_assignments (exam_id, candidate_id, candidate_email, candidate_source, status) VALUES (?, ?, ?, 'open_registration', 'assigned')";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);
    if ($stmt_insert) {
        // Note: The candidate_id here is the user_id from our local users table.
        // The original table design might have intended this to be an employee_id from HR.
        // For open registration, using the local user_id is correct.
        mysqli_stmt_bind_param($stmt_insert, "iis", $exam_id, $candidate_id, $candidate_email);
        if (!mysqli_stmt_execute($stmt_insert)) {
            die("Failed to create exam assignment. Please try again.");
        }
        mysqli_stmt_close($stmt_insert);
    }
}
mysqli_stmt_close($stmt_check);

// 5. Redirect to the exam page
header("Location: " . BASE_URL . "/exam/take/" . $exam_id);
exit();
