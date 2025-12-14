<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

require_login_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Invalid Request'], 405);
}

$id = $_POST['id'] ?? null;

if (!$id) {
    json_response(['error' => 'No ID provided'], 400);
}

try {
    // Optionally fetch image path to delete file, but for now just DB delete
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    
    json_response(['success' => true]);
} catch (PDOException $e) {
    json_response(['error' => $e->getMessage()], 500);
}
