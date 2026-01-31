<?php
/**
 * Root Index File
 * UAE.GIFT | Gift Card Wholesale & Retail System
 */

$configFile = __DIR__ . '/system/includes/config.php';

if (!file_exists($configFile)) {
    header("Location: install/index.php");
    exit;
}

require_once $configFile;

if (!defined('INSTALLED') || INSTALLED !== true) {
    header("Location: install/index.php");
    exit;
}

// Simple language detection if passed via query string (from .htaccess)
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'])) {
    if (!defined('APP_LANG')) define('APP_LANG', $_GET['lang']);
}

require_once 'site/index.php';
