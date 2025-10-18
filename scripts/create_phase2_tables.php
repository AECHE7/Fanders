<?php
/**
 * Database migration script for Phase 2 features
 * Creates tables for Transaction Audit Log and Cash Blotter
 */

// Include configuration and database class
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

// Initialize database connection
$db = Database::getInstance();

echo "Starting Phase 2 database migration...\n";

// Create transactions table for audit logging
$transactionsTable = "
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    reference_id INT NULL,
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_reference_id (reference_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

echo "Creating transactions table...\n";
if ($db->query($transactionsTable)) {
    echo "✓ Transactions table created successfully\n";
} else {
    echo "✗ Failed to create transactions table\n";
}

// Create cash_blotter table for daily cash flow tracking
$cashBlotterTable = "
CREATE TABLE IF NOT EXISTS cash_blotter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blotter_date DATE NOT NULL UNIQUE,
    total_inflow DECIMAL(15,2) DEFAULT 0.00,
    total_outflow DECIMAL(15,2) DEFAULT 0.00,
    calculated_balance DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_blotter_date (blotter_date),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

echo "Creating cash_blotter table...\n";
if ($db->query($cashBlotterTable)) {
    echo "✓ Cash blotter table created successfully\n";
} else {
    echo "✗ Failed to create cash blotter table\n";
}

// Insert initial transaction types for reference
$initialTransactions = [
    ['user_id' => 1, 'transaction_type' => 'SYSTEM_INIT', 'reference_id' => NULL, 'details' => '{"message": "Phase 2 database migration completed"}']
];

echo "Inserting initial transaction record...\n";
foreach ($initialTransactions as $transaction) {
    $db->query(
        "INSERT INTO transactions (user_id, transaction_type, reference_id, details) VALUES (?, ?, ?, ?)",
        [$transaction['user_id'], $transaction['transaction_type'], $transaction['reference_id'], json_encode($transaction['details'])]
    );
}

echo "✓ Initial transaction record inserted\n";

echo "\nPhase 2 database migration completed successfully!\n";
echo "\nTables created:\n";
echo "- transactions: Audit log for all system activities\n";
echo "- cash_blotter: Daily cash flow tracking\n";
echo "\nNext steps:\n";
echo "1. Run this script to create the tables\n";
echo "2. Update your application code to use the new services\n";
echo "3. Test the audit logging and cash blotter functionality\n";
