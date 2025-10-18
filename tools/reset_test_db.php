<?php
require __DIR__ . '/../app/config/config.php';
require __DIR__ . '/../app/core/Database.php';

$db = Database::getInstance()->getConnection();
try {
    $tables = ['transactions', 'payments', 'loans', 'clients', 'users'];
    foreach ($tables as $t) {
        $db->exec("SET FOREIGN_KEY_CHECKS=0; TRUNCATE TABLE {$t}; SET FOREIGN_KEY_CHECKS=1;");
        echo "Truncated {$t}\n";
    }
} catch (Exception $e) {
    echo "Failed to reset test DB: " . $e->getMessage() . PHP_EOL;
}

?>
