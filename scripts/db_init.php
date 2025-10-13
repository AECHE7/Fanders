<?php
/**
 * Database initialization script for the LibraryVault System
 * This script creates all required tables per the new specification and populates with sample data
 */

// Include configuration
require_once '../app/config/config.php';

// Include database class
require_once '../app/core/Database.php';

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

echo "Starting database initialization...\n";

// USERS TABLE
$createUsersTable = "
    CREATE TABLE IF NOT EXISTS users (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone_number VARCHAR(20) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
";

// BOOK_CATEGORIES TABLE
$createBookCategoriesTable = "
    CREATE TABLE IF NOT EXISTS book_categories (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB;
";

// BOOKS TABLE
$createBooksTable = "
    CREATE TABLE IF NOT EXISTS books (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(255) NOT NULL,
        category_id INT(11) UNSIGNED,
        published_year YEAR,
        total_copies INT(20) NOT NULL,
        available_copies INT(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES book_categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;
";

// TRANSACTION TABLE
$createTransactionTable = "
    CREATE TABLE IF NOT EXISTS transaction (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED,
        book_id INT(11) UNSIGNED,
        borrow_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        due_date TIMESTAMP NULL,
        return_date TIMESTAMP NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'borrowed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
";

// PENALTIES TABLE
$createPenaltiesTable = "
    CREATE TABLE IF NOT EXISTS penalties (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED,
        transaction_id INT(11) UNSIGNED,
        penalty_amount DECIMAL(10, 2) NOT NULL,
        penalty_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (transaction_id) REFERENCES transaction(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
";

// Begin transaction
try {
    $pdo->beginTransaction();
    
    // Create tables
    $pdo->exec($createUsersTable);
    echo "Users table created successfully\n";
    
    $pdo->exec($createBookCategoriesTable);
    echo "Book categories table created successfully\n";
    
    $pdo->exec($createBooksTable);
    echo "Books table created successfully\n";
    
    $pdo->exec($createTransactionTable);
    echo "Transaction table created successfully\n";
    
    $pdo->exec($createPenaltiesTable);
    echo "Penalties table created successfully\n";
    
    // Insert initial data
    // 1. Book Categories
    $defaultCategories = [
        'Fiction', 'Non-Fiction', 'Science', 'Technology', 'History', 'Philosophy', 'Arts', 'Reference'
    ];
    
    $insertCategory = $pdo->prepare("INSERT INTO book_categories (category_name) VALUES (:category_name)");
    
    foreach ($defaultCategories as $category) {
        $insertCategory->execute([':category_name' => $category]);
    }
    
    echo "Default book categories inserted successfully\n";
    
    // 2. Create default admin user
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    
    $insertUser = $pdo->prepare("
        INSERT INTO users (name, email, phone_number, password, role, status)
        VALUES (:name, :email, :phone_number, :password, :role, :status)
    ");
    
    $insertUser->execute([
        ':name' => 'Super Admin',
        ':email' => 'admin@library.com',
        ':phone_number' => '09170000000',
        ':password' => $adminPass,
        ':role' => 'super-admin',
        ':status' => 'active'
    ]);
    
    echo "Default super-admin user created successfully\n";
    
    // 3. Insert sample book if not exists
    $catIdRes = $pdo->query("SELECT id FROM book_categories WHERE category_name='Fiction' LIMIT 1");
    $fictionCatId = $catIdRes->fetchColumn();
    
    $bookTitle = 'Sample Book';
    $bookCheck = $pdo->prepare("SELECT id FROM books WHERE title = :title LIMIT 1");
    $bookCheck->execute([':title' => $bookTitle]);
    
    if (!$bookCheck->fetchColumn()) {
        $insertBook = $pdo->prepare("
            INSERT INTO books (title, author, category_id, published_year, total_copies, available_copies)
            VALUES (:title, :author, :category_id, :published_year, :total_copies, :available_copies)
        ");
        
        $insertBook->execute([
            ':title' => $bookTitle,
            ':author' => 'John Doe',
            ':category_id' => $fictionCatId,
            ':published_year' => 2022,
            ':total_copies' => 10,
            ':available_copies' => 10
        ]);
        
        echo "Sample book inserted successfully\n";
    } else {
        echo "Sample book already exists\n";
    }
    
    // 4. Insert sample transaction if not exists
    $adminEmail = 'admin@library.com';
    $userIdRes = $pdo->query("SELECT id FROM users WHERE email='$adminEmail' LIMIT 1");
    $userId = $userIdRes->fetchColumn();
    
    $bookIdRes = $pdo->query("SELECT id FROM books WHERE title='$bookTitle' LIMIT 1");
    $bookId = $bookIdRes->fetchColumn();
    
    if ($userId && $bookId) {
        $transCheck = $pdo->prepare("SELECT id FROM transaction WHERE user_id=:user_id AND book_id=:book_id LIMIT 1");
        $transCheck->execute([':user_id' => $userId, ':book_id' => $bookId]);
        
        if (!$transCheck->fetchColumn()) {
            $insertTrans = $pdo->prepare("
                INSERT INTO transaction (user_id, book_id, borrow_date, due_date, status)
                VALUES (:user_id, :book_id, :borrow_date, :due_date, :status)
            ");
            
            $borrowDate = date('Y-m-d H:i:s');
            $dueDate = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            $insertTrans->execute([
                ':user_id' => $userId,
                ':book_id' => $bookId,
                ':borrow_date' => $borrowDate,
                ':due_date' => $dueDate,
                ':status' => 'borrowed'
            ]);
            
            echo "Sample transaction inserted successfully\n";
        } else {
            echo "Sample transaction already exists for this user and book\n";
        }
    } else {
        echo "Warning: Could not create sample transaction - user or book not found\n";
    }
    
    // 5. Insert sample penalty if not exists
    if ($userId && $bookId) {
        $transIdRes = $pdo->query("SELECT id FROM transaction WHERE user_id=$userId AND book_id=$bookId LIMIT 1");
        $transId = $transIdRes->fetchColumn();
        
        if ($transId) {
            $penaltyCheck = $pdo->prepare("SELECT id FROM penalties WHERE user_id=:user_id AND transaction_id=:transaction_id LIMIT 1");
            $penaltyCheck->execute([':user_id' => $userId, ':transaction_id' => $transId]);
            
            if (!$penaltyCheck->fetchColumn()) {
                $insertPenalty = $pdo->prepare("
                    INSERT INTO penalties (user_id, transaction_id, penalty_amount, penalty_date)
                    VALUES (:user_id, :transaction_id, :penalty_amount, :penalty_date)
                ");
                
                $insertPenalty->execute([
                    ':user_id' => $userId,
                    ':transaction_id' => $transId,
                    ':penalty_amount' => 25.00,
                    ':penalty_date' => date('Y-m-d H:i:s')
                ]);
                
                echo "Sample penalty inserted successfully\n";
            } else {
                echo "Sample penalty already exists for this user/transaction\n";
            }
        } else {
            echo "Warning: Could not create sample penalty - transaction not found\n";
        }
    }
    
    // Commit transaction
    $pdo->commit();
    echo "Database initialization completed successfully!\n";
    echo "You can now login with email 'admin@library.com' and password 'admin123'\n";
    
} catch (PDOException $e) {
    // Rollback transaction on error if transaction is active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Database initialization failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    
    // Additional debug information
    if ($e->getCode() == '42S01') {
        echo "Hint: Table already exists. If you want to recreate tables, drop them first.\n";
    } elseif ($e->getCode() == '23000') {
        echo "Hint: Duplicate entry or constraint violation. Check if data already exists.\n";
    }
}

// Final status message
echo "\nScript execution completed.\n";
