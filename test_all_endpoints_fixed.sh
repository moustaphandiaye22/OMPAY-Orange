#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

BASE_URL="http://localhost:8000/api"
PASSED=0
FAILED=0

echo "=========================================="
echo "OMPAY API Comprehensive Test Suite"
echo "=========================================="

# Get authentication token
echo -e "\n${YELLOW}[1/6] Acquiring authentication token...${NC}"
TOKEN=$(curl -s -X POST "$BASE_URL/auth/connexion" \
  -H "Content-Type: application/json" \
  -d '{"numeroTelephone":"+221771000001","codePin":"0000"}' | jq -r '.data.jetonAcces')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
  echo -e "${RED}✗ Failed to get token${NC}"
  exit 1
fi
echo -e "${GREEN}✓ Token acquired: ${TOKEN:0:20}...${NC}"

# Helper function to test an endpoint
test_endpoint() {
  local method=$1
  local endpoint=$2
  local data=$3
  local description=$4

  local response=$(curl -s -w "\n%{http_code}" -X "$method" "$BASE_URL$endpoint" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d "$data")

  local http_code=$(echo "$response" | tail -n1)
  local body=$(echo "$response" | sed '$d')

  if [[ $http_code =~ ^[2][0-9]{2}$ ]]; then
    echo -e "${GREEN}✓ $method $endpoint (HTTP $http_code)${NC}"
    echo "  Description: $description"
    PASSED=$((PASSED+1))
    return 0
  else
    echo -e "${RED}✗ $method $endpoint (HTTP $http_code)${NC}"
    echo "  Description: $description"
    echo "  Response: $(echo "$body" | head -c 100)..."
    FAILED=$((FAILED+1))
    return 1
  fi
}

# Section 1: User & Authentication
echo -e "\n${YELLOW}[2/6] Testing User & Authentication endpoints...${NC}"
test_endpoint "GET" "/utilisateurs/profil" "" "Get user profile"
test_endpoint "POST" "/auth/deconnexion" '{}' "Logout user"

# Section 2: Wallet/Portefeuille
echo -e "\n${YELLOW}[3/6] Testing Portefeuille (Wallet) endpoints...${NC}"
test_endpoint "GET" "/portefeuille/solde" "" "Get wallet balance"
test_endpoint "GET" "/portefeuille/transactions" "" "Get transaction history"

# Section 3: Contacts

# Section 4: Transfers
echo -e "\n${YELLOW}[5/6] Testing Transfer endpoints...${NC}"
test_endpoint "POST" "/transfert/effectuer-transfert" '{"telephoneDestinataire":"+221771234567","montant":500,"devise":"XOF","codePin":"0000","note":"Test transfer 2"}' "Perform transfer"

# Section 5: Payments
echo -e "\n${YELLOW}[6/6] Testing Payment endpoints...${NC}"
test_endpoint "GET" "/paiement/categories" "" "List payment categories"

# Summary
echo -e "\n=========================================="
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo "=========================================="

if [ $FAILED -eq 0 ]; then
  echo -e "${GREEN}All tests passed!${NC}"
  exit 0
else
  echo -e "${RED}Some tests failed.${NC}"
  exit 1
fi
