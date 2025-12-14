<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

require_login_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Invalid Request'], 405);
}

// Validate Inputs
$id = $_POST['id'] ?? null;
$category_id = $_POST['category_id'] ?? null;
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
// Removed day_of_week and is_special
$is_active = isset($_POST['is_active']) ? 1 : 0;
$variants_json = $_POST['variants'] ?? '[]'; // JSON string of variants

if (!$name || !$category_id) {
    json_response(['error' => 'Missing required fields'], 400);
}

$variants = json_decode($variants_json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $variants = []; // Fallback/Error
}

// Check for at least one valid variant (Price > 0)
$has_valid_variant = false;
foreach ($variants as $v) {
    if (floatval($v['price']) > 0) {
        $has_valid_variant = true;
        break;
    }
}

if (!$has_valid_variant) {
    json_response(['error' => 'At least one size with a valid price (> 0) is required'], 400);
}

// Image Upload Logic Removed completely

try {
    $pdo->beginTransaction();

    if ($id) {
        // Update existing item
        // Removed is_special, day_of_week, image_path from update
        $sql = "UPDATE menu_items SET category_id = ?, name = ?, description = ?, is_active = ? WHERE id = ?";
        $params = [$category_id, $name, $description, $is_active, $id];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Update Variants: Delete all and re-insert
        $stmt_del = $pdo->prepare("DELETE FROM item_variants WHERE menu_item_id = ?");
        $stmt_del->execute([$id]);
        $item_id = $id;
        
    } else {
        // Create new item
        // Removed is_special, day_of_week, image_path from insert
        $sql = "INSERT INTO menu_items (category_id, name, description, is_active) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$category_id, $name, $description, $is_active]);
        $item_id = $pdo->lastInsertId();
    }

    // Insert Variants
    $stmt_var = $pdo->prepare("INSERT INTO item_variants (menu_item_id, size_id, price) VALUES (?, ?, ?)");
    foreach ($variants as $v) {
        $price = floatval($v['price']);
        $size_id = intval($v['size']);
        // Item only has variant if price > 0
        if ($price > 0 && $size_id > 0) {
            $stmt_var->execute([$item_id, $size_id, $price]);
        }
    }
    
    $pdo->commit();
    json_response(['success' => true]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    json_response(['error' => $e->getMessage()], 500);
}
