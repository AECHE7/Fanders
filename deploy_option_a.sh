#!/bin/bash
# Deploy Option A: Form Inside Modal Pattern
# This script deploys the refactored client form with zero jittering

set -e  # Exit on error

WORKSPACE="/workspaces/Fanders"
cd "$WORKSPACE"

echo "=========================================="
echo "Option A Deployment: Form Inside Modal"
echo "=========================================="
echo ""

# Step 1: Backup original file
echo "📦 Step 1: Backing up original file..."
if [ -f "templates/clients/form.php" ]; then
    cp templates/clients/form.php "templates/clients/form_OLD_$(date +%Y%m%d_%H%M%S).php"
    echo "✅ Backup created: templates/clients/form_OLD_$(date +%Y%m%d_%H%M%S).php"
else
    echo "⚠️  Original file not found (might be first deploy)"
fi
echo ""

# Step 2: Deploy refactored version
echo "🚀 Step 2: Deploying refactored version..."
if [ -f "templates/clients/form_refactored.php" ]; then
    cp templates/clients/form_refactored.php templates/clients/form.php
    echo "✅ Refactored version deployed to templates/clients/form.php"
else
    echo "❌ Error: templates/clients/form_refactored.php not found!"
    exit 1
fi
echo ""

# Step 3: Verify deployment
echo "🔍 Step 3: Verifying deployment..."
if grep -q "REFACTORED PATTERN: Form is INSIDE modal" templates/clients/form.php; then
    echo "✅ Deployment verified - new pattern detected"
else
    echo "⚠️  Warning: Could not verify new pattern"
fi
echo ""

# Step 4: Git operations
echo "📝 Step 4: Preparing git commit..."
git add templates/clients/form.php
git add templates/clients/form_refactored.php
git add OPTION_A_IMPLEMENTATION.md
git add MODAL_PATTERN_ANALYSIS.md

echo "✅ Files staged for commit"
echo ""

echo "=========================================="
echo "✅ Deployment Complete!"
echo "=========================================="
echo ""
echo "Next Steps:"
echo "1. Test the form at: /public/clients/add.php"
echo "2. Verify:"
echo "   - Modal opens smoothly (no jittering)"
echo "   - All fields are visible"
echo "   - Form validation works"
echo "   - Submission succeeds"
echo "3. If successful, commit:"
echo "   git commit -m 'refactor: Implement Option A (form inside modal) for clients/form.php - eliminates jittering'"
echo "   git push origin main"
echo "4. Roll out to other forms"
echo ""
echo "📚 Documentation:"
echo "   - Implementation guide: OPTION_A_IMPLEMENTATION.md"
echo "   - Pattern analysis: MODAL_PATTERN_ANALYSIS.md"
echo ""
echo "🔄 To rollback:"
echo "   cp templates/clients/form_OLD_*.php templates/clients/form.php"
echo ""
