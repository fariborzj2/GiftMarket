<?php
session_start();
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
    return htmlspecialchars((string)($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function getGroupedProducts() {
    $query = "SELECT p.*, pk.id as pack_id, pk.pack_size, pk.price_digital, pk.price_physical
              FROM products p
              LEFT JOIN product_packs pk ON p.id = pk.product_id
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
