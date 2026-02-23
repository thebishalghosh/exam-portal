<?php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/User.php';
require_once ROOT_PATH . '/app/services/EmailService.php';

// Always return JSON from this webhook
header('Content-Type: application/json');

class InterviewController {

    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    /**
     * Handles webhook from interview portal
     */
    public function handleCandidateWebhook() {

        // Log payload
        $raw = file_get_contents('php://input');
        file_put_contents(ROOT_PATH . '/storage/logs/api.log',
            "WEBHOOK: " . date('Y-m-d H:i:s') . " payload=" . $raw . "\n",
            FILE_APPEND
        );

        // --- 1. API Key Validation ---
        // Fetch headers in a way that works on both Apache and FPM, and be tolerant of casing.
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (strpos($name, 'HTTP_') === 0) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$key] = $value;
                }
            }
        }

        $provided_key = '';
        if (isset($headers['X-API-KEY'])) {
            $provided_key = $headers['X-API-KEY'];
        } elseif (isset($headers['x-api-key'])) {
            $provided_key = $headers['x-api-key'];
        } elseif (isset($headers['X-Api-Key'])) {
            $provided_key = $headers['X-Api-Key'];
        }

        $expected_key = getenv('INTERVIEW_API_KEY');

        if (!$provided_key || $provided_key !== $expected_key) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit();
        }

        // --- 2. Parse Input ---
        $input = json_decode($raw, true);
        $name  = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');

        if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid name or email']);
            exit();
        }

        // --- 3. Create / Update User ---
        $user_id = $this->createOrUpdateUser($name, $email);

        if (!$user_id) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'User creation failed']);
            exit();
        }

        // --- 3a. auto‑assign the candidate to all active interview exams ---
        $examIds = [];
        $sql_exams = "SELECT exam_id FROM exams WHERE exam_type = 'interview' AND status = 'active'";
        $res_ex = mysqli_query($this->conn, $sql_exams);
        while ($row_ex = mysqli_fetch_assoc($res_ex)) {
            $examIds[] = (int)$row_ex['exam_id'];
        }
        foreach ($examIds as $eid) {
            $check = "SELECT assignment_id FROM exam_assignments WHERE exam_id = ? AND candidate_email = ?";
            $stmtc = mysqli_prepare($this->conn, $check);
            mysqli_stmt_bind_param($stmtc, "is", $eid, $email);
            mysqli_stmt_execute($stmtc);
            mysqli_stmt_store_result($stmtc);
            if (mysqli_stmt_num_rows($stmtc) === 0) {
                $insert = "INSERT INTO exam_assignments (exam_id, candidate_id, candidate_email, candidate_source, status) VALUES (?, ?, ?, 'interview', 'assigned')";
                $stmti = mysqli_prepare($this->conn, $insert);
                mysqli_stmt_bind_param($stmti, "iis", $eid, $user_id, $email);
                mysqli_stmt_execute($stmti);
                mysqli_stmt_close($stmti);
            }
            mysqli_stmt_close($stmtc);
        }

        // --- 4. Generate Token ---
        try {
            $token = bin2hex(random_bytes(32));

            // Store token with PHP time (IST)
            $this->storeMagicToken($user_id, $token);

            $magic_link = BASE_URL . '/interviews?token=' . $token;

            if (!$this->sendMagicLinkEmail($email, $name, $magic_link)) {
                throw new Exception('Email sending failed');
            }

        } catch (Exception $e) {
            error_log("Magic link error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to send magic link']);
            exit();
        }

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Magic link sent successfully'
        ]);
    }

    private function createOrUpdateUser($name, $email) {

        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        // Use bind_result / fetch instead of mysqli_stmt_get_result for maximum compatibility
        mysqli_stmt_bind_result($stmt, $existing_id);

        if (mysqli_stmt_fetch($stmt)) {
            $user_id = (int)$existing_id;
            mysqli_stmt_close($stmt);

            $update = "UPDATE users SET name = ? WHERE id = ?";
            $stmt2 = mysqli_prepare($this->conn, $update);
            if ($stmt2) {
                mysqli_stmt_bind_param($stmt2, "si", $name, $user_id);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            }

            return $user_id;

        } else {
            mysqli_stmt_close($stmt);

            $insert = "INSERT INTO users (name, email) VALUES (?, ?)";
            $stmt3 = mysqli_prepare($this->conn, $insert);
            if (!$stmt3) {
                return false;
            }
            mysqli_stmt_bind_param($stmt3, "ss", $name, $email);

            if (mysqli_stmt_execute($stmt3)) {
                $new_id = mysqli_insert_id($this->conn);
                mysqli_stmt_close($stmt3);
                return $new_id;
            }
            mysqli_stmt_close($stmt3);
        }

        return false;
    }

    /**
     * Store token with correct IST expiry
     */
    private function storeMagicToken($user_id, $token) {

        // Generate expiry time using PHP timezone (Asia/Kolkata)
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $sql = "UPDATE users 
                SET magic_link_token = ?, 
                    magic_link_expiry = ? 
                WHERE id = ?";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $token, $expiry, $user_id);

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_error($this->conn);
            error_log("storeMagicToken error: " . $err);
            throw new Exception("Token storage failed");
        }
    }

    private function sendMagicLinkEmail($email, $name, $magic_link) {

        $subject = "Your Interview Invitation";

        $body = "
            <p>Hello {$name},</p>
            <p>You have been invited for an interview.</p>
            <p><a href='{$magic_link}'>Click here to access your exam</a></p>
            <p>This link is valid for 1 hour.</p>
            <p>Good luck!</p>
        ";

        $emailService = new EmailService();
        return $emailService->sendEmail($email, $subject, $body);
    }
}