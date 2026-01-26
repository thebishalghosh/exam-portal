<?php
session_start();

// Define BASE_URL if it's not already set
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
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Travarsa-Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #43A047;
            --text-dark: #1F2937;
            --text-muted: #6B7280;
            --bg-light: #F3F4F6;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: white;
            height: 100vh;
            overflow: hidden;
        }
        .split-layout {
            display: flex;
            height: 100%;
            width: 100%;
        }
        /* Left Side - Image */
        .left-panel {
            flex: 1;
            background-image: url('https://images.unsplash.com/photo-1497215728101-856f4ea42174?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            position: relative;
            display: none; /* Hidden on mobile */
        }
        .left-panel::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.8));
        }
        .left-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            color: white;
        }
        .left-content h1 {
            font-weight: 700;
            font-size: 3.5rem;
            margin-bottom: 1rem;
            line-height: 1.1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .left-content p {
            font-size: 1.25rem;
            opacity: 0.95;
            max-width: 500px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        /* Right Side - Form */
        .right-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background-color: white;
            overflow-y: auto;
        }
        .form-container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
        }
        .logo-img {
            height: 50px;
            margin-bottom: 2rem;
        }
        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        .form-subtitle {
            color: var(--text-muted);
            margin-bottom: 2.5rem;
        }
        .form-label {
            font-weight: 500;
            color: #374151;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.1);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.875rem;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
            margin-top: 1.5rem;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        /* Responsive Adjustments */
        @media (min-width: 992px) {
            .left-panel { display: block; }
        }
        @media (max-width: 991px) {
            .right-panel {
                align-items: flex-start; /* Align to top on mobile */
                padding-top: 4rem;
            }
            .form-container {
                padding: 1rem;
            }
            .form-title {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="split-layout">
        <!-- Left Side -->
        <div class="left-panel">
            <div class="left-content">
                <h1>Welcome Back,<br>Administrator.</h1>
                <p>Manage exams, monitor candidates, and analyze performance from your secure dashboard.</p>
            </div>
        </div>

        <!-- Right Side -->
        <div class="right-panel">
            <div class="form-container">
                <img src="<?php echo BASE_URL; ?>/assets/images/Travarsa-Logo.png" alt="Travarsa Logo" class="logo-img">

                <h2 class="form-title">Admin Login</h2>
                <p class="form-subtitle">Please enter your credentials to continue.</p>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger py-2 mb-4 text-center border-0 bg-danger bg-opacity-10 text-danger">
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
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Sign In</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
