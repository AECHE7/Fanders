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
    'name' => 'Test Admin',
    'email' => 'admin@example.com',
    'phone_number' => '09123456788',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'role' => 'admin',
    'status' => 'active'
];

echo "Testing addUser functionality for admin role...\n";
$result = $userService->addUser($userData, 1); // Assuming super-admin ID is 1

if ($result) {
    echo "SUCCESS: Admin user added with ID: $result\n";
} else {
    echo "FAILED: " . $userService->getErrorMessage() . "\n";
}
