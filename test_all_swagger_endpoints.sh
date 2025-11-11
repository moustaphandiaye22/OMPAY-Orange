#!/usr/bin/env bash
set -euo pipefail
SPEC="storage/api-docs/api-docs.json"
if [ ! -f "$SPEC" ]; then
  echo "Spec $SPEC introuvable"
  exit 1
fi
# couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

BASE_URL=$(jq -r '.servers[0].url // "http://localhost:8000/api"' "$SPEC")
echo -e "${YELLOW}Base URL: $BASE_URL${NC}\n"

# Obtention d'un token via /auth/connexion avec données de test connues
echo -e "${YELLOW}1) Obtention d'un jeton via /auth/connexion${NC}"
CONN_PAYLOAD='{ "numeroTelephone": "+221771000001", "codePin": "0000" }'
CONN_R=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/auth/connexion" -H "Content-Type: application/json" -d "$CONN_PAYLOAD")
HTTP=$(echo "$CONN_R" | tail -n1)
BODY=$(echo "$CONN_R" | sed '$d')
TOKEN=$(echo "$BODY" | jq -r '.data.jetonAcces // empty') || true
if [ -n "$TOKEN" ]; then
  echo -e "${GREEN}✓ Token obtenu (début): ${TOKEN:0:20}...${NC}\n"
else
  echo -e "${RED}✗ Impossible d'obtenir un token. La plupart des endpoints authentifiés échoueront.${NC}\n"
fi

# utilitaires pour construire payloads
sample_uuid="a053e8c2-225f-4c83-ad2d-610cfec446c7"

# fonction pour produire une valeur d'exemple pour une propriété
get_example_value(){
  prop_path="$1" # jq path returning the property object
  example=$(jq -r "$prop_path + \"|EXAMPLE_SEPARATOR|\"" "$SPEC" 2>/dev/null || true)
}

# Itération sur chaque path/méthode
echo -e "${YELLOW}2) Exécution des endpoints listés dans la spec...${NC}\n"

# Iterate over paths
jq -c '.paths | to_entries[]' "$SPEC" | while read -r entry; do
  path=$(echo "$entry" | jq -r '.key')
  methods_json=$(echo "$entry" | jq -c '.value')
  # iterate methods available under this path
  echo "$methods_json" | jq -c 'to_entries[]' | while read -r mentry; do
    method=$(echo "$mentry" | jq -r '.key' | tr '[:lower:]' '[:upper:]')
    op=$(echo "$mentry" | jq -c '.value')
    summary=$(echo "$op" | jq -r '.summary // "-"')
    requires_auth=$(echo "$op" | jq -e 'has("security")' >/dev/null 2>&1 && echo "yes" || echo "no")

    # Build URL: replace path params with sample values
    url_path="$path"
    # replace any {param} with sample_uuid or 123 or sample phone
    url_path=$(echo "$url_path" | sed -E "s/\{[^}]+\}/$sample_uuid/g")
    url="$BASE_URL${url_path}"

    # Build request
    headers=("-H" "Content-Type: application/json")
    if [ "$requires_auth" = "yes" ] && [ -n "$TOKEN" ]; then
      headers+=("-H" "Authorization: Bearer $TOKEN")
    fi

    # Determine request body if exists (use op which contains the operation object)
    has_reqbody=$(echo "$op" | jq -e 'has("requestBody")' >/dev/null 2>&1 && echo "yes" || echo "no")
    data=''
    if [ "$has_reqbody" = "yes" ]; then
      # extract properties keys from the operation
      props=$(echo "$op" | jq -r '.requestBody.content["application/json"].schema.properties // {} | keys[]' 2>/dev/null || true)
      json_obj="{}"
      if [ -n "$props" ]; then
        json_obj="{"
        first=true
        echo "$props" | while read -r prop; do
          # get example from the operation schema if present
          example=$(echo "$op" | jq -r --arg p "$prop" '.requestBody.content["application/json"].schema.properties[$p].example // empty' 2>/dev/null || true)
          if [ -z "$example" ] || [ "$example" = "null" ]; then
            # fallback by name/type heuristics
            case "$prop" in
              *numero*|*telephone*) example="+221771000001" ;;
              *codePin*|*code_pin*|*codepin*|*pin*) example="0000" ;;
              *montant*|*amount*) example=1000 ;;
              *email*) example="tmp.user@example.com" ;;
              *id*|*uuid*) example="$sample_uuid" ;;
              *devise*|*currency*) example="FCFA" ;;
              *donneesQR*|*donneesQr*) example="data:image/png;base64,AAA..." ;;
              *) example="example" ;;
            esac
          fi
          # decide whether to quote
          if [[ "$example" =~ ^[0-9]+$ ]]; then
            val=$example
          else
            # escape quotes
            esc=$(echo "$example" | sed 's/"/\\"/g')
            val="\"$esc\""
          fi
          if [ "$first" = true ]; then
            json_obj+="\"$prop\":$val"
            first=false
          else
            json_obj+=" , \"$prop\":$val"
          fi
        done
        json_obj+="}"
      fi
      data="$json_obj"
    fi

    # Execute request
    echo -e "${YELLOW}--> $method $path : $summary${NC}"
    if [ -n "$data" ]; then
      echo "Request body: $data"
      resp=$(curl -s -w "\n%{http_code}" -X "$method" "$url" "${headers[@]}" -d "$data")
    else
      resp=$(curl -s -w "\n%{http_code}" -X "$method" "$url" "${headers[@]}")
    fi
    http_code=$(echo "$resp" | tail -n1)
    body=$(echo "$resp" | sed '$d')
    if [[ "$http_code" =~ ^2 ]]; then
      echo -e "${GREEN}HTTP $http_code OK${NC}"
    else
      echo -e "${RED}HTTP $http_code${NC}"
    fi
    echo "Body:"
    echo "$body" | jq '.' 2>/dev/null || echo "$body"
    echo "----------------------------------------\n"
  done
done

echo -e "${YELLOW}Fin des tests Swagger automatisés${NC}"
