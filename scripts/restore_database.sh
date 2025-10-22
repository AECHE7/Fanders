#!/bin/bash
# Database Restore Script for Railway Deployment
# Restores a PostgreSQL database from a backup file

# Configuration
DB_HOST="${DATABASE_HOST:-localhost}"
DB_PORT="${DATABASE_PORT:-5432}"
DB_NAME="${DATABASE_NAME:-railway}"
DB_USER="${DATABASE_USER:-postgres}"
DB_PASSWORD="${DATABASE_PASSWORD}"

# Check if backup file is provided
if [ -z "$1" ]; then
    echo "Usage: $0 <backup_file.sql.gz>"
    echo ""
    echo "Available backups:"
    ls -lh /app/backups/fanders_backup_*.sql.gz 2>/dev/null || echo "No backups found"
    exit 1
fi

BACKUP_FILE="$1"

# Check if file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo "Error: Backup file not found: $BACKUP_FILE"
    exit 1
fi

echo "=== Database Restore Process ==="
echo "Database: $DB_NAME on $DB_HOST:$DB_PORT"
echo "Backup file: $BACKUP_FILE"
echo ""
echo "WARNING: This will restore the database from backup."
echo "All current data will be replaced with the backup data."
read -p "Are you sure you want to continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

# Create temporary directory for extraction
TMP_DIR=$(mktemp -d)
TMP_SQL="$TMP_DIR/backup.sql"

# Extract backup if it's gzipped
echo "Extracting backup..."
if [[ "$BACKUP_FILE" == *.gz ]]; then
    gunzip -c "$BACKUP_FILE" > "$TMP_SQL"
else
    cp "$BACKUP_FILE" "$TMP_SQL"
fi

# Restore database
echo "Restoring database..."
export PGPASSWORD="$DB_PASSWORD"

if psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "$TMP_SQL"; then
    echo "✓ Database restored successfully"
    rm -rf "$TMP_DIR"
    exit 0
else
    echo "✗ Database restore failed"
    rm -rf "$TMP_DIR"
    exit 1
fi

unset PGPASSWORD
