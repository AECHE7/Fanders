-- Fanders Microfinance Loaning Management System - Phase 1 Schema
-- This schema includes all tables defined in the project specification.

-- Table: users (Staff Management)
CREATE TABLE users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL, -- Admin, Manager, Cashier, AO (Account Officer)
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- active, inactive
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: clients (Borrower Management)
CREATE TABLE clients (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    address VARCHAR(255) NOT NULL,
    identification_type VARCHAR(50), -- e.g., National ID, Driver's License
    identification_number VARCHAR(100) UNIQUE,
    date_of_birth DATE,
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- active, inactive, blacklisted
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: loans (Core Loan Data)
CREATE TABLE loans (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    client_id INT(11) NOT NULL,
    principal DECIMAL(10, 2) NOT NULL,
    interest_rate DECIMAL(5, 4) NOT NULL, -- Fixed 0.05 (5%)
    term_weeks INT(3) NOT NULL, -- Fixed 17 weeks
    total_interest DECIMAL(10, 2) NOT NULL,
    insurance_fee DECIMAL(10, 2) NOT NULL, -- Fixed 425.00
    total_loan_amount DECIMAL(10, 2) NOT NULL, -- Principal + Interest + Fee
    status VARCHAR(20) NOT NULL DEFAULT 'Application', -- Application, Approved, Active, Completed, Defaulted
    application_date TIMESTAMP NOT NULL,
    approval_date TIMESTAMP NULL,
    disbursement_date TIMESTAMP NULL, -- When funds are released (loan becomes ACTIVE)
    completion_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Table: payments (Transactional Data - Weekly Payments)
CREATE TABLE payments (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    loan_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL, -- Cashier/AO who recorded payment
    amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Table: cash_blotter (Phase 2 - Cash Flow Management)
CREATE TABLE cash_blotter (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    blotter_date DATE UNIQUE NOT NULL,
    total_inflow DECIMAL(10, 2) DEFAULT 0.00,
    total_outflow DECIMAL(10, 2) DEFAULT 0.00,
    calculated_balance DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: transactions (Phase 2 - Audit Log)
CREATE TABLE transactions (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    transaction_type VARCHAR(50) NOT NULL, -- e.g., PAYMENT_RECORDED, LOAN_CREATED, USER_UPDATED
    reference_id INT(11) NULL, -- ID of the affected record (loan, payment, client, etc.)
    details TEXT, -- JSON string of relevant information or changes made
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
