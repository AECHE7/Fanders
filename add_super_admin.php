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
    'name' => 'Super Admin',
    'email' => 'superadmin@fanders.com',
    'phone_number' => '09123456988',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'role' => 'super-admin',
    'status' => 'active'
];

echo "Adding Super Admin account...\n";
$result = $userService->addUser($userData); // No creator specified - should use fallback logic

if ($result) {
    echo "SUCCESS: Super Admin added with ID: $result\n";
    echo "Credentials:\n";
    echo "- Email: superadmin@fanders.com\n";
    echo "- Password: password123\n";
    echo "- Phone: 09123456988\n";
} else {
    echo "FAILED: " . $userService->getErrorMessage() . "\n";
}
?>
