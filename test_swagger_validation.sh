#!/bin/bash

# Script de test pour valider les réponses API contre la documentation Swagger
# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

BASE_URL="http://localhost:8000/api"
TOKEN=""
REFRESH_TOKEN=""

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}Test de validation Swagger OMPAY API${NC}"
echo -e "${YELLOW}========================================${NC}\n"

# Test 1: Connexion
echo -e "${YELLOW}1. Test Connexion${NC}"
RESPONSE=$(curl -s -X POST "$BASE_URL/auth/connexion" \
  -H "Content-Type: application/json" \
  -d '{
    "numeroTelephone": "+221771000001",
    "codePin": "0000"
  }')

echo "Réponse:"
echo "$RESPONSE" | jq '.'
echo ""

# Extraire les tokens
TOKEN=$(echo "$RESPONSE" | jq -r '.data.jetonAcces')
REFRESH_TOKEN=$(echo "$RESPONSE" | jq -r '.data.jetonRafraichissement')

# Vérifier les champs
if echo "$RESPONSE" | jq -e '.data.jetonAcces' > /dev/null; then
  echo -e "${GREEN}✓ Champ 'jetonAcces' présent${NC}"
else
  echo -e "${RED}✗ Champ 'jetonAcces' manquant (documentation dit 'token')${NC}"
fi

if echo "$RESPONSE" | jq -e '.data.jetonRafraichissement' > /dev/null; then
  echo -e "${GREEN}✓ Champ 'jetonRafraichissement' présent${NC}"
else
  echo -e "${RED}✗ Champ 'jetonRafraichissement' manquant (documentation dit 'refreshToken')${NC}"
fi

echo -e "\n${YELLOW}2. Test Consulter Profil (avec token)${NC}"
RESPONSE=$(curl -s -X GET "$BASE_URL/utilisateurs/profil" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "Réponse:"
echo "$RESPONSE" | jq '.'
echo ""

echo -e "\n${YELLOW}3. Test Consulter Solde Portefeuille${NC}"
RESPONSE=$(curl -s -X GET "$BASE_URL/portefeuille/solde" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "Réponse:"
echo "$RESPONSE" | jq '.'
echo ""




echo -e "\n${YELLOW}========================================${NC}"
echo -e "${YELLOW}Fin des tests${NC}"
echo -e "${YELLOW}========================================${NC}"
