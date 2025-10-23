# Production Migration Guide

## Issue Resolution
The error you're seeing:
```
Warning: Undefined property: DocumentArchiveService::$pdo
Fatal error: Call to a member function prepare() on null
```

This occurs because:
1. Railway deployment may be using cached/older code
2. Production database doesn't have the required tables (`collection_sheets`, `collection_sheet_items`, `document_archive`)

## Steps to Fix

### 1. Wait for Deployment (5-10 minutes)
Railway should automatically redeploy with the latest code changes we just pushed.

### 2. Run Database Migration

You have several options to create the required tables:

#### Option A: Using Supabase Dashboard (Recommended)
1. Go to your [Supabase Dashboard](https://supabase.com/dashboard)
2. Select your project
3. Go to SQL Editor
4. Copy the contents of `database/migrations/create_collection_sheets_tables.sql`
5. Paste and run the SQL

#### Option B: Using psql command line
```bash
# Set password as environment variable for security
export PGPASSWORD="105489100018Gadiano"

# Run the migration script
./deploy_production_migration.sh
```

#### Option C: Using PHP migration (if you have server access)
```bash
# On a server with PHP and pdo_pgsql extension
php setup_database_web.php
```

### 3. Verify the Fix
After running the migration, test the SLR generation:
- Go to: https://fanders-production.up.railway.app/public/slr/generate.php?loan_id=14
- Should work without the PDO error

## Tables Created
- `collection_sheets` - Main collection sheet records
- `collection_sheet_items` - Individual payment items  
- `document_archive` - SLR document storage and tracking

## Files Changed
- ✅ `app/services/DocumentArchiveService.php` - Fixed PDO references
- ✅ `app/models/CollectionSheetModel.php` - Added missing methods
- ✅ Database migration scripts created and tested

The fix should resolve both the "Failed to create collection sheet" and DocumentArchiveService PDO errors.