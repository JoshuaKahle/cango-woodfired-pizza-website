<?php
/**
 * Shared Helper Functions
 */

$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $is_https,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf_api() {
    $token = '';
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) $token = (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
    if ($token === '' && isset($_POST['csrf_token'])) $token = (string)$_POST['csrf_token'];

    if ($token === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $token)) {
        json_response(['error' => 'Invalid CSRF token'], 403);
    }
}

/**
 * Check if the user is logged in as admin.
 * Redirects to login page if not.
 */
function require_login() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

function require_login_api() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        json_response(['error' => 'Not authenticated'], 401);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        require_csrf_api();
    }
}

/**
 * Sanitize output for HTML display
 */
function h($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function format_rand($value) {
    if ($value === null) return '';
    if (is_string($value)) {
        $value = trim($value);
        if ($value === '') return '';
    }
    if (!is_numeric($value)) return (string)$value;
    $num = (float)$value;
    $rounded = (int)round($num);
    return (string)$rounded;
}

/**
 * Get all categories with their menu items
 */
function get_full_menu($pdo, $type = 'menu', $include_inactive = false) {
    // 1. Fetch Size Definitions
    $stmt = $pdo->query("SELECT * FROM size_definitions ORDER BY id ASC");
    $sizeDefinitions = $stmt->fetchAll(PDO::FETCH_ASSOC); // Global sizes

    // 2. Fetch Categories
    // 2. Fetch Categories by Type
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE type = ? ORDER BY display_order ASC");
    $stmt->execute([$type]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch All Items
    if ($include_inactive) {
        $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY id ASC");
    } else {
        $stmt = $pdo->query("SELECT * FROM menu_items WHERE is_active = 1 ORDER BY id ASC");
    }
    $allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch All Variants
    // FETCH_GROUP groups by the first column (menu_item_id)
    $stmt = $pdo->query("SELECT menu_item_id, size_id, price FROM item_variants ORDER BY price ASC");
    $allVariants = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

    // 5. Assemble
    $menu = [];
    foreach ($categories as $cat) {
        $cat['items'] = [];
        // Decode allowed_variants JSON
        $cat['allowed_variants'] = !empty($cat['allowed_variants']) ? json_decode($cat['allowed_variants'], true) : [];
        $cat['active_days'] = !empty($cat['active_days']) ? json_decode($cat['active_days'], true) : [];
        
        foreach ($allItems as $item) {
            if ($item['category_id'] == $cat['id']) {
                // Attach variants
                $item['variants'] = $allVariants[$item['id']] ?? [];
                
                // Helper: Min Price for sorting/display
                $prices = array_column($item['variants'], 'price');
                $item['min_price'] = !empty($prices) ? min($prices) : 0;
                
                $cat['items'][] = $item;
            }
        }
        $menu[] = $cat;
    }
    
    // Return both menu structure and global definitions
    // NOTE: Previous calls expected just array of categories. 
    // We should probably keep returning the array of categories (with extra data embedded) 
    // OR change the return signature. 
    // Use a hack: Attach size_definitions as a special key or rely on globals?
    // Better: Attach to every category? No, wasteful.
    // Changing return type might break 'menu.php' loop `foreach ($menu as $category)`.
    // Let's attach 'all_sizes' to the first category? Hacky.
    // Actually, dashboard and menu.php just iterate. 
    // Let's just return the categories array, but maybe we need a separate function for sizes?
    // OR we can rely on fetching sizes separately in the view if needed.
    // BUT the prompt wants "Update backend logic".
    // I'll make a new function `get_size_definitions($pdo)` and call it where needed.
    // AND update `get_full_menu` to handle the `allowed_variants` decoding.
    
    return $menu;
}

function get_size_definitions($pdo) {
    if (!$pdo) return [];
    $stmt = $pdo->query("SELECT * FROM size_definitions ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * JSON Response Helper
 */
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode($data);
    exit;
}

function ensure_settings_table($pdo) {
    static $initialized = false;
    if ($initialized) return;
    if (!$pdo) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(100) NOT NULL PRIMARY KEY, setting_value TEXT NULL)");

    // If the table existed previously with a different schema, ensure required columns exist.
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_ASSOC);
        $colNames = [];
        foreach ($cols as $c) {
            if (!empty($c['Field'])) $colNames[] = $c['Field'];
        }

        // Migrate legacy schemas if present.
        if (!in_array('setting_key', $colNames) && in_array('key', $colNames)) {
            $pdo->exec("ALTER TABLE settings CHANGE COLUMN `key` setting_key VARCHAR(100) NOT NULL");
            $colNames[] = 'setting_key';
        }
        if (!in_array('setting_key', $colNames) && in_array('key_name', $colNames)) {
            $pdo->exec("ALTER TABLE settings CHANGE COLUMN `key_name` setting_key VARCHAR(100) NOT NULL");
            $colNames[] = 'setting_key';
        }
        if (!in_array('setting_value', $colNames) && in_array('value', $colNames)) {
            $pdo->exec("ALTER TABLE settings CHANGE COLUMN `value` setting_value TEXT NULL");
            $colNames[] = 'setting_value';
        }
        if (!in_array('setting_value', $colNames) && in_array('value_name', $colNames)) {
            $pdo->exec("ALTER TABLE settings CHANGE COLUMN `value_name` setting_value TEXT NULL");
            $colNames[] = 'setting_value';
        }

        // If a legacy NOT NULL key column still exists alongside the new one, drop it so inserts don't fail.
        if (in_array('setting_key', $colNames) && in_array('key_name', $colNames)) {
            try {
                $pdo->exec("ALTER TABLE settings DROP COLUMN `key_name`");
            } catch (PDOException $e) {
                // ignore
            }
        }

        if (!in_array('setting_key', $colNames)) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN setting_key VARCHAR(100) NOT NULL FIRST");
        }
        if (!in_array('setting_value', $colNames)) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN setting_value TEXT NULL");
        }

        // Ensure primary key exists on setting_key
        $hasPrimary = false;
        foreach ($cols as $c) {
            if (!empty($c['Key']) && $c['Key'] === 'PRI' && !empty($c['Field']) && $c['Field'] === 'setting_key') {
                $hasPrimary = true;
                break;
            }
        }
        if (!$hasPrimary) {
            // If there's an existing primary key, this will fail; ignore and proceed.
            try {
                $pdo->exec("ALTER TABLE settings ADD PRIMARY KEY (setting_key)");
            } catch (PDOException $e) {
                // ignore
            }
        }
    } catch (PDOException $e) {
        // ignore
    }
    $initialized = true;
}

function get_setting($pdo, $key, $default = null) {
    if (!$pdo) return $default;
    ensure_settings_table($pdo);

    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();

    if ($val === false || $val === null) return $default;
    return $val;
}

function set_setting($pdo, $key, $value) {
    if (!$pdo) return false;
    ensure_settings_table($pdo);

    // Safe upsert without relying on UPDATE rowCount semantics.
    $existsStmt = $pdo->prepare("SELECT 1 FROM settings WHERE setting_key = ? LIMIT 1");
    $existsStmt->execute([$key]);
    $exists = ($existsStmt->fetchColumn() !== false);

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        return $stmt->execute([$value, $key]);
    }

    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
    return $stmt->execute([$key, $value]);
}
