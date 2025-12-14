<?php
require_once __DIR__ . '/public_html/config/db.php';

try {
    echo "Starting migration...\n";

    // 1. Create size_definitions table
    $sql = "CREATE TABLE IF NOT EXISTS size_definitions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        measurement VARCHAR(50) NOT NULL
    )";
    $pdo->exec($sql);
    echo "Created size_definitions table.\n";

    // 2. Seed data
    $stmt = $pdo->prepare("INSERT IGNORE INTO size_definitions (name, measurement) VALUES (?, ?)");
    $definitions = [
        ['Small', '20cm'],
        ['Medium', '30cm'],
        ['Large', '40cm']
    ];
    foreach ($definitions as $def) {
        $stmt->execute($def);
    }
    echo "Seeded size_definitions.\n";

    // 3. Alter categories table
    // Check if column exists first to avoid error on repeated runs (primitive check)
    $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'allowed_variants'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN allowed_variants JSON DEFAULT NULL");
        echo "Added allowed_variants column to categories.\n";
    } else {
        echo "Column allowed_variants already exists.\n";
    }

    echo "Migration complete.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
