<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="icon" href="/exam/public/assets/images/Travarsa-Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #4CAF50;
            --primary-green-dark: #388E3C;
            --primary-green-light: #f1f8f2;
            --muted: #f8f9fa;
            --border: #dee2e6;
            --text-muted: #6c757d;
        }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, rgba(186,240,209,0.4), rgba(65,167,115,0.25)),
                       var(--muted);
            animation: fadeIn 0.6s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .auth-card {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(65, 167, 115, 0.15);
            max-width: 460px;
            width: 100%;
            padding: 2.4rem 2rem;
            animation: pop 0.35s ease;
            text-align: center; /* Center align content */
        }
        @keyframes pop {
            from { transform: scale(0.97); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .logo {
            max-width: 150px; /* Reduced logo size */
            margin-bottom: 1.5rem; /* Space below logo */
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-green-light);
            color: var(--primary-green-dark);
            padding: 0.55rem 1rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 1.2rem;
        }
        .auth-card h1 {
            font-weight: 800;
            color: var(--primary-green-dark);
            margin-bottom: 0.4rem;
            font-size: 1.9rem;
        }
        .auth-card .lead {
            color: var(--text-muted);
            margin-bottom: 1.4rem;
            font-size: 0.98rem;
        }
        .form-control {
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 0.85rem 0.95rem;
            transition: 0.2s;
            text-align: left; /* Keep form text left-aligned */
        }
        .form-label {
            text-align: left;
            display: block;
            width: 100%;
        }
        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.15rem rgba(65,167,115,0.22);
        }
        .btn-primary {
            background: linear-gradient(120deg, var(--primary-green), var(--primary-green-dark));
            border: none;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(65, 167, 115, 0.25);
            font-weight: 700;
            padding: 0.8rem 1rem;
            transition: 0.25s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(65, 167, 115, 0.32);
            filter: brightness(0.97);
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <img src="/exam/public/assets/images/Travarsa-Logo.png" alt="Travarsa Logo" class="logo">

        <h1>Admin Access</h1>
        <p class="lead">Sign in to continue to your dashboard.</p>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger text-start">
                <?php
                    if ($_GET['error'] === 'invalidcredentials') echo 'Invalid username or password.';
                    else echo 'Please fill in all fields.';
                ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>/login/process" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
