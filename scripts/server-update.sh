#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
cd "$ROOT"

# Le site pointe sur public/ via une réécriture Apache. Avec un umask
# restrictif (600), un nouvel asset CSS/JS existe mais renvoie HTTP 403.
# Corriger uniquement les fichiers SERVIS au public, jamais data/storage/src.
normalize_public_permissions() {
  find "$ROOT/public" -type d ! -perm -005 -exec chmod o+rx '{}' +
  find "$ROOT/public" -type f ! -perm -004 -exec chmod o+r '{}' +
}

record_live_commit() {
  # Public commit marker, not a credential. Written after successful preflight.
  local marker
  marker="$(mktemp "$ROOT/public/.deploy-version.XXXXXX")"
  printf '%s\n' "$(git rev-parse HEAD)" > "$marker"
  chmod 644 "$marker"
  mv -f "$marker" "$ROOT/public/deploy-version.txt"
}

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  echo "ERREUR: le clone de déploiement contient des modifications suivies." >&2
  git status --short >&2
  exit 2
fi

git fetch origin main
CURRENT="$(git rev-parse HEAD)"
TARGET="$(git rev-parse origin/main)"

if [ "$CURRENT" = "$TARGET" ]; then
  normalize_public_permissions
  "$PHP_BIN" bin/preflight.php
  record_live_commit
  echo "Déjà à jour: $CURRENT"
  exit 0
fi

if ! git merge-base --is-ancestor "$CURRENT" "$TARGET"; then
  echo "ERREUR: origin/main n'est pas un fast-forward du live." >&2
  exit 3
fi

TMP="$(mktemp -d)"
cleanup() {
  git worktree remove --force "$TMP" >/dev/null 2>&1 || true
  rm -rf "$TMP"
}
trap cleanup EXIT

git worktree add --detach "$TMP" "$TARGET" >/dev/null

(
  cd "$TMP"
  export DATABASE_PATH="$TMP/data/update-check.sqlite"
  export STORAGE_PATH="$TMP/storage"
  export SELF_PUBLISHING_ENABLED=false
  export FEEDBACK_ENABLED=false
  "$PHP_BIN" bin/preflight.php
  "$PHP_BIN" tests/MvpWorkflowTest.php
  "$PHP_BIN" tests/DiscoverySourcesTest.php
)

cleanup
trap - EXIT

( umask 022; git merge --ff-only "$TARGET" )
normalize_public_permissions
"$PHP_BIN" bin/preflight.php
record_live_commit

echo "Déployé: $(git rev-parse HEAD)"
