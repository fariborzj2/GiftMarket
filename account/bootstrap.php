<?php
/**
 * Customer account bootstrap.
 * Loads the core system and provides customer-auth helpers.
 * Customer sessions use $_SESSION['customer_id'] and are fully
 * independent from the admin session ($_SESSION['user_id']).
 */
require_once __DIR__ . '/../system/includes/functions.php';

function customerLoggedIn() {
    return isset($_SESSION['customer_id']);
}

function currentCustomer() {
    static $customer = false;
    if ($customer !== false) return $customer;

    if (!customerLoggedIn()) {
        $customer = null;
        return null;
    }

    $stmt = db()->prepare("SELECT * FROM customers WHERE id = ? AND status = 1");
    $stmt->execute([$_SESSION['customer_id']]);
    $customer = $stmt->fetch() ?: null;

    if (!$customer) {
        // Stale or disabled account: clear the session.
        unset($_SESSION['customer_id']);
    }
    return $customer;
}

/** Guard: send guests to the login page. */
function requireCustomer() {
    if (!currentCustomer()) {
        redirect('login.php');
    }
}

/** Guard for public pages (login/register): send logged-in users to the dashboard. */
function redirectIfCustomer() {
    if (currentCustomer()) {
        redirect('index.php');
    }
}

/** IDs of products the current customer has saved. */
function customerWatchlistIds() {
    static $ids = null;
    if ($ids !== null) return $ids;
    $ids = [];
    $c = currentCustomer();
    if (!$c) return $ids;
    $stmt = db()->prepare("SELECT product_id FROM customer_watchlist WHERE customer_id = ?");
    $stmt->execute([$c['id']]);
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    return $ids;
}
