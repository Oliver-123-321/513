<?php
// Temporary debug for support.php — remove after testing.
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

echo "support_debug start\n\n";

$checks = [];

// check files existence
$paths = [
    'utils' => __DIR__ . '/src/utils.php',
    'storage' => __DIR__ . '/src/storage.php',
    'db' => __DIR__ . '/src/db.php',
    'header' => __DIR__ . '/partials/header.php',
];
foreach ($paths as $k => $p) {
    $checks[$k] = file_exists($p) ? "ok" : "missing ($p)";
    echo "check $k: {$checks[$k]}\n";
}

// try includes individually and report errors
try {
    echo "\nIncluding utils.php... ";
    require_once __DIR__ . '/src/utils.php';
    echo "done\n";
} catch (Throwable $e) {
    echo "error: " . $e->getMessage() . "\n";
}

try {
    echo "Including storage.php... ";
    require_once __DIR__ . '/src/storage.php';
    echo "done\n";
} catch (Throwable $e) {
    echo "error: " . $e->getMessage() . "\n";
}

try {
    echo "Including db.php... ";
    require_once __DIR__ . '/src/db.php';
    echo "done\n";
} catch (Throwable $e) {
    echo "error: " . $e->getMessage() . "\n";
}

// session test
try {
    echo "\nStarting session test... ";
    session_start();
    echo "session id: " . session_id() . "\n";
} catch (Throwable $e) {
    echo "session error: " . $e->getMessage() . "\n";
}

// DB connection test
try {
    echo "\nTesting DB connection...\n";
    if (function_exists('getDbConnection')) {
        $pdo = getDbConnection();
        echo "getDbConnection OK\n";
        $cnt = $pdo->query('SELECT 1')->fetchColumn();
        echo "Simple query OK: $cnt\n";
    } else {
        echo "getDbConnection() not defined\n";
    }
} catch (Throwable $e) {
    echo "DB error: " . $e->getMessage() . "\n";
}

echo "\nNow trying to include partial header...\n";
try {
    include __DIR__ . '/partials/header.php';
    echo "Included header.php OK\n";
} catch (Throwable $e) {
    echo "Header include error: " . $e->getMessage() . "\n";
}

echo "\nDone. Remove this file after debugging.\n";


