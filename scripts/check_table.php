<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    $result = $pdo->query('DESCRIBE users');
    echo "Users table structure:\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
