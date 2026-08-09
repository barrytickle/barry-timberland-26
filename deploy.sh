#!/usr/bin/env bash
set -euo pipefail

# Deploy built assets to barrytickle.com (multisite site 5).
# Run from the theme repo root: ./deploy.sh

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

SSH_KEY="${SSH_KEY:-$HOME/.ssh/id_rsa_wordpress}"
SSH_USER="${SSH_USER:-yp58a1p9h3zh}"
SSH_HOST="${SSH_HOST:-92.205.251.126}"
REMOTE_THEME="${REMOTE_THEME:-public_html/wp-content/themes/barry-timberland-26}"

SSH_CMD=(ssh -i "$SSH_KEY" "${SSH_USER}@${SSH_HOST}")
RSYNC_SSH="ssh -i ${SSH_KEY}"

echo "→ Building production assets..."
npm run build

echo "→ Uploading theme/assets/dist/ to server..."
rsync -avz --delete \
	-e "$RSYNC_SSH" \
	theme/assets/dist/ \
	"${SSH_USER}@${SSH_HOST}:~/${REMOTE_THEME}/theme/assets/dist/"

echo "→ Ensuring production config on server..."
"${SSH_CMD[@]}" "printf '%s\n' '{\"vite\":{\"environment\":\"production\"}}' > ~/${REMOTE_THEME}/config.json"

echo "✓ Deploy complete."
