<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

require_login_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Invalid Request'], 405);
}

ensure_settings_table($pdo);

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS size_definitions (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE, measurement VARCHAR(50) NOT NULL DEFAULT '')");
    $colStmt = $pdo->query("SHOW COLUMNS FROM size_definitions LIKE 'measurement'");
    if ($colStmt && $colStmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE size_definitions ADD COLUMN measurement VARCHAR(50) NOT NULL DEFAULT ''");
    }
} catch (PDOException $e) {
    json_response(['error' => $e->getMessage()], 500);
}

$measurements = $_POST['measurements'] ?? [];
$pdf_width_mm = trim($_POST['pdf_page_width_mm'] ?? '');
$pdf_height_mm = trim($_POST['pdf_page_height_mm'] ?? '');

try {
    $pdo->beginTransaction();

    if (is_array($measurements)) {
        $stmt = $pdo->prepare('UPDATE size_definitions SET measurement = ? WHERE id = ?');
        foreach ($measurements as $id => $measurement) {
            $stmt->execute([trim((string)$measurement), (int)$id]);
        }
    }

    if ($pdf_width_mm === '') $pdf_width_mm = '148';
    if ($pdf_height_mm === '') $pdf_height_mm = '230';

    if (!is_numeric($pdf_width_mm) || (float)$pdf_width_mm <= 0) {
        throw new Exception('PDF page width must be a number greater than 0');
    }
    if (!is_numeric($pdf_height_mm) || (float)$pdf_height_mm <= 0) {
        throw new Exception('PDF page height must be a number greater than 0');
    }

    set_setting($pdo, 'pdf_page_width_mm', (string)$pdf_width_mm);
    set_setting($pdo, 'pdf_page_height_mm', (string)$pdf_height_mm);

    $pdo->commit();
    json_response(['success' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['error' => $e->getMessage()], 500);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['error' => $e->getMessage()], 400);
}
