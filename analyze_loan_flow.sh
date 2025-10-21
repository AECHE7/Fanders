#!/bin/bash
# Manual Code Flow Analysis for Loan Submission

echo "=== MANUAL LOAN SUBMISSION FLOW ANALYSIS ==="
echo ""
echo "This script analyzes the code to identify potential issues without needing PHP execution"
echo ""

# Check if add.php has the submit_loan handler
echo "Step 1: Checking if submit_loan handler exists in add.php..."
grep -n "submit_loan" /workspaces/Fanders/public/loans/add.php | head -5
echo ""

# Check the validation logic
echo "Step 2: Checking CSRF validation logic..."
grep -n "validateRequest" /workspaces/Fanders/public/loans/add.php
echo ""

# Check error handling
echo "Step 3: Checking error handling..."
grep -n "error_log\|error =" /workspaces/Fanders/public/loans/add.php | head -10
echo ""

# Check applyForLoan method
echo "Step 4: Checking LoanService::applyForLoan structure..."
grep -n "public function applyForLoan\|validateLoanData\|getErrorMessage" /workspaces/Fanders/app/services/LoanService.php | head -15
echo ""

# Check if there are any database errors
echo "Step 5: Checking database configuration..."
test -f /workspaces/Fanders/app/config/database.php && echo "Database config exists" || echo "Database config MISSING"
echo ""

# Check if loans table exists in any migration or schema file
echo "Step 6: Checking for loans table schema..."
find /workspaces/Fanders -name "*.sql" -o -name "*migration*" -o -name "*schema*" | head -10
echo ""

# Show the exact form submission code
echo "Step 7: Hidden form in preview (lines 205-220)..."
sed -n '205,220p' /workspaces/Fanders/public/loans/add.php
echo ""

# Show the submit_loan handling code
echo "Step 8: Submit handler code (lines 62-86)..."
sed -n '62,86p' /workspaces/Fanders/public/loans/add.php
echo ""

echo "=== END ANALYSIS ==="
