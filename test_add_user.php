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

$userService = new UserService();

$userData = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'phone_number' => '09123456789',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'role' => 'cashier',
    'status' => 'active'
];

echo "Testing addUser functionality...\n";
$result = $userService->addUser($userData);

if ($result) {
    echo "SUCCESS: User added with ID: $result\n";
} else {
    echo "FAILED: " . $userService->getErrorMessage() . "\n";
}
?>
