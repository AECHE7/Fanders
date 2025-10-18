<?php
$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: 'fanders';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
try {
    $dsn = "mysql:host={$host};port={$port};dbname={$name}";
    $pdo = new PDO($dsn, $user, $pass);
    echo "connected\n";
} catch (PDOException $e) {
    echo "connect error: " . $e->getMessage() . "\n";
}
