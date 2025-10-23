<?php
/**
 * Simple migration script using PDO directly
 */

// Get database URL from environment
$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    die("ERROR: DATABASE_URL environment variable not set\n");
}

// Parse the database URL
$url = parse_url($databaseUrl);

$host = $url['host'] ?? 'localhost';
$port = $url['port'] ?? 5432;
$dbname = ltrim($url['path'], '/');
$user = $url['user'] ?? '';
$password = $url['pass'] ?? '';

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

echo "\n==============================================\n";
echo "TRANSACTION TABLE MIGRATION\n";
echo "==============================================\n\n";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ Connected to database: {$dbname}\n\n";
    
    // Check tables
    $stmt = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name IN ('transactions', 'transaction_logs')
        ORDER BY table_name
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Existing tables:\n";
    foreach ($tables as $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
        $count = $countStmt->fetchColumn();
        echo "  - {$table}: {$count} records\n";
    }
    echo "\n";
    
    // Drop transactions table if it exists
    if (in_array('transactions', $tables)) {
        echo "Dropping 'transactions' table...\n";
        $pdo->exec("DROP TABLE IF EXISTS transactions CASCADE");
        echo "✓ 'transactions' table dropped\n\n";
    } else {
        echo "✓ 'transactions' table does not exist (already removed)\n\n";
    }
    
    // Verify transaction_logs exists
    if (!in_array('transaction_logs', $tables)) {
        die("❌ ERROR: transaction_logs table does not exist!\n");
    }
    
    $logsCount = $pdo->query("SELECT COUNT(*) FROM transaction_logs")->fetchColumn();
    echo "✓ System using transaction_logs table ({$logsCount} records)\n\n";
    
    echo "✅ Migration complete!\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
