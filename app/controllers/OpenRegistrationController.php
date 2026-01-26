<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}

require_once ROOT_PATH . '/config/database.php';

session_start();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $roll_number = trim($_POST['roll_number'] ?? '');
    $college_name = trim($_POST['college_name'] ?? '');

    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid name and email.";
        require_once ROOT_PATH . '/public/register/open_exam.php';
        exit();
    }

    // Check if user exists
    $user_id = 0;
    $sql_check = "SELECT id FROM users WHERE email = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $email);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if ($row = mysqli_fetch_assoc($result_check)) {
        $user_id = $row['id'];
        // Update existing user's roll_number and college_name if provided
        $sql_update = "UPDATE users SET roll_number = ?, college_name = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ssi", $roll_number, $college_name, $user_id);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);

    } else {
        // Create new user
        $sql_create = "INSERT INTO users (email, roll_number, college_name) VALUES (?, ?, ?)";
        $stmt_create = mysqli_prepare($conn, $sql_create);
        mysqli_stmt_bind_param($stmt_create, "sss", $email, $roll_number, $college_name);
        if (mysqli_stmt_execute($stmt_create)) {
            $user_id = mysqli_insert_id($conn);
        } else {
            $error = "Registration failed. Please try again.";
            require_once ROOT_PATH . '/public/register/open_exam.php';
            exit();
        }
        mysqli_stmt_close($stmt_create);
    }
    mysqli_stmt_close($stmt_check);

    // Set Session
    $_SESSION['candidate_id'] = $user_id;
    $_SESSION['candidate_email'] = $email;
    $_SESSION['candidate_name'] = $name;
    $_SESSION['candidate_roll_number'] = $roll_number;
    $_SESSION['candidate_college_name'] = $college_name;
    $_SESSION['candidate_logged_in'] = true;

    // Redirect to Open Exams List
    header("Location: " . BASE_URL . "/exams/open");
    exit();
}

// Show Registration Form
require_once ROOT_PATH . '/public/register/open_exam.php';
