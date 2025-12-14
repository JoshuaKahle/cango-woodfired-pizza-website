<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (php_sapi_name() !== 'cli') {
    require_login();
}

// Check if 'Standard' exists
$stmt = $pdo->prepare("SELECT id FROM size_definitions WHERE name = ?");
$stmt->execute(['Standard']);
$standard = $stmt->fetch(PDO::FETCH_ASSOC);

if ($standard) {
    echo "Standard size exists with ID: " . $standard['id'] . "\n";
} else {
    echo "Creating Standard size...\n";
    $stmt = $pdo->prepare("INSERT INTO size_definitions (name, measurement) VALUES (?, ?)");
    $stmt->execute(['Standard', '']);
    echo "Created Standard size with ID: " . $pdo->lastInsertId() . "\n";
}
