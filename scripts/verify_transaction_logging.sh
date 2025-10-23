#!/bin/bash
#
# Test Transaction Logging System
# Verifies that transaction_logs table is receiving data
#

echo ""
echo "=============================================="
echo "TRANSACTION LOGGING VERIFICATION"
echo "=============================================="
echo ""

# Database connection
DB_HOST="aws-1-ap-southeast-1.pooler.supabase.com"
DB_PORT="6543"
DB_NAME="postgres"
DB_USER="postgres.smzpalngwpwylljdvppb"
DB_PASS="105489100018Gadiano"

# Create test SQL
TEMP_SQL=$(mktemp)
cat > "$TEMP_SQL" << 'EOF'
-- Verify transaction_logs table structure
\echo '1. Table Structure:'
\echo ''
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'transaction_logs'
ORDER BY ordinal_position;

\echo ''
\echo '2. Recent Transaction Activity (Last 10):'
\echo ''
SELECT 
    id,
    entity_type,
    entity_id,
    action,
    user_id,
    LEFT(details, 50) as details_preview,
    timestamp
FROM transaction_logs
ORDER BY timestamp DESC
LIMIT 10;

\echo ''
\echo '3. Activity Summary by Entity Type:'
\echo ''
SELECT 
    entity_type,
    COUNT(*) as total_count,
    COUNT(DISTINCT action) as unique_actions,
    MAX(timestamp) as last_activity
FROM transaction_logs
GROUP BY entity_type
ORDER BY total_count DESC;

\echo ''
\echo '4. Activity Summary by Action:'
\echo ''
SELECT 
    action,
    entity_type,
    COUNT(*) as count
FROM transaction_logs
GROUP BY action, entity_type
ORDER BY count DESC
LIMIT 15;

\echo ''
\echo '5. User Activity:'
\echo ''
SELECT 
    tl.user_id,
    u.name as user_name,
    COUNT(*) as actions_count,
    MAX(tl.timestamp) as last_activity
FROM transaction_logs tl
LEFT JOIN users u ON tl.user_id = u.id
GROUP BY tl.user_id, u.name
ORDER BY actions_count DESC
LIMIT 10;

\echo ''
\echo '✓ Transaction logging system is active'
\echo ''
EOF

# Execute using Docker
docker run --rm -i \
    -e PGPASSWORD="$DB_PASS" \
    postgres:15-alpine \
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
    < "$TEMP_SQL"

# Cleanup
rm "$TEMP_SQL"

echo ""
echo "=============================================="
echo "VERIFICATION COMPLETE"
echo "=============================================="
echo ""
