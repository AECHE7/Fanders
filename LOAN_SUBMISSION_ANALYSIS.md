# Loan Submission Analysis Report

## Overview
Analyzed the complete loan creation flow to identify why loan submissions are not proceeding after successful calculation.

## Flow Analysis

### Current Two-Step Process
1. **Step 1: Calculate Button**
   - User fills form: Client ID, Loan Amount, Loan Term
   - Clicks "Calculate" button
   - Form submits POST with: `client_id`, `loan_amount`, `loan_term`, `csrf_token`
   - Server validates CSRF token (no regeneration)
   - Server calculates loan details
   - Server regenerates CSRF token with new value
   - Preview form appears with calculation details

2. **Step 2: Submit Button**
   - User clicks "Submit Loan Application" button on preview
   - Hidden form submits POST with: `client_id`, `loan_amount`, `loan_term`, `csrf_token` (new value), `submit_loan`
   - Server validates CSRF token
   - Server recalculates loan (verification step)
   - Server calls `LoanService::applyForLoan()`
   - If successful: Redirect to loans list
   - If failed: Show error on same page

## Code Review Findings

### Files Examined
- `/public/loans/add.php` - Main loan controller
- `/app/services/LoanService.php` - Loan business logic
- `/app/services/LoanCalculationService.php` - Calculation logic
- `/app/models/LoanModel.php` - Database operations
- `/templates/loans/form.php` - Form template

### Potential Failure Points Identified

#### 1. **CSRF Token Validation** (UNLIKELY - but verified)
- ✓ Token generated after first calculation
- ✓ Hidden form includes regenerated token
- ✓ `validateRequest(false)` allows multiple validations
- Status: Should work correctly

#### 2. **Loan Amount/Term Casting** (LOW RISK)
- Values converted from POST strings to float/int
- Hidden form preserves values with `htmlspecialchars()`
- Server recasts values on submission
- Status: Should work correctly

#### 3. **Calculation on Submission** (POSSIBLE)
- Loan is recalculated when `applyForLoan()` is called
- If calculation fails on 2nd attempt while 1st succeeded = SUSPICIOUS
- Could indicate state-dependent calculation issues

#### 4. **Service Validation Checks** (LIKELY CULPRIT)
In `LoanService::validateLoanData()`, checked:
```php
// Check for active loan - Client already has an active loan
// Check for defaulted loan - Client has defaulted loans
// Validation of loan amount against business rules (1000-50000)
```

**Key Issue**: If user tested with same client TWICE, first loan might still be "Application" status, not "Active". But the check is for "getClientActiveLoan()" which only checks for ACTIVE loans, not Application status.

However, looking more carefully at line 203-204 of LoanService:
```php
// Check for active loan
if ($this->loanModel->getClientActiveLoan($clientId)) {
    $this->setErrorMessage('Client already has an active loan...');
```

This should only fail if client has an ACTIVE loan (not Application). So this shouldn't be the issue.

#### 5. **Database Insert Failure** (POSSIBLE)
- Missing database columns
- Constraint violations
- Data type mismatches
- Permission issues

#### 6. **Missing User ID** (VERY POSSIBLE)
Looking at add.php line 72:
```php
$loanId = $loanService->applyForLoan($loanData, $user['id']);
```

**Critical Question**: Is `$user['id']` set? 
- Need to verify: `$user` array is populated from session/auth
- Need to verify: `$user['id']` is not null/empty

## Diagnostic Enhancements Added

### Error Logging
Added `error_log()` statements at critical points:

1. **Calculation Error Logging** (line 57)
```php
error_log("Loan calculation error on add.php: " . $error . " (amount: " . $loan['loan_amount'] . ", term: " . $loan['loan_term'] . ")");
```

2. **Submission Attempt Logging** (line 74)
```php
error_log("Loan submission attempt: client=" . $loan['client_id'] . ", amount=" . $loan['loan_amount'] . ", term=" . $loan['loan_term']);
```

3. **Success Logging** (line 80)
```php
error_log("Loan created successfully. Loan ID: " . $loanId);
```

4. **Failure Logging** (line 86)
```php
error_log("Loan creation failed: " . $error);
```

### Enhanced Form Field
Added explicit value to submit button (line 212):
```php
<button type="submit" name="submit_loan" value="1" class="btn btn-success btn-lg">
```

## Recommended Next Steps

### 1. **Check Error Logs**
```bash
# Find PHP error logs (location varies by server)
tail -f /var/log/php-fpm/error.log  # or
tail -f /var/log/apache2/error.log  # or
tail -f ~/.pm2/logs/*               # if using PM2
```

Look for messages with "Loan submission attempt" or "Loan creation failed"

### 2. **Test Workflow**
1. Create a fresh test client (no existing loans)
2. Navigate to: `/public/loans/add.php?client_id=TEST_ID`
3. Fill in: Amount=5000, Term=17
4. Click: Calculate
5. Verify: Preview appears with calculations
6. Click: Submit Loan Application
7. Check: Did it redirect to loans list?
8. If not: Check error message displayed
9. Check: PHP error log for detailed failure message

### 3. **Database Verification**
```sql
-- Check if loans table has all required columns:
DESC loans;

-- Check if user making request has necessary permissions:
SELECT * FROM users WHERE id = SESSION_USER_ID;

-- Verify the calculation:
SELECT principal, interest_rate, term_weeks, total_interest, insurance_fee, total_loan_amount 
FROM loans 
WHERE client_id = TEST_CLIENT_ID 
ORDER BY created_at DESC LIMIT 1;
```

### 4. **Browser Developer Tools**
1. Open browser developer console (F12)
2. Go to Network tab
3. Perform loan submission
4. Check the POST request to add.php:
   - Status: 200 (OK) or error?
   - Response: Contains error message?
   - Redirect: Happens or not?

### 5. **Session Verification**
Verify that `$user` object is populated:
```php
// Add to add.php after line 18:
error_log("Current user: " . json_encode($user ?? 'NOT SET'));
```

## Critical Issue Found During Analysis

### **Likely Root Cause: User Object Not Passed Correctly**

Looking at line 15-18 of add.php:
```php
// Enforce role-based access control (Staff roles allowed to apply for loans)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer']);
```

But I don't see where `$user` is defined! Let me check if it comes from init.php...

This needs verification. If `$user` is not defined or is empty, then:
```php
$loanId = $loanService->applyForLoan($loanData, $user['id']);
// Would become:
$loanId = $loanService->applyForLoan($loanData, NULL);
// Or cause a PHP Notice/Warning
```

This could cause the service to fail!

## Files Modified in This Analysis

### Enhanced Diagnostic Version
- `/public/loans/add.php` - Added error logging and button value attribute
- Commit: `3887eaf` - "Enhancement: Add detailed diagnostic logging to loan submission flow"

## Action Items for User

1. ✅ Enhanced diagnostic logging deployed
2. ⏳ **Check PHP error logs** for detailed failure messages
3. ⏳ **Test the workflow** with fresh data
4. ⏳ **Verify `$user` variable** is properly set in init.php or session
5. ⏳ **Share error log output** if submission still fails
6. ⏳ **Share browser console messages** if visible errors occur

## Summary

The loan submission flow appears well-structured at first glance, but the actual failure point cannot be determined without:
1. Checking PHP error logs for the exact failure message
2. Verifying the `$user` variable is properly populated
3. Testing with sample data to see which error message appears

The diagnostic enhancements I've added will make it much easier to pinpoint the exact issue when you run through the workflow again.

Once you check the error logs and share what message appears, I can provide a targeted fix for the specific problem.
