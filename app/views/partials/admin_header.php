<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Define BASE_URL if it's not already set
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__, 3) . '/config/app.php';
    define('BASE_URL', getenv('APP_URL'));
}

// If the admin is not logged in, redirect to the login page
if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/login");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/assets/images/Travarsa-Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/public/assets/css/admin.css" rel="stylesheet">
    <!-- Google Charts Loader -->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
</head>
<body>
<div class="d-flex" id="wrapper">
