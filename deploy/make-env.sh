#!/usr/bin/env bash
# Writes deploy/.env for the production compose file (secrets generated here).
# Usage: deploy/make-env.sh <domain> <admin-password>
set -euo pipefail
cd "$(dirname "$0")"
DOMAIN="$1"
PASSWORD="$2"
HASH="$(docker run --rm caddy:2.8 caddy hash-password --plaintext "$PASSWORD" </dev/null)"

cat > .env <<ENV
TATLAS_DOMAIN=$DOMAIN
MAIL_UI_USER=admin
MAIL_UI_HASH='$HASH'

APP_NAME=Tatlas
APP_ENV=staging
APP_DEBUG=false
APP_KEY=base64:$(openssl rand -base64 32)
APP_URL=https://$DOMAIN
APP_LOCALE=it
APP_FALLBACK_LOCALE=it
LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=tatlas
DB_USERNAME=tatlas
DB_PASSWORD=$(openssl rand -hex 24)

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@$DOMAIN
MAIL_FROM_NAME=Tatlas
ENV
chmod 600 .env
echo "deploy/.env written for $DOMAIN"
