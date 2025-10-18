-- Fanders Microfinance - PostgreSQL schema for Supabase
-- Converted from MySQL schema: changes include
--  - AUTO_INCREMENT -> SERIAL
--  - removed ON UPDATE CURRENT_TIMESTAMP; added trigger to set updated_at
--  - INT(11) -> INTEGER
--  - timestamps default to NOW()

-- Utility function to set updated_at on update
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Table: users (Staff Management)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    last_login TIMESTAMP NULL,
    phone_number VARCHAR(20) UNIQUE,
    password_changed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE TRIGGER users_set_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

-- Table: clients (Borrower Management)
CREATE TABLE IF NOT EXISTS clients (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    address VARCHAR(255) NOT NULL,
    identification_type VARCHAR(50),
    identification_number VARCHAR(100) UNIQUE,
    date_of_birth DATE,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE TRIGGER clients_set_updated_at
    BEFORE UPDATE ON clients
    FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

-- Table: loans (Core Loan Data)
CREATE TABLE IF NOT EXISTS loans (
    id SERIAL PRIMARY KEY,
    client_id INTEGER NOT NULL,
    principal NUMERIC(10,2) NOT NULL,
    interest_rate NUMERIC(5,4) NOT NULL,
    term_weeks INTEGER NOT NULL,
    total_interest NUMERIC(10,2) NOT NULL,
    insurance_fee NUMERIC(10,2) NOT NULL,
    total_loan_amount NUMERIC(10,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Application',
    application_date TIMESTAMP NOT NULL,
    approval_date TIMESTAMP NULL,
    disbursement_date TIMESTAMP NULL,
    completion_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT fk_loans_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TRIGGER loans_set_updated_at
    BEFORE UPDATE ON loans
    FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

-- Table: payments (Transactional Data - Weekly Payments)
CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    loan_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    amount NUMERIC(10,2) NOT NULL,
    payment_date TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT fk_payments_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Table: cash_blotter (Phase 2 - Cash Flow Management)
CREATE TABLE IF NOT EXISTS cash_blotter (
    id SERIAL PRIMARY KEY,
    blotter_date DATE UNIQUE NOT NULL,
    total_inflow NUMERIC(10,2) DEFAULT 0.00,
    total_outflow NUMERIC(10,2) DEFAULT 0.00,
    calculated_balance NUMERIC(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE TRIGGER cash_blotter_set_updated_at
    BEFORE UPDATE ON cash_blotter
    FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

-- Table: transactions (Phase 2 - Audit Log)
CREATE TABLE IF NOT EXISTS transactions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    reference_id INTEGER NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Create transaction_logs table expected by TransactionLogModel
CREATE TABLE IF NOT EXISTS transaction_logs (
    id SERIAL PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INTEGER NULL,
    action VARCHAR(50) NOT NULL,
    user_id INTEGER NULL,
    details TEXT,
    timestamp TIMESTAMP DEFAULT NOW(),
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT fk_tlogs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
);
CREATE TRIGGER transaction_logs_set_updated_at
    BEFORE UPDATE ON transaction_logs
    FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

-- Indexes / optimization suggestions
CREATE INDEX IF NOT EXISTS idx_loans_client_id ON loans(client_id);
CREATE INDEX IF NOT EXISTS idx_payments_loan_id ON payments(loan_id);
CREATE INDEX IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);

-- End of PostgreSQL schema
