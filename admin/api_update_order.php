<?php
require_once __DIR__ . '/../system/includes/functions.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table = $_POST['table'] ?? '';
    $ids = $_POST['ids'] ?? [];

    if (!in_array($table, ['brands', 'countries'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid table']);
        exit;
    }

    if (!empty($ids)) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE $table SET sort_order = ? WHERE id = ?");
            foreach ($ids as $index => $id) {
                $stmt->execute([$index, $id]);
            }
            $pdo->commit();
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
