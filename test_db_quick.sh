#!/bin/bash

echo "🔧 Creating Database Test Script"
echo "================================"

# Create a minimal PHP script that tests database connectivity
cat > test_db_connection.php << 'EOF'
<?php
// Minimal database test without loading full framework
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_DATABASE') ?: 'fanders';
$username = getenv('DB_USERNAME') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: '';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful\n";
    
    // Check if SLR tables exist
    $tables = ['slr_documents', 'slr_generation_rules', 'slr_access_log'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = '$table'");
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }
    
    // Check generation rules
    $stmt = $pdo->query("SELECT COUNT(*) FROM slr_generation_rules WHERE trigger_event = 'manual_request' AND is_active = true");
    $count = $stmt->fetchColumn();
    echo "📋 Active manual_request rules: $count\n";
    
    // Check completed loans
    $stmt = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'completed'");
    $count = $stmt->fetchColumn();
    echo "📊 Completed loans: $count\n";
    
    echo "\n🎯 Database status: Ready for SLR testing\n";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
EOF

echo "Running database connectivity test..."