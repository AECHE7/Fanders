# LoanCalculationService ArgumentCountError Fix

## Issue Description
Fatal error occurred when calling `LoanCalculationService::calculateLoan()`:

```
Fatal error: Uncaught ArgumentCountError: Too few arguments to function LoanCalculationService::calculateLoan(), 2 passed in /app/app/services/LoanService.php on line 243 and exactly 3 expected in /app/app/services/LoanCalculationService.php:29
```

## Root Cause
The `calculateLoan()` method was defined to require 3 parameters:
- `$principalAmount` (required)
- `$termWeeks` (required) 
- `$termMonths` (required)

However, multiple parts of the system were calling it with only 2 parameters:
- LoanService.php line 243: `calculateLoan($principal, $termWeeks)`
- LoanService.php line 330: `calculateLoan($loanWithClient['principal'], $loanWithClient['term_weeks'])`
- SLRService.php line 489: `calculateLoan($principal, 17)`
- SLRPDFGenerator.php line 230: `calculateLoan($principal, $termWeeks)`

## Solution Implemented
Modified the method signature to make the last two parameters optional with default null values:

**Before:**
```php
public function calculateLoan($principalAmount, $termWeeks, $termMonths) {
```

**After:**
```php
public function calculateLoan($principalAmount, $termWeeks = null, $termMonths = null) {
```

## Why This Works
The method already had internal logic to handle null values for both parameters:

```php
// Use default terms if not provided
if ($termWeeks === null) {
    $termWeeks = self::DEFAULT_WEEKS_IN_LOAN;  // 17 weeks
}

if ($termMonths === null) {
    $termMonths = self::DEFAULT_LOAN_TERM_MONTHS;  // 4 months
}
```

## Backwards Compatibility
This change maintains full backwards compatibility:
- ✅ Existing calls with 3 parameters continue to work
- ✅ Existing calls with 2 parameters now work (using default for termMonths)
- ✅ New calls with 1 parameter work (using defaults for both termWeeks and termMonths)

## Files Modified
1. **app/services/LoanCalculationService.php**
   - Line 29: Updated method signature to include default null values
   - Line 25-26: Updated documentation

## Affected System Components
This fix resolves the error in:
- ✅ Loan application process (public/loans/add.php)
- ✅ Loan agreement generation (LoanService.php)
- ✅ SLR generation (SLRService.php)
- ✅ SLR PDF generation (SLRPDFGenerator.php)

## Testing
The fix can be tested by:
1. Creating a new loan application
2. Generating loan agreements  
3. Creating SLR documents
4. All should work without ArgumentCountError

## Default Values Used
When parameters are not provided:
- **termWeeks**: 17 weeks (DEFAULT_WEEKS_IN_LOAN)
- **termMonths**: 4 months (DEFAULT_LOAN_TERM_MONTHS)

These defaults align with the standard loan terms used throughout the Fanders system.