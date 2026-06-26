<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/dev/backend/Controllers/ContasReceberController.php';
    echo "No parse error in ContasReceberController.php\n";
} catch (\Throwable $e) {
    echo "Error parsing ContasReceberController.php: " . $e->getMessage() . "\n";
}

try {
    require_once __DIR__ . '/dev/backend/Routes/web.php';
    echo "No parse error in web.php\n";
} catch (\Throwable $e) {
    echo "Error parsing web.php: " . $e->getMessage() . "\n";
}

echo "Done.";
