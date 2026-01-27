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

require_once 'site/index.php';
