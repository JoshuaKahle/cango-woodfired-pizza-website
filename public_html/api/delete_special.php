<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

require_login_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Invalid Request'], 405);
}

$id = $_POST['id'] ?? null;

if (!$id) {
    json_response(['error' => 'ID is required'], 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM item_variants WHERE menu_item_id = ?");
    $stmt->execute([(int)$id]);

    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->execute([(int)$id]);

    $pdo->commit();
    json_response(['success' => true]);
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => $e->getMessage()], 500);
}
