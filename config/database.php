<?php
// This file connects to the database using credentials from the .env file.

// Ensure environment variables are loaded (should be done by index.php)
if (!getenv('DB_SERVERNAME')) {
    // This can happen if the file is accessed directly or .env is not loaded
    die("Database configuration is not loaded. Please check your .env file and config/app.php.");
}

$servername = getenv('DB_SERVERNAME');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    // Use mysqli_connect_error() to get the specific error message
    die("Connection failed: " . mysqli_connect_error());
}
