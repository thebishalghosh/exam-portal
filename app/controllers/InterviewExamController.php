<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
}

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Exam.php';

class InterviewExamController {

    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    public function showExams() {
        $token = isset($_GET['token']) ? $_GET['token'] : '';

        if (empty($token)) {
            die("Invalid or missing token.");
        }

        $user = $this->verifyTokenAndLogin($token);
        if (!$user) {
            die("Invalid or expired token.");
        }

        $exams = $this->getInterviewExams();

        // Load the view
        require_once ROOT_PATH . '/app/views/interview/exams.php';
    }

    private function verifyTokenAndLogin($token) {
        // fetch the token row without filtering by expiry; we'll check in PHP
        $sql = "SELECT id, name, email, magic_link_expiry FROM users WHERE magic_link_token = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            $expiry = $user['magic_link_expiry'];
            if ($expiry && strtotime($expiry) > time()) {
                // valid, invalidate and log in
                $sql_update = "UPDATE users SET magic_link_token = NULL, magic_link_expiry = NULL WHERE id = ?";
                $stmt_update = mysqli_prepare($this->conn, $sql_update);
                mysqli_stmt_bind_param($stmt_update, "i", $user['id']);
                mysqli_stmt_execute($stmt_update);

                session_start();
                $_SESSION['candidate_id'] = $user['id'];
                $_SESSION['candidate_email'] = $user['email'];
                $_SESSION['candidate_name'] = $user['name'];
                $_SESSION['candidate_logged_in'] = true;

                return $user;
            }
        }
        return false;
    }

    private function getInterviewExams() {
        $sql = "SELECT exam_id, title, description, duration FROM exams WHERE exam_type = 'interview' AND status = 'active' ORDER BY title ASC";
        $result = mysqli_query($this->conn, $sql);
        return $result;
    }
}
