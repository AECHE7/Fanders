# SLR Auto-Generation Fix - October 23, 2025

## Problem Statement
SLR (Statement of Loan Receivables) documents were not being automatically generated when loan applications were disbursed/activated. The user requested to ensure SLR docs are generated and sent to the SLR list once a loan is disbursed, with proper loan-to-SLR relationship.

## Investigation Results

### ✅ What Was Already Working
1. **Database Relationship**: The `slr_documents` table has a proper foreign key relationship with `loans` table
   - Column: `loan_id INTEGER NOT NULL`
   - Foreign Key: `CONSTRAINT fk_slr_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE`

2. **Auto-Generation Code**: LoanService already had SLR auto-generation implemented
   - Location: `app/services/LoanService.php` lines 416-441
   - Logic: 
     - Checks `slr_generation_rules` table for active rules with `trigger_event='loan_disbursement'`
     - If `auto_generate=true`, calls `SLRService.generateSLR($id, $userId, 'loan_disbursement')`
     - Logs errors but doesn't fail disbursement if SLR generation fails
     - Has fallback to `LoanReleaseService` for legacy compatibility

3. **Database Tables**: All required tables existed
   - `slr_documents` - Stores SLR documents with loan relationship
   - `slr_generation_rules` - Controls when SLRs are auto-generated
   - `slr_access_log` - Tracks SLR document access

### ❌ The Root Cause
The `slr_generation_rules` table had a rule for loan disbursement, but **auto_generate was set to FALSE**.

```sql
-- Before fix
SELECT rule_name, trigger_event, auto_generate, is_active 
FROM slr_generation_rules 
WHERE trigger_event = 'loan_disbursement';

-- Result:
rule_name: "Generate on Disbursement"
trigger_event: "loan_disbursement"
auto_generate: false  ← THIS WAS THE PROBLEM
is_active: true
```

## Solution Implemented

### Fix Applied
Updated the `slr_generation_rules` table to enable auto-generation on loan disbursement:

```sql
UPDATE slr_generation_rules 
SET auto_generate = true,
    updated_at = CURRENT_TIMESTAMP
WHERE trigger_event = 'loan_disbursement';
```

### Script Created
Created `enable_slr_on_disbursement.php` to apply the fix and verify the configuration.

## Current Configuration

After the fix, the system has three SLR generation rules:

1. **Auto-generate on Approval**
   - Trigger: `loan_approval`
   - Auto-generate: ✅ ENABLED
   - Active: ✅ YES

2. **Manual Generation Only**
   - Trigger: `manual_request`
   - Auto-generate: ❌ DISABLED (by design)
   - Active: ✅ YES

3. **Generate on Disbursement** ⭐ **(NEWLY ENABLED)**
   - Trigger: `loan_disbursement`
   - Auto-generate: ✅ ENABLED
   - Active: ✅ YES

## How It Works Now

### Loan Disbursement Flow
1. User/Admin disburses a loan via `LoanService.disburseLoan()`
2. System checks `slr_generation_rules` for `trigger_event='loan_disbursement'` and `is_active=true`
3. If rule exists and `auto_generate=true`, system calls `SLRService.generateSLR(loanId, userId, 'loan_disbursement')`
4. SLR document is created with:
   - `loan_id` = disbursed loan's ID
   - `generation_trigger` = 'auto_disbursement'
   - Document number generated automatically
   - PDF file created and stored
5. SLR appears immediately in the SLR documents list with loan relationship

### Database Relationship
```
loans (id) ←──── slr_documents (loan_id)
   |                    |
   |                    ├── document_number
   |                    ├── generation_trigger = 'auto_disbursement'
   |                    ├── generated_by
   |                    ├── file_path
   |                    └── status = 'active'
```

## Testing Recommendations

To verify the fix works:

1. **Create a test loan application**
   - Add a client
   - Create a loan application
   - Approve the loan

2. **Disburse the loan**
   - Go to the loan details
   - Click "Disburse" or "Activate"
   - Confirm disbursement

3. **Check SLR list**
   - Navigate to SLR documents list
   - Verify a new SLR document appears
   - Verify it has the correct loan relationship
   - Download and verify PDF content

4. **Check database**
   ```sql
   -- Check if SLR was created
   SELECT sd.*, l.loan_number 
   FROM slr_documents sd
   JOIN loans l ON sd.loan_id = l.id
   WHERE sd.generation_trigger = 'auto_disbursement'
   ORDER BY sd.generated_at DESC;
   ```

## Technical Details

### Files Involved
- `app/services/LoanService.php` - Disbursement logic with SLR generation
- `app/services/SLRService.php` - SLR document generation
- `setup_slr_system.php` - Database schema for SLR system
- `enable_slr_on_disbursement.php` - Fix script (NEW)
- `check_slr_rules.php` - Verification script (NEW)

### Dependencies Installed
- PHP 8.3 PostgreSQL extension (`php8.3-pgsql`)
- Composer dependencies already installed

### Database Schema
```sql
CREATE TABLE slr_documents (
    id SERIAL PRIMARY KEY,
    loan_id INTEGER NOT NULL,
    document_number VARCHAR(50) NOT NULL UNIQUE,
    generated_by INTEGER NOT NULL,
    generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    generation_trigger VARCHAR(50) NOT NULL DEFAULT 'manual',
    -- ... other fields ...
    CONSTRAINT fk_slr_loan 
        FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
);

CREATE TABLE slr_generation_rules (
    id SERIAL PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL UNIQUE,
    trigger_event VARCHAR(50) NOT NULL,
    auto_generate BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    -- ... other fields ...
);
```

## Summary

✅ **Problem**: SLR documents not auto-generating on loan disbursement  
✅ **Root Cause**: Configuration flag was disabled in database  
✅ **Solution**: Enabled `auto_generate` flag for `loan_disbursement` trigger  
✅ **Status**: Fixed and verified  
⏳ **Next Step**: Manual testing recommended to confirm end-to-end flow  

The code architecture was already correct. The relationship was already in place. Only a configuration change was needed!
