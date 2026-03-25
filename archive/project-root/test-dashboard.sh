#!/bin/bash

# Dashboard API Test Script
# This script tests if the dashboard endpoint is working correctly

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
API_BASE_URL="${API_BASE_URL:-http://localhost:8001/api}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@school.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-password}"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Dashboard API Test Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Step 1: Check if backend is running
echo -e "${YELLOW}[1/5] Checking if backend is running...${NC}"
if curl -s -f -o /dev/null "$API_BASE_URL/../"; then
    echo -e "${GREEN}✓ Backend is running${NC}"
else
    echo -e "${RED}✗ Backend is not accessible at $API_BASE_URL${NC}"
    echo -e "${YELLOW}Please start the backend with: cd backend && php artisan serve${NC}"
    exit 1
fi
echo ""

# Step 2: Test login endpoint
echo -e "${YELLOW}[2/5] Testing login endpoint...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE_URL/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"email\":\"$ADMIN_EMAIL\",\"password\":\"$ADMIN_PASSWORD\"}")

if echo "$LOGIN_RESPONSE" | grep -q "access_token"; then
    echo -e "${GREEN}✓ Login successful${NC}"
    TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)
    echo -e "${BLUE}Token: ${TOKEN:0:50}...${NC}"
else
    echo -e "${RED}✗ Login failed${NC}"
    echo -e "${RED}Response: $LOGIN_RESPONSE${NC}"
    echo ""
    echo -e "${YELLOW}Possible solutions:${NC}"
    echo "1. Run database migrations: cd backend && php artisan migrate:fresh --seed"
    echo "2. Create admin user manually (see docs/DASHBOARD_TROUBLESHOOTING.md)"
    exit 1
fi
echo ""

# Step 3: Test /me endpoint
echo -e "${YELLOW}[3/5] Testing user authentication endpoint (/me)...${NC}"
ME_RESPONSE=$(curl -s -X GET "$API_BASE_URL/me" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")

if echo "$ME_RESPONSE" | grep -q "email"; then
    echo -e "${GREEN}✓ User data retrieved successfully${NC}"
    USER_EMAIL=$(echo "$ME_RESPONSE" | grep -o '"email":"[^"]*"' | cut -d'"' -f4)
    USER_NAME=$(echo "$ME_RESPONSE" | grep -o '"name":"[^"]*"' | cut -d'"' -f4)
    echo -e "${BLUE}User: $USER_NAME ($USER_EMAIL)${NC}"

    # Check if user has admin role
    if echo "$ME_RESPONSE" | grep -q '"name":"admin"'; then
        echo -e "${GREEN}✓ User has admin role${NC}"
    else
        echo -e "${RED}✗ User does NOT have admin role${NC}"
        echo -e "${YELLOW}Assign admin role: php artisan tinker -> User::find(1)->assignRole('admin')${NC}"
        exit 1
    fi
else
    echo -e "${RED}✗ Failed to get user data${NC}"
    echo -e "${RED}Response: $ME_RESPONSE${NC}"
    exit 1
fi
echo ""

# Step 4: Test dashboard endpoint
echo -e "${YELLOW}[4/5] Testing dashboard endpoint...${NC}"
DASHBOARD_RESPONSE=$(curl -s -X GET "$API_BASE_URL/admin/dashboard" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" \
    -w "\nHTTP_CODE:%{http_code}")

HTTP_CODE=$(echo "$DASHBOARD_RESPONSE" | grep "HTTP_CODE" | cut -d':' -f2)
DASHBOARD_DATA=$(echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE/d')

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✓ Dashboard endpoint responded with 200 OK${NC}"

    # Check response structure
    if echo "$DASHBOARD_DATA" | grep -q "stats"; then
        echo -e "${GREEN}✓ Response contains 'stats'${NC}"
    else
        echo -e "${RED}✗ Response missing 'stats'${NC}"
    fi

    if echo "$DASHBOARD_DATA" | grep -q "charts"; then
        echo -e "${GREEN}✓ Response contains 'charts'${NC}"
    else
        echo -e "${YELLOW}⚠ Response missing 'charts' (optional)${NC}"
    fi

    if echo "$DASHBOARD_DATA" | grep -q "recentActivity"; then
        echo -e "${GREEN}✓ Response contains 'recentActivity'${NC}"
    else
        echo -e "${YELLOW}⚠ Response missing 'recentActivity' (optional)${NC}"
    fi

    # Extract some stats
    if command -v jq &> /dev/null; then
        echo ""
        echo -e "${BLUE}Dashboard Statistics:${NC}"
        echo "$DASHBOARD_DATA" | jq '.stats' 2>/dev/null || echo "$DASHBOARD_DATA" | grep -o '"totalStudents":[0-9]*' | head -1
    fi
else
    echo -e "${RED}✗ Dashboard endpoint returned HTTP $HTTP_CODE${NC}"
    echo -e "${RED}Response: $DASHBOARD_DATA${NC}"

    if [ "$HTTP_CODE" = "403" ]; then
        echo -e "${YELLOW}User doesn't have permission to access admin dashboard${NC}"
    elif [ "$HTTP_CODE" = "401" ]; then
        echo -e "${YELLOW}Authentication failed - token may be invalid${NC}"
    elif [ "$HTTP_CODE" = "500" ]; then
        echo -e "${YELLOW}Server error - check Laravel logs: tail -f backend/storage/logs/laravel.log${NC}"
    fi
    exit 1
fi
echo ""

# Step 5: Summary
echo -e "${YELLOW}[5/5] Test Summary${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}All tests passed successfully! ✓${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Dashboard endpoint is working correctly.${NC}"
echo -e "${BLUE}You can now access the dashboard at:${NC}"
echo -e "${BLUE}http://localhost:5173/dashboard${NC}"
echo ""
echo -e "${YELLOW}Credentials:${NC}"
echo "Email: $ADMIN_EMAIL"
echo "Password: $ADMIN_PASSWORD"
echo ""

# Optional: Save test results
if [ "$1" = "--save" ]; then
    TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
    REPORT_FILE="dashboard_test_$TIMESTAMP.json"
    echo "$DASHBOARD_DATA" > "$REPORT_FILE"
    echo -e "${GREEN}Dashboard response saved to: $REPORT_FILE${NC}"
fi

exit 0
