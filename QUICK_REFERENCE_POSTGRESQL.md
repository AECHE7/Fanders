# PostgreSQL Quick Reference - Fanders LMS

## Database Schema Columns

### Users Table
```
id, name, email, password, role, status, last_login, 
phone_number, password_changed_at, created_at, updated_at
```

### Clients Table
```
id, name, email, phone_number, address, identification_type, 
identification_number, date_of_birth, status, created_at, updated_at
```

### Loans Table
```
id, client_id, principal, interest_rate, term_weeks, total_interest, 
insurance_fee, total_loan_amount, status, application_date, approval_date, 
disbursement_date, completion_date, created_at, updated_at
```

### Payments Table
```
id, loan_id, user_id, amount, payment_date, created_at
```

### Cash Blotter Table
```
id, blotter_date, total_inflow, total_outflow, calculated_balance, 
created_at, updated_at
```

### Transaction Logs Table
```
id, entity_type, entity_id, action, user_id, details, timestamp, 
ip_address, created_at, updated_at
```

### System Backups Table
```
id, filename, filepath, type, size, status, cloud_url, created_by, 
created_at, last_restored_at, restore_count, notes
```

---

## Common PostgreSQL Conversions

| MySQL | PostgreSQL | Example |
|-------|-----------|---------|
| `AUTO_INCREMENT` | `SERIAL` | `id SERIAL PRIMARY KEY` |
| `INT(11)` | `INTEGER` | `client_id INTEGER` |
| `CURRENT_TIMESTAMP` | `NOW()` | `DEFAULT NOW()` |
| `DATE_SUB(NOW(), INTERVAL 30 DAY)` | `NOW() - INTERVAL '30 days'` | ✅ |
| `CURDATE()` | `CURRENT_DATE` | ✅ |
| `DATEDIFF(a, b)` | `(a::date - b::date)` | Returns integer days |
| `DATE(timestamp)` | `timestamp::date` | Cast to date |
| `YEAR(timestamp)` | `EXTRACT(YEAR FROM timestamp)` | ✅ |
| `MONTH(timestamp)` | `EXTRACT(MONTH FROM timestamp)` | ✅ |
| `CONCAT(a, b)` | `CONCAT(a, b)` or `a || b` | Both work! |

---

## Field Mapping (Common Mistakes to Avoid)

### ❌ Don't Use (Old MySQL)
```sql
-- Clients
c.first_name, c.last_name, c.phone

-- Users  
u.first_name, u.last_name, u.username, u.is_active

-- Loans
l.loan_number, l.principal_amount, l.total_amount, 
l.term_months, l.maturity_date

-- Payments
p.payment_number, p.payment_method, p.status
```

### ✅ Use Instead (PostgreSQL Schema)
```sql
-- Clients
c.name, c.phone_number

-- Users
u.name, u.email, u.status

-- Loans  
l.id (as loan_number), l.principal, l.total_loan_amount,
l.term_weeks, l.completion_date

-- Payments
p.id (as payment_number), p.amount, p.payment_date
```

---

## Required Database Function

Create this function BEFORE running schema:

```sql
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

---

## Connection String Example

```php
$host = 'localhost';
$db   = 'fanders_lms';
$user = 'fanders_user';
$pass = 'secure_password';
$charset = 'utf8mb4';

$dsn = "pgsql:host=$host;port=5432;dbname=$db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO($dsn, $user, $pass, $options);
```

---

## Testing Queries

### Test Date Functions
```sql
-- Should work in PostgreSQL
SELECT NOW() - INTERVAL '30 days' as thirty_days_ago;
SELECT CURRENT_DATE;
SELECT (CURRENT_DATE - '2025-01-01'::date) as days_diff;
SELECT EXTRACT(YEAR FROM NOW());
```

### Test Table Structure
```sql
\d users
\d clients  
\d loans
\d payments
```

### Verify Triggers
```sql
SELECT tgname, tgrelid::regclass 
FROM pg_trigger 
WHERE tgname LIKE '%updated_at%';
```

---

## Common Queries

### Get Active Loans
```sql
SELECT l.*, c.name as client_name
FROM loans l
JOIN clients c ON l.client_id = c.id
WHERE l.status = 'Active';
```

### Get Recent Payments (Last 30 Days)
```sql
SELECT p.*, l.id as loan_id, c.name as client_name
FROM payments p
JOIN loans l ON p.loan_id = l.id
JOIN clients c ON l.client_id = c.id
WHERE p.payment_date >= NOW() - INTERVAL '30 days'
ORDER BY p.payment_date DESC;
```

### Get User Stats
```sql
SELECT 
    status,
    COUNT(*) as count
FROM users
GROUP BY status;
```

---

## Indexes for Performance

```sql
CREATE INDEX idx_loans_client_id ON loans(client_id);
CREATE INDEX idx_loans_status ON loans(status);
CREATE INDEX idx_payments_loan_id ON payments(loan_id);
CREATE INDEX idx_payments_payment_date ON payments(payment_date);
CREATE INDEX idx_clients_status ON clients(status);
CREATE INDEX idx_users_status ON users(status);
```
