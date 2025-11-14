#!/bin/bash

# Database Restore Script for Signing API
# This script restores a PostgreSQL backup

# Configuration
DB_NAME="${DB_DATABASE:-signing_api}"
DB_USER="${DB_USERNAME:-postgres}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
BACKUP_DIR="storage/backups/database"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Database Restore Script${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Check if backup file is provided
if [ -z "$1" ]; then
    echo -e "${YELLOW}Available backups:${NC}"
    ls -lh "$BACKUP_DIR"/signing_api_*.sql* 2>/dev/null | awk '{print $9, "(" $5 ")"}'
    echo ""
    echo -e "${RED}Usage: $0 <backup_file>${NC}"
    echo -e "Example: $0 $BACKUP_DIR/signing_api_20251114_120000.sql.gz"
    exit 1
fi

BACKUP_FILE="$1"

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}Error: Backup file not found: $BACKUP_FILE${NC}"
    exit 1
fi

# Decompress if needed
if [[ "$BACKUP_FILE" == *.gz ]]; then
    echo -e "${YELLOW}Decompressing backup...${NC}"
    DECOMPRESSED_FILE="${BACKUP_FILE%.gz}"
    gunzip -k -f "$BACKUP_FILE"
    BACKUP_FILE="$DECOMPRESSED_FILE"
    echo -e "${GREEN}✓ Decompression complete${NC}"
    echo ""
fi

# Warning
echo -e "${RED}WARNING: This will DROP and recreate the database!${NC}"
echo -e "${RED}All current data will be lost!${NC}"
echo -e "Database: ${YELLOW}$DB_NAME${NC}"
echo -e "Backup file: ${YELLOW}$BACKUP_FILE${NC}"
echo ""
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${YELLOW}Restore cancelled${NC}"
    exit 0
fi

echo ""

# Drop existing database
echo -e "${YELLOW}Dropping existing database...${NC}"
dropdb -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" --if-exists "$DB_NAME"
echo -e "${GREEN}✓ Database dropped${NC}"

# Create new database
echo -e "${YELLOW}Creating new database...${NC}"
createdb -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" "$DB_NAME"
echo -e "${GREEN}✓ Database created${NC}"

# Restore backup
echo -e "${YELLOW}Restoring backup...${NC}"
if psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "$BACKUP_FILE" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Restore completed successfully${NC}"
else
    echo -e "${RED}Error: Restore failed${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Database restored successfully!${NC}"
echo -e "${GREEN}========================================${NC}"
