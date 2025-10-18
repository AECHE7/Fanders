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

try {
    // Check if phone_number column exists
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone_number'");
    $phoneExists = $result->rowCount() > 0;

    if (!$phoneExists) {
        echo "Adding phone_number column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN phone_number VARCHAR(20) UNIQUE AFTER email");
        echo "phone_number column added successfully.\n";
    } else {
        echo "phone_number column already exists.\n";
    }

    // Check if password_changed_at column exists
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_changed_at'");
    $passwordChangedExists = $result->rowCount() > 0;

    if (!$passwordChangedExists) {
        echo "Adding password_changed_at column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN password_changed_at TIMESTAMP NULL AFTER status");
        echo "password_changed_at column added successfully.\n";
    } else {
        echo "password_changed_at column already exists.\n";
    }

    echo "Database schema update completed.\n";

} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>
