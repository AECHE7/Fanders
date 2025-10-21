#!/bin/bash

echo "=== APPLYING CRITICAL FIX TO public/loans/add.php ==="
echo

FILE="/workspaces/Fanders/public/loans/add.php"
BACKUP="${FILE}.backup.$(date +%Y%m%d_%H%M%S)"

# Create backup
echo "1. Creating backup: $BACKUP"
cp "$FILE" "$BACKUP"

# Apply fix using sed
echo "2. Applying fix..."

# First, add the two debug lines after line containing 'submit_loan value'
sed -i '/submit_loan value:/a\        $debugLine .= "Error at this point: " . ($error ?? '\''NONE'\'') . "\\n";\
        $debugLine .= "Calculation success: " . ($loanCalculation ? '\''YES'\'' : '\''NO'\'') . "\\n";' "$FILE"

# Then, replace the submission check line
sed -i 's/if (isset($_POST\['\''submit_loan'\''\])) {/if (isset($_POST['\''submit_loan'\'']) \&\& $loanCalculation \&\& !$error) {/' "$FILE"

echo "3. Verifying fix..."
echo

# Verify the changes
echo "--- Checking for updated submission condition ---"
grep -n "if (isset(\$_POST\['submit_loan'\])" "$FILE"
echo

echo "--- Checking for new debug lines ---"
grep -n "Error at this point" "$FILE"
grep -n "Calculation success" "$FILE"
echo

echo "=== FIX APPLIED SUCCESSFULLY ==="
echo
echo "Backup saved at: $BACKUP"
echo
echo "Next steps:"
echo "  1. Test loan submission"
echo "  2. Check /workspaces/Fanders/LOAN_DEBUG_LOG.txt for debug output"
echo "  3. If issues occur, restore backup: cp $BACKUP $FILE"
echo
