<?php
/**
 * Script to fetch database schema from Supabase and populate users table with super-admin role
 */

// Include configuration
require_once 'app/config/config.php';

// Include database class
require_once 'app/core/Database.php';

// Include password hash class
require_once 'app/core/PasswordHash.php';

echo "Starting database schema fetch and super-admin creation...\n";

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Initialize password hash
$passwordHash = new PasswordHash();

try {
    // Fetch database schema
    echo "\n=== DATABASE SCHEMA ===\n";

    // Get all tables
    $tablesQuery = "
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
        ORDER BY table_name
    ";
    $tables = $db->resultSet($tablesQuery);

    if ($tables) {
        foreach ($tables as $table) {
            $tableName = $table['table_name'];
            echo "\nTable: {$tableName}\n";
            echo str_repeat("-", strlen("Table: {$tableName}") + 1) . "\n";

            // Get columns for this table
            $columnsQuery = "
                SELECT column_name, data_type, is_nullable, column_default
                FROM information_schema.columns
                WHERE table_schema = 'public' AND table_name = ?
                ORDER BY ordinal_position
            ";
            $columns = $db->resultSet($columnsQuery, [$tableName]);

            if ($columns) {
                foreach ($columns as $column) {
                    $nullable = $column['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL';
                    $default = $column['column_default'] ? " DEFAULT {$column['column_default']}" : '';
                    echo "  - {$column['column_name']} ({$column['data_type']}) {$nullable}{$default}\n";
                }
            } else {
                echo "  No columns found.\n";
            }
        }
    } else {
        echo "No tables found in the database.\n";
    }

    echo "\n=== SUPER-ADMIN CREATION ===\n";

    // Super-admin credentials
    $adminData = [
        'name' => 'Super Admin',
        'email' => 'admin@fandersmicrofinance.com',
        'phone_number' => '09123456789',
        'password' => 'admin123', // Will be hashed
        'role' => 'super-admin',
        'status' => 'active'
    ];

    // Check if a super-admin already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE role = ? LIMIT 1");
    $checkStmt->execute(['super-admin']);
    $existingAdmin = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingAdmin) {
        echo "Super-admin user already exists (ID: {$existingAdmin['id']}). Skipping creation.\n";
        echo "You can login with email: {$adminData['email']} and password: {$adminData['password']}\n";
    } else {
        // Hash the password
        $hashedPassword = $passwordHash->hash($adminData['password']);

        // Insert the super-admin user
        $insertStmt = $pdo->prepare("
            INSERT INTO users (name, email, phone_number, password, role, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $insertStmt->execute([
            $adminData['name'],
            $adminData['email'],
            $adminData['phone_number'],
            $hashedPassword,
            $adminData['role'],
            $adminData['status']
        ]);

        $newUserId = $pdo->lastInsertId();

        echo "Super-admin user created successfully!\n";
        echo "User ID: {$newUserId}\n";
        echo "Name: {$adminData['name']}\n";
        echo "Email: {$adminData['email']}\n";
        echo "Phone: {$adminData['phone_number']}\n";
        echo "Role: {$adminData['role']}\n";
        echo "Status: {$adminData['status']}\n";
        echo "\nYou can now login with:\n";
        echo "Email: {$adminData['email']}\n";
        echo "Password: {$adminData['password']}\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    exit(1);
}

echo "\nScript execution completed.\n";
