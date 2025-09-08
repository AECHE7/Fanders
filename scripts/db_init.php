<?php
/**
 * Database initialization script for the Library Management System
 * This script creates all required tables for the application
 */

// Include configuration
require_once '../app/config/config.php';

// Include database class
require_once '../app/core/Database.php';

// Include password hasher
require_once '../app/utilities/PasswordHash.php';

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

echo "Starting database initialization...\n";

// Define SQL for creating users table
$createUsersTable = "
    CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        role_id INTEGER NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
";

// Define SQL for creating categories table
$createCategoriesTable = "
    CREATE TABLE IF NOT EXISTS categories (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
";

// Define SQL for creating books table
$createBooksTable = "
    CREATE TABLE IF NOT EXISTS books (
        id SERIAL PRIMARY KEY,
        isbn VARCHAR(20) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(100) NOT NULL,
        category_id INTEGER REFERENCES categories(id),
        publisher VARCHAR(100),
        publication_year INTEGER,
        edition VARCHAR(50),
        pages INTEGER,
        quantity INTEGER NOT NULL DEFAULT 1,
        available_quantity INTEGER NOT NULL DEFAULT 1,
        shelf_location VARCHAR(50),
        description TEXT,
        cover_image VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
";

// Define SQL for creating transactions table
$createTransactionsTable = "
    CREATE TABLE IF NOT EXISTS transactions (
        id SERIAL PRIMARY KEY,
        borrower_id INTEGER NOT NULL REFERENCES users(id),
        book_id INTEGER NOT NULL REFERENCES books(id),
        borrow_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        due_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        return_date TIMESTAMP NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'borrowed',
        issued_by INTEGER NOT NULL REFERENCES users(id),
        received_by INTEGER REFERENCES users(id),
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
";

// Define SQL for creating penalties table
$createPenaltiesTable = "
    CREATE TABLE IF NOT EXISTS penalties (
        id SERIAL PRIMARY KEY,
        transaction_id INTEGER NOT NULL REFERENCES transactions(id),
        amount DECIMAL(10, 2) NOT NULL,
        payment_date TIMESTAMP NULL,
        payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
        received_by INTEGER REFERENCES users(id),
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
";

// Begin transaction
try {
    $pdo->beginTransaction();
    
    // Create tables
    $pdo->exec($createUsersTable);
    echo "Users table created successfully\n";
    
    $pdo->exec($createCategoriesTable);
    echo "Categories table created successfully\n";
    
    $pdo->exec($createBooksTable);
    echo "Books table created successfully\n";
    
    $pdo->exec($createTransactionsTable);
    echo "Transactions table created successfully\n";
    
    $pdo->exec($createPenaltiesTable);
    echo "Penalties table created successfully\n";
    
    // Create default superadmin user
    $hasher = new PasswordHash();
    $defaultPassword = $hasher->hash('admin123');
    
    $createSuperAdmin = "
        INSERT IGNORE INTO users (username, password, first_name, last_name, email, role_id) 
        VALUES ('admin', :password, 'System', 'Administrator', 'admin@library.com', :role_id)
    ";
    
    $stmt = $pdo->prepare($createSuperAdmin);
    $stmt->execute([
        ':password' => $defaultPassword,
        ':role_id' => ROLE_SUPER_ADMIN
    ]);
    
    echo "Default superadmin user created successfully\n";
    
    // Create default categories
    $defaultCategories = [
        ['Fiction', 'Novels, short stories, and other fictional works'],
        ['Non-Fiction', 'Factual books, biographies, and academic texts'],
        ['Science', 'Books related to various scientific disciplines'],
        ['Technology', 'Books about computers, programming, and technology'],
        ['History', 'Historical accounts and analyses'],
        ['Philosophy', 'Philosophical texts and discussions'],
        ['Arts', 'Books about visual arts, music, and other creative fields'],
        ['Reference', 'Dictionaries, encyclopedias, and other reference materials']
    ];
    
    $insertCategory = "INSERT IGNORE INTO categories (name, description) VALUES (:name, :description)";
    $stmt = $pdo->prepare($insertCategory);
    
    foreach ($defaultCategories as $category) {
        $stmt->execute([
            ':name' => $category[0],
            ':description' => $category[1]
        ]);
    }
    
    echo "Default categories created successfully\n";
    
    // Commit transaction
    $pdo->commit();
    echo "Database initialization completed successfully!\n";
    echo "You can now login with username 'admin' and password 'admin123'\n";
    
} catch (PDOException $e) {
    // Rollback transaction on error if transaction is active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Database initialization failed: " . $e->getMessage() . "\n";
}
