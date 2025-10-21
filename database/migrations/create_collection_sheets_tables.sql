-- Migration: Create collection sheets tables for FR-006 / UR-006 / FR-007

CREATE TABLE IF NOT EXISTS collection_sheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    officer_id INT NOT NULL,
    sheet_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft', -- draft|submitted|posted
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_officer_date (officer_id, sheet_date),
    INDEX idx_status_date (status, sheet_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collection_sheet_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sheet_id INT NOT NULL,
    client_id INT NOT NULL,
    loan_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    notes VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft', -- draft|submitted|posted|rejected
    posted_at TIMESTAMP NULL,
    posted_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sheet (sheet_id),
    INDEX idx_client_loan (client_id, loan_id),
    CONSTRAINT fk_sheet_items_sheet FOREIGN KEY (sheet_id) REFERENCES collection_sheets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
