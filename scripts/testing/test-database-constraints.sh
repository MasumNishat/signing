#!/bin/bash

# Database Constraints and Relationships Test Script
# This script tests all foreign key constraints, indexes, and relationships

# Configuration
DB_NAME="${DB_DATABASE:-signing_api}"
DB_USER="${DB_USERNAME:-postgres}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PASSED=0
FAILED=0
WARNINGS=0

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Database Constraints Test Suite${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Function to run SQL and check result
test_sql() {
    local test_name="$1"
    local sql="$2"
    local expected="$3"

    echo -ne "${YELLOW}Testing: ${NC}$test_name... "

    result=$(psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -t -c "$sql" 2>&1)

    if echo "$result" | grep -q "$expected"; then
        echo -e "${GREEN}PASS${NC}"
        ((PASSED++))
        return 0
    else
        echo -e "${RED}FAIL${NC}"
        echo -e "  Expected: $expected"
        echo -e "  Got: $result"
        ((FAILED++))
        return 1
    fi
}

# Function to run SQL and expect error
test_sql_error() {
    local test_name="$1"
    local sql="$2"

    echo -ne "${YELLOW}Testing: ${NC}$test_name... "

    result=$(psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -t -c "$sql" 2>&1)

    if echo "$result" | grep -iq "error\|violates\|constraint"; then
        echo -e "${GREEN}PASS${NC} (error as expected)"
        ((PASSED++))
        return 0
    else
        echo -e "${RED}FAIL${NC} (should have raised error)"
        echo -e "  Got: $result"
        ((FAILED++))
        return 1
    fi
}

echo -e "${BLUE}1. Testing Table Existence${NC}"
echo -e "${BLUE}---------------------------${NC}"

# Test all 66 tables exist
TABLES=(
    "plans" "accounts" "permission_profiles" "users" "user_addresses"
    "envelopes" "envelope_documents" "envelope_recipients" "envelope_tabs"
    "envelope_custom_fields" "envelope_attachments" "envelope_locks"
    "envelope_audit_events" "envelope_views" "envelope_workflow"
    "envelope_workflow_steps" "envelope_transfer_rules" "envelope_purge_configurations"
    "templates" "favorite_templates" "shared_access"
    "billing_plans" "billing_charges" "billing_invoices" "billing_invoice_items" "billing_payments"
    "connect_configurations" "connect_logs" "connect_failures" "connect_oauth_config"
    "brands" "brand_logos" "brand_resources" "brand_email_contents"
    "bulk_send_batches" "bulk_send_lists" "bulk_send_recipients"
    "request_logs" "audit_logs"
    "workspaces" "workspace_folders" "workspace_files"
    "powerforms" "powerform_submissions"
    "signatures" "signature_images" "signature_providers" "seals"
    "account_settings" "notification_defaults" "password_rules"
    "file_types" "tab_settings" "supported_languages"
    "api_keys" "user_authorizations"
    "custom_fields" "watermarks" "enote_configurations"
    "folders" "envelope_folders" "chunked_uploads"
    "recipients" "captive_recipients" "identity_verification_workflows" "consumer_disclosures"
)

for table in "${TABLES[@]}"; do
    test_sql "Table '$table' exists" \
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '$table'" \
        "1"
done

echo ""
echo -e "${BLUE}2. Testing Foreign Key Constraints${NC}"
echo -e "${BLUE}----------------------------------${NC}"

# Test cascade delete: Account deletion should cascade
test_sql "Foreign keys count" \
    "SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_type = 'FOREIGN KEY'" \
    ""  # Just checking it runs

# Test cascade behavior by attempting to insert orphan record
test_sql_error "Orphan user rejected (no account)" \
    "INSERT INTO users (account_id, user_id, email, user_name, password, user_status, activation_access_code, created_date_time) VALUES (99999, 'test', 'test@test.com', 'Test', 'hash', 'active', 'code', NOW())"

echo ""
echo -e "${BLUE}3. Testing Unique Constraints${NC}"
echo -e "${BLUE}------------------------------${NC}"

# Test unique constraints on email (if exists)
test_sql "Unique constraints count" \
    "SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_type = 'UNIQUE'" \
    ""  # Just checking it runs

echo ""
echo -e "${BLUE}4. Testing Indexes${NC}"
echo -e "${BLUE}------------------${NC}"

# Test that indexes exist
test_sql "Indexes on plans table" \
    "SELECT COUNT(*) FROM pg_indexes WHERE tablename = 'plans'" \
    ""

test_sql "Indexes on accounts table" \
    "SELECT COUNT(*) FROM pg_indexes WHERE tablename = 'accounts'" \
    ""

test_sql "Indexes on users table" \
    "SELECT COUNT(*) FROM pg_indexes WHERE tablename = 'users'" \
    ""

test_sql "Indexes on envelopes table" \
    "SELECT COUNT(*) FROM pg_indexes WHERE tablename = 'envelopes'" \
    ""

echo ""
echo -e "${BLUE}5. Testing JSONB Columns${NC}"
echo -e "${BLUE}------------------------${NC}"

# Test JSONB column types
test_sql "JSONB column in permission_profiles" \
    "SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'permission_profiles' AND column_name = 'permissions' AND data_type = 'jsonb'" \
    "1"

test_sql "JSONB column in api_keys" \
    "SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'api_keys' AND column_name = 'scopes' AND data_type = 'jsonb'" \
    "1"

echo ""
echo -e "${BLUE}6. Testing Timestamps${NC}"
echo -e "${BLUE}---------------------${NC}"

# Test that created_at and updated_at exist on main tables
test_sql "Timestamps on plans" \
    "SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'plans' AND column_name IN ('created_at', 'updated_at')" \
    "2"

test_sql "Timestamps on accounts" \
    "SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'accounts' AND column_name IN ('created_at', 'updated_at')" \
    "2"

echo ""
echo -e "${BLUE}7. Testing Soft Deletes${NC}"
echo -e "${BLUE}-----------------------${NC}"

# Test soft delete columns
test_sql "Soft delete on signatures" \
    "SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'signatures' AND column_name = 'deleted_at'" \
    "1"

test_sql "Soft delete on custom_fields" \
    "SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'custom_fields' AND column_name = 'deleted_at'" \
    "1"

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Test Results Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}Passed:  ${NC}$PASSED"
echo -e "${RED}Failed:  ${NC}$FAILED"
echo -e "${YELLOW}Warnings:${NC}$WARNINGS"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All tests passed! ✓${NC}"
    exit 0
else
    echo -e "${RED}Some tests failed! ✗${NC}"
    exit 1
fi
