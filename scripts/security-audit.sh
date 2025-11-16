#!/bin/bash

###############################################################################
# Security Audit Script
#
# Runs automated security checks on the Laravel application
# Checks for common vulnerabilities and configuration issues
#
# Usage: ./scripts/security-audit.sh
#
# Prerequisites:
#   - php installed
#   - composer installed
#   - Application installed and configured
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT_DIR="storage/logs/security"
REPORT_FILE="${REPORT_DIR}/security_audit_${TIMESTAMP}.txt"

# Create report directory
mkdir -p "$REPORT_DIR"

echo -e "${BLUE}=== Security Audit ===${NC}"
echo "Timestamp: $(date)"
echo "Report: $REPORT_FILE"
echo ""

# Initialize report
{
    echo "Security Audit Report"
    echo "====================="
    echo "Timestamp: $(date)"
    echo ""
} > "$REPORT_FILE"

ISSUES_FOUND=0

###############################################################################
# Helper Functions
###############################################################################

pass() {
    echo -e "${GREEN}✓ PASS:${NC} $1"
    echo "[PASS] $1" >> "$REPORT_FILE"
}

fail() {
    echo -e "${RED}✗ FAIL:${NC} $1"
    echo "[FAIL] $1" >> "$REPORT_FILE"
    ((ISSUES_FOUND++))
}

warn() {
    echo -e "${YELLOW}⚠ WARN:${NC} $1"
    echo "[WARN] $1" >> "$REPORT_FILE"
}

info() {
    echo -e "${BLUE}ℹ INFO:${NC} $1"
    echo "[INFO] $1" >> "$REPORT_FILE"
}

section() {
    echo ""
    echo -e "${BLUE}=== $1 ===${NC}"
    echo "" >> "$REPORT_FILE"
    echo "=== $1 ===" >> "$REPORT_FILE"
}

###############################################################################
# Check 1: Environment Configuration
###############################################################################
section "Environment Configuration"

# Check if .env exists
if [ -f ".env" ]; then
    pass ".env file exists"

    # Check APP_DEBUG
    if grep -q "APP_DEBUG=false" .env; then
        pass "APP_DEBUG is set to false (production)"
    elif grep -q "APP_DEBUG=true" .env; then
        warn "APP_DEBUG is set to true (should be false in production)"
    else
        fail "APP_DEBUG not set"
    fi

    # Check APP_KEY
    if grep -q "APP_KEY=base64:" .env; then
        pass "APP_KEY is set"
    else
        fail "APP_KEY is not set or invalid"
    fi

    # Check if .env is in .gitignore
    if grep -q "\.env" .gitignore; then
        pass ".env is in .gitignore"
    else
        fail ".env is NOT in .gitignore (security risk!)"
    fi

else
    fail ".env file does not exist"
fi

###############################################################################
# Check 2: Dependency Vulnerabilities
###############################################################################
section "Dependency Vulnerabilities"

if command -v composer &> /dev/null; then
    info "Running composer audit..."
    if composer audit --no-dev 2>&1 | tee -a "$REPORT_FILE"; then
        pass "No known vulnerabilities in dependencies"
    else
        warn "Vulnerabilities found in dependencies - review output above"
    fi
else
    warn "Composer not found - skipping dependency audit"
fi

###############################################################################
# Check 3: File Permissions
###############################################################################
section "File Permissions"

# Check storage directory
if [ -d "storage" ]; then
    STORAGE_PERMS=$(stat -c "%a" storage 2>/dev/null || stat -f "%A" storage 2>/dev/null)
    if [ "$STORAGE_PERMS" = "755" ] || [ "$STORAGE_PERMS" = "775" ]; then
        pass "storage/ has correct permissions ($STORAGE_PERMS)"
    else
        warn "storage/ has permissions $STORAGE_PERMS (should be 755 or 775)"
    fi
fi

# Check bootstrap/cache directory
if [ -d "bootstrap/cache" ]; then
    CACHE_PERMS=$(stat -c "%a" bootstrap/cache 2>/dev/null || stat -f "%A" bootstrap/cache 2>/dev/null)
    if [ "$CACHE_PERMS" = "755" ] || [ "$CACHE_PERMS" = "775" ]; then
        pass "bootstrap/cache/ has correct permissions ($CACHE_PERMS)"
    else
        warn "bootstrap/cache/ has permissions $CACHE_PERMS (should be 755 or 775)"
    fi
fi

###############################################################################
# Check 4: Sensitive Files Exposure
###############################################################################
section "Sensitive Files Exposure"

# Check if .env is accessible via web
if [ -f "public/.env" ]; then
    fail ".env file is in public directory (CRITICAL SECURITY RISK!)"
else
    pass ".env is not in public directory"
fi

# Check if vendor is accessible via web
if [ -d "public/vendor" ]; then
    warn "vendor directory might be in public (check if necessary)"
else
    pass "vendor directory is not in public"
fi

# Check if .git is accessible via web
if [ -d "public/.git" ]; then
    fail ".git directory is in public (CRITICAL SECURITY RISK!)"
else
    pass ".git directory is not in public"
fi

###############################################################################
# Check 5: Authentication & Authorization
###############################################################################
section "Authentication & Authorization"

# Check if Laravel Passport is installed
if grep -q "laravel/passport" composer.json; then
    pass "Laravel Passport is installed"
else
    warn "Laravel Passport not found (OAuth2 authentication)"
fi

# Check for password hashing
if grep -rq "bcrypt\|Hash::make" app/; then
    pass "Password hashing detected (bcrypt)"
else
    warn "No password hashing detected"
fi

###############################################################################
# Check 6: Security Headers
###############################################################################
section "Security Headers"

# Check if security middleware exists
if [ -f "app/Http/Middleware/SecurityHeaders.php" ]; then
    pass "SecurityHeaders middleware exists"
else
    warn "SecurityHeaders middleware not found"
fi

###############################################################################
# Check 7: Database Security
###############################################################################
section "Database Security"

# Check database connection in .env
if [ -f ".env" ]; then
    if grep -q "DB_PASSWORD=" .env; then
        DB_PASS=$(grep "DB_PASSWORD=" .env | cut -d '=' -f2)
        if [ -z "$DB_PASS" ]; then
            fail "Database password is empty"
        elif [ ${#DB_PASS} -lt 12 ]; then
            warn "Database password is less than 12 characters"
        else
            pass "Database password is set and sufficiently long"
        fi
    fi
fi

###############################################################################
# Check 8: Rate Limiting
###############################################################################
section "Rate Limiting"

# Check for throttle middleware in routes
if grep -rq "throttle:" routes/; then
    pass "Rate limiting (throttle) is configured in routes"
else
    warn "No rate limiting detected in routes"
fi

###############################################################################
# Check 9: CSRF Protection
###############################################################################
section "CSRF Protection"

# Check if VerifyCsrfToken middleware exists
if [ -f "app/Http/Middleware/VerifyCsrfToken.php" ]; then
    pass "VerifyCsrfToken middleware exists"
else
    warn "VerifyCsrfToken middleware not found"
fi

###############################################################################
# Check 10: Error Handling
###############################################################################
section "Error Handling"

# Check if whoops is not in production dependencies
if grep -q "filp/whoops" composer.json; then
    if grep -A 5 "require-dev" composer.json | grep -q "filp/whoops"; then
        pass "whoops is in dev dependencies only"
    else
        warn "whoops is in production dependencies (should be dev only)"
    fi
fi

###############################################################################
# Check 11: Code Quality
###############################################################################
section "Code Quality"

# Check for debug statements
info "Checking for debug statements..."
DEBUG_COUNT=$(grep -r "dd(\|dump(\|var_dump(\|print_r(" app/ | wc -l)
if [ "$DEBUG_COUNT" -eq 0 ]; then
    pass "No debug statements found in app/"
else
    warn "Found $DEBUG_COUNT debug statements in app/ (should remove for production)"
fi

# Check for TODO comments
TODO_COUNT=$(grep -r "TODO\|FIXME\|XXX\|HACK" app/ | wc -l)
if [ "$TODO_COUNT" -eq 0 ]; then
    pass "No TODO/FIXME comments in app/"
else
    info "Found $TODO_COUNT TODO/FIXME comments in app/"
fi

###############################################################################
# Check 12: Logging Configuration
###############################################################################
section "Logging Configuration"

# Check if logs directory is writable
if [ -w "storage/logs" ]; then
    pass "storage/logs is writable"
else
    fail "storage/logs is not writable"
fi

# Check log file size
if [ -f "storage/logs/laravel.log" ]; then
    LOG_SIZE=$(du -h storage/logs/laravel.log | cut -f1)
    info "laravel.log size: $LOG_SIZE"

    # Warn if log file is very large
    LOG_SIZE_MB=$(du -m storage/logs/laravel.log | cut -f1)
    if [ "$LOG_SIZE_MB" -gt 100 ]; then
        warn "laravel.log is larger than 100MB ($LOG_SIZE_MB MB) - consider log rotation"
    fi
fi

###############################################################################
# Generate Summary
###############################################################################
section "Summary"

{
    echo ""
    echo "Total Issues Found: $ISSUES_FOUND"
    echo ""
    echo "Report saved to: $REPORT_FILE"
    echo ""
} | tee -a "$REPORT_FILE"

if [ "$ISSUES_FOUND" -eq 0 ]; then
    echo -e "${GREEN}✓ No critical security issues found!${NC}"
    exit 0
else
    echo -e "${RED}✗ Found $ISSUES_FOUND security issues that need attention${NC}"
    exit 1
fi
