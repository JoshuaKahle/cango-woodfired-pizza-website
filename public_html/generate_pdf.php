<?php
$path = __DIR__ . '/assets/menu.pdf';

if (!is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Menu PDF is not available yet. Please ask the admin to generate it from the dashboard.";
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="cango_menu.pdf"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
