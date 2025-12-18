<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

session_start();
session_unset();
session_destroy();

header("Location: " . BASE_URL . "/login");
exit();
