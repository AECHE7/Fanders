#!/bin/bash
# Automated PostgreSQL Backup Script for Railway Deployment
# This script performs daily backups of the PostgreSQL database

# Configuration - These will be set via Railway environment variables
# Railway provides these variables when you attach a PostgreSQL database
DB_HOST="${PGHOST:-localhost}"
DB_PORT="${PGPORT:-5432}"
DB_NAME="${PGDATABASE:-railway}"
DB_USER="${PGUSER:-postgres}"
DB_PASSWORD="${PGPASSWORD}"
BACKUP_DIR="/app/backups"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Generate backup filename with timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="$BACKUP_DIR/fanders_backup_$TIMESTAMP.sql"
BACKUP_FILE_GZ="$BACKUP_FILE.gz"

# Log file
LOG_FILE="$BACKUP_DIR/backup.log"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log_message "=== Starting backup process ==="
log_message "Database: $DB_NAME on $DB_HOST:$DB_PORT"
log_message "User: $DB_USER"
log_message "Backup directory: $BACKUP_DIR"
log_message "Backup file: $BACKUP_FILE_GZ"

# Check if required environment variables are set
if [ -z "$DB_PASSWORD" ]; then
    log_message "✗ ERROR: Database password not set (PGPASSWORD)"
    exit 1
fi

if [ -z "$DB_HOST" ] || [ "$DB_HOST" = "localhost" ]; then
    log_message "⚠ WARNING: DB_HOST is localhost - this may be incorrect for Railway"
fi

# Perform backup using pg_dump
export PGPASSWORD="$DB_PASSWORD"
if pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
    --verbose --no-owner --no-acl \
    -f "$BACKUP_FILE" 2>> "$LOG_FILE"; then
    
    log_message "✓ Database dump completed successfully"
    
    # Compress the backup
    if gzip "$BACKUP_FILE"; then
        log_message "✓ Backup compressed successfully"
        
        # Get file size
        BACKUP_SIZE=$(du -h "$BACKUP_FILE_GZ" | cut -f1)
        log_message "Backup size: $BACKUP_SIZE"
        
        # Remove old backups (older than RETENTION_DAYS)
        log_message "Cleaning up old backups (older than $RETENTION_DAYS days)..."
        find "$BACKUP_DIR" -name "fanders_backup_*.sql.gz" -type f -mtime +$RETENTION_DAYS -delete
        
        # Count remaining backups
        BACKUP_COUNT=$(find "$BACKUP_DIR" -name "fanders_backup_*.sql.gz" -type f | wc -l)
        log_message "Total backups retained: $BACKUP_COUNT"
        
        log_message "=== Backup completed successfully ==="
        exit 0
    else
        log_message "✗ Failed to compress backup"
        exit 1
    fi
else
    log_message "✗ Database dump failed"
    exit 1
fi

# Unset password
unset PGPASSWORD
