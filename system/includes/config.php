<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'uaegift_db');
define('DB_USER', 'root');
define('DB_PASS', '');

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
