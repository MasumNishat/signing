#!/bin/bash

# Database Backup Script for Signing API
# This script creates a timestamped PostgreSQL backup

# Configuration
DB_NAME="${DB_DATABASE:-signing_api}"
DB_USER="${DB_USERNAME:-postgres}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
BACKUP_DIR="storage/backups/database"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/signing_api_${TIMESTAMP}.sql"
COMPRESSED_FILE="${BACKUP_FILE}.gz"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Database Backup Script${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Create backup directory if it doesn't exist
if [ ! -d "$BACKUP_DIR" ]; then
    echo -e "${YELLOW}Creating backup directory...${NC}"
    mkdir -p "$BACKUP_DIR"
fi

# Check if PostgreSQL is accessible
echo -e "${YELLOW}Checking database connection...${NC}"
if ! pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" > /dev/null 2>&1; then
    echo -e "${RED}Error: Cannot connect to PostgreSQL database${NC}"
    echo -e "${RED}Host: $DB_HOST, Port: $DB_PORT, Database: $DB_NAME${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Database connection successful${NC}"
echo ""

# Create backup
echo -e "${YELLOW}Creating database backup...${NC}"
echo -e "Database: ${GREEN}$DB_NAME${NC}"
echo -e "Output: ${GREEN}$BACKUP_FILE${NC}"
echo ""

if pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -F p -f "$BACKUP_FILE"; then
    echo -e "${GREEN}✓ Backup created successfully${NC}"

    # Compress backup
    echo -e "${YELLOW}Compressing backup...${NC}"
    if gzip -f "$BACKUP_FILE"; then
        BACKUP_SIZE=$(du -h "$COMPRESSED_FILE" | cut -f1)
        echo -e "${GREEN}✓ Backup compressed successfully${NC}"
        echo -e "Compressed size: ${GREEN}$BACKUP_SIZE${NC}"
        echo -e "Location: ${GREEN}$COMPRESSED_FILE${NC}"
    else
        echo -e "${RED}Warning: Compression failed, keeping uncompressed backup${NC}"
        BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        echo -e "Backup size: ${GREEN}$BACKUP_SIZE${NC}"
        echo -e "Location: ${GREEN}$BACKUP_FILE${NC}"
    fi
else
    echo -e "${RED}Error: Backup failed${NC}"
    exit 1
fi

echo ""

# Cleanup old backups (keep last 7 days)
echo -e "${YELLOW}Cleaning up old backups (keeping last 7 days)...${NC}"
find "$BACKUP_DIR" -name "signing_api_*.sql.gz" -type f -mtime +7 -delete
find "$BACKUP_DIR" -name "signing_api_*.sql" -type f -mtime +7 -delete
OLD_BACKUPS=$(find "$BACKUP_DIR" -name "signing_api_*.sql*" -type f | wc -l)
echo -e "${GREEN}✓ Cleanup complete${NC}"
echo -e "Total backups: ${GREEN}$OLD_BACKUPS${NC}"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Backup completed successfully!${NC}"
echo -e "${GREEN}========================================${NC}"
