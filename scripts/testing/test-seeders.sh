#!/bin/bash

# Database Seeder Test Script
# This script tests that all seeders run successfully

# Configuration
DB_NAME="${DB_DATABASE:-signing_api}"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Database Seeder Test Suite${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

echo -e "${YELLOW}Running migrations (fresh)...${NC}"
if php artisan migrate:fresh --force; then
    echo -e "${GREEN}✓ Migrations completed${NC}"
else
    echo -e "${RED}✗ Migrations failed${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Running seeders...${NC}"
if php artisan db:seed --force; then
    echo -e "${GREEN}✓ Seeders completed${NC}"
else
    echo -e "${RED}✗ Seeders failed${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}Verifying seeded data...${NC}"
echo -e "${BLUE}------------------------${NC}"

# Verify plans
PLANS_COUNT=$(php artisan tinker --execute="echo \DB::table('plans')->count();" 2>/dev/null | tail -1)
echo -ne "Plans: "
if [ "$PLANS_COUNT" -eq 4 ]; then
    echo -e "${GREEN}✓ $PLANS_COUNT plans${NC}"
else
    echo -e "${RED}✗ Expected 4, got $PLANS_COUNT${NC}"
fi

# Verify accounts
ACCOUNTS_COUNT=$(php artisan tinker --execute="echo \DB::table('accounts')->count();" 2>/dev/null | tail -1)
echo -ne "Accounts: "
if [ "$ACCOUNTS_COUNT" -eq 2 ]; then
    echo -e "${GREEN}✓ $ACCOUNTS_COUNT accounts${NC}"
else
    echo -e "${RED}✗ Expected 2, got $ACCOUNTS_COUNT${NC}"
fi

# Verify users
USERS_COUNT=$(php artisan tinker --execute="echo \DB::table('users')->count();" 2>/dev/null | tail -1)
echo -ne "Users: "
if [ "$USERS_COUNT" -eq 3 ]; then
    echo -e "${GREEN}✓ $USERS_COUNT users${NC}"
else
    echo -e "${RED}✗ Expected 3, got $USERS_COUNT${NC}"
fi

# Verify file types
FILE_TYPES_COUNT=$(php artisan tinker --execute="echo \DB::table('file_types')->count();" 2>/dev/null | tail -1)
echo -ne "File Types: "
if [ "$FILE_TYPES_COUNT" -eq 23 ]; then
    echo -e "${GREEN}✓ $FILE_TYPES_COUNT file types${NC}"
else
    echo -e "${RED}✗ Expected 23, got $FILE_TYPES_COUNT${NC}"
fi

# Verify languages
LANGUAGES_COUNT=$(php artisan tinker --execute="echo \DB::table('supported_languages')->count();" 2>/dev/null | tail -1)
echo -ne "Languages: "
if [ "$LANGUAGES_COUNT" -eq 20 ]; then
    echo -e "${GREEN}✓ $LANGUAGES_COUNT languages${NC}"
else
    echo -e "${RED}✗ Expected 20, got $LANGUAGES_COUNT${NC}"
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Seeder tests completed!${NC}"
echo -e "${GREEN}========================================${NC}"
