# 🚂 Railway Deployment Configuration Guide

## Automated Database Backups

### Overview
The system includes automated PostgreSQL backups that run daily to protect your data.

### Setup Instructions

#### 1. Railway Environment Variables
Ensure these are set in your Railway project (most should already be set):

```bash
DATABASE_HOST=your-railway-postgres-host
DATABASE_PORT=5432
DATABASE_NAME=railway
DATABASE_USER=postgres
DATABASE_PASSWORD=your-password
BACKUP_RETENTION_DAYS=30  # Optional, defaults to 30
```

#### 2. Install PostgreSQL Client Tools

Railway doesn't include `pg_dump` by default. Add this to your `nixpacks.toml`:

```toml
[phases.setup]
aptPkgs = ["postgresql-client"]
```

Or add a `Procfile` command to install it:

```
release: apt-get update && apt-get install -y postgresql-client
```

#### 3. Set Up Cron Job for Automated Backups

Create a file `railway_cron.sh` in your project root:

```bash
#!/bin/bash
# Run backup daily at 2 AM
0 2 * * * /app/scripts/backup_database.sh >> /app/storage/logs/backup.log 2>&1
```

Add to your `Procfile`:

```
web: php -S 0.0.0.0:$PORT -t public
cron: cron -f
```

#### 4. Alternative: Railway Cron Service

If Railway doesn't support cron directly, use **Railway Cron Service**:

1. Create a new service in Railway
2. Set it to run on a schedule
3. Command: `/app/scripts/backup_database.sh`
4. Schedule: `0 2 * * *` (Daily at 2 AM)

### Manual Backup

To manually create a backup:

```bash
railway run bash scripts/backup_database.sh
```

Or via Railway CLI:

```bash
railway connect
cd /app
bash scripts/backup_database.sh
```

### Restore from Backup

To restore a backup:

```bash
railway run bash scripts/restore_database.sh /app/backups/fanders_backup_YYYYMMDD_HHMMSS.sql.gz
```

### Backup Storage Options

#### Option 1: Railway Volumes (Built-in)
- Backups stored in `/app/backups` directory
- Retained for 30 days (configurable)
- **Limitation:** Limited storage space

#### Option 2: Cloud Storage (Recommended for Production)

For production, integrate with cloud storage:

**AWS S3 Integration:**

1. Install AWS CLI in Railway:
```toml
[phases.setup]
aptPkgs = ["postgresql-client", "awscli"]
```

2. Add to `backup_database.sh`:
```bash
# Upload to S3
aws s3 cp "$BACKUP_FILE_GZ" "s3://your-bucket/backups/" \
    --region your-region
```

3. Set environment variables:
```bash
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
```

**Google Cloud Storage Integration:**

1. Install gcloud:
```toml
[phases.setup]
aptPkgs = ["postgresql-client", "google-cloud-sdk"]
```

2. Add to backup script:
```bash
# Upload to GCS
gsutil cp "$BACKUP_FILE_GZ" "gs://your-bucket/backups/"
```

### Monitoring Backups

Check backup logs:

```bash
railway run cat /app/backups/backup.log
```

List all backups:

```bash
railway run ls -lh /app/backups/
```

### Backup Retention Policy

- **Default:** 30 days
- **Customize:** Set `BACKUP_RETENTION_DAYS` environment variable
- Old backups are automatically deleted

### Testing Your Backup System

1. **Create a test backup:**
   ```bash
   railway run bash scripts/backup_database.sh
   ```

2. **Verify backup file exists:**
   ```bash
   railway run ls -lh /app/backups/
   ```

3. **Test restore (on a test database):**
   ```bash
   # Create a test database first
   railway run bash scripts/restore_database.sh /app/backups/latest.sql.gz
   ```

### Troubleshooting

**Issue: pg_dump command not found**
- Solution: Add `postgresql-client` to nixpacks.toml or Procfile

**Issue: Permission denied**
- Solution: Make scripts executable:
  ```bash
  chmod +x scripts/*.sh
  ```

**Issue: Out of disk space**
- Solution: Reduce BACKUP_RETENTION_DAYS or implement cloud storage

**Issue: Backup fails with connection error**
- Solution: Verify DATABASE_* environment variables are correct

### Best Practices

1. ✅ **Test restores regularly** - Backups are useless if they don't restore
2. ✅ **Monitor backup logs** - Check for failures
3. ✅ **Use cloud storage for production** - Local storage has limits
4. ✅ **Keep multiple backup locations** - Don't rely on one storage location
5. ✅ **Document your backup strategy** - Make sure team knows the process

### Emergency Restore Procedure

If you need to restore in an emergency:

1. **Connect to Railway:**
   ```bash
   railway connect
   ```

2. **List available backups:**
   ```bash
   ls -lh /app/backups/
   ```

3. **Choose backup and restore:**
   ```bash
   bash scripts/restore_database.sh /app/backups/fanders_backup_YYYYMMDD_HHMMSS.sql.gz
   ```

4. **Verify restoration:**
   ```bash
   # Check if data is restored
   railway run php test_db_connection.php
   ```

### Automated Monitoring (Optional)

To get notified of backup failures, add this to your backup script:

```bash
# Send email on failure (requires mail command)
if [ $? -ne 0 ]; then
    echo "Backup failed!" | mail -s "Fanders Backup Failure" admin@yourcompany.com
fi
```

Or use a webhook:

```bash
# Send to Slack/Discord/etc
if [ $? -ne 0 ]; then
    curl -X POST -H 'Content-type: application/json' \
        --data '{"text":"Backup failed!"}' \
        YOUR_WEBHOOK_URL
fi
```

---

## Quick Start Checklist

- [ ] Add `postgresql-client` to nixpacks.toml
- [ ] Set all required environment variables in Railway
- [ ] Make backup scripts executable (`chmod +x scripts/*.sh`)
- [ ] Set up cron job or Railway Cron Service
- [ ] Run first manual backup to test
- [ ] Verify backup file is created
- [ ] Test restore on a test environment
- [ ] Set up cloud storage (AWS S3 or GCS) for production
- [ ] Configure monitoring/alerts for backup failures
- [ ] Document backup and restore procedures for your team

---

**Last Updated:** October 22, 2025
**Status:** Ready for deployment ✅
