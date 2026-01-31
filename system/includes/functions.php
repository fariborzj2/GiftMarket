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
