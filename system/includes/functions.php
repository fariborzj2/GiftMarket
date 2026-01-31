<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
require_once __DIR__ . '/exchange_rate_helper.php';

// Language detection and translation
function getLanguage() {
    if (defined('APP_LANG')) return APP_LANG;

    $lang = 'en';

    // Check URL path (if using /en/ or /ar/)
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    // Normalize URI by removing base path if it exists
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = dirname($scriptName);
    if ($basePath === '/' || $basePath === '\\' || $basePath === '.') $basePath = '';

    $relativeUri = $uri;
    if ($basePath !== '' && strpos($uri, $basePath) === 0) {
        $relativeUri = substr($uri, strlen($basePath));
    }
    $relativeUri = ltrim($relativeUri, '/');

    if (preg_match('/^(en|ar)(\/|$)/', $relativeUri, $matches)) {
        $lang = $matches[1];
    }
    // Check query parameter
    elseif (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'])) {
        $lang = $_GET['lang'];
    }
    // Check session
    elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['en', 'ar'])) {
        $lang = $_SESSION['lang'];
    }
    // Check cookie
    elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], ['en', 'ar'])) {
        $lang = $_COOKIE['lang'];
    }

    if (!defined('APP_LANG')) define('APP_LANG', $lang);
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + (86400 * 30), "/");

    return $lang;
}

$currentLang = getLanguage();
$translations = [];
$langFile = __DIR__ . "/../languages/{$currentLang}.php";
if (file_exists($langFile)) {
    $translations = require $langFile;
}

function __($key, $default = null) {
    global $translations;
    return $translations[$key] ?? ($default ?? $key);
}

function getBaseUrl() {
    // Determine Protocol
    $protocol = 'http';
    if ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1)) ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
        (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on')) {
        $protocol = 'https';
    }

    // For local development or subdirectories
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($script);
    $dir = str_replace('\\', '/', $dir); // Windows fix

    // Ensure $dir starts with / and doesn't end with /
    if ($dir === '/' || $dir === '.') {
        $dir = '';
    } else {
        $dir = '/' . trim($dir, '/');
    }

    // Check if we can determine the host
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . rtrim($host, '/') . $dir . '/';
    } else {
        // Fallback for CLI or cases without HTTP_HOST
        return ($dir === '' ? '/' : $dir . '/');
    }
}
if (!defined('BASE_URL')) define('BASE_URL', getBaseUrl());

// Check for auto-update of exchange rate
if (!is_admin_path()) {
    checkAndAutoUpdateRate();
}

function is_admin_path() {
    return strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
}

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
    return htmlspecialchars((string)($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function getSetting($key, $default = null) {
    try {
        $stmt = db()->prepare("SELECT key_value FROM settings WHERE key_name = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function updateSetting($key, $value) {
    $stmt = db()->prepare("INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)");
    return $stmt->execute([$key, $value]);
}

function getGroupedProducts() {
    $query = "SELECT p.*, pk.id as pack_id, pk.pack_size, pk.price_digital, pk.price_physical
              FROM products p
              LEFT JOIN product_packs pk ON p.id = pk.product_id
              WHERE p.status = 1
              ORDER BY p.brand, p.country, p.denomination, pk.pack_size";
    $results = db()->query($query)->fetchAll();

    $tempProducts = [];
    foreach ($results as $row) {
        $productId = $row['id'];
        if (!isset($tempProducts[$productId])) {
            $tempProducts[$productId] = $row;
            unset($tempProducts[$productId]['pack_id'], $tempProducts[$productId]['pack_size'], $tempProducts[$productId]['price_digital'], $tempProducts[$productId]['price_physical']);
            $tempProducts[$productId]['packs'] = [];
        }
        if ($row['pack_id']) {
            $tempProducts[$productId]['packs'][] = [
                'id' => $row['pack_id'],
                'pack_size' => $row['pack_size'],
                'price_digital' => $row['price_digital'],
                'price_physical' => $row['price_physical']
            ];
        }
    }

    $groupedProducts = [];
    foreach ($tempProducts as $p) {
        $groupedProducts[$p['brand']][$p['country']][] = $p;
    }
    return $groupedProducts;
}
