-- Sample Data for Library Management System

-- First, clear existing data to avoid conflicts
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE penalties;
TRUNCATE TABLE transaction;
TRUNCATE TABLE books;
TRUNCATE TABLE book_categories;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- Insert Categories
INSERT INTO book_categories (category_name) VALUES
('Fiction'),
('Non-Fiction'),
('Science'),
('History'),
('Technology'),
('Literature'),
('Biography'),
('Philosophy');

-- Insert Users
INSERT INTO users (name, email, phone_number, password, role, status, created_at, updated_at) VALUES
('Admin User', 'admin@library.com', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super-admin', 'active', NOW(), NOW()),
('Librarian Staff', 'librarian@library.com', '1234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NOW(), NOW()),
('John Doe', 'john.doe@email.com', '1234567892', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'students', 'active', NOW(), NOW()),
('Jane Smith', 'jane.smith@email.com', '1234567893', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active', NOW(), NOW()),
('Robert Johnson', 'robert.j@email.com', '1234567894', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'others', 'active', NOW(), NOW());

-- Insert Books
INSERT INTO books (title, author, category_id, published_year, total_copies, available_copies, created_at, updated_at) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 1, 1925, 5, 3, NOW(), NOW()),
('To Kill a Mockingbird', 'Harper Lee', 1, 1960, 4, 2, NOW(), NOW()),
('1984', 'George Orwell', 1, 1949, 3, 1, NOW(), NOW()),
('The Art of War', 'Sun Tzu', 2, -500, 2, 1, NOW(), NOW()),
('A Brief History of Time', 'Stephen Hawking', 3, 1988, 3, 2, NOW(), NOW()),
('The World Wars', 'Various Authors', 4, 2014, 2, 1, NOW(), NOW()),
('Clean Code', 'Robert C. Martin', 5, 2008, 4, 3, NOW(), NOW()),
('Pride and Prejudice', 'Jane Austen', 6, 1813, 3, 2, NOW(), NOW()),
('Steve Jobs', 'Walter Isaacson', 7, 2011, 2, 1, NOW(), NOW()),
('Meditations', 'Marcus Aurelius', 8, 180, 3, 2, NOW(), NOW()),
('The Old Man and the Sea', 'Ernest Hemingway', 1, 1952, 2, 0, NOW(), NOW()),
('The Republic', 'Plato', 8, -380, 2, 0, NOW(), NOW());

-- Insert Transactions
INSERT INTO transaction (user_id, book_id, borrow_date, due_date, return_date, status, created_at, updated_at) VALUES
(3, 1, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 9 DAY), NULL, 'borrowed', NOW(), NOW()),
(4, 2, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 11 DAY), NULL, 'borrowed', NOW(), NOW()),
(5, 3, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 7 DAY), NULL, 'borrowed', NOW(), NOW()),
(3, 4, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, 'overdue', NOW(), NOW()),
(4, 5, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 'returned', NOW(), NOW()),
(5, 6, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 4 DAY), NULL, 'borrowed', NOW(), NOW()),
(3, 7, DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY), 'returned', NOW(), NOW()),
(4, 8, DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_ADD(NOW(), INTERVAL 6 DAY), NULL, 'borrowed', NOW(), NOW());

-- Insert Penalties
INSERT INTO penalties (user_id, transaction_id, penalty_amount, penalty_date, created_at, updated_at) VALUES
(3, 4, 5.00, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW(), NOW()),
(4, 5, 3.00, DATE_SUB(NOW(), INTERVAL 6 DAY), NOW(), NOW()),
(5, 7, 2.00, DATE_SUB(NOW(), INTERVAL 11 DAY), NOW(), NOW());

-- Update book available copies based on transactions
UPDATE books SET available_copies = available_copies - 1 
WHERE id IN (SELECT book_id FROM transaction WHERE return_date IS NULL);

-- Archive some books
UPDATE books 
SET is_archived = 1, 
    archived_at = NOW(), 
    archive_reason = 'Outdated edition'
WHERE id IN (11, 12); 