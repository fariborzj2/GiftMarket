<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

$customer = currentCustomer();
if (!$customer) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'bad request']);
    exit;
}

$productId = (int) ($_POST['product_id'] ?? 0);
if ($productId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'invalid product']);
    exit;
}

// Ensure the product exists (avoids orphan rows / FK errors)
$chk = db()->prepare("SELECT id FROM products WHERE id = ?");
$chk->execute([$productId]);
if (!$chk->fetch()) {
    echo json_encode(['ok' => false, 'message' => 'not found']);
    exit;
}

$exists = db()->prepare("SELECT id FROM customer_watchlist WHERE customer_id = ? AND product_id = ?");
$exists->execute([$customer['id'], $productId]);

if ($exists->fetch()) {
    $del = db()->prepare("DELETE FROM customer_watchlist WHERE customer_id = ? AND product_id = ?");
    $del->execute([$customer['id'], $productId]);
    $saved = false;
} else {
    $ins = db()->prepare("INSERT INTO customer_watchlist (customer_id, product_id) VALUES (?, ?)");
    $ins->execute([$customer['id'], $productId]);
    $saved = true;
}

$cnt = db()->prepare("SELECT COUNT(*) FROM customer_watchlist WHERE customer_id = ?");
$cnt->execute([$customer['id']]);

echo json_encode(['ok' => true, 'saved' => $saved, 'count' => (int) $cnt->fetchColumn()]);
