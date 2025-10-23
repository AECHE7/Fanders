#!/bin/bash
# Production Migration Script for Supabase
# Run this script to create the required tables on your production database

echo "🚀 Production Database Migration for Collection Sheets & SLR"
echo "=================================================="

# Database connection details for Supabase
SUPABASE_HOST="aws-1-ap-southeast-1.pooler.supabase.com"
SUPABASE_PORT="6543"
SUPABASE_DB="postgres"
SUPABASE_USER="postgres.smzpalngwpwylljdvppb"

# Note: Password should be set as environment variable for security
# export PGPASSWORD="your_password_here"

echo "📋 Connecting to Supabase database..."
echo "Host: $SUPABASE_HOST"
echo "Database: $SUPABASE_DB"
echo ""

# Check if psql is available
if command -v psql &> /dev/null; then
    echo "✅ psql found - executing migration..."
    
    # Run the migration
    psql -h "$SUPABASE_HOST" -p "$SUPABASE_PORT" -U "$SUPABASE_USER" -d "$SUPABASE_DB" -f database/migrations/create_collection_sheets_tables.sql
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "🎉 Migration completed successfully!"
        echo "✅ Tables created: collection_sheets, collection_sheet_items, document_archive"
        echo "✅ Indexes created for optimal performance"
        echo ""
        echo "Your production database is now ready for Collection Sheets and SLR features!"
    else
        echo "❌ Migration failed. Please check the error messages above."
        exit 1
    fi
else
    echo "❌ psql not found on this system."
    echo ""
    echo "📝 Alternative options:"
    echo "1. Install PostgreSQL client: apt-get install postgresql-client"
    echo "2. Use Supabase SQL Editor:"
    echo "   - Go to your Supabase dashboard"
    echo "   - Open SQL Editor"
    echo "   - Copy and paste the contents of database/migrations/create_collection_sheets_tables.sql"
    echo "   - Run the SQL"
    echo ""
    echo "3. Use the web-based migration:"
    echo "   - Deploy this code to a server with PHP and pdo_pgsql"
    echo "   - Run: php setup_database_web.php"
    exit 1
fi