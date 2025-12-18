<?php
// The ROOT_PATH and BASE_URL are defined in index.php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

// This file is now purely for viewing.
// It just needs to include the login form template.
require_once ROOT_PATH . '/app/views/auth/login.php';
