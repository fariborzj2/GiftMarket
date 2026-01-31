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

// Get base path
$scriptName = $_SERVER['SCRIPT_NAME']; // e.g. /index.php
$basePath = dirname($scriptName);
if ($basePath === '/' || $basePath === '\\' || $basePath === '.') $basePath = '';

// Get URI relative to base path
$uri = $_SERVER['REQUEST_URI'];
$relativeUri = substr($uri, strlen($basePath));
$relativeUri = ltrim($relativeUri, '/');
$parts = explode('/', $relativeUri);

$lang = null;
if (isset($parts[0]) && in_array($parts[0], ['en', 'ar'])) {
    $lang = $parts[0];
    if (!defined('APP_LANG')) define('APP_LANG', $lang);
} else {
    // If no lang in URL, redirect based on cookie or default to 'en'
    // But only if we are at the root
    if (empty($relativeUri) || $relativeUri === 'index.php') {
        $cookieLang = $_COOKIE['lang'] ?? 'en';
        if (!in_array($cookieLang, ['en', 'ar'])) $cookieLang = 'en';

        $redirectUrl = ($basePath === '' ? '' : $basePath) . '/' . $cookieLang . '/';
        header("Location: $redirectUrl");
        exit;
    }
}

require_once 'site/index.php';
