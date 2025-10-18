<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    $table = isset($argv[1]) ? $argv[1] : 'users';
    echo "\n{$table} table structure:\n";
    $result = $pdo->query("DESCRIBE {$table}");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
