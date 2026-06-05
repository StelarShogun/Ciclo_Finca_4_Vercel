#!/usr/bin/env bash
# Aggressive JS→TS migration helper (migrate-to-ts-and-react branch).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

DELETE_FILES=(
  resources/js/bootstrap.js
  resources/js/client/clients-home.js
  resources/js/client/clients-catalog.js
  resources/js/client/clients-cart.js
  resources/js/client/clients-product.js
  resources/js/client/clients-users.js
  resources/js/client/clients-page.js
  resources/js/client/clients-auth.js
  resources/js/client/clients-profile.js
  resources/js/client/bundles/cart.js
  resources/js/client/bundles/product.js
  resources/js/client/client-flash.js
  resources/js/client/recovery-success-modal.js
  resources/js/client/register-validation-errors.js
  resources/js/client/invoices-review-modal.js
  resources/js/client/clients-header.js
  resources/js/client/clients-notification-toasts.js
  resources/js/client/clients-invoice-heartbeat.js
  resources/js/client/cart-actions.js
  resources/js/client/auth-welcome-toast.js
)

echo "==> Deleting obsolete client / duplicate JS..."
for f in "${DELETE_FILES[@]}"; do
  if [[ -f "$f" ]]; then
    rm -f "$f"
    echo "  deleted $f"
  fi
done

echo "==> Renaming remaining resources/js/**/*.js → .ts..."
while IFS= read -r -d '' f; do
  ts="${f%.js}.ts"
  if [[ -f "$ts" ]]; then
    echo "  skip (ts exists): $f"
    rm -f "$f"
  else
    mv "$f" "$ts"
    echo "  mv $f → $ts"
  fi
done < <(find resources/js -type f -name '*.js' -print0)

echo "==> Stripping .js extensions from TS/TSX imports..."
find resources/js -type f \( -name '*.ts' -o -name '*.tsx' \) -print0 | while IFS= read -r -d '' f; do
  sed -i \
    -e "s/from '\([^']*\)\.js'/from '\1'/g" \
    -e 's/from "\([^"]*\)\.js"/from "\1"/g' \
    -e "s/import('\([^']*\)\.js')/import('\1')/g" \
    -e 's/import("\([^"]*\)\.js")/import("\1")/g' \
    "$f"
done

echo "==> Updating Blade @vite .js → .ts..."
find resources/views -name '*.blade.php' -print0 | while IFS= read -r -d '' f; do
  if grep -q 'resources/js/.*\.js' "$f" 2>/dev/null; then
    sed -i -E \
      -e "s@(resources/js/[^'\"]+)\.js@\1.ts@g" \
      "$f"
  fi
done

echo "==> Updating vite.config.js .js → .ts for resources/js paths..."
sed -i -E 's@(resources/js/[^"'"'"']+)\.js@\1.ts@g' vite.config.js

echo "Done. Remaining .js under resources/js:"
find resources/js -type f -name '*.js' | sort || true
