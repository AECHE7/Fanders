-- Fanders Microfinance Loaning Management System - Schema
-- This schema includes all tables defined in the project specification.
-- Updated to match PostgreSQL production schema

-- Table: users (Staff Management)
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL, -- Admin, Manager, Cashier, AO (Account Officer)
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- active, inactive
    last_login TIMESTAMP NULL,
    phone_number VARCHAR(20) UNIQUE NULL,
    password_changed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Table: clients (Borrower Management)
CREATE TABLE clients (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    address VARCHAR(255) NOT NULL,
    identification_type VARCHAR(50), -- e.g., National ID, Driver's License
    identification_number VARCHAR(100) UNIQUE,
    date_of_birth DATE,
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- active, inactive, blacklisted
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Table: loans (Core Loan Data)
CREATE TABLE loans (
    id SERIAL PRIMARY KEY,
    client_id INTEGER NOT NULL,
    principal DECIMAL(10, 2) NOT NULL,
    interest_rate DECIMAL(5, 4) NOT NULL, -- Fixed 0.05 (5%)
    term_weeks INTEGER NOT NULL, -- Fixed 17 weeks
    total_interest DECIMAL(10, 2) NOT NULL,
    insurance_fee DECIMAL(10, 2) NOT NULL, -- Fixed 425.00
    total_loan_amount DECIMAL(10, 2) NOT NULL, -- Principal + Interest + Fee
    status VARCHAR(20) NOT NULL DEFAULT 'Application', -- Application, Approved, Active, Completed, Defaulted
    application_date TIMESTAMP NOT NULL,
    approval_date TIMESTAMP NULL,
    disbursement_date TIMESTAMP NULL, -- When funds are released (loan becomes ACTIVE)
    completion_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT fk_loans_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_loans_client_id ON loans(client_id);

-- Table: payments (Transactional Data - Weekly Payments)
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    loan_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL, -- Cashier/AO who recorded payment
    amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT fk_payments_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_payments_loan_id ON payments(loan_id);

-- Table: cash_blotter (Cash Flow Management)
CREATE TABLE cash_blotter (
    id SERIAL PRIMARY KEY,
    blotter_date DATE UNIQUE NOT NULL,
    total_inflow DECIMAL(10, 2) DEFAULT 0.00,
    total_outflow DECIMAL(10, 2) DEFAULT 0.00,
    calculated_balance DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Table: transaction_logs (Audit Log)
CREATE TABLE transaction_logs (
    id SERIAL PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL, -- e.g., loan, payment, client, user
    entity_id INTEGER NULL, -- ID of the affected record
    action VARCHAR(50) NOT NULL, -- e.g., PAYMENT_RECORDED, LOAN_CREATED, USER_UPDATED
    user_id INTEGER NULL,
    details TEXT, -- JSON string of relevant information or changes made
    timestamp TIMESTAMP DEFAULT NOW(),
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT fk_tlogs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Table: system_backups (Backup Management)
CREATE TABLE system_backups (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'manual', -- full, incremental, scheduled, manual
    size BIGINT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, completed, failed
    cloud_url VARCHAR(500) NULL,
    created_by VARCHAR(100) NOT NULL DEFAULT 'system',
    created_at TIMESTAMP DEFAULT NOW(),
    last_restored_at TIMESTAMP NULL,
    restore_count INTEGER NOT NULL DEFAULT 0,
    notes TEXT NULL,
    CONSTRAINT system_backups_status_check CHECK (status IN ('pending', 'completed', 'failed')),
    CONSTRAINT system_backups_type_check CHECK (type IN ('full', 'incremental', 'scheduled', 'manual'))
);

CREATE INDEX IF NOT EXISTS idx_system_backups_type ON system_backups(type);
CREATE INDEX IF NOT EXISTS idx_system_backups_status ON system_backups(status);
CREATE INDEX IF NOT EXISTS idx_system_backups_created_at ON system_backups(created_at);
CREATE INDEX IF NOT EXISTS idx_system_backups_created_by ON system_backups(created_by);

-- Triggers for updated_at (PostgreSQL requires a function)
-- Note: This assumes you have a set_updated_at() function defined
-- CREATE OR REPLACE FUNCTION set_updated_at()
-- RETURNS TRIGGER AS $$
-- BEGIN
--     NEW.updated_at = NOW();
--     RETURN NEW;
-- END;
-- $$ LANGUAGE plpgsql;

-- CREATE TRIGGER users_set_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION set_updated_at();
-- CREATE TRIGGER clients_set_updated_at BEFORE UPDATE ON clients FOR EACH ROW EXECUTE FUNCTION set_updated_at();
-- CREATE TRIGGER loans_set_updated_at BEFORE UPDATE ON loans FOR EACH ROW EXECUTE FUNCTION set_updated_at();
-- CREATE TRIGGER cash_blotter_set_updated_at BEFORE UPDATE ON cash_blotter FOR EACH ROW EXECUTE FUNCTION set_updated_at();
-- CREATE TRIGGER transaction_logs_set_updated_at BEFORE UPDATE ON transaction_logs FOR EACH ROW EXECUTE FUNCTION set_updated_at();
