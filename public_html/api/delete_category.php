<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

require_login_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Invalid Request'], 405);
}

$id = $_POST['id'] ?? null;
if (!$id || !is_numeric($id)) {
    json_response(['error' => 'ID is required'], 400);
}

$id = (int)$id;

try {
    $pdo->beginTransaction();

    // Ensure category exists
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $exists = $stmt->fetchColumn();
    if (!$exists) {
        $pdo->rollBack();
        json_response(['error' => 'Category not found'], 404);
    }

    // Delete variants for items in this category
    $stmt = $pdo->prepare("DELETE iv FROM item_variants iv INNER JOIN menu_items mi ON mi.id = iv.menu_item_id WHERE mi.category_id = ?");
    $stmt->execute([$id]);

    // Delete items
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE category_id = ?");
    $stmt->execute([$id]);

    // Delete category
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);

    $pdo->commit();
    json_response(['success' => true]);
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => 'Database error'], 500);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => $e->getMessage()], 500);
}
