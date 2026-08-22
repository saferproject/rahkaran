#!/usr/bin/env bash
# End-to-end manual test of the Rahkaran/AvanSeir login flow, replicating what
# App\Services\FinancialVoucherService::login() does — and the exact request
# shape confirmed to work in Postman: raw body, Content-Type: text/plain,
# session cookies carried from the /session call into the /login call.
#
# Usage: scripts/rahkaran_login_test.sh <base_url> <username> <password>
# Example:
#   scripts/rahkaran_login_test.sh "http://172.23.30.11/AvanSeir" "سافر" "a1234567"

set -euo pipefail

BASE_URL="${1:?usage: $0 <base_url> <username> <password>}"
USERNAME="${2:?usage: $0 <base_url> <username> <password>}"
PASSWORD="${3:?usage: $0 <base_url> <username> <password>}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COOKIE_JAR="$(mktemp)"
trap 'rm -f "$COOKIE_JAR"' EXIT

echo "==> 1/3 GET session"
SESSION_JSON="$(curl -sS -c "$COOKIE_JAR" \
  -H "Accept: application/json" \
  "$BASE_URL/Services/Framework/AuthenticationService.svc/session")"
echo "$SESSION_JSON"

SESSION_ID="$(php -r '$d=json_decode($argv[1],true); echo $d["id"];' "$SESSION_JSON")"
MODULUS_HEX="$(php -r '$d=json_decode($argv[1],true); echo $d["rsa"]["M"];' "$SESSION_JSON")"
EXPONENT_HEX="$(php -r '$d=json_decode($argv[1],true); echo $d["rsa"]["E"];' "$SESSION_JSON")"

echo "sessionId=$SESSION_ID"

echo "==> 2/3 encrypting password"
ENCRYPTED_PASSWORD="$(php "$SCRIPT_DIR/rahkaran_encrypt_password.php" "$SESSION_ID" "$MODULUS_HEX" "$EXPONENT_HEX" "$PASSWORD")"
echo "encrypted password=$ENCRYPTED_PASSWORD"

BODY="$(php -r '
echo json_encode([
    "sessionId" => $argv[1],
    "username" => $argv[2],
    "password" => $argv[3],
], JSON_UNESCAPED_UNICODE);
' "$SESSION_ID" "$USERNAME" "$ENCRYPTED_PASSWORD")"

echo "==> 3/3 POST login"
curl -sS -b "$COOKIE_JAR" -c "$COOKIE_JAR" -i \
  -H "Content-Type: text/plain" \
  -H "Accept: application/json" \
  --data-raw "$BODY" \
  "$BASE_URL/Services/Framework/AuthenticationService.svc/login"

echo
echo "==> cookies after login (empty body above + sg-auth* cookie below means success)"
cat "$COOKIE_JAR"
