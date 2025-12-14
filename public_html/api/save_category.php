<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

require_login_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Invalid Request'], 405);
}

    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $display_order = $_POST['display_order'] ?? 0;
    $show_measurements = isset($_POST['show_measurements']) ? 1 : 0;
    
    // Process allowed_variants (expecting array or JSON string)
    $allowed_variants = $_POST['allowed_variants'] ?? [];
    if (is_string($allowed_variants)) {
        // Checking if it's already JSON or just a string from some other input?
        // Assuming AJAX sends it as an array, PHP sees it as array if name="allowed_variants[]"
        // If JS sends JSON string manually, decode it.
        $decoded = json_decode($allowed_variants, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $allowed_variants = $decoded;
        } else {
             // Maybe comma separated?
             $allowed_variants = []; 
        }
    }
    
    // Ensure all variants are integers for cleaner JSON and strictly typed comparisons
    if (!empty($allowed_variants)) {
        $allowed_variants = array_map('intval', $allowed_variants);
    }

    // Encode for DB
    $allowed_variants_json = !empty($allowed_variants) ? json_encode($allowed_variants) : null;
    
    // Start of new inputs
    $type = $_POST['type'] ?? 'menu';
    $active_days = $_POST['active_days'] ?? [];
    
    // Process active_days (similar to allowed_variants)
    if (is_string($active_days)) {
        $decoded_days = json_decode($active_days, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $active_days = $decoded_days;
        } else {
            $active_days = [];
        }
    }
    $active_days_json = !empty($active_days) ? json_encode($active_days) : null;
    
    if (!$name) {
        json_response(['error' => 'Category name required'], 400);
    }
    
    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, display_order = ?, allowed_variants = ?, show_measurements = ?, type = ?, active_days = ? WHERE id = ?");
            $stmt->execute([$name, $display_order, $allowed_variants_json, $show_measurements, $type, $active_days_json, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, display_order, allowed_variants, show_measurements, type, active_days) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $display_order, $allowed_variants_json, $show_measurements, $type, $active_days_json]);
        }
    
    json_response(['success' => true]);
} catch (PDOException $e) {
    json_response(['error' => $e->getMessage()], 500);
}
