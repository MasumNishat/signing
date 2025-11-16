#!/bin/bash

###############################################################################
# Performance Testing Script
#
# Runs load tests using Apache Bench (ab) against the API endpoints
# Generates performance reports for response times, throughput, and errors
#
# Usage: ./scripts/performance-test.sh [environment]
#   environment: local|staging|production (default: local)
#
# Prerequisites:
#   - Apache Bench (ab) installed: apt-get install apache2-utils
#   - API server running
#   - Valid access token
###############################################################################

set -e

# Configuration
ENV=${1:-local}
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT_DIR="storage/logs/performance"
REPORT_FILE="${REPORT_DIR}/performance_${TIMESTAMP}.txt"

# Environment-specific base URLs
case $ENV in
    local)
        BASE_URL="http://localhost/api/v2.1"
        ;;
    staging)
        BASE_URL="https://staging-api.example.com/api/v2.1"
        ;;
    production)
        BASE_URL="https://api.example.com/api/v2.1"
        ;;
    *)
        echo "Invalid environment: $ENV"
        echo "Usage: $0 [local|staging|production]"
        exit 1
        ;;
esac

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Create report directory
mkdir -p "$REPORT_DIR"

echo -e "${GREEN}=== Performance Testing ===${NC}"
echo "Environment: $ENV"
echo "Base URL: $BASE_URL"
echo "Report: $REPORT_FILE"
echo ""

# Initialize report
{
    echo "Performance Test Report"
    echo "======================="
    echo "Timestamp: $(date)"
    echo "Environment: $ENV"
    echo "Base URL: $BASE_URL"
    echo ""
} > "$REPORT_FILE"

# Test configuration
CONCURRENCY=10  # Number of concurrent requests
REQUESTS=100    # Total number of requests per endpoint
TIMEOUT=30      # Timeout in seconds

# Get access token (you'll need to implement this based on your auth)
echo -e "${YELLOW}Note: Set ACCESS_TOKEN environment variable for authenticated endpoints${NC}"
ACCESS_TOKEN=${ACCESS_TOKEN:-""}

###############################################################################
# Test Function
###############################################################################
run_test() {
    local name=$1
    local url=$2
    local method=${3:-GET}
    local data=${4:-}

    echo -e "${YELLOW}Testing: $name${NC}"
    echo "  URL: $url"
    echo "  Method: $method"
    echo "  Concurrency: $CONCURRENCY"
    echo "  Requests: $REQUESTS"

    {
        echo ""
        echo "Test: $name"
        echo "-------------------------------------------"
        echo "URL: $url"
        echo "Method: $method"
        echo ""
    } >> "$REPORT_FILE"

    # Build ab command
    AB_CMD="ab -n $REQUESTS -c $CONCURRENCY -s $TIMEOUT"

    # Add auth header if token available
    if [ -n "$ACCESS_TOKEN" ]; then
        AB_CMD="$AB_CMD -H \"Authorization: Bearer $ACCESS_TOKEN\""
    fi

    # Add content type for POST/PUT
    if [ "$method" = "POST" ] || [ "$method" = "PUT" ]; then
        AB_CMD="$AB_CMD -H \"Content-Type: application/json\""
        if [ -n "$data" ]; then
            AB_CMD="$AB_CMD -p <(echo '$data')"
        fi
    fi

    # Execute test
    if eval $AB_CMD "$url" >> "$REPORT_FILE" 2>&1; then
        echo -e "${GREEN}  ✓ Test completed${NC}"
    else
        echo -e "${RED}  ✗ Test failed${NC}"
    fi

    echo ""
}

###############################################################################
# Run Tests
###############################################################################

echo -e "${GREEN}Starting performance tests...${NC}"
echo ""

# Test 1: Health Check (no auth required)
run_test "Health Check" "${BASE_URL}/health" "GET"

# Test 2: Authentication
run_test "Login" "${BASE_URL}/auth/login" "POST" '{"email":"test@example.com","password":"SecurePass123!"}'

# Test 3: List Accounts (requires auth)
if [ -n "$ACCESS_TOKEN" ]; then
    run_test "List Accounts" "${BASE_URL}/accounts" "GET"
fi

# Test 4: List Envelopes (requires auth)
if [ -n "$ACCESS_TOKEN" ]; then
    run_test "List Envelopes" "${BASE_URL}/accounts/1/envelopes" "GET"
fi

# Test 5: Get Account Settings (requires auth)
if [ -n "$ACCESS_TOKEN" ]; then
    run_test "Account Settings" "${BASE_URL}/accounts/1/settings" "GET"
fi

###############################################################################
# Generate Summary
###############################################################################

echo -e "${GREEN}=== Test Summary ===${NC}"
echo ""

{
    echo ""
    echo "=== SUMMARY ==="
    echo "======================="
    echo "Total tests: 5"
    echo "Environment: $ENV"
    echo "Report saved to: $REPORT_FILE"
    echo ""
    echo "Key Metrics to Review:"
    echo "  - Time per request (mean)"
    echo "  - Requests per second"
    echo "  - Failed requests (should be 0)"
    echo "  - 95th percentile response time"
    echo ""
} >> "$REPORT_FILE"

echo "Report saved to: $REPORT_FILE"
echo ""
echo -e "${GREEN}Performance testing complete!${NC}"
echo ""
echo "To view the report:"
echo "  cat $REPORT_FILE"
echo ""
echo "To run authenticated tests, set ACCESS_TOKEN:"
echo "  export ACCESS_TOKEN='your-token-here'"
echo "  ./scripts/performance-test.sh $ENV"
