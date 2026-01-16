<?php
// Temporary debug endpoint — remove after testing.
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

require __DIR__ . '/src/db.php';

echo "DB debug\n\n";

try {
    // Check PDO driver availability
    if (!extension_loaded('pdo')) {
        echo "PDO extension not loaded\n";
    } elseif (!in_array('mysql', PDO::getAvailableDrivers())) {
        echo "PDO MySQL driver not available\n";
    } else {
        echo "PDO MySQL driver present\n";
    }

    $pdo = getDbConnection();
    echo "Connected: OK\n\n";

    $tables = ['Forum', 'Orders', 'Support'];
    $extra = ['Posts', 'Comments'];
    foreach ($tables as $t) {
        try {
            $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            echo "$t rows: $cnt\n";
            $rows = $pdo->query("SELECT * FROM `$t` ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            echo "Latest $t:\n";
            foreach ($rows as $r) {
                echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
            }
            echo "\n";
        } catch (Exception $e) {
            echo "Error querying $t: " . $e->getMessage() . "\n\n";
        }
    }

    // Also check Posts and Comments tables
    foreach ($extra as $t) {
        try {
            $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            echo "$t rows: $cnt\n";
        } catch (Exception $e) {
            echo "Error querying $t: " . $e->getMessage() . "\n";
        }
    }

    // Quick check: list PDO drivers
    echo "Available PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
} catch (Exception $e) {
    echo "DB connect error: " . $e->getMessage() . "\n";
}

echo "\nDelete this file after testing.\n";
// end of debug file