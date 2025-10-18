<?php
require __DIR__ . '/../app/config/config.php';
require __DIR__ . '/../app/models/UserModel.php';

$m = new UserModel();
$data = [
    'name' => 'Debug User',
    'email' => 'debug.user@example.com',
    'phone_number' => '09123459999',
    'password' => 'debugpass123',
    'password_confirmation' => 'debugpass123',
    'role' => 'cashier'
];

$res = $m->create($data);
var_dump($res);
echo "LastError: " . ($m->getLastError() ?: '(none)') . PHP_EOL;

// Also check if users table exists and show row count
try {
    $count = $m->count();
    echo "Users table count: " . ($count ?? 'unknown') . PHP_EOL;
} catch (Exception $e) {
    echo "DB check error: " . $e->getMessage() . PHP_EOL;
}

?>
