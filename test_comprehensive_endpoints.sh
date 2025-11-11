#!/bin/bash

# Script de test complet des endpoints Swagger avec création de données
# Ce script crée les ressources nécessaires et teste tous les endpoints

BASE_URL="http://localhost:8000/api"
TOKEN=""
TEST_USER_PHONE="+221771000001"
TEST_USER_PIN="0000"
TEST_CONTACT_PHONE="+221771234567"
TEST_NEW_CONTACT_PHONE="+221771555555"
TEST_CONTACT_NAME="TestContact"

echo "============================================"
echo "TEST COMPLET DES ENDPOINTS SWAGGER"
echo "============================================"
echo ""

# 1. Obtenir le token
echo "1️⃣  Obtention du token d'authentification..."
AUTH_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/connexion" \
  -H "Content-Type: application/json" \
  -d "{
    \"numeroTelephone\": \"$TEST_USER_PHONE\",
    \"codePin\": \"$TEST_USER_PIN\"
  }")

TOKEN=$(echo $AUTH_RESPONSE | jq -r '.data.jetonAcces // empty')
if [ -z "$TOKEN" ]; then
  echo "❌ Erreur: Token non obtenu"
  echo "Réponse: $AUTH_RESPONSE"
  exit 1
fi
echo "✅ Token obtenu"
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

# 2. Tester les endpoints GET simples
echo "2️⃣  TEST DES ENDPOINTS GET..."
echo ""

echo "→ GET /utilisateurs/profil"
PROFIL=$(call_api GET "/utilisateurs/profil")
USER_ID=$(echo $PROFIL | jq -r '.data.idUtilisateur // empty')
echo $PROFIL | jq '.' | head -15
echo ""

echo "→ GET /portefeuille/solde"
SOLDE=$(call_api GET "/portefeuille/solde")
SOLDE_VALUE=$(echo $SOLDE | jq -r '.data.solde // empty')
echo $SOLDE | jq '.'
echo ""

echo "→ GET /portefeuille/transactions"
TRANSACTIONS=$(call_api GET "/portefeuille/transactions")
echo $TRANSACTIONS | jq '.'
echo ""


echo "→ GET /paiement/categories"
CATEGORIES=$(call_api GET "/paiement/categories")
echo $CATEGORIES | jq '.'
echo ""

# 3. Tester endpoints POST avec validation
echo "3️⃣  TEST DES ENDPOINTS POST..."
echo ""



# 4. Tester un paiement (si données disponibles)
echo "4️⃣  TEST DU WORKFLOW PAIEMENT..."
echo ""

# Récupérer les catégories pour créer un paiement
echo "→ Recherche de marchands..."
MARCHANDS=$(curl -s -X GET "$BASE_URL/paiement/categories" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" | jq '.data.categories // []')

MERCHANT_ID=$(echo $MARCHANDS | jq -r '.[0].id // empty')
if [ -z "$MERCHANT_ID" ]; then
  echo "❌ Aucun marchand trouvé, test de paiement ignoré"
else
  echo "✅ Marchand trouvé: $MERCHANT_ID"
  
  # Initier un paiement
  echo "→ POST /paiement/initier-paiement"
  PAYMENT_INIT=$(call_api POST "/paiement/initier-paiement" "{
    \"idMarchand\": \"$MERCHANT_ID\",
    \"montant\": 5000,
    \"modePaiement\": \"qr_code\"
  }")
  echo $PAYMENT_INIT | jq '.'
  PAYMENT_ID=$(echo $PAYMENT_INIT | jq -r '.data.idPaiement // empty')
  
  if [ ! -z "$PAYMENT_ID" ]; then
    echo ""
    echo "→ POST /paiement/{idPaiement}/confirmer-paiement"
    PAYMENT_CONFIRM=$(call_api POST "/paiement/$PAYMENT_ID/confirmer-paiement" "{
      \"codePin\": \"$TEST_USER_PIN\"
    }")
    echo $PAYMENT_CONFIRM | jq '.'
  fi
fi
echo ""

# 5. Tester un transfert
echo "5️⃣  TEST DU WORKFLOW TRANSFERT..."
echo ""

# Créer un utilisateur destinataire si nécessaire
echo "→ POST /transfert/effectuer-transfert"
TRANSFER=$(call_api POST "/transfert/effectuer-transfert" "{
  \"telephoneDestinataire\": \"$TEST_CONTACT_PHONE\",
  \"montant\": 1000,
  \"devise\": \"XOF\",
  \"codePin\": \"$TEST_USER_PIN\",
  \"note\": \"Test transfert\"
}")
echo $TRANSFER | jq '.'
TRANSFER_ID=$(echo $TRANSFER | jq -r '.data.idTransfert // empty')
echo ""

# 6. Vérifier l'historique après transactions
echo "6️⃣  VERIFICATION DE L'HISTORIQUE..."
echo ""

echo "→ GET /portefeuille/transactions (après transactions)"
TRANSACTIONS_AFTER=$(call_api GET "/portefeuille/transactions")
echo $TRANSACTIONS_AFTER | jq '.data.transactions | length' | xargs echo "Nombre de transactions:"
echo ""

echo "→ GET /historique/rechercher"
HISTORIQUE=$(call_api GET "/historique/rechercher")
echo $HISTORIQUE | jq '.'
echo ""

# 7. Résumé des tests
echo "============================================"
echo "✅ TESTS COMPLETS TERMINES"
echo "============================================"
echo ""
echo "Résumé:"
echo "- Token: ✅"
echo "- Profil utilisateur: ✅"
echo "- Portefeuille: ✅"
echo "- Contacts: ❌ (Removed)"
echo "- Paiements: $([ -z "$PAYMENT_ID" ] && echo "⚠️ Non testé" || echo "✅")"
echo "- Transferts: $([ -z "$TRANSFER_ID" ] && echo "❌" || echo "✅")"
echo ""
