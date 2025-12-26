<?php
session_start();

// Define BASE_URL at the top so it's always available
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/app.php';
    define('BASE_URL', getenv('APP_URL'));
}

// If the admin is already logged in, redirect them to the dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: " . BASE_URL . "/admin/dashboard");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Exam Portal</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/public/assets/images/Travarsa-Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #4CAF50;
            --dark-green: #388E3C;
            --light-green-bg: #e8f5e9;
        }
        body {
            background-color: var(--light-green-bg);
            font-family: 'Roboto', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            border-top: 5px solid var(--primary-green);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo {
            max-width: 180px;
            margin-bottom: 15px;
        }
        .login-header h2 {
            color: #333;
            font-weight: 500;
            font-size: 24px;
        }
        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }
        .btn-primary {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
            padding: 12px;
            font-weight: 500;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: var(--dark-green);
            border-color: var(--dark-green);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="<?php echo BASE_URL; ?>/public/assets/images/Travarsa-Logo.png" alt="Travarsa Logo" class="login-logo">
            <h2>Admin Login</h2>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger text-center">
                <?php
                    if ($_GET['error'] == 'invalid_credentials') {
                        echo "Invalid username or password.";
                    } else {
                        echo "An unknown error occurred.";
                    }
                ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>/login/process" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>
    </div>
</body>
</html>
