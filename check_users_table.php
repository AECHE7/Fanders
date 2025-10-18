<?php
require 'app/config/config.php';

// Autoload classes
function autoload($className) {
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];

    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}
spl_autoload_register('autoload');

$db = Database::getInstance();
$pdo = $db->getConnection();

$result = $pdo->query('DESCRIBE users');
echo "Users table structure:\n";
foreach($result as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . ' - ' . $row['Default'] . "\n";
}
?>
