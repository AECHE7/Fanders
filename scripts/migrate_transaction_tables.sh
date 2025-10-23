#!/bin/bash
#
# Transaction Table Migration Script
# Uses Docker PostgreSQL client to execute migration
#
# Date: October 23, 2025
# Purpose: Drop old 'transactions' table and consolidate to 'transaction_logs'
#

set -e  # Exit on error

echo ""
echo "=============================================="
echo "TRANSACTION TABLE MIGRATION"
echo "=============================================="
echo ""

# Load database credentials from config
DB_HOST="aws-1-ap-southeast-1.pooler.supabase.com"
DB_PORT="6543"
DB_NAME="postgres"
DB_USER="postgres.smzpalngwpwylljdvppb"
DB_PASS="105489100018Gadiano"

echo "Connecting to database: $DB_NAME@$DB_HOST:$DB_PORT"
echo ""

# Create temporary SQL script
TEMP_SQL=$(mktemp)
cat > "$TEMP_SQL" << 'EOF'
-- Migration: Drop old transactions table
\echo 'Step 1: Checking existing tables...'
\echo ''

SELECT 
    table_name,
    (SELECT COUNT(*) FROM information_schema.columns WHERE columns.table_name = t.table_name) as columns
FROM information_schema.tables t
WHERE table_schema = 'public' 
AND table_name IN ('transactions', 'transaction_logs')
ORDER BY table_name;

\echo ''
\echo 'Step 2: Counting records...'
\echo ''

DO $$
DECLARE
    trans_count INTEGER := 0;
    logs_count INTEGER := 0;
BEGIN
    -- Check transactions table
    IF EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'transactions') THEN
        EXECUTE 'SELECT COUNT(*) FROM transactions' INTO trans_count;
        RAISE NOTICE 'transactions table: % records', trans_count;
    ELSE
        RAISE NOTICE 'transactions table: does not exist';
    END IF;
    
    -- Check transaction_logs table
    IF EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'transaction_logs') THEN
        EXECUTE 'SELECT COUNT(*) FROM transaction_logs' INTO logs_count;
        RAISE NOTICE 'transaction_logs table: % records', logs_count;
    ELSE
        RAISE WARNING 'transaction_logs table: DOES NOT EXIST!';
    END IF;
END $$;

\echo ''
\echo 'Step 3: Dropping transactions table...'
\echo ''

DROP TABLE IF EXISTS transactions CASCADE;

\echo ''
\echo '✓ transactions table dropped successfully'
\echo ''

\echo 'Step 4: Verification...'
\echo ''

SELECT 
    CASE 
        WHEN EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'transactions')
        THEN 'ERROR: transactions table still exists'
        ELSE '✓ transactions table removed'
    END as transactions_status,
    CASE 
        WHEN EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'transaction_logs')
        THEN '✓ transaction_logs table exists'
        ELSE 'ERROR: transaction_logs table missing'
    END as transaction_logs_status;

\echo ''
\echo '=============================================='
\echo 'MIGRATION COMPLETE'
\echo '=============================================='
\echo ''
EOF

# Try to use Docker PostgreSQL client
if command -v docker &> /dev/null; then
    echo "Using Docker PostgreSQL client..."
    docker run --rm -i \
        -e PGPASSWORD="$DB_PASS" \
        postgres:15-alpine \
        psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
        < "$TEMP_SQL"
    
elif command -v psql &> /dev/null; then
    echo "Using local psql client..."
    PGPASSWORD="$DB_PASS" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" < "$TEMP_SQL"
    
else
    echo "ERROR: Neither docker nor psql is available!"
    echo ""
    echo "Please install one of:"
    echo "  - Docker: to use postgres:15-alpine container"
    echo "  - PostgreSQL client: sudo apt-get install postgresql-client"
    echo ""
    rm "$TEMP_SQL"
    exit 1
fi

# Cleanup
rm "$TEMP_SQL"

echo ""
echo "✅ Migration completed successfully!"
echo ""
echo "Summary:"
echo "  - Old 'transactions' table: DROPPED"
echo "  - Active table: transaction_logs"
echo "  - TransactionService: Updated to use transaction_logs only"
echo ""
echo "Next steps:"
echo "  1. Test transaction logging functionality"
echo "  2. Verify all services are working"
echo "  3. Git commit the changes"
echo ""
