# Quick Loan Issue Troubleshooting Checklist

## 🔧 Immediate Checks (Do These First)

### Check 1: Database Status Values
```bash
# Run this SQL query to check database status values
mysql -u [user] -p[password] [database] -e "SELECT DISTINCT status FROM loans LIMIT 10;"
```

**Expected Output:**
```
Application
Approved
Active
Completed
Defaulted
```

**❌ If you see:** `application`, `active`, `ACTIVE`, etc. (different casing)  
**→ This is the issue!** Status values must be exactly as in LoanModel constants.

---

### Check 2: Can Client Apply?
Run the test script to verify eligibility:

```php
// At /workspaces/Fanders/test_loan_eligibility.php
<?php
require_once 'public/init.php';

$loanService = new LoanService();
$clientId = 1; // Change this to test client

if ($loanService->canClientApplyForLoan($clientId)) {
    echo "✓ Client IS eligible\n";
} else {
    echo "✗ Client NOT eligible: " . $loanService->getErrorMessage() . "\n";
}

// Check for active loans
$activeLoan = $loanService->getLoansByClient($clientId, ['status' => 'Active']);
echo "Active loans: " . count($activeLoan) . "\n";

// Check for defaulted loans
$loans = $loanService->getLoansByClient($clientId);
foreach ($loans as $loan) {
    echo "  - Loan ID {$loan['id']}: Status={$loan['status']}\n";
}
?>
```

---

### Check 3: Test Direct Loan Creation
```php
// At /workspaces/Fanders/test_create_loan.php
<?php
require_once 'public/init.php';

$loanService = new LoanService();
$clientId = 1; // Use an active client
$userId = 1;   // Staff member creating the loan

$loanData = [
    'client_id' => $clientId,
    'principal' => 5000,
    'term_weeks' => 17
];

echo "Attempting to create loan...\n";
$loanId = $loanService->applyForLoan($loanData, $userId);

if ($loanId) {
    echo "✓ SUCCESS: Loan created with ID: $loanId\n";
} else {
    echo "✗ FAILED: " . $loanService->getErrorMessage() . "\n";
}
?>
```

---

## 🔍 Diagnosis Guide

### Symptom: "Client already has an active loan"

**File:** `/workspaces/Fanders/app/models/LoanModel.php` line 282

```php
// This query checks for Active loans
WHERE client_id = ? AND status = ?  // status = 'Active'
```

**Issue:** Status value in database doesn't match `'Active'` (capital A)

**Quick Fix:**
```sql
-- Check what status values exist
SELECT DISTINCT status FROM loans WHERE client_id = 1;

-- If you see 'active' (lowercase), fix it:
UPDATE loans SET status = 'Active' WHERE status = 'active';
UPDATE loans SET status = 'Active' WHERE status = 'ACTIVE';
```

---

### Symptom: "Failed to submit loan application" (no specific error)

**File:** `/workspaces/Fanders/public/loans/add.php` line 100

The error message is being swallowed. Check:

1. **Is LoanModel::create() being called?**
   ```php
   // Add this to LoanModel::create() to debug
   error_log("LoanModel::create() called with: " . json_encode($data));
   ```

2. **Is the INSERT query failing?**
   ```php
   // Add this to BaseModel::create() to debug
   error_log("SQL: INSERT INTO {$this->table}...");
   error_log("Result: " . ($result ? "SUCCESS" : "FAILED"));
   ```

3. **Check browser console for JavaScript errors:**
   - Open DevTools (F12)
   - Look for form submission errors
   - Check Network tab for 500 errors

---

### Symptom: Calculation works, but Submit fails

**File:** `/workspaces/Fanders/public/loans/add.php` line 94

**Check:**
1. Is `submit_loan` POST parameter being sent?
   ```php
   // Add debug output
   var_dump($_POST);  // Should show ['submit_loan' => '1']
   ```

2. Is CSRF token valid after regeneration?
   ```php
   // Check CSRF validation
   if (!$csrf->validateRequest(false)) {
       error_log("CSRF validation failed");
   }
   ```

3. Is error message empty?
   ```php
   // In add.php around line 109
   $submissionError = $loanService->getErrorMessage();
   error_log("Error message: " . ($submissionError ?: "EMPTY"));
   ```

---

## 🔧 Common Fixes

### Fix 1: Standardize Status Values in Database
```sql
-- Update all status values to match constants
UPDATE loans SET status = 'Application' WHERE LOWER(status) = 'application';
UPDATE loans SET status = 'Approved' WHERE LOWER(status) = 'approved';
UPDATE loans SET status = 'Active' WHERE LOWER(status) = 'active';
UPDATE loans SET status = 'Completed' WHERE LOWER(status) = 'completed';
UPDATE loans SET status = 'Defaulted' WHERE LOWER(status) = 'defaulted';

-- Verify
SELECT DISTINCT status FROM loans;
```

---

### Fix 2: Add Better Error Logging
**File:** `/workspaces/Fanders/public/loans/add.php`

Replace line 99-109:
```php
// OLD
if ($loanId) {
    // success
} else {
    $submissionError = $loanService->getErrorMessage();
    $error = $submissionError ?: "Failed to submit loan application.";
}

// NEW - Better error tracking
if ($loanId) {
    // success
} else {
    $submissionError = $loanService->getErrorMessage();
    
    // Log for debugging
    error_log("Loan submission failed for client_id=$clientId");
    error_log("Error message: " . ($submissionError ?: "NO ERROR MESSAGE"));
    error_log("Service error message exists: " . (!!$submissionError ? "YES" : "NO"));
    
    $error = !empty($submissionError) ? $submissionError : "Failed to submit loan application.";
}
```

---

### Fix 3: Check Database Collation
```sql
-- Check loans table collation
SHOW CREATE TABLE loans;

-- Should see utf8mb4_unicode_ci or similar case-insensitive collation
-- If it's utf8mb4_bin (case-sensitive), change it:
ALTER TABLE loans CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

### Fix 4: Verify Fillable Fields
**File:** `/workspaces/Fanders/app/models/LoanModel.php`

```php
// Check if all fields match database columns
protected $fillable = [
    'client_id', 
    'principal', 
    'interest_rate', 
    'term_weeks',
    'total_interest', 
    'insurance_fee', 
    'total_loan_amount',
    'status', 
    'application_date', 
    'created_at', 
    'updated_at'
];

// Compare with actual database columns:
// mysql> DESCRIBE loans;
```

---

## 📋 Testing Flow

1. **Test Database Insert:**
   ```sql
   INSERT INTO loans (client_id, principal, interest_rate, term_weeks, total_interest, insurance_fee, total_loan_amount, status, application_date, created_at, updated_at)
   VALUES (1, 5000, 0.05, 17, 1000, 425, 6425, 'Application', NOW(), NOW(), NOW());
   ```
   ✓ If this works, the table is fine.

2. **Test Model directly:**
   ```php
   $loanModel = new LoanModel();
   $id = $loanModel->create([...]);
   echo $id ? "✓ Works" : "✗ Failed: " . $loanModel->getLastError();
   ```
   ✓ If this works, the model is fine.

3. **Test Service:**
   ```php
   $loanService = new LoanService();
   $id = $loanService->applyForLoan([...], 1);
   echo $id ? "✓ Works" : "✗ Failed: " . $loanService->getErrorMessage();
   ```
   ✓ If this works, the service is fine.

4. **Test UI Form:**
   - Navigate to Loans → New Loan Application
   - Select client
   - Enter amount
   - Click Calculate
   - Click Submit
   ✓ If all steps complete, the issue is fixed.

---

## 📞 If All Else Fails

1. **Enable PHP error logging:**
   ```php
   // Add to /workspaces/Fanders/app/core/Database.php
   error_log("Database query: " . $sql);
   error_log("Parameters: " . json_encode($params));
   error_log("Result: " . ($result ? "SUCCESS" : "FAILED - " . $this->connection->error));
   ```

2. **Check browser network tab:**
   - Right-click → Inspect → Network tab
   - Submit the form
   - Look for the request to `add.php`
   - Check response body for error details

3. **Check PHP error log:**
   ```bash
   tail -f /var/log/php.log
   # or wherever PHP errors are logged
   ```

4. **Check database error log:**
   ```bash
   tail -f /var/log/mysql/error.log
   # or wherever MySQL errors are logged
   ```

---

## 📝 Status Constants Reference

Always use these exact values:

```php
// From LoanModel
'Application'  // Initial status when loan is first created
'Approved'     // After manager approves
'Active'       // After funds are disbursed
'Completed'    // After all payments received
'Defaulted'    // If loan is written off

// IMPORTANT: Capital A, C, D
// NOT: 'application', 'active', 'ACTIVE', etc.
```

---

## 🎯 Most Likely Fix

Based on analysis, the **most probable fix** is:

```sql
-- Run this to standardize all status values
UPDATE loans SET status = CONCAT(UPPER(SUBSTR(status,1,1)), LOWER(SUBSTR(status,2))) 
WHERE status NOT IN ('Application', 'Approved', 'Active', 'Completed', 'Defaulted');
```

Then test loan creation again.

---

