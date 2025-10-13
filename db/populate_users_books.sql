-- Sample data population script for LibraryVault
-- Populate USERS table

INSERT INTO users (name, email, phone_number, password, role, status, created_at, updated_at)
VALUES
('Alice Smith', 'alice@example.com', '55511122', '$2y$10$3T6DhjmVIBhxKdZA5FYi1O6JoIsoKLFVtxI6plc3l0AcmEXzPZ9jy', 'super-admin', 'active', NOW(), NOW()), -- password: password123
('Bob Jones', 'bob@example.com', '55522233', '$2y$10$d9Ya2JQn/aXZEK3OBQKJvOZcFIc4AuQipjStKQJwzEK3tYqztQ7Im', 'admin', 'active', NOW(), NOW()), -- password: secret321
('Carla Student', 'carla.student@example.com', '55533344', '$2y$10$Ksqjqf2DK5cPzXk4Vx90kuxMmTxOlA.A6XW/H0nEIz5rtbkEro4N2', 'students', 'active', NOW(), NOW()), -- password: carla2024
('David Staff', 'david.staff@example.com', '55544455', '$2y$10$KKQB8kIUc8zOu2EwEGTsoexB5VQ33X9N83wzpQAxqCkEA4j9Vl/J2', 'staff', 'active', NOW(), NOW()), -- password: staffpass
('Eve Other', 'eve.other@example.com', '55555566', '$2y$10$61vUZybxF/kl9ADJVKnt5.kMNWOezOGvx8D1HnN.OGME65vFIP9lu', 'others', 'inactive', NOW(), NOW()); -- password: user@2024

-- Populate BOOK_CATEGORIES table with some categories
INSERT INTO book_categories (category_name)
VALUES
('Science Fiction'),
('Computer Science'),
('Literature'),
('Mathematics'),
('History');

-- Populate BOOKS table with some books
INSERT INTO books (title, author, category_id, published_year, total_copies, available_copies, created_at, updated_at)
VALUES
('The Time Machine', 'H.G. Wells', 1, 1895, 5, 5, NOW(), NOW()),
('Clean Code', 'Robert C. Martin', 2, 2008, 3, 3, NOW(), NOW()),
('Hamlet', 'William Shakespeare', 3, 1603, 8, 8, NOW(), NOW()),
('Calculus: Early Transcendentals', 'James Stewart', 4, 2015, 6, 6, NOW(), NOW()),
('A Brief History of Time', 'Stephen Hawking', 5, 1998, 4, 4, NOW(), NOW());

-- Note: The password hashes are bcrypt and correspond to real passwords in comments.