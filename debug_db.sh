#!/bin/bash

echo "🔍 Database-level debugging for SLR error"
echo "========================================"

# Check generation rules
echo "1. Checking SLR generation rules..."
psql $DATABASE_URL -c "
SELECT rule_name, trigger_event, auto_generate, is_active 
FROM slr_generation_rules 
WHERE trigger_event = 'manual_request';
" 2>/dev/null

echo ""
echo "2. All generation rules:"
psql $DATABASE_URL -c "
SELECT rule_name, trigger_event, is_active 
FROM slr_generation_rules 
ORDER BY id;
" 2>/dev/null

echo ""
echo "3. Finding completed loans..."
psql $DATABASE_URL -c "
SELECT id, principal, status 
FROM loans 
WHERE status = 'completed' 
LIMIT 3;
" 2>/dev/null

echo ""
echo "4. Checking slr_documents table structure..."
psql $DATABASE_URL -c "
\d slr_documents;
" 2>/dev/null

echo ""
echo "5. Recent SLR documents..."
psql $DATABASE_URL -c "
SELECT document_number, loan_id, status, generated_at 
FROM slr_documents 
ORDER BY generated_at DESC 
LIMIT 5;
" 2>/dev/null

echo ""
echo "🎯 Debugging complete!"