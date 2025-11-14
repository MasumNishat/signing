#!/bin/bash

# Automated Daily Backup Script for Signing API
# This script should be run via cron for automated backups
#
# Example crontab entry (daily at 2 AM):
# 0 2 * * * /path/to/signing/scripts/backup/automated-backup.sh >> /path/to/signing/storage/logs/backup.log 2>&1

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$PROJECT_ROOT" || exit 1

# Load environment variables
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Run backup script
"$SCRIPT_DIR/backup-database.sh"

# Send notification (optional - implement based on your needs)
# Example: Send email or Slack notification on success/failure
