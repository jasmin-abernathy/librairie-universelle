#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
cd "$ROOT"

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  echo "ERREUR: le clone de déploiement contient des modifications suivies." >&2
  git status --short >&2
  exit 2
fi

git fetch origin main
CURRENT="$(git rev-parse HEAD)"
TARGET="$(git rev-parse origin/main)"

if [ "$CURRENT" = "$TARGET" ]; then
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
)

cleanup
trap - EXIT

git merge --ff-only "$TARGET"
"$PHP_BIN" bin/preflight.php

echo "Déployé: $(git rev-parse HEAD)"
