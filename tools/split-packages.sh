#!/usr/bin/env bash
# Mirrors every packages/*/* subdirectory into its own GitHub repository
# (fellipuscampos/phpmodern-<slug>) via `git subtree split`, so each
# phpmodern/* package can be installed on its own instead of pulling the
# whole monorepo. Re-run this after merging changes to keep the mirrors
# current — it force-pushes `main` on each mirror (safe: those repos are
# generated output, never hand-edited) but never touches existing tags, so
# cutting a new released version for a package is still a deliberate,
# separate step (see the versioning note in README.md's Distribution section).
#
# Requires: git, gh (authenticated with `repo` scope for the target account).
# Usage: tools/split-packages.sh [github-owner]
set -euo pipefail

OWNER="${1:-fellipuscampos}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

declare -A PACKAGES=(
  [packages/bridge/adapter]="phpmodern/bridge-adapter"
  [packages/core/auth]="phpmodern/auth"
  [packages/core/authorization]="phpmodern/authorization"
  [packages/core/cache]="phpmodern/cache"
  [packages/core/component-engine]="phpmodern/component-engine"
  [packages/core/config]="phpmodern/config"
  [packages/core/console]="phpmodern/console"
  [packages/core/container]="phpmodern/container"
  [packages/core/error-handler]="phpmodern/error-handler"
  [packages/core/events]="phpmodern/events"
  [packages/core/http]="phpmodern/http"
  [packages/core/i18n]="phpmodern/i18n"
  [packages/core/logging]="phpmodern/logging"
  [packages/core/mail]="phpmodern/mail"
  [packages/core/notifications]="phpmodern/notifications"
  [packages/core/orm]="phpmodern/orm"
  [packages/core/push-hub]="phpmodern/push-hub"
  [packages/core/queue]="phpmodern/queue"
  [packages/core/rate-limiting]="phpmodern/rate-limiting"
  [packages/core/scheduler]="phpmodern/scheduler"
  [packages/core/security]="phpmodern/security"
  [packages/core/storage]="phpmodern/storage"
  [packages/core/store]="phpmodern/store"
  [packages/core/testing]="phpmodern/testing"
  [packages/core/templating]="phpmodern/templating"
  [packages/core/typing-contracts]="phpmodern/typing-contracts"
  [packages/core/validation]="phpmodern/validation"
  [packages/devtools/debugbar]="phpmodern/debugbar"
  [packages/devtools/dev-server]="phpmodern/dev-server"
  [packages/framework/kernel]="phpmodern/kernel"
)

for dir in "${!PACKAGES[@]}"; do
  name="${PACKAGES[$dir]}"
  slug="${name#phpmodern/}"
  repo="phpmodern-${slug}"
  branch="split-${slug}"

  echo "=== ${name} (${dir}) -> ${OWNER}/${repo} ==="

  git branch -D "$branch" >/dev/null 2>&1 || true
  git subtree split --prefix="$dir" -b "$branch"

  if ! gh repo view "${OWNER}/${repo}" >/dev/null 2>&1; then
    desc=$(grep -m1 '"description"' "$dir/composer.json" | sed -E 's/.*"description": *"([^"]*)".*/\1/')
    suffix=" Part of the phpmodern framework (github.com/${OWNER}/phpmodern)."
    full="${name}: ${desc}${suffix}"
    # GitHub repo descriptions are capped at 350 characters.
    short="${full:0:349}"
    gh repo create "${OWNER}/${repo}" --public --description "$short"
  fi

  git push --force "https://github.com/${OWNER}/${repo}.git" "${branch}:main"
  git branch -D "$branch"

  echo "OK: ${repo}"
done

echo "=== Done. Existing tags were left untouched — bump a package's version with a new tag manually when you cut a release. ==="
