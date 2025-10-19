<?php
/**
 * Test database connection for Railway deployment
 */

require_once 'app/config/config.php';

try {
    $dsn = DB_TYPE . ':host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Test query
    $stmt = $pdo->query("SELECT version()");
    $result = $stmt->fetch();

    echo "✅ Database connection successful!\n";
    echo "PostgreSQL Version: " . $result['version'] . "\n";

    // Test if tables exist
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "\n📋 Available tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
