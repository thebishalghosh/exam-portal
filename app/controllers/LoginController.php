<?php
// The ROOT_PATH and BASE_URL are defined in index.php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

session_start();
require_once ROOT_PATH . '/config/database.php';

// Handle the login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        header("Location: " . BASE_URL . "/login?error=emptyfields");
        exit();
    }

    $sql = "SELECT admin_id, username, password FROM admin WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: " . BASE_URL . "/admin/dashboard");
            exit();
        } else {
            header("Location: " . BASE_URL . "/login?error=invalidcredentials");
            exit();
        }
        mysqli_stmt_close($stmt);
    } else {
        header("Location: " . BASE_URL . "/login?error=sqlerror");
        exit();
    }
    mysqli_close($conn);
} else {
    // If not a POST request, redirect to the login page
    header("Location: " . BASE_URL . "/login");
    exit();
}
