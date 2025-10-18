<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    $result = $pdo->query('SHOW TABLES');
    echo "Available tables:\n";
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        echo "- " . $row[0] . "\n";
    }

    // Check clients table structure
    echo "\nclients table structure:\n";
    $result = $pdo->query('DESCRIBE clients');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
