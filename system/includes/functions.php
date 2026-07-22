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
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . $dir . '/';
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

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function e($data) {
    return htmlspecialchars((string)($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function getCurrencySymbol($curr) {
    switch ($curr) {
        case 'USD': return '$';
        case 'GBP': return '£';
        case 'TRY': return 'TL';
        case 'AED': return 'AED';
        case 'EUR': return '€';
        default: return $curr;
    }
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

/**
 * Remove potentially dangerous content from an SVG string.
 * Strips <script>/<foreignObject>, inline event handlers, javascript: URIs
 * and DOCTYPE/ENTITY declarations (XXE). Returns the cleaned SVG, or false
 * if unsafe content still remains after sanitization.
 */
function sanitizeSvg($svg) {
    if (!is_string($svg) || stripos($svg, '<svg') === false) {
        return false;
    }
    // Remove XXE / entity-expansion vectors
    $svg = preg_replace('/<!DOCTYPE[^>]*(\[[^\]]*\])?[^>]*>/is', '', $svg);
    $svg = preg_replace('/<!ENTITY[^>]*>/is', '', $svg);
    // Remove script and foreignObject blocks
    $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg);
    $svg = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $svg);
    // Remove inline event handlers (onload, onclick, ...)
    $svg = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg);
    // Remove javascript: URIs in href/src attributes
    $svg = preg_replace('/(href|xlink:href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', '', $svg);

    // Final safety gate: reject if any known vector survived
    if (preg_match('/<script\b/i', $svg)
        || preg_match('/javascript:/i', $svg)
        || preg_match('/\son\w+\s*=/i', $svg)
        || preg_match('/<!ENTITY/i', $svg)) {
        return false;
    }
    return $svg;
}

/**
 * Securely validate and save an uploaded image.
 * - Enforces a 2MB size limit and an extension whitelist.
 * - Raster images are verified with getimagesize() and their real MIME type
 *   must match the extension (defeats disguised-file uploads).
 * - SVG files are sanitized before being written to disk.
 *
 * @param array  $file       An entry from $_FILES.
 * @param string $uploadDir  Filesystem dir to write to (CWD-relative, e.g. '../assets/images/brand/').
 * @param string $webDir     Public web path prefix stored in the DB (e.g. 'assets/images/brand/').
 * @param string $namePrefix Prefix for the generated filename.
 * @return array ['ok'=>true,'path'=>string] on success, ['ok'=>false,'error'=>string] on failure.
 */
function saveUploadedImage($file, $uploadDir, $webDir, $namePrefix) {
    $allowed = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
    ];

    if (!isset($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'خطا در آپلود فایل.'];
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'خطا: حجم فایل بیش از حد مجاز (۲ مگابایت) است.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        return ['ok' => false, 'error' => 'خطا: پسوند فایل مجاز نیست. (فقط تصاویر مجاز هستند)'];
    }

    $tmp = $file['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'خطا در آپلود فایل.'];
    }

    $svgContent = null;
    if ($ext === 'svg') {
        $svgContent = @file_get_contents($tmp);
        $svgContent = sanitizeSvg($svgContent);
        if ($svgContent === false) {
            return ['ok' => false, 'error' => 'خطا: فایل SVG حاوی محتوای غیرمجاز است.'];
        }
    } else {
        $info = @getimagesize($tmp);
        if ($info === false || ($info['mime'] ?? '') !== $allowed[$ext]) {
            return ['ok' => false, 'error' => 'خطا: فایل یک تصویر معتبر نیست یا نوع آن با پسوند مطابقت ندارد.'];
        }
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        return ['ok' => false, 'error' => 'خطا: پوشه آپلود قابل ایجاد نیست.'];
    }

    $fileName = $namePrefix . '_' . time() . '.' . $ext;
    $destFs = rtrim($uploadDir, '/') . '/' . $fileName;

    if ($ext === 'svg') {
        if (file_put_contents($destFs, $svgContent) === false) {
            return ['ok' => false, 'error' => 'خطا در ذخیره فایل.'];
        }
    } else {
        if (!move_uploaded_file($tmp, $destFs)) {
            return ['ok' => false, 'error' => 'خطا در ذخیره فایل.'];
        }
    }

    return ['ok' => true, 'path' => rtrim($webDir, '/') . '/' . $fileName];
}
