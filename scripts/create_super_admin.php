<?php
/**
 * Script to create a super-admin user for the Fanders Microfinance system
 * This script inserts a default super-admin user if one doesn't already exist
 */

// Include configuration
require_once 'app/config/config.php';

// Include database class
require_once 'app/core/Database.php';

// Include password hash class
require_once 'app/core/PasswordHash.php';

echo "Starting super-admin user creation...\n";

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Initialize password hash
$passwordHash = new PasswordHash();

// Super-admin credentials
$adminData = [
    'name' => 'Super Admin',
    'email' => 'admin@fandersmicrofinance.com',
    'phone_number' => '09123456789',
    'password' => 'admin123', // Will be hashed
    'role' => 'super-admin',
    'status' => 'active'
];

try {
    // Check if a super-admin already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE role = ? LIMIT 1");
    $checkStmt->execute(['super-admin']);
    $existingAdmin = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingAdmin) {
        echo "Super-admin user already exists (ID: {$existingAdmin['id']}). Skipping creation.\n";
        echo "You can login with email: {$adminData['email']} and password: {$adminData['password']}\n";
        exit(0);
    }

    // Hash the password
    $hashedPassword = $passwordHash->hash($adminData['password']);

    // Insert the super-admin user
    $insertStmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $insertStmt->execute([
        $adminData['name'],
        $adminData['email'],
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

} catch (PDOException $e) {
    echo "Error creating super-admin user: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    exit(1);
}

echo "\nScript execution completed.\n";
