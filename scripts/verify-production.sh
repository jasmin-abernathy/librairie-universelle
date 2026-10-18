#!/usr/bin/env bash
set -euo pipefail
EXPECTED="${1:-}"
BASE="https://librairie.lepotager.org"
if ! [[ "$EXPECTED" =~ ^[a-f0-9]{40}$ ]]; then
  echo "Usage: bash scripts/verify-production.sh <40-char-commit-sha>" >&2
  exit 2
fi
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

fetch() {
  local path="$1" dest="$2" sep='?'
  [[ "$path" == *'?'* ]] && sep='&'
  curl --fail --silent --show-error --location --connect-timeout 8 --max-time 18 \
    --header 'Cache-Control: no-cache' "$BASE$path${sep}verify=$EXPECTED" > "$dest"
}
assert_content() {
  local path="$1" marker="$2" file="$TMP/response"
  fetch "$path" "$file"
  if ! grep -Fq -- "$marker" "$file"; then
    echo "KO $path : marqueur manquant : $marker" >&2
    return 1
  fi
  echo "OK $path"
}
fetch '/deploy-version.php' "$TMP/commit"
DEPLOYED="$(tr -d '\r\n' < "$TMP/commit")"
if [[ "$DEPLOYED" != "$EXPECTED" ]]; then
  echo "KO : GitHub=$EXPECTED mais o2switch=$DEPLOYED" >&2
  exit 1
fi
echo "OK SHA de production $DEPLOYED"
assert_content '/autoedition.php?partie=paper' 'href="/dossier-impression.php"'
assert_content '/dossier-impression.php' 'id="printer-kit-form"'
assert_content '/assistant-papier.php' 'id="paper-save-draft"'
assert_content '/assets/js/printer-kit.js' 'librairie-print-package-v1'
assert_content '/assets/js/author-draft.js' 'AuthorDraft'
assert_content '/assets/js/paper-barcode.js' 'PaperBarcode'
assert_content '/assets/vendor/pagedjs/paged.polyfill.js' '@license Paged.js v0.4.3'
assert_content '/work.php?id=2' 'id="work-access-filters"'
curl --fail --silent --show-error --head --connect-timeout 8 --max-time 18 \
  "$BASE/print-preview.php" > "$TMP/headers"
if ! grep -qi 'Content-Security-Policy:' "$TMP/headers" ||
   ! grep -Fq "connect-src 'none'" "$TMP/headers"; then
  echo "KO : CSP de l'aperçu papier absente" >&2
  exit 1
fi
echo "OK CSP de l'aperçu papier"
echo "Production et version GitHub concordent."
