<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

require_login();

echo "Size Definitions:\n";
$stmt = $pdo->query("SELECT * FROM size_definitions");
$sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($sizes);

echo "\nCategories:\n";
$stmt = $pdo->query("SELECT * FROM categories");
$cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($cats);
