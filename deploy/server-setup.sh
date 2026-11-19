#!/usr/bin/env bash
# Sets up a fresh Ubuntu 24.04 VM as a public Tatlas test server, end to end:
# Docker, the app stack with HTTPS on <ip>.sslip.io, admin account, OSM import,
# optional demo data, and automatic updates from the git branch.
#
# Meant to run as root from cloud-init "user data" (see docs/DEPLOY.md):
#   export ADMIN_EMAIL=... ADMIN_PASSWORD=... [DEMO=1] [BRANCH=...] [TATLAS_DOMAIN=...]
#   curl -fsSL https://raw.githubusercontent.com/<repo>/<branch>/deploy/server-setup.sh -o /root/tatlas-setup.sh
#   bash /root/tatlas-setup.sh
# (download first: piping into bash would let commands read the rest of the script)
set -euo pipefail
exec > >(tee -a /var/log/tatlas-setup.log) 2>&1

REPO_URL="${REPO_URL:-https://github.com/gianluigiribezzo3582-al/Tatlas.git}"
BRANCH="${BRANCH:-ccr-5fc7a7c3-55si3b}"
DIR=/opt/tatlas
DEMO="${DEMO:-1}"
: "${ADMIN_EMAIL:?set ADMIN_EMAIL}"
: "${ADMIN_PASSWORD:?set ADMIN_PASSWORD (min 12 characters)}"
[[ ${#ADMIN_PASSWORD} -ge 12 ]] || { echo "ADMIN_PASSWORD must be at least 12 characters"; exit 1; }

echo "== Tatlas setup started $(date -Is)"
export DEBIAN_FRONTEND=noninteractive

# --- system --------------------------------------------------------------------
apt-get update -y
apt-get install -y ca-certificates curl git openssl
if ! command -v docker >/dev/null; then
  curl -fsSL https://get.docker.com | sh
fi
if ! swapon --show | grep -q .; then        # composer/npm builds on 2-4 GB VMs
  fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
  echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi
if command -v ufw >/dev/null && ufw status | grep -q active; then
  ufw allow 22/tcp && ufw allow 80/tcp && ufw allow 443/tcp && ufw allow 443/udp
fi

# --- code ----------------------------------------------------------------------
if [[ -d $DIR/.git ]]; then
  git -C $DIR fetch origin "$BRANCH" && git -C $DIR reset --hard "origin/$BRANCH"
else
  git clone --branch "$BRANCH" "$REPO_URL" $DIR
fi
cd $DIR/deploy

# --- configuration (generated once, never committed) ---------------------------
if [[ ! -f .env ]]; then
  IP="$(curl -4 -fsS https://api.ipify.org || hostname -I | awk '{print $1}')"
  ./make-env.sh "${TATLAS_DOMAIN:-${IP//./-}.sslip.io}" "$ADMIN_PASSWORD"
fi
DOMAIN="$(grep '^TATLAS_DOMAIN=' .env | cut -d= -f2)"

# --- start ---------------------------------------------------------------------
docker compose -f docker-compose.prod.yml up -d --build </dev/null

app() { docker compose -f docker-compose.prod.yml exec -T app "$@" </dev/null; }
echo "== waiting for the database and migrations"
for i in $(seq 1 60); do app php artisan migrate --force >/dev/null 2>&1 && break; sleep 5; done
app php artisan migrate --force
app php artisan db:seed --class=StyleSeeder --force
app php artisan tatlas:make-admin "$ADMIN_EMAIL" --password="$ADMIN_PASSWORD"
app php artisan tatlas:import-osm || echo "!! OSM import failed (Overpass busy?) — the weekly schedule will retry"
if [[ "$DEMO" == 1 ]] && ! app php artisan tinker --execute='exit(App\Models\Artist::where("slug","like","demo-%")->exists() ? 0 : 1);' >/dev/null 2>&1; then
  app php artisan tatlas:demo-data
fi

# --- automatic updates from the branch (every 10 minutes) ----------------------
cat > /etc/systemd/system/tatlas-update.service <<UNIT
[Unit]
Description=Update Tatlas from git
After=docker.service network-online.target
[Service]
Type=oneshot
Environment=BRANCH=$BRANCH
ExecStart=$DIR/deploy/update.sh
UNIT
cat > /etc/systemd/system/tatlas-update.timer <<UNIT
[Unit]
Description=Check for Tatlas updates every 10 minutes
[Timer]
OnBootSec=5min
OnUnitActiveSec=10min
[Install]
WantedBy=timers.target
UNIT
chmod +x $DIR/deploy/update.sh
systemctl daemon-reload
systemctl enable --now tatlas-update.timer

echo "== Tatlas is up: https://$DOMAIN  (admin: https://$DOMAIN/admin · mail: https://$DOMAIN/mail · user 'admin')"
echo "== setup finished $(date -Is)"
