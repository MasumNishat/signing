# Database Management Scripts

This directory contains scripts for database backup, restore, and testing.

## Backup Scripts (`backup/`)

### backup-database.sh
Creates a timestamped backup of the PostgreSQL database.

**Usage:**
```bash
./scripts/backup/backup-database.sh
```

**Features:**
- Creates timestamped SQL dump
- Compresses backup with gzip
- Stores in `storage/backups/database/`
- Automatically cleans up backups older than 7 days
- Validates database connection before backup

**Output:**
```
storage/backups/database/signing_api_YYYYMMDD_HHMMSS.sql.gz
```

### restore-database.sh
Restores a database from a backup file.

**Usage:**
```bash
./scripts/backup/restore-database.sh <backup_file>
```

**Example:**
```bash
./scripts/backup/restore-database.sh storage/backups/database/signing_api_20251114_120000.sql.gz
```

**Warning:** This will DROP and recreate the database!

**Features:**
- Lists available backups if no file specified
- Automatic decompression of .gz files
- Safety confirmation prompt
- Drops and recreates database

### automated-backup.sh
Automated backup script for cron scheduling.

**Cron Setup:**
```bash
# Edit crontab
crontab -e

# Add entry for daily backup at 2 AM
0 2 * * * /path/to/signing/scripts/backup/automated-backup.sh >> /path/to/signing/storage/logs/backup.log 2>&1
```

**Features:**
- Loads .env environment variables
- Runs backup-database.sh
- Logs output for monitoring

## Testing Scripts (`testing/`)

### test-database-constraints.sh
Comprehensive test suite for database constraints and relationships.

**Usage:**
```bash
./scripts/testing/test-database-constraints.sh
```

**Tests:**
1. **Table Existence** - Verifies all 66 tables exist
2. **Foreign Key Constraints** - Tests FK relationships and orphan rejection
3. **Unique Constraints** - Verifies unique indexes
4. **Indexes** - Checks index presence on key tables
5. **JSONB Columns** - Validates JSONB data types
6. **Timestamps** - Ensures created_at/updated_at exist
7. **Soft Deletes** - Checks deleted_at columns

**Exit Codes:**
- 0: All tests passed
- 1: Some tests failed

### test-seeders.sh
Tests that all database seeders run successfully.

**Usage:**
```bash
./scripts/testing/test-seeders.sh
```

**Tests:**
- Runs migrations (fresh)
- Runs all seeders
- Verifies seeded data counts:
  - 4 plans
  - 2 accounts
  - 3 users
  - 23 file types
  - 20 languages

**Warning:** This will reset the database!

## Configuration

All scripts use environment variables from `.env`:

```env
DB_DATABASE=signing_api
DB_USERNAME=postgres
DB_HOST=localhost
DB_PORT=5432
```

## Directory Structure

```
scripts/
├── backup/
│   ├── backup-database.sh       # Manual backup
│   ├── restore-database.sh      # Restore from backup
│   └── automated-backup.sh      # Cron automation
└── testing/
    ├── test-database-constraints.sh  # Constraint tests
    └── test-seeders.sh               # Seeder tests
```

## Best Practices

### Backups
- Run daily automated backups via cron
- Test restore procedure regularly
- Keep backups offsite for disaster recovery
- Monitor backup logs for failures

### Testing
- Run constraint tests after migrations
- Run seeder tests before deploying
- Include in CI/CD pipeline
- Test in staging environment first

## Troubleshooting

### Backup fails
- Check PostgreSQL is running: `pg_isready`
- Verify credentials in `.env`
- Ensure disk space: `df -h`
- Check permissions on `storage/backups/database/`

### Restore fails
- Verify backup file exists and is not corrupt
- Check database user has CREATE DATABASE permission
- Ensure PostgreSQL version compatibility
- Review error messages in output

### Test failures
- Check all migrations are up to date: `php artisan migrate:status`
- Verify PostgreSQL is running
- Review specific test failures in output
- Ensure proper database permissions

## Security Notes

- Backup files contain sensitive data - store securely
- Restrict access to backup directory
- Use strong PostgreSQL credentials
- Consider encrypting backups for compliance
- Never commit backups to git (already in .gitignore)

## Support

For issues or questions:
1. Check script output for error messages
2. Review PostgreSQL logs
3. Verify environment configuration
4. Test database connectivity manually
