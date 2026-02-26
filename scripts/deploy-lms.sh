#!/usr/bin/env bash
# Deploy Tutor81 LMS sul server Vultr (corsi.tutor81.com)
# Uso: ./scripts/deploy-lms.sh
# Oppure: SERVER_PATH=/var/www/tutor81-lms ./scripts/deploy-lms.sh

set -e
SERVER_USER="${DEPLOY_USER:-root}"
SERVER_HOST="${DEPLOY_HOST:-45.32.154.126}"
SERVER_PATH="${DEPLOY_PATH:-/root/tutor81-lms}"
PM2_NAME="${PM2_APP:-tutor81-lms}"

echo "=== Deploy LMS su $SERVER_USER@$SERVER_HOST ==="
echo "Percorso sul server: $SERVER_PATH"
echo ""

ssh "$SERVER_USER@$SERVER_HOST" "cd $SERVER_PATH && git pull && npm run build && pm2 restart $PM2_NAME"

echo ""
echo "=== Deploy completato. Controlla: https://corsi.tutor81.com ==="
