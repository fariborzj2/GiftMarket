<?php
// Database Configuration
define('DB_PATH', __DIR__ . '/../core/database.sqlite');

// System Configuration
define('SITE_NAME', 'UAE.GIFT Admin');
define('BASE_URL', 'http://localhost:3000');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
