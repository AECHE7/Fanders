-- Fanders Microfinance Loaning Management System - Phase 1 Schema
-- This schema includes all tables defined in the project specification.

-- Table: users (Staff Management)
CREATE TABLE IF NOT EXISTS users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    last_login TIMESTAMP NULL,
    phone_number VARCHAR(20) UNIQUE,
    password_changed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: clients (Borrower Management)
CREATE TABLE IF NOT EXISTS clients (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    address VARCHAR(255) NOT NULL,
    identification_type VARCHAR(50),
    identification_number VARCHAR(100) UNIQUE,
    date_of_birth DATE,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: loans (Core Loan Data)
CREATE TABLE IF NOT EXISTS loans (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    client_id INT(11) NOT NULL,
    principal DECIMAL(10, 2) NOT NULL,
    interest_rate DECIMAL(5, 4) NOT NULL,
    term_weeks INT(3) NOT NULL,
    total_interest DECIMAL(10, 2) NOT NULL,
    insurance_fee DECIMAL(10, 2) NOT NULL,
    total_loan_amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Application',
    application_date TIMESTAMP NOT NULL,
    approval_date TIMESTAMP NULL,
    disbursement_date TIMESTAMP NULL,
    completion_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Table: payments (Transactional Data - Weekly Payments)
CREATE TABLE IF NOT EXISTS payments (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    loan_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Table: cash_blotter (Phase 2 - Cash Flow Management)
CREATE TABLE IF NOT EXISTS cash_blotter (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    blotter_date DATE UNIQUE NOT NULL,
    total_inflow DECIMAL(10, 2) DEFAULT 0.00,
    total_outflow DECIMAL(10, 2) DEFAULT 0.00,
    calculated_balance DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: transactions (Phase 2 - Audit Log)
CREATE TABLE IF NOT EXISTS transactions (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    reference_id INT(11) NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
