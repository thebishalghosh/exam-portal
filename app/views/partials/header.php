<?php
// Generic header used by public-facing pages (candidates, open exams, etc.)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Define BASE_URL if not defined already
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__, 3) . '/config/app.php';
    define('BASE_URL', getenv('APP_URL'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Portal</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/assets/images/Travarsa-Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/public/assets/css/site.css" rel="stylesheet">
</head>
<body>
<div class="container">
