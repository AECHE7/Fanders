#!/bin/bash

echo "=== LOAN SUBMISSION - ALL POSSIBLE ERROR MESSAGES ==="
echo ""
echo "This script lists all error messages that could be displayed to the user"
echo ""

echo "1. CSRF Token Errors (from add.php):"
grep -n "Invalid security token" /workspaces/Fanders/public/loans/add.php
echo ""

echo "2. Calculation Errors (from add.php and service):"
grep -n "Failed to calculate\|getErrorMessage" /workspaces/Fanders/public/loans/add.php | head -5
echo ""

echo "3. Loan Application Errors (from service):"
grep -n "setErrorMessage" /workspaces/Fanders/app/services/LoanService.php | head -15
echo ""

echo "=== CRITICAL ERROR MESSAGES FROM LOANSERVICE ==="
echo ""
echo "Extracting all setErrorMessage calls:"
grep -B2 "setErrorMessage" /workspaces/Fanders/app/services/LoanService.php | grep -E "setErrorMessage|'|\"" | head -30
echo ""

echo "=== POSSIBLE VALIDATION FAILURES IN validateLoanData ==="
echo ""
echo "Line 482 (client validation):"
sed -n '482,484p' /workspaces/Fanders/app/services/LoanService.php
echo ""
echo "Line 493 (client existence):"
sed -n '493,495p' /workspaces/Fanders/app/services/LoanService.php
echo ""
echo "Line 501 (active loan check):"
sed -n '501,504p' /workspaces/Fanders/app/services/LoanService.php
echo ""
echo "Line 509 (defaulted loan check):"
sed -n '509,512p' /workspaces/Fanders/app/services/LoanService.php
echo ""
echo "Line 517 (loan amount validation):"
sed -n '517,520p' /workspaces/Fanders/app/services/LoanService.php
echo ""

echo "=== LIKELY ERROR MESSAGES USER WOULD SEE ==="
echo ""
echo "1. 'Selected client does not exist.'"
echo "2. 'Client already has an active loan and cannot apply for another.'"
echo "3. 'Client has defaulted loans and must settle their account before applying.'"
echo "4. 'Loan amount must be at least ₱1,000.'"
echo "5. 'Loan amount cannot exceed ₱50,000.'"
echo "6. 'Failed to save loan application.' (database error)"
echo "7. 'Failed to submit loan application.' (generic fallback)"
echo ""
