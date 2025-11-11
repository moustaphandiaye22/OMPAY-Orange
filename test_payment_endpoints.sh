#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

BASE_URL="http://localhost:8000/api"
TOKEN=""
TEST_USER_PHONE="+221771000001"
TEST_USER_PIN="0000"

# Test data from database
MERCHANT_ID="76104421-859e-4710-99d7-2800cefa5b0a"
PAYMENT_CODE="2KYFUQ"
QR_DATA="OM_PAY_66d2b15b-fd4b-4bc7-8525-7721f970b824_22068.00_1762858921_ceda1ff69b236bbef052475e3f3f31de"

echo "============================================"
echo "TEST DES ENDPOINTS DE PAIEMENT"
echo "============================================"
echo ""

# 1. Obtenir le token
echo -e "${YELLOW}1️⃣  Obtention du token d'authentification...${NC}"
AUTH_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/connexion" \
  -H "Content-Type: application/json" \
  -d "{
    \"numeroTelephone\": \"$TEST_USER_PHONE\",
    \"codePin\": \"$TEST_USER_PIN\"
  }")

TOKEN=$(echo $AUTH_RESPONSE | jq -r '.data.jetonAcces // empty')
if [ -z "$TOKEN" ]; then
  echo -e "${RED}❌ Erreur: Token non obtenu${NC}"
  echo "Réponse: $AUTH_RESPONSE"
  exit 1
fi
echo -e "${GREEN}✅ Token obtenu${NC}"
echo ""

# Helper function pour faire des requêtes avec le token
function call_api() {
  local method=$1
  local endpoint=$2
  local data=$3

  if [ -z "$data" ]; then
    curl -s -X "$method" "$BASE_URL$endpoint" \
      -H "Authorization: Bearer $TOKEN" \
      -H "Content-Type: application/json"
  else
    curl -s -X "$method" "$BASE_URL$endpoint" \
      -H "Authorization: Bearer $TOKEN" \
      -H "Content-Type: application/json" \
      -d "$data"
  fi
}

# Helper function pour tester un endpoint
function test_endpoint() {
  local method=$1
  local endpoint=$2
  local data=$3
  local description=$4

  echo -e "${BLUE}→ $method $endpoint${NC}"
  echo "Description: $description"

  local response=$(call_api "$method" "$endpoint" "$data")
  local success=$(echo $response | jq -r '.success // false')

  if [ "$success" = "true" ]; then
    echo -e "${GREEN}✅ SUCCÈS${NC}"
    echo "Message: $(echo $response | jq -r '.message // "N/A"')"
    echo $response | jq -r '.data // empty' | head -10
  else
    echo -e "${RED}❌ ÉCHEC${NC}"
    echo "Erreur: $(echo $response | jq -r '.error.message // "Erreur inconnue"')"
    echo $response | jq -r '.error // empty'
  fi
  echo ""
}

# 2. Tester scanner QR
echo -e "${YELLOW}2️⃣  TEST SCANNER QR...${NC}"
test_endpoint "POST" "/paiement/scanner-qr" "{\"donneesQR\":\"$QR_DATA\"}" "Scanner un code QR"

# 3. Tester saisir code
echo -e "${YELLOW}3️⃣  TEST SAISIR CODE...${NC}"
test_endpoint "POST" "/paiement/saisir-code" "{\"code\":\"$PAYMENT_CODE\"}" "Saisir un code de paiement"

# 4. Tester initier paiement
echo -e "${YELLOW}4️⃣  TEST INITIER PAIEMENT...${NC}"
INIT_RESPONSE=$(call_api "POST" "/paiement/initier-paiement" "{\"idMarchand\":\"$MERCHANT_ID\",\"montant\":5000,\"devise\":\"XOF\"}")
PAYMENT_ID=$(echo $INIT_RESPONSE | jq -r '.data.idPaiement // empty')

if [ ! -z "$PAYMENT_ID" ]; then
  echo -e "${GREEN}✅ Paiement initié - ID: $PAYMENT_ID${NC}"
  echo "Message: $(echo $INIT_RESPONSE | jq -r '.message // "N/A"')"
  echo $INIT_RESPONSE | jq -r '.data // empty'
else
  echo -e "${RED}❌ Échec initiation paiement${NC}"
  echo "Réponse: $INIT_RESPONSE"
fi
echo ""

# 5. Tester confirmer paiement (si paiement initié)
if [ ! -z "$PAYMENT_ID" ]; then
  echo -e "${YELLOW}5️⃣  TEST CONFIRMER PAIEMENT...${NC}"
  test_endpoint "POST" "/paiement/$PAYMENT_ID/confirmer-paiement" "{\"codePin\":\"$TEST_USER_PIN\"}" "Confirmer le paiement avec PIN"
else
  echo -e "${YELLOW}⚠️  Test confirmation ignoré (pas d'ID paiement)${NC}"
  echo ""
fi

# 6. Tester annuler paiement (initier un nouveau pour test)
echo -e "${YELLOW}6️⃣  TEST ANNULER PAIEMENT...${NC}"
INIT_CANCEL_RESPONSE=$(call_api "POST" "/paiement/initier-paiement" "{\"idMarchand\":\"$MERCHANT_ID\",\"montant\":2000,\"devise\":\"XOF\"}")
CANCEL_PAYMENT_ID=$(echo $INIT_CANCEL_RESPONSE | jq -r '.data.idPaiement // empty')

if [ ! -z "$CANCEL_PAYMENT_ID" ]; then
  echo -e "${BLUE}→ DELETE /paiement/$CANCEL_PAYMENT_ID/annuler-paiement${NC}"
  echo "Description: Annuler un paiement en attente"

  CANCEL_RESPONSE=$(call_api "DELETE" "/paiement/$CANCEL_PAYMENT_ID/annuler-paiement")
  CANCEL_SUCCESS=$(echo $CANCEL_RESPONSE | jq -r '.success // false')

  if [ "$CANCEL_SUCCESS" = "true" ]; then
    echo -e "${GREEN}✅ SUCCÈS${NC}"
    echo "Message: $(echo $CANCEL_RESPONSE | jq -r '.message // "N/A"')"
  else
    echo -e "${RED}❌ ÉCHEC${NC}"
    echo "Erreur: $(echo $CANCEL_RESPONSE | jq -r '.error.message // "Erreur inconnue"')"
  fi
else
  echo -e "${RED}❌ Impossible d'initier paiement pour test annulation${NC}"
fi
echo ""

# 7. Vérifier le solde après transactions
echo -e "${YELLOW}7️⃣  VÉRIFICATION DU SOLDE APRÈS TRANSACTIONS...${NC}"
BALANCE_RESPONSE=$(call_api "GET" "/portefeuille/solde")
BALANCE=$(echo $BALANCE_RESPONSE | jq -r '.data.solde // "N/A"')
echo "Solde actuel: $BALANCE XOF"
echo ""

# 8. Vérifier l'historique des transactions
echo -e "${YELLOW}8️⃣  HISTORIQUE DES TRANSACTIONS...${NC}"
TRANSACTIONS_RESPONSE=$(call_api "GET" "/portefeuille/transactions")
TRANSACTION_COUNT=$(echo $TRANSACTIONS_RESPONSE | jq -r '.data.transactions | length // 0')
echo "Nombre de transactions: $TRANSACTION_COUNT"
echo $TRANSACTIONS_RESPONSE | jq -r '.data.transactions[0:3] // empty' | head -20
echo ""

echo "============================================"
echo -e "${GREEN}✅ TESTS DES ENDPOINTS PAIEMENT TERMINÉS${NC}"
echo "============================================"