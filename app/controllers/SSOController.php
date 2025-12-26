<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}

require_once ROOT_PATH . '/config/database.php';

session_start();

// 1. Get token and target exam from URL
$session_token = isset($_GET['session_token']) ? trim($_GET['session_token']) : '';
$target_exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

if (empty($session_token)) {
    die("Error: Missing session token.");
}

// 2. Construct verification URL
$hr_api_base = dirname(getenv('HR_API_URL'));
$verify_url = $hr_api_base . '/verify-session.php';
$api_key = getenv('HR_API_KEY');

// 3. Call HR Portal API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $verify_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['session_token' => $session_token]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-KEY: ' . $api_key]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    die("SSO Error: Failed to verify session with HR Portal. (HTTP $http_code)");
}

$result = json_decode($response, true);

if (!$result || !isset($result['status']) || $result['status'] !== 'success' || empty($result['user_email']) || empty($result['role'])) {
    die("SSO Error: Invalid or expired session, or role not provided.");
}

$user_email = $result['user_email'];
$user_role = $result['role'];

// 4. Handle login based on role
if ($user_role === 'admin') {
    // --- Admin Login Flow ---
    $admin_id = 0;
    $sql_check = "SELECT admin_id FROM admin WHERE username = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $user_email);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if ($row = mysqli_fetch_assoc($result_check)) {
        $admin_id = $row['admin_id'];
    } else {
        // Auto-create admin account if it doesn't exist
        // Note: A dummy password is used as auth is handled by the HR portal
        $dummy_password = password_hash(uniqid(), PASSWORD_DEFAULT);
        $sql_create = "INSERT INTO admin (username, password) VALUES (?, ?)";
        $stmt_create = mysqli_prepare($conn, $sql_create);
        mysqli_stmt_bind_param($stmt_create, "ss", $user_email, $dummy_password);
        if (mysqli_stmt_execute($stmt_create)) {
            $admin_id = mysqli_insert_id($conn);
        } else {
            die("SSO Error: Failed to create local admin account.");
        }
        mysqli_stmt_close($stmt_create);
    }
    mysqli_stmt_close($stmt_check);

    // Create Admin Session
    $_SESSION['admin_id'] = $admin_id;
    $_SESSION['admin_username'] = $user_email;
    $_SESSION['admin_logged_in'] = true;

    // Redirect to Admin Dashboard
    header("Location: " . BASE_URL . "/admin/dashboard");

} elseif ($user_role === 'candidate') {
    // --- Candidate Login Flow ---
    $user_id = 0;
    $sql_check = "SELECT id FROM users WHERE email = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $user_email);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if ($row = mysqli_fetch_assoc($result_check)) {
        $user_id = $row['id'];
    } else {
        $sql_create = "INSERT INTO users (email) VALUES (?)";
        $stmt_create = mysqli_prepare($conn, $sql_create);
        mysqli_stmt_bind_param($stmt_create, "s", $user_email);
        if (mysqli_stmt_execute($stmt_create)) {
            $user_id = mysqli_insert_id($conn);
        } else {
            die("SSO Error: Failed to create local user account.");
        }
        mysqli_stmt_close($stmt_create);
    }
    mysqli_stmt_close($stmt_check);

    // Create Candidate Session
    $_SESSION['candidate_id'] = $user_id;
    $_SESSION['candidate_email'] = $user_email;
    $_SESSION['candidate_logged_in'] = true;

    // Redirect to the Exam
    if ($target_exam_id > 0) {
        header("Location: " . BASE_URL . "/exam/take/" . $target_exam_id);
    } else {
        die("Login successful! You can now close this window.");
    }

} else {
    die("SSO Error: Unknown user role provided.");
}

exit();
