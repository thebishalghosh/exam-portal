<?php
session_start();

// The path is relative to the location of AuthController.php
require_once __DIR__ . '/../../config/database.php';

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($password)) {
        header("Location: /exam/app/views/auth/login.php?error=emptyfields");
        exit();
    }

    // Prepare and execute the statement to find the admin by username
    $sql = "SELECT admin_id, username, password FROM admin WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);

        // Verify admin exists and password is correct
        if ($admin && password_verify($password, $admin['password'])) {
            // Password is correct, set session variables
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];

            // Redirect to the admin dashboard
            // NOTE: We will create this file in the next step.
            header("Location: /exam/public/admin/dashboard.php");
            exit();

        } else {
            // Invalid credentials
            header("Location: /exam/app/views/auth/login.php?error=invalidcredentials");
            exit();
        }
        mysqli_stmt_close($stmt);
    } else {
        // SQL error
        header("Location: /exam/app/views/auth/login.php?error=sqlerror");
        exit();
    }
    mysqli_close($conn);
} else {
    // Redirect to login if accessed directly
    header("Location: /exam/app/views/auth/login.php");
    exit();
}
