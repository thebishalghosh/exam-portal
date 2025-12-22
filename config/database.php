<?php
// This file connects to the database using credentials from the .env file.

// Ensure environment variables are loaded
if (!getenv('DB_SERVERNAME')) {
    die("Database configuration is not loaded. Please check your .env file and config/app.php.");
}

// --- Enable mysqli error reporting ---
// This is the crucial line. It tells mysqli to throw exceptions on errors.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = getenv('DB_SERVERNAME');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
