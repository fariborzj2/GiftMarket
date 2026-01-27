<?php
if (!file_exists(__DIR__ . '/config.php')) {
    if (file_exists(__DIR__ . '/../../install/index.php')) {
        header("Location: ../install/index.php");
        exit;
    } else {
        die("System not installed and installer missing.");
    }
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../core/Database.php';

function db() {
    return Database::getInstance()->getConnection();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function clean($data) {
    return strip_tags(trim($data));
}

function e($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
