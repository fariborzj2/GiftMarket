<?php
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
