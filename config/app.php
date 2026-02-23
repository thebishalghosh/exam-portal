<?php
// This file loads environment variables from the .env file.

// Check if the .env file exists
$envPath = ROOT_PATH . '/.env';
if (!is_readable($envPath)) {
    die("Error: .env file not found or is not readable. Please create it in the project root.");
}

// Read the lines from the .env file
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    // Ignore comments
    if (strpos(trim($line), '#') === 0) {
        continue;
    }

    // Split the line into a key and a value
    list($name, $value) = explode('=', $line, 2);
    $name = trim($name);
    $value = trim($value);

    // Remove surrounding quotes from the value
    if (substr($value, 0, 1) == '"' && substr($value, -1) == '"') {
        $value = substr($value, 1, -1);
    }

    // Set the environment variable
    putenv("$name=$value");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

// Base URL convenience constant for scripts that rely on it.  Defined here once.
if (!defined('BASE_URL')) {
    $appUrl = getenv('APP_URL');
    // ensure there's no trailing slash
    $appUrl = rtrim($appUrl, '/');
    define('BASE_URL', $appUrl);
}
date_default_timezone_set('Asia/Kolkata');