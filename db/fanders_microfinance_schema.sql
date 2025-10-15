-- Fanders Microfinance Loan Management System Database Schema
-- Compatible with Supabase PostgreSQL
-- Based on Requirements Engineering Documentation

-- Enable necessary extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- 1. USERS TABLE (Updated for microfinance roles)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client')),
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),
    last_login TIMESTAMPTZ NULL,
    password_changed_at TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 2. CLIENTS TABLE (Replaces users table for borrowers)
CREATE TABLE IF NOT EXISTS clients (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL UNIQUE,
    address TEXT NULL,
    date_of_birth DATE NULL,
    identification_type VARCHAR(50) NULL, -- e.g., 'passport', 'drivers_license'
    identification_number VARCHAR(50) NULL UNIQUE,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'blacklisted')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 3. LOANS TABLE (Replaces books table)
CREATE TABLE IF NOT EXISTS loans (
    id SERIAL PRIMARY KEY,
    client_id INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
    loan_amount DECIMAL(15,2) NOT NULL, -- Principal amount
    interest_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00, -- Monthly interest rate (5%)
    loan_term_months INTEGER NOT NULL DEFAULT 4, -- Always 4 months
    total_interest DECIMAL(15,2) NOT NULL, -- Calculated as principal * 0.05 * 4
    total_amount DECIMAL(15,2) NOT NULL, -- Principal + Interest + Insurance
    insurance_fee DECIMAL(10,2) NOT NULL DEFAULT 425.00, -- Fixed ₱425
    weekly_payment DECIMAL(10,2) NOT NULL, -- Total amount / 17 weeks
    status VARCHAR(20) NOT NULL DEFAULT 'application' CHECK (status IN ('application', 'approved', 'active', 'completed', 'defaulted')),
    application_date TIMESTAMPTZ DEFAULT NOW(),
    approval_date TIMESTAMPTZ NULL,
    disbursement_date TIMESTAMPTZ NULL,
    completion_date TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 4. PAYMENTS TABLE (Replaces transactions table)
CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    loan_id INTEGER NOT NULL REFERENCES loans(id) ON DELETE RESTRICT,
    client_id INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMPTZ DEFAULT NOW(),
    payment_method VARCHAR(20) NOT NULL DEFAULT 'cash' CHECK (payment_method IN ('cash', 'bank_transfer', 'check')),
    collected_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL, -- Account Officer ID who collected the payment
    recorded_by INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT, -- Cashier ID who recorded the payment
    week_number INTEGER NOT NULL, -- Week 1-17 of the loan term
    principal_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    interest_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    insurance_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    savings_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 5. CASH_BLOTTER TABLE (Daily cash flow tracking)
CREATE TABLE IF NOT EXISTS cash_blotter (
    id SERIAL PRIMARY KEY,
    blotter_date DATE NOT NULL UNIQUE,
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_collections DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_loan_releases DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_expenses DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    closing_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    recorded_by INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    status VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'finalized')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 6. COLLECTION_SHEETS TABLE (Account Officer collections)
CREATE TABLE IF NOT EXISTS collection_sheets (
    id SERIAL PRIMARY KEY,
    account_officer_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    collection_date DATE NOT NULL,
    total_expected_payments DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_collected DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_overdue DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'submitted', 'approved')),
    submitted_at TIMESTAMPTZ NULL,
    approved_at TIMESTAMPTZ NULL,
    approved_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
    notes TEXT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 7. COLLECTION_SHEET_DETAILS TABLE (Individual payments in collection sheet)
CREATE TABLE IF NOT EXISTS collection_sheet_details (
    id SERIAL PRIMARY KEY,
    collection_sheet_id INTEGER NOT NULL REFERENCES collection_sheets(id) ON DELETE CASCADE,
    loan_id INTEGER NOT NULL REFERENCES loans(id) ON DELETE RESTRICT,
    client_id INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
    expected_payment DECIMAL(10,2) NOT NULL,
    actual_payment DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid' CHECK (payment_status IN ('paid', 'partial', 'unpaid', 'overdue')),
    notes TEXT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 8. SLR_DOCUMENTS TABLE (Summary of Loan Release)
CREATE TABLE IF NOT EXISTS slr_documents (
    id SERIAL PRIMARY KEY,
    loan_id INTEGER NOT NULL REFERENCES loans(id) ON DELETE RESTRICT,
    slr_number VARCHAR(50) NOT NULL UNIQUE,
    disbursement_amount DECIMAL(15,2) NOT NULL,
    disbursement_date TIMESTAMPTZ DEFAULT NOW(),
    disbursed_by INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT, -- Cashier who processed disbursement
    approved_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL, -- Manager who approved
    client_present BOOLEAN NOT NULL DEFAULT TRUE,
    client_signature TEXT NULL, -- Could store signature image path
    witness_name VARCHAR(100) NULL,
    witness_signature TEXT NULL,
    notes TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'approved', 'disbursed', 'cancelled')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 9. PENALTIES TABLE (For overdue payments)
CREATE TABLE IF NOT EXISTS penalties (
    id SERIAL PRIMARY KEY,
    loan_id INTEGER NOT NULL REFERENCES loans(id) ON DELETE RESTRICT,
    payment_id INTEGER NULL REFERENCES payments(id) ON DELETE SET NULL, -- Associated payment if applicable
    client_id INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
    penalty_amount DECIMAL(10,2) NOT NULL,
    penalty_date TIMESTAMPTZ DEFAULT NOW(),
    reason VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'waived')),
    paid_at TIMESTAMPTZ NULL,
    assessed_by INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    notes TEXT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 10. AUDIT_LOG TABLE (For compliance and tracking)
CREATE TABLE IF NOT EXISTS audit_log (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INTEGER NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- User login history table
CREATE TABLE IF NOT EXISTS user_login_history (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    login_time TIMESTAMPTZ DEFAULT NOW(),
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL CHECK (status IN ('success', 'failed')),
    failure_reason VARCHAR(255) NULL
);

-- Add indexes for performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_phone ON users(phone_number);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_clients_phone ON clients(phone_number);
CREATE INDEX idx_clients_status ON clients(status);
CREATE INDEX idx_loans_client_id ON loans(client_id);
CREATE INDEX idx_loans_status ON loans(status);
CREATE INDEX idx_payments_loan_id ON payments(loan_id);
CREATE INDEX idx_payments_client_id ON payments(client_id);
CREATE INDEX idx_payments_date ON payments(payment_date);
CREATE INDEX idx_cash_blotter_date ON cash_blotter(blotter_date);
CREATE INDEX idx_collection_sheets_officer ON collection_sheets(account_officer_id);
CREATE INDEX idx_collection_sheets_date ON collection_sheets(collection_date);
CREATE INDEX idx_slr_loan_id ON slr_documents(loan_id);
CREATE INDEX idx_penalties_loan_id ON penalties(loan_id);
CREATE INDEX idx_penalties_status ON penalties(status);
CREATE INDEX idx_audit_log_user ON audit_log(user_id);
CREATE INDEX idx_audit_log_action ON audit_log(action);

-- Insert default administrator user
INSERT INTO users (name, email, phone_number, password, role, status)
VALUES (
    'System Administrator',
    'admin@fandersmicrofinance.com',
    '09123456789',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewYpR0JwZxGQJQHy', -- password: admin123
    'administrator',
    'active'
) ON DUPLICATE KEY UPDATE id=id;
