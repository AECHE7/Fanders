# LOAN ISSUE - QUICK REFERENCE CARD

**Print this or bookmark it**

---

## 🔴 THE PROBLEM

When trying to add a new loan for a client **WITHOUT an active loan**, the submission fails.

```
✓ Calculate button works → shows preview
✗ Submit button fails → stays on same page or shows generic error
```

---

## 🎯 MOST LIKELY CAUSES (in order)

### #1: Database Status Values Wrong Case (60% chance)
**Check:** 
```sql
SELECT DISTINCT status FROM loans LIMIT 10;
```

**Expected:** `Application`, `Approved`, `Active`, `Completed`, `Defaulted`  
**If you see:** `active`, `ACTIVE`, `application`, etc. → **THIS IS THE ISSUE**

**Fix:** See page 3 (Database Fixes)

---

### #2: Error Message Hidden (50% chance)
**Symptom:** Form fails with "Failed to submit..." (too generic)  
**Reality:** Real error exists but not shown  

**Fix:** See page 3 (Code Fixes #2)

---

### #3: Status Comparison Case-Sensitive (40% chance)
**File:** `LoanModel.php` lines 282, 290  
**Fix:** See page 3 (Code Fixes #3)

---

## ⚡ QUICK FIXES (Do in This Order)

### FIX #1: Check Database (2 minutes)
```bash
# SSH to your server
mysql -u [user] -p[password] [database]

# Run this query
SELECT DISTINCT status FROM loans LIMIT 10;

# If status values are lowercase or mixed case:
# CONTINUE TO FIX #2
```

---

### FIX #2: Standardize Database Status (5 minutes)
```sql
-- Copy-paste this entire block:
UPDATE loans SET status = 'Application' WHERE LOWER(status) = 'application';
UPDATE loans SET status = 'Approved' WHERE LOWER(status) = 'approved';
UPDATE loans SET status = 'Active' WHERE LOWER(status) = 'active';
UPDATE loans SET status = 'Completed' WHERE LOWER(status) = 'completed';
UPDATE loans SET status = 'Defaulted' WHERE LOWER(status) = 'defaulted';

-- Verify
SELECT DISTINCT status FROM loans;
-- Should show exactly: Application, Approved, Active, Completed, Defaulted
```

---

### FIX #3: Update LoanModel.php (10 minutes)
**File:** `/workspaces/Fanders/app/models/LoanModel.php`

**Find Line 282:**
```php
// FIND THIS:
public function getClientActiveLoan($clientId) {
    $sql = "SELECT * FROM {$this->table}
            WHERE client_id = ? AND status = ?
            ORDER BY created_at DESC LIMIT 1";

// CHANGE TO:
public function getClientActiveLoan($clientId) {
    $sql = "SELECT * FROM {$this->table}
            WHERE client_id = ? AND LOWER(status) = LOWER(?)
            ORDER BY created_at DESC LIMIT 1";
```

**Find Line 290:**
```php
// FIND THIS:
public function hasClientDefaultedLoan($clientId) {
    $sql = "SELECT COUNT(*) as count FROM {$this->table}
            WHERE client_id = ? AND status = ?";

// CHANGE TO:
public function hasClientDefaultedLoan($clientId) {
    $sql = "SELECT COUNT(*) as count FROM {$this->table}
            WHERE client_id = ? AND LOWER(status) = LOWER(?)";
```

---

### FIX #4: Update add.php (10 minutes)
**File:** `/workspaces/Fanders/public/loans/add.php`

**Find Line 100-115:**
```php
// FIND THIS:
} else {
    $submissionError = $loanService->getErrorMessage();
    $error = $submissionError ?: "Failed to submit loan application.";
    $session->setFlash('error', $error);
}

// CHANGE TO:
} else {
    $submissionError = $loanService->getErrorMessage();
    
    if (!$submissionError || trim($submissionError) === '') {
        $error = "Failed to submit loan application. Please check the form and try again.";
        error_log("CRITICAL: Loan submission failed for client_id=" . $loan['client_id'] . " but no error message provided");
    } else {
        $error = $submissionError;
    }
    
    $session->setFlash('error', $error);
}
```

---

## 🧪 TEST IT (5 minutes)

After applying fixes:

1. **Open browser:** `http://yourapp.com/public/loans/add.php`
2. **Select:** Any client from dropdown
3. **Enter:** Amount = 5000
4. **Enter:** Term = 17
5. **Click:** "Calculate" button
   - ✓ Should show preview with payment schedule
6. **Click:** "Submit Loan Application" button
   - ✓ Should redirect to loans list
   - ✓ Should see success message
   - ✓ New loan should be in list with "Application" status

---

## 📋 SYMPTOMS & FIXES QUICK MAP

| Symptom | Likely Cause | Fix |
|---------|--------------|-----|
| "Client already has active loan" when they don't | Status case mismatch | Fix #2 or #3 |
| "Failed to submit..." (generic) | Error message hidden | Fix #4 |
| No error, stays on page | Database or query issue | Fix #2 or #3 |
| Form data lost after error | Session issue | Check session config |
| Calculation works but submit fails | One of above | Fix #3 or #4 |

---

## 🔗 DETAILED DOCS REFERENCE

- **Want more details?** → `LOAN_ISSUE_SUMMARY.md`
- **Need diagnostics?** → `LOAN_TROUBLESHOOTING_CHECKLIST.md`
- **Want visuals?** → `LOAN_ISSUE_VISUAL_FLOW.md`
- **Need exact code?** → `LOAN_ISSUE_SPECIFIC_FIXES.md`
- **Deep dive?** → `LOAN_CREATION_ISSUE_ANALYSIS.md`

---

## 📞 IF STILL FAILING

### Check #1: Is form reaching the server?
```
Open: Browser DevTools (F12) → Network tab
Action: Submit loan form
Look for: POST request to add.php
Check: Response code (should be 200, not 500)
```

### Check #2: Is error in PHP error log?
```bash
# Check PHP error log
tail -50 /var/log/php.log
# or wherever your PHP logs are

# Look for: "Loan submission failed"
# or: MySQL error messages
```

### Check #3: Is database query returning results?
```sql
-- Test the active loan query directly
SELECT * FROM loans WHERE client_id = 1 AND LOWER(status) = LOWER('Active');
-- Should return 0 rows for a client without active loans
```

### Check #4: Is error message empty?
```php
// Add this to add.php line 101 temporarily:
error_log("DEBUG: submissionError = '" . $loanService->getErrorMessage() . "'");
error_log("DEBUG: error = '" . $error . "'");

// Then check PHP error log for these messages
// If they show empty '', this is the issue
```

---

## ✅ VERIFICATION CHECKLIST

After applying all fixes, verify:

- [ ] Database status values are standardized
- [ ] `LOWER()` added to LoanModel.php queries
- [ ] Error message handling improved in add.php
- [ ] Test client created successfully
- [ ] Loan calculated successfully
- [ ] Loan submitted successfully
- [ ] Loan appears in loans list
- [ ] Loan status is "Application"
- [ ] Can create multiple test loans
- [ ] No error messages in PHP error log

---

## 🆘 IF YOU NEED HELP

Provide this information:

1. **Error message you see:** (full text)
2. **SQL output:** `SELECT DISTINCT status FROM loans;`
3. **PHP error log:** Last 20 lines containing "loan" or "error"
4. **Database collation:** `SHOW CREATE TABLE loans\G`
5. **Which fix(es) applied:** (all or specific ones)
6. **Still failing:** Yes/No

---

## 📊 ESTIMATED TIME

| Task | Time | Complexity |
|------|------|-----------|
| Read this card | 3 min | ★☆☆ |
| Run FIX #1-2 | 7 min | ★☆☆ |
| Apply FIX #3-4 | 20 min | ★★☆ |
| Test | 5 min | ★☆☆ |
| **TOTAL** | **35 min** | **Easy** |

---

## ⚡ ONE-LINER CHECKLIST

```
□ Check status values in DB are capitalized correctly
□ Run SQL standardization if needed
□ Add LOWER() to LoanModel.php line 282 and 290
□ Fix error message handling in add.php line 107-110
□ Test with a new client loan application
□ Verify loan appears in loans list
□ Done! ✓
```

---

**Last Updated:** October 21, 2025  
**Analysis Status:** Complete ✓  
**Implementation Status:** Ready ✓  
**Testing Status:** Ready ✓

